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
	private $availability_repository;

	public function __construct( WP_Plugin_Stamdata_Field_Repository $repository, WP_Plugin_Stamdata_Location_Repository $location_repository ) {
		$this->repository = $repository;
		$this->location_repository = $location_repository;
		$this->availability_repository = new WP_Plugin_Stamdata_Field_Availability_Repository();
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
		if ( 'availability' === $view ) { $this->render_availability_page(); return; }
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
							<td><a href="<?php echo esc_url( $this->get_edit_url( (int) $field['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'wp-plugin-stamdata' ); ?></a> | <a href="<?php echo esc_url( $this->get_availability_url( (int) $field['id'] ) ); ?>"><?php esc_html_e( 'Beschikbaarheid', 'wp-plugin-stamdata' ); ?></a> | <a href="<?php echo esc_url( $this->get_delete_url( (int) $field['id'] ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this field?', 'wp-plugin-stamdata' ) ); ?>');"><?php esc_html_e( 'Delete', 'wp-plugin-stamdata' ); ?></a></td>
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
				<?php if ( $field ) : ?><a href="<?php echo esc_url( $this->get_availability_url( (int) $field['id'] ) ); ?>" class="button"><?php esc_html_e( 'Manage beschikbaarheid', 'wp-plugin-stamdata' ); ?></a><?php endif; ?>
				<a href="<?php echo esc_url( $this->get_list_url() ); ?>" class="button button-secondary"><?php esc_html_e( 'Back to velden', 'wp-plugin-stamdata' ); ?></a>
			</form>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_availability_page() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You do not have permission to manage field availability.', 'wp-plugin-stamdata' ) ); }
		$field_id     = isset( $_GET['field_id'] ) ? absint( $_GET['field_id'] ) : 0;
		$field        = $field_id ? $this->repository->get_by_id( $field_id ) : null;
		$week_number  = isset( $_GET['week_number'] ) ? absint( $_GET['week_number'] ) : 0;
		$scope        = $week_number > 0 ? 'exception' : 'default';
		$edit_id      = isset( $_GET['availability_id'] ) ? absint( $_GET['availability_id'] ) : 0;
		$edit_row     = $edit_id ? $this->availability_repository->get_by_id( $edit_id ) : null;
		$rows         = $this->availability_repository->get_for_field( $field_id, $week_number > 0 ? $week_number : null );

		if ( ! $field ) { $this->redirect_to_list_with_notice( 'not_found' ); }
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Veld beschikbaarheid', 'wp-plugin-stamdata' ); ?></h1>
			<p><?php echo esc_html( sprintf( __( 'Manage availability for veld: %s', 'wp-plugin-stamdata' ), $field['name'] ) ); ?></p>
			<?php $this->render_notices(); ?>
			<form method="get" action="">
				<input type="hidden" name="page" value="wp-plugin-stamdata-fields" />
				<input type="hidden" name="view" value="availability" />
				<input type="hidden" name="field_id" value="<?php echo esc_attr( $field_id ); ?>" />
				<label for="week_number"><strong><?php esc_html_e( 'Week', 'wp-plugin-stamdata' ); ?></strong></label>
				<select name="week_number" id="week_number">
					<option value="0"><?php esc_html_e( 'Standard week', 'wp-plugin-stamdata' ); ?></option>
					<?php for ( $week = 1; $week <= 53; $week++ ) : ?>
						<option value="<?php echo esc_attr( $week ); ?>" <?php selected( $week_number, $week ); ?>><?php echo esc_html( sprintf( __( 'Week %d', 'wp-plugin-stamdata' ), $week ) ); ?></option>
					<?php endfor; ?>
				</select>
				<?php submit_button( __( 'Select week', 'wp-plugin-stamdata' ), 'secondary', '', false ); ?>
			</form>
			<h2><?php echo esc_html( 'default' === $scope ? __( 'Weekly availability', 'wp-plugin-stamdata' ) : sprintf( __( 'Exception week %d', 'wp-plugin-stamdata' ), $week_number ) ); ?></h2>
			<form method="post" action="">
				<?php wp_nonce_field( 'wp_plugin_stamdata_save_field_availability', 'wp_plugin_stamdata_field_availability_nonce' ); ?>
				<input type="hidden" name="page" value="wp-plugin-stamdata-fields" />
				<input type="hidden" name="view" value="availability" />
				<input type="hidden" name="field_id" value="<?php echo esc_attr( $field_id ); ?>" />
				<input type="hidden" name="week_number" value="<?php echo esc_attr( $week_number ); ?>" />
				<input type="hidden" name="stamdata_action" value="save_field_availability" />
				<?php if ( $edit_row ) : ?><input type="hidden" name="availability_id" value="<?php echo esc_attr( $edit_row['id'] ); ?>" /><?php endif; ?>
				<table class="form-table" role="presentation"><tbody>
					<tr><th><label for="availability_day"><?php esc_html_e( 'Day', 'wp-plugin-stamdata' ); ?></label></th><td><select name="day_of_week" id="availability_day"><?php foreach ( $this->get_day_options() as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $edit_row['day_of_week'] ) ? (int) $edit_row['day_of_week'] : 1, $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
					<tr><th><label for="availability_start"><?php esc_html_e( 'Start time', 'wp-plugin-stamdata' ); ?></label></th><td><input type="time" name="start_time" id="availability_start" required value="<?php echo esc_attr( $edit_row['start_time'] ?? '18:00' ); ?>" /></td></tr>
					<tr><th><label for="availability_end"><?php esc_html_e( 'End time', 'wp-plugin-stamdata' ); ?></label></th><td><input type="time" name="end_time" id="availability_end" required value="<?php echo esc_attr( $edit_row['end_time'] ?? '19:00' ); ?>" /></td></tr>
				</tbody></table>
				<?php submit_button( $edit_row ? __( 'Update availability', 'wp-plugin-stamdata' ) : __( 'Add availability', 'wp-plugin-stamdata' ) ); ?>
				<a href="<?php echo esc_url( $this->get_list_url() ); ?>" class="button button-secondary"><?php esc_html_e( 'Back to velden', 'wp-plugin-stamdata' ); ?></a>
			</form>
			<?php if ( empty( $rows ) ) : ?>
				<p><?php esc_html_e( 'No availability rows found for this selection.', 'wp-plugin-stamdata' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Day', 'wp-plugin-stamdata' ); ?></th><th><?php esc_html_e( 'Start', 'wp-plugin-stamdata' ); ?></th><th><?php esc_html_e( 'End', 'wp-plugin-stamdata' ); ?></th><th><?php esc_html_e( 'Actions', 'wp-plugin-stamdata' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $this->get_day_label( (int) $row['day_of_week'] ) ); ?></td>
							<td><?php echo esc_html( $row['start_time'] ); ?></td>
							<td><?php echo esc_html( $row['end_time'] ); ?></td>
							<td><a href="<?php echo esc_url( $this->get_availability_url( $field_id, $week_number, (int) $row['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'wp-plugin-stamdata' ); ?></a> | <a href="<?php echo esc_url( $this->get_delete_availability_url( $field_id, $week_number, (int) $row['id'] ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this availability row?', 'wp-plugin-stamdata' ) ); ?>');"><?php esc_html_e( 'Delete', 'wp-plugin-stamdata' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	public function handle_request() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) { return; }
		$page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';
		if ( 'wp-plugin-stamdata-fields' !== $page ) { return; }
		if ( isset( $_POST['stamdata_action'] ) && 'save_field' === sanitize_key( wp_unslash( $_POST['stamdata_action'] ) ) ) { $this->handle_save(); }
		if ( isset( $_POST['stamdata_action'] ) && 'save_field_availability' === sanitize_key( wp_unslash( $_POST['stamdata_action'] ) ) ) { $this->handle_save_availability(); }
		if ( isset( $_GET['action'] ) && 'delete' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) { $this->handle_delete(); }
		if ( isset( $_GET['action'] ) && 'delete_availability' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) { $this->handle_delete_availability(); }
	}

	private function handle_save() {
		check_admin_referer( 'wp_plugin_stamdata_save_field', 'wp_plugin_stamdata_field_nonce' );
		$field_id = isset( $_POST['field_id'] ) ? absint( $_POST['field_id'] ) : 0;
		$data = array(
			'name'         => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'slug'         => isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '',
			'location_id'  => isset( $_POST['location_id'] ) ? absint( $_POST['location_id'] ) : 0,
			'sort_order'   => isset( $_POST['sort_order'] ) ? intval( wp_unslash( $_POST['sort_order'] ) ) : 0,
			'data_version' => wp_plugin_stamdata_get_active_data_version(),
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

	private function handle_save_availability() {
		check_admin_referer( 'wp_plugin_stamdata_save_field_availability', 'wp_plugin_stamdata_field_availability_nonce' );
		$field_id         = isset( $_POST['field_id'] ) ? absint( $_POST['field_id'] ) : 0;
		$availability_id  = isset( $_POST['availability_id'] ) ? absint( $_POST['availability_id'] ) : 0;
		$week_number      = isset( $_POST['week_number'] ) ? absint( $_POST['week_number'] ) : 0;
		$data             = array(
			'field_id'      => $field_id,
			'week_type'     => $week_number > 0 ? 'exception' : 'default',
			'week_number'   => $week_number > 0 ? $week_number : null,
			'day_of_week'   => isset( $_POST['day_of_week'] ) ? absint( $_POST['day_of_week'] ) : 0,
			'start_time'    => isset( $_POST['start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['start_time'] ) ) : '',
			'end_time'      => isset( $_POST['end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['end_time'] ) ) : '',
			'data_version'  => wp_plugin_stamdata_get_active_data_version(),
		);
		if ( $field_id < 1 || $data['day_of_week'] < 1 || $data['day_of_week'] > 7 || '' === $data['start_time'] || '' === $data['end_time'] || $data['start_time'] >= $data['end_time'] ) {
			$this->redirect_to_availability_with_notice( 'invalid_availability', $field_id, $week_number );
		}
		$result = $availability_id ? $this->availability_repository->update( $availability_id, $data ) : $this->availability_repository->create( $data );
		if ( is_wp_error( $result ) ) {
			$this->redirect_to_availability_with_notice( 'availability_error', $field_id, $week_number );
		}
		$this->redirect_to_availability_with_notice( $availability_id ? 'availability_updated' : 'availability_created', $field_id, $week_number );
	}

	private function handle_delete_availability() {
		$field_id        = isset( $_GET['field_id'] ) ? absint( $_GET['field_id'] ) : 0;
		$week_number     = isset( $_GET['week_number'] ) ? absint( $_GET['week_number'] ) : 0;
		$availability_id = isset( $_GET['availability_id'] ) ? absint( $_GET['availability_id'] ) : 0;
		check_admin_referer( 'wp_plugin_stamdata_delete_field_availability_' . $availability_id );
		$result = $this->availability_repository->delete( $availability_id );
		if ( is_wp_error( $result ) ) {
			$this->redirect_to_availability_with_notice( 'availability_error', $field_id, $week_number );
		}
		$this->redirect_to_availability_with_notice( 'availability_deleted', $field_id, $week_number );
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
		elseif ( 'availability_created' === $message ) { $text = __( 'Availability created.', 'wp-plugin-stamdata' ); }
		elseif ( 'availability_updated' === $message ) { $text = __( 'Availability updated.', 'wp-plugin-stamdata' ); }
		elseif ( 'availability_deleted' === $message ) { $text = __( 'Availability deleted.', 'wp-plugin-stamdata' ); }
		elseif ( 'invalid_availability' === $message ) { $type = 'error'; $text = __( 'Please provide valid availability data.', 'wp-plugin-stamdata' ); }
		elseif ( 'availability_error' === $message ) { $type = 'error'; $text = __( 'Something went wrong while saving the availability.', 'wp-plugin-stamdata' ); }
		if ( '' === $text ) { return; }
		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
	}

	private function get_list_url() { return add_query_arg( array( 'page' => 'wp-plugin-stamdata-fields' ), admin_url( 'admin.php' ) ); }
	private function get_add_url() { return add_query_arg( array( 'page' => 'wp-plugin-stamdata-fields', 'view' => 'edit' ), admin_url( 'admin.php' ) ); }
	private function get_edit_url( $field_id ) { return add_query_arg( array( 'page' => 'wp-plugin-stamdata-fields', 'view' => 'edit', 'field_id' => $field_id ), admin_url( 'admin.php' ) ); }
	private function get_availability_url( $field_id, $week_number = 0, $availability_id = 0 ) { $args = array( 'page' => 'wp-plugin-stamdata-fields', 'view' => 'availability', 'field_id' => $field_id ); if ( $week_number > 0 ) { $args['week_number'] = $week_number; } if ( $availability_id > 0 ) { $args['availability_id'] = $availability_id; } return add_query_arg( $args, admin_url( 'admin.php' ) ); }
	private function get_delete_url( $field_id ) { return wp_nonce_url( add_query_arg( array( 'page' => 'wp-plugin-stamdata-fields', 'action' => 'delete', 'field_id' => $field_id ), admin_url( 'admin.php' ) ), 'wp_plugin_stamdata_delete_field_' . $field_id ); }
	private function get_delete_availability_url( $field_id, $week_number, $availability_id ) { $args = array( 'page' => 'wp-plugin-stamdata-fields', 'view' => 'availability', 'action' => 'delete_availability', 'field_id' => $field_id, 'availability_id' => $availability_id ); if ( $week_number > 0 ) { $args['week_number'] = $week_number; } return wp_nonce_url( add_query_arg( $args, admin_url( 'admin.php' ) ), 'wp_plugin_stamdata_delete_field_availability_' . $availability_id ); }
	private function redirect_to_list_with_notice( $message ) { wp_safe_redirect( add_query_arg( array( 'page' => 'wp-plugin-stamdata-fields', 'message' => $message ), admin_url( 'admin.php' ) ) ); exit; }
	private function redirect_to_editor_with_notice( $message, $field_id = 0 ) { $args = array( 'page' => 'wp-plugin-stamdata-fields', 'view' => 'edit', 'message' => $message ); if ( $field_id > 0 ) { $args['field_id'] = $field_id; } wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) ); exit; }
	private function redirect_to_availability_with_notice( $message, $field_id, $week_number = 0 ) { $args = array( 'page' => 'wp-plugin-stamdata-fields', 'view' => 'availability', 'field_id' => $field_id, 'message' => $message ); if ( $week_number > 0 ) { $args['week_number'] = $week_number; } wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) ); exit; }
	private function get_day_options() { return array( 1 => __( 'Monday', 'wp-plugin-stamdata' ), 2 => __( 'Tuesday', 'wp-plugin-stamdata' ), 3 => __( 'Wednesday', 'wp-plugin-stamdata' ), 4 => __( 'Thursday', 'wp-plugin-stamdata' ), 5 => __( 'Friday', 'wp-plugin-stamdata' ), 6 => __( 'Saturday', 'wp-plugin-stamdata' ), 7 => __( 'Sunday', 'wp-plugin-stamdata' ) ); }
	private function get_day_label( $day ) { $options = $this->get_day_options(); return isset( $options[ $day ] ) ? $options[ $day ] : (string) $day; }
}
