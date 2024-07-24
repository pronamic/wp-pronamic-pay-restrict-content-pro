<?php
/**
 * Card gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContent
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContent\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Card gateway
 *
 * @author  Reüel van der Steege
 * @version 4.6.0
 * @since   4.6.0
 */
class CardGateway extends Gateway {
	/**
	 * Gateway id.
	 *
	 * @var string
	 */
	protected $id = 'pronamic_pay_card';

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::CARD;
}
