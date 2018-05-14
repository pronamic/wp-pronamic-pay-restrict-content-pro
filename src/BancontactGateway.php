<?php

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Title: Restrict Content Pro Bancontact gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Reüel van der Steege
 * @version 2.0.0
 * @since   1.0.0
 */
class BancontactGateway extends Gateway {
	/**
	 * Gateway id.
	 */
	protected $id = 'pronamic_pay_bancontact';

	/**
	 * Payment method.
	 *
	 * @var string $payment_method
	 */
	protected $payment_method = PaymentMethods::BANCONTACT;
}
