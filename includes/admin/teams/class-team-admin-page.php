<?php
/**
 * Teams admin screen.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the Teams admin UI and handles form submissions.
 */
class WP_Plugin_Stamdata_Team_Admin_Page {

	/**
	 * Teams repository.
	 *
	 * @var WP_Plugin_Stamdata_Team_Repository
	 */
	private $repository;

	/**
	 * Team importer service.
	 *
	 * @var WP_Plugin_Stamdata_Team_Importer
	 */
	private $importer;

	/**
	 * Constructor.
	 *
	 * @param WP_Plugin_Stamdata_Team_Repository $repository Repository instance.
	 */
	public function __construct( WP_Plugin_Stamdata_Team_Repository $repository ) {
		$this->repository = $repository;
		$this->importer   = new WP_Plugin_Stamdata_Team_Importer( $repository );
	}

	/**
	 * Register the teams submenu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'wp-plugin-stamdata',
			__( 'Teams', 'wp-plugin-stamdata' ),
			__( 'Teams', 'wp-plugin-stamdata' ),
			'manage_options',
			'wp-plugin-stamdata-teams',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue assets for the team editor page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'stamdata_page_wp-plugin-stamdata-teams' !== $hook_suffix ) {
			return;
		}

		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';

		if ( 'edit' !== $view ) {
			return;
		}

		wp_enqueue_media();
	}

	/**
	 * Render the correct teams admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';

		if ( 'edit' === $view ) {
			$this->render_editor_page();
			return;
		}

		$this->render_list_page();
	}

	/**
	 * Render the teams list page.
	 *
	 * @return void
	 */
	private function render_list_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage teams.', 'wp-plugin-stamdata' ) );
		}

		$active_version = stamdata_get_active_data_version();
		$teams          = $this->repository->get_all( $active_version );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Teams', 'wp-plugin-stamdata' ); ?></h1>
			<a href="<?php echo esc_url( $this->get_add_url() ); ?>" class="page-title-action"><?php esc_html_e( 'Add new', 'wp-plugin-stamdata' ); ?></a>
			<form method="post" action="" style="display:inline-block;margin-left:8px;vertical-align:middle;">
				<?php wp_nonce_field( 'wp_plugin_stamdata_import_teams', 'wp_plugin_stamdata_import_nonce' ); ?>
				<input type="hidden" name="page" value="wp-plugin-stamdata-teams" />
				<input type="hidden" name="stamdata_action" value="import_teams" />
				<?php submit_button( __( 'Import from Nevobo', 'wp-plugin-stamdata' ), 'page-title-action', 'submit', false, array( 'style' => 'margin-left:0;' ) ); ?>
			</form>
			<hr class="wp-header-end" />
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: data version. */
						__( 'You are editing the %s dataset. Change the master switch on the Stamdata settings page to work on the other dataset.', 'wp-plugin-stamdata' ),
						$active_version
					)
				);
				?>
			</p>

			<?php $this->render_notices(); ?>
			<p class="description">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: endpoint URL, 2: vereniging path. */
						__( 'Import uses %1$s with vereniging %2$s. You can change these values on the Stamdata settings page.', 'wp-plugin-stamdata' ),
						stamdata_get_nevobo_teams_endpoint(),
						stamdata_get_nevobo_vereniging_path()
					)
				);
				?>
			</p>

			<?php if ( empty( $teams ) ) : ?>
				<p><?php esc_html_e( 'No teams found yet for the active dataset.', 'wp-plugin-stamdata' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Image', 'wp-plugin-stamdata' ); ?></th>
							<th><?php esc_html_e( 'Name', 'wp-plugin-stamdata' ); ?></th>
							<th><?php esc_html_e( 'Short name', 'wp-plugin-stamdata' ); ?></th>
							<th><?php esc_html_e( 'Sortable rank', 'wp-plugin-stamdata' ); ?></th>
							<th><?php esc_html_e( 'Slug', 'wp-plugin-stamdata' ); ?></th>
							<th><?php esc_html_e( 'External API ID', 'wp-plugin-stamdata' ); ?></th>
							<th><?php esc_html_e( 'Updated', 'wp-plugin-stamdata' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'wp-plugin-stamdata' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $teams as $team ) : ?>
							<tr>
								<td><?php echo wp_kses_post( $this->get_image_preview_html( isset( $team['image_id'] ) ? (int) $team['image_id'] : 0, 'thumbnail' ) ); ?></td>
								<td><?php echo esc_html( $team['name'] ); ?></td>
								<td><code><?php echo esc_html( isset( $team['short_name'] ) ? $team['short_name'] : '' ); ?></code></td>
								<td><code><?php echo esc_html( isset( $team['sortable_rank'] ) ? $team['sortable_rank'] : '' ); ?></code></td>
								<td><code><?php echo esc_html( $team['slug'] ); ?></code></td>
								<td><code><?php echo esc_html( isset( $team['external_api_id'] ) ? $team['external_api_id'] : '' ); ?></code></td>
								<td><?php echo esc_html( $team['updated_at'] ); ?></td>
								<td>
									<a href="<?php echo esc_url( $this->get_edit_url( (int) $team['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'wp-plugin-stamdata' ); ?></a>
									|
									<a href="<?php echo esc_url( $this->get_delete_url( (int) $team['id'] ) ); ?>" style="color:#b32d2e;" onclick="return confirm('<?php echo esc_js( __( 'Delete this team?', 'wp-plugin-stamdata' ) ); ?>');"><?php esc_html_e( 'Delete', 'wp-plugin-stamdata' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the team editor page.
	 *
	 * @return void
	 */
	public function render_editor_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage teams.', 'wp-plugin-stamdata' ) );
		}

		$active_version = stamdata_get_active_data_version();
		$team_id        = isset( $_GET['team_id'] ) ? absint( $_GET['team_id'] ) : 0;
		$edit_team      = null;
		$image_id       = 0;

		if ( $team_id > 0 ) {
			$edit_team = $this->repository->get_by_id( $team_id, $active_version );

			if ( ! $edit_team ) {
				$this->redirect_to_list_with_notice( 'not_found' );
			}
		}

		if ( $edit_team && ! empty( $edit_team['image_id'] ) ) {
			$image_id = (int) $edit_team['image_id'];
		}

		?>
		<div class="wrap">
			<h1><?php echo $edit_team ? esc_html__( 'Edit team', 'wp-plugin-stamdata' ) : esc_html__( 'Add team', 'wp-plugin-stamdata' ); ?></h1>
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: data version. */
						__( 'You are editing the %s dataset. Change the master switch on the Stamdata settings page to work on the other dataset.', 'wp-plugin-stamdata' ),
						$active_version
					)
				);
				?>
			</p>

			<?php $this->render_notices(); ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'wp_plugin_stamdata_save_team', 'wp_plugin_stamdata_nonce' ); ?>
				<input type="hidden" name="page" value="wp-plugin-stamdata-teams" />
				<input type="hidden" name="view" value="edit" />
				<input type="hidden" name="stamdata_action" value="save_team" />
				<?php if ( $edit_team ) : ?>
					<input type="hidden" name="team_id" value="<?php echo esc_attr( $edit_team['id'] ); ?>" />
				<?php endif; ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="team_name"><?php esc_html_e( 'Team name', 'wp-plugin-stamdata' ); ?></label>
							</th>
							<td>
								<input name="name" id="team_name" type="text" class="regular-text" required value="<?php echo esc_attr( $edit_team['name'] ?? '' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="team_sortable_rank"><?php esc_html_e( 'Sortable rank', 'wp-plugin-stamdata' ); ?></label>
							</th>
							<td>
								<input name="sortable_rank" id="team_sortable_rank" type="text" class="regular-text code" value="<?php echo esc_attr( $edit_team['sortable_rank'] ?? '' ); ?>" />
								<p class="description"><?php esc_html_e( 'Teams overview is sorted by this value first, then by name.', 'wp-plugin-stamdata' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="team_slug"><?php esc_html_e( 'Slug', 'wp-plugin-stamdata' ); ?></label>
							</th>
							<td>
								<input name="slug" id="team_slug" type="text" class="regular-text" required value="<?php echo esc_attr( $edit_team['slug'] ?? '' ); ?>" />
								<p class="description"><?php esc_html_e( 'Used as the stable identifier for a team within this plugin.', 'wp-plugin-stamdata' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="team_external_source"><?php esc_html_e( 'External source', 'wp-plugin-stamdata' ); ?></label>
							</th>
							<td>
								<input name="external_source" id="team_external_source" type="text" class="regular-text" value="<?php echo esc_attr( $edit_team['external_source'] ?? '' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="team_external_id"><?php esc_html_e( 'External ID', 'wp-plugin-stamdata' ); ?></label>
							</th>
							<td>
								<input name="external_id" id="team_external_id" type="text" class="regular-text code" value="<?php echo esc_attr( $edit_team['external_id'] ?? '' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="team_external_api_id"><?php esc_html_e( 'External API ID', 'wp-plugin-stamdata' ); ?></label>
							</th>
							<td>
								<input name="external_api_id" id="team_external_api_id" type="text" class="large-text code" value="<?php echo esc_attr( $edit_team['external_api_id'] ?? '' ); ?>" />
								<p class="description"><?php esc_html_e( 'For Nevobo imports this stores the raw remote @id value.', 'wp-plugin-stamdata' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Team image', 'wp-plugin-stamdata' ); ?></th>
							<td>
								<div id="wp-plugin-stamdata-team-image-preview">
									<?php echo wp_kses_post( $this->get_image_preview_html( $image_id, 'medium' ) ); ?>
								</div>
								<input type="hidden" name="image_id" id="team_image_id" value="<?php echo esc_attr( $image_id ); ?>" />
								<p>
									<button type="button" class="button" id="wp-plugin-stamdata-select-team-image"><?php esc_html_e( 'Select image', 'wp-plugin-stamdata' ); ?></button>
									<button type="button" class="button button-secondary" id="wp-plugin-stamdata-remove-team-image"><?php esc_html_e( 'Remove image', 'wp-plugin-stamdata' ); ?></button>
								</p>
								<p class="description"><?php esc_html_e( 'Choose a team image from the WordPress media library.', 'wp-plugin-stamdata' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( $edit_team ? __( 'Update team', 'wp-plugin-stamdata' ) : __( 'Add team', 'wp-plugin-stamdata' ) ); ?>
				<a href="<?php echo esc_url( $this->get_list_url() ); ?>" class="button button-secondary"><?php esc_html_e( 'Back to teams', 'wp-plugin-stamdata' ); ?></a>
			</form>
			<script>
				document.addEventListener('DOMContentLoaded', function () {
					var selectButton = document.getElementById('wp-plugin-stamdata-select-team-image');
					var removeButton = document.getElementById('wp-plugin-stamdata-remove-team-image');
					var imageField = document.getElementById('team_image_id');
					var preview = document.getElementById('wp-plugin-stamdata-team-image-preview');
					var frame;

					if (!selectButton || !removeButton || !imageField || !preview || typeof wp === 'undefined' || !wp.media) {
						return;
					}

					selectButton.addEventListener('click', function (event) {
						event.preventDefault();

						if (frame) {
							frame.open();
							return;
						}

						frame = wp.media({
							title: '<?php echo esc_js( __( 'Select team image', 'wp-plugin-stamdata' ) ); ?>',
							button: {
								text: '<?php echo esc_js( __( 'Use this image', 'wp-plugin-stamdata' ) ); ?>'
							},
							multiple: false
						});

						frame.on('select', function () {
							var attachment = frame.state().get('selection').first().toJSON();

							imageField.value = attachment.id || '';
							preview.innerHTML = '<img src="' + (attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url) + '" alt="" style="max-width:300px;height:auto;" />';
						});

						frame.open();
					});

					removeButton.addEventListener('click', function (event) {
						event.preventDefault();
						imageField.value = '';
						preview.innerHTML = '<p><?php echo esc_js( __( 'No image selected.', 'wp-plugin-stamdata' ) ); ?></p>';
					});
				});
			</script>
		</div>
		<?php
	}

	/**
	 * Handle admin actions on page load.
	 *
	 * @return void
	 */
	public function handle_request() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';

		if ( 'wp-plugin-stamdata-teams' !== $page ) {
			return;
		}

		if ( isset( $_POST['stamdata_action'] ) && 'save_team' === sanitize_key( wp_unslash( $_POST['stamdata_action'] ) ) ) {
			$this->handle_save();
		}

		if ( isset( $_POST['stamdata_action'] ) && 'import_teams' === sanitize_key( wp_unslash( $_POST['stamdata_action'] ) ) ) {
			$this->handle_import();
		}

		if ( isset( $_GET['action'] ) && 'delete' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
			$this->handle_delete();
		}
	}

	/**
	 * Handle team import requests.
	 *
	 * @return void
	 */
	private function handle_import() {
		check_admin_referer( 'wp_plugin_stamdata_import_teams', 'wp_plugin_stamdata_import_nonce' );

		$result = $this->importer->import_nevobo_teams( stamdata_get_active_data_version() );

		if ( is_wp_error( $result ) ) {
			$this->redirect_to_list_with_notice( 'import_error', array( 'error_detail' => $result->get_error_message() ) );
		}

		$this->redirect_to_list_with_notice(
			'imported',
			array(
				'import_created' => $result['created'],
				'import_updated' => $result['updated'],
				'import_skipped' => $result['skipped'],
				'import_total'   => $result['total'],
			)
		);
	}

	/**
	 * Handle team save requests.
	 *
	 * @return void
	 */
	private function handle_save() {
		check_admin_referer( 'wp_plugin_stamdata_save_team', 'wp_plugin_stamdata_nonce' );

		$team_id  = isset( $_POST['team_id'] ) ? absint( $_POST['team_id'] ) : 0;
		$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$raw_slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$image_id = isset( $_POST['image_id'] ) ? absint( $_POST['image_id'] ) : 0;
		$sortable_rank = isset( $_POST['sortable_rank'] ) ? sanitize_text_field( wp_unslash( $_POST['sortable_rank'] ) ) : '';
		$external_source = isset( $_POST['external_source'] ) ? sanitize_text_field( wp_unslash( $_POST['external_source'] ) ) : '';
		$external_id = isset( $_POST['external_id'] ) ? sanitize_text_field( wp_unslash( $_POST['external_id'] ) ) : '';
		$external_api_id = isset( $_POST['external_api_id'] ) ? sanitize_text_field( wp_unslash( $_POST['external_api_id'] ) ) : '';

		if ( '' === $name || '' === $raw_slug ) {
			$this->redirect_to_editor_with_notice( 'invalid', $team_id );
		}

		$data = array(
			'name'            => $name,
			'slug'            => $raw_slug,
			'image_id'        => $image_id,
			'sortable_rank'   => $sortable_rank,
			'external_source' => $external_source,
			'external_id'     => $external_id,
			'external_api_id' => $external_api_id,
			'data_version'    => stamdata_get_active_data_version(),
		);

		$result = $team_id > 0
			? $this->repository->update( $team_id, $data )
			: $this->repository->create( $data );

		if ( is_wp_error( $result ) ) {
			$this->redirect_to_editor_with_notice( 'error', $team_id );
		}

		$this->redirect_to_list_with_notice( $team_id > 0 ? 'updated' : 'created' );
	}

	/**
	 * Handle delete requests.
	 *
	 * @return void
	 */
	private function handle_delete() {
		$team_id = isset( $_GET['team_id'] ) ? absint( $_GET['team_id'] ) : 0;

		check_admin_referer( 'wp_plugin_stamdata_delete_team_' . $team_id );

		if ( $team_id < 1 ) {
			$this->redirect_to_list_with_notice( 'invalid' );
		}

		$result = $this->repository->delete( $team_id );

		if ( is_wp_error( $result ) ) {
			$this->redirect_to_list_with_notice( 'error' );
		}

		$this->redirect_to_list_with_notice( 'deleted' );
	}

	/**
	 * Render admin notices based on redirect parameters.
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

		switch ( $message ) {
			case 'created':
				$text = __( 'Team created.', 'wp-plugin-stamdata' );
				break;
			case 'updated':
				$text = __( 'Team updated.', 'wp-plugin-stamdata' );
				break;
			case 'deleted':
				$text = __( 'Team deleted.', 'wp-plugin-stamdata' );
				break;
			case 'invalid':
				$type = 'error';
				$text = __( 'Please provide valid team data.', 'wp-plugin-stamdata' );
				break;
			case 'error':
				$type = 'error';
				$text = __( 'Something went wrong while saving the team.', 'wp-plugin-stamdata' );
				break;
			case 'not_found':
				$type = 'error';
				$text = __( 'The requested team could not be found in the active dataset.', 'wp-plugin-stamdata' );
				break;
			case 'imported':
				$text = sprintf(
					/* translators: 1: created count, 2: updated count, 3: skipped count, 4: total count. */
					__( 'Nevobo import finished. Created: %1$d, updated: %2$d, skipped: %3$d, total received: %4$d.', 'wp-plugin-stamdata' ),
					isset( $_GET['import_created'] ) ? absint( $_GET['import_created'] ) : 0,
					isset( $_GET['import_updated'] ) ? absint( $_GET['import_updated'] ) : 0,
					isset( $_GET['import_skipped'] ) ? absint( $_GET['import_skipped'] ) : 0,
					isset( $_GET['import_total'] ) ? absint( $_GET['import_total'] ) : 0
				);
				break;
			case 'import_error':
				$type = 'error';
				$text = isset( $_GET['error_detail'] )
					? sanitize_text_field( wp_unslash( $_GET['error_detail'] ) )
					: __( 'The Nevobo import failed.', 'wp-plugin-stamdata' );
				break;
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
	 * Build the edit URL.
	 *
	 * @param int $team_id Team ID.
	 * @return string
	 */
	private function get_edit_url( $team_id ) {
		return add_query_arg(
			array(
				'page'    => 'wp-plugin-stamdata-teams',
				'view'    => 'edit',
				'team_id' => $team_id,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Build the add URL.
	 *
	 * @return string
	 */
	private function get_add_url() {
		return add_query_arg(
			array(
				'page' => 'wp-plugin-stamdata-teams',
				'view' => 'edit',
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Build the list URL.
	 *
	 * @return string
	 */
	private function get_list_url() {
		return add_query_arg(
			array(
				'page' => 'wp-plugin-stamdata-teams',
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Build the delete URL with nonce.
	 *
	 * @param int $team_id Team ID.
	 * @return string
	 */
	private function get_delete_url( $team_id ) {
		$url = add_query_arg(
			array(
				'page'    => 'wp-plugin-stamdata-teams',
				'action'  => 'delete',
				'team_id' => $team_id,
			),
			admin_url( 'admin.php' )
		);

		return wp_nonce_url( $url, 'wp_plugin_stamdata_delete_team_' . $team_id );
	}

	/**
	 * Render an image preview for a team.
	 *
	 * @param int    $image_id Attachment ID.
	 * @param string $size     Image size.
	 * @return string
	 */
	private function get_image_preview_html( $image_id, $size = 'thumbnail' ) {
		if ( $image_id < 1 ) {
			return '<p>' . esc_html__( 'No image selected.', 'wp-plugin-stamdata' ) . '</p>';
		}

		$image = wp_get_attachment_image( $image_id, $size, false, array( 'style' => 'max-width:300px;height:auto;' ) );

		return $image ? $image : '<p>' . esc_html__( 'Selected image could not be loaded.', 'wp-plugin-stamdata' ) . '</p>';
	}

	/**
	 * Redirect back to the list page with a notice code.
	 *
	 * @param string $message Notice key.
	 * @return void
	 */
	private function redirect_to_list_with_notice( $message, array $extra_args = array() ) {
		wp_safe_redirect(
			add_query_arg(
				array_merge(
					array(
						'page'    => 'wp-plugin-stamdata-teams',
						'message' => $message,
					),
					$extra_args
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Redirect back to the editor page with a notice code.
	 *
	 * @param string $message Notice key.
	 * @param int    $team_id Team ID.
	 * @return void
	 */
	private function redirect_to_editor_with_notice( $message, $team_id = 0 ) {
		$args = array(
			'page'    => 'wp-plugin-stamdata-teams',
			'view'    => 'edit',
			'message' => $message,
		);

		if ( $team_id > 0 ) {
			$args['team_id'] = $team_id;
		}

		wp_safe_redirect(
			add_query_arg(
				$args,
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
