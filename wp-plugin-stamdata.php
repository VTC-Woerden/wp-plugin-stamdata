<?php
/**
 * Plugin Name: WP Plugin Stamdata
 * Plugin URI:  https://example.com/
 * Description: Central master-data plugin for sports-related site data.
 * Version:     0.1.0
 * Author:      Thierry Rietveld
 * Text Domain: wp-plugin-stamdata
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

define( 'WP_PLUGIN_STAMDATA_VERSION', '0.1.0' );
define( 'WP_PLUGIN_STAMDATA_DB_VERSION', '3' );
define( 'WP_PLUGIN_STAMDATA_FILE', __FILE__ );
define( 'WP_PLUGIN_STAMDATA_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_PLUGIN_STAMDATA_URL', plugin_dir_url( __FILE__ ) );

require_once WP_PLUGIN_STAMDATA_PATH . 'includes/class-schema.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/class-installer.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/repositories/class-team-repository.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/repositories/class-location-repository.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/repositories/class-field-repository.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/admin/class-settings-admin-page.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/admin/teams/class-team-admin-page.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/admin/locations/class-location-admin-page.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/admin/fields/class-field-admin-page.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/class-plugin.php';

register_activation_hook( WP_PLUGIN_STAMDATA_FILE, array( 'WP_Plugin_Stamdata_Installer', 'activate' ) );

/**
 * Bootstrap the plugin runtime.
 *
 * @return WP_Plugin_Stamdata_Plugin
 */
function wp_plugin_stamdata() {
	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new WP_Plugin_Stamdata_Plugin();
	}

	return $plugin;
}

wp_plugin_stamdata();

/**
 * Public helper to fetch a team by ID.
 *
 * @param int         $team_id       Team ID.
 * @param string|null $data_version  Optional data version override.
 * @return array|null
 */
function wp_plugin_stamdata_get_team( $team_id, $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Team_Repository();

	return $repository->get_by_id( (int) $team_id, $data_version );
}

/**
 * Return the active data version for the whole plugin.
 *
 * @return string
 */
function wp_plugin_stamdata_get_active_data_version() {
	$data_version = get_option( 'wp_plugin_stamdata_active_data_version', 'live' );

	return in_array( $data_version, array( 'live', 'test' ), true ) ? $data_version : 'live';
}
