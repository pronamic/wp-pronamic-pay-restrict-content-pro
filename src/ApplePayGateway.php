<?php
/**
 * Apple Pay gateway
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
 * Apple Pay gateway
 *
 * @author  Reüel van der Steege
 * @version 3.1.0
 * @since   3.1.0
 */
class ApplePayGateway extends Gateway {
	/**
	 * Gateway id.
	 *
	 * @var string
	 */
	protected $id = 'pronamic_pay_apple_pay';

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::APPLE_PAY;

	/**
	 * Construct and initialize Apple Pay gateway.
	 */
	public function init() {
		parent::init();

		// Support recurring subscription payments.
		$gateway = Plugin::get_gateway( $this->get_pronamic_config_id() );

		if ( null !== $gateway && $gateway->supports( 'recurring_apple_pay' ) ) {
			$this->supports = array(
				'recurring',
				'trial',
			);
		}
	}
}
