<?php
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: Restrict Content Pro gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version unreleased
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_RCP_Gateway extends RCP_Payment_Gateway {
	/**
	 * ID
	 *
	 * @var string
	 */
	protected $id;

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
	 * Payment method
	 *
	 * @var string
	 */
	protected $payment_method;

	//////////////////////////////////////////////////

	/**
	 * Bootstrap
	 *
	 * @param array $subscription_data
	 */
	public function __construct( $subscription_data = array() ) {
		global $rcp_options;

		parent::__construct( $subscription_data );

		// Settings
		if ( isset( $rcp_options[ $this->id . '_label' ] ) && ! empty( $rcp_options[ $this->id . '_label' ] ) ) {
			$this->label = $rcp_options[ $this->id . '_label' ];
		}

		// Actions
		add_action( 'rcp_gateway_' . $this->id, array( $this, 'process_purchase' ) );

		// Filters
		add_filter( 'rcp_payments_settings', array( $this, 'payments_settings' ) );

		$config_option = $this->id . '_config_id';

		if ( is_admin() || ( isset( $rcp_options[ $config_option ] ) && '0' !== $rcp_options[ $config_option ] ) ) {
			add_filter( 'rcp_payment_gateways', array( $this, 'payment_gateways' ) );
		}

		add_filter( 'rcp_get_payment_transaction_id-' . $this->id, array( $this, 'get_payment_transaction_id' ) );
	}

	//////////////////////////////////////////////////

	/**
	 * Init
	 */
	public function init() {
		$this->id          = 'pronamic_pay';
		$this->label       = __( 'Pronamic', 'pronamic_ideal' );
		$this->admin_label = __( 'Pronamic', 'pronamic_ideal' );
	}

	//////////////////////////////////////////////////

	/**
	 * Add the gateway to Restrict Content Pro
	 *
	 * @param mixed $gateways
	 *
	 * @return mixed $gateways
	 */
	public function payment_gateways( $gateways ) {
		$gateways[ $this->id ] = array(
			'label'       => $this->label,
			'admin_label' => $this->admin_label,
			'supports'    => $this->supports,
			'class'       => get_class( $this ),
		);

		return $gateways;
	}

	//////////////////////////////////////////////////

	/**
	 * Add the iDEAL configuration settings to the Restrict Content Pro payment gateways settings page.
	 *
	 * @see https://github.com/restrictcontentpro/restrict-content-pro/blob/2.2.8/includes/admin/settings/register-settings.php#L126
	 *
	 * @param mixed $settings_gateways
	 * @return mixed $settings_gateways
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

					<input class="regular-text" id="rcp_settings[<?php echo esc_attr( $label_option ); ?>]" style="width: 300px;" name="rcp_settings[<?php echo esc_attr( $label_option ); ?>]" value="<?php echo esc_attr( $label ); ?>" />

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

					Pronamic_WP_Pay_Admin::dropdown_configs( array(
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

	//////////////////////////////////////////////////

	/**
	 * Payment fields for this gateway
	 *
	 * @version 1.0.0
	 * @see https://github.com/restrictcontentpro/restrict-content-pro/blob/1.9.4/includes/checkout/template.php#L167
	 */
	public function payment_fields() {
		echo $this->fields(); // WPCS: XSS ok.
	}

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

	//////////////////////////////////////////////////

	/**
	 * The $purchase_data array consists of the following data:
	 *
	 * $purchase_data = array(
	 *   'downloads'    => array of download IDs,
	 *   'tax'          => taxed amount on shopping cart
	 *   'subtotal'     => total price before tax
	 *   'price'        => total price of cart contents after taxes,
	 *   'purchase_key' => Random key
	 *   'user_email'   => $user_email,
	 *   'date'         => date( 'Y-m-d H:i:s' ),
	 *   'user_id'      => $user_id,
	 *   'post_data'    => $_POST,
	 *   'user_info'    => array of user's information and used discount code
	 *   'cart_details' => array of cart details,
	 * );
	 */
	public function process_purchase( $subscription_data ) {
		global $rcp_options;

		$config_id = $rcp_options[ $this->id . '_config_id' ];

		$rcp_transaction_id = $this->generate_transaction_id();

		// Collect payment data
		$rcp_payment_data = array(
			'subscription'     => $subscription_data['subscription_name'],
			'date'             => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
			'amount'           => $subscription_data['price'] + $subscription_data['fee'],
			'user_id'          => $subscription_data['user_id'],
			'payment_type'     => $this->admin_label,
			'subscription_key' => $subscription_data['key'],
			'transaction_id'   => $rcp_transaction_id,
			'status'           => 'pending',
		);

		$data = array(
			'email'             => $subscription_data['user_email'],
			'user_name'         => $subscription_data['user_name'],
			'currency'          => $subscription_data['currency'],
			'discount'          => $subscription_data['discount'],
			'discount_code'     => $subscription_data['discount_code'],
			'length'            => $subscription_data['length'],
			'length_unit'       => $subscription_data['length_unit'],
			'signup_fee'        => $this->supports( 'fees' ) ? $subscription_data['fee'] : 0,
			'subscription_id'   => $subscription_data['subscription_id'],
			'subscription_name' => $subscription_data['subscription_name'],
			'auto_renew'        => $this->supports( 'recurring' ) ? $subscription_data['auto_renew'] : false,
			'return_url'        => $subscription_data['return_url'],
			'subscription_data' => $subscription_data,
		);

		$payment_data = array_merge( $rcp_payment_data, $data );

		// Set user recurring option.
		$member = new RCP_Member( $payment_data['user_id'] );

		$member->set_recurring( $payment_data['auto_renew'] );

		// Record the pending payment
		$payments = new RCP_Payments();

		if ( ! is_callable( array( $member, 'get_pending_payment_id' ) ) || ! $member->get_pending_payment_id() ) {
			$payment_id = $payments->insert( $rcp_payment_data );
		} else {
			$payment_id = $member->get_pending_payment_id();

			$payments->update( $payment_id, $rcp_payment_data );
		}

		// Check payment
		if ( ! $payment_id ) {
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
		} else {
			$data = new Pronamic_WP_Pay_Extensions_RCP_PaymentData( $payment_id, $payment_data );

			$gateway = Plugin::get_gateway( $config_id );

			if ( $gateway ) {
				// Maybe update existing subscription
				if ( $data->get_subscription_id() ) {
					$new_subscription = $data->get_subscription();

					$update_meta = array(
						'amount'          => $new_subscription->get_amount(),
						'frequency'       => $new_subscription->get_frequency(),
						'interval'        => $new_subscription->get_interval(),
						'interval_period' => $new_subscription->get_interval_period(),
					);

					$subscription = get_pronamic_subscription( $data->get_subscription_id() );

					$subscription->update_meta( $update_meta );
				}

				// Start
				$payment = Plugin::start( $config_id, $gateway, $data, $this->payment_method );

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
				} else {
					// Transaction ID
					if ( '' !== $payment->get_transaction_id() ) {
						$rcp_payment_data['transaction_id'] = $payment->get_transaction_id();

						$payments->update( $payment_id, $rcp_payment_data );
					}

					$gateway->redirect( $payment );

					exit;
				}
			} else {
				do_action( 'rcp_registration_failed', $this );

				wp_die(
					esc_html( Plugin::get_default_error_message() ),
					esc_html__( 'Payment Error', 'pronamic_ideal' ),
					array( 'response' => '401' )
				);
			}
		}
	}
}
