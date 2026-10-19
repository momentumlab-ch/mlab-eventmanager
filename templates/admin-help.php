<?php
/**
 * Usage hints on the settings page.
 *
 * @package mlab-eventmanager
 *
 * @var array{submit_url: string} $args
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="card">
	<h2><?php esc_html_e( 'How to use', 'mlab-eventmanager' ); ?></h2>
	<p><?php esc_html_e( 'Show events on any page with the shortcode:', 'mlab-eventmanager' ); ?> <code>[mlab_events]</code></p>
	<p><?php esc_html_e( 'Options: show="upcoming", "past" or "all", category="slug", limit="10", filters="yes" (search, category and date for visitors).', 'mlab-eventmanager' ); ?></p>
	<?php if ( $args['submit_url'] ) : ?>
		<p>
			<?php esc_html_e( 'Add events on your phone: open this page and save it to your home screen.', 'mlab-eventmanager' ); ?>
			<br><a href="<?php echo esc_url( $args['submit_url'] ); ?>"><?php echo esc_html( $args['submit_url'] ); ?></a>
		</p>
	<?php else : ?>
		<p><?php esc_html_e( 'Choose a page for quick entry below, or add the shortcode [mlab_event_form] to any page.', 'mlab-eventmanager' ); ?></p>
	<?php endif; ?>
</div>
