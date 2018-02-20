<?php

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Title: Restrict Content Pro Bank Transfer gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Reüel van der Steege
 * @version 1.0.0
 * @since   1.0.0
 */
class BankTransferGateway extends Gateway {
	/**
	 * Gateway id.
	 */
	protected $id = 'pronamic_pay_bank_transfer';

	/**
	 * Payment method.
	 *
	 * @var string $payment_method
	 */
	protected $payment_method = PaymentMethods::BANK_TRANSFER;
}
