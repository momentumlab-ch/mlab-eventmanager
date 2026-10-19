<?php
/**
 * Settings page under Events > Settings.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'mlab_em_add_settings_page' );
add_action( 'admin_init', 'mlab_em_register_settings' );
add_filter( 'plugin_action_links_' . plugin_basename( MLAB_EM_FILE ), 'mlab_em_plugin_action_links' );

/**
 * Adds the submenu page.
 */
function mlab_em_add_settings_page(): void {
	add_submenu_page(
		'edit.php?post_type=' . MLAB_EM_POST_TYPE,
		__( 'Event settings', 'mlab-eventmanager' ),
		__( 'Settings', 'mlab-eventmanager' ),
		'manage_options',
		'mlab-em-settings',
		'mlab_em_render_settings_page'
	);
}

/**
 * Registers the option and one field per setting definition.
 */
function mlab_em_register_settings(): void {
	register_setting( 'mlab_em_settings', MLAB_EM_OPTION, array( 'sanitize_callback' => 'mlab_em_sanitize_settings' ) );
	add_settings_section( 'mlab_em_pages', __( 'Pages', 'mlab-eventmanager' ), '__return_null', 'mlab-em-settings' );
	add_settings_section( 'mlab_em_display', __( 'Display', 'mlab-eventmanager' ), '__return_null', 'mlab-em-settings' );

	foreach ( mlab_em_settings_fields() as $key => $field ) {
		add_settings_field(
			'mlab_em_' . $key,
			$field['label'],
			'mlab_em_render_setting_field',
			'mlab-em-settings',
			'mlab_em_' . ( $field['section'] ?? 'display' ),
			array(
				'key'       => $key,
				'field'     => $field,
				'label_for' => 'mlab-em-setting-' . $key,
			)
		);
	}
}

/**
 * Prints one settings input.
 *
 * @param array{key: string, field: array<string, mixed>, label_for: string} $args Field data.
 */
function mlab_em_render_setting_field( array $args ): void {
	$name  = MLAB_EM_OPTION . '[' . $args['key'] . ']';
	$value = mlab_em_get_option( $args['key'] );

	if ( 'page' === $args['field']['type'] ) {
		wp_dropdown_pages(
			array(
				'name'              => esc_attr( $name ),
				'id'                => esc_attr( $args['label_for'] ),
				'selected'          => absint( $value ),
				'show_option_none'  => esc_html__( '— Select a page —', 'mlab-eventmanager' ),
				'option_none_value' => '0',
			)
		);
	} elseif ( 'checkbox' === $args['field']['type'] ) {
		printf( '<input type="checkbox" id="%s" name="%s" value="1"%s>', esc_attr( $args['label_for'] ), esc_attr( $name ), checked( (int) $value, 1, false ) );
	} else {
		printf( '<input type="number" min="%d" class="small-text" id="%s" name="%s" value="%s">', (int) ( $args['field']['min'] ?? 1 ), esc_attr( $args['label_for'] ), esc_attr( $name ), esc_attr( (string) $value ) );
	}

	if ( ! empty( $args['field']['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $args['field']['description'] ) );
	}
}

/**
 * Prints the settings page with usage hints.
 */
function mlab_em_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<div class="wrap"><h1>' . esc_html__( 'Event settings', 'mlab-eventmanager' ) . '</h1>';
	echo mlab_em_get_template( 'admin-help', array( 'submit_url' => mlab_em_submit_page_url() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its own output.
	echo '<form method="post" action="options.php">';
	settings_fields( 'mlab_em_settings' );
	do_settings_sections( 'mlab-em-settings' );
	submit_button();
	echo '</form></div>';
}

/**
 * Adds "Settings" and "Quick entry" links in the plugin list.
 *
 * @param array<int|string, string> $links Existing links.
 * @return array<int|string, string>
 */
function mlab_em_plugin_action_links( array $links ): array {
	$settings = admin_url( 'edit.php?post_type=' . MLAB_EM_POST_TYPE . '&page=mlab-em-settings' );
	array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( $settings ), esc_html__( 'Settings', 'mlab-eventmanager' ) ) );

	$submit_url = mlab_em_submit_page_url();
	if ( $submit_url ) {
		$links[] = sprintf( '<a href="%s">%s</a>', esc_url( $submit_url ), esc_html__( 'Quick entry', 'mlab-eventmanager' ) );
	}

	return $links;
}
