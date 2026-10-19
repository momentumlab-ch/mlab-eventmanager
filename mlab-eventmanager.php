<?php
/**
 * Plugin Name:       MomentumLab Event Manager
 * Plugin URI:        https://github.com/momentumlab-ch/mlab-eventmanager
 * Description:       Lightweight event management: create, list and manage events in the backend, on the website and quickly on a phone.
 * Version:           0.3.1
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            MomentumLab
 * Author URI:        https://momentumlab.ch
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mlab-eventmanager
 * Domain Path:       /languages
 * Update URI:        https://github.com/momentumlab-ch/mlab-eventmanager/
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

define( 'MLAB_EM_VERSION', '0.3.1' );
define( 'MLAB_EM_FILE', __FILE__ );
define( 'MLAB_EM_DIR', plugin_dir_path( __FILE__ ) );
define( 'MLAB_EM_URL', plugin_dir_url( __FILE__ ) );

$mlab_em_modules = array(
	'i18n',
	'options',
	'fields',
	'post-types',
	'linked-posts',
	'display',
	'queries',
	'map',
	'geocode',
	'templates',
	'assets',
	'admin-meta-box',
	'admin-list',
	'admin-settings',
	'shortcode-list',
	'single',
	'calendar',
	'schema',
	'duplicate',
	'submit-form',
	'my-events',
	'export',
	'migration',
	'activation',
	'updater',
);

foreach ( $mlab_em_modules as $mlab_em_module ) {
	require_once MLAB_EM_DIR . 'inc/' . $mlab_em_module . '.php';
}

register_activation_hook( __FILE__, 'mlab_em_activate' );
register_deactivation_hook( __FILE__, 'mlab_em_deactivate' );
