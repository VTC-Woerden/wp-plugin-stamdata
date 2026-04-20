<?php
/**
 * Plugin Name: WP Plugin Stamdata
 * Plugin URI:  https://example.com/
 * Description: Central master-data plugin for sports-related site data.
 * Version:     0.1.0
 * Author:      Thierry Rietveld
 * Text Domain: wp-plugin-stamdata
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

define( 'WP_PLUGIN_STAMDATA_VERSION', '0.1.0' );
define( 'WP_PLUGIN_STAMDATA_DB_VERSION', '11' );
define( 'WP_PLUGIN_STAMDATA_FILE', __FILE__ );
define( 'WP_PLUGIN_STAMDATA_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_PLUGIN_STAMDATA_URL', plugin_dir_url( __FILE__ ) );

require_once WP_PLUGIN_STAMDATA_PATH . 'includes/class-schema.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/class-installer.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/repositories/class-team-repository.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/repositories/class-location-repository.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/repositories/class-field-repository.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/repositories/class-blueprint-repository.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/repositories/class-blueprint-availability-repository.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/services/class-team-importer.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/admin/class-settings-admin-page.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/admin/teams/class-team-admin-page.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/admin/locations/class-location-admin-page.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/admin/fields/class-field-admin-page.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/admin/blueprints/class-blueprint-admin-page.php';
require_once WP_PLUGIN_STAMDATA_PATH . 'includes/class-plugin.php';

register_activation_hook( WP_PLUGIN_STAMDATA_FILE, array( 'WP_Plugin_Stamdata_Installer', 'activate' ) );

/**
 * Bootstrap the plugin runtime.
 *
 * @return WP_Plugin_Stamdata_Plugin
 */
function wp_plugin_stamdata() {
	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new WP_Plugin_Stamdata_Plugin();
	}

	return $plugin;
}

wp_plugin_stamdata();

/**
 * Public helper to fetch a team by ID.
 *
 * @param int         $team_id       Team ID.
 * @param string|null $data_version  Optional data version override.
 * @return array|null
 */
function stamdata_get_team( $team_id, $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Team_Repository();

	return $repository->get_by_id( (int) $team_id, $data_version );
}

/**
 * Return all teams for a dataset.
 *
 * @param string|null $data_version Optional data version override.
 * @return array
 */
function stamdata_get_teams( $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Team_Repository();

	return $repository->get_all( null === $data_version ? '' : $data_version );
}

/**
 * Return a single location by ID.
 *
 * @param int         $location_id  Location ID.
 * @param string|null $data_version Optional data version override.
 * @return array|null
 */
function stamdata_get_location( $location_id, $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Location_Repository();

	return $repository->get_by_id( (int) $location_id, $data_version );
}

/**
 * Return all locations for a dataset.
 *
 * @param string|null $data_version Optional data version override.
 * @return array
 */
function stamdata_get_locations( $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Location_Repository();

	return $repository->get_all( null === $data_version ? '' : $data_version );
}

/**
 * Return a single field by ID.
 *
 * @param int         $field_id     Field ID.
 * @param string|null $data_version Optional data version override.
 * @return array|null
 */
function stamdata_get_field( $field_id, $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Field_Repository();

	return $repository->get_by_id( (int) $field_id, $data_version );
}

/**
 * Return all fields for a dataset.
 *
 * @param string|null $data_version Optional data version override.
 * @return array
 */
function stamdata_get_fields( $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Field_Repository();

	return $repository->get_all( null === $data_version ? '' : $data_version );
}

/**
 * Return all fields that belong to a location.
 *
 * @param int         $location_id  Location ID.
 * @param string|null $data_version Optional data version override.
 * @return array
 */
function stamdata_get_fields_by_location( $location_id, $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Field_Repository();

	return $repository->get_by_location( (int) $location_id, $data_version );
}

/**
 * Return blueprint availability rows for the default week or an exception week.
 *
 * @param int         $blueprint_id Blueprint ID.
 * @param int|null    $week_number  Optional exception week number (1-53).
 * @param string|null $data_version Optional data version override.
 * @return array
 */
function stamdata_get_blueprint_availability( $blueprint_id, $week_number = null, $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Blueprint_Availability_Repository();

	return $repository->get_for_blueprint( (int) $blueprint_id, $week_number, $data_version );
}

/**
 * Return blueprint availability rows for a specific field.
 *
 * @param int         $blueprint_id Blueprint ID.
 * @param int         $field_id     Field ID.
 * @param int|null    $week_number  Optional exception week number (1-53).
 * @param string|null $data_version Optional data version override.
 * @return array
 */
function stamdata_get_blueprint_availability_for_field( $blueprint_id, $field_id, $week_number = null, $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Blueprint_Availability_Repository();

	return $repository->get_for_blueprint_and_field( (int) $blueprint_id, (int) $field_id, $week_number, $data_version );
}

/**
 * Return blueprint timeslots for a given week number and weekday.
 *
 * Day number uses 0 = Monday through 6 = Sunday.
 *
 * @param int         $week_number  Week number (1-53).
 * @param int         $day_number   Weekday number where Monday = 0.
 * @param string|null $data_version Optional data version override.
 * @return array
 */
function stamdata_get_blueprint_timeslots_for_day( $week_number, $day_number, $data_version = null ) {
	$day_number = (int) $day_number;

	if ( $day_number < 0 || $day_number > 6 ) {
		return array();
	}

	$blueprint_repository    = new WP_Plugin_Stamdata_Blueprint_Repository();
	$availability_repository = new WP_Plugin_Stamdata_Blueprint_Availability_Repository();
	$blueprint               = $blueprint_repository->get_effective_for_week( (int) $week_number, $data_version );

	if ( ! $blueprint || empty( $blueprint['id'] ) ) {
		return array();
	}

	$effective_week_number = 'exception' === ( $blueprint['week_type'] ?? 'default' )
		? (int) $blueprint['week_number']
		: null;

	return $availability_repository->get_for_blueprint_and_day(
		(int) $blueprint['id'],
		$day_number + 1,
		$effective_week_number,
		$data_version
	);
}

/**
 * Return a single blueprint by ID.
 *
 * @param int         $blueprint_id  Blueprint ID.
 * @param string|null $data_version  Optional data version override.
 * @return array|null
 */
function stamdata_get_blueprint( $blueprint_id, $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Blueprint_Repository();

	return $repository->get_by_id( (int) $blueprint_id, $data_version );
}

/**
 * Return all blueprints for a dataset.
 *
 * @param string|null $data_version Optional data version override.
 * @return array
 */
function stamdata_get_blueprints( $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Blueprint_Repository();

	return $repository->get_all( null === $data_version ? '' : $data_version );
}

/**
 * Return the effective blueprint for a week, falling back to default.
 *
 * @param int         $week_number  Week number (1-53).
 * @param string|null $data_version Optional data version override.
 * @return array|null
 */
function stamdata_get_blueprint_for_week( $week_number, $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Blueprint_Repository();

	return $repository->get_effective_for_week( (int) $week_number, $data_version );
}

/**
 * Return all location IDs assigned to a blueprint.
 *
 * @param int         $blueprint_id Blueprint ID.
 * @param string|null $data_version Optional data version override.
 * @return array
 */
function stamdata_get_blueprint_location_ids( $blueprint_id, $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Blueprint_Repository();

	return $repository->get_location_ids( (int) $blueprint_id, $data_version );
}

/**
 * Return all field IDs assigned to a blueprint.
 *
 * @param int         $blueprint_id Blueprint ID.
 * @param string|null $data_version Optional data version override.
 * @return array
 */
function stamdata_get_blueprint_field_ids( $blueprint_id, $data_version = null ) {
	$repository = new WP_Plugin_Stamdata_Blueprint_Repository();

	return $repository->get_field_ids( (int) $blueprint_id, $data_version );
}

/**
 * Return the active data version for the whole plugin.
 *
 * @return string
 */
function stamdata_get_active_data_version() {
	$data_version = get_option( 'wp_plugin_stamdata_active_data_version', 'live' );

	return in_array( $data_version, array( 'live', 'test' ), true ) ? $data_version : 'live';
}

/**
 * Return the stored Nevobo teams endpoint URL.
 *
 * @return string
 */
function stamdata_get_nevobo_teams_endpoint() {
	$default = 'https://api.nevobo.nl/competitie/teams.jsonld';
	$value   = get_option( 'wp_plugin_stamdata_nevobo_teams_endpoint', $default );

	return is_string( $value ) && '' !== $value ? esc_url_raw( $value ) : $default;
}

/**
 * Return the stored Nevobo vereniging path.
 *
 * @return string
 */
function stamdata_get_nevobo_vereniging_path() {
	$default = '/relatiebeheer/verenigingen/ckl9x7n';
	$value   = get_option( 'wp_plugin_stamdata_nevobo_vereniging_path', $default );

	return is_string( $value ) && '' !== $value ? sanitize_text_field( $value ) : $default;
}

/**
 * Return the stored Nevobo request limit.
 *
 * @return int
 */
function stamdata_get_nevobo_teams_limit() {
	$value = (int) get_option( 'wp_plugin_stamdata_nevobo_teams_limit', 100 );

	return $value > 0 ? $value : 100;
}
