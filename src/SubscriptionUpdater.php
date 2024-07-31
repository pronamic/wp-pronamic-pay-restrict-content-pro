<?php
/**
 * Subscription updater
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContent
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContent;

use DateTimeImmutable;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Pay\Extensions\RestrictContent\LengthUnit;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionInterval;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionPhase;
use RCP_Membership;

/**
 * Subscription updater class
 */
class SubscriptionUpdater {
	/**
	 * Restrict Content membership.
	 *
	 * @var RCP_Membership
	 */
	private $rcp_membership;

	/**
	 * Pronamic subscription.
	 *
	 * @var Subscription
	 */
	private $pronamic_subscription;

	/**
	 * Construct subscription updater.
	 *
	 * @param RCP_Membership $rcp_membership        Restrict Content membership.
	 * @param Subscription   $pronamic_subscription Pronamic subscription.
	 */
	public function __construct( RCP_Membership $rcp_membership, Subscription $pronamic_subscription ) {
		$this->rcp_membership        = $rcp_membership;
		$this->pronamic_subscription = $pronamic_subscription;
	}

	/**
	 * Update Pronamic subscription.
	 *
	 * @return void
	 * @throws \Exception Throws an exception if no Restrict Content membership level can be found.
	 */
	public function update_pronamic_subscription() {
		$rcp_membership        = $this->rcp_membership;
		$pronamic_subscription = $this->pronamic_subscription;

		$rcp_membership_level = \rcp_get_membership_level( (int) $rcp_membership->get_object_id() );

		if ( false === $rcp_membership_level ) {
			throw new \Exception( 'Cannot find the Restrict Content membership level for the Restrict Content membership to be updated.' );
		}

		/**
		 * Maximum number of times to renew this membership. Default is `0` for unlimited.
		 *
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/-/blob/3.3.3/includes/memberships/class-rcp-membership.php#L138-143
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/-/blob/3.3.3/includes/memberships/class-rcp-membership.php#L1169-1178
		 */
		$maximum_renewals = (int) $rcp_membership->get_maximum_renewals();

		$interval_spec = 'P' . $rcp_membership_level->get_duration() . LengthUnit::to_core( $rcp_membership_level->get_duration_unit() );

		if ( $rcp_membership_level->has_trial() ) {
			$interval_spec = 'P' . $rcp_membership_level->get_trial_duration() . LengthUnit::to_core( $rcp_membership_level->get_trial_duration_unit() );
		}

		$initial_phase_start_date = new DateTimeImmutable( $rcp_membership->get_created_date( false ) );

		$initial_phase = new SubscriptionPhase(
			$pronamic_subscription,
			$initial_phase_start_date,
			new SubscriptionInterval( $interval_spec ),
			new Money( $rcp_membership->get_initial_amount(), $rcp_membership->get_currency() )
		);

		$initial_phase->set_total_periods( 1 );

		$initial_phase_end_date = $initial_phase->get_end_date();

		if ( null === $initial_phase_end_date ) {
			throw new \Exception( 'The initial subscription phase has no end date, this should not happen.' );
		}

		$regular_phase = new SubscriptionPhase(
			$pronamic_subscription,
			$initial_phase_end_date,
			new SubscriptionInterval( 'P' . $rcp_membership_level->get_duration() . LengthUnit::to_core( $rcp_membership_level->get_duration_unit() ) ),
			new Money( $rcp_membership->get_recurring_amount(), $rcp_membership->get_currency() )
		);

		if ( 0 !== $maximum_renewals ) {
			$regular_phase->set_total_periods( $maximum_renewals );
		}

		$pronamic_subscription->set_phases( [] );

		$pronamic_subscription->add_phase( $initial_phase );
		$pronamic_subscription->add_phase( $regular_phase );

		$next_payment_date = null;

		$expiration_timestamp = $rcp_membership->get_expiration_time();

		if ( false !== $expiration_timestamp ) {
			$next_payment_date = new DateTimeImmutable( '@' . $expiration_timestamp );

			$next_payment_date = $next_payment_date->setTime(
				(int) $initial_phase_start_date->format( 'H' ),
				(int) $initial_phase_start_date->format( 'i' ),
				(int) $initial_phase_start_date->format( 's' )
			);
		}

		$pronamic_subscription->set_next_payment_date( $next_payment_date );
	}
}
