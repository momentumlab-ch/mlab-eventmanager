<?php
/**
 * Event list. Override in <theme>/mlab-eventmanager/event-list.php.
 *
 * @package mlab-eventmanager
 *
 * @var array{query: WP_Query, filters: array{q: string, cat: string, from: string}|null, locked: bool} $args
 */

defined( 'ABSPATH' ) || exit;

$mlab_em_query = $args['query'];
?>
<div class="mlab-em-list">
	<?php
	if ( $args['filters'] ) {
		$mlab_em_filter_args = array(
			'values'        => $args['filters'],
			'show_category' => ! $args['locked'],
		);
		echo mlab_em_get_template( 'event-filters', $mlab_em_filter_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its own output.
	}
	?>

	<?php if ( ! $mlab_em_query->have_posts() ) : ?>
		<p class="mlab-em-empty"><?php esc_html_e( 'There are no events at the moment.', 'mlab-eventmanager' ); ?></p>
	<?php endif; ?>

	<?php
	while ( $mlab_em_query->have_posts() ) {
		$mlab_em_query->the_post();
		echo mlab_em_get_template( 'event-list-item', array( 'post_id' => get_the_ID() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its own output.
	}
	?>

	<?php if ( $mlab_em_query->max_num_pages > 1 ) : ?>
		<nav class="mlab-em-pagination" aria-label="<?php esc_attr_e( 'More events', 'mlab-eventmanager' ); ?>">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'total'   => $mlab_em_query->max_num_pages,
						'current' => max( 1, (int) $mlab_em_query->get( 'paged' ) ),
					)
				)
			);
			?>
		</nav>
	<?php endif; ?>
</div>
