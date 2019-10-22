<?php

/**
 * Update source ID of subscriptions.
 */
$query = new \WP_Query(
	array(
		'post_type'     => 'pronamic_pay_subscr',
		'post_status'   => 'any',
		'meta_query'    => array(
			array(
				'key'   => '_pronamic_subscription_source',
				'value' => 'restrictcontentpro',
			),
		),
		'nopaging'      => true,
		'no_found_rows' => true,
		'order'         => 'DESC',
		'orderby'       => 'ID',
	)
);

if ( $query->have_posts() ) {
	while ( $query->have_posts() ) {
		$query->the_post();

		$post_id = \get_the_ID();

		$source_id = \get_post_meta( $post_id, '_pronamic_subscription_source_id', true );
	}

	\wp_reset_postdata();
}
