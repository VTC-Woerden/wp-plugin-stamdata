<?php
/**
 * Database schema helpers.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles database schema creation and upgrades.
 */
class WP_Plugin_Stamdata_Schema {

	/**
	 * Return the teams table name.
	 *
	 * @return string
	 */
	public static function get_teams_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'stamdata_teams';
	}

	/**
	 * Create or update plugin tables.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::get_teams_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			image_id bigint(20) unsigned DEFAULT NULL,
			data_version varchar(20) NOT NULL DEFAULT 'live',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug_version (slug, data_version),
			KEY image_id (image_id),
			KEY data_version (data_version)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}
