<?php
/**
 * Blueprint availability repository.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

class WP_Plugin_Stamdata_Blueprint_Availability_Repository {

	private function get_table_name() {
		return WP_Plugin_Stamdata_Schema::get_blueprint_availability_table_name();
	}

	public function get_for_blueprint( $blueprint_id, $week_number = null, $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$week_type = null === $week_number ? 'default' : 'exception';
		$sql       = $wpdb->prepare(
			"SELECT * FROM {$this->get_table_name()} WHERE blueprint_id = %d AND week_type = %s AND week_number " . ( null === $week_number ? 'IS NULL' : '= %d' ) . " AND data_version = %s ORDER BY field_id ASC, day_of_week ASC, start_time ASC",
			...( null === $week_number ? array( $blueprint_id, $week_type, $data_version ) : array( $blueprint_id, $week_type, (int) $week_number, $data_version ) )
		);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	public function get_for_blueprint_and_field( $blueprint_id, $field_id, $week_number = null, $data_version = null ) {
		$rows = $this->get_for_blueprint( $blueprint_id, $week_number, $data_version );

		return array_values(
			array_filter(
				$rows,
				function ( $row ) use ( $field_id ) {
					return isset( $row['field_id'] ) && (int) $row['field_id'] === (int) $field_id;
				}
			)
		);
	}

	public function get_for_blueprint_and_day( $blueprint_id, $day_of_week, $week_number = null, $data_version = null ) {
		$rows = $this->get_for_blueprint( $blueprint_id, $week_number, $data_version );

		return array_values(
			array_filter(
				$rows,
				function ( $row ) use ( $day_of_week ) {
					return isset( $row['day_of_week'] ) && (int) $row['day_of_week'] === (int) $day_of_week;
				}
			)
		);
	}

	public function get_by_id( $availability_id, $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->get_table_name()} WHERE id = %d AND data_version = %s LIMIT 1",
			$availability_id,
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
				'blueprint_id' => (int) $data['blueprint_id'],
				'field_id'     => (int) $data['field_id'],
				'week_type'    => $data['week_type'],
				'week_number'  => isset( $data['week_number'] ) ? $data['week_number'] : null,
				'day_of_week'  => (int) $data['day_of_week'],
				'start_time'   => $data['start_time'],
				'end_time'     => $data['end_time'],
				'data_version' => empty( $data['data_version'] ) ? stamdata_get_active_data_version() : $data['data_version'],
				'created_at'   => $timestamp,
				'updated_at'   => $timestamp,
			),
			array( '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'wp_plugin_stamdata_blueprint_availability_create_failed', __( 'Could not create the blueprint availability.', 'wp-plugin-stamdata' ) );
		}

		return (int) $wpdb->insert_id;
	}

	public function update( $availability_id, array $data ) {
		global $wpdb;

		$updated = $wpdb->update(
			$this->get_table_name(),
			array(
				'blueprint_id' => (int) $data['blueprint_id'],
				'field_id'     => (int) $data['field_id'],
				'week_type'    => $data['week_type'],
				'week_number'  => isset( $data['week_number'] ) ? $data['week_number'] : null,
				'day_of_week'  => (int) $data['day_of_week'],
				'start_time'   => $data['start_time'],
				'end_time'     => $data['end_time'],
				'data_version' => empty( $data['data_version'] ) ? stamdata_get_active_data_version() : $data['data_version'],
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'id' => $availability_id ),
			array( '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'wp_plugin_stamdata_blueprint_availability_update_failed', __( 'Could not update the blueprint availability.', 'wp-plugin-stamdata' ) );
		}

		return true;
	}

	public function delete( $availability_id ) {
		global $wpdb;

		$deleted = $wpdb->delete( $this->get_table_name(), array( 'id' => $availability_id ), array( '%d' ) );

		if ( false === $deleted ) {
			return new WP_Error( 'wp_plugin_stamdata_blueprint_availability_delete_failed', __( 'Could not delete the blueprint availability.', 'wp-plugin-stamdata' ) );
		}

		return true;
	}

	public function sync_for_blueprint( $blueprint_id, array $rows, $week_type, $week_number = null, $data_version = null ) {
		global $wpdb;

		if ( null === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$delete_where = array(
			'blueprint_id' => $blueprint_id,
			'week_type'    => $week_type,
			'data_version' => $data_version,
		);

		$delete_format = array( '%d', '%s', '%s' );

		if ( null === $week_number ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$this->get_table_name()} WHERE blueprint_id = %d AND week_type = %s AND week_number IS NULL AND data_version = %s",
					$blueprint_id,
					$week_type,
					$data_version
				)
			);
		} else {
			$delete_where['week_number'] = (int) $week_number;
			$delete_format[]             = '%d';
			$wpdb->delete( $this->get_table_name(), $delete_where, $delete_format );
		}

		$timestamp = current_time( 'mysql' );

		foreach ( $rows as $row ) {
			$wpdb->insert(
				$this->get_table_name(),
				array(
					'blueprint_id' => $blueprint_id,
					'field_id'     => (int) $row['field_id'],
					'week_type'    => $week_type,
					'week_number'  => null === $week_number ? null : (int) $week_number,
					'day_of_week'  => (int) $row['day_of_week'],
					'start_time'   => $row['start_time'],
					'end_time'     => $row['end_time'],
					'data_version' => $data_version,
					'created_at'   => $timestamp,
					'updated_at'   => $timestamp,
				),
				array( '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
			);
		}
	}
}
