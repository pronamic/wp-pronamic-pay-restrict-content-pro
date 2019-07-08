<?php
/**
 * Extension
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Core\Recurring;
use Pronamic\WordPress\Pay\Core\Statuses;
use Pronamic\WordPress\Pay\Payments\Payment;
use RCP_Member;
use RCP_Payments;
use WP_Query;

/**
 * Extension
 *
 * @author  Reüel van der Steege
 * @version 2.1.1
 * @since   1.0.0
 */
class Extension {
	/**
	 * Bootstrap.
	 */
	public static function bootstrap() {
		$extension = new self();
		$extension->setup();
	}

	/**
	 * Setup.
	 */
	public function setup() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	/**
	 * Plugins loaded.
	 */
	public function plugins_loaded() {
		add_filter( 'pronamic_payment_source_description_restrictcontentpro', array( $this, 'source_description' ), 10, 2 );
		add_filter( 'pronamic_payment_source_url_restrictcontentpro', array( $this, 'source_url' ), 10, 2 );

		// Test to see if the Restrict Content Pro plugin is active, then add all actions.
		if ( ! RestrictContentPro::is_active() ) {
			return;
		}

		add_filter( 'rcp_payment_gateways', array( $this, 'register_pronamic_gateways' ) );
		add_action( 'rcp_payments_settings', array( $this, 'payments_settings' ) );

		add_action( 'pronamic_payment_status_update_restrictcontentpro', array( $this, 'status_update' ), 10, 1 );
		add_filter( 'pronamic_payment_redirect_url_restrictcontentpro', array( $this, 'redirect_url' ), 10, 2 );
		add_filter( 'pronamic_payment_source_text_restrictcontentpro', array( $this, 'source_text' ), 10, 2 );

		add_action( 'rcp_set_status', array( $this, 'rcp_set_status' ), 10, 3 );
	}

	/**
	 * Register Pronamic gateways.
	 *
	 * @param array $gateways Gateways.
	 * @return array
	 */
	public function register_pronamic_gateways( $gateways ) {
		return array_merge( $gateways, $this->get_gateways() );
	}

	/**
	 * Get gateway data.
	 *
	 * @param string $label Label.
	 * @param string $class Class.
	 * @return array
	 */
	private function get_gateway_data( $label, $class ) {
		return array(
			'label'       => $label,
			'admin_label' => sprintf(
				'%s - %s',
				__( 'Pronamic', 'pronamic_ideal' ),
				$label
			),
			'class'       => $class,
		);
	}

	/**
	 * Get Pronamic gateways.
	 *
	 * @return array
	 */
	public function get_gateways() {
		$gateways = array(
			'pronamic_pay'              => $this->get_gateway_data( __( 'Pay', 'pronamic_ideal' ), Gateway::class ),
			'pronamic_pay_bancontact'   => $this->get_gateway_data( __( 'Bancontact', 'pronamic_ideal' ), BancontactGateway::class ),
			'pronamic_pay_banktransfer' => $this->get_gateway_data( __( 'Bank Transfer', 'pronamic_ideal' ), BankTransferGateway::class ),
			'pronamic_pay_bitcoin'      => $this->get_gateway_data( __( 'Bitcoin', 'pronamic_ideal' ), BitcoinGateway::class ),
			'pronamic_pay_credit_card'  => $this->get_gateway_data( __( 'Credit Card', 'pronamic_ideal' ), CreditCardGateway::class ),
			'pronamic_pay_direct_debit' => $this->get_gateway_data( __( 'Direct Debit', 'pronamic_ideal' ), DirectDebitGateway::class ),
			'pronamic_pay_ideal'        => $this->get_gateway_data( __( 'iDEAL', 'pronamic_ideal' ), IDealGateway::class ),
			'pronamic_pay_paypal'       => $this->get_gateway_data( __( 'PayPal', 'pronamic_ideal' ), PayPalGateway::class ),
			'pronamic_pay_sofort'       => $this->get_gateway_data( __( 'SOFORT', 'pronamic_ideal' ), SofortGateway::class ),
		);

		// Add direct debit recurring gateways only if no level set or level duration is not forever.
		global $rcp_level;

		$level = rcp_get_subscription_details( $rcp_level );

		if ( empty( $rcp_level ) || ! ( is_object( $level ) && isset( $level->duration ) && '0' === $level->duration ) ) {
			$gateways = array_merge(
				$gateways,
				array(
					'pronamic_pay_direct_debit_bancontact' => $this->get_gateway_data( __( 'Direct Debit mandate via Bancontact', 'pronamic_ideal' ), DirectDebitBancontactGateway::class ),
					'pronamic_pay_direct_debit_ideal'      => $this->get_gateway_data( __( 'Direct Debit mandate via iDEAL', 'pronamic_ideal' ), DirectDebitIDealGateway::class ),
					'pronamic_pay_direct_debit_sofort'     => $this->get_gateway_data( __( 'Direct Debit mandate via SOFORT', 'pronamic_ideal' ), DirectDebitSofortGateway::class ),
				)
			);
		}

		return $gateways;
	}

	/**
	 * Payment settings.
	 *
	 * @param array $rcp_options Restrict Content Pro options.
	 */
	public function payments_settings( $rcp_options ) {
		foreach ( $this->get_gateways() as $data ) {
			$gateway = new $data['class']();

			$gateway->payments_settings( $rcp_options );
		}
	}

	/**
	 * Payment redirect URL filter.
	 *
	 * @param string  $url     URL.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public function redirect_url( $url, $payment ) {
		if ( Statuses::SUCCESS !== $payment->get_status() ) {
			return $url;
		}

		// Return success page URL.
		return rcp_get_return_url( $payment->get_customer()->get_user_id() );
	}

	/**
	 * Update the status of the specified payment.
	 *
	 * @global RCP_Payments $rcp_payments_db Restrict Content Pro payments object.
	 * @param Payment $payment Payment.
	 */
	public function status_update( Payment $payment ) {
		global $rcp_payments_db;

		$source_id = $payment->get_source_id();

		$rcp_payment = $rcp_payments_db->get_payment( $source_id );

		// Only update if order is not completed.
		if ( RestrictContentPro::PAYMENT_STATUS_COMPLETE === $rcp_payment->status ) {
			return;
		}

		$member = new RCP_Member( $payment->get_customer()->get_user_id() );

		switch ( $payment->get_status() ) {
			case Statuses::CANCELLED:
				$rcp_payments_db->update( $source_id, array( 'status' => RestrictContentPro::PAYMENT_STATUS_CANCELLED ) );

				break;
			case Statuses::EXPIRED:
				$rcp_payments_db->update( $source_id, array( 'status' => RestrictContentPro::PAYMENT_STATUS_EXPIRED ) );

				break;
			case Statuses::FAILURE:
				$rcp_payments_db->update( $source_id, array( 'status' => RestrictContentPro::PAYMENT_STATUS_FAILED ) );

				break;
			case Statuses::SUCCESS:
				$rcp_payments_db->update( $source_id, array( 'status' => RestrictContentPro::PAYMENT_STATUS_COMPLETE ) );

				$subscription = $payment->get_subscription();

				$recurring = empty( $subscription ) ? false : true;

				if ( ! is_callable( array( $member, 'get_pending_payment_id' ) ) || Recurring::RECURRING === $payment->recurring_type ) {
					$member->renew( $recurring, 'active' );
				} else {
					$member->set_recurring( $recurring );
				}

				$this->cancel_other_subscriptions( $payment );

				break;
			case Statuses::OPEN:
				// Nothing to do?
				break;
		}
	}

	/**
	 * Cancel other Restrict Content Pro subscription.
	 *
	 * @param Payment $payment Payment.
	 */
	public function cancel_other_subscriptions( $payment ) {
		$args = array(
			'post_type'     => 'pronamic_pay_subscr',
			'post_status'   => 'any',
			'author'        => $payment->get_customer()->get_user_id(),
			'meta_query'    => array(
				array(
					'key'   => '_pronamic_subscription_source',
					'value' => 'restrictcontentpro',
				),
			),
			'no_found_rows' => true,
			'order'         => 'DESC',
			'orderby'       => 'ID',
		);

		// Check if there is a subscription, make sure we don't cancel this.
		$subscription = $payment->get_subscription();

		if ( $subscription ) {
			$args['post__not_in'] = array(
				$subscription->get_id(),
			);
		}

		// Query.
		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$subscription = get_pronamic_subscription( get_the_ID() );

				if ( $subscription ) {
					// @todo Add note to subscription with info why subscription is cancelled?
					$subscription->set_status( Statuses::CANCELLED );

					$subscription->save();
				}
			}

			wp_reset_postdata();
		}
	}

	/**
	 * Restrict Content Pro user subscription status updated.
	 *
	 * @param string     $new_status New status.
	 * @param string|int $user_id    User ID.
	 * @param string     $old_status Old status.
	 */
	public function rcp_set_status( $new_status, $user_id, $old_status ) {
		$subscription = Util::get_subscription_by_user( $user_id );

		if ( ! $subscription ) {
			return;
		}

		if ( $new_status === $old_status ) {
			return;
		}

		$note = null;

		switch ( $new_status ) {
			case 'active':
				$status = Statuses::ACTIVE;

				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription activated by %s.', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;

			case 'cancelled':
				$status = Statuses::CANCELLED;

				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription canceled by %s.', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;

			case 'free':
			case 'expired':
				$status = Statuses::COMPLETED;

				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription completed by %s.', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;

			case 'pending':
				$status = Statuses::OPEN;

				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription pending by %s.', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;
		}

		if ( isset( $status ) ) {
			$subscription->set_status( $status );

			$subscription->add_note( $note );

			$subscription->save();
		}
	}

	/**
	 * Source column
	 *
	 * @param string  $text    Text.
	 * @param Payment $payment Payment.
	 *
	 * @return string $text
	 */
	public function source_text( $text, Payment $payment ) {
		$text = __( 'Restrict Content Pro', 'pronamic_ideal' ) . '<br />';

		$source_url = add_query_arg(
			'user_id',
			$payment->post->post_author,
			menu_page_url( 'rcp-payments', false )
		);

		$text .= sprintf(
			'<a href="%s">%s</a>',
			esc_url( $source_url ),
			/* translators: %s: source id */
			sprintf( __( 'Payment %s', 'pronamic_ideal' ), $payment->source_id )
		);

		return $text;
	}

	/**
	 * Source description.
	 *
	 * @param string  $description Description.
	 * @param Payment $payment     Payment.
	 *
	 * @return string
	 */
	public function source_description( $description, Payment $payment ) {
		return __( 'Restrict Content Pro Payment', 'pronamic_ideal' );
	}

	/**
	 * Source URL.
	 *
	 * @param string  $url     URL.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public function source_url( $url, Payment $payment ) {
		$url = add_query_arg(
			array(
				'page'       => 'rcp-payments',
				'view'       => 'edit-payment',
				'payment_id' => $payment->source_id,
			),
			admin_url( 'admin.php' )
		);

		return $url;
	}
}
