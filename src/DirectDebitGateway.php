<?php
use Pronamic\WordPress\Pay\Core\PaymentMethods;

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
class Pronamic_WP_Pay_Extensions_RCP_DirectDebitGateway extends Pronamic_WP_Pay_Extensions_RCP_Gateway {
	/**
	 * Initialize Credit Card gateway
	 */
	public function init() {
		$this->id             = 'pronamic_pay_direct_debit';
		$this->label          = __( 'Direct Debit', 'pronamic_ideal' );
		$this->admin_label    = __( 'Direct Debit', 'pronamic_ideal' );
		$this->payment_method = PaymentMethods::DIRECT_DEBIT;
	}
}
