<?php
/**
 * Credit Card gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Credit Card gateway
 *
 * @author  Reüel van der Steege
 * @version 2.0.0
 * @since   1.0.0
 */
class CreditCardGateway extends Gateway {
	/**
	 * Gateway id.
	 *
	 * @var string
	 */
	protected $id = 'pronamic_pay_credit_card';

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::CREDIT_CARD;

	/**
	 * Construct and initialize Credit Card gateway.
	 */
	public function init() {
		parent::init();

		// Support recurring subscription payments.
		$gateway = Plugin::get_gateway( $this->get_pronamic_config_id() );

		if ( null !== $gateway && $gateway->supports( 'recurring_credit_card' ) ) {
			$this->supports = array(
				'recurring',
				'trial',
			);
		}
	}
}
