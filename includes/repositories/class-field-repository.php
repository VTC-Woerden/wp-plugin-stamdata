<?php
/**
 * Fields repository.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

class WP_Plugin_Stamdata_Field_Repository {

	private function get_table_name() {
		return WP_Plugin_Stamdata_Schema::get_fields_table_name();
	}

	public function get_all( $data_version = '' ) {
		global $wpdb;

		if ( '' === $data_version ) {
			$data_version = wp_plugin_stamdata_get_active_data_version();
		}

		$fields_table    = $this->get_table_name();
		$locations_table = WP_Plugin_Stamdata_Schema::get_locations_table_name();
		$sql             = $wpdb->prepare(
			"SELECT f.*, l.name AS location_name
			FROM {$fields_table} f
			LEFT JOIN {$locations_table} l ON l.id = f.location_id AND l.data_version = f.data_version
			WHERE f.data_version = %s
			ORDER BY l.name ASC, f.sort_order ASC, f.name ASC",
			$data_version
		);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	public function get_by_id( $field_id, $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = wp_plugin_stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->get_table_name()} WHERE id = %d AND data_version = %s LIMIT 1",
			$field_id,
			$data_version
		);

		$result = $wpdb->get_row( $sql, ARRAY_A );

		return $result ? $result : null;
	}

	public function get_by_location( $location_id, $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = wp_plugin_stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->get_table_name()} WHERE location_id = %d AND data_version = %s ORDER BY sort_order ASC, name ASC",
			$location_id,
			$data_version
		);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	public function create( array $data ) {
		global $wpdb;

		$timestamp = current_time( 'mysql' );
		$inserted  = $wpdb->insert(
			$this->get_table_name(),
			array(
				'location_id'   => (int) $data['location_id'],
				'name'          => $data['name'],
				'slug'          => $data['slug'],
				'sort_order'    => isset( $data['sort_order'] ) ? (int) $data['sort_order'] : 0,
				'data_version'  => empty( $data['data_version'] ) ? wp_plugin_stamdata_get_active_data_version() : $data['data_version'],
				'created_at'    => $timestamp,
				'updated_at'    => $timestamp,
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'wp_plugin_stamdata_field_create_failed', __( 'Could not create the field.', 'wp-plugin-stamdata' ) );
		}

		return (int) $wpdb->insert_id;
	}

	public function update( $field_id, array $data ) {
		global $wpdb;

		$updated = $wpdb->update(
			$this->get_table_name(),
			array(
				'location_id'   => (int) $data['location_id'],
				'name'          => $data['name'],
				'slug'          => $data['slug'],
				'sort_order'    => isset( $data['sort_order'] ) ? (int) $data['sort_order'] : 0,
				'data_version'  => empty( $data['data_version'] ) ? wp_plugin_stamdata_get_active_data_version() : $data['data_version'],
				'updated_at'    => current_time( 'mysql' ),
			),
			array( 'id' => $field_id ),
			array( '%d', '%s', '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'wp_plugin_stamdata_field_update_failed', __( 'Could not update the field.', 'wp-plugin-stamdata' ) );
		}

		return true;
	}

	public function delete( $field_id ) {
		global $wpdb;

		$deleted = $wpdb->delete( $this->get_table_name(), array( 'id' => $field_id ), array( '%d' ) );

		if ( false === $deleted ) {
			return new WP_Error( 'wp_plugin_stamdata_field_delete_failed', __( 'Could not delete the field.', 'wp-plugin-stamdata' ) );
		}

		return true;
	}

	public function count_by_location( $location_id, $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = wp_plugin_stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->get_table_name()} WHERE location_id = %d AND data_version = %s",
			$location_id,
			$data_version
		);

		return (int) $wpdb->get_var( $sql );
	}
}
