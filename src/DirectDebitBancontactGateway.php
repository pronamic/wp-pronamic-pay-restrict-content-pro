<?php

/**
 * Title: Restrict Content Pro Direct Debit (mandate via Bancontact) gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_RCP_DirectDebitBancontactGateway extends Pronamic_WP_Pay_Extensions_RCP_Gateway {
	/**
	 * Initialize Direct Debit (mandate via Bancontact) gateway
	 */
	public function init() {
		$this->id             = 'pronamic_pay_direct_debit_bancontact';
		$this->label          = __( 'Direct Debit (mandate via Bancontact)', 'pronamic_ideal' );
		$this->admin_label    = __( 'Direct Debit (mandate via Bancontact)', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT_BANCONTACT;
		$this->supports       = array(
			'recurring',
		);
	}
}
