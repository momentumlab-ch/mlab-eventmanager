<?php
/**
 * All plugin settings in one place: key, label, type and default.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

const MLAB_EM_OPTION = 'mlab_em_settings';

/**
 * Setting definitions. Extend via the "mlab_em_settings_fields" filter.
 *
 * Types: page (page ID) and number (positive integer, at least "min").
 *
 * @return array<string, array{label: string, type: string, default: mixed, min?: int, description?: string}>
 */
function mlab_em_settings_fields(): array {
	$fields = array(
		'submit_page' => array(
			'label'       => __( 'Quick entry page', 'mlab-eventmanager' ),
			'type'        => 'page',
			'default'     => 0,
			'description' => __( 'Page with the phone form. It is created automatically on activation.', 'mlab-eventmanager' ),
		),
		'per_page'    => array(
			'label'   => __( 'Events per page', 'mlab-eventmanager' ),
			'type'    => 'number',
			'default' => 10,
			'min'     => 1,
		),
	);

	return apply_filters( 'mlab_em_settings_fields', $fields );
}

/**
 * Saved settings as an array.
 *
 * @return array<string, mixed>
 */
function mlab_em_saved_options(): array {
	$saved = get_option( MLAB_EM_OPTION, array() );

	return is_array( $saved ) ? $saved : array();
}

/**
 * Returns one setting, falling back to its default.
 *
 * @param string $key Setting key.
 * @return mixed
 */
function mlab_em_get_option( string $key ) {
	$saved = mlab_em_saved_options();
	if ( array_key_exists( $key, $saved ) ) {
		return $saved[ $key ];
	}

	return mlab_em_settings_fields()[ $key ]['default'] ?? null;
}

/**
 * Updates one setting.
 *
 * @param string $key   Setting key.
 * @param mixed  $value New value.
 */
function mlab_em_update_option( string $key, $value ): void {
	update_option( MLAB_EM_OPTION, array( $key => $value ) + mlab_em_saved_options() );
}

/**
 * Sanitizes the settings array on save.
 *
 * @param mixed $input Raw input.
 * @return array<string, mixed>
 */
function mlab_em_sanitize_settings( $input ): array {
	$input = is_array( $input ) ? $input : array();
	$clean = array();

	foreach ( mlab_em_settings_fields() as $key => $field ) {
		$clean[ $key ] = max( $field['min'] ?? 0, absint( $input[ $key ] ?? $field['default'] ) );
	}

	return $clean;
}
