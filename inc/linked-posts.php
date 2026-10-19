<?php
/**
 * Venues and organizers: reusable entries an event can point to.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'mlab_em_register_linked_post_types' );
add_action( 'add_meta_boxes', 'mlab_em_add_linked_meta_box' );
add_action( 'save_post', 'mlab_em_save_linked_meta_box' );

/**
 * Post types an event can link to.
 *
 * @return string[]
 */
function mlab_em_linked_post_types(): array {
	return array( MLAB_EM_VENUE, MLAB_EM_ORGANIZER );
}

/**
 * Registers venue and organizer. They have no public page and sit under Events in the menu.
 */
function mlab_em_register_linked_post_types(): void {
	$types = array(
		MLAB_EM_VENUE     => array( __( 'Venues', 'mlab-eventmanager' ), __( 'Venue', 'mlab-eventmanager' ), __( 'Add venue', 'mlab-eventmanager' ) ),
		MLAB_EM_ORGANIZER => array( __( 'Organizers', 'mlab-eventmanager' ), __( 'Organizer', 'mlab-eventmanager' ), __( 'Add organizer', 'mlab-eventmanager' ) ),
	);

	foreach ( $types as $post_type => $labels ) {
		register_post_type(
			$post_type,
			array(
				'labels'       => array(
					'name'          => $labels[0],
					'singular_name' => $labels[1],
					'add_new'       => $labels[2],
					'add_new_item'  => $labels[2],
					'all_items'     => $labels[0],
					'edit_item'     => $labels[1],
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'edit.php?post_type=' . MLAB_EM_POST_TYPE,
				'supports'     => array( 'title' ),
			)
		);
	}
}

/**
 * Fields of a venue or organizer. Extend via the "mlab_em_linked_fields" filter.
 *
 * @param string $post_type Venue or organizer post type.
 * @return array<string, array<string, mixed>>
 */
function mlab_em_linked_fields( string $post_type ): array {
	$fields = array(
		'url' => array(
			'label' => __( 'Website', 'mlab-eventmanager' ),
			'type'  => 'url',
		),
	);

	if ( MLAB_EM_VENUE === $post_type ) {
		$fields = array(
			'address' => array(
				'label' => __( 'Address', 'mlab-eventmanager' ),
				'type'  => 'text',
				'hint'  => __( 'Street, postcode and city. Used for the map link.', 'mlab-eventmanager' ),
			),
			'lat'     => array(
				'label' => __( 'Latitude (optional)', 'mlab-eventmanager' ),
				'type'  => 'latitude',
				'hint'  => __( 'Decimal degrees, e.g. 47.3769. Makes the map pin exact.', 'mlab-eventmanager' ),
			),
			'lon'     => array(
				'label' => __( 'Longitude (optional)', 'mlab-eventmanager' ),
				'type'  => 'longitude',
				'hint'  => __( 'Decimal degrees, e.g. 8.5417.', 'mlab-eventmanager' ),
			),
		) + $fields;
	}

	return apply_filters( 'mlab_em_linked_fields', $fields, $post_type );
}

/**
 * Adds the details box to venues and organizers.
 */
function mlab_em_add_linked_meta_box(): void {
	foreach ( mlab_em_linked_post_types() as $post_type ) {
		add_meta_box( 'mlab-em-linked', __( 'Details', 'mlab-eventmanager' ), 'mlab_em_render_linked_meta_box', $post_type, 'normal', 'high' );
	}
}

/**
 * Prints the fields of a venue or organizer.
 *
 * @param WP_Post $post Current entry.
 */
function mlab_em_render_linked_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'mlab_em_save_linked', 'mlab_em_linked_nonce' );

	echo '<div class="mlab-em-meta-box">';
	mlab_em_render_field_list( mlab_em_linked_fields( $post->post_type ), static fn( $key ) => mlab_em_get_field( $post->ID, $key ) );
	echo '</div>';
}

/**
 * Saves the details of a venue or organizer.
 *
 * @param int $post_id Entry ID.
 */
function mlab_em_save_linked_meta_box( int $post_id ): void {
	$post_type = get_post_type( $post_id );
	$nonce     = isset( $_POST['mlab_em_linked_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mlab_em_linked_nonce'] ) ) : '';

	if ( ! in_array( $post_type, mlab_em_linked_post_types(), true ) || ! wp_verify_nonce( $nonce, 'mlab_em_save_linked' ) ) {
		return;
	}
	if ( wp_is_post_autosave( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Each value is sanitized per field type in mlab_em_sanitize_values().
	$input = isset( $_POST['mlab_em'] ) && is_array( $_POST['mlab_em'] ) ? wp_unslash( $_POST['mlab_em'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	mlab_em_store_values( $post_id, mlab_em_sanitize_values( mlab_em_linked_fields( $post_type ), $input ) );
}
