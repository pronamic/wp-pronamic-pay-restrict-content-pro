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
		add_filter( 'pronamic_payment_source_description', array( $this, 'payment_source_description' ), 10, 2 );
		add_filter( 'pronamic_payment_source_url', array( $this, 'payment_source_url' ), 10, 2 );

		// Test to see if the Restrict Content Pro plugin is active, then add all actions.
		if ( ! RestrictContentPro::is_active() ) {
			return;
		}

		add_filter( 'rcp_payment_gateways', array( $this, 'register_pronamic_gateways' ) );
		add_action( 'rcp_payments_settings', array( $this, 'payments_settings' ) );
		add_action( 'rcp_transition_membership_status', array( $this, 'rcp_transition_membership_status' ), 10, 3 );

		add_filter( 'rcp_membership_can_cancel', array( $this, 'rcp_membership_can_cancel' ), 10, 3 );
		add_filter( 'rcp_membership_payment_profile_cancelled', array( $this, 'rcp_membership_payment_profile_cancelled' ), 10, 5 );

		add_action( 'pronamic_payment_status_update_rcp_payment', array( $this, 'payment_status_update' ), 10, 1 );
		add_filter( 'pronamic_payment_redirect_url', array( $this, 'payment_redirect_url' ), 10, 2 );
		add_filter( 'pronamic_payment_source_text_rcp_payment', array( $this, 'payment_source_text' ), 10, 2 );

		add_filter( 'pronamic_subscription_source_text_rcp_membership', array( $this, 'subscription_source_text' ), 10, 2 );

		add_action( 'pronamic_pay_new_payment', array( $this, 'new_payment' ) );
		add_action( 'pronamic_pay_update_payment', array( $this, 'update_payment' ) );

		add_action( 'rcp_edit_membership_after', array( $this, 'rcp_edit_membership_after' ) );
		add_action( 'rcp_edit_payment_after', array( $this, 'rcp_edit_payment_after' ) );
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
	private function get_gateways() {
		return array(
			'pronamic_pay'                         => $this->get_gateway_data( __( 'Pay', 'pronamic_ideal' ), Gateway::class ),
			'pronamic_pay_bancontact'              => $this->get_gateway_data( __( 'Bancontact', 'pronamic_ideal' ), BancontactGateway::class ),
			'pronamic_pay_banktransfer'            => $this->get_gateway_data( __( 'Bank Transfer', 'pronamic_ideal' ), BankTransferGateway::class ),
			'pronamic_pay_bitcoin'                 => $this->get_gateway_data( __( 'Bitcoin', 'pronamic_ideal' ), BitcoinGateway::class ),
			'pronamic_pay_credit_card'             => $this->get_gateway_data( __( 'Credit Card', 'pronamic_ideal' ), CreditCardGateway::class ),
			'pronamic_pay_direct_debit'            => $this->get_gateway_data( __( 'Direct Debit', 'pronamic_ideal' ), DirectDebitGateway::class ),
			'pronamic_pay_direct_debit_bancontact' => $this->get_gateway_data(
				sprintf(
					/* translators: %s: Payment method */
					__( 'Direct Debit (mandate via %s)', 'pronamic_ideal' ),
					__( 'Bancontact', 'pronamic_ideal' )
				),
				DirectDebitBancontactGateway::class
			),
			'pronamic_pay_direct_debit_ideal'      => $this->get_gateway_data(
				sprintf(
					/* translators: %s: Payment method */
					__( 'Direct Debit (mandate via %s)', 'pronamic_ideal' ),
					__( 'iDEAL', 'pronamic_ideal' )
				),
				DirectDebitIDealGateway::class
			),
			'pronamic_pay_direct_debit_sofort'     => $this->get_gateway_data(
				sprintf(
					/* translators: %s: Payment method */
					__( 'Direct Debit (mandate via %s)', 'pronamic_ideal' ),
					__( 'SOFORT', 'pronamic_ideal' )
				),
				DirectDebitSofortGateway::class
			),
			'pronamic_pay_ideal'                   => $this->get_gateway_data( __( 'iDEAL', 'pronamic_ideal' ), IDealGateway::class ),
			'pronamic_pay_paypal'                  => $this->get_gateway_data( __( 'PayPal', 'pronamic_ideal' ), PayPalGateway::class ),
			'pronamic_pay_sofort'                  => $this->get_gateway_data( __( 'SOFORT', 'pronamic_ideal' ), SofortGateway::class ),
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
	 * Payment redirect URL filter.
	 *
	 * @param string  $url     URL.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public function payment_redirect_url( $url, $payment ) {
		$sources = array(
			'restrictcontentpro',
			'rcp_payment',
		);

		if ( ! in_array( $payment->source, $sources, true ) ) {
			return $url;
		}

		if ( Statuses::SUCCESS !== $payment->get_status() ) {
			return $url;
		}

		// Return success page URL.
		return rcp_get_return_url( $payment->get_customer()->get_user_id() );
	}

	/**
	 * Update the status of the specified payment.
	 *
	 * @param Payment $payment Payment.
	 */
	public function payment_status_update( Payment $payment ) {
		/**
		 * Find the related Restrict Content Pro payment.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/class-rcp-payments.php#L309-334
		 */
		$rcp_payments = new RCP_Payments();

		$rcp_payment_id = $payment->get_source_id();

		$rcp_payment = $rcp_payments->get_payment( $rcp_payment_id );

		if ( is_null( $rcp_payment ) ) {
			return;
		}

		// Only update if order is not completed.
		if ( PaymentStatus::COMPLETE === $rcp_payment->status ) {
			return;
		}

		$core_status = $payment->get_status();

		$rcp_payment_data = array(
			'status' => PaymentStatus::from_core( $core_status ),
		);

		switch ( $core_status ) {
			case Statuses::CANCELLED:
				$rcp_payments->update( $rcp_payment_id, $rcp_payment_data );

				break;
			case Statuses::EXPIRED:
				$rcp_payments->update( $rcp_payment_id, $rcp_payment_data );

				break;
			case Statuses::FAILURE:
				$rcp_payments->update( $rcp_payment_id, $rcp_payment_data );

				break;
			case Statuses::SUCCESS:
				$rcp_payments->update( $rcp_payment_id, $rcp_payment_data );

				/**
				 * Find and renew the Restrict Content Pro membership.
				 *
				 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/memberships/membership-functions.php#L15-29
				 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/class-rcp-payments.php#L75
				 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/memberships/class-rcp-membership.php#L1700-1808
				 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/gateways/class-rcp-payment-gateway-paypal.php#L466
				 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/gateways/class-rcp-payment-gateway-paypal.php#L570-571
				 */
				$rcp_membership = rcp_get_membership( $rcp_payment->membership_id );

				if ( false !== $rcp_membership ) {
					$subscription = $payment->get_subscription();

					$recurring = empty( $subscription ) ? false : true;

					$rcp_membership->renew( $recurring );
				}

				break;
			case Statuses::OPEN:
				// Nothing to do?
				break;
		}
	}

	/**
	 * Restrict Content Pro transition membership status.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/master/includes/memberships/class-rcp-membership.php#L673-683
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/master/includes/database/engine/class-query.php#L2061-2070
	 *
	 * @param string $old_status    Old membership status.
	 * @param string $new_status    New membership status.
	 * @param int    $membership_id ID of the membership.
	 */
	public function rcp_transition_membership_status( $old_status, $new_status, $membership_id ) {
		$query = new WP_Query(
			array(
				'post_type'     => 'pronamic_pay_subscr',
				'post_status'   => 'any',
				'meta_query'    => array(
					array(
						'key'   => '_pronamic_subscription_source',
						'value' => 'rcp_membership',
					),
					array(
						'key'   => '_pronamic_subscription_source_id',
						'value' => $membership_id,
					),
				),
				'nopaging'      => true,
				'no_found_rows' => true,
				'order'         => 'DESC',
				'orderby'       => 'ID',
			)
		);

		if ( ! $query->have_posts() ) {
			return;
		}

		$core_status = MembershipStatus::to_core( $new_status );

		switch ( $new_status ) {
			case MembershipStatus::ACTIVE:
				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription activated by %s.', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;
			case MembershipStatus::CANCELLED:
				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription canceled by %s.', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;

			case MembershipStatus::EXPIRED:
				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription expired by %s.', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;

			case MembershipStatus::PENDING:
				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription pending by %s.', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;
		}

		if ( is_null( $core_status ) ) {
			return;
		}

		while ( $query->have_posts() ) {
			$query->the_post();

			$subscription = get_pronamic_subscription( get_the_ID() );

			if ( null === $subscription ) {
				continue;
			}

			$subscription->set_status( $core_status );

			$subscription->add_note( $note );

			$subscription->save();
		}

		wp_reset_postdata();
	}

	/**
	 * Restrict Content Pro membership can cancel.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/memberships/class-rcp-membership.php#L2239-2248
	 *
	 * @param bool           $can_cancel    Whether or not this membership can be cancelled.
	 * @param int            $membership_id ID of the membership.
	 * @param RCP_Membership $membership    Membership object.
	 * @return bool
	 */
	public function rcp_membership_can_cancel( $can_cancel, $membership_id, $membership ) {
		$gateways = $this->get_gateways();

		if ( ! array_key_exists( $membership->get_gateway(), $gateways ) ) {
			return $can_cancel;
		}

		if ( ! $membership->is_recurring() ) {
			return $can_cancel;
		}

		if ( MembershipStatus::ACTIVE !== $membership->get_status() ) {
			return $can_cancel;
		}

		if ( ! $membership->is_paid() ) {
			return $can_cancel;
		}

		if ( $membership->is_expired() ) {
			return $can_cancel;
		}

		return true;
	}

	/**
	 * Restrict Content Pro membership payment profile cancelled.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/memberships/class-rcp-membership.php#L2372-2385
	 *
	 * @param true|WP_Error  $success                 Whether or not the cancellation was successful.
	 * @param string         $gateway                 Payment gateway for this membership.
	 * @param string         $gateway_subscription_id Gateway subscription ID.
	 * @param int            $membership_id           ID of the membership.
	 * @param RCP_Membership $membership              Membership object.
	 */
	public function rcp_membership_payment_profile_cancelled( $success, $gateway, $gateway_subscription_id, $membership_id, $membership ) {
		$gateways = $this->get_gateways();

		if ( ! array_key_exists( $gateway, $gateways ) ) {
			return $success;
		}

		return true;
	}

	/**
	 * Source column
	 *
	 * @param string  $text    Text.
	 * @param Payment $payment Payment.
	 *
	 * @return string $text
	 */
	public function payment_source_text( $text, Payment $payment ) {
		$text = __( 'Restrict Content Pro', 'pronamic_ideal' ) . '<br />';

		$source_url = add_query_arg(
			array(
				'page'       => 'rcp-payments',
				'payment_id' => $payment->source_id,
				'view'       => 'edit-payment',
			),
			admin_url( 'admin.php' )
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
	 * Subscription source text.
	 *
	 * @param string       $text         Text.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return string $text
	 */
	public function subscription_source_text( $text, $subscription ) {
		$text = __( 'Restrict Content Pro', 'pronamic_ideal' ) . '<br />';

		$source_url = add_query_arg(
			array(
				'page'          => 'rcp-members',
				'membership_id' => $subscription->source_id,
				'view'          => 'edit',
			),
			admin_url( 'admin.php' )
		);

		$text .= sprintf(
			'<a href="%s">%s</a>',
			esc_url( $source_url ),
			/* translators: %s: source id */
			sprintf( __( 'Membership %s', 'pronamic_ideal' ), $subscription->source_id )
		);

		return $text;
	}

	/**
	 * Payment source description.
	 *
	 * @link https://github.com/wp-pay/core/blob/2.1.6/src/Payments/Payment.php#L659-L671
	 *
	 * @param string  $description Description.
	 * @param Payment $payment     Payment.
	 *
	 * @return string
	 */
	public function payment_source_description( $description, Payment $payment ) {
		switch ( $payment->source ) {
			case 'restrictcontentpro':
				return __( 'Restrict Content Pro', 'pronamic_ideal' );
			case 'rcp_payment':
				return __( 'Restrict Content Pro Payment', 'pronamic_ideal' );
			default:
				return $description;
		}
	}

	/**
	 * Payment source URL.
	 *
	 * @param string  $url     URL.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public function payment_source_url( $url, Payment $payment ) {
		switch ( $payment->source ) {
			case 'restrictcontentpro':
				return add_query_arg(
					'user_id',
					$payment->post->post_author,
					menu_page_url( 'rcp-payments', false )
				);
			case 'rcp_payment':
				return add_query_arg(
					array(
						'page'       => 'rcp-payments',
						'view'       => 'edit-payment',
						'payment_id' => $payment->source_id,
					),
					admin_url( 'admin.php' )
				);
			default:
				return $url;
		}
	}

	/**
	 * Get Restrict Content Pro mebership from payment.
	 *
	 * @param Payment $payment Pronamic Pay payment.
	 * @return RCP_Membership|null
	 * @throws Exception When Restrict Content Pro membership can not be found.
	 */
	private function get_rcp_membership_from_payment( Payment $payment ) {
		/**
		 * In version <= 2.1.3 we used the source 'restrictcontentpro'
		 * with a user ID.
		 *
		 * @link https://gist.github.com/remcotolsma/ecc33e2f14c5035aabc876d73fbe0b62
		 */
		if ( 'restrictcontentpro' === $payment->source ) {
			$user_id = $payment->source_id;

			/**
			 * Get customer by user ID.
			 *
			 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.1/includes/customers/customer-functions.php#L15-34
			 */
			$rcp_customer = rcp_get_customer_by_user_id( $user_id );

			if ( false === $rcp_customer ) {
				throw new Exception(
					sprintf(
						'Could not find Restrict Content Pro customer for user ID: %s.',
						$user_id
					)
				);
			}

			/**
			 * Get customer single membership by customer ID.
			 *
			 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.1/includes/customers/customer-functions.php#L280-320
			 */
			$rcp_membership = rcp_get_customer_single_membership( $rcp_customer->get_id() );

			if ( false === $rcp_membership ) {
				throw new Exception(
					sprintf(
						'Could not find Restrict Content Pro membership for customer ID: %s.',
						$rcp_customer->get_id()
					)
				);
			}

			return $rcp_membership;
		}

		/**
		 * In version > 2.1.3 we use the source 'rcp_membership'
		 * with a membership ID.
		 */
		if ( 'rcp_membership' === $payment->source ) {
			$membership_id = $payment->source_id;

			/**
			 * Try to find the Restrict Content Pro membership from the
			 * payment source ID.
			 *
			 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/memberships/membership-functions.php#L15-29
			 */
			$rcp_membership = rcp_get_membership( $membership_id );

			if ( false === $rcp_membership ) {
				throw new Exception(
					sprintf(
						'Could not find Restrict Content Pro membership with ID: %s.',
						$membership_id
					)
				);
			}

			return $rcp_membership;
		}

		return null;
	}

	/**
	 * This function is hooked into the `pronamic_pay_new_payment` routine.
	 * It will check if the new payment is created from a Restrict Content Pro
	 * membership. If that is the case it will create a new Restrict Content Pro
	 * payment.
	 *
	 * @link https://github.com/wp-pay/core/blob/2.1.6/src/Payments/PaymentsDataStoreCPT.php#L234
	 *
	 * @param Payment $payment Payment.
	 * @throws Exception When Restrict Content Pro returns unexpected value.
	 */
	public function new_payment( Payment $payment ) {
		$rcp_membership = $this->get_rcp_membership_from_payment( $payment );

		if ( null === $rcp_membership ) {
			return;
		}

		/**
		 * Insert Restrict Content Pro payment.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/class-rcp-payments.php#L55-191
		 */
		$rcp_payments = new RCP_Payments();

		$result = $rcp_payments->insert(
			array(
				'date'             => $payment->get_date()->format( 'Y-m-d g:i:s' ),
				'payment_type'     => '',
				'transaction_type' => 'renewal',
				'user_id'          => $rcp_membership->get_customer()->get_user_id(),
				'customer_id'      => $rcp_membership->get_customer_id(),
				'membership_id'    => $rcp_membership->get_id(),
				'amount'           => $payment->get_total_amount()->get_value(),
				// Transaction ID can not be null therefor we use `strval` to cast `null` to an empty string.
				'transaction_id'   => strval( $payment->get_transaction_id() ),
				'subscription'     => rcp_get_subscription_name( $rcp_membership->get_object_id() ),
				'subscription_key' => $rcp_membership->get_subscription_key(),
				'object_type'      => 'subscription',
				'object_id'        => $rcp_membership->get_object_id(),
				'status'           => PaymentStatus::from_core( $payment->get_status() ),
			)
		);

		if ( false === $result ) {
			throw new Exception(
				sprintf(
					'Could not create Restrict Content Pro payment for payment %s.',
					$payment->get_id()
				)
			);
		}

		$rcp_payment_id = $result;

		$payment->source    = 'rcp_payment';
		$payment->source_id = $rcp_payment_id;

		$payment->save();
	}

	/**
	 * Update payment.
	 *
	 * @param Payment $payment Payment.
	 * @throws Exception When Restrict Content Pro returns unexpected value.
	 */
	public function update_payment( Payment $payment ) {
		/**
		 * Check if the payment is connected to a Restrict Content Pro
		 * payment.
		 */
		if ( 'rcp_payment' !== $payment->source ) {
			return;
		}

		/**
		 * Update Restrict Content Pro payment.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/master/includes/class-rcp-payments.php#L219-284
		 */
		$rcp_payments = new RCP_Payments();

		$result = $rcp_payments->update(
			$payment->source_id,
			array(
				'status'         => PaymentStatus::from_core( $payment->get_status() ),
				'transaction_id' => strval( $payment->get_transaction_id() ),
			)
		);

		if ( false === $result ) {
			throw new Exception(
				sprintf(
					'Could not update Restrict Content Pro payment for payment %s.',
					$payment->get_id()
				)
			);
		}
	}

	/**
	 * Restrict Conent Pro edit membership after.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/memberships/edit-membership.php#L285-294
	 *
	 * @param RCP_Membership $membership Restrict Content Pro membership.
	 */
	public function rcp_edit_membership_after( $membership ) {
		$query = new WP_Query(
			array(
				'post_type'     => 'pronamic_pay_subscr',
				'post_status'   => 'any',
				'meta_query'    => array(
					array(
						'key'   => '_pronamic_subscription_source',
						'value' => 'rcp_membership',
					),
					array(
						'key'   => '_pronamic_subscription_source_id',
						'value' => $membership->get_id(),
					),
				),
				'nopaging'      => true,
				'no_found_rows' => true,
				'order'         => 'DESC',
				'orderby'       => 'ID',
			)
		);

		include __DIR__ . '/../views/edit-membership.php';

		wp_reset_postdata();
	}

	/**
	 * Restrict Content Pro edit payment after.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/payments/edit-payment.php#L127
	 *
	 * @param object $payment Restrict Content Pro payment.
	 */
	public function rcp_edit_payment_after( $payment ) {
		$query = new WP_Query(
			array(
				'post_type'     => 'pronamic_payment',
				'post_status'   => 'any',
				'meta_query'    => array(
					array(
						'key'   => '_pronamic_payment_source',
						'value' => 'rcp_payment',
					),
					array(
						'key'   => '_pronamic_payment_source_id',
						'value' => $payment->id,
					),
				),
				'nopaging'      => true,
				'no_found_rows' => true,
				'order'         => 'DESC',
				'orderby'       => 'ID',
			)
		);

		include __DIR__ . '/../views/edit-payment.php';

		wp_reset_postdata();
	}
}
