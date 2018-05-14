<?php

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Title: Restrict Content Pro Sofort gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Reüel van der Steege
 * @version 2.0.0
 * @since   1.0.0
 */
class SofortGateway extends Gateway {
	/**
	 * Gateway id.
	 */
	protected $id = 'pronamic_pay_sofort';

	/**
	 * Payment method.
	 *
	 * @var string $payment_method
	 */
	protected $payment_method = PaymentMethods::SOFORT;
}
