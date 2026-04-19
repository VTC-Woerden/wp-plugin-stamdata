<?php
/**
 * Plugin activation logic.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles activation-time setup.
 */
class WP_Plugin_Stamdata_Installer {

	/**
	 * Run plugin activation tasks.
	 *
	 * @return void
	 */
	public static function activate() {
		WP_Plugin_Stamdata_Schema::create_tables();
		update_option( 'wp_plugin_stamdata_db_version', WP_PLUGIN_STAMDATA_DB_VERSION );
		add_option( 'wp_plugin_stamdata_active_data_version', 'live' );
	}
}
