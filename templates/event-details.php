<?php
/**
 * Details on the single event page. Override in <theme>/mlab-eventmanager/event-details.php.
 *
 * @package mlab-eventmanager
 *
 * @var array{post_id: int} $args
 */

defined( 'ABSPATH' ) || exit;

$mlab_em_id        = $args['post_id'];
$mlab_em_map_link  = mlab_em_show_map( $mlab_em_id ) ? mlab_em_map_link( $mlab_em_id ) : '';
$mlab_em_embed_url = mlab_em_show_map( $mlab_em_id ) ? mlab_em_map_embed_url( $mlab_em_id ) : '';
$mlab_em_google    = mlab_em_google_calendar_url( $mlab_em_id );
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

	<div class="mlab-em-details__actions">
		<?php foreach ( mlab_em_details_buttons( $mlab_em_id ) as $mlab_em_button ) : ?>
			<a class="mlab-em-button" href="<?php echo esc_url( $mlab_em_button['url'] ); ?>" rel="noopener" target="_blank"><?php echo esc_html( $mlab_em_button['label'] ); ?></a>
		<?php endforeach; ?>
		<?php if ( $mlab_em_map_link ) : ?>
			<a class="mlab-em-button mlab-em-button--light" href="<?php echo esc_url( $mlab_em_map_link ); ?>" rel="noopener" target="_blank"><?php esc_html_e( 'Open map', 'mlab-eventmanager' ); ?></a>
		<?php endif; ?>
	</div>

	<?php if ( $mlab_em_google ) : ?>
		<p class="mlab-em-details__calendar">
			<?php esc_html_e( 'Add to calendar:', 'mlab-eventmanager' ); ?>
			<a href="<?php echo esc_url( mlab_em_ics_url( $mlab_em_id ) ); ?>"><?php esc_html_e( 'Apple, Outlook (.ics)', 'mlab-eventmanager' ); ?></a>
			<a href="<?php echo esc_url( $mlab_em_google ); ?>" rel="noopener" target="_blank"><?php esc_html_e( 'Google Calendar', 'mlab-eventmanager' ); ?></a>
		</p>
	<?php endif; ?>

	<?php if ( $mlab_em_embed_url ) : ?>
		<iframe class="mlab-em-details__map" src="<?php echo esc_url( $mlab_em_embed_url ); ?>" title="<?php esc_attr_e( 'Map', 'mlab-eventmanager' ); ?>" loading="lazy" referrerpolicy="no-referrer"></iframe>
	<?php endif; ?>

	<?php do_action( 'mlab_em_details_after', $mlab_em_id ); ?>
</div>
