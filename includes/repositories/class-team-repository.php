<?php
/**
 * Teams repository.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides all persistence logic for teams.
 */
class WP_Plugin_Stamdata_Team_Repository {

	/**
	 * Return the table name.
	 *
	 * @return string
	 */
	private function get_table_name() {
		return WP_Plugin_Stamdata_Schema::get_teams_table_name();
	}

	/**
	 * Fetch all teams.
	 *
	 * @param string $data_version Optional data version filter.
	 * @return array
	 */
	public function get_all( $data_version = '' ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		if ( '' === $data_version ) {
			$data_version = wp_plugin_stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE data_version = %s ORDER BY name ASC",
			$data_version
		);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Fetch a team by ID.
	 *
	 * @param int         $team_id       Team ID.
	 * @param string|null $data_version  Optional data version filter.
	 * @return array|null
	 */
	public function get_by_id( $team_id, $data_version = null ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		if ( null === $data_version ) {
			$data_version = wp_plugin_stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE id = %d AND data_version = %s LIMIT 1",
			$team_id,
			$data_version
		);

		$result = $wpdb->get_row( $sql, ARRAY_A );

		return $result ? $result : null;
	}

	/**
	 * Insert a new team.
	 *
	 * @param array $data Team data.
	 * @return int|\WP_Error
	 */
	public function create( array $data ) {
		global $wpdb;

		$table_name = $this->get_table_name();
		$timestamp  = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'name'         => $data['name'],
				'slug'         => $data['slug'],
				'image_id'     => empty( $data['image_id'] ) ? null : (int) $data['image_id'],
				'data_version' => empty( $data['data_version'] ) ? wp_plugin_stamdata_get_active_data_version() : $data['data_version'],
				'created_at'   => $timestamp,
				'updated_at'   => $timestamp,
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'wp_plugin_stamdata_team_create_failed', __( 'Could not create the team.', 'wp-plugin-stamdata' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update an existing team.
	 *
	 * @param int   $team_id Team ID.
	 * @param array $data    Team data.
	 * @return bool|\WP_Error
	 */
	public function update( $team_id, array $data ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		$updated = $wpdb->update(
			$table_name,
			array(
				'name'         => $data['name'],
				'slug'         => $data['slug'],
				'image_id'     => empty( $data['image_id'] ) ? null : (int) $data['image_id'],
				'data_version' => empty( $data['data_version'] ) ? wp_plugin_stamdata_get_active_data_version() : $data['data_version'],
				'updated_at'   => current_time( 'mysql' ),
			),
			array(
				'id' => $team_id,
			),
			array( '%s', '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'wp_plugin_stamdata_team_update_failed', __( 'Could not update the team.', 'wp-plugin-stamdata' ) );
		}

		return true;
	}

	/**
	 * Delete a team.
	 *
	 * @param int $team_id Team ID.
	 * @return bool|\WP_Error
	 */
	public function delete( $team_id ) {
		global $wpdb;

		$deleted = $wpdb->delete(
			$this->get_table_name(),
			array( 'id' => $team_id ),
			array( '%d' )
		);

		if ( false === $deleted ) {
			return new WP_Error( 'wp_plugin_stamdata_team_delete_failed', __( 'Could not delete the team.', 'wp-plugin-stamdata' ) );
		}

		return true;
	}
}
