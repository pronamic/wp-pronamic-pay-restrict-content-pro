<?php
/**
 * Upgrade 4.5.0
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Upgrades
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContent;

use Pronamic\WordPress\Pay\Upgrades\Upgrade;
use WP_Query;

/**
 * Upgrade 4.5.0 class
 */
class Upgrade450 extends Upgrade {
	/**
	 * Construct 2.1.6 upgrade.
	 */
	public function __construct() {
		parent::__construct( '4.5.0' );

		\add_action( 'pronamic_pay_restrictcontentpro_upgrade_4_5_0', [ $this, 'upgrade' ], 10, 1 );
	}

	/**
	 * Execute.
	 *
	 * @return void
	 */
	public function execute() {
		\as_enqueue_async_action( 'pronamic_pay_restrictcontentpro_upgrade_4_5_0', [], 'pronamic-pay' );
	}

	/**
	 * Upgrade.
	 *
	 * @return void
	 */
	public function upgrade() {
		$this->upgrade_items();
	}

	/**
	 * Upgrade items.
	 *
	 * @return void
	 */
	public function upgrade_items() {
		$query = new WP_Query(
			[
				'post_type'     => 'pronamic_pay_subscr',
				'post_status'   => 'any',
				'meta_query'    => [
					[
						'key'   => '_pronamic_subscription_source',
						'value' => 'rcp_membership',
					],
					[
						'key'     => '_pronamic_subscription_source_id',
						'compare' => 'EXISTS',
					],
				],
				'nopaging'      => true,
				'no_found_rows' => true,
				'order'         => 'DESC',
				'orderby'       => 'ID',
			]
		);

		foreach ( $query->posts as $subscription_post ) {
			$this->upgrade_item( (int) \get_post_field( 'ID', $subscription_post ) );
		}
	}

	/**
	 * Upgrade item.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return void
	 */
	public function upgrade_item( $post_id ) {
		$subscription = \get_pronamic_subscription( $post_id );

		if ( null === $subscription ) {
			return;
		}

		$source = $subscription->get_source();

		if ( 'rcp_membership' !== $source ) {
			return;
		}

		$source_id = $subscription->get_source_id();

		if ( null === $source_id ) {
			return;
		}

		$rcp_membership = \rcp_get_membership( (int) $source_id );

		if ( false === $rcp_membership ) {
			return;
		}

		$gateway_subscription_id = $rcp_membership->get_gateway_subscription_id();

		if ( '' !== $gateway_subscription_id ) {
			return;
		}

		$rcp_membership->set_gateway_subscription_id( (string) $post_id );
	}
}
