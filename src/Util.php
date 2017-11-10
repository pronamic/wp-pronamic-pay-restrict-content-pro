<?php

/**
 * Title: Restrict Content Pro Util
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_RCP_Util {
	/**
	 * Get Pronamic RCP subscription for user.
	 *
	 * @return boolean
	 */
	public static function get_subscription_by_user( $user_id = null ) {
		if ( ! $user_id ) {
			return;
		}

		global $wpdb;

		$subscription = null;

		$db_query = $wpdb->prepare( "
		SELECT
			p.ID
		FROM
			wp_posts as `p`
		LEFT JOIN wp_postmeta as `m` ON p.ID = m.post_id
		WHERE
			p.post_type = 'pronamic_pay_subscr'
				AND
			p.post_author = '%s'
				AND
			m.meta_key = '_pronamic_subscription_source'
				AND
			m.meta_value = 'restrictcontentpro'
		ORDER BY p.ID DESC
		LIMIT 1;", $user_id );

		$post_id = $wpdb->get_var( $db_query ); // WPCS: unprepared SQL ok.

		if ( $post_id ) {
			$subscription = new Pronamic_WP_Pay_Subscription( $post_id );
		}

		return $subscription;
	}
}
