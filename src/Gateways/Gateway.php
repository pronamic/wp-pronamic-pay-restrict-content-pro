<?php
/**
 * Gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContent
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContent\Gateways;

use Pronamic\WordPress\Pay\Admin\AdminModule;
use Pronamic\WordPress\Pay\Core\Gateway as PronamicGateway;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Extensions\RestrictContent\Util;
use Pronamic\WordPress\Pay\Plugin;
use RCP_Payment_Gateway;
use RCP_Payments;

/**
 * Gateway
 *
 * @author  Reüel van der Steege
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
					'trial',
				];
			}
		}
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

		$label = (string) PaymentMethods::get_name( $this->payment_method, __( 'Pronamic', 'pronamic_ideal' ) );

		// Check options.
		if ( ! \is_array( $rcp_options ) ) {
			return $label;
		}

		// Get label from options.
		$option = $this->id . '_label';

		if ( \array_key_exists( $option, $rcp_options ) && ! empty( $rcp_options[ $option ] ) ) {
			return (string) $rcp_options[ $option ];
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
			\__( 'Pronamic', 'pronamic_ideal' ),
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
								__( '%s Settings', 'pronamic_ideal' ),
								$this->get_admin_label()
							)
						)
					);

					?>
				</th>
			</tr>

			<tr>
				<th>
					<label for="rcp_settings[<?php echo esc_attr( $this->id . '_label' ); ?>]"><?php esc_html_e( 'Checkout label', 'pronamic_ideal' ); ?></label>
				</th>
				<td>
					<input class="regular-text" id="rcp_settings[<?php echo esc_attr( $label_option ); ?>]" style="width: 300px;" name="rcp_settings[<?php echo esc_attr( $label_option ); ?>]" value="<?php echo esc_attr( $this->get_label() ); ?>"/>

					<p class="description"><?php esc_html_e( 'Enter a label to display at checkout.', 'pronamic_ideal' ); ?></p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="rcp_settings[<?php echo esc_attr( $config_option ); ?>]"><?php esc_html_e( 'Gateway Configuration', 'pronamic_ideal' ); ?></label>
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

					<p class="description"><?php esc_html_e( 'Choose your configuration.', 'pronamic_ideal' ); ?></p>
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
				\esc_html__( 'Payment Error', 'pronamic_ideal' ),
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
						__( 'Payment creation failed before sending buyer to the payment provider. Error: %s', 'pronamic_ideal' ),
						$e->getMessage()
					)
				),
				\esc_html__( 'Payment Error', 'pronamic_ideal' ),
				[ 'response' => 401 ]
			);
		}

		$gateway->redirect( $payment );

		exit;
	}
}
