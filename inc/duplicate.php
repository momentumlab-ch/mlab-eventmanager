<?php
/**
 * Duplicate an event: row action in the admin list, link in "My events".
 * The copy is a draft, so nothing goes live by accident.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'post_row_actions', 'mlab_em_duplicate_row_action', 10, 2 );
add_action( 'admin_post_mlab_em_duplicate', 'mlab_em_handle_duplicate' );

/**
 * URL that duplicates an event.
 *
 * @param int  $post_id Event ID.
 * @param bool $front   True to return to the frontend "My events" page afterwards.
 */
function mlab_em_duplicate_url( int $post_id, bool $front = false ): string {
	$args = array(
		'action' => 'mlab_em_duplicate',
		'event'  => $post_id,
	);
	if ( $front ) {
		$args['front'] = 1;
	}

	return wp_nonce_url( add_query_arg( $args, admin_url( 'admin-post.php' ) ), 'mlab_em_duplicate_' . $post_id );
}

/**
 * Adds "Duplicate" to the event rows of the admin list.
 *
 * @param array<string, string> $actions Row actions.
 * @param WP_Post               $post    Row event.
 * @return array<string, string>
 */
function mlab_em_duplicate_row_action( array $actions, WP_Post $post ): array {
	if ( MLAB_EM_POST_TYPE === $post->post_type && mlab_em_user_can( 'create_posts' ) && current_user_can( 'edit_post', $post->ID ) ) {
		$actions['mlab_em_duplicate'] = sprintf( '<a href="%s">%s</a>', esc_url( mlab_em_duplicate_url( $post->ID ) ), esc_html__( 'Duplicate', 'mlab-eventmanager' ) );
	}

	return $actions;
}

/**
 * Copies an event as draft owned by the current user: fields, terms and image.
 *
 * @param int $source_id Event to copy.
 * @return int|WP_Error New event ID.
 */
function mlab_em_duplicate_event( int $source_id ) {
	$source = get_post( $source_id );
	if ( ! $source || MLAB_EM_POST_TYPE !== $source->post_type ) {
		return new WP_Error( 'mlab_em_not_found', __( 'Event not found.', 'mlab-eventmanager' ) );
	}

	$new_id = wp_insert_post(
		array(
			'post_type'    => MLAB_EM_POST_TYPE,
			'post_status'  => 'draft',
			/* translators: %s: event title. */
			'post_title'   => sprintf( __( '%s (copy)', 'mlab-eventmanager' ), $source->post_title ),
			'post_content' => $source->post_content,
			'post_excerpt' => $source->post_excerpt,
		),
		true
	);
	if ( is_wp_error( $new_id ) ) {
		return $new_id;
	}

	$values = array();
	foreach ( array_keys( mlab_em_event_fields() ) as $key ) {
		$values[ $key ] = mlab_em_get_field( $source_id, $key );
	}
	mlab_em_store_values( $new_id, $values );

	foreach ( array( MLAB_EM_CATEGORY, MLAB_EM_TAG ) as $taxonomy ) {
		wp_set_object_terms( $new_id, wp_get_object_terms( $source_id, $taxonomy, array( 'fields' => 'ids' ) ), $taxonomy );
	}
	if ( has_post_thumbnail( $source_id ) ) {
		set_post_thumbnail( $new_id, (int) get_post_thumbnail_id( $source_id ) );
	}

	do_action( 'mlab_em_event_duplicated', $new_id, $source_id );

	return $new_id;
}

/**
 * Handles the duplicate link and redirects to the copy.
 */
function mlab_em_handle_duplicate(): void {
	$source_id = isset( $_GET['event'] ) ? absint( $_GET['event'] ) : 0;
	check_admin_referer( 'mlab_em_duplicate_' . $source_id );

	if ( ! mlab_em_user_can( 'create_posts' ) || ! current_user_can( 'edit_post', $source_id ) ) {
		wp_die( esc_html__( 'You may not duplicate this event.', 'mlab-eventmanager' ), '', array( 'response' => 403 ) );
	}

	$new_id = mlab_em_duplicate_event( $source_id );
	if ( is_wp_error( $new_id ) ) {
		wp_die( esc_html( $new_id->get_error_message() ), '', array( 'response' => 500 ) );
	}

	$target = isset( $_GET['front'] ) ? mlab_em_my_events_url( 'edit', $new_id ) : get_edit_post_link( $new_id, 'raw' );
	wp_safe_redirect( (string) $target );
	exit;
}
