<?php
use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Title: Restrict Content Pro Sofort gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_RCP_SofortGateway extends Pronamic_WP_Pay_Extensions_RCP_Gateway {
	/**
	 * Initialize Sofort gateway
	 */
	public function init() {
		$this->id             = 'pronamic_pay_sofort';
		$this->label          = __( 'SOFORT Banking', 'pronamic_ideal' );
		$this->admin_label    = __( 'SOFORT Banking', 'pronamic_ideal' );
		$this->payment_method = PaymentMethods::SOFORT;
	}
}
