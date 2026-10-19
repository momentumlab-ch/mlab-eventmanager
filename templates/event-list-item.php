<?php
/**
 * One event in the list. Override in <theme>/mlab-eventmanager/event-list-item.php.
 *
 * @package mlab-eventmanager
 *
 * @var array{post_id: int} $args
 */

defined( 'ABSPATH' ) || exit;

$mlab_em_id    = $args['post_id'];
$mlab_em_start = mlab_em_get_timestamp( $mlab_em_id, 'start' );
$mlab_em_place = mlab_em_get_place( $mlab_em_id );
$mlab_em_where = '' !== $mlab_em_place['name'] ? $mlab_em_place['name'] : $mlab_em_place['address'];
$mlab_em_terms = get_the_terms( $mlab_em_id, MLAB_EM_CATEGORY );
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
			<span class="mlab-em-item__date"><?php echo esc_html( mlab_em_format_date_range( $mlab_em_id ) ); ?></span>
			<?php if ( '' !== $mlab_em_where ) : ?>
				<span class="mlab-em-item__location"><?php echo esc_html( $mlab_em_where ); ?></span>
			<?php endif; ?>
		</p>
		<?php if ( is_array( $mlab_em_terms ) && $mlab_em_terms ) : ?>
			<p class="mlab-em-item__terms">
				<?php foreach ( $mlab_em_terms as $mlab_em_term ) : ?>
					<span class="mlab-em-badge"><?php echo esc_html( $mlab_em_term->name ); ?></span>
				<?php endforeach; ?>
				<?php if ( '' !== mlab_em_get_field( $mlab_em_id, 'online_url' ) ) : ?>
					<span class="mlab-em-badge"><?php esc_html_e( 'Online', 'mlab-eventmanager' ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>
		<?php do_action( 'mlab_em_list_item_after', $mlab_em_id ); ?>
	</div>

	<?php if ( has_post_thumbnail( $mlab_em_id ) ) : ?>
		<a class="mlab-em-item__image" href="<?php echo esc_url( get_permalink( $mlab_em_id ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo get_the_post_thumbnail( $mlab_em_id, 'medium', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core builds the image tag with escaped attributes. ?>
		</a>
	<?php endif; ?>
</article>
