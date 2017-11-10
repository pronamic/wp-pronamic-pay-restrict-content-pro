<?php

/**
 * Title: Restrict Content Pro Bancontact gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_RCP_BancontactGateway extends Pronamic_WP_Pay_Extensions_RCP_Gateway {
	/**
	 * Initialize Bancontact gateway
	 */
	public function init() {
		$this->id             = 'pronamic_pay_bancontact';
		$this->label          = __( 'Bancontact', 'pronamic_ideal' );
		$this->admin_label    = __( 'Bancontact', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::BANCONTACT;
	}
}
