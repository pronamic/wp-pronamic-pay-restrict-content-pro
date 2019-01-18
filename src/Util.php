<?php
/**
 * Util
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use WP_Query;

/**
 * Util
 *
 * @author  Reüel van der Steege
 * @version 2.0.0
 * @since   1.0.0
 */
class Util {
	/**
	 * Get Pronamic RCP subscription for user.
	 *
	 * @param int|string $user_id WordPress user ID.
	 *
	 * @return Subscription|null
	 */
	public static function get_subscription_by_user( $user_id = null ) {
		if ( empty( $user_id ) ) {
			return;
		}

		$query = new WP_Query(
			array(
				'fields'         => 'ids',
				'post_type'      => 'pronamic_pay_subscr',
				'post_status'    => 'any',
				'author'         => $user_id,
				'meta_query'     => array(
					array(
						'key'   => '_pronamic_subscription_source',
						'value' => 'restrictcontentpro',
					),
				),
				'no_found_rows'  => true,
				'order'          => 'DESC',
				'orderby'        => 'ID',
				'posts_per_page' => 1,
			)
		);

		$post_id = reset( $query->posts );

		if ( false === $post_id ) {
			return;
		}

		return new Subscription( $post_id );
	}
}
