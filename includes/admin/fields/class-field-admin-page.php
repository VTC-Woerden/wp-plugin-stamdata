<?php
/**
 * Fields admin screen.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

class WP_Plugin_Stamdata_Field_Admin_Page {

	private $repository;
	private $location_repository;

	public function __construct( WP_Plugin_Stamdata_Field_Repository $repository, WP_Plugin_Stamdata_Location_Repository $location_repository ) {
		$this->repository = $repository;
		$this->location_repository = $location_repository;
	}

	public function register_menu() {
		add_submenu_page(
			'wp-plugin-stamdata',
			__( 'Velden', 'wp-plugin-stamdata' ),
			__( 'Velden', 'wp-plugin-stamdata' ),
			'manage_options',
			'wp-plugin-stamdata-fields',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_assets( $hook_suffix ) {}

	public function render_page() {
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
		if ( 'edit' === $view ) { $this->render_editor_page(); return; }
		$this->render_list_page();
	}

	private function render_list_page() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You do not have permission to manage fields.', 'wp-plugin-stamdata' ) ); }
		$fields = $this->repository->get_all();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Velden', 'wp-plugin-stamdata' ); ?></h1>
			<a href="<?php echo esc_url( $this->get_add_url() ); ?>" class="page-title-action"><?php esc_html_e( 'Add new', 'wp-plugin-stamdata' ); ?></a>
			<hr class="wp-header-end" />
			<?php $this->render_notices(); ?>
			<?php if ( empty( $fields ) ) : ?>
				<p><?php esc_html_e( 'No fields found yet for the active dataset.', 'wp-plugin-stamdata' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Name', 'wp-plugin-stamdata' ); ?></th><th><?php esc_html_e( 'Location', 'wp-plugin-stamdata' ); ?></th><th><?php esc_html_e( 'Slug', 'wp-plugin-stamdata' ); ?></th><th><?php esc_html_e( 'Order', 'wp-plugin-stamdata' ); ?></th><th><?php esc_html_e( 'Actions', 'wp-plugin-stamdata' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $fields as $field ) : ?>
						<tr>
							<td><?php echo esc_html( $field['name'] ); ?></td>
							<td><?php echo esc_html( $field['location_name'] ); ?></td>
							<td><code><?php echo esc_html( $field['slug'] ); ?></code></td>
							<td><?php echo esc_html( $field['sort_order'] ); ?></td>
							<td><a href="<?php echo esc_url( $this->get_edit_url( (int) $field['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'wp-plugin-stamdata' ); ?></a> | <a href="<?php echo esc_url( $this->get_delete_url( (int) $field['id'] ) ); ?>" style="color:#b32d2e;" onclick="return confirm('<?php echo esc_js( __( 'Delete this field?', 'wp-plugin-stamdata' ) ); ?>');"><?php esc_html_e( 'Delete', 'wp-plugin-stamdata' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_editor_page() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You do not have permission to manage fields.', 'wp-plugin-stamdata' ) ); }
		$field_id   = isset( $_GET['field_id'] ) ? absint( $_GET['field_id'] ) : 0;
		$field      = $field_id ? $this->repository->get_by_id( $field_id ) : null;
		$locations  = $this->location_repository->get_all();
		if ( $field_id > 0 && ! $field ) { $this->redirect_to_list_with_notice( 'not_found' ); }
		?>
		<div class="wrap">
			<h1><?php echo $field ? esc_html__( 'Edit veld', 'wp-plugin-stamdata' ) : esc_html__( 'Add veld', 'wp-plugin-stamdata' ); ?></h1>
			<?php $this->render_notices(); ?>
			<?php if ( empty( $locations ) ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'Create at least one locatie before adding velden.', 'wp-plugin-stamdata' ); ?></p></div>
				<p><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wp-plugin-stamdata-locations', 'view' => 'edit' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add locatie', 'wp-plugin-stamdata' ); ?></a></p>
			<?php else : ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'wp_plugin_stamdata_save_field', 'wp_plugin_stamdata_field_nonce' ); ?>
				<input type="hidden" name="page" value="wp-plugin-stamdata-fields" />
				<input type="hidden" name="view" value="edit" />
				<input type="hidden" name="stamdata_action" value="save_field" />
				<?php if ( $field ) : ?><input type="hidden" name="field_id" value="<?php echo esc_attr( $field['id'] ); ?>" /><?php endif; ?>
				<table class="form-table" role="presentation"><tbody>
					<tr><th><label for="field_name"><?php esc_html_e( 'Name', 'wp-plugin-stamdata' ); ?></label></th><td><input name="name" id="field_name" type="text" class="regular-text" required value="<?php echo esc_attr( $field['name'] ?? '' ); ?>" /></td></tr>
					<tr><th><label for="field_slug"><?php esc_html_e( 'Slug', 'wp-plugin-stamdata' ); ?></label></th><td><input name="slug" id="field_slug" type="text" class="regular-text" required value="<?php echo esc_attr( $field['slug'] ?? '' ); ?>" /></td></tr>
					<tr><th><label for="field_location_id"><?php esc_html_e( 'Locatie', 'wp-plugin-stamdata' ); ?></label></th><td><select name="location_id" id="field_location_id" required><?php foreach ( $locations as $location ) : ?><option value="<?php echo esc_attr( $location['id'] ); ?>" <?php selected( isset( $field['location_id'] ) ? (int) $field['location_id'] : 0, (int) $location['id'] ); ?>><?php echo esc_html( $location['name'] ); ?></option><?php endforeach; ?></select></td></tr>
					<tr><th><label for="field_sort_order"><?php esc_html_e( 'Sort order', 'wp-plugin-stamdata' ); ?></label></th><td><input name="sort_order" id="field_sort_order" type="number" class="small-text" value="<?php echo esc_attr( $field['sort_order'] ?? 0 ); ?>" /></td></tr>
				</tbody></table>
				<?php submit_button( $field ? __( 'Update veld', 'wp-plugin-stamdata' ) : __( 'Add veld', 'wp-plugin-stamdata' ) ); ?>
				<a href="<?php echo esc_url( $this->get_list_url() ); ?>" class="button button-secondary"><?php esc_html_e( 'Back to velden', 'wp-plugin-stamdata' ); ?></a>
			</form>
			<?php endif; ?>
		</div>
		<?php
	}

	public function handle_request() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) { return; }
		$page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';
		if ( 'wp-plugin-stamdata-fields' !== $page ) { return; }
		if ( isset( $_POST['stamdata_action'] ) && 'save_field' === sanitize_key( wp_unslash( $_POST['stamdata_action'] ) ) ) { $this->handle_save(); }
		if ( isset( $_GET['action'] ) && 'delete' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) { $this->handle_delete(); }
	}

	private function handle_save() {
		check_admin_referer( 'wp_plugin_stamdata_save_field', 'wp_plugin_stamdata_field_nonce' );
		$field_id = isset( $_POST['field_id'] ) ? absint( $_POST['field_id'] ) : 0;
		$data = array(
			'name'         => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'slug'         => isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '',
			'location_id'  => isset( $_POST['location_id'] ) ? absint( $_POST['location_id'] ) : 0,
			'sort_order'   => isset( $_POST['sort_order'] ) ? intval( wp_unslash( $_POST['sort_order'] ) ) : 0,
			'data_version' => stamdata_get_active_data_version(),
		);
		if ( '' === $data['name'] || '' === $data['slug'] || $data['location_id'] < 1 ) { $this->redirect_to_editor_with_notice( 'invalid', $field_id ); }
		if ( ! $this->location_repository->get_by_id( $data['location_id'], $data['data_version'] ) ) { $this->redirect_to_editor_with_notice( 'invalid_location', $field_id ); }
		$result = $field_id ? $this->repository->update( $field_id, $data ) : $this->repository->create( $data );
		if ( is_wp_error( $result ) ) { $this->redirect_to_editor_with_notice( 'error', $field_id ); }
		$this->redirect_to_list_with_notice( $field_id ? 'updated' : 'created' );
	}

	private function handle_delete() {
		$field_id = isset( $_GET['field_id'] ) ? absint( $_GET['field_id'] ) : 0;
		check_admin_referer( 'wp_plugin_stamdata_delete_field_' . $field_id );
		$result = $this->repository->delete( $field_id );
		if ( is_wp_error( $result ) ) { $this->redirect_to_list_with_notice( 'error' ); }
		$this->redirect_to_list_with_notice( 'deleted' );
	}

	private function render_notices() {
		if ( ! isset( $_GET['message'] ) ) { return; }
		$message = sanitize_key( wp_unslash( $_GET['message'] ) );
		$type = 'success';
		$text = '';
		if ( 'created' === $message ) { $text = __( 'Field created.', 'wp-plugin-stamdata' ); }
		elseif ( 'updated' === $message ) { $text = __( 'Field updated.', 'wp-plugin-stamdata' ); }
		elseif ( 'deleted' === $message ) { $text = __( 'Field deleted.', 'wp-plugin-stamdata' ); }
		elseif ( 'invalid' === $message ) { $type = 'error'; $text = __( 'Please provide valid field data.', 'wp-plugin-stamdata' ); }
		elseif ( 'invalid_location' === $message ) { $type = 'error'; $text = __( 'Please choose a valid locatie for this veld.', 'wp-plugin-stamdata' ); }
		elseif ( 'error' === $message ) { $type = 'error'; $text = __( 'Something went wrong while saving the field.', 'wp-plugin-stamdata' ); }
		elseif ( 'not_found' === $message ) { $type = 'error'; $text = __( 'The requested field could not be found.', 'wp-plugin-stamdata' ); }
		if ( '' === $text ) { return; }
		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
	}

	private function get_list_url() { return add_query_arg( array( 'page' => 'wp-plugin-stamdata-fields' ), admin_url( 'admin.php' ) ); }
	private function get_add_url() { return add_query_arg( array( 'page' => 'wp-plugin-stamdata-fields', 'view' => 'edit' ), admin_url( 'admin.php' ) ); }
	private function get_edit_url( $field_id ) { return add_query_arg( array( 'page' => 'wp-plugin-stamdata-fields', 'view' => 'edit', 'field_id' => $field_id ), admin_url( 'admin.php' ) ); }
	private function get_delete_url( $field_id ) { return wp_nonce_url( add_query_arg( array( 'page' => 'wp-plugin-stamdata-fields', 'action' => 'delete', 'field_id' => $field_id ), admin_url( 'admin.php' ) ), 'wp_plugin_stamdata_delete_field_' . $field_id ); }
	private function redirect_to_list_with_notice( $message ) { wp_safe_redirect( add_query_arg( array( 'page' => 'wp-plugin-stamdata-fields', 'message' => $message ), admin_url( 'admin.php' ) ) ); exit; }
	private function redirect_to_editor_with_notice( $message, $field_id = 0 ) { $args = array( 'page' => 'wp-plugin-stamdata-fields', 'view' => 'edit', 'message' => $message ); if ( $field_id > 0 ) { $args['field_id'] = $field_id; } wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) ); exit; }
}
