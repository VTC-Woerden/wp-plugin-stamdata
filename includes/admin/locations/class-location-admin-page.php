<?php
/**
 * Locations admin screen.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

class WP_Plugin_Stamdata_Location_Admin_Page {

	private $repository;
	private $field_repository;

	public function __construct( WP_Plugin_Stamdata_Location_Repository $repository ) {
		$this->repository = $repository;
		$this->field_repository = new WP_Plugin_Stamdata_Field_Repository();
	}

	public function register_menu() {
		add_submenu_page(
			'wp-plugin-stamdata',
			__( 'Locaties', 'wp-plugin-stamdata' ),
			__( 'Locaties', 'wp-plugin-stamdata' ),
			'manage_options',
			'wp-plugin-stamdata-locations',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_assets( $hook_suffix ) {}

	public function render_page() {
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
		if ( 'edit' === $view ) {
			$this->render_editor_page();
			return;
		}
		$this->render_list_page();
	}

	private function render_list_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage locations.', 'wp-plugin-stamdata' ) );
		}
		$locations = $this->repository->get_all();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Locaties', 'wp-plugin-stamdata' ); ?></h1>
			<a href="<?php echo esc_url( $this->get_add_url() ); ?>" class="page-title-action"><?php esc_html_e( 'Add new', 'wp-plugin-stamdata' ); ?></a>
			<hr class="wp-header-end" />
			<?php $this->render_notices(); ?>
			<?php if ( empty( $locations ) ) : ?>
				<p><?php esc_html_e( 'No locations found yet for the active dataset.', 'wp-plugin-stamdata' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Name', 'wp-plugin-stamdata' ); ?></th><th><?php esc_html_e( 'Slug', 'wp-plugin-stamdata' ); ?></th><th><?php esc_html_e( 'Address', 'wp-plugin-stamdata' ); ?></th><th><?php esc_html_e( 'City', 'wp-plugin-stamdata' ); ?></th><th><?php esc_html_e( 'Actions', 'wp-plugin-stamdata' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $locations as $location ) : ?>
						<tr>
							<td><?php echo esc_html( $location['name'] ); ?></td>
							<td><code><?php echo esc_html( $location['slug'] ); ?></code></td>
							<td><?php echo esc_html( $location['address'] ); ?></td>
							<td><?php echo esc_html( $location['city'] ); ?></td>
							<td><a href="<?php echo esc_url( $this->get_edit_url( (int) $location['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'wp-plugin-stamdata' ); ?></a> | <a href="<?php echo esc_url( $this->get_delete_url( (int) $location['id'] ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this location?', 'wp-plugin-stamdata' ) ); ?>');"><?php esc_html_e( 'Delete', 'wp-plugin-stamdata' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_editor_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage locations.', 'wp-plugin-stamdata' ) );
		}
		$location_id = isset( $_GET['location_id'] ) ? absint( $_GET['location_id'] ) : 0;
		$location    = $location_id ? $this->repository->get_by_id( $location_id ) : null;
		if ( $location_id > 0 && ! $location ) {
			$this->redirect_to_list_with_notice( 'not_found' );
		}
		?>
		<div class="wrap">
			<h1><?php echo $location ? esc_html__( 'Edit locatie', 'wp-plugin-stamdata' ) : esc_html__( 'Add locatie', 'wp-plugin-stamdata' ); ?></h1>
			<?php $this->render_notices(); ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'wp_plugin_stamdata_save_location', 'wp_plugin_stamdata_location_nonce' ); ?>
				<input type="hidden" name="page" value="wp-plugin-stamdata-locations" />
				<input type="hidden" name="view" value="edit" />
				<input type="hidden" name="stamdata_action" value="save_location" />
				<?php if ( $location ) : ?><input type="hidden" name="location_id" value="<?php echo esc_attr( $location['id'] ); ?>" /><?php endif; ?>
				<table class="form-table" role="presentation"><tbody>
					<tr><th><label for="location_name"><?php esc_html_e( 'Name', 'wp-plugin-stamdata' ); ?></label></th><td><input name="name" id="location_name" type="text" class="regular-text" required value="<?php echo esc_attr( $location['name'] ?? '' ); ?>" /></td></tr>
					<tr><th><label for="location_slug"><?php esc_html_e( 'Slug', 'wp-plugin-stamdata' ); ?></label></th><td><input name="slug" id="location_slug" type="text" class="regular-text" required value="<?php echo esc_attr( $location['slug'] ?? '' ); ?>" /></td></tr>
					<tr><th><label for="location_address"><?php esc_html_e( 'Address', 'wp-plugin-stamdata' ); ?></label></th><td><input name="address" id="location_address" type="text" class="regular-text" value="<?php echo esc_attr( $location['address'] ?? '' ); ?>" /></td></tr>
					<tr><th><label for="location_city"><?php esc_html_e( 'City', 'wp-plugin-stamdata' ); ?></label></th><td><input name="city" id="location_city" type="text" class="regular-text" value="<?php echo esc_attr( $location['city'] ?? '' ); ?>" /></td></tr>
				</tbody></table>
				<?php submit_button( $location ? __( 'Update locatie', 'wp-plugin-stamdata' ) : __( 'Add locatie', 'wp-plugin-stamdata' ) ); ?>
				<a href="<?php echo esc_url( $this->get_list_url() ); ?>" class="button button-secondary"><?php esc_html_e( 'Back to locaties', 'wp-plugin-stamdata' ); ?></a>
			</form>
		</div>
		<?php
	}

	public function handle_request() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';
		if ( 'wp-plugin-stamdata-locations' !== $page ) {
			return;
		}
		if ( isset( $_POST['stamdata_action'] ) && 'save_location' === sanitize_key( wp_unslash( $_POST['stamdata_action'] ) ) ) {
			$this->handle_save();
		}
		if ( isset( $_GET['action'] ) && 'delete' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
			$this->handle_delete();
		}
	}

	private function handle_save() {
		check_admin_referer( 'wp_plugin_stamdata_save_location', 'wp_plugin_stamdata_location_nonce' );
		$location_id = isset( $_POST['location_id'] ) ? absint( $_POST['location_id'] ) : 0;
		$data        = array(
			'name'         => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'slug'         => isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '',
			'address'      => isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '',
			'city'         => isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '',
			'data_version' => wp_plugin_stamdata_get_active_data_version(),
		);
		if ( '' === $data['name'] || '' === $data['slug'] ) {
			$this->redirect_to_editor_with_notice( 'invalid', $location_id );
		}
		$result = $location_id ? $this->repository->update( $location_id, $data ) : $this->repository->create( $data );
		if ( is_wp_error( $result ) ) {
			$this->redirect_to_editor_with_notice( 'error', $location_id );
		}
		$this->redirect_to_list_with_notice( $location_id ? 'updated' : 'created' );
	}

	private function handle_delete() {
		$location_id = isset( $_GET['location_id'] ) ? absint( $_GET['location_id'] ) : 0;
		check_admin_referer( 'wp_plugin_stamdata_delete_location_' . $location_id );
		if ( $this->field_repository->count_by_location( $location_id ) > 0 ) {
			$this->redirect_to_list_with_notice( 'has_fields' );
		}
		$result = $this->repository->delete( $location_id );
		if ( is_wp_error( $result ) ) {
			$this->redirect_to_list_with_notice( 'error' );
		}
		$this->redirect_to_list_with_notice( 'deleted' );
	}

	private function render_notices() {
		if ( ! isset( $_GET['message'] ) ) { return; }
		$message = sanitize_key( wp_unslash( $_GET['message'] ) );
		$type = 'success';
		$text = '';
		if ( 'created' === $message ) { $text = __( 'Location created.', 'wp-plugin-stamdata' ); }
		elseif ( 'updated' === $message ) { $text = __( 'Location updated.', 'wp-plugin-stamdata' ); }
		elseif ( 'deleted' === $message ) { $text = __( 'Location deleted.', 'wp-plugin-stamdata' ); }
		elseif ( 'invalid' === $message ) { $type = 'error'; $text = __( 'Please provide valid location data.', 'wp-plugin-stamdata' ); }
		elseif ( 'error' === $message ) { $type = 'error'; $text = __( 'Something went wrong while saving the location.', 'wp-plugin-stamdata' ); }
		elseif ( 'not_found' === $message ) { $type = 'error'; $text = __( 'The requested location could not be found.', 'wp-plugin-stamdata' ); }
		elseif ( 'has_fields' === $message ) { $type = 'error'; $text = __( 'This locatie cannot be deleted because one or more velden still belong to it.', 'wp-plugin-stamdata' ); }
		if ( '' === $text ) { return; }
		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
	}

	private function get_list_url() { return add_query_arg( array( 'page' => 'wp-plugin-stamdata-locations' ), admin_url( 'admin.php' ) ); }
	private function get_add_url() { return add_query_arg( array( 'page' => 'wp-plugin-stamdata-locations', 'view' => 'edit' ), admin_url( 'admin.php' ) ); }
	private function get_edit_url( $location_id ) { return add_query_arg( array( 'page' => 'wp-plugin-stamdata-locations', 'view' => 'edit', 'location_id' => $location_id ), admin_url( 'admin.php' ) ); }
	private function get_delete_url( $location_id ) { return wp_nonce_url( add_query_arg( array( 'page' => 'wp-plugin-stamdata-locations', 'action' => 'delete', 'location_id' => $location_id ), admin_url( 'admin.php' ) ), 'wp_plugin_stamdata_delete_location_' . $location_id ); }
	private function redirect_to_list_with_notice( $message ) { wp_safe_redirect( add_query_arg( array( 'page' => 'wp-plugin-stamdata-locations', 'message' => $message ), admin_url( 'admin.php' ) ) ); exit; }
	private function redirect_to_editor_with_notice( $message, $location_id = 0 ) { $args = array( 'page' => 'wp-plugin-stamdata-locations', 'view' => 'edit', 'message' => $message ); if ( $location_id > 0 ) { $args['location_id'] = $location_id; } wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) ); exit; }
}
