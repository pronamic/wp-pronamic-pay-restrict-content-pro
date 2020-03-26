<?php
/**
 * Upgrade 2.1.6
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2020 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Upgrades
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus;
use Pronamic\WordPress\Pay\Upgrades\Upgrade;

/**
 * Upgrade 2.1.6
 *
 * @author  Remco Tolsma
 * @version 2.1.6
 * @since   2.1.6
 */
class Upgrade216 extends Upgrade {
	/**
	 * Construct 2.1.6 upgrade.
	 */
	public function __construct() {
		parent::__construct( '2.1.6' );

		if ( \defined( '\WP_CLI' ) && \WP_CLI ) {
			$this->cli_init();
		}
	}

	/**
	 * Execute.
	 *
	 * @return void
	 */
	public function execute() {
		$this->upgrade_subscriptions();
		$this->upgrade_payments();
	}

	/**
	 * WP-CLI initialize.
	 *
	 * @link https://github.com/wp-cli/wp-cli/issues/4818
	 * @return void
	 */
	public function cli_init() {
		\WP_CLI::add_command(
			'pronamic-pay restrict-content-pro upgrade-216 execute',
			function( $args, $assoc_args ) {
				\WP_CLI::log( 'Upgrade 2.1.6' );

				$this->execute();
			},
			array(
				'shortdesc' => 'Execute Restrict Content Pro upgrade 2.1.6.',
			)
		);

		\WP_CLI::add_command(
			'pronamic-pay restrict-content-pro upgrade-216 list-subscriptions',
			function( $args, $assoc_args ) {
				\WP_CLI::log( 'Upgrade 2.1.6 - Subscriptions List' );

				$posts = $this->get_subscription_posts();

				\WP_CLI\Utils\format_items( 'table', $posts, array( 'ID', 'post_title', 'post_status' ) );
			},
			array(
				'shortdesc' => 'Execute Restrict Content Pro upgrade 2.1.6.',
			)
		);

		\WP_CLI::add_command(
			'pronamic-pay restrict-content-pro upgrade-216 upgrade-subscriptions',
			function( $args, $assoc_args ) {
				\WP_CLI::log( 'Upgrade 2.1.6 - Subscriptions' );

				$this->upgrade_subscriptions( array(
					'skip-notes' => \WP_CLI\Utils\get_flag_value( $assoc_args, 'skip-notes', true ),
					'status'     => \WP_CLI\Utils\get_flag_value( $assoc_args, 'status', null ),
					'dry-run'    => \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', true ),
				) );
			},
			array(
				'shortdesc' => 'Execute Restrict Content Pro upgrade 2.1.6.',
			)
		);

		\WP_CLI::add_command(
			'pronamic-pay restrict-content-pro upgrade-216 list-payments',
			function( $args, $assoc_args ) {
				\WP_CLI::log( 'Upgrade 2.1.6 - Payments List' );

				$posts = $this->get_payment_posts();

				\WP_CLI\Utils\format_items( 'table', $posts, array( 'ID', 'post_title', 'post_status' ) );
			},
			array(
				'shortdesc' => 'Execute Restrict Content Pro upgrade 2.1.6.',
			)
		);
	}

	/**
	 * Get subscription posts to upgrade.
	 *
	 * @return array
	 */
	private function get_subscription_posts() {
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

		return $query->posts;
	}

	/**
	 * Get payment posts to upgrade.
	 *
	 * @return array
	 */
	private function get_payment_posts() {
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

		return $query->posts;
	}

	/**
	 * Log.
	 *
	 * @link https://make.wordpress.org/cli/handbook/internal-api/wp-cli-log/
	 * @param string $message Message.
	 * @return void
	 */
	private function log( $message ) {
		if ( method_exists( '\WP_CLI', 'log' ) ) {
			\WP_CLI::log( $message );
		}
	}

	/**
	 * Upgrade subscriptions.
	 *
	 * @param array $args       Arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	private function upgrade_subscriptions( $args = array() ) {
		$args = \wp_parse_args( $args, array(
			'skip-notes' => true,
			'status'     => null,
			'dry-run'    => false,
		) );

		$add_note = ! \filter_var( $args['skip-notes'], FILTER_VALIDATE_BOOLEAN );
		$dry_run  = \filter_var( $args['dry-run'], FILTER_VALIDATE_BOOLEAN );

		$subscription_posts = $this->get_subscription_posts();

		foreach ( $subscription_posts as $subscription_post ) {
			$subscription_post_id = $subscription_post->ID;

			$this->log(
				\sprintf(
					'Subscription post %s',
					$subscription_post_id
				)
			);

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
				$potential_rcp_payment_id = $subscription_source_id;

				$rcp_membership = $this->get_rcp_membership_by_rcp_payment_id( $potential_rcp_payment_id );

				if ( null !== $rcp_membership ) {
					$this->log( '- Found Restrict Content Pro membership through potential Restrict Content Pro payment ID.' );
				}
			}

			/**
			 * In Restrict Content Pro versions before 3.0 we may have saved the WordPress user ID as source ID.
			 */
			if ( null === $rcp_membership ) {
				$potential_wp_user_id = $subscription_source_id;

				$rcp_membership = $this->get_rcp_membership_by_wp_user_id( $potential_wp_user_id );

				if ( null !== $rcp_membership ) {
					$this->log( '- Found Restrict Content Pro membership through potential WordPress user ID.' );
				}
			}

			/**
			 * No match.
			 */
			if ( null === $rcp_membership ) {
				$this->log( '- No Restrict Content Pro membership found.' );

				if ( false === $dry_run ) {
					$subscription->set_status( SubscriptionStatus::ON_HOLD );

					if ( true === $add_note ) {
						$subscription->add_note(
							\sprintf(
								/* translators: %s: Potential WordPress user ID. */
								__( 'Since Restrict Content Pro 3 a subscription must be linked to a Restrict Content Pro membership. Unfortunately, this subscription could not be linked to a Restrict Content Pro membership based on the source ID %s. That is why this subscription has been put on hold so that it can be corrected manually.', 'pronamic_ideal' ),
								$subscription_source_id
							)
						);
					}

					$subscription->save();
				}

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

			$rcp_customer_user_id = null;

			if ( $rcp_customer instanceof \RCP_Customer ) {
				$rcp_customer_user_id = \intval( $rcp_customer->get_user_id() );
			}

			if ( $pronamic_subscription_post_author_id !== $rcp_customer_user_id ) {
				$this->log( '- Pronamic subscription post author does not match Restrict Content Pro customer user ID.' );

				if ( false === $dry_run ) {
					$subscription->set_status( SubscriptionStatus::ON_HOLD );

					if ( true === $add_note ) {
						$subscription->add_note(
							\sprintf(
								/* translators: %s: Potential WordPress user ID. */
								__( 'Since Restrict Content Pro 3 a subscription must be linked to a Restrict Content Pro membership. Unfortunately, this subscription could not be linked to a Restrict Content Pro membership based on the source ID %s. That is why this subscription has been put on hold so that it can be corrected manually.', 'pronamic_ideal' ),
								$subscription_source_id
							)
						);
					}

					$subscription->save();
				}

				continue;
			}

			/**
			 * Ok.
			 */
			$status = $args['status'];

			if ( false === $dry_run ) {
				$this->log( '- Pronamic subscription post author does not match Restrict Content Pro customer user ID.' );

				if ( null !== $status ) {
					$subscription->set_status( $status );
				}

				$subscription->set_source( 'rcp_membership' );
				$subscription->set_source_id( $rcp_membership->get_id() );

				if ( true === $add_note ) {
					$subscription->add_note(
						\sprintf(
							/* translators: 1: Old source, 2: Old source ID, 3: New source, 4: New source ID. */
							__( 'Since Restrict Content Pro 3 a subscription must be linked to a Restrict Content Pro membership. That\'s why source "%1$s" with ID "%2$s" was updated to source "%3$s" with ID "%4$s".', 'pronamic_ideal' ),
							$subscription_source,
							$subscription_source_id,
							'rcp_membership',
							$rcp_membership->get_id()
						)
					);
				}

				$subscription->save();
			}

			$this->log( '' );
		}
	}

	/**
	 * Upgrade payments.
	 */
	private function upgrade_payments() {
		$payment_posts = $this->get_payment_posts();

		foreach ( $payment_posts as $payment_post ) {
			$payment_post_id = $payment_post->ID;

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

				$rcp_payment = $this->get_rcp_payment_by_rcp_payment_id( $potential_rcp_payment_id );
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
	}

	/**
	 * Get Restrict Content Pro payment by a Restrict Content Pro payment ID.
	 *
	 * @param int $rcp_payment_id Restrict Content Pro payment ID.
	 * @return object|null
	 */
	private function get_rcp_payment_by_rcp_payment_id( $rcp_payment_id ) {
		$rcp_payments = new \RCP_Payments();

		$rcp_payment = $rcp_payments->get_payment( $rcp_payment_id );

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
	private function get_rcp_membership_by_rcp_payment_id( $rcp_payment_id ) {
		$rcp_payment = $this->get_rcp_payment_by_rcp_payment_id( $rcp_payment_id );

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
	private function get_rcp_membership_by_wp_user_id( $wp_user_id ) {
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
}
