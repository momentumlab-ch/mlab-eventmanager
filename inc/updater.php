<?php
/**
 * Updates via GitHub releases (Plugin Update Checker v5).
 *
 * The update source is defined only here. The "Update URI" header in the main file uses the same URL.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

add_action( 'init', 'mlab_em_register_updater' );

/**
 * Registers the update checker. Private repos need the token constant in wp-config.php.
 */
function mlab_em_register_updater(): void {
	$private = false;
	$token   = defined( 'MLAB_EM_GITHUB_TOKEN' ) ? (string) constant( 'MLAB_EM_GITHUB_TOKEN' ) : '';

	if ( $private && '' === $token ) {
		add_action( 'admin_notices', 'mlab_em_missing_token_notice' );
		return;
	}

	$checker = PucFactory::buildUpdateChecker(
		'https://github.com/momentumlab-ch/mlab-eventmanager/',
		dirname( __DIR__ ) . '/mlab-eventmanager.php',
		'mlab-eventmanager'
	);
	$checker->setBranch( 'main' );

	$api = $checker->getVcsApi();
	// 2 = Api::REQUIRE_RELEASE_ASSETS: no update without the release ZIP, never the GitHub source archive.
	$api->enableReleaseAssets( '/^mlab-eventmanager\.zip$/', 2 );
	if ( $private ) {
		$api->setAuthentication( $token );
	}
}

/**
 * Admin-only hint, shown when a private repo has no token constant.
 */
function mlab_em_missing_token_notice(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'Updates are disabled: the constant MLAB_EM_GITHUB_TOKEN is missing in wp-config.php.', 'mlab-eventmanager' )
	);
}
