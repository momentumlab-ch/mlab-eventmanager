<?php
/**
 * Quick entry for phones: [mlab_event_form].
 * Requires a WordPress login. Users who may publish posts publish directly,
 * all others submit for review. The same page lists, edits and deletes the user's own events.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'mlab_event_form', 'mlab_em_render_form_shortcode' );
add_action( 'admin_post_mlab_em_submit', 'mlab_em_handle_submit' );
add_action( 'admin_post_nopriv_mlab_em_submit', 'mlab_em_redirect_to_login' );

/**
 * URL of the quick entry page, empty if none is set.
 */
function mlab_em_submit_page_url(): string {
	$page_id = absint( mlab_em_get_option( 'submit_page' ) );

	return $page_id && 'publish' === get_post_status( $page_id ) ? (string) get_permalink( $page_id ) : '';
}

/**
 * Whether the current user has an event capability, e.g. create_posts or publish_posts.
 *
 * @param string $cap Capability name of the event post type.
 */
function mlab_em_user_can( string $cap ): bool {
	$post_type = get_post_type_object( MLAB_EM_POST_TYPE );

	return $post_type && current_user_can( $post_type->cap->$cap );
}

/**
 * Renders the quick entry page, or a login form for guests.
 */
function mlab_em_render_form_shortcode(): string {
	wp_enqueue_style( 'mlab-em-form' );

	if ( ! is_user_logged_in() ) {
		return mlab_em_get_template( 'event-login' );
	}
	if ( ! mlab_em_user_can( 'create_posts' ) ) {
		$notice = array(
			'type'    => 'warning',
			'message' => __( 'Your account may not add events. Please ask an administrator for access.', 'mlab-eventmanager' ),
		);
		return '<div class="mlab-em-form-wrap">' . mlab_em_get_template( 'event-notice', $notice ) . '</div>';
	}

	$view = mlab_em_current_view();

	return mlab_em_get_template(
		'event-page',
		array(
			'view'   => $view['name'],
			'notice' => mlab_em_form_notice(),
			'body'   => mlab_em_render_view( $view ),
		)
	);
}

/**
 * Prepares the form for a new event or an existing one.
 *
 * @param WP_Post|null $event Event to edit, null for a new one.
 */
function mlab_em_render_event_form( ?WP_Post $event ): string {
	$category = $event ? wp_get_object_terms( $event->ID, MLAB_EM_CATEGORY, array( 'fields' => 'ids' ) ) : array();

	return mlab_em_get_template(
		'event-form',
		array(
			'event'      => $event,
			'categories' => get_terms(
				array(
					'taxonomy'   => MLAB_EM_CATEGORY,
					'hide_empty' => false,
				)
			),
			'category'   => is_array( $category ) && $category ? (int) $category[0] : 0,
		)
	);
}

/**
 * Notice texts by status code.
 *
 * @param string $field_label Label of the missing field, for "missing_field".
 * @return array<string, array{0: string, 1: string}> Code => [type, message].
 */
function mlab_em_notice_messages( string $field_label ): array {
	return array(
		'publish'       => array( 'success', __( 'Event saved and published.', 'mlab-eventmanager' ) ),
		'pending'       => array( 'success', __( 'Event saved. It will be published after review.', 'mlab-eventmanager' ) ),
		'draft'         => array( 'success', __( 'Event saved as draft.', 'mlab-eventmanager' ) ),
		'updated'       => array( 'success', __( 'Changes saved.', 'mlab-eventmanager' ) ),
		'deleted'       => array( 'success', __( 'Event moved to the trash.', 'mlab-eventmanager' ) ),
		'image_failed'  => array( 'warning', __( 'Event saved, but the photo could not be uploaded. Please add it in the backend.', 'mlab-eventmanager' ) ),
		'missing_title' => array( 'error', __( 'Please enter a title.', 'mlab-eventmanager' ) ),
		/* translators: %s: field label, e.g. "Start". */
		'missing_field' => array( 'error', sprintf( __( 'Please fill in "%s".', 'mlab-eventmanager' ), $field_label ) ),
		'failed'        => array( 'error', __( 'The event could not be saved. Please try again.', 'mlab-eventmanager' ) ),
	);
}

/**
 * Message after a submission, read from the redirect URL.
 *
 * @return array{type: string, message: string, link?: string}|null
 */
function mlab_em_form_notice(): ?array {
	// Read-only display of the result; the submission itself was nonce-checked.
	$status   = isset( $_GET['mlab_em_status'] ) ? sanitize_key( $_GET['mlab_em_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post_id  = isset( $_GET['mlab_em_event'] ) ? absint( $_GET['mlab_em_event'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$field    = isset( $_GET['mlab_em_field'] ) ? sanitize_key( $_GET['mlab_em_field'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$messages = mlab_em_notice_messages( mlab_em_event_fields()[ $field ]['label'] ?? '' );

	if ( ! isset( $messages[ $status ] ) ) {
		return null;
	}

	$notice = array(
		'type'    => $messages[ $status ][0],
		'message' => $messages[ $status ][1],
	);
	if ( $post_id && get_current_user_id() === (int) get_post_field( 'post_author', $post_id ) ) {
		$notice['link'] = 'publish' === get_post_status( $post_id ) ? (string) get_permalink( $post_id ) : (string) get_edit_post_link( $post_id, 'raw' );
	}

	return $notice;
}

/**
 * Handles the form post and redirects back to the form.
 */
function mlab_em_handle_submit(): void {
	check_admin_referer( 'mlab_em_submit', 'mlab_em_nonce' );

	if ( ! mlab_em_user_can( 'create_posts' ) ) {
		wp_die( esc_html__( 'Your account may not add events.', 'mlab-eventmanager' ), '', array( 'response' => 403 ) );
	}

	$result = mlab_em_create_event_from_request();

	if ( is_wp_error( $result ) ) {
		mlab_em_redirect_with_status( mlab_em_back_url(), $result->get_error_code(), 0, (string) $result->get_error_data() );
	}

	$status = mlab_em_attach_uploaded_image( $result ) ? (string) get_post_status( $result ) : 'image_failed';
	mlab_em_redirect_with_status( mlab_em_back_url(), $status, $result );
}

/**
 * Redirects to a URL with the result code that the notice reads.
 *
 * @param string $url      Target URL.
 * @param string $status   Notice code.
 * @param int    $event_id Event the notice links to, 0 for none.
 * @param string $field    Field key for "missing_field".
 */
function mlab_em_redirect_with_status( string $url, string $status, int $event_id = 0, string $field = '' ): void {
	$url  = remove_query_arg( array( 'mlab_em_status', 'mlab_em_event', 'mlab_em_field' ), $url );
	$args = array(
		'mlab_em_status' => $status,
		'mlab_em_event'  => $event_id,
		'mlab_em_field'  => $field,
	);

	wp_safe_redirect( add_query_arg( array_filter( $args ), $url ) );
	exit;
}

/**
 * Page the form was sent from, or the home page.
 */
function mlab_em_back_url(): string {
	$referer = wp_get_referer();

	return $referer ? $referer : home_url( '/' );
}

/**
 * Status for new events: published if the user may publish, otherwise pending review.
 */
function mlab_em_submit_post_status(): string {
	$status = mlab_em_user_can( 'publish_posts' ) ? 'publish' : 'pending';

	return (string) apply_filters( 'mlab_em_submit_post_status', $status );
}

/**
 * The posted form values, still unsanitized. Each is sanitized per type when saved.
 *
 * @return array<string, mixed>
 */
function mlab_em_posted_input(): array {
	// Nonce checked by the calling handler; fields are sanitized per type in mlab_em_save_fields().
	return isset( $_POST['mlab_em'] ) && is_array( $_POST['mlab_em'] ) ? wp_unslash( $_POST['mlab_em'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
}

/**
 * Checks title and required fields of a submission.
 *
 * @param array<string, mixed> $input Raw values.
 * @return WP_Error|null Error with a notice code, or null if the input is complete.
 */
function mlab_em_validate_input( array $input ): ?WP_Error {
	if ( '' === sanitize_text_field( $input['title'] ?? '' ) ) {
		return new WP_Error( 'missing_title' );
	}

	$missing = mlab_em_missing_required_field( $input );

	return null === $missing ? null : new WP_Error( 'missing_field', '', $missing );
}

/**
 * Creates the event from the posted form.
 *
 * @return int|WP_Error Event ID or error with a notice code.
 */
function mlab_em_create_event_from_request() {
	$input = mlab_em_posted_input();
	$error = mlab_em_validate_input( $input );
	if ( $error ) {
		return $error;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => MLAB_EM_POST_TYPE,
			'post_title'   => sanitize_text_field( $input['title'] ),
			'post_content' => sanitize_textarea_field( $input['description'] ?? '' ),
			'post_status'  => mlab_em_submit_post_status(),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		return new WP_Error( 'failed' );
	}

	mlab_em_save_fields( $post_id, $input );
	mlab_em_assign_category( $post_id, absint( $input['category'] ?? 0 ) );
	do_action( 'mlab_em_event_submitted', $post_id, $input );

	return $post_id;
}

/**
 * Assigns the chosen category if it exists. 0 removes the category.
 *
 * @param int $post_id Event ID.
 * @param int $term_id Category term ID, 0 for none.
 */
function mlab_em_assign_category( int $post_id, int $term_id ): void {
	if ( ! $term_id ) {
		wp_set_object_terms( $post_id, array(), MLAB_EM_CATEGORY );
	} elseif ( term_exists( $term_id, MLAB_EM_CATEGORY ) ) {
		wp_set_object_terms( $post_id, $term_id, MLAB_EM_CATEGORY );
	}
}

/**
 * Saves an uploaded photo as featured image. Only image types are accepted.
 *
 * @param int $post_id Event ID.
 * @return bool False if a file was sent but could not be saved.
 */
function mlab_em_attach_uploaded_image( int $post_id ): bool {
	if ( empty( $_FILES['mlab_em_image']['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in mlab_em_handle_submit().
		return true;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$image_mimes   = array_filter( get_allowed_mime_types(), static fn( $mime ) => str_starts_with( $mime, 'image/' ) );
	$attachment_id = media_handle_upload(
		'mlab_em_image',
		$post_id,
		array(),
		array(
			'test_form' => false,
			'mimes'     => $image_mimes,
		)
	);
	if ( is_wp_error( $attachment_id ) ) {
		return false;
	}

	return (bool) set_post_thumbnail( $post_id, $attachment_id );
}

/**
 * Guests who post the form are sent to the login.
 */
function mlab_em_redirect_to_login(): void {
	wp_safe_redirect( wp_login_url( mlab_em_back_url() ) );
	exit;
}
