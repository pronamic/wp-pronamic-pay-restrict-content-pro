<?php

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Title: Restrict Content Pro Direct Debit (mandate via Sofort) gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Reüel van der Steege
 * @version 1.0.1
 * @since   1.0.1
 */
class DirectDebitSofortGateway extends Gateway {
	/**
	 * Gateway id.
	 */
	protected $id = 'pronamic_pay_direct_debit_sofort';

	/**
	 * Payment method.
	 *
	 * @var string $payment_method
	 */
	protected $payment_method = PaymentMethods::DIRECT_DEBIT_SOFORT;
}
