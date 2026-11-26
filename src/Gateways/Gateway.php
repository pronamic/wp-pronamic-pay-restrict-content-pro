<?php
/**
 * Gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContent
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContent\Gateways;

use Pronamic\WordPress\Pay\Admin\AdminModule;
use Pronamic\WordPress\Pay\Core\Gateway as PronamicGateway;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Extensions\RestrictContent\MembershipStatus;
use Pronamic\WordPress\Pay\Extensions\RestrictContent\SubscriptionUpdater;
use Pronamic\WordPress\Pay\Extensions\RestrictContent\Util;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus;
use RCP_Membership;
use RCP_Payment_Gateway;

/**
 * Gateway
 *
 * @version 2.1.6
 * @since   1.0.0
 */
class Gateway extends RCP_Payment_Gateway {
	/**
	 * ID
	 *
	 * @var string
	 */
	protected $id = 'pronamic_pay';

	/**
	 * Payment method
	 *
	 * @var string|null
	 */
	protected $payment_method;

	/**
	 * Initialize.
	 */
	public function init() {
		// Set supported features based on gateway.
		$gateway = $this->get_pronamic_gateway();

		if ( null !== $gateway && null !== $this->payment_method ) {
			$payment_method = $gateway->get_payment_method( $this->payment_method );

			if ( null !== $payment_method && $payment_method->supports( 'recurring' ) ) {
				$this->supports = [
					/**
					 * Expiration extension on renewals.
					 *
					 * Without this feature Restrict Content Pro calculates the new expiration
					 * date of a renewed auto renewing membership from today, instead of from
					 * the current expiration date. The subscription period of the renewal
					 * payment starts at the current expiration date.
					 *
					 * @link https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/issues/28
					 * @link https://plugins.trac.wordpress.org/browser/restrict-content/tags/3.2.10/core/includes/memberships/class-rcp-membership.php#L2075
					 */
					'expiration-extension-on-renewals',
					/**
					 * Price changes.
					 *
					 * @link https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/issues/19
					 */
					'price-changes',
					'recurring',
					/**
					 * Renewal date changes.
					 *
					 * @link https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/issues/17
					 * @link https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/pull/18#issuecomment-2107023059
					 * @link https://plugins.trac.wordpress.org/browser/restrict-content/tags/3.2.10/core/includes/memberships/class-rcp-membership.php#L3454
					 */
					'renewal-date-changes',
					/**
					 * Subscription creation outside the registration form.
					 *
					 * Restrict Content Pro uses this feature when an administrator
					 * changes the membership level of a recurring membership.
					 *
					 * @link https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/issues/2
					 * @link https://plugins.trac.wordpress.org/browser/restrict-content/tags/3.2.10/core/includes/admin/memberships/membership-actions.php#L396
					 */
					'subscription-creation',
					'trial',
				];
			}
		}
	}

	/**
	 * Change the price of the subscription of a membership.
	 *
	 * Restrict Content Pro only stores the new recurring amount in the membership
	 * if this function returns `true`. The Pronamic Pay subscription is updated
	 * from the membership after the membership has been updated.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/issues/19
	 * @link https://plugins.trac.wordpress.org/browser/restrict-content/tags/3.2.10/core/includes/memberships/class-rcp-membership.php#L3353
	 * @see \Pronamic\WordPress\Pay\Extensions\RestrictContent\Extension::rcp_after_membership_admin_update()
	 * @param RCP_Membership $membership Membership object.
	 * @param float          $new_price  New price.
	 * @return true
	 */
	public function change_membership_subscription_price( $membership, $new_price ) {
		return true;
	}

	/**
	 * Change the next bill date of a membership.
	 *
	 * The Pronamic Pay subscription is updated from the membership after the
	 * membership has been updated.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/issues/17
	 * @link https://plugins.trac.wordpress.org/browser/restrict-content/tags/3.2.10/core/includes/memberships/class-rcp-membership.php#L3489
	 * @see \Pronamic\WordPress\Pay\Extensions\RestrictContent\Extension::rcp_after_membership_admin_update()
	 * @param RCP_Membership $membership     Membership object.
	 * @param string         $next_bill_date Next bill date in MySQL format.
	 * @return true
	 */
	public function change_next_bill_date( $membership, $next_bill_date ) {
		return true;
	}

	/**
	 * Create a subscription for a membership.
	 *
	 * Restrict Content Pro calls this function after an administrator has changed
	 * the membership level of a recurring membership. Restrict Content Pro then
	 * disables the previous membership, which cancels the subscription, and adds
	 * a new membership without subscription.
	 *
	 * The payment mandate belongs to the Pronamic Pay subscription of the previous
	 * membership. Instead of creating a new subscription, the existing subscription
	 * is moved to the new membership and updated to the new membership level.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/issues/2
	 * @link https://plugins.trac.wordpress.org/browser/restrict-content/tags/3.2.10/core/includes/admin/memberships/membership-actions.php#L188
	 * @link https://plugins.trac.wordpress.org/browser/restrict-content/tags/3.2.10/core/includes/memberships/class-rcp-membership.php#L3420
	 * @param RCP_Membership $membership Membership object.
	 * @param bool           $charge_now True if the customer should be charged immediately, false if the first payment is due at the membership expiration date.
	 * @return true|\WP_Error
	 */
	public function create_subscription_for_membership( $membership, $charge_now = false ) {
		if ( $charge_now ) {
			return new \WP_Error(
				'pronamic_pay_charge_now_not_supported',
				\__( 'Creating a subscription with an immediate charge is not supported.', 'pronamic-pay-restrict-content' )
			);
		}

		/**
		 * The new membership gets the status of the previous membership. If it
		 * is not active, the subscription was not active either and should not
		 * be activated by a membership level change.
		 */
		if ( MembershipStatus::ACTIVE !== $membership->get_status() ) {
			return new \WP_Error(
				'pronamic_pay_membership_not_active',
				\__( 'The membership is not active.', 'pronamic-pay-restrict-content' )
			);
		}

		$previous_membership = \rcp_get_membership( (int) $membership->get_upgraded_from() );

		if ( false === $previous_membership ) {
			return new \WP_Error(
				'pronamic_pay_no_previous_membership',
				\__( 'The membership is not changed from a previous membership.', 'pronamic-pay-restrict-content' )
			);
		}

		$subscriptions = \get_pronamic_subscriptions_by_source( 'rcp_membership', (string) $previous_membership->get_id() );

		$subscription = \array_shift( $subscriptions );

		if ( null === $subscription ) {
			return new \WP_Error(
				'pronamic_pay_no_subscription',
				\__( 'The previous membership has no Pronamic Pay subscription.', 'pronamic-pay-restrict-content' )
			);
		}

		// Update subscription.
		$subscription->set_source_id( $membership->get_id() );

		$membership_level_name = $membership->get_membership_level_name();

		if ( false !== $membership_level_name ) {
			$subscription->set_description( $membership_level_name );
		}

		try {
			$subscription_updater = new SubscriptionUpdater( $membership, $subscription );

			$subscription_updater->update_pronamic_subscription();
		} catch ( \Exception $e ) {
			return new \WP_Error( 'pronamic_pay_subscription_update_failed', $e->getMessage() );
		}

		$subscription->set_status( SubscriptionStatus::ACTIVE );

		$subscription->add_note(
			\sprintf(
				/* translators: 1: previous Restrict Content Pro membership ID, 2: new Restrict Content Pro membership ID */
				\__( 'Subscription moved from Restrict Content Pro membership %1$s to membership %2$s because the membership level was changed.', 'pronamic-pay-restrict-content' ),
				$previous_membership->get_id(),
				$membership->get_id()
			)
		);

		$subscription->save();

		$subscription_id = (string) $subscription->get_id();

		/**
		 * Disconnect the previous membership, otherwise status changes of the
		 * disabled membership would still update the moved subscription.
		 *
		 * @see \Pronamic\WordPress\Pay\Extensions\RestrictContent\Extension::rcp_transition_membership_status()
		 */
		if ( $previous_membership->get_gateway_subscription_id() === $subscription_id ) {
			$previous_membership->set_gateway_subscription_id( '' );
		}

		$previous_membership->add_note(
			\sprintf(
				/* translators: 1: Pronamic Pay subscription ID, 2: new Restrict Content Pro membership ID */
				\__( 'Pronamic Pay subscription %1$s moved to membership %2$s.', 'pronamic-pay-restrict-content' ),
				$subscription_id,
				$membership->get_id()
			)
		);

		// Connect the new membership.
		$gateway_customer_id = $previous_membership->get_gateway_customer_id();

		if ( '' !== $gateway_customer_id ) {
			$membership->set_gateway_customer_id( $gateway_customer_id );
		}

		$membership->set_gateway_subscription_id( $subscription_id );

		$membership->add_note(
			\sprintf(
				/* translators: 1: Pronamic Pay subscription ID, 2: previous Restrict Content Pro membership ID */
				\__( 'Pronamic Pay subscription %1$s moved from membership %2$s.', 'pronamic-pay-restrict-content' ),
				$subscription_id,
				$previous_membership->get_id()
			)
		);

		return true;
	}

	/**
	 * Get payment method.
	 *
	 * @return string|null
	 */
	public function get_pronamic_payment_method() {
		return $this->payment_method;
	}

	/**
	 * Get the Pronamic configuration ID for this gateway.
	 *
	 * @global array $rcp_options Restrict Content Pro options.
	 * @return string|null
	 */
	protected function get_pronamic_config_id() {
		global $rcp_options;

		$key = $this->id . '_config_id';

		$config_id = null;

		if ( array_key_exists( $key, $rcp_options ) ) {
			$config_id = $rcp_options[ $key ];
		}

		if ( empty( $config_id ) ) {
			$default_config_id = get_option( 'pronamic_pay_config_id' );

			if ( false !== $default_config_id ) {
				$config_id = $default_config_id;
			}
		}

		return $config_id;
	}

	/**
	 * Get the Pronamic gateway.
	 *
	 * @return PronamicGateway|null
	 */
	private function get_pronamic_gateway() {
		$config_id = $this->get_pronamic_config_id();

		if ( null === $config_id ) {
			return null;
		}

		if ( '' === $config_id ) {
			return null;
		}

		return Plugin::get_gateway( (int) $config_id );
	}

	/**
	 * Get label from settings, fallback to core payment method name.
	 *
	 * @return string
	 */
	public function get_label() {
		global $rcp_options;

		$label = (string) PaymentMethods::get_name( $this->payment_method, __( 'Pronamic', 'pronamic-pay-restrict-content' ) );

		// Check options.
		if ( ! \is_array( $rcp_options ) ) {
			return $label;
		}

		// Get label from options.
		$option = $this->id . '_label';

		$value = $rcp_options[ $option ] ?? null;

		if ( \is_scalar( $value ) && ! empty( $value ) ) {
			return (string) $value;
		}

		return $label;
	}

	/**
	 * Get admin label.
	 *
	 * @return string
	 */
	public function get_admin_label() {
		return \sprintf(
			'%s - %s',
			\__( 'Pronamic', 'pronamic-pay-restrict-content' ),
			PaymentMethods::get_name( $this->payment_method )
		);
	}

	/**
	 * Add the iDEAL configuration settings to the Restrict Content Pro payment gateways settings page.
	 *
	 * @link https://github.com/restrictcontentpro/restrict-content-pro/blob/2.2.8/includes/admin/settings/register-settings.php#L126
	 * @param array<string, mixed> $rcp_options Restrict Content Pro options.
	 * @return void
	 */
	public function payments_settings( $rcp_options ) {
		$config_option = $this->id . '_config_id';
		$label_option  = $this->id . '_label';

		?>

		<table class="form-table">
			<tr valign="top">
				<th colspan=2>
					<?php

					printf(
						'<h3>%s</h3>',
						esc_html(
							sprintf(
								/* translators: %s: admin label */
								__( '%s Settings', 'pronamic-pay-restrict-content' ),
								$this->get_admin_label()
							)
						)
					);

					?>
				</th>
			</tr>

			<tr>
				<th>
					<label for="rcp_settings[<?php echo esc_attr( $this->id . '_label' ); ?>]"><?php esc_html_e( 'Checkout label', 'pronamic-pay-restrict-content' ); ?></label>
				</th>
				<td>
					<input class="regular-text" id="rcp_settings[<?php echo esc_attr( $label_option ); ?>]" style="width: 300px;" name="rcp_settings[<?php echo esc_attr( $label_option ); ?>]" value="<?php echo esc_attr( $this->get_label() ); ?>"/>

					<p class="description"><?php esc_html_e( 'Enter a label to display at checkout.', 'pronamic-pay-restrict-content' ); ?></p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="rcp_settings[<?php echo esc_attr( $config_option ); ?>]"><?php esc_html_e( 'Gateway Configuration', 'pronamic-pay-restrict-content' ); ?></label>
				</th>
				<td>
					<?php

					AdminModule::dropdown_configs(
						[
							'name'           => 'rcp_settings[' . esc_attr( $config_option ) . ']',
							'selected'       => $this->get_pronamic_config_id(),
							'payment_method' => $this->payment_method,
						]
					);

					?>

					<p class="description"><?php esc_html_e( 'Choose your configuration.', 'pronamic-pay-restrict-content' ); ?></p>
				</td>
			</tr>
		</table>

		<?php
	}

	/**
	 * Payment fields for this gateway
	 *
	 * @version 1.0.0
	 * @link https://github.com/restrictcontentpro/restrict-content-pro/blob/1.9.4/includes/checkout/template.php#L167
	 * @return void
	 */
	public function payment_fields() {
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $this->fields();
	}

	/**
	 * Fields.
	 *
	 * @return string
	 */
	public function fields() {
		$gateway = $this->get_pronamic_gateway();

		if ( null === $gateway ) {
			return '';
		}

		if ( null === $this->payment_method ) {
			return '';
		}

		$payment_method = $gateway->get_payment_method( $this->payment_method );

		if ( null === $payment_method ) {
			return '';
		}

		$fields = $payment_method->get_fields();

		if ( empty( $fields ) ) {
			return '';
		}

		$output = '<fieldset class="rcp_card_fieldset"><p>';

		foreach ( $fields as $field ) {
			$output .= $field->render();
		}

		$output .= '</p></fieldset>';

		return $output;
	}

	/**
	 * Process signup.
	 *
	 * @return void
	 * @throws \Exception Throws an exception if the Restrict Content data does not meet expectations.
	 */
	public function process_signup() {
		if ( ! \property_exists( $this->payment, 'id' ) ) {
			throw new \Exception( 'Payment object from Restrict Content gateway object does not contain an ID.' );
		}

		$gateway = $this->get_pronamic_gateway();

		if ( empty( $gateway ) ) {
			\do_action( 'rcp_registration_failed', $this );

			\wp_die(
				\esc_html( Plugin::get_default_error_message() ),
				\esc_html__( 'Payment Error', 'pronamic-pay-restrict-content' ),
				[ 'response' => 401 ]
			);
		}

		$payment = Util::new_payment_from_rcp_gateway( $this );

		$payment->config_id = (int) $this->get_pronamic_config_id();

		$payment->set_payment_method( $this->payment_method );

		try {
			$payment = Plugin::start_payment( $payment );

			Util::connect_pronamic_payment_id_to_rcp_membership( $this->membership->get_id(), $payment );
			Util::connect_pronamic_payment_id_to_rcp_payment( $this->payment->id, $payment );
		} catch ( \Exception $e ) {
			\do_action( 'rcp_registration_failed', $this );

			\wp_die(
				\esc_html(
					\sprintf(
						/* translators: %s: JSON encoded payment data */
						__( 'Payment creation failed before sending buyer to the payment provider. Error: %s', 'pronamic-pay-restrict-content' ),
						$e->getMessage()
					)
				),
				\esc_html__( 'Payment Error', 'pronamic-pay-restrict-content' ),
				[ 'response' => 401 ]
			);
		}

		$gateway->redirect( $payment );

		exit;
	}
}
