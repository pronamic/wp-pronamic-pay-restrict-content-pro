<?php
/**
 * Gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Admin\AdminModule;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;
use RCP_Member;
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
	 * @var string
	 */
	protected $payment_method;

	/**
	 * Initialize.
	 */
	public function init() {
		// Set supported features based on gateway.
		$gateway = Plugin::get_gateway( $this->get_pronamic_config_id() );

		if (
			null !== $gateway
				&&
			$gateway->supports( 'recurring' )
				&&
			(
				PaymentMethods::is_recurring_method( $this->payment_method )
					||
				\in_array( $this->payment_method, PaymentMethods::get_recurring_methods(), true )
			)
		) {
			$this->supports = array(
				'recurring',
				'trial',
			);
		}
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
	 * Get label from settings, fallback to core payment method name.
	 *
	 * @return string|null
	 */
	public function get_label() {
		global $rcp_options;

		$label = PaymentMethods::get_name( $this->payment_method, __( 'Pronamic', 'pronamic_ideal' ) );

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
	 *
	 * @param array $rcp_options Restrict Content Pro options.
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
						array(
							'name'           => 'rcp_settings[' . esc_attr( $config_option ) . ']',
							'selected'       => $this->get_pronamic_config_id(),
							'payment_method' => $this->payment_method,
						)
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
	 * @see     https://github.com/restrictcontentpro/restrict-content-pro/blob/1.9.4/includes/checkout/template.php#L167
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
		ob_start();

		$gateway = Plugin::get_gateway( $this->get_pronamic_config_id() );

		if ( $gateway ) {
			$gateway->set_payment_method( $this->payment_method );

			$input = $gateway->get_input_html();

			if ( $input ) {
				echo '<fieldset class="rcp_card_fieldset"><p>';
				// phpcs:ignore WordPress.Security.EscapeOutput
				echo $input;
				echo '</p></fieldset>';
			}
		}

		return ob_get_clean();
	}

	/**
	 * Process signup.
	 *
	 * @global RCP_Payments $rcp_payments_db Restrict Content Pro payments object.
	 */
	public function process_signup() {
		global $rcp_payments_db;

		$config_id = $this->get_pronamic_config_id();

		$gateway = Plugin::get_gateway( $config_id );

		if ( empty( $gateway ) ) {
			do_action( 'rcp_registration_failed', $this );

			wp_die(
				esc_html( Plugin::get_default_error_message() ),
				esc_html__( 'Payment Error', 'pronamic_ideal' ),
				array( 'response' => '401' )
			);
		}

		$payment = Util::new_payment_from_rcp_gateway( $this );

		$payment->config_id = $config_id;

		$payment->set_payment_method( $this->payment_method );

		try {
			$payment = Plugin::start_payment( $payment, $gateway );
		} catch ( \Exception $e ) {
			do_action( 'rcp_registration_failed', $this );

			wp_die(
				esc_html(
					sprintf(
						/* translators: %s: JSON encoded payment data */
						__( 'Payment creation failed before sending buyer to the payment provider. Error: %s', 'pronamic_ideal' ),
						$e->getMessage()
					)
				),
				esc_html__( 'Payment Error', 'pronamic_ideal' ),
				array( 'response' => '401' )
			);
		}

		// Transaction ID.
		$transaction_id = $payment->get_transaction_id();

		if ( null !== $transaction_id ) {
			$rcp_payments_db->update(
				$this->payment->id,
				array(
					'transaction_id' => $transaction_id,
				)
			);
		}

		$gateway->redirect( $payment );

		exit;
	}
}
