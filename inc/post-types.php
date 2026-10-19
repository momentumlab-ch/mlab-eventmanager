<?php
/**
 * Event post type, taxonomies and REST meta.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

const MLAB_EM_POST_TYPE = 'mlab_event';
const MLAB_EM_CATEGORY  = 'mlab_event_category';
const MLAB_EM_TAG       = 'mlab_event_tag';
const MLAB_EM_VENUE     = 'mlab_venue';
const MLAB_EM_ORGANIZER = 'mlab_organizer';

add_action( 'init', 'mlab_em_register_post_type' );
add_action( 'init', 'mlab_em_register_taxonomies' );
add_action( 'init', 'mlab_em_register_meta', 20 );

/**
 * Registers the event post type.
 */
function mlab_em_register_post_type(): void {
	$args = array(
		'labels'        => array(
			'name'               => __( 'Events', 'mlab-eventmanager' ),
			'singular_name'      => __( 'Event', 'mlab-eventmanager' ),
			'add_new'            => __( 'Add event', 'mlab-eventmanager' ),
			'add_new_item'       => __( 'Add new event', 'mlab-eventmanager' ),
			'edit_item'          => __( 'Edit event', 'mlab-eventmanager' ),
			'view_item'          => __( 'View event', 'mlab-eventmanager' ),
			'search_items'       => __( 'Search events', 'mlab-eventmanager' ),
			'not_found'          => __( 'No events found.', 'mlab-eventmanager' ),
			'not_found_in_trash' => __( 'No events found in the trash.', 'mlab-eventmanager' ),
			'all_items'          => __( 'All events', 'mlab-eventmanager' ),
		),
		'public'        => true,
		'show_in_rest'  => true,
		'has_archive'   => true,
		'menu_icon'     => 'dashicons-calendar-alt',
		'menu_position' => 20,
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' ),
		'rewrite'       => array(
			'slug'       => _x( 'events', 'URL slug', 'mlab-eventmanager' ),
			'with_front' => false,
		),
	);

	register_post_type( MLAB_EM_POST_TYPE, apply_filters( 'mlab_em_post_type_args', $args ) );
}

/**
 * Registers event categories (hierarchical) and tags.
 */
function mlab_em_register_taxonomies(): void {
	$taxonomies = array(
		MLAB_EM_CATEGORY => array(
			'hierarchical' => true,
			'label'        => __( 'Event categories', 'mlab-eventmanager' ),
			'slug'         => _x( 'event-category', 'URL slug', 'mlab-eventmanager' ),
		),
		MLAB_EM_TAG      => array(
			'hierarchical' => false,
			'label'        => __( 'Event tags', 'mlab-eventmanager' ),
			'slug'         => _x( 'event-tag', 'URL slug', 'mlab-eventmanager' ),
		),
	);

	foreach ( $taxonomies as $taxonomy => $config ) {
		$args = array(
			'hierarchical'      => $config['hierarchical'],
			'label'             => $config['label'],
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => $config['slug'] ),
		);
		register_taxonomy( $taxonomy, MLAB_EM_POST_TYPE, apply_filters( 'mlab_em_taxonomy_args', $args, $taxonomy ) );
	}
}

/**
 * Exposes all event fields in the REST API, writable by users who may edit the event.
 */
function mlab_em_register_meta(): void {
	foreach ( mlab_em_event_fields() as $key => $field ) {
				register_post_meta(
					MLAB_EM_POST_TYPE,
					mlab_em_meta_key( $key ),
					array(
						'type'              => 'string',
						'single'            => true,
						'show_in_rest'      => true,
						'sanitize_callback' => static fn( $value ) => mlab_em_sanitize_value( $value, $field['type'], $field ),
						'auth_callback'     => static fn( $allowed, $meta_key, $post_id ) => current_user_can( 'edit_post', $post_id ),
					)
				);
	}
}
