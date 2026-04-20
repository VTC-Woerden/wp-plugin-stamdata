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
		add_option( 'wp_plugin_stamdata_nevobo_teams_endpoint', 'https://api.nevobo.nl/competitie/teams.jsonld' );
		add_option( 'wp_plugin_stamdata_nevobo_vereniging_path', '/relatiebeheer/verenigingen/ckl9x7n' );
		add_option( 'wp_plugin_stamdata_nevobo_teams_limit', 100 );
		add_option( 'wp_plugin_stamdata_nevobo_last_team_import_at', '' );
		add_option( 'wp_plugin_stamdata_nevobo_last_team_import_dataset', '' );
		add_option( 'wp_plugin_stamdata_nevobo_last_team_import_summary', array() );
	}
}
