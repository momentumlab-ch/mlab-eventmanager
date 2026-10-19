<?php
/**
 * Quick entry page: tabs, notice and the current view. Override in <theme>/mlab-eventmanager/event-page.php.
 *
 * @package mlab-eventmanager
 *
 * @var array{view: string, notice: array<string, string>|null, body: string} $args
 */

defined( 'ABSPATH' ) || exit;

$mlab_em_tabs = array(
	'new'  => __( 'Add event', 'mlab-eventmanager' ),
	'mine' => __( 'My events', 'mlab-eventmanager' ),
);
?>
<div class="mlab-em-form-wrap">
	<nav class="mlab-em-tabs" aria-label="<?php esc_attr_e( 'Events', 'mlab-eventmanager' ); ?>">
		<?php foreach ( $mlab_em_tabs as $mlab_em_view => $mlab_em_label ) : ?>
			<?php $mlab_em_current = ( 'new' === $args['view'] ? 'new' : 'mine' ) === $mlab_em_view; ?>
			<a class="mlab-em-tabs__tab<?php echo $mlab_em_current ? ' is-active' : ''; ?>" href="<?php echo esc_url( mlab_em_my_events_url( $mlab_em_view ) ); ?>"<?php echo $mlab_em_current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $mlab_em_label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<?php
	if ( $args['notice'] ) {
		echo mlab_em_get_template( 'event-notice', $args['notice'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its own output.
	}
	echo $args['body']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from templates that escape their own output.
	?>
</div>
