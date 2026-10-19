<?php
/**
 * Migration page. Override in <theme>/mlab-eventmanager/admin-migration.php.
 *
 * @package mlab-eventmanager
 *
 * @var array{found: int, venues: int, organizers: int, migrated: int, pending: int, skipped: int, result: array<string, mixed>|null} $args
 */

defined( 'ABSPATH' ) || exit;

$mlab_em_result = $args['result'];
?>
<div class="wrap mlab-em-wrap">
	<h1><?php esc_html_e( 'Migration from WP Event Manager', 'mlab-eventmanager' ); ?></h1>

	<?php if ( $mlab_em_result ) : ?>
		<div class="notice notice-<?php echo $mlab_em_result['dry_run'] ? 'info' : 'success'; ?>">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: number of events, 2: number of skipped events, 3: number of events left. */
						$mlab_em_result['dry_run'] ? __( 'Check done, nothing was changed. %1$d events can be copied, %2$d cannot. %3$d more are waiting.', 'mlab-eventmanager' ) : __( 'Done. %1$d events copied, %2$d skipped. %3$d more are waiting.', 'mlab-eventmanager' ),
						(int) $mlab_em_result['copied'],
						(int) $mlab_em_result['skipped'],
						(int) $mlab_em_result['remaining']
					)
				);
				?>
			</p>
		</div>

		<?php if ( $mlab_em_result['rows'] ) : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Event', 'mlab-eventmanager' ); ?></th>
						<th><?php esc_html_e( 'Start', 'mlab-eventmanager' ); ?></th>
						<th><?php esc_html_e( 'Notes', 'mlab-eventmanager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $mlab_em_result['rows'] as $mlab_em_row ) : ?>
						<tr>
							<td><?php echo esc_html( $mlab_em_row['title'] ); ?></td>
							<td><?php echo esc_html( $mlab_em_row['start'] ); ?></td>
							<td><?php echo esc_html( implode( ' ', $mlab_em_row['warnings'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( ! $args['found'] ) : ?>
		<p><?php esc_html_e( 'No events from WP Event Manager were found in this website.', 'mlab-eventmanager' ); ?></p>
	<?php else : ?>
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: events, 2: venues, 3: organizers found in WP Event Manager. */
					__( 'Found in WP Event Manager: %1$d events, %2$d venues, %3$d organizers.', 'mlab-eventmanager' ),
					$args['found'],
					$args['venues'],
					$args['organizers']
				)
			);
			?>
			<br>
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: events already copied, 2: events still to copy. */
					__( 'Already copied: %1$d. Still to copy: %2$d.', 'mlab-eventmanager' ),
					$args['migrated'],
					$args['pending']
				)
			);
			?>
		</p>
		<p class="description"><?php esc_html_e( 'Your WP Event Manager data stays untouched. Venues and organizers are copied together with the events that use them. Every run copies up to 50 events; run it again for the rest. Events copied before are skipped.', 'mlab-eventmanager' ); ?></p>

		<?php if ( $args['skipped'] ) : ?>
			<p class="description">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of skipped events. */
						_n( '%d event was skipped because it has no readable start date. Fix the date in WP Event Manager, then use "Try skipped events again".', '%d events were skipped because they have no readable start date. Fix the dates in WP Event Manager, then use "Try skipped events again".', $args['skipped'], 'mlab-eventmanager' ),
						$args['skipped']
					)
				);
				?>
			</p>
		<?php endif; ?>

		<?php if ( $args['pending'] || $args['skipped'] ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mlab_em_migrate">
				<?php wp_nonce_field( 'mlab_em_migrate', 'mlab_em_nonce' ); ?>
				<p>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Copy events now', 'mlab-eventmanager' ); ?></button>
					<button type="submit" name="mlab_em_dry_run" value="1" class="button"><?php esc_html_e( 'Check first (changes nothing)', 'mlab-eventmanager' ); ?></button>
					<?php if ( $args['skipped'] ) : ?>
						<button type="submit" name="mlab_em_retry_skipped" value="1" class="button"><?php esc_html_e( 'Try skipped events again', 'mlab-eventmanager' ); ?></button>
					<?php endif; ?>
				</p>
			</form>
		<?php endif; ?>
	<?php endif; ?>
</div>
