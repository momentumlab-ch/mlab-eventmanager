<?php
/**
 * Confirmation before an event is deleted. Override in <theme>/mlab-eventmanager/event-delete.php.
 *
 * @package mlab-eventmanager
 *
 * @var array{event: WP_Post} $args
 */

defined( 'ABSPATH' ) || exit;
?>
<form class="mlab-em-form mlab-em-delete" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="mlab_em_delete">
	<input type="hidden" name="mlab_em_event" value="<?php echo esc_attr( (string) $args['event']->ID ); ?>">
	<?php wp_nonce_field( 'mlab_em_delete', 'mlab_em_nonce' ); ?>

	<p>
		<?php
		/* translators: %s: event title. */
		echo esc_html( sprintf( __( 'Delete "%s"? It is moved to the trash.', 'mlab-eventmanager' ), get_the_title( $args['event'] ) ) );
		?>
	</p>

	<div class="mlab-em-form__actions">
		<button type="submit" class="mlab-em-button mlab-em-button--danger"><?php esc_html_e( 'Delete event', 'mlab-eventmanager' ); ?></button>
		<a class="mlab-em-link" href="<?php echo esc_url( mlab_em_my_events_url( 'mine' ) ); ?>"><?php esc_html_e( 'Cancel', 'mlab-eventmanager' ); ?></a>
	</div>
</form>
