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
			$data_version = stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE data_version = %s ORDER BY sortable_rank ASC, name ASC",
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
			$data_version = stamdata_get_active_data_version();
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
	 * Fetch a team by slug.
	 *
	 * @param string      $slug         Team slug.
	 * @param string|null $data_version Optional data version filter.
	 * @return array|null
	 */
	public function get_by_slug( $slug, $data_version = null ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		if ( null === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE slug = %s AND data_version = %s LIMIT 1",
			$slug,
			$data_version
		);

		$result = $wpdb->get_row( $sql, ARRAY_A );

		return $result ? $result : null;
	}

	/**
	 * Fetch a team by external ID.
	 *
	 * @param string      $external_id     External team ID.
	 * @param string|null $data_version    Optional data version filter.
	 * @param string      $external_source Source identifier.
	 * @return array|null
	 */
	public function get_by_external_id( $external_id, $data_version = null, $external_source = 'nevobo' ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		if ( null === $data_version ) {
			$data_version = stamdata_get_active_data_version();
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE external_source = %s AND external_id = %s AND data_version = %s LIMIT 1",
			$external_source,
			$external_id,
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
		$short_name = $this->resolve_short_name( $data );

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'name'            => $data['name'],
				'short_name'      => $short_name,
				'sortable_rank'   => empty( $data['sortable_rank'] ) ? null : sanitize_text_field( $data['sortable_rank'] ),
				'slug'            => $data['slug'],
				'image_id'        => empty( $data['image_id'] ) ? null : (int) $data['image_id'],
				'external_source' => empty( $data['external_source'] ) ? null : $data['external_source'],
				'external_id'     => empty( $data['external_id'] ) ? null : $data['external_id'],
				'external_api_id' => empty( $data['external_api_id'] ) ? null : $data['external_api_id'],
				'data_version'    => empty( $data['data_version'] ) ? stamdata_get_active_data_version() : $data['data_version'],
				'created_at'      => $timestamp,
				'updated_at'      => $timestamp,
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
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
		$short_name = $this->resolve_short_name( $data );

		$updated = $wpdb->update(
			$table_name,
			array(
				'name'            => $data['name'],
				'short_name'      => $short_name,
				'sortable_rank'   => empty( $data['sortable_rank'] ) ? null : sanitize_text_field( $data['sortable_rank'] ),
				'slug'            => $data['slug'],
				'image_id'        => empty( $data['image_id'] ) ? null : (int) $data['image_id'],
				'external_source' => empty( $data['external_source'] ) ? null : $data['external_source'],
				'external_id'     => empty( $data['external_id'] ) ? null : $data['external_id'],
				'external_api_id' => empty( $data['external_api_id'] ) ? null : $data['external_api_id'],
				'data_version'    => empty( $data['data_version'] ) ? stamdata_get_active_data_version() : $data['data_version'],
				'updated_at'      => current_time( 'mysql' ),
			),
			array(
				'id' => $team_id,
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ),
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

	/**
	 * Upsert an imported team using its external identity.
	 *
	 * @param array $data Imported team data.
	 * @return string|\WP_Error
	 */
	public function upsert_imported_team( array $data ) {
		$existing = $this->get_by_external_id(
			$data['external_id'],
			$data['data_version'],
			empty( $data['external_source'] ) ? 'nevobo' : $data['external_source']
		);

		if ( $existing ) {
			$result = $this->update(
				(int) $existing['id'],
				array(
					'name'            => $data['name'],
					'short_name'      => empty( $data['short_name'] ) ? '' : $data['short_name'],
					'sortable_rank'   => empty( $data['sortable_rank'] ) ? '' : $data['sortable_rank'],
					'slug'            => $data['slug'],
					'image_id'        => isset( $existing['image_id'] ) ? (int) $existing['image_id'] : 0,
					'data_version'    => $data['data_version'],
					'external_source' => empty( $data['external_source'] ) ? 'nevobo' : $data['external_source'],
					'external_id'     => $data['external_id'],
					'external_api_id' => empty( $data['external_api_id'] ) ? null : $data['external_api_id'],
				)
			);

			return is_wp_error( $result ) ? $result : 'updated';
		}

		$result = $this->create( $data );

		return is_wp_error( $result ) ? $result : 'created';
	}

	/**
	 * Resolve the short name for a team.
	 *
	 * @param array $data Team data.
	 * @return string|null
	 */
	private function resolve_short_name( array $data ) {
		if ( ! empty( $data['short_name'] ) ) {
			return sanitize_text_field( $data['short_name'] );
		}

		if ( empty( $data['name'] ) ) {
			return null;
		}

		$name = trim( sanitize_text_field( $data['name'] ) );

		if ( '' === $name ) {
			return null;
		}

		if ( strlen( $name ) <= 4 ) {
			return $name;
		}

		return substr( $name, -4 );
	}
}
