<?php
/**
 * Details on the single event page. Override in <theme>/mlab-eventmanager/event-details.php.
 *
 * @package mlab-eventmanager
 *
 * @var array{post_id: int} $args
 */

defined( 'ABSPATH' ) || exit;

$mlab_em_id = $args['post_id'];
?>
<div class="mlab-em-details">
	<dl class="mlab-em-details__list">
		<?php foreach ( mlab_em_details_rows( $mlab_em_id ) as $mlab_em_label => $mlab_em_value ) : ?>
			<div class="mlab-em-details__row">
				<dt><?php echo esc_html( $mlab_em_label ); ?></dt>
				<dd><?php echo esc_html( $mlab_em_value ); ?></dd>
			</div>
		<?php endforeach; ?>
	</dl>

	<?php foreach ( mlab_em_details_buttons( $mlab_em_id ) as $mlab_em_button ) : ?>
		<a class="mlab-em-button" href="<?php echo esc_url( $mlab_em_button['url'] ); ?>" rel="noopener" target="_blank"><?php echo esc_html( $mlab_em_button['label'] ); ?></a>
	<?php endforeach; ?>

	<?php do_action( 'mlab_em_details_after', $mlab_em_id ); ?>
</div>
