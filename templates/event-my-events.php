<?php
/**
 * The user's own events. Override in <theme>/mlab-eventmanager/event-my-events.php.
 *
 * @package mlab-eventmanager
 *
 * @var array{events: WP_Post[]} $args
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="mlab-em-mine">
	<?php if ( ! $args['events'] ) : ?>
		<p class="mlab-em-empty"><?php esc_html_e( 'You have not added any events yet.', 'mlab-eventmanager' ); ?></p>
		<a class="mlab-em-button" href="<?php echo esc_url( mlab_em_my_events_url( 'new' ) ); ?>"><?php esc_html_e( 'Add event', 'mlab-eventmanager' ); ?></a>
	<?php endif; ?>

	<?php foreach ( $args['events'] as $mlab_em_event ) : ?>
		<article class="mlab-em-mine__item">
			<div class="mlab-em-mine__info">
				<h3 class="mlab-em-mine__title"><?php echo esc_html( get_the_title( $mlab_em_event ) ); ?></h3>
				<p class="mlab-em-mine__meta">
					<?php echo esc_html( mlab_em_format_date_range( $mlab_em_event->ID ) ); ?>
				</p>
				<span class="mlab-em-badge mlab-em-badge--<?php echo esc_attr( $mlab_em_event->post_status ); ?>"><?php echo esc_html( mlab_em_status_label( $mlab_em_event->post_status ) ); ?></span>
			</div>
			<div class="mlab-em-mine__actions">
				<?php if ( 'publish' === $mlab_em_event->post_status ) : ?>
					<a href="<?php echo esc_url( get_permalink( $mlab_em_event ) ); ?>"><?php esc_html_e( 'View', 'mlab-eventmanager' ); ?></a>
				<?php endif; ?>
				<?php if ( current_user_can( 'edit_post', $mlab_em_event->ID ) ) : ?>
					<a href="<?php echo esc_url( mlab_em_my_events_url( 'edit', $mlab_em_event->ID ) ); ?>"><?php esc_html_e( 'Edit', 'mlab-eventmanager' ); ?></a>
					<a href="<?php echo esc_url( mlab_em_duplicate_url( $mlab_em_event->ID, true ) ); ?>"><?php esc_html_e( 'Duplicate', 'mlab-eventmanager' ); ?></a>
				<?php endif; ?>
				<?php if ( current_user_can( 'delete_post', $mlab_em_event->ID ) ) : ?>
					<a class="mlab-em-danger" href="<?php echo esc_url( mlab_em_my_events_url( 'delete', $mlab_em_event->ID ) ); ?>"><?php esc_html_e( 'Delete', 'mlab-eventmanager' ); ?></a>
				<?php endif; ?>
			</div>
		</article>
	<?php endforeach; ?>
</div>
