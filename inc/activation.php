<?php
/**
 * Activation: creates the quick entry page and refreshes permalinks.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Runs on activation.
 */
function mlab_em_activate(): void {
	mlab_em_load_textdomain();
	mlab_em_register_post_type();
	mlab_em_register_taxonomies();
	mlab_em_create_submit_page();
	flush_rewrite_rules();
}

/**
 * Runs on deactivation. Events and settings stay.
 */
function mlab_em_deactivate(): void {
	flush_rewrite_rules();
}

/**
 * Creates the "Add event" page once, unless a page is already set.
 */
function mlab_em_create_submit_page(): void {
	$page_id = absint( mlab_em_get_option( 'submit_page' ) );
	if ( $page_id && get_post( $page_id ) ) {
		return;
	}

	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => __( 'Add event', 'mlab-eventmanager' ),
			'post_content' => '<!-- wp:shortcode -->[mlab_event_form]<!-- /wp:shortcode -->',
		)
	);

	if ( $page_id && ! is_wp_error( $page_id ) ) {
		mlab_em_update_option( 'submit_page', $page_id );
	}
}
