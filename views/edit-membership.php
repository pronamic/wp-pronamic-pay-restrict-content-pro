<?php
/**
 * Restrict Content Pro edit membership after.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2023 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 *
 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/memberships/edit-membership.php#L285-294
 */

?>
<tr>
	<th scope="row" class="row-title">
		<?php esc_html_e( 'Pronamic Pay Subscription:', 'pronamic_ideal' ); ?>
	</th>
	<td>
		<?php

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				echo '<div style="display: flex; gap: 1em; align-items: center;">';

				printf(
					'<a href="%s">%s</a>',
					esc_url( get_edit_post_link() ),
					esc_html( get_the_ID() )
				);

				echo ' ';

				$action_url = wp_nonce_url(
					add_query_arg(
						[
							'subscription_id' => get_the_ID(),
							'action'          => 'pronamic_pay_rcp_update_subscription',
						],
					),
					'pronamic_pay_rcp_update_subscription_' . get_the_ID()
				);

				printf(
					'<a class="button" href="%s">%s</a>',
					esc_url( $action_url ),
					esc_html__( 'Update details to Pronamic Pay subscription', 'pronamic_ideal' )
				);

				echo ' ';

				printf(
					'<span class="rcp-help-tip dashicons dashicons-editor-help" title="%s"></span>',
					esc_attr__( 'Updating ensures that date changes in the Restrict Content membership are updated in the Pronamic Pay subscription.', 'pronamic_ideal' )
				);

				echo '</div>';
			}
		}

		?>
	</td>
</tr>
