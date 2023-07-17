<?php
/**
 * Giropay gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2023 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Giropay gateway
 *
 * @author  Reüel van der Steege
 * @version 4.3.5
 * @since   4.3.5
 */
class GiropayGateway extends Gateway {
	/**
	 * Gateway id.
	 *
	 * @var string
	 */
	protected $id = 'pronamic_pay_giropay';

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::GIROPAY;
}
