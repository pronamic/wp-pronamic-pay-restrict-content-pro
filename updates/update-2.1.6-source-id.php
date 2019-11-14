<?php
/**
 * Updated 2.1.6 source ID.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus as CoreSubscriptionStatus;

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

		$subscription_post_id = \get_the_ID();

		if ( false === $subscription_post_id ) {
			continue;
		}

		/**
		 * Get subscription.
		 *
		 * @link https://github.com/wp-pay/core/blob/2.2.4/includes/functions.php#L158-L180
		 */
		$subscription = \get_pronamic_subscription( $subscription_post_id );

		if ( null === $subscription ) {
			continue;
		}

		/**
		 * Get source.
		 */
		$subscription_source_id = \get_post_meta( $subscription_post_id, '_pronamic_subscription_source_id', true );

		$potential_user_id = $subscription_source_id;

		/**
		 * Get customer by user ID.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.1/includes/customers/customer-functions.php#L15-34
		 */
		$rcp_customer = \rcp_get_customer_by_user_id( $potential_user_id );

		if ( false === $rcp_customer ) {
			$subscription->set_status( CoreSubscriptionStatus::ON_HOLD );

			$subscription->add_note(
				\sprintf(
					/* translators: %s: Potential WordPress user ID. */
					__( 'Since Restrict Content Pro 3 a subscription must be linked to a Restrict Content Pro membership. Unfortunately, this subscription could not be linked to a Restrict Content Pro membership based on the WordPress user ID %s. That is why this subscription has been put on hold so that it can be corrected manually.', 'pronamic_ideal' ),
					$potential_user_id
				)
			);

			$subscription->save();

			continue;
		}

		/**
		 * Get customer single membership by customer ID.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.1/includes/customers/customer-functions.php#L280-320
		 */
		$rcp_membership = \rcp_get_customer_single_membership( $rcp_customer->get_id() );

		if ( false === $rcp_membership ) {
			$subscription->set_status( CoreSubscriptionStatus::ON_HOLD );

			$subscription->add_note(
				\sprintf(
					/* translators: %s: Potential Restrict Content Pro customer ID. */
					__( 'Since Restrict Content Pro 3 a subscription must be linked to a Restrict Content Pro membership. Unfortunately, this subscription could not be linked to a Restrict Content Pro membership based on the Restrict Content Pro customer ID %s. That is why this subscription has been put on hold so that it can be corrected manually.', 'pronamic_ideal' ),
					$rcp_customer->get_id()
				)
			);

			$subscription->save();

			continue;
		}

		/**
		 * Update meta.
		 *
		 * @link https://developer.wordpress.org/reference/functions/update_post_meta/
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.1/includes/memberships/class-rcp-membership.php#L354-363
		 */
		$result = \update_post_meta( $subscription_post_id, '_pronamic_payment_source', 'rcp_membership' );

		if ( false === $result ) {
			// What to do?

			continue;
		}

		$result = \update_post_meta( $subscription_post_id, '_pronamic_payment_source_id', $rcp_membership->get_id() );

		if ( false === $result ) {
			// What to do?

			continue;
		}
	}

	\wp_reset_postdata();
}

/**
 * Update source ID of payments.
 */
$query = new \WP_Query(
	array(
		'post_type'     => 'pronamic_payment',
		'post_status'   => 'any',
		'meta_query'    => array(
			array(
				'key'   => '_pronamic_payment_source',
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

		$payment_post_id = \get_the_ID();

		if ( false === $payment_post_id ) {
			continue;
		}

		/**
		 * Get payment.
		 *
		 * @link https://github.com/wp-pay/core/blob/2.2.4/includes/functions.php#L24-L46
		 */
		$payment = \get_pronamic_payment( $payment_post_id );

		if ( null === $payment ) {
			continue;
		}

		/**
		 * Source.
		 */
		$payment_source_id = \get_post_meta( $payment_post_id, '_pronamic_payment_source_id', true );

		$potential_user_id = $payment_source_id;

		/**
		 * Get customer by user ID.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.1/includes/customers/customer-functions.php#L15-34
		 */
		$rcp_customer = \rcp_get_customer_by_user_id( $potential_user_id );

		if ( false === $rcp_customer ) {
			// What to do?

			continue;
		}

		/**
		 * Get customer single membership by customer ID.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.1/includes/customers/customer-functions.php#L280-320
		 */
		$rcp_membership = \rcp_get_customer_single_membership( $rcp_customer->get_id() );

		if ( false === $rcp_membership ) {
			// What to do?

			continue;
		}

		/**
		 * Update meta.
		 *
		 * @link https://developer.wordpress.org/reference/functions/update_post_meta/
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.1/includes/memberships/class-rcp-membership.php#L354-363
		 */
		$result = \update_post_meta( $payment_post_id, '_pronamic_payment_source', 'rcp_membership' );

		if ( false === $result ) {
			// What to do?

			continue;
		}

		$result = \update_post_meta( $payment_post_id, '_pronamic_payment_source_id', $rcp_membership->get_id() );

		if ( false === $result ) {
			// What to do?

			continue;
		}
	}

	\wp_reset_postdata();
}
