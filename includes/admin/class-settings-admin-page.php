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

		$active_version = stamdata_get_active_data_version();
		$nevobo_endpoint = stamdata_get_nevobo_teams_endpoint();
		$vereniging_path = stamdata_get_nevobo_vereniging_path();
		$request_limit   = stamdata_get_nevobo_teams_limit();
		$last_import_at  = get_option( 'wp_plugin_stamdata_nevobo_last_team_import_at', '' );
		$last_dataset    = get_option( 'wp_plugin_stamdata_nevobo_last_team_import_dataset', '' );
		$last_summary    = get_option( 'wp_plugin_stamdata_nevobo_last_team_import_summary', array() );
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
						<tr>
							<th scope="row">
								<label for="wp-plugin-stamdata-nevobo-endpoint"><?php esc_html_e( 'Nevobo teams endpoint', 'wp-plugin-stamdata' ); ?></label>
							</th>
							<td>
								<input id="wp-plugin-stamdata-nevobo-endpoint" type="url" name="nevobo_teams_endpoint" class="regular-text code" value="<?php echo esc_attr( $nevobo_endpoint ); ?>" />
								<p class="description"><?php esc_html_e( 'Base URL used by the Teams import button.', 'wp-plugin-stamdata' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wp-plugin-stamdata-nevobo-vereniging"><?php esc_html_e( 'Nevobo vereniging path', 'wp-plugin-stamdata' ); ?></label>
							</th>
							<td>
								<input id="wp-plugin-stamdata-nevobo-vereniging" type="text" name="nevobo_vereniging_path" class="regular-text code" value="<?php echo esc_attr( $vereniging_path ); ?>" />
								<p class="description"><?php esc_html_e( 'Stored separately so you can switch clubs without editing code.', 'wp-plugin-stamdata' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wp-plugin-stamdata-nevobo-limit"><?php esc_html_e( 'Nevobo import limit', 'wp-plugin-stamdata' ); ?></label>
							</th>
							<td>
								<input id="wp-plugin-stamdata-nevobo-limit" type="number" min="1" step="1" name="nevobo_teams_limit" class="small-text" value="<?php echo esc_attr( $request_limit ); ?>" />
								<p class="description"><?php esc_html_e( 'Maximum number of teams requested per import run.', 'wp-plugin-stamdata' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Last Nevobo team import', 'wp-plugin-stamdata' ); ?></th>
							<td>
								<?php if ( '' === $last_import_at ) : ?>
									<p><?php esc_html_e( 'No team import has been run yet.', 'wp-plugin-stamdata' ); ?></p>
								<?php else : ?>
									<p>
										<?php
										echo esc_html(
											sprintf(
												/* translators: 1: datetime, 2: dataset. */
												__( '%1$s into the %2$s dataset.', 'wp-plugin-stamdata' ),
												$last_import_at,
												$last_dataset ? $last_dataset : __( 'unknown', 'wp-plugin-stamdata' )
											)
										);
										?>
									</p>
									<?php if ( is_array( $last_summary ) ) : ?>
										<p class="description">
											<?php
											echo esc_html(
												sprintf(
													/* translators: 1: created count, 2: updated count, 3: skipped count, 4: total count. */
													__( 'Created: %1$d, updated: %2$d, skipped: %3$d, total received: %4$d.', 'wp-plugin-stamdata' ),
													isset( $last_summary['created'] ) ? (int) $last_summary['created'] : 0,
													isset( $last_summary['updated'] ) ? (int) $last_summary['updated'] : 0,
													isset( $last_summary['skipped'] ) ? (int) $last_summary['skipped'] : 0,
													isset( $last_summary['total'] ) ? (int) $last_summary['total'] : 0
												)
											);
											?>
										</p>
									<?php endif; ?>
								<?php endif; ?>
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
		$nevobo_teams_endpoint = isset( $_POST['nevobo_teams_endpoint'] ) ? esc_url_raw( wp_unslash( $_POST['nevobo_teams_endpoint'] ) ) : '';
		$nevobo_vereniging_path = isset( $_POST['nevobo_vereniging_path'] ) ? sanitize_text_field( wp_unslash( $_POST['nevobo_vereniging_path'] ) ) : '';
		$nevobo_teams_limit = isset( $_POST['nevobo_teams_limit'] ) ? absint( $_POST['nevobo_teams_limit'] ) : 100;

		if ( ! in_array( $active_data_version, array( 'live', 'test' ), true ) ) {
			$this->redirect_with_notice( 'invalid' );
		}

		if ( '' === $nevobo_teams_endpoint ) {
			$nevobo_teams_endpoint = 'https://api.nevobo.nl/competitie/teams.jsonld';
		}

		if ( '' === $nevobo_vereniging_path ) {
			$nevobo_vereniging_path = '/relatiebeheer/verenigingen/ckl9x7n';
		}

		if ( $nevobo_teams_limit < 1 ) {
			$nevobo_teams_limit = 100;
		}

		update_option( 'wp_plugin_stamdata_active_data_version', $active_data_version );
		update_option( 'wp_plugin_stamdata_nevobo_teams_endpoint', $nevobo_teams_endpoint );
		update_option( 'wp_plugin_stamdata_nevobo_vereniging_path', $nevobo_vereniging_path );
		update_option( 'wp_plugin_stamdata_nevobo_teams_limit', $nevobo_teams_limit );

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
