<?php
/**
 * Main plugin runtime.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bootstraps plugin services.
 */
class WP_Plugin_Stamdata_Plugin {

	/**
	 * Settings admin page.
	 *
	 * @var WP_Plugin_Stamdata_Settings_Admin_Page
	 */
	private $settings_admin_page;

	/**
	 * Teams admin page.
	 *
	 * @var WP_Plugin_Stamdata_Team_Admin_Page
	 */
	private $team_admin_page;

	/**
	 * Locations admin page.
	 *
	 * @var WP_Plugin_Stamdata_Location_Admin_Page
	 */
	private $location_admin_page;

	/**
	 * Fields admin page.
	 *
	 * @var WP_Plugin_Stamdata_Field_Admin_Page
	 */
	private $field_admin_page;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings_admin_page = new WP_Plugin_Stamdata_Settings_Admin_Page();
		$this->team_admin_page = new WP_Plugin_Stamdata_Team_Admin_Page( new WP_Plugin_Stamdata_Team_Repository() );
		$this->location_admin_page = new WP_Plugin_Stamdata_Location_Admin_Page( new WP_Plugin_Stamdata_Location_Repository() );
		$this->field_admin_page = new WP_Plugin_Stamdata_Field_Admin_Page( new WP_Plugin_Stamdata_Field_Repository(), new WP_Plugin_Stamdata_Location_Repository() );

		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade_schema' ) );
		add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Ensure schema is up to date.
	 *
	 * @return void
	 */
	public function maybe_upgrade_schema() {
		$current_version = get_option( 'wp_plugin_stamdata_db_version' );

		if ( WP_PLUGIN_STAMDATA_DB_VERSION !== (string) $current_version ) {
			WP_Plugin_Stamdata_Schema::create_tables();
			update_option( 'wp_plugin_stamdata_db_version', WP_PLUGIN_STAMDATA_DB_VERSION );
		}
	}

	/**
	 * Register admin menu items.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		$this->settings_admin_page->register_menu();
		$this->team_admin_page->register_menu();
		$this->location_admin_page->register_menu();
		$this->field_admin_page->register_menu();
	}

	/**
	 * Handle admin form actions before page output starts.
	 *
	 * @return void
	 */
	public function handle_admin_actions() {
		$this->settings_admin_page->handle_request();
		$this->team_admin_page->handle_request();
		$this->location_admin_page->handle_request();
		$this->field_admin_page->handle_request();
	}

	/**
	 * Enqueue admin assets for plugin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		$this->team_admin_page->enqueue_assets( $hook_suffix );
		$this->location_admin_page->enqueue_assets( $hook_suffix );
		$this->field_admin_page->enqueue_assets( $hook_suffix );
	}
}
