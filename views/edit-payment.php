<?php
/**
 * Restrict Content Pro edit payment after.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2023 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 *
 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/blob/3.0.10/includes/admin/payments/edit-payment.php#L127
 */

$pronamic_payment_id = (string) \rcp_get_payment_meta( $payment->id, '_pronamic_payment_id', true );

?>
<style type="text/css">
	.pronamic-pay-payments-table th {
		line-height: 1.4em;

		padding: 8px 10px;
	}
</style>

<tr>
	<th scope="row" class="row-title">
		<?php esc_html_e( 'Pronamic Pay Payments', 'pronamic_ideal' ); ?>
	</th>
	<td>
		<table class="pronamic-pay-payments-table wp-list-table widefat striped table-view-list">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'ID', 'pronamic_ideal' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Last', 'pronamic_ideal' ); ?></th>
				</tr>
			</thead>

			<tbody>
		
				<?php if ( $query->have_posts() ) : ?>

					<?php while ( $query->have_posts() ) : ?>

						<tr>
							<?php $query->the_post(); ?>

							<td>
								<?php

								printf(
									'<a href="%s">%s</a>',
									esc_url( get_edit_post_link() ),
									esc_html( get_the_ID() )
								);

								?>
							</td>
							<td>
								<?php

								if ( $pronamic_payment_id === (string) \get_the_ID() ) {
									echo '✓';
								}

								?>
							</td>
						</tr>

					<?php endwhile; ?>

				<?php else : ?>

					<tr>
						<td colspan="2">
							<?php esc_html_e( 'No payments found.', 'pronamic_ideal' ); ?>
						</td>
					</tr>
				
				<?php endif; ?>

			</tbody>
		</table>
	</td>
</tr>
