<?php

/**
 * Title: Restrict Content Pro Bitcoin gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_RCP_BitcoinGateway extends Pronamic_WP_Pay_Extensions_RCP_Gateway {
	/**
	 * Construct and initialize Credit Card gateway
	 */
	public function init() {
		$this->id             = 'pronamic_pay_bitcoin';
		$this->label          = __( 'Bitcoin', 'pronamic_ideal' );
		$this->admin_label    = __( 'Bitcoin', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::BITCOIN;
	}
}
