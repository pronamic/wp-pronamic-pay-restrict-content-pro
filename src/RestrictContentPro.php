<?php

/**
 * Title: Restrict Content Pro
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_RCP_RestrictContentPro {
	/**
	 * Payment status cancelled
	 *
	 * @var string
	 */
	const PAYMENT_STATUS_CANCELLED = 'cancelled';

	/**
	 * Payment status complete
	 *
	 * @var string
	 */
	const PAYMENT_STATUS_COMPLETE = 'complete';

	/**
	 * Payment status expired
	 *
	 * @var string
	 */
	const PAYMENT_STATUS_EXPIRED = 'expired';

	/**
	 * Payment status failed
	 *
	 * @var string
	 */
	const PAYMENT_STATUS_FAILED = 'failed';

	/**
	 * Payment status pending
	 *
	 * @var string
	 */
	const PAYMENT_STATUS_PENDING = 'pending';

	/**
	 * Payment status refunded
	 *
	 * @var string
	 */
	const PAYMENT_STATUS_REFUNDED = 'refunded';

	//////////////////////////////////////////////////

	/**
	 * Check if Restrict Content Pro is active
	 *
	 * @return boolean
	 */
	public static function is_active() {
		return defined( 'RCP_PLUGIN_VERSION' );
	}
}
