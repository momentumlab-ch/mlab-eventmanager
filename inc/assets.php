<?php
/**
 * Styles. Registered globally, enqueued only where they are needed.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'mlab_em_register_assets' );
add_action( 'admin_enqueue_scripts', 'mlab_em_admin_assets' );

/**
 * Registers the frontend styles and loads them on single event pages.
 */
function mlab_em_register_assets(): void {
	wp_register_style( 'mlab-em-frontend', MLAB_EM_URL . 'assets/css/frontend.css', array(), MLAB_EM_VERSION );
	wp_register_style( 'mlab-em-form', MLAB_EM_URL . 'assets/css/form.css', array( 'mlab-em-frontend' ), MLAB_EM_VERSION );

	if ( is_singular( MLAB_EM_POST_TYPE ) || is_post_type_archive( MLAB_EM_POST_TYPE ) ) {
		wp_enqueue_style( 'mlab-em-frontend' );
	}
}

/**
 * Loads the admin styles and the tabs script on the screens of events, venues and organizers.
 */
function mlab_em_admin_assets(): void {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array_merge( array( MLAB_EM_POST_TYPE ), mlab_em_linked_post_types() ), true ) ) {
		return;
	}

	wp_enqueue_style( 'mlab-em-admin', MLAB_EM_URL . 'assets/css/admin.css', array(), MLAB_EM_VERSION );
	wp_enqueue_script(
		'mlab-em-admin',
		MLAB_EM_URL . 'assets/js/admin.js',
		array(),
		MLAB_EM_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
