<?php
/**
 * Template loader. Themes override a template by copying it to
 * <theme>/mlab-eventmanager/<name>.php.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Finds a template, theme first, then plugin.
 *
 * @param string $name Template name without extension.
 */
function mlab_em_locate_template( string $name ): string {
	$theme_file = locate_template( 'mlab-eventmanager/' . $name . '.php' );
	$path       = $theme_file ? $theme_file : MLAB_EM_DIR . 'templates/' . $name . '.php';

	return (string) apply_filters( 'mlab_em_template_path', $path, $name );
}

/**
 * Renders a template and returns its output. Data is available as $args.
 *
 * @param string               $name Template name without extension.
 * @param array<string, mixed> $args Data for the template.
 */
function mlab_em_get_template( string $name, array $args = array() ): string {
	$path = mlab_em_locate_template( $name );
	if ( ! is_readable( $path ) ) {
		return '';
	}

	ob_start();
	load_template( $path, false, $args );
	return (string) ob_get_clean();
}
