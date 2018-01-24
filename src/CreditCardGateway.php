<?php
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: Restrict Content Pro Credit Card gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_RCP_CreditCardGateway extends Pronamic_WP_Pay_Extensions_RCP_Gateway {
	/**
	 * Construct and initialize Credit Card gateway
	 */
	public function init() {
		global $rcp_options;

		$this->id             = 'pronamic_pay_credit_card';
		$this->label          = __( 'Credit Card', 'pronamic_ideal' );
		$this->admin_label    = __( 'Credit Card', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::CREDIT_CARD;

		// Recurring subscription payments
		$config_option = $this->id . '_config_id';

		if ( isset( $rcp_options[ $config_option ] ) ) {
			$gateway = Plugin::get_gateway( $rcp_options[ $config_option ] );

			if ( $gateway && $gateway->supports( 'recurring_credit_card' ) ) {
				$this->supports = array(
					'recurring',
				);
			}
		}
	}
}
