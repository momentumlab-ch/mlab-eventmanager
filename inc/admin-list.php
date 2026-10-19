<?php
/**
 * Event list in the admin: Upcoming/Past views, date and place columns, sortable by date.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'manage_' . MLAB_EM_POST_TYPE . '_posts_columns', 'mlab_em_admin_columns' );
add_action( 'manage_' . MLAB_EM_POST_TYPE . '_posts_custom_column', 'mlab_em_admin_column_content', 10, 2 );
add_filter( 'manage_edit-' . MLAB_EM_POST_TYPE . '_sortable_columns', 'mlab_em_admin_sortable_columns' );
add_filter( 'views_edit-' . MLAB_EM_POST_TYPE, 'mlab_em_admin_views' );
add_action( 'pre_get_posts', 'mlab_em_admin_query' );

/**
 * Inserts the date and place columns and all fields marked "column" after the title.
 *
 * @param array<string, string> $columns Existing columns.
 * @return array<string, string>
 */
function mlab_em_admin_columns( array $columns ): array {
	$added = array(
		'mlab_em_start' => __( 'Event date', 'mlab-eventmanager' ),
		'mlab_em_place' => __( 'Where', 'mlab-eventmanager' ),
	);
	foreach ( mlab_em_event_fields() as $key => $field ) {
		if ( ! empty( $field['column'] ) ) {
			$added[ 'mlab_em_field_' . $key ] = $field['label'];
		}
	}

	$position = array_search( 'title', array_keys( $columns ), true );
	$position = false === $position ? count( $columns ) : $position + 1;

	return array_slice( $columns, 0, $position, true ) + $added + array_slice( $columns, $position, null, true );
}

/**
 * Prints the column content.
 *
 * @param string $column  Column key.
 * @param int    $post_id Event ID.
 */
function mlab_em_admin_column_content( string $column, int $post_id ): void {
	if ( 'mlab_em_start' === $column ) {
		echo esc_html( mlab_em_format_date_range( $post_id ) );
		return;
	}
	if ( 'mlab_em_place' === $column ) {
		echo esc_html( mlab_em_place_text( $post_id ) );
		if ( '' !== mlab_em_get_field( $post_id, 'online_url' ) ) {
			echo ' <span class="mlab-em-badge">' . esc_html__( 'Online', 'mlab-eventmanager' ) . '</span>';
		}
		return;
	}

	$key   = str_replace( 'mlab_em_field_', '', $column );
	$field = mlab_em_event_fields()[ $key ] ?? null;
	if ( str_starts_with( $column, 'mlab_em_field_' ) && $field ) {
		echo esc_html( mlab_em_display_value( $post_id, $key, $field['type'] ) );
	}
}

/**
 * Makes the date column sortable.
 *
 * @param array<string, string> $columns Sortable columns.
 * @return array<string, string>
 */
function mlab_em_admin_sortable_columns( array $columns ): array {
	$columns['mlab_em_start'] = 'mlab_em_start';
	return $columns;
}

/**
 * The selected Upcoming/Past view, empty for all events.
 */
function mlab_em_admin_when(): string {
	// Read-only list filter in the admin URL.
	$when = isset( $_GET['mlab_em_when'] ) ? sanitize_key( $_GET['mlab_em_when'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return in_array( $when, array( 'upcoming', 'past' ), true ) ? $when : '';
}

/**
 * Adds the Upcoming and Past views next to All, Published and Draft.
 *
 * @param array<string, string> $views Existing views.
 * @return array<string, string>
 */
function mlab_em_admin_views( array $views ): array {
	$current = mlab_em_admin_when();
	$labels  = array(
		'upcoming' => __( 'Upcoming', 'mlab-eventmanager' ),
		'past'     => __( 'Past', 'mlab-eventmanager' ),
	);

	foreach ( $labels as $when => $label ) {
		$count = new WP_Query(
			array(
				'post_type'              => MLAB_EM_POST_TYPE,
				'post_status'            => mlab_em_event_statuses(),
				'fields'                 => 'ids',
				'posts_per_page'         => 1,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			) + mlab_em_date_query_args( $when )
		);
		$url   = add_query_arg(
			array(
				'post_type'    => MLAB_EM_POST_TYPE,
				'mlab_em_when' => $when,
			),
			admin_url( 'edit.php' )
		);

		$views[ 'mlab_em_' . $when ] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( $url ),
			$current === $when ? ' class="current" aria-current="page"' : '',
			esc_html( $label ),
			(int) $count->found_posts
		);
	}

	return $views;
}

/**
 * Applies the Upcoming/Past view and the date sort to the admin list.
 *
 * @param WP_Query $query Current query.
 */
function mlab_em_admin_query( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() || MLAB_EM_POST_TYPE !== $query->get( 'post_type' ) ) {
		return;
	}

	$when    = mlab_em_admin_when();
	$sorting = 'mlab_em_start' === $query->get( 'orderby' );
	if ( '' === $when && ! $sorting ) {
		return;
	}

	$order = $sorting ? (string) $query->get( 'order' ) : '';
	$args  = mlab_em_date_query_args( '' === $when ? 'all' : $when, '', $order );
	foreach ( $args as $key => $value ) {
		$query->set( $key, $value );
	}
}
