<?php
/**
 * Extension
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Core\Recurring;
use Pronamic\WordPress\Pay\Core\Statuses;
use Pronamic\WordPress\Pay\Payments\Payment;
use RCP_Member;
use RCP_Payments;

/**
 * Extension
 *
 * @author  Reüel van der Steege
 * @version 2.0.1
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
		// The "plugins_loaded" is one of the earliest hooks after EDD is set up.
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

		add_filter( 'rcp_payment_status_label', array( $this, 'rcp_status_label_cancelled' ), 10, 2 );

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
			'class'       => __NAMESPACE__ . '\\' . $class,
		);
	}

	/**
	 * Get Pronamic gateways.
	 *
	 * @return array
	 */
	public function get_gateways() {
		return array(
			'pronamic_pay'                         => $this->get_gateway_data( __( 'Pay', 'pronamic_ideal' ), 'Gateway' ),
			'pronamic_pay_bancontact'              => $this->get_gateway_data( __( 'Bancontact', 'pronamic_ideal' ), 'BancontactGateway' ),
			'pronamic_pay_banktransfer'            => $this->get_gateway_data( __( 'Bank Transfer', 'pronamic_ideal' ), 'BankTransferGateway' ),
			'pronamic_pay_bitcoin'                 => $this->get_gateway_data( __( 'Bitcoin', 'pronamic_ideal' ), 'BitcoinGateway' ),
			'pronamic_pay_credit_card'             => $this->get_gateway_data( __( 'Credit Card', 'pronamic_ideal' ), 'CreditCardGateway' ),
			'pronamic_pay_direct_debit'            => $this->get_gateway_data( __( 'Direct Debit', 'pronamic_ideal' ), 'DirectDebitGateway' ),
			'pronamic_pay_direct_debit_bancontact' => $this->get_gateway_data( __( 'Direct Debit mandate via Bancontact', 'pronamic_ideal' ), 'DirectDebitBancontactGateway' ),
			'pronamic_pay_direct_debit_ideal'      => $this->get_gateway_data( __( 'Direct Debit mandate via iDEAL', 'pronamic_ideal' ), 'DirectDebitIDealGateway' ),
			'pronamic_pay_direct_debit_sofort'     => $this->get_gateway_data( __( 'Direct Debit mandate via SOFORT', 'pronamic_ideal' ), 'DirectDebitSofortGateway' ),
			'pronamic_pay_ideal'                   => $this->get_gateway_data( __( 'iDEAL', 'pronamic_ideal' ), 'IDealGateway' ),
			'pronamic_pay_paypal'                  => $this->get_gateway_data( __( 'PayPal', 'pronamic_ideal' ), 'PayPalGateway' ),
			'pronamic_pay_sofort'                  => $this->get_gateway_data( __( 'SOFORT', 'pronamic_ideal' ), 'SofortGateway' ),
		);
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
	 * Restrict Content Pro payment status 'Cancelled' label.
	 *
	 * @param string $label  Label.
	 * @param string $status Status.
	 *
	 * @return string
	 */
	public function rcp_status_label_cancelled( $label, $status ) {
		switch ( $status ) {
			case 'cancelled':
				$label = _x( 'Cancelled', 'Payment status', 'pronamic_ideal' );

				break;
			case 'expired':
				$label = _x( 'Expired', 'Payment status', 'pronamic_ideal' );

				break;
			case 'failed':
				$label = _x( 'Failed', 'Payment status', 'pronamic_ideal' );

				break;
		}

		return $label;
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
		$source_id = $payment->get_source_id();

		$data = new PaymentData( $source_id, array() );

		$url = $data->get_normal_return_url();

		switch ( $payment->get_status() ) {
			case Statuses::CANCELLED:
				$url = $data->get_cancel_url();

				break;
			case Statuses::EXPIRED:
				$url = $data->get_error_url();

				break;
			case Statuses::FAILURE:
				$url = $data->get_error_url();

				break;
			case Statuses::SUCCESS:
				$url = $data->get_success_url();

				break;
			case Statuses::OPEN:
				// Nothing to do?
				break;
		}

		return $url;
	}

	/**
	 * Update the status of the specified payment.
	 *
	 * @param Payment $payment Payment.
	 */
	public function status_update( Payment $payment ) {
		$source_id = $payment->get_source_id();

		$payments    = new RCP_Payments();
		$rcp_payment = $payments->get_payment( $source_id );

		// Only update if order is not completed.
		if ( ! $payment->get_subscription() && RestrictContentPro::PAYMENT_STATUS_COMPLETE === $rcp_payment->status ) {
			return;
		}

		$data = new PaymentData( $source_id, array() );

		$member = new RCP_Member( $data->get_user_id() );

		switch ( $payment->get_status() ) {
			case Statuses::CANCELLED:
				$payments->update( $source_id, array( 'status' => RestrictContentPro::PAYMENT_STATUS_CANCELLED ) );

				if ( $member ) {
					$member->cancel();
				}

				break;
			case Statuses::EXPIRED:
				$payments->update( $source_id, array( 'status' => RestrictContentPro::PAYMENT_STATUS_EXPIRED ) );

				if ( $member ) {
					$member->cancel();
				}

				break;
			case Statuses::FAILURE:
				$payments->update( $source_id, array( 'status' => RestrictContentPro::PAYMENT_STATUS_FAILED ) );

				if ( $member ) {
					$member->cancel();
				}

				break;
			case Statuses::SUCCESS:
				$payments->update( $source_id, array( 'status' => RestrictContentPro::PAYMENT_STATUS_COMPLETE ) );

				if ( $member && ( ! is_callable( array( $member, 'get_pending_payment_id' ) ) || Recurring::RECURRING === $payment->recurring_type ) ) {
					$auto_renew = false;

					if ( 'yes' === get_user_meta( $data->get_user_id(), 'rcp_recurring', true ) ) {
						$auto_renew = true;
					}

					$member->renew( $auto_renew, 'active' );
				}

				break;
			case Statuses::OPEN:
				// Nothing to do?
				break;
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
					__( 'Subscription activated by %s', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;

			case 'cancelled':
				$status = Statuses::CANCELLED;

				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription canceled by %s', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;

			case 'free':
			case 'expired':
				$status = Statuses::COMPLETED;

				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription completed by %s', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;

			case 'pending':
				$status = Statuses::OPEN;

				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription pending by %s', 'pronamic_ideal' ),
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
		return __( 'Restrict Content Pro Order', 'pronamic_ideal' );
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
			'user_id',
			$payment->post->post_author,
			menu_page_url( 'rcp-payments', false )
		);

		return $url;
	}
}
