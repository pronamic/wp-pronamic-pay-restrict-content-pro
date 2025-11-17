<?php
/**
 * Extension
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContent
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContent;

use Pronamic\WordPress\DateTime\DateTime;
use Pronamic\WordPress\DateTime\DateTimeImmutable;
use Pronamic\WordPress\Pay\AbstractPluginIntegration;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Payments\PaymentStatus as Core_PaymentStatus;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus as Core_SubscriptionStatus;
use RCP_Membership;
use RCP_Payment_Gateways;
use RCP_Payments;
use WP_Query;

/**
 * Extension class
 *
 * @link https://plugins.trac.wordpress.org/browser/restrict-content/tags/3.2.10/core/includes/gateways/class-rcp-payment-gateways.php#L47
 * @phpstan-type RestrictContentProGatewayRegistration array{label: string, admin_label: string, class: class-string}
 * @phpstan-type RestrictContentProPaymentObject object{id: int, membership_id: int, status: string}
 */
class Extension extends AbstractPluginIntegration {
	/**
	 * Registered gateways.
	 *
	 * @var array<string, RestrictContentProGatewayRegistration>
	 */
	private $gateways;

	/**
	 * Construct Restrict Content Pro plugin integration.
	 *
	 * @param array<string, mixed> $args Arguments.
	 * @return void
	 */
	public function __construct( $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'name'                => 'Restrict Content Pro',
				'slug'                => 'restrict-content-pro',
				'version'             => '4.5.0',
				'version_option_name' => 'pronamic_pay_restrictcontentpro_version',
			]
		);

		parent::__construct( $args );

		// Dependencies.
		$dependencies = $this->get_dependencies();

		$dependencies->add( new RestrictContentProDependency() );

		// Upgrades.
		$upgrades = $this->get_upgrades();

		$upgrades->add( new Upgrade216() );
		$upgrades->add( new Upgrade450() );
	}

	/**
	 * Setup plugin integration.
	 *
	 * @return void
	 */
	public function setup() {
		\add_filter( 'pronamic_payment_source_description', $this->payment_source_description( ... ), 10, 2 );
		\add_filter( 'pronamic_payment_source_url', $this->payment_source_url( ... ), 10, 2 );
		\add_filter( 'pronamic_subscription_source_description', $this->subscription_source_description( ... ), 10, 2 );
		\add_filter( 'pronamic_subscription_source_url', $this->subscription_source_url( ... ), 10, 2 );

		/*
		 * The Restrict Content Pro plugin gets bootstrapped with priority `4`.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/-/blob/3.3.3/restrict-content-pro.php#L119
		 */
		\add_action( 'plugins_loaded', $this->plugins_loaded( ... ), 5 );

		\add_action( 'rcp_after_membership_admin_update', $this->rcp_after_membership_admin_update( ... ) );

		/*
		 * Filter subscription details actions HTML.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/-/blob/3.4.4/templates/subscription.php#L156-164
		 */
		\add_filter( 'rcp_subscription_details_actions', $this->rcp_subscription_details_actions( ... ), 10, 4 );
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
		\add_action( 'admin_init', $this->admin_init_upgrades_executable( ... ), 4 );

		\add_filter( 'rcp_payment_gateways', $this->register_pronamic_gateways( ... ) );
		\add_action( 'rcp_payments_settings', $this->payments_settings( ... ) );

		/**
		 * We hook into membership status transitions with priority `9` as the default priority results in a conflicting
		 * infinite loop by `rcp_set_customer_trialing_flag` of RCP membership and Pronamic subscription status updates.
		 *
		 * @link https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/issues/26
		 * @link https://github.com/stellarwp/restrict-content/blob/1b7914806e9703b6820d9073f0e6d965ebee7337/core/includes/customers/customer-actions.php#L15-L37
		 */
		\add_action( 'rcp_transition_membership_status', $this->rcp_transition_membership_status( ... ), 9, 3 );

		\add_filter( 'rcp_membership_can_cancel', $this->rcp_membership_can_cancel( ... ), 10, 3 );
		\add_filter( 'rcp_membership_payment_profile_cancelled', $this->rcp_membership_payment_profile_cancelled( ... ), 10, 5 );

		\add_action( 'pronamic_payment_status_update_rcp_payment', $this->payment_status_update( ... ), 10, 1 );
		\add_filter( 'pronamic_payment_redirect_url', $this->payment_redirect_url( ... ), 10, 2 );
		\add_filter( 'pronamic_payment_source_text_rcp_payment', $this->payment_source_text( ... ), 10, 2 );

		\add_action( 'pronamic_subscription_status_update_rcp_membership', $this->subscription_status_update( ... ), 10, 1 );
		\add_filter( 'pronamic_subscription_source_text_rcp_membership', $this->subscription_source_text( ... ), 10, 2 );

		\add_action( 'pronamic_pay_new_payment', $this->new_payment( ... ) );
		\add_action( 'pronamic_pay_update_payment', $this->maybe_record_restrictcontentpro_payment_refund( ... ), 10, 1 );

		\add_action( 'rcp_edit_payment_after', $this->rcp_edit_payment_after( ... ) );

		\add_filter( 'rcp_gateway_subscription_id_url', $this->rcp_gateway_subscription_id_url( ... ), 10, 3 );

		\add_action( 'save_post_pronamic_pay_subscr', $this->maybe_update_membership_gateway( ... ) );

		/**
		 * Filter Restrict Content Pro to query expired memberships up to an hour ago instead of current time,
		 * to prevent RCP from sending an expiration email before the renewal payment has been created.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/-/blob/3.4.4/includes/cron-functions.php#L47-106
		 */
		\add_filter( 'rcp_check_for_expired_memberships_query_args', $this->expired_memberships_query_args( ... ) );
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
		if ( ! \is_admin() ) {
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
			[
				'status' => 'incomplete',
			]
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
	 * @param array<string, RestrictContentProGatewayRegistration> $gateways Gateways.
	 * @return array<string, RestrictContentProGatewayRegistration>
	 */
	public function register_pronamic_gateways( $gateways ) {
		return array_merge( $gateways, $this->get_gateways() );
	}

	/**
	 * Get Pronamic gateways.
	 *
	 * @return array<string, RestrictContentProGatewayRegistration>
	 */
	private function get_gateways() {
		if ( null === $this->gateways ) {
			$gateways = [
				'pronamic_pay'                         => Gateways\Gateway::class,
				'pronamic_pay_apple_pay'               => Gateways\ApplePayGateway::class,
				'pronamic_pay_bancontact'              => Gateways\BancontactGateway::class,
				'pronamic_pay_banktransfer'            => Gateways\BankTransferGateway::class,
				'pronamic_pay_bitcoin'                 => Gateways\BitcoinGateway::class,
				'pronamic_pay_card'                    => Gateways\CardGateway::class,
				'pronamic_pay_credit_card'             => Gateways\CreditCardGateway::class,
				'pronamic_pay_direct_debit'            => Gateways\DirectDebitGateway::class,
				'pronamic_pay_direct_debit_bancontact' => Gateways\DirectDebitBancontactGateway::class,
				'pronamic_pay_direct_debit_ideal'      => Gateways\DirectDebitIDealGateway::class,
				'pronamic_pay_direct_debit_sofort'     => Gateways\DirectDebitSofortGateway::class,
				'pronamic_pay_eps'                     => Gateways\EpsGateway::class,
				'pronamic_pay_ideal'                   => Gateways\IDealGateway::class,
				'pronamic_pay_giropay'                 => Gateways\GiropayGateway::class,
				'pronamic_pay_paypal'                  => Gateways\PayPalGateway::class,
				'pronamic_pay_sofort'                  => Gateways\SofortGateway::class,
			];

			foreach ( $gateways as $gateway_id => $class ) {
				$gateway = new $class();

				$this->gateways[ $gateway_id ] = [
					'label'       => $gateway->get_label(),
					'admin_label' => $gateway->get_admin_label(),
					'class'       => $class,
				];
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

			if ( $gateway instanceof Gateways\Gateway ) {
				$gateway->payments_settings( $rcp_options );
			}
		}
	}

	/**
	 * Get account page URL.
	 *
	 * @return string|null
	 */
	private function get_account_page_url() {
		global $rcp_options;

		$url = null;

		if ( ! \is_array( $rcp_options ) ) {
			return $url;
		}

		if ( ! \array_key_exists( 'account_page', $rcp_options ) ) {
			return $url;
		}

		$page_id = (int) $rcp_options['account_page'];

		if ( 0 === $page_id ) {
			return $url;
		}

		$permalink = \get_permalink( $page_id );

		if ( false === $permalink ) {
			return $url;
		}

		return $permalink;
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
			$account_page_url = $this->get_account_page_url();

			if ( null !== $account_page_url ) {
				$url = $account_page_url;
			}

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

		$rcp_payment_id = (int) $payment->get_source_id();

		/**
		 * The RCP Payments class will return a `object`.
		 *
		 * @var null|RestrictContentProPaymentObject $rcp_payment Restrict Content Pro payment object.
		 */
		$rcp_payment = $rcp_payments->get_payment( $rcp_payment_id );

		if ( null === $rcp_payment ) {
			return;
		}

		// Only update if order is not completed.
		if ( PaymentStatus::COMPLETE === $rcp_payment->status ) {
			return;
		}

		$core_status = $payment->get_status();

		$rcp_payment_data = [
			'status' => PaymentStatus::from_core( $core_status ),
		];

		switch ( $core_status ) {
			case Core_PaymentStatus::CANCELLED:
			case Core_PaymentStatus::EXPIRED:
			case Core_PaymentStatus::FAILURE:
				$this_pronamic_payment_id = $payment->get_id();
				$last_pronamic_payment_id = \rcp_get_payment_meta( $rcp_payment_id, '_pronamic_payment_id', true );

				if ( '' == $last_pronamic_payment_id || $this_pronamic_payment_id == $last_pronamic_payment_id ) {
					$rcp_payments->update( $rcp_payment_id, $rcp_payment_data );
				}

				$rcp_membership = \rcp_get_membership( $rcp_payment->membership_id );

				if ( false === $rcp_membership ) {
					return;
				}

				if ( ! $rcp_membership->is_active() ) {
					return;
				}

				if ( 'recurring' !== $payment->get_meta( 'mollie_sequence_type' ) ) {
					return;
				}

				$last_pronamic_payment_id = \rcp_get_membership_meta( $rcp_membership->get_id(), '_pronamic_payment_id', true );

				if ( '' != $last_pronamic_payment_id && $this_pronamic_payment_id != $last_pronamic_payment_id ) {
					return;
				}

				$gateways = $this->get_gateways();

				$gateway_id = $rcp_membership->get_gateway();

				if ( ! \array_key_exists( $gateway_id, $gateways ) ) {
					return;
				}

				$gateway = $gateways[ $gateway_id ];

				$rcp_gateway = new $gateway['class']();

				$rcp_gateway->membership = $rcp_membership;

				$member = new \RCP_Member( $payment->get_customer()->get_user_id() );

				\do_action( 'rcp_recurring_payment_failed', $member, $rcp_gateway );

				break;
			case Core_PaymentStatus::SUCCESS:
				$rcp_payment_data['transaction_id'] = (string) $payment->get_transaction_id();

				$rcp_payments->update( $rcp_payment_id, $rcp_payment_data );

				$rcp_membership = \rcp_get_membership( $rcp_payment->membership_id );

				if ( false === $rcp_membership ) {
					return;
				}

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
	 * Update RCP membership status on subscription status update.
	 *
	 * @param Subscription $pronamic_subscription Subscription.
	 * @return void
	 */
	public function subscription_status_update( Subscription $pronamic_subscription ) {
		$membership_id = (int) $pronamic_subscription->get_source_id();

		$rcp_membership = \rcp_get_membership( $membership_id );

		if ( false === $rcp_membership ) {
			return;
		}

		$status = MembershipStatus::transform_from_pronamic( $pronamic_subscription->get_status() );

		if ( null !== $status ) {
			$rcp_membership->set_status( $status );
		}

		$id = $pronamic_subscription->get_id();

		if ( null !== $id ) {
			$rcp_membership->set_gateway_subscription_id( (string) $id );
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
		$rcp_membership = \rcp_get_membership( (int) $membership_id );

		if ( false === $rcp_membership ) {
			return;
		}

		$gateway_subscription_id = $rcp_membership->get_gateway_subscription_id();

		if ( '' === $gateway_subscription_id ) {
			return;
		}

		$pronamic_subscription = \get_pronamic_subscription( (int) $gateway_subscription_id );

		if ( null === $pronamic_subscription ) {
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

		if ( null === $core_status ) {
			return;
		}

		$pronamic_subscription->set_status( $core_status );

		if ( null !== $note ) {
			$pronamic_subscription->add_note( $note );
		}

		$pronamic_subscription->save();
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
	 * Subscription action links HTML.
	 *
	 * @param string         $actions        Formatted HTML links.
	 * @param array<string>  $links          Links.
	 * @param int            $user_id        Current user ID.
	 * @param RCP_Membership $rcp_membership Membership object.
	 * @return string
	 */
	public function rcp_subscription_details_actions( $actions, $links, $user_id, $rcp_membership ) {
		$subscriptions = \get_pronamic_subscriptions_by_source( 'rcp_membership', $rcp_membership->get_id() );

		if ( 0 === count( $subscriptions ) ) {
			return $actions;
		}

		$subscription = reset( $subscriptions );

		// Payment method can only be updated for active subscription.
		if ( Core_SubscriptionStatus::ACTIVE !== $subscription->get_status() ) {
			return $actions;
		}

		$action = \sprintf(
			'<a href="%1$s" title="%2$s"><button type="button">%3$s</button></a>',
			\esc_url( $subscription->get_mandate_selection_url() ),
			\esc_attr( \__( 'Update payment method', 'pronamic_ideal' ) ),
			\esc_html( \__( 'Update payment method', 'pronamic_ideal' ) )
		);

		$actions = \sprintf( '%s<br/>%s', $action, $actions );

		return $actions;
	}

	/**
	 * Maybe update membership gateway on subscription updates.
	 *
	 * @param int $post_id Subscription post ID.
	 * @return void
	 */
	public function maybe_update_membership_gateway( $post_id ) {
		$subscription = \get_pronamic_subscription( $post_id );

		if ( null === $subscription ) {
			return;
		}

		if ( 'rcp_membership' !== $subscription->get_source() ) {
			return;
		}

		$rcp_membership = \rcp_get_membership( (int) $subscription->get_source_id() );

		if ( false === $rcp_membership ) {
			return;
		}

		/**
		 * Update membership gateway.
		 */
		$rcp_gateways = new RCP_Payment_Gateways();

		foreach ( $rcp_gateways->available_gateways as $gateway_id => $gateway ) {
			if ( ! \array_key_exists( 'class', $gateway ) ) {
				continue;
			}

			$rcp_gateway = new $gateway['class']();

			if ( ! \method_exists( $rcp_gateway, 'get_pronamic_payment_method' ) ) {
				continue;
			}

			if ( $rcp_gateway->get_pronamic_payment_method() !== $subscription->get_payment_method() ) {
				continue;
			}

			if ( $rcp_membership->get_gateway() === $gateway_id ) {
				break;
			}

			$rcp_membership->update(
				[
					'gateway' => $gateway_id,
				]
			);

			break;
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
	public function payment_source_text( $text, Payment $payment ) {
		$text = __( 'Restrict Content Pro', 'pronamic_ideal' ) . '<br />';

		$source_url = add_query_arg(
			[
				'page'       => 'rcp-payments',
				'payment_id' => $payment->source_id,
				'view'       => 'edit-payment',
			],
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
		return match ( $payment->source ) {
			'rcp_payment' => __( 'Restrict Content Pro Payment', 'pronamic_ideal' ),
			default => $description,
		};
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
		return match ( $payment->source ) {
			'rcp_payment' => add_query_arg(
				[
					'page'       => 'rcp-payments',
					'view'       => 'edit-payment',
					'payment_id' => $payment->source_id,
				],
				admin_url( 'admin.php' )
			),
			default => $url,
		};
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
		return match ( $subscription->get_source() ) {
			'rcp_membership' => __( 'Restrict Content Pro Membership', 'pronamic_ideal' ),
			default => $description,
		};
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
			[
				'page'          => 'rcp-members',
				'membership_id' => $subscription->get_source_id(),
				'view'          => 'edit',
			],
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
		return match ( $subscription->get_source() ) {
			'rcp_membership' => add_query_arg(
				[
					'page'          => 'rcp-members',
					'view'          => 'edit',
					'membership_id' => $subscription->get_source_id(),
				],
				admin_url( 'admin.php' )
			),
			default => $url,
		};
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

		$membership_id = (int) $payment->source_id;

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
					\esc_html( (string) $membership_id )
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
			[
				'date'             => $payment->get_date()->format( 'Y-m-d g:i:s' ),
				'payment_type'     => '',
				'transaction_type' => 'renewal',
				'user_id'          => $rcp_membership->get_customer()->get_user_id(),
				'customer_id'      => $rcp_membership->get_customer_id(),
				'membership_id'    => $rcp_membership->get_id(),
				'amount'           => $payment->get_total_amount()->get_value(),
				'transaction_id'   => '',
				'subscription'     => $rcp_membership->get_membership_level_name(),
				'subscription_key' => $rcp_membership->get_subscription_key(),
				'object_type'      => 'subscription',
				'object_id'        => $rcp_membership->get_object_id(),
				'status'           => PaymentStatus::from_core( $payment->get_status() ),
				'gateway'          => $rcp_membership->get_gateway(),
			]
		);

		if ( false === $result ) {
			throw new \Exception(
				\sprintf(
					'Could not create Restrict Content Pro payment for payment %s.',
					\esc_html( (string) $payment->get_id() )
				)
			);
		}

		$rcp_payment_id = $result;

		Util::connect_pronamic_payment_id_to_rcp_membership( $rcp_membership->get_id(), $payment );
		Util::connect_pronamic_payment_id_to_rcp_payment( $rcp_payment_id, $payment );

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

		$payment->source    = 'rcp_payment';
		$payment->source_id = $rcp_payment_id;

		$payment->save();
	}

	/**
	 * Maybe record refund for Restrict Content Pro payment.
	 *
	 * @param Payment $payment Payment.
	 * @return void
	 */
	public function maybe_record_restrictcontentpro_payment_refund( Payment $payment ) {
		if ( 'rcp_payment' !== $payment->get_source() ) {
			return;
		}

		$amount_refunded = $payment->get_refunded_amount();

		$amount_charged_back = $payment->get_charged_back_amount();

		if ( $amount_refunded->get_value() <= 0 && null === $amount_charged_back ) {
			return;
		}

		/**
		 * Find the related Restrict Content Pro payment.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.4.4/includes/class-rcp-payments.php#L312-337
		 */
		$rcp_payments = new RCP_Payments();

		$rcp_payment_id = (int) $payment->get_source_id();

		/**
		 * The RCP Payments class will return a `object`.
		 *
		 * @var null|RestrictContentProPaymentObject $rcp_payment Restrict Content Pro payment object.
		 */
		$rcp_payment = $rcp_payments->get_payment( $rcp_payment_id );

		if ( null === $rcp_payment ) {
			return;
		}

		if ( PaymentStatus::REFUNDED === $rcp_payment->status ) {
			return;
		}

		$rcp_payments->update(
			$rcp_payment_id,
			[
				'status' => PaymentStatus::REFUNDED,
			]
		);
	}

	/**
	 * Restrict Content Pro edit payment after.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/payments/edit-payment.php#L127
	 * @param RestrictContentProPaymentObject $payment Restrict Content Pro payment.
	 * @return void
	 */
	public function rcp_edit_payment_after( $payment ) {
		$query = new \WP_Query(
			[
				'post_type'     => 'pronamic_payment',
				'post_status'   => 'any',
				'meta_query'    => [
					[
						'key'   => '_pronamic_payment_source',
						'value' => 'rcp_payment',
					],
					[
						'key'   => '_pronamic_payment_source_id',
						'value' => $payment->id,
					],
				],
				'nopaging'      => true,
				'no_found_rows' => true,
				'order'         => 'DESC',
				'orderby'       => 'ID',
			]
		);

		include __DIR__ . '/../views/edit-payment.php';

		\wp_reset_postdata();
	}

	/**
	 * Filter Restrict Content Pro expired memberships query arguments.
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
	 * @param array{'expiration_date_query'?: array{'before'?: string}} $args Expired memberships query arguments.
	 * @return array{'expiration_date_query'?: array{'before'?: string}}
	 */
	public function expired_memberships_query_args( array $args ) {
		if ( ! \array_key_exists( 'expiration_date_query', $args ) ) {
			return $args;
		}

		if ( ! \array_key_exists( 'before', $args['expiration_date_query'] ) ) {
			return $args;
		}

		$hour_ago = ( new DateTime() )->modify( '-1 hour' );

		$args['expiration_date_query']['before'] = $hour_ago->get_local_date()->format( 'Y-m-d H:i:s' );

		return $args;
	}

	/**
	 * Restrict Content Pro after membership admin update.
	 *
	 * @link https://plugins.trac.wordpress.org/browser/restrict-content/tags/3.2.10/core/includes/admin/memberships/membership-actions.php#L371
	 * @param RCP_Membership $rcp_membership Restrict Content Pro membership object.
	 * @return void
	 */
	public function rcp_after_membership_admin_update( RCP_Membership $rcp_membership ) {
		$subscriptions = \get_pronamic_subscriptions_by_source( 'rcp_membership', (string) $rcp_membership->get_id() );

		foreach ( $subscriptions as $subscription ) {
			$subscription_updater = new SubscriptionUpdater( $rcp_membership, $subscription );

			$subscription_updater->update_pronamic_subscription();

			$subscription->save();
		}
	}

	/**
	 * Restrict Content Pro gateway subscription ID URL.
	 *
	 * @param string $url             URL.
	 * @param string $gateway         Payment gateway slug.
	 * @param string $subscription_id ID of the subscription in the gateway.
	 * @return string
	 */
	public function rcp_gateway_subscription_id_url( $url, $gateway, $subscription_id ) {
		$gateways = $this->get_gateways();

		if ( ! \array_key_exists( $gateway, $gateways ) ) {
			return $url;
		}

		$edit_post_link = \get_edit_post_link( (int) $subscription_id );

		if ( null === $edit_post_link ) {
			return $url;
		}

		$url = $edit_post_link;

		return $url;
	}
}
