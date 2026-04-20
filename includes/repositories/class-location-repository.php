<?php
/**
 * Locations repository.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

class WP_Plugin_Stamdata_Location_Repository {

	private function get_table_name() {
		return WP_Plugin_Stamdata_Schema::get_locations_table_name();
	}

	public function get_all( $data_version = '' ) {
		global $wpdb;

		if ( '' === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->get_table_name()} WHERE data_version = %s ORDER BY name ASC",
			$data_version
		);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	public function get_by_id( $location_id, $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->get_table_name()} WHERE id = %d AND data_version = %s LIMIT 1",
			$location_id,
			$data_version
		);

		$result = $wpdb->get_row( $sql, ARRAY_A );

		return $result ? $result : null;
	}

	public function create( array $data ) {
		global $wpdb;

		$timestamp = current_time( 'mysql' );
		$inserted  = $wpdb->insert(
			$this->get_table_name(),
			array(
				'name'         => $data['name'],
				'address'      => empty( $data['address'] ) ? null : $data['address'],
				'city'         => empty( $data['city'] ) ? null : $data['city'],
				'data_version' => empty( $data['data_version'] ) ? stamdata_get_active_data_version() : $data['data_version'],
				'created_at'   => $timestamp,
				'updated_at'   => $timestamp,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'wp_plugin_stamdata_location_create_failed', __( 'Could not create the location.', 'wp-plugin-stamdata' ) );
		}

		return (int) $wpdb->insert_id;
	}

	public function update( $location_id, array $data ) {
		global $wpdb;

		$updated = $wpdb->update(
			$this->get_table_name(),
			array(
				'name'         => $data['name'],
				'address'      => empty( $data['address'] ) ? null : $data['address'],
				'city'         => empty( $data['city'] ) ? null : $data['city'],
				'data_version' => empty( $data['data_version'] ) ? stamdata_get_active_data_version() : $data['data_version'],
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'id' => $location_id ),
			array( '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'wp_plugin_stamdata_location_update_failed', __( 'Could not update the location.', 'wp-plugin-stamdata' ) );
		}

		return true;
	}

	public function delete( $location_id ) {
		global $wpdb;

		$deleted = $wpdb->delete( $this->get_table_name(), array( 'id' => $location_id ), array( '%d' ) );

		if ( false === $deleted ) {
			return new WP_Error( 'wp_plugin_stamdata_location_delete_failed', __( 'Could not delete the location.', 'wp-plugin-stamdata' ) );
		}

		return true;
	}
}
