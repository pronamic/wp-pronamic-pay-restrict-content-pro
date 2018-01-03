<?php

/**
 * Title: Restrict Content Pro PayPal gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.1
 * @since 1.0.1
 */
class Pronamic_WP_Pay_Extensions_RCP_PayPalGateway extends Pronamic_WP_Pay_Extensions_RCP_Gateway {
	/**
	 * Construct and initialize PayPal gateway
	 */
	public function init() {
		$this->id             = 'pronamic_pay_paypal';
		$this->label          = __( 'PayPal', 'pronamic_ideal' );
		$this->admin_label    = __( 'PayPal', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::PAYPAL;
	}
}
