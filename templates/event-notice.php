<?php
/**
 * Notice box. Override in <theme>/mlab-eventmanager/event-notice.php.
 *
 * @package mlab-eventmanager
 *
 * @var array{type: string, message: string, link?: string} $args
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="mlab-em-notice mlab-em-notice--<?php echo esc_attr( $args['type'] ); ?>" role="status">
	<p><?php echo esc_html( $args['message'] ); ?></p>
	<?php if ( ! empty( $args['link'] ) ) : ?>
		<p><a href="<?php echo esc_url( $args['link'] ); ?>"><?php esc_html_e( 'View event', 'mlab-eventmanager' ); ?></a></p>
	<?php endif; ?>
</div>
