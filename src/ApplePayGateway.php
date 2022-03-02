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
}
