<?php
/**
 * Direct Debit (mandate via Bancontact) gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Direct Debit (mandate via Bancontact) gateway
 *
 * @author  Reüel van der Steege
 * @version 2.0.0
 * @since   1.0.0
 */
class DirectDebitBancontactGateway extends Gateway {
	/**
	 * Gateway id.
	 *
	 * @var string
	 */
	protected $id = 'pronamic_pay_direct_debit_bancontact';

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::DIRECT_DEBIT_BANCONTACT;
}
