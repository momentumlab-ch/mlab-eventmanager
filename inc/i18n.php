<?php
/**
 * Translations.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'mlab_em_load_textdomain', 0 );

/**
 * Loads the plugin translations from /languages.
 */
function mlab_em_load_textdomain(): void {
	load_plugin_textdomain( 'mlab-eventmanager', false, dirname( plugin_basename( MLAB_EM_FILE ) ) . '/languages' );
}
