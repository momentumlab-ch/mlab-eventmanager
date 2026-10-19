<?php
/**
 * Event list: [mlab_events show="upcoming|past|all" category="slug" limit="10"].
 * Archive pages show upcoming events in date order.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'mlab_events', 'mlab_em_render_list_shortcode' );
add_action( 'pre_get_posts', 'mlab_em_order_archive' );

/**
 * Query args for the event list.
 *
 * @param string $show     upcoming, past or all.
 * @param string $category Category slug, empty for all.
 * @param int    $limit    Events per page.
 * @return array<string, mixed>
 */
function mlab_em_list_query_args( string $show, string $category, int $limit ): array {
	$args = array(
		'post_type'      => MLAB_EM_POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'paged'          => max( 1, absint( get_query_var( 'paged' ) ), absint( get_query_var( 'page' ) ) ),
	) + mlab_em_date_query_args( $show );

	if ( '' !== $category ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Category filter.
			array(
				'taxonomy' => MLAB_EM_CATEGORY,
				'field'    => 'slug',
				'terms'    => array_map( 'sanitize_title', explode( ',', $category ) ),
			),
		);
	}

	return apply_filters( 'mlab_em_list_query_args', $args, $show, $category );
}

/**
 * Renders the shortcode.
 *
 * @param array<string, string>|string $atts Shortcode attributes.
 */
function mlab_em_render_list_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'show'     => 'upcoming',
			'category' => '',
			'limit'    => mlab_em_get_option( 'per_page' ),
		),
		$atts,
		'mlab_events'
	);

	$show  = in_array( $atts['show'], array( 'upcoming', 'past', 'all' ), true ) ? $atts['show'] : 'upcoming';
	$query = new WP_Query( mlab_em_list_query_args( $show, sanitize_text_field( $atts['category'] ), max( 1, absint( $atts['limit'] ) ) ) );

	wp_enqueue_style( 'mlab-em-frontend' );
	$output = mlab_em_get_template( 'event-list', array( 'query' => $query ) );
	wp_reset_postdata();

	return $output;
}

/**
 * Archive and category pages: upcoming events, soonest first.
 *
 * @param WP_Query $query Current query.
 */
function mlab_em_order_archive( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( ! $query->is_post_type_archive( MLAB_EM_POST_TYPE ) && ! $query->is_tax( array( MLAB_EM_CATEGORY, MLAB_EM_TAG ) ) ) {
		return;
	}
	if ( ! apply_filters( 'mlab_em_archive_upcoming_only', true, $query ) ) {
		return;
	}

	foreach ( mlab_em_date_query_args( 'upcoming' ) as $key => $value ) {
		$query->set( $key, $value );
	}
}
