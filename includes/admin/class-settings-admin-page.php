<?php
/**
 * Stamdata settings admin screen.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the global plugin settings page.
 */
class WP_Plugin_Stamdata_Settings_Admin_Page {

	/**
	 * Register the main Stamdata menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Stamdata', 'wp-plugin-stamdata' ),
			__( 'Stamdata', 'wp-plugin-stamdata' ),
			'manage_options',
			'wp-plugin-stamdata',
			array( $this, 'render_page' ),
			'dashicons-database',
			58
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Stamdata settings.', 'wp-plugin-stamdata' ) );
		}

		$active_version = wp_plugin_stamdata_get_active_data_version();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Stamdata Settings', 'wp-plugin-stamdata' ); ?></h1>
			<p><?php esc_html_e( 'Use one master switch to choose which dataset the plugin should use.', 'wp-plugin-stamdata' ); ?></p>

			<?php $this->render_notices(); ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'wp_plugin_stamdata_save_settings', 'wp_plugin_stamdata_settings_nonce' ); ?>
				<input type="hidden" name="stamdata_action" value="save_settings" />
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'Active data version', 'wp-plugin-stamdata' ); ?></th>
							<td>
								<fieldset>
									<label for="wp-plugin-stamdata-live">
										<input id="wp-plugin-stamdata-live" type="radio" name="active_data_version" value="live" <?php checked( $active_version, 'live' ); ?> />
										<?php esc_html_e( 'Live', 'wp-plugin-stamdata' ); ?>
									</label>
									<br />
									<label for="wp-plugin-stamdata-test">
										<input id="wp-plugin-stamdata-test" type="radio" name="active_data_version" value="test" <?php checked( $active_version, 'test' ); ?> />
										<?php esc_html_e( 'Test', 'wp-plugin-stamdata' ); ?>
									</label>
								</fieldset>
								<p class="description"><?php esc_html_e( 'This switch applies to the whole plugin. Entity admin pages will read and write the active dataset.', 'wp-plugin-stamdata' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( __( 'Save settings', 'wp-plugin-stamdata' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle form submissions.
	 *
	 * @return void
	 */
	public function handle_request() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';

		if ( 'wp-plugin-stamdata' !== $page ) {
			return;
		}

		if ( ! isset( $_POST['stamdata_action'] ) || 'save_settings' !== sanitize_key( wp_unslash( $_POST['stamdata_action'] ) ) ) {
			return;
		}

		check_admin_referer( 'wp_plugin_stamdata_save_settings', 'wp_plugin_stamdata_settings_nonce' );

		$active_data_version = isset( $_POST['active_data_version'] ) ? sanitize_key( wp_unslash( $_POST['active_data_version'] ) ) : 'live';

		if ( ! in_array( $active_data_version, array( 'live', 'test' ), true ) ) {
			$this->redirect_with_notice( 'invalid' );
		}

		update_option( 'wp_plugin_stamdata_active_data_version', $active_data_version );

		$this->redirect_with_notice( 'updated' );
	}

	/**
	 * Render notices.
	 *
	 * @return void
	 */
	private function render_notices() {
		if ( ! isset( $_GET['message'] ) ) {
			return;
		}

		$message = sanitize_key( wp_unslash( $_GET['message'] ) );
		$type    = 'success';
		$text    = '';

		if ( 'updated' === $message ) {
			$text = __( 'Settings saved.', 'wp-plugin-stamdata' );
		} elseif ( 'invalid' === $message ) {
			$type = 'error';
			$text = __( 'Please choose a valid data version.', 'wp-plugin-stamdata' );
		}

		if ( '' === $text ) {
			return;
		}
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
			<p><?php echo esc_html( $text ); ?></p>
		</div>
		<?php
	}

	/**
	 * Redirect back to the settings page.
	 *
	 * @param string $message Notice key.
	 * @return void
	 */
	private function redirect_with_notice( $message ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wp-plugin-stamdata',
					'message' => $message,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
