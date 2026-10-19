<?php
/**
 * "Event details" box in the editor.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_' . MLAB_EM_POST_TYPE, 'mlab_em_add_meta_box' );
add_action( 'save_post_' . MLAB_EM_POST_TYPE, 'mlab_em_save_meta_box' );

/**
 * Adds the details box below the editor.
 */
function mlab_em_add_meta_box(): void {
	add_meta_box( 'mlab-em-details', __( 'Event details', 'mlab-eventmanager' ), 'mlab_em_render_meta_box', MLAB_EM_POST_TYPE, 'normal', 'high' );
}

/**
 * Prints all event fields.
 *
 * @param WP_Post $post Current event.
 */
function mlab_em_render_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'mlab_em_save_meta', 'mlab_em_meta_nonce' );

	echo '<div class="mlab-em-meta-box">';
	foreach ( mlab_em_event_fields() as $key => $field ) {
		echo '<p class="mlab-em-field">';
		mlab_em_render_input( $key, $field, mlab_em_get_field( $post->ID, $key ) );
		echo '</p>';
	}
	echo '</div>';
}

/**
 * Saves the details box.
 *
 * @param int $post_id Event ID.
 */
function mlab_em_save_meta_box( int $post_id ): void {
	$nonce = isset( $_POST['mlab_em_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mlab_em_meta_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'mlab_em_save_meta' ) || wp_is_post_autosave( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Each value is sanitized per field type in mlab_em_save_fields().
	$input = isset( $_POST['mlab_em'] ) && is_array( $_POST['mlab_em'] ) ? wp_unslash( $_POST['mlab_em'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	mlab_em_save_fields( $post_id, $input );
}
