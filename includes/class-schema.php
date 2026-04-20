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
	 * Return the blueprint availability table name.
	 *
	 * @return string
	 */
	public static function get_blueprint_availability_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'stamdata_blueprint_availability';
	}

	/**
	 * Return the blueprints table name.
	 *
	 * @return string
	 */
	public static function get_blueprints_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'stamdata_blueprints';
	}

	/**
	 * Return the blueprint locations table name.
	 *
	 * @return string
	 */
	public static function get_blueprint_locations_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'stamdata_blueprint_locations';
	}

	/**
	 * Return the blueprint fields table name.
	 *
	 * @return string
	 */
	public static function get_blueprint_fields_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'stamdata_blueprint_fields';
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
			short_name varchar(20) DEFAULT NULL,
			sortable_rank varchar(20) DEFAULT NULL,
			image_id bigint(20) unsigned DEFAULT NULL,
			external_source varchar(50) DEFAULT NULL,
			external_id varchar(191) DEFAULT NULL,
			external_api_id varchar(191) DEFAULT NULL,
			data_version varchar(20) NOT NULL DEFAULT 'live',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY external_identifier (external_source, external_id, data_version),
			KEY short_name (short_name),
			KEY sortable_rank (sortable_rank),
			KEY image_id (image_id),
			KEY external_lookup (external_source, data_version),
			KEY external_api_lookup (external_source, external_api_id, data_version),
			KEY data_version (data_version)
		) {$charset_collate};";

		dbDelta( $sql );

		$locations_table = self::get_locations_table_name();
		$locations_sql   = "CREATE TABLE {$locations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			address varchar(191) DEFAULT NULL,
			city varchar(191) DEFAULT NULL,
			data_version varchar(20) NOT NULL DEFAULT 'live',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY data_version (data_version)
		) {$charset_collate};";

		dbDelta( $locations_sql );

		$fields_table = self::get_fields_table_name();
		$fields_sql   = "CREATE TABLE {$fields_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			location_id bigint(20) unsigned NOT NULL,
			name varchar(191) NOT NULL,
			sort_order int(11) NOT NULL DEFAULT 0,
			data_version varchar(20) NOT NULL DEFAULT 'live',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY location_id (location_id),
			KEY data_version (data_version)
		) {$charset_collate};";

		dbDelta( $fields_sql );

		$availability_table = self::get_blueprint_availability_table_name();
		$availability_sql   = "CREATE TABLE {$availability_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			blueprint_id bigint(20) unsigned NOT NULL,
			field_id bigint(20) unsigned NOT NULL DEFAULT 0,
			week_type varchar(20) NOT NULL DEFAULT 'default',
			week_number tinyint(3) unsigned DEFAULT NULL,
			day_of_week tinyint(1) unsigned NOT NULL,
			start_time time NOT NULL,
			end_time time NOT NULL,
			data_version varchar(20) NOT NULL DEFAULT 'live',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY blueprint_id (blueprint_id),
			KEY field_id (field_id),
			KEY week_lookup (blueprint_id, field_id, week_type, week_number, data_version),
			KEY data_version (data_version)
		) {$charset_collate};";

		dbDelta( $availability_sql );

		$blueprints_table = self::get_blueprints_table_name();
		$blueprints_sql   = "CREATE TABLE {$blueprints_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			week_type varchar(20) NOT NULL DEFAULT 'default',
			week_number tinyint(3) unsigned NOT NULL DEFAULT 0,
			notes text DEFAULT NULL,
			data_version varchar(20) NOT NULL DEFAULT 'live',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY week_scope (week_type, week_number, data_version),
			KEY data_version (data_version)
		) {$charset_collate};";

		dbDelta( $blueprints_sql );

		self::drop_legacy_slug_columns();

		$blueprint_locations_table = self::get_blueprint_locations_table_name();
		$blueprint_locations_sql   = "CREATE TABLE {$blueprint_locations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			blueprint_id bigint(20) unsigned NOT NULL,
			location_id bigint(20) unsigned NOT NULL,
			data_version varchar(20) NOT NULL DEFAULT 'live',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY blueprint_location (blueprint_id, location_id, data_version),
			KEY blueprint_id (blueprint_id),
			KEY location_id (location_id),
			KEY data_version (data_version)
		) {$charset_collate};";

		dbDelta( $blueprint_locations_sql );

		$blueprint_fields_table = self::get_blueprint_fields_table_name();
		$blueprint_fields_sql   = "CREATE TABLE {$blueprint_fields_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			blueprint_id bigint(20) unsigned NOT NULL,
			field_id bigint(20) unsigned NOT NULL,
			data_version varchar(20) NOT NULL DEFAULT 'live',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY blueprint_field (blueprint_id, field_id, data_version),
			KEY blueprint_id (blueprint_id),
			KEY field_id (field_id),
			KEY data_version (data_version)
		) {$charset_collate};";

		dbDelta( $blueprint_fields_sql );
	}

	/**
	 * Remove deprecated slug columns and indexes from current tables.
	 *
	 * @return void
	 */
	private static function drop_legacy_slug_columns() {
		global $wpdb;

		$tables = array(
			self::get_teams_table_name(),
			self::get_locations_table_name(),
			self::get_fields_table_name(),
			self::get_blueprints_table_name(),
		);

		foreach ( $tables as $table_name ) {
			$slug_column_exists = $wpdb->get_var(
				$wpdb->prepare(
					"SHOW COLUMNS FROM {$table_name} LIKE %s",
					'slug'
				)
			);

			if ( $slug_column_exists ) {
				$wpdb->query( "ALTER TABLE {$table_name} DROP INDEX slug_version" );
				$wpdb->query( "ALTER TABLE {$table_name} DROP COLUMN slug" );
			}
		}
	}
}
