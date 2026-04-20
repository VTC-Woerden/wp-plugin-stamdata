<?php
/**
 * Team importer service.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

/**
 * Imports teams from the Nevobo API.
 */
class WP_Plugin_Stamdata_Team_Importer {

	/**
	 * Teams repository.
	 *
	 * @var WP_Plugin_Stamdata_Team_Repository
	 */
	private $repository;

	/**
	 * Constructor.
	 *
	 * @param WP_Plugin_Stamdata_Team_Repository $repository Repository instance.
	 */
	public function __construct( WP_Plugin_Stamdata_Team_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Import teams for a given data version.
	 *
	 * @param string $data_version Target dataset.
	 * @return array|\WP_Error
	 */
	public function import_nevobo_teams( $data_version ) {
		$request_url = add_query_arg(
			array(
				'vereniging' => stamdata_get_nevobo_vereniging_path(),
				'limit'      => stamdata_get_nevobo_teams_limit(),
			),
			stamdata_get_nevobo_teams_endpoint()
		);

		$response = wp_remote_get(
			$request_url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Accept' => 'application/ld+json, application/json;q=0.9',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( 200 > $status_code || 300 <= $status_code ) {
			return new WP_Error(
				'wp_plugin_stamdata_team_import_http_error',
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'The Nevobo API request failed with status code %d.', 'wp-plugin-stamdata' ),
					$status_code
				)
			);
		}

		$payload = json_decode( $body, true );

		if ( ! is_array( $payload ) ) {
			return new WP_Error(
				'wp_plugin_stamdata_team_import_invalid_json',
				__( 'The Nevobo API response could not be parsed as JSON.', 'wp-plugin-stamdata' )
			);
		}

		$items = $this->extract_team_items( $payload );

		if ( empty( $items ) ) {
			return new WP_Error(
				'wp_plugin_stamdata_team_import_empty',
				__( 'The Nevobo API response did not contain any teams.', 'wp-plugin-stamdata' )
			);
		}

		$results = array(
			'created'      => 0,
			'updated'      => 0,
			'skipped'      => 0,
			'request_url'  => $request_url,
			'data_version' => $data_version,
			'total'        => count( $items ),
		);

		foreach ( $items as $item ) {
			$mapped_team = $this->map_team( $item, $data_version );

			if ( empty( $mapped_team['external_id'] ) || empty( $mapped_team['name'] ) ) {
				++$results['skipped'];
				continue;
			}

			$import_result = $this->repository->upsert_imported_team( $mapped_team );

			if ( is_wp_error( $import_result ) ) {
				++$results['skipped'];
				continue;
			}

			if ( 'created' === $import_result ) {
				++$results['created'];
			} else {
				++$results['updated'];
			}
		}

		update_option( 'wp_plugin_stamdata_nevobo_last_team_import_at', current_time( 'mysql' ) );
		update_option( 'wp_plugin_stamdata_nevobo_last_team_import_dataset', $data_version );
		update_option(
			'wp_plugin_stamdata_nevobo_last_team_import_summary',
			array(
				'created' => $results['created'],
				'updated' => $results['updated'],
				'skipped' => $results['skipped'],
				'total'   => $results['total'],
			)
		);

		return $results;
	}

	/**
	 * Extract team items from a Hydra or plain JSON response.
	 *
	 * @param array $payload Decoded response payload.
	 * @return array
	 */
	private function extract_team_items( array $payload ) {
		if ( isset( $payload['hydra:member'] ) && is_array( $payload['hydra:member'] ) ) {
			return $payload['hydra:member'];
		}

		if ( isset( $payload['member'] ) && is_array( $payload['member'] ) ) {
			return $payload['member'];
		}

		if ( wp_is_numeric_array( $payload ) ) {
			return $payload;
		}

		return array();
	}

	/**
	 * Map one remote team to local schema fields.
	 *
	 * @param array  $item         Remote item.
	 * @param string $data_version Target dataset.
	 * @return array
	 */
	private function map_team( array $item, $data_version ) {
		$name            = $this->first_string_value( $item, array( 'naam', 'teamNaam', 'name', 'label' ) );
		$external_id     = $this->extract_external_id( $item );
		$external_api_id = $this->extract_external_api_id( $item );
		$sortable_rank   = $this->first_string_value( $item, array( 'sortableRank', 'sortable_rank' ) );

		return array(
			'name'            => $name,
			'short_name'      => $this->extract_short_name( $name ),
			'sortable_rank'   => $sortable_rank,
			'slug'            => $this->build_slug( $item, $name, $external_id, $data_version ),
			'image_id'        => 0,
			'data_version'    => $data_version,
			'external_source' => 'nevobo',
			'external_id'     => $external_id,
			'external_api_id' => $external_api_id,
		);
	}

	/**
	 * Extract a stable external ID from the payload.
	 *
	 * @param array $item Remote team payload.
	 * @return string
	 */
	private function extract_external_id( array $item ) {
		$value = $this->first_string_value( $item, array( 'uuid', '@id', 'id', 'code' ) );

		if ( '' === $value ) {
			return '';
		}

		if ( false !== strpos( $value, '/' ) ) {
			$value = trim( strtolower( $value ), '/' );
			$value = str_replace( '/', '--', $value );
		}

		return sanitize_key( strtolower( $value ) );
	}

	/**
	 * Extract the raw remote @id value for auditing and debugging.
	 *
	 * @param array $item Remote team payload.
	 * @return string
	 */
	private function extract_external_api_id( array $item ) {
		return $this->first_string_value( $item, array( '@id' ) );
	}

	/**
	 * Derive the short team name from the full team name.
	 *
	 * @param string $name Full team name.
	 * @return string
	 */
	private function extract_short_name( $name ) {
		$name = trim( $name );

		if ( '' === $name ) {
			return '';
		}

		if ( strlen( $name ) <= 4 ) {
			return $name;
		}

		return substr( $name, -4 );
	}

	/**
	 * Build a stable slug for imported teams.
	 *
	 * @param array  $item         Remote team payload.
	 * @param string $name         Team name.
	 * @param string $external_id  Remote team ID.
	 * @param string $data_version Target dataset.
	 * @return string
	 */
	private function build_slug( array $item, $name, $external_id, $data_version ) {
		$raw_slug = $this->first_string_value( $item, array( 'slug' ) );
		$slug     = '' !== $raw_slug ? sanitize_title( $raw_slug ) : sanitize_title( $name );

		if ( '' === $slug && '' !== $external_id ) {
			$slug = 'team-' . sanitize_title( $external_id );
		}

		$existing = $this->repository->get_by_slug( $slug, $data_version );

		if ( ! $existing || ( isset( $existing['external_id'] ) && $existing['external_id'] === $external_id ) ) {
			return $slug;
		}

		return $slug . '-' . sanitize_title( $external_id );
	}

	/**
	 * Return the first non-empty string value from a list of keys.
	 *
	 * @param array $item Remote payload.
	 * @param array $keys Keys to inspect.
	 * @return string
	 */
	private function first_string_value( array $item, array $keys ) {
		foreach ( $keys as $key ) {
			if ( ! isset( $item[ $key ] ) ) {
				continue;
			}

			if ( is_scalar( $item[ $key ] ) ) {
				$value = sanitize_text_field( (string) $item[ $key ] );

				if ( '' !== $value ) {
					return $value;
				}
			}
		}

		return '';
	}
}
