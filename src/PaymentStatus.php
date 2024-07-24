<?php
/**
 * Restrict Content Pro payment status
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContent
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContent;

use Pronamic\WordPress\Pay\Payments\PaymentStatus as Core_PaymentStatus;

/**
 * Restrict Content Pro payment status
 *
 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/payments/edit-payment.php#L104-118
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class PaymentStatus {
	/**
	 * Payment status pending.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/payments/edit-payment.php#L104-118
	 *
	 * @var string
	 */
	const PENDING = 'pending';

	/**
	 * Payment status complete.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/payments/edit-payment.php#L104-118
	 *
	 * @var string
	 */
	const COMPLETE = 'complete';

	/**
	 * Payment status failed.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/payments/edit-payment.php#L104-118
	 *
	 * @var string
	 */
	const FAILED = 'failed';

	/**
	 * Payment status refunded.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/payments/edit-payment.php#L104-118
	 *
	 * @var string
	 */
	const REFUNDED = 'refunded';

	/**
	 * Payment status abandoned.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/payments/edit-payment.php#L104-118
	 *
	 * @var string
	 */
	const ABANDONED = 'abandoned';

	/**
	 * Convert a core payment status to a Restrict Content Pro payment status.
	 *
	 * @link https://github.com/wp-pay/core/blob/2.1.6/src/Core/Statuses.php
	 *
	 * @param string|null $core_status Core payment status.
	 * @return string|null Restrict Content Pro payment status.
	 */
	public static function from_core( $core_status ) {
		switch ( $core_status ) {
			case Core_PaymentStatus::OPEN:
				return self::PENDING;
			case Core_PaymentStatus::CANCELLED:
				return self::FAILED;
			case Core_PaymentStatus::EXPIRED:
				return self::ABANDONED;
			case Core_PaymentStatus::FAILURE:
				return self::FAILED;
			case Core_PaymentStatus::SUCCESS:
				return self::COMPLETE;
			default:
				return null;
		}
	}
}
