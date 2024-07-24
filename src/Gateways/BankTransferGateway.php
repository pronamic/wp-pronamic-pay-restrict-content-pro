<?php
/**
 * Bank Transfer gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContent
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContent\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Bank Transfer gateway
 *
 * @author  Reüel van der Steege
 * @version 2.0.0
 * @since   1.0.0
 */
class BankTransferGateway extends Gateway {
	/**
	 * Gateway id.
	 *
	 * @var string
	 */
	protected $id = 'pronamic_pay_bank_transfer';

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::BANK_TRANSFER;
}
