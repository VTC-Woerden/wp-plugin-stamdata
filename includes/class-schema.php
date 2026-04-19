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
	 * Return the locations table name.
	 *
	 * @return string
	 */
	public static function get_locations_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'stamdata_locations';
	}

	/**
	 * Return the fields table name.
	 *
	 * @return string
	 */
	public static function get_fields_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'stamdata_fields';
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

		$locations_table = self::get_locations_table_name();
		$locations_sql   = "CREATE TABLE {$locations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			address varchar(191) DEFAULT NULL,
			city varchar(191) DEFAULT NULL,
			data_version varchar(20) NOT NULL DEFAULT 'live',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug_version (slug, data_version),
			KEY data_version (data_version)
		) {$charset_collate};";

		dbDelta( $locations_sql );

		$fields_table = self::get_fields_table_name();
		$fields_sql   = "CREATE TABLE {$fields_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			location_id bigint(20) unsigned NOT NULL,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			sort_order int(11) NOT NULL DEFAULT 0,
			data_version varchar(20) NOT NULL DEFAULT 'live',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug_version (slug, data_version),
			KEY location_id (location_id),
			KEY data_version (data_version)
		) {$charset_collate};";

		dbDelta( $fields_sql );
	}
}
