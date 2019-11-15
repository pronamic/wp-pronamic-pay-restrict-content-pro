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

use Pronamic\WordPress\Pay\Subscriptions\Subscription as CoreSubscription;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus as CoreSubscriptionStatus;

/**
 * Get Restrict Content Pro payment by a Restrict Content Pro payment ID.
 *
 * @param int $rcp_payment_id Restrict Content Pro payment ID.
 * @return object|null
 */
function get_rcp_payment_by_rcp_payment_id( $rcp_payment_id ) {
	$rcp_payments = new \RCP_Payments();

	$rcp_payment = $rcp_payments->get_payment( $potential_rcp_payment_id );

	if ( null === $rcp_payment ) {
		return null;
	}

	return $rcp_payment;
}

/**
 * Get Restrict Content Pro membership by Restrict Content Pro payment ID.
 *
 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.1/includes/class-rcp-payments.php
 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.1/includes/database/engine/class-query.php#L1546-1564
 * @param int $rcp_payment_id Restrict Content Pro payment ID.
 * @return \RCP_Membership|null
 */
function get_rcp_membership_by_rcp_payment_id( $rcp_payment_id ) {
	$rcp_payment = get_rcp_payment_by_rcp_payment_id( $rcp_payment_id );

	if ( null === $rcp_payment ) {
		return null;
	}

	$rcp_membership = \rcp_get_membership( $rcp_payment->membership_id );

	if ( false === $rcp_membership ) {
		return null;
	}

	return $rcp_membership;
}

/**
 * Get Restrict Content Pro membership by WordPress user ID.
 *
 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.1/includes/customers/customer-functions.php#L15-34
 * @param int $wp_user_id WordPress user ID.
 * @return \RCP_Membership|null
 */
function get_rcp_membership_by_wp_user_id( $wp_user_id ) {
	$rcp_customer = \rcp_get_customer_by_user_id( $wp_user_id );

	if ( false === $rcp_customer ) {
		return null;
	}

	$rcp_membership = \rcp_get_customer_single_membership( $rcp_customer->get_id() );

	if ( false === $rcp_membership ) {
		return null;
	}

	return $rcp_membership;
}

/**
 * Update subscriptions source.
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
		$subscription_source    = \get_post_meta( $subscription_post_id, '_pronamic_subscription_source', true );
		$subscription_source_id = \get_post_meta( $subscription_post_id, '_pronamic_subscription_source_id', true );

		\update_post_meta( $subscription_post_id, '_pronamic_subscription_rcp_update_source', $subscription_source );
		\update_post_meta( $subscription_post_id, '_pronamic_subscription_rcp_update_source_id', $subscription_source_id );

		/**
		 * We have to find a matching Restrict Content Pro membership.
		 */
		$rcp_membership = null;

		/**
		 * In Restrict Content Pro versions before 3.0 we may have saved the Restrict Content Pro payment ID as source ID.
		 */
		if ( null === $rcp_membership ) {
			$potential_rcp_payment_id = $old_subscription_source_id;

			$rcp_membership = get_rcp_membership_by_rcp_payment_id( $potential_rcp_payment_id );
		}

		/**
		 * In Restrict Content Pro versions before 3.0 we may have saved the WordPress user ID as source ID.
		 */
		if ( null === $rcp_membership ) {
			$potential_wp_user_id = $old_subscription_source_id;

			$rcp_membership = get_rcp_membership_by_wp_user_id( $potential_wp_user_id );
		}

		/**
		 * No match.
		 */
		if ( null === $rcp_membership ) {
			$subscription->set_status( CoreSubscriptionStatus::ON_HOLD );

			$subscription->add_note(
				\sprintf(
					/* translators: %s: Potential WordPress user ID. */
					__( 'Since Restrict Content Pro 3 a subscription must be linked to a Restrict Content Pro membership. Unfortunately, this subscription could not be linked to a Restrict Content Pro membership based on the source ID %s. That is why this subscription has been put on hold so that it can be corrected manually.', 'pronamic_ideal' ),
					$subscription_source_id
				)
			);

			$subscription->save();

			continue;
		}

		/**
		 * Check if Restrict Content Pro membership customer matches subscription post author.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.1/includes/memberships/class-rcp-membership.php#L376-391
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.1/includes/customers/class-rcp-customer.php#L198-207
		 */
		$rcp_customer = $rcp_membership->get_customer();

		$pronamic_subscription_post_author_id = \intval( get_post_field( 'post_author', $subscription_post_id ) );

		$rcp_customer_user_id = \intval( $rcp_customer->get_user_id() );

		if ( $pronamic_subscription_post_author_id !== $rcp_customer_user_id ) {
			$subscription->set_status( CoreSubscriptionStatus::ON_HOLD );

			$subscription->add_note(
				\sprintf(
					/* translators: %s: Potential WordPress user ID. */
					__( 'Since Restrict Content Pro 3 a subscription must be linked to a Restrict Content Pro membership. Unfortunately, this subscription could not be linked to a Restrict Content Pro membership based on the source ID %s. That is why this subscription has been put on hold so that it can be corrected manually.', 'pronamic_ideal' ),
					$subscription_source_id
				)
			);

			$subscription->save();

			continue;
		}

		/**
		 * Ok.
		 */
		$subscription->set_source( 'rcp_membership' );
		$subscription->set_source_id( $membership->get_id() );

		$subscription->add_note(
			\sprintf(
				/* translators: 1: Old source, 2: Old source ID, 3: New source, 4: New source ID. */
				__( 'Since Restrict Content Pro 3 a subscription must be linked to a Restrict Content Pro membership. That\'s why source "%1$s" with ID "%2$s" was updated to source "%3$s" with ID "%4$s".', 'pronamic_ideal' ),
				$subscription_source,
				$subscription_source_id,
				'rcp_membership',
				$membership->get_id()
			)
		);

		$subscription->save();
	}

	\wp_reset_postdata();
}

/**
 * Update payments source.
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
		 * Get source.
		 */
		$payment_source    = \get_post_meta( $payment_post_id, '_pronamic_payment_source', true );
		$payment_source_id = \get_post_meta( $payment_post_id, '_pronamic_payment_source_id', true );

		\update_post_meta( $payment_post_id, '_pronamic_payment_rcp_update_source', $payment_source );
		\update_post_meta( $payment_post_id, '_pronamic_payment_rcp_update_source_id', $payment_source_id );

		/**
		 * We have to find a matching Restrict Content Pro payment.
		 */
		$rcp_payment = null;

		/**
		 * In Restrict Content Pro versions before 3.0 we may have saved the Restrict Content Pro payment ID as source ID.
		 */
		if ( null === $rcp_payment ) {
			$potential_rcp_payment_id = $payment_source_id;

			$rcp_payment = get_rcp_payment_by_rcp_payment_id( $potential_rcp_payment_id );
		}

		/**
		 * No match, no problem.
		 */
		if ( null === $rcp_payment ) {
			continue;
		}

		/**
		 * Check if Restrict Content Pro payment user ID matches payment post author.
		 */
		$pronamic_payment_post_author_id = \intval( get_post_field( 'post_author', $payment_post_id ) );

		$rcp_payment_user_id = \intval( $rcp_payment->user_id );

		if ( $pronamic_payment_post_author_id !== $rcp_payment_user_id ) {
			continue;
		}

		/**
		 * Ok.
		 */
		$payment->set_source( 'rcp_payment' );
		$payment->set_source_id( $rcp_payment->id );

		$payment->add_note(
			\sprintf(
				/* translators: 1: Old source, 2: Old source ID, 3: New source, 4: New source ID. */
				__( 'Since Restrict Content Pro 3 a payment must be linked to a Restrict Content Pro payment. That\'s why source "%1$s" with ID "%2$s" was updated to source "%3$s" with ID %4$s".', 'pronamic_ideal' ),
				$payment_source,
				$payment_source_id,
				'rcp_payment',
				$rcp_payment->id
			)
		);

		$payment->save();
	}

	\wp_reset_postdata();
}
