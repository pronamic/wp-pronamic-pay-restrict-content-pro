<?php
/**
 * Restrict Content Pro membership status
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

/**
 * Restrict Content Pro membership status
 *
 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/memberships/edit-membership.php#L105-112
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class MembershipStatus {
	/**
	 * Membership status active.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/memberships/edit-membership.php#L105-112
	 *
	 * @var string
	 */
	const ACTIVE = 'active';

	/**
	 * Membership status expired.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/memberships/edit-membership.php#L105-112
	 *
	 * @var string
	 */
	const EXPIRED = 'expired';

	/**
	 * Membership status cancelled.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/memberships/edit-membership.php#L105-112
	 *
	 * @var string
	 */
	const CANCELLED = 'cancelled';

	/**
	 * Membership status pending.
	 *
	 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/memberships/edit-membership.php#L105-112
	 *
	 * @var string
	 */
	const PENDING = 'pending';

	/**
	 * Convert a Restrict Content Pro membership status to a core subscription status.
	 *
	 * @link https://github.com/wp-pay/core/blob/2.1.6/src/Core/Statuses.php
	 *
	 * @param string|null $rcp_status Restrict Content Pro membership status.
	 * @return string|null Core subscription status.
	 */
	public static function to_core( $rcp_status ) {
		switch ( $rcp_status ) {
			case self::ACTIVE:
				return null;
			case self::EXPIRED:
				return null;
			case self::CANCELLED:
				return null;
			case self::PENDING:
				return null;
			default:
				return null;
		}
	}
}
