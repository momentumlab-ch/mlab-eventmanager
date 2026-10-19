<?php
/**
 * "Event details" box in the editor.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_' . MLAB_EM_POST_TYPE, 'mlab_em_add_meta_box' );
add_action( 'save_post_' . MLAB_EM_POST_TYPE, 'mlab_em_save_meta_box' );
add_action( 'edit_form_after_title', 'mlab_em_render_top_boxes' );
add_filter( 'get_user_option_meta-box-order_' . MLAB_EM_POST_TYPE, 'mlab_em_keep_details_on_top' );

/**
 * Adds the details box. It sits right below the title, so date and place come first.
 */
function mlab_em_add_meta_box(): void {
	add_meta_box( 'mlab-em-details', __( 'Event details', 'mlab-eventmanager' ), 'mlab_em_render_meta_box', MLAB_EM_POST_TYPE, 'mlab_em_top', 'high' );
}

/**
 * Prints the boxes of the "mlab_em_top" context between title and text editor.
 *
 * @param WP_Post $post Current post.
 */
function mlab_em_render_top_boxes( WP_Post $post ): void {
	if ( MLAB_EM_POST_TYPE !== $post->post_type ) {
		return;
	}
	do_meta_boxes( get_current_screen(), 'mlab_em_top', $post );
}

/**
 * Prints all event fields.
 *
 * @param WP_Post $post Current event.
 */
function mlab_em_render_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'mlab_em_save_meta', 'mlab_em_meta_nonce' );

	echo '<div class="mlab-em-meta-box" data-mlab-em-tabs>';
	mlab_em_render_field_groups( mlab_em_event_fields(), static fn( $key ) => mlab_em_get_field( $post->ID, $key ) );
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

/**
 * A box order saved by the user (dragged earlier) would move the details box back below the editor.
 * The details box is left out of that order, so it always sits below the title.
 *
 * @param mixed $order Saved order per context.
 * @return mixed
 */
function mlab_em_keep_details_on_top( $order ) {
	if ( ! is_array( $order ) ) {
		return $order;
	}
	foreach ( $order as $context => $boxes ) {
		$ids               = array_filter( explode( ',', (string) $boxes ), static fn( $id ) => 'mlab-em-details' !== $id );
		$order[ $context ] = implode( ',', $ids );
	}
	return $order;
}
