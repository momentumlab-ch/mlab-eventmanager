<?php
/**
 * "My events" on the quick entry page: see, change, duplicate and delete your own events.
 * Views are chosen by ?mlab_em_view=new|mine|edit|delete, so every view has its own address.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_mlab_em_update', 'mlab_em_handle_update' );
add_action( 'admin_post_mlab_em_delete', 'mlab_em_handle_delete' );
add_action( 'admin_post_nopriv_mlab_em_update', 'mlab_em_redirect_to_login' );
add_action( 'admin_post_nopriv_mlab_em_delete', 'mlab_em_redirect_to_login' );

/**
 * URL of a view of the quick entry page.
 *
 * @param string $view     new, mine, edit or delete.
 * @param int    $event_id Event for edit and delete.
 */
function mlab_em_my_events_url( string $view = 'mine', int $event_id = 0 ): string {
	$base = mlab_em_submit_page_url();
	$args = array(
		'mlab_em_view'  => $view,
		'mlab_em_event' => $event_id,
	);

	return add_query_arg( array_filter( $args ), '' !== $base ? $base : home_url( '/' ) );
}

/**
 * The requested view and event. Events of other users are never returned.
 *
 * @return array{name: string, event: WP_Post|null}
 */
function mlab_em_current_view(): array {
	// Read-only navigation; changes go through nonce-checked handlers.
	$name     = isset( $_GET['mlab_em_view'] ) ? sanitize_key( $_GET['mlab_em_view'] ) : 'new'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$event_id = isset( $_GET['mlab_em_event'] ) ? absint( $_GET['mlab_em_event'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$event    = get_post( $event_id );

	if ( in_array( $name, array( 'edit', 'delete' ), true ) ) {
		$allowed = $event && MLAB_EM_POST_TYPE === $event->post_type && current_user_can( 'edit_post', $event->ID );
		if ( ! $allowed || ( 'delete' === $name && ! current_user_can( 'delete_post', $event->ID ) ) ) {
			return array(
				'name'  => 'mine',
				'event' => null,
			);
		}

		return array(
			'name'  => $name,
			'event' => $event,
		);
	}

	return array(
		'name'  => 'mine' === $name ? 'mine' : 'new',
		'event' => null,
	);
}

/**
 * Content of the current view.
 *
 * @param array{name: string, event: WP_Post|null} $view Current view.
 */
function mlab_em_render_view( array $view ): string {
	switch ( $view['name'] ) {
		case 'mine':
			return mlab_em_get_template( 'event-my-events', array( 'events' => mlab_em_get_own_events() ) );
		case 'delete':
			return mlab_em_get_template( 'event-delete', array( 'event' => $view['event'] ) );
		default:
			return mlab_em_render_event_form( $view['event'] );
	}
}

/**
 * The current user's events in every status except trash, newest event date first.
 *
 * @return WP_Post[]
 */
function mlab_em_get_own_events(): array {
	$args = array(
		'post_type'      => MLAB_EM_POST_TYPE,
		'post_status'    => mlab_em_event_statuses(),
		'author'         => get_current_user_id(),
		'posts_per_page' => 50,
		'no_found_rows'  => true,
	) + mlab_em_date_query_args( 'all', '', 'DESC' );

	return get_posts( apply_filters( 'mlab_em_own_events_args', $args ) );
}

/**
 * Short label of an event status for the list.
 *
 * @param string $status Post status.
 */
function mlab_em_status_label( string $status ): string {
	$labels = array(
		'publish' => __( 'Published', 'mlab-eventmanager' ),
		'pending' => __( 'Waiting for review', 'mlab-eventmanager' ),
		'draft'   => __( 'Draft', 'mlab-eventmanager' ),
		'future'  => __( 'Scheduled', 'mlab-eventmanager' ),
		'private' => __( 'Private', 'mlab-eventmanager' ),
	);

	return $labels[ $status ] ?? $status;
}

/**
 * Handles the edit form.
 */
function mlab_em_handle_update(): void {
	check_admin_referer( 'mlab_em_update', 'mlab_em_nonce' );

	$post_id = isset( $_POST['mlab_em_event'] ) ? absint( $_POST['mlab_em_event'] ) : 0;
	if ( MLAB_EM_POST_TYPE !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You may not change this event.', 'mlab-eventmanager' ), '', array( 'response' => 403 ) );
	}

	$input = mlab_em_posted_input();
	$error = mlab_em_validate_input( $input );
	if ( $error ) {
		mlab_em_redirect_with_status( mlab_em_back_url(), $error->get_error_code(), 0, (string) $error->get_error_data() );
	}

	$postarr = array(
		'ID'         => $post_id,
		'post_title' => sanitize_text_field( $input['title'] ),
	);
	if ( mlab_em_description_editable( (string) get_post_field( 'post_content', $post_id ) ) ) {
		$postarr['post_content'] = sanitize_textarea_field( $input['description'] ?? '' );
	}

	if ( is_wp_error( wp_update_post( $postarr, true ) ) ) {
		mlab_em_redirect_with_status( mlab_em_back_url(), 'failed' );
	}

	mlab_em_save_fields( $post_id, $input );
	mlab_em_assign_category( $post_id, absint( $input['category'] ?? 0 ) );
	do_action( 'mlab_em_event_updated', $post_id, $input );

	$status = mlab_em_attach_uploaded_image( $post_id ) ? 'updated' : 'image_failed';
	mlab_em_redirect_with_status( mlab_em_my_events_url( 'mine' ), $status, $post_id );
}

/**
 * Plain text descriptions can be edited on the phone. Text with markup or blocks from the
 * backend editor can only be changed there, so formatting is never lost.
 *
 * @param string $content Post content.
 */
function mlab_em_description_editable( string $content ): bool {
	return wp_strip_all_tags( $content ) === $content;
}

/**
 * Moves an event to the trash after confirmation.
 */
function mlab_em_handle_delete(): void {
	check_admin_referer( 'mlab_em_delete', 'mlab_em_nonce' );

	$post_id = isset( $_POST['mlab_em_event'] ) ? absint( $_POST['mlab_em_event'] ) : 0;
	if ( MLAB_EM_POST_TYPE !== get_post_type( $post_id ) || ! current_user_can( 'delete_post', $post_id ) ) {
		wp_die( esc_html__( 'You may not delete this event.', 'mlab-eventmanager' ), '', array( 'response' => 403 ) );
	}

	$status = wp_trash_post( $post_id ) ? 'deleted' : 'failed';
	mlab_em_redirect_with_status( mlab_em_my_events_url( 'mine' ), $status );
}
