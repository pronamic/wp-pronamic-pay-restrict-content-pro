<?php
/**
 * Restrict Content Pro length unit
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContent
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContent;

use Pronamic\WordPress\Pay\Payments\PaymentStatus as Core_PaymentStatus;

/**
 * Restrict Content Pro length unit
 *
 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/payments/edit-payment.php#L104-118
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class LengthUnit {
	/**
	 * Day.
	 *
	 * @var string
	 */
	const DAY = 'day';

	/**
	 * Month.
	 *
	 * @var string
	 */
	const MONTH = 'month';

	/**
	 * Year.
	 *
	 * @var string
	 */
	const YEAR = 'year';

	/**
	 * Convert a Restrict Content Pro length unit to WordPress payment core unit.
	 *
	 * @param string|null $length_unit Restrict Content Pro unit.
	 * @return string|null WordPress payment core unit.
	 */
	public static function to_core( $length_unit ) {
		switch ( $length_unit ) {
			case self::DAY:
				return 'D';
			case self::MONTH:
				return 'M';
			case self::YEAR:
				return 'Y';
			default:
				return null;
		}
	}
}
