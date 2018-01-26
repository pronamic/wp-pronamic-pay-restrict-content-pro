<?php
use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Title: Restrict Content Pro Direct Debit (mandate via Sofort) gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.1
 * @since 1.0.1
 */
class Pronamic_WP_Pay_Extensions_RCP_DirectDebitSofortGateway extends Pronamic_WP_Pay_Extensions_RCP_Gateway {
	/**
	 * Initialize Direct Debit (mandate via Sofort) gateway
	 */
	public function init() {
		$this->id             = 'pronamic_pay_direct_debit_sofort';
		$this->label          = __( 'Direct Debit (mandate via Sofort)', 'pronamic_ideal' );
		$this->admin_label    = __( 'Direct Debit (mandate via Sofort)', 'pronamic_ideal' );
		$this->payment_method = PaymentMethods::DIRECT_DEBIT_SOFORT;
		$this->supports       = array(
			'recurring',
		);
	}
}
