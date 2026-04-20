<?php
/**
 * Blueprint repository.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

class WP_Plugin_Stamdata_Blueprint_Repository {

	private function get_table_name() {
		return WP_Plugin_Stamdata_Schema::get_blueprints_table_name();
	}

	private function get_locations_table_name() {
		return WP_Plugin_Stamdata_Schema::get_blueprint_locations_table_name();
	}

	private function get_fields_table_name() {
		return WP_Plugin_Stamdata_Schema::get_blueprint_fields_table_name();
	}

	public function get_all( $data_version = '' ) {
		global $wpdb;

		if ( '' === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->get_table_name()} WHERE data_version = %s ORDER BY week_type ASC, week_number ASC, name ASC",
			$data_version
		);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	public function get_by_id( $blueprint_id, $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->get_table_name()} WHERE id = %d AND data_version = %s LIMIT 1",
			$blueprint_id,
			$data_version
		);

		$result = $wpdb->get_row( $sql, ARRAY_A );

		return $result ? $result : null;
	}

	public function get_default( $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->get_table_name()} WHERE week_type = %s AND week_number = 0 AND data_version = %s LIMIT 1",
			'default',
			$data_version
		);

		$result = $wpdb->get_row( $sql, ARRAY_A );

		return $result ? $result : null;
	}

	public function get_by_week( $week_number, $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->get_table_name()} WHERE week_type = %s AND week_number = %d AND data_version = %s LIMIT 1",
			'exception',
			$week_number,
			$data_version
		);

		$result = $wpdb->get_row( $sql, ARRAY_A );

		return $result ? $result : null;
	}

	public function get_effective_for_week( $week_number, $data_version = null ) {
		$blueprint = $this->get_by_week( $week_number, $data_version );

		if ( $blueprint ) {
			return $blueprint;
		}

		return $this->get_default( $data_version );
	}

	public function get_location_ids( $blueprint_id, $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT location_id FROM {$this->get_locations_table_name()} WHERE blueprint_id = %d AND data_version = %s ORDER BY location_id ASC",
			$blueprint_id,
			$data_version
		);

		return array_map( 'intval', $wpdb->get_col( $sql ) );
	}

	public function get_field_ids( $blueprint_id, $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT field_id FROM {$this->get_fields_table_name()} WHERE blueprint_id = %d AND data_version = %s ORDER BY field_id ASC",
			$blueprint_id,
			$data_version
		);

		return array_map( 'intval', $wpdb->get_col( $sql ) );
	}

	public function create( array $data ) {
		global $wpdb;

		$timestamp = current_time( 'mysql' );
		$inserted  = $wpdb->insert(
			$this->get_table_name(),
			array(
				'name'         => $data['name'],
				'slug'         => $data['slug'],
				'week_type'    => $data['week_type'],
				'week_number'  => isset( $data['week_number'] ) ? (int) $data['week_number'] : 0,
				'notes'        => empty( $data['notes'] ) ? null : $data['notes'],
				'data_version' => empty( $data['data_version'] ) ? stamdata_get_active_data_version() : $data['data_version'],
				'created_at'   => $timestamp,
				'updated_at'   => $timestamp,
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'wp_plugin_stamdata_blueprint_create_failed', __( 'Could not create the blueprint.', 'wp-plugin-stamdata' ) );
		}

		return (int) $wpdb->insert_id;
	}

	public function update( $blueprint_id, array $data ) {
		global $wpdb;

		$updated = $wpdb->update(
			$this->get_table_name(),
			array(
				'name'         => $data['name'],
				'slug'         => $data['slug'],
				'week_type'    => $data['week_type'],
				'week_number'  => isset( $data['week_number'] ) ? (int) $data['week_number'] : 0,
				'notes'        => empty( $data['notes'] ) ? null : $data['notes'],
				'data_version' => empty( $data['data_version'] ) ? stamdata_get_active_data_version() : $data['data_version'],
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'id' => $blueprint_id ),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'wp_plugin_stamdata_blueprint_update_failed', __( 'Could not update the blueprint.', 'wp-plugin-stamdata' ) );
		}

		return true;
	}

	public function delete( $blueprint_id ) {
		global $wpdb;

		$wpdb->delete( $this->get_locations_table_name(), array( 'blueprint_id' => $blueprint_id ), array( '%d' ) );
		$wpdb->delete( $this->get_fields_table_name(), array( 'blueprint_id' => $blueprint_id ), array( '%d' ) );
		$deleted = $wpdb->delete( $this->get_table_name(), array( 'id' => $blueprint_id ), array( '%d' ) );

		if ( false === $deleted ) {
			return new WP_Error( 'wp_plugin_stamdata_blueprint_delete_failed', __( 'Could not delete the blueprint.', 'wp-plugin-stamdata' ) );
		}

		return true;
	}

	public function sync_locations( $blueprint_id, array $location_ids, $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$wpdb->delete(
			$this->get_locations_table_name(),
			array(
				'blueprint_id' => $blueprint_id,
				'data_version' => $data_version,
			),
			array( '%d', '%s' )
		);

		$timestamp = current_time( 'mysql' );

		foreach ( $location_ids as $location_id ) {
			$wpdb->insert(
				$this->get_locations_table_name(),
				array(
					'blueprint_id' => $blueprint_id,
					'location_id'  => (int) $location_id,
					'data_version' => $data_version,
					'created_at'   => $timestamp,
					'updated_at'   => $timestamp,
				),
				array( '%d', '%d', '%s', '%s', '%s' )
			);
		}
	}

	public function sync_fields( $blueprint_id, array $field_ids, $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$wpdb->delete(
			$this->get_fields_table_name(),
			array(
				'blueprint_id' => $blueprint_id,
				'data_version' => $data_version,
			),
			array( '%d', '%s' )
		);

		$timestamp = current_time( 'mysql' );

		foreach ( $field_ids as $field_id ) {
			$wpdb->insert(
				$this->get_fields_table_name(),
				array(
					'blueprint_id' => $blueprint_id,
					'field_id'     => (int) $field_id,
					'data_version' => $data_version,
					'created_at'   => $timestamp,
					'updated_at'   => $timestamp,
				),
				array( '%d', '%d', '%s', '%s', '%s' )
			);
		}
	}
}
