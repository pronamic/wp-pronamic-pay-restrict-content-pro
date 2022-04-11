<?php
/**
 * Extension
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\DateTime\DateTime;
use Pronamic\WordPress\DateTime\DateTimeImmutable;
use Pronamic\WordPress\Pay\AbstractPluginIntegration;
use Pronamic\WordPress\Pay\Payments\PaymentStatus as Core_PaymentStatus;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus;
use RCP_Member;
use RCP_Membership;
use RCP_Payments;
use WP_Query;

/**
 * Extension
 *
 * @author  Reüel van der Steege
 * @version 2.2.2
 * @since   1.0.0
 */
class Extension extends AbstractPluginIntegration {
	/**
	 * Registered gateways.
	 *
	 * @var array<string, array<string, string>>
	 */
	private $gateways;

	/**
	 * Construct Restrict Content Pro plugin integration.
	 *
	 * @param array<string, mixed> $args Arguments.
	 * @return void
	 */
	public function __construct( $args = array() ) {
		$args['name'] = __( 'Restrict Content Pro', 'pronamic_ideal' );

		parent::__construct( $args );

		// Dependencies.
		$dependencies = $this->get_dependencies();

		$dependencies->add( new RestrictContentProDependency() );

		// Upgrades.
		$upgrades = $this->get_upgrades();

		$upgrades->add( new Upgrade216() );
	}

	/**
	 * Setup plugin integration.
	 *
	 * @return void
	 */
	public function setup() {
		add_filter( 'pronamic_payment_source_description', array( $this, 'payment_source_description' ), 10, 2 );
		add_filter( 'pronamic_payment_source_url', array( $this, 'payment_source_url' ), 10, 2 );
		add_filter( 'pronamic_subscription_source_description', array( $this, 'subscription_source_description' ), 10, 2 );
		add_filter( 'pronamic_subscription_source_url', array( $this, 'subscription_source_url' ), 10, 2 );

		/*
		 * The Restrict Content Pro plugin gets bootstrapped with priority `4`.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/-/blob/3.3.3/restrict-content-pro.php#L119
		 */
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 5 );
	}

	/**
	 * Plugins loaded.
	 *
	 * @return void
	 */
	public function plugins_loaded() {
		// Check if dependencies are met and integration is active.
		if ( ! $this->is_active() ) {
			return;
		}

		/**
		 * On admin initialize we mark the upgrades as executable. This needs to run before
		 * the `wp-pay/core` admin init install routine (priority 5).
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.2.3/includes/class-restrict-content-pro.php#L199-215
		 * @link https://github.com/wp-pay/core/blob/2.2.0/src/Admin/Install.php#L65
		 */
		add_action( 'admin_init', array( $this, 'admin_init_upgrades_executable' ), 4 );

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

		/**
		 * Filter the subscription next payment delivery date.
		 *
		 * Priority is set to 9 so payment gateways can override with priority 10.
		 *
		 * @link https://github.com/wp-pay-gateways/mollie/blob/2.1.4/src/Integration.php#L272-L344
		 */
		\add_filter( 'pronamic_pay_subscription_next_payment_delivery_date', array( $this, 'next_payment_delivery_date' ), 9, 2 );
	}

	/**
	 * Are upgrades executable.
	 *
	 * @return boolean True if upgrades are executable, false otherwise.
	 * @throws \Exception When this function is called outside the WordPress admin environment.
	 */
	private function are_upgrades_executable() {
		/**
		 * This function can only run in the admin.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.2.3/includes/class-restrict-content-pro.php#L199-215
		 */
		if ( ! is_admin() ) {
			throw new \Exception( 'Can not run `are_upgrades_executable` function outside the WordPress admin environment.' );
		}

		/**
		 * Check if upgrade needed.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.2.3/includes/admin/upgrades.php#L11-39
		 * @link https://basecamp.com/1810084/projects/10966871/todos/404760254
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.2.3/includes/class-restrict-content-pro.php#L199-215
		 */
		if ( \rcp_check_if_upgrade_needed() ) {
			return false;
		}

		/**
		 * Check for incomplete jobs.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.2.3/includes/batch/batch-functions.php#L254-277
		 */
		$queue = \RCP\Utils\Batch\get_jobs(
			array(
				'status' => 'incomplete',
			)
		);

		if ( ! empty( $queue ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Upgrades are only executable when no Restrict Content Pro upgrade is needed.
	 *
	 * @return void
	 */
	public function admin_init_upgrades_executable() {
		$this->get_upgrades()->set_executable( $this->are_upgrades_executable() );
	}

	/**
	 * Register Pronamic gateways.
	 *
	 * @param array<string, array<string, string>> $gateways Gateways.
	 * @return array<string, array<string, string>>
	 */
	public function register_pronamic_gateways( $gateways ) {
		return array_merge( $gateways, $this->get_gateways() );
	}

	/**
	 * Get Pronamic gateways.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_gateways() {
		if ( null === $this->gateways ) {
			$gateways = array(
				'pronamic_pay'                         => Gateway::class,
				'pronamic_pay_apple_pay'               => ApplePayGateway::class,
				'pronamic_pay_bancontact'              => BancontactGateway::class,
				'pronamic_pay_banktransfer'            => BankTransferGateway::class,
				'pronamic_pay_bitcoin'                 => BitcoinGateway::class,
				'pronamic_pay_credit_card'             => CreditCardGateway::class,
				'pronamic_pay_direct_debit'            => DirectDebitGateway::class,
				'pronamic_pay_direct_debit_bancontact' => DirectDebitBancontactGateway::class,
				'pronamic_pay_direct_debit_ideal'      => DirectDebitIDealGateway::class,
				'pronamic_pay_direct_debit_sofort'     => DirectDebitSofortGateway::class,
				'pronamic_pay_ideal'                   => IDealGateway::class,
				'pronamic_pay_paypal'                  => PayPalGateway::class,
				'pronamic_pay_sofort'                  => SofortGateway::class,
			);

			foreach ( $gateways as $gateway_id => $class ) {
				$gateway = new $class();

				$this->gateways[ $gateway_id ] = array(
					'label'       => $gateway->get_label(),
					'admin_label' => $gateway->get_admin_label(),
					'class'       => $class,
				);
			}
		}

		return $this->gateways;
	}

	/**
	 * Payment settings.
	 *
	 * @param array<string, mixed> $rcp_options Restrict Content Pro options.
	 * @return void
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
		if ( 'rcp_payment' !== $payment->source ) {
			return $url;
		}

		if ( Core_PaymentStatus::SUCCESS !== $payment->get_status() ) {
			return $url;
		}

		// Return success page URL.
		$customer = $payment->get_customer();

		if ( null !== $customer ) {
			$url = \rcp_get_return_url( $customer->get_user_id() );
		}

		return $url;
	}

	/**
	 * Update the status of the specified payment.
	 *
	 * @param Payment $payment Payment.
	 * @return void
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
			case Core_PaymentStatus::CANCELLED:
			case Core_PaymentStatus::EXPIRED:
			case Core_PaymentStatus::FAILURE:
				$rcp_payments->update( $rcp_payment_id, $rcp_payment_data );

				/**
				 * Find and cancel the Restrict Content Pro membership.
				 *
				 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/memberships/membership-functions.php#L15-29
				 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/class-rcp-payments.php#L75
				 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/memberships/class-rcp-membership.php#L1700-1808
				 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/gateways/class-rcp-payment-gateway-paypal.php#L466
				 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/gateways/class-rcp-payment-gateway-paypal.php#L570-571
				 */
				$rcp_membership = rcp_get_membership( $rcp_payment->membership_id );

				$should_expire = false !== $rcp_membership;

				// Do not expire membership if first payment expires and subscription is active,
				// because a newer completed payment activated the subscription.
				$subscriptions = $payment->get_subscriptions();

				foreach ( $subscriptions as $subscription ) {
					// Check expired payment status.
					if ( Core_PaymentStatus::EXPIRED !== $core_status ) {
						continue;
					}

					// Check if first payment for subscription.
					if ( ! $subscription->is_first_payment( $payment ) ) {
						continue;
					}

					// Check if subscription is active.
					if ( SubscriptionStatus::ACTIVE !== $subscription->get_status() ) {
						continue;
					}

					// Do not expire membership because a newer completed payment activated the subscription.
					$should_expire = false;
				}

				if ( $should_expire ) {
					// Set expiration date to yesterday.
					$rcp_membership->expire();

					// Set status to `pending` to prevent access to restricted content.
					$rcp_membership->set_status( 'pending' );
				}

				break;
			case Core_PaymentStatus::SUCCESS:
				$rcp_payments->update( $rcp_payment_id, $rcp_payment_data );

				// Renew membership if not active.
				$rcp_membership = rcp_get_membership( $rcp_payment->membership_id );

				if ( MembershipStatus::ACTIVE !== $rcp_membership->get_status() ) {
					$expiration = '';

					$periods = $payment->get_periods();

					if ( null !== $periods ) {
						$end_date = null;

						foreach ( $periods as $period ) {
							$end_date = \max( $end_date, $period->get_end_date() );
						}

						if ( null !== $end_date ) {
							$expiration = $end_date->format( DateTime::MYSQL );
						}
					}

					$rcp_membership->renew( true, 'active', $expiration );
				}

				break;
			case Core_PaymentStatus::OPEN:
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
	 * @return void
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

		$core_status = MembershipStatus::to_core_subscription_status( $new_status );

		$note = null;

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

			// Get subscription.
			$post_id = get_the_ID();

			if ( false === $post_id ) {
				continue;
			}

			$subscription = get_pronamic_subscription( $post_id );

			if ( null === $subscription ) {
				continue;
			}

			// Set subscription status.
			$subscription->set_status( $core_status );

			// Add note.
			if ( null !== $note ) {
				$subscription->add_note( $note );
			}

			$subscription->save();
		}

		wp_reset_postdata();
	}

	/**
	 * Restrict Content Pro membership can cancel.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/memberships/class-rcp-membership.php#L2239-2248
	 *
	 * @param bool            $can_cancel    Whether or not this membership can be cancelled.
	 * @param int             $membership_id ID of the membership.
	 * @param \RCP_Membership $membership    Membership object.
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
	 * @param true|\WP_Error $success                Whether or not the cancellation was successful.
	 * @param string         $gateway                 Payment gateway for this membership.
	 * @param string         $gateway_subscription_id Gateway subscription ID.
	 * @param int            $membership_id           ID of the membership.
	 * @param RCP_Membership $membership              Membership object.
	 * @return true|\WP_Error
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
			/* translators: %s: payment number */
			sprintf( __( 'Payment %s', 'pronamic_ideal' ), $payment->source_id )
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
	 * Subscription source description.
	 *
	 * @link https://github.com/wp-pay/core/blob/2.1.6/src/Payments/Payment.php#L659-L671
	 *
	 * @param string       $description  Description.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return string
	 */
	public function subscription_source_description( $description, Subscription $subscription ) {
		switch ( $subscription->get_source() ) {
			case 'rcp_membership':
				return __( 'Restrict Content Pro Membership', 'pronamic_ideal' );
			default:
				return $description;
		}
	}

	/**
	 * Subscription source text.
	 *
	 * @param string       $text         Text.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return string $text
	 */
	public function subscription_source_text( $text, Subscription $subscription ) {
		$text = __( 'Restrict Content Pro', 'pronamic_ideal' ) . '<br />';

		$source_url = add_query_arg(
			array(
				'page'          => 'rcp-members',
				'membership_id' => $subscription->get_source_id(),
				'view'          => 'edit',
			),
			admin_url( 'admin.php' )
		);

		$text .= sprintf(
			'<a href="%s">%s</a>',
			esc_url( $source_url ),
			/* translators: %s: source id */
			sprintf( __( 'Membership %s', 'pronamic_ideal' ), $subscription->get_source_id() )
		);

		return $text;
	}

	/**
	 * Subscription source URL.
	 *
	 * @param string       $url          URL.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return string
	 */
	public function subscription_source_url( $url, Subscription $subscription ) {
		switch ( $subscription->get_source() ) {
			case 'rcp_membership':
				return add_query_arg(
					array(
						'page'          => 'rcp-members',
						'view'          => 'edit',
						'membership_id' => $subscription->get_source_id(),
					),
					admin_url( 'admin.php' )
				);
			default:
				return $url;
		}
	}

	/**
	 * Get Restrict Content Pro membership from payment.
	 *
	 * @param Payment $payment Pronamic Pay payment.
	 * @return \RCP_Membership|null
	 * @throws \Exception When Restrict Content Pro membership can not be found.
	 */
	private function get_rcp_membership_from_payment( Payment $payment ) {
		if ( 'rcp_membership' !== $payment->source ) {
			return null;
		}

		$membership_id = $payment->source_id;

		/**
		 * Try to find the Restrict Content Pro membership from the
		 * payment source ID.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/memberships/membership-functions.php#L15-29
		 */
		$rcp_membership = \rcp_get_membership( $membership_id );

		if ( false === $rcp_membership ) {
			throw new \Exception(
				\sprintf(
					'Could not find Restrict Content Pro membership with ID: %s.',
					$membership_id
				)
			);
		}

		return $rcp_membership;
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
	 * @return void
	 * @throws \Exception When Restrict Content Pro returns unexpected value.
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
		$rcp_payments = new \RCP_Payments();

		$result = $rcp_payments->insert(
			array(
				'date'             => $payment->get_date()->format( 'Y-m-d g:i:s' ),
				'payment_type'     => '',
				'transaction_type' => 'renewal',
				'user_id'          => $rcp_membership->get_customer()->get_user_id(),
				'customer_id'      => $rcp_membership->get_customer_id(),
				'membership_id'    => $rcp_membership->get_id(),
				'amount'           => $payment->get_total_amount()->get_value(),
				// Transaction ID can not be null therefore we use `strval` to cast `null` to an empty string.
				'transaction_id'   => \strval( $payment->get_transaction_id() ),
				'subscription'     => \rcp_get_subscription_name( $rcp_membership->get_object_id() ),
				'subscription_key' => $rcp_membership->get_subscription_key(),
				'object_type'      => 'subscription',
				'object_id'        => $rcp_membership->get_object_id(),
				'status'           => PaymentStatus::from_core( $payment->get_status() ),
			)
		);

		if ( false === $result ) {
			throw new \Exception(
				\sprintf(
					'Could not create Restrict Content Pro payment for payment %s.',
					$payment->get_id()
				)
			);
		}

		// Renew membership.
		$expiration = '';

		$periods = $payment->get_periods();

		if ( null !== $periods ) {
			$end_date = null;

			foreach ( $periods as $period ) {
				$end_date = \max( $end_date, $period->get_end_date() );
			}

			if ( null !== $end_date ) {
				$expiration = $end_date->format( DateTime::MYSQL );
			}
		}

		$rcp_membership->renew( true, 'active', $expiration );

		// Set source.
		$rcp_payment_id = $result;

		$payment->source    = 'rcp_payment';
		$payment->source_id = $rcp_payment_id;

		$payment->save();
	}

	/**
	 * Update payment.
	 *
	 * @param Payment $payment Payment.
	 * @return void
	 * @throws \Exception When Restrict Content Pro returns unexpected value.
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
		$rcp_payments = new \RCP_Payments();

		$result = $rcp_payments->update(
			$payment->source_id,
			array(
				'status'         => PaymentStatus::from_core( $payment->get_status() ),
				'transaction_id' => \strval( $payment->get_transaction_id() ),
			)
		);

		if ( false === $result ) {
			throw new \Exception(
				\sprintf(
					'Could not update Restrict Content Pro payment for payment %s.',
					$payment->get_id()
				)
			);
		}
	}

	/**
	 * Restrict Content Pro edit membership after.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/memberships/edit-membership.php#L285-294
	 *
	 * @param RCP_Membership $membership Restrict Content Pro membership.
	 * @return void
	 */
	public function rcp_edit_membership_after( $membership ) {
		$query = new \WP_Query(
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

		\wp_reset_postdata();
	}

	/**
	 * Restrict Content Pro edit payment after.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/payments/edit-payment.php#L127
	 *
	 * @param object $payment Restrict Content Pro payment.
	 * @return void
	 */
	public function rcp_edit_payment_after( $payment ) {
		$query = new \WP_Query(
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

		\wp_reset_postdata();
	}

	/**
	 * Next payment delivery date.
	 *
	 * The Restrict Content Pro will check for expired memberships on a daily base:
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/-/blob/3.3.3/includes/cron-functions.php#L29-31
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/-/blob/3.3.3/includes/cron-functions.php#L47-106
	 *
	 * To ensure that renewal payments are started on time, we set the next payment date 1 day earlier.
	 *
	 * Otherwise Restrict Content Pro will send an expiration email when the renewal payment was created too late:
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/-/blob/3.3.3/includes/email-functions.php#L328-348
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/-/blob/3.3.3/includes/email-functions.php#L207-242
	 *
	 * @param DateTimeImmutable $next_payment_delivery_date Next payment delivery date.
	 * @param Subscription      $subscription               Subscription.
	 * @return DateTimeImmutable
	 */
	public function next_payment_delivery_date( DateTimeImmutable $next_payment_delivery_date, Subscription $subscription ) {
		if ( 'rcp_membership' !== $subscription->source ) {
			return $next_payment_delivery_date;
		}

		$date = clone $next_payment_delivery_date;

		$date = $date->modify( '-1 day' );

		return $date;
	}
}
