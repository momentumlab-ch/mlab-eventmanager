<?php
/**
 * Date and location columns in the event list, sortable by date.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'manage_' . MLAB_EM_POST_TYPE . '_posts_columns', 'mlab_em_admin_columns' );
add_action( 'manage_' . MLAB_EM_POST_TYPE . '_posts_custom_column', 'mlab_em_admin_column_content', 10, 2 );
add_filter( 'manage_edit-' . MLAB_EM_POST_TYPE . '_sortable_columns', 'mlab_em_admin_sortable_columns' );
add_action( 'pre_get_posts', 'mlab_em_admin_sort_by_start' );

/**
 * Inserts the date column and all fields marked "column" after the title.
 *
 * @param array<string, string> $columns Existing columns.
 * @return array<string, string>
 */
function mlab_em_admin_columns( array $columns ): array {
	$added = array( 'mlab_em_start' => __( 'Event date', 'mlab-eventmanager' ) );
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
 * Sorts the admin list by start date when requested.
 *
 * @param WP_Query $query Current query.
 */
function mlab_em_admin_sort_by_start( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() || 'mlab_em_start' !== $query->get( 'orderby' ) ) {
		return;
	}

	$args            = mlab_em_date_query_args( 'all' );
	$args['orderby'] = array( 'mlab_em_start' => 'desc' === strtolower( (string) $query->get( 'order' ) ) ? 'DESC' : 'ASC' );
	foreach ( $args as $key => $value ) {
		$query->set( $key, $value );
	}
}
