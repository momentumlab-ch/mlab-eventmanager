<?php
/**
 * Event list: [mlab_events show="upcoming|past|all" category="slug" limit="10" filters="yes"].
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
 * @param string $from     Earliest day (Y-m-d), empty for none.
 * @return array<string, mixed>
 */
function mlab_em_list_query_args( string $show, string $category, int $limit, string $from = '' ): array {
	$args = array(
		'post_type'      => MLAB_EM_POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'paged'          => max( 1, absint( get_query_var( 'paged' ) ), absint( get_query_var( 'page' ) ) ),
	) + mlab_em_date_query_args( $show, $from );

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
 * Visitor filter values from the URL: search text, category slug and earliest date.
 *
 * @return array{q: string, cat: string, from: string}
 */
function mlab_em_filter_values(): array {
	// Read-only list filter in the URL, nothing is changed by it.
	$from = isset( $_GET['mlab_em_from'] ) ? sanitize_text_field( wp_unslash( $_GET['mlab_em_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return array(
		'q'    => isset( $_GET['mlab_em_q'] ) ? sanitize_text_field( wp_unslash( $_GET['mlab_em_q'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'cat'  => isset( $_GET['mlab_em_cat'] ) ? sanitize_title( wp_unslash( $_GET['mlab_em_cat'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'from' => DateTimeImmutable::createFromFormat( '!Y-m-d', $from ) ? $from : '',
	);
}

/**
 * Adds the visitor search and category filter to the list query.
 *
 * @param array<string, mixed>                        $args   Query args.
 * @param array{q: string, cat: string, from: string} $values Filter values.
 * @return array<string, mixed>
 */
function mlab_em_apply_filters( array $args, array $values ): array {
	if ( '' !== $values['q'] ) {
		$args['s'] = $values['q'];
	}
	if ( '' !== $values['cat'] && empty( $args['tax_query'] ) ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Category filter.
			array(
				'taxonomy' => MLAB_EM_CATEGORY,
				'field'    => 'slug',
				'terms'    => array( $values['cat'] ),
			),
		);
	}
	return $args;
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
			'filters'  => 'no',
		),
		$atts,
		'mlab_events'
	);

	$show         = in_array( $atts['show'], array( 'upcoming', 'past', 'all' ), true ) ? $atts['show'] : 'upcoming';
	$show_filters = 'yes' === $atts['filters'];
	$values       = mlab_em_filter_values();
	$args         = mlab_em_list_query_args( $show, sanitize_text_field( $atts['category'] ), max( 1, absint( $atts['limit'] ) ), $show_filters ? $values['from'] : '' );
	$query        = new WP_Query( $show_filters ? mlab_em_apply_filters( $args, $values ) : $args );

	wp_enqueue_style( 'mlab-em-frontend' );
	$output = mlab_em_get_template(
		'event-list',
		array(
			'query'   => $query,
			'filters' => $show_filters ? $values : null,
			'locked'  => '' !== $atts['category'],
		)
	);
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
