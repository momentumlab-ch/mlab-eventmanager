<?php
/**
 * One event in the list. Override in <theme>/mlab-eventmanager/event-list-item.php.
 *
 * @package mlab-eventmanager
 *
 * @var array{post_id: int} $args
 */

defined( 'ABSPATH' ) || exit;

$mlab_em_id       = $args['post_id'];
$mlab_em_start    = mlab_em_get_timestamp( $mlab_em_id, 'start' );
$mlab_em_location = mlab_em_get_field( $mlab_em_id, 'location' );
?>
<article class="mlab-em-item">
	<?php if ( $mlab_em_start ) : ?>
		<time class="mlab-em-item__badge" datetime="<?php echo esc_attr( wp_date( 'c', $mlab_em_start ) ); ?>">
			<span class="mlab-em-item__day"><?php echo esc_html( wp_date( 'j', $mlab_em_start ) ); ?></span>
			<span class="mlab-em-item__month"><?php echo esc_html( wp_date( 'M', $mlab_em_start ) ); ?></span>
		</time>
	<?php endif; ?>

	<div class="mlab-em-item__body">
		<h3 class="mlab-em-item__title"><a href="<?php echo esc_url( get_permalink( $mlab_em_id ) ); ?>"><?php echo esc_html( get_the_title( $mlab_em_id ) ); ?></a></h3>
		<p class="mlab-em-item__meta">
			<?php echo esc_html( mlab_em_format_date_range( $mlab_em_id ) ); ?>
			<?php if ( $mlab_em_location ) : ?>
				<span class="mlab-em-item__location"><?php echo esc_html( $mlab_em_location ); ?></span>
			<?php endif; ?>
		</p>
		<?php do_action( 'mlab_em_list_item_after', $mlab_em_id ); ?>
	</div>
</article>
