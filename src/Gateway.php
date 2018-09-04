<?php
/**
 * Gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
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
 * @version 2.0.2
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
	 * Admin label
	 *
	 * @var string
	 */
	protected $admin_label;

	/**
	 * Label
	 *
	 * @var string
	 */
	protected $label;

	/**
	 * Initialize.
	 */
	public function init() {
		$this->label       = PaymentMethods::get_name( $this->payment_method, __( 'Pronamic', 'pronamic_ideal' ) );
		$this->admin_label = PaymentMethods::get_name( $this->payment_method, __( 'Pronamic', 'pronamic_ideal' ) );

		if ( PaymentMethods::is_direct_debit_method( $this->payment_method ) ) {
			$this->supports = array(
				'recurring',
			);
		}
	}

	/**
	 * Add the iDEAL configuration settings to the Restrict Content Pro payment gateways settings page.
	 *
	 * @see https://github.com/restrictcontentpro/restrict-content-pro/blob/2.2.8/includes/admin/settings/register-settings.php#L126
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
						esc_html( sprintf(
							/* translators: %s: admin label */
							__( '%s Settings', 'pronamic_ideal' ),
							$this->admin_label
						) )
					);

					?>
				</th>
			</tr>

			<tr>
				<th>
					<label for="rcp_settings[<?php echo esc_attr( $this->id . '_label' ); ?>]"><?php esc_html_e( 'Checkout label', 'pronamic_ideal' ); ?></label>
				</th>
				<td>
					<?php

					$label = $this->label;

					if ( isset( $rcp_options[ $label_option ] ) ) {
						$label = $rcp_options[ $label_option ];
					}

					?>

					<input class="regular-text" id="rcp_settings[<?php echo esc_attr( $label_option ); ?>]" style="width: 300px;" name="rcp_settings[<?php echo esc_attr( $label_option ); ?>]" value="<?php echo esc_attr( $label ); ?>"/>

					<p class="description"><?php esc_html_e( 'Enter a label to display at checkout.', 'pronamic_ideal' ); ?></p>
				</td>
			</tr>

			<tr>
				<th>
					<label for="rcp_settings[<?php echo esc_attr( $config_option ); ?>]"><?php esc_html_e( 'Gateway Configuration', 'pronamic_ideal' ); ?></label>
				</th>
				<td>
					<?php

					$config_id = get_option( 'pronamic_pay_config_id' );

					if ( isset( $rcp_options[ $config_option ] ) ) {
						$config_id = $rcp_options[ $config_option ];
					}

					AdminModule::dropdown_configs( array(
						'name'           => 'rcp_settings[' . esc_attr( $config_option ) . ']',
						'selected'       => $config_id,
						'payment_method' => $this->payment_method,
					) );

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
		echo $this->fields(); // WPCS: XSS ok.
	}

	/**
	 * Fields.
	 *
	 * @return string
	 */
	public function fields() {
		global $rcp_options;

		ob_start();

		$gateway = Plugin::get_gateway( $rcp_options[ $this->id . '_config_id' ] );

		if ( $gateway ) {
			$gateway->set_payment_method( $this->payment_method );

			$input = $gateway->get_input_html();

			if ( $input ) {
				echo '<fieldset class="rcp_card_fieldset"><p>';
				echo $input; // WPCS: XSS ok.
				echo '</p></fieldset>';
			}
		}

		return ob_get_clean();
	}

	/**
	 * Process signup.
	 */
	public function process_signup() {
		global $rcp_options;

		$config_id = $rcp_options[ $this->id . '_config_id' ];

		$gateway = Plugin::get_gateway( $config_id );

		if ( empty( $gateway ) ) {
			do_action( 'rcp_registration_failed', $this );

			wp_die(
				esc_html( Plugin::get_default_error_message() ),
				esc_html__( 'Payment Error', 'pronamic_ideal' ),
				array( 'response' => '401' )
			);
		}

		$data = new PaymentData( $this );

		// Start payment.
		if ( $data->get_subscription_id() ) {
			$new_subscription = $data->get_subscription();

			$update_meta = array(
				'amount'          => $new_subscription->get_amount()->get_amount(),
				'frequency'       => $new_subscription->get_frequency(),
				'interval'        => $new_subscription->get_interval(),
				'interval_period' => $new_subscription->get_interval_period(),
			);

			$subscription = get_pronamic_subscription( $data->get_subscription_id() );

			$subscription->update_meta( $update_meta );

			// Set updated meta in subscription.
			$subscription->set_amount( $new_subscription->get_amount() );

			$subscription->frequency       = $update_meta['frequency'];
			$subscription->interval        = $update_meta['interval'];
			$subscription->interval_period = $update_meta['interval_period'];

			// Start recurring.
			$payment = Plugin::start_recurring( $subscription, $gateway, $data );
		} else {
			// Start.
			$payment = Plugin::start( $config_id, $gateway, $data, $this->payment_method );
		}

		$error = $gateway->get_error();

		if ( is_wp_error( $error ) ) {
			do_action( 'rcp_registration_failed', $this );

			wp_die(
				esc_html( sprintf(
					/* translators: %s: JSON encoded payment data */
					__( 'Payment creation failed before sending buyer to the payment provider. Payment data: %s', 'pronamic_ideal' ),
					wp_json_encode( $payment_data )
				) ),
				esc_html__( 'Payment Error', 'pronamic_ideal' ),
				array( 'response' => '401' )
			);
		}

		// Transaction ID.
		if ( '' !== $payment->get_transaction_id() ) {
			$rcp_payment_data['transaction_id'] = $payment->get_transaction_id();

			$payments->update( $payment_id, $rcp_payment_data );
		}

		$gateway->redirect( $payment );

		exit;
	}
}
