<?php
/**
 * Blueprints admin screen.
 *
 * @package WP_Plugin_Stamdata
 */

defined( 'ABSPATH' ) || exit;

class WP_Plugin_Stamdata_Blueprint_Admin_Page {

	private $repository;
	private $location_repository;
	private $field_repository;
	private $availability_repository;

	public function __construct( WP_Plugin_Stamdata_Blueprint_Repository $repository ) {
		$this->repository          = $repository;
		$this->location_repository = new WP_Plugin_Stamdata_Location_Repository();
		$this->field_repository    = new WP_Plugin_Stamdata_Field_Repository();
		$this->availability_repository = new WP_Plugin_Stamdata_Blueprint_Availability_Repository();
	}

	public function register_menu() {
		add_submenu_page(
			'wp-plugin-stamdata',
			__( 'Blueprints', 'wp-plugin-stamdata' ),
			__( 'Blueprints', 'wp-plugin-stamdata' ),
			'manage_options',
			'wp-plugin-stamdata-blueprints',
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
			wp_die( esc_html__( 'You do not have permission to manage blueprints.', 'wp-plugin-stamdata' ) );
		}

		$blueprints       = $this->repository->get_all();
		$locations        = $this->location_repository->get_all();
		$fields           = $this->field_repository->get_all();
		$locations_by_id  = array();
		$fields_by_id     = array();

		foreach ( $locations as $location ) {
			$locations_by_id[ (int) $location['id'] ] = $location;
		}

		foreach ( $fields as $field ) {
			$fields_by_id[ (int) $field['id'] ] = $field;
		}
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Blueprints', 'wp-plugin-stamdata' ); ?></h1>
			<a href="<?php echo esc_url( $this->get_add_url() ); ?>" class="page-title-action"><?php esc_html_e( 'Add new', 'wp-plugin-stamdata' ); ?></a>
			<hr class="wp-header-end" />
			<p><?php esc_html_e( 'Blueprints describe which locaties and velden are available for a normal week or for a specific exception week.', 'wp-plugin-stamdata' ); ?></p>
			<?php $this->render_notices(); ?>
			<?php if ( empty( $blueprints ) ) : ?>
				<p><?php esc_html_e( 'No blueprints found yet for the active dataset.', 'wp-plugin-stamdata' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'wp-plugin-stamdata' ); ?></th>
							<th><?php esc_html_e( 'Type', 'wp-plugin-stamdata' ); ?></th>
							<th><?php esc_html_e( 'Week', 'wp-plugin-stamdata' ); ?></th>
							<th><?php esc_html_e( 'Velden en slots', 'wp-plugin-stamdata' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'wp-plugin-stamdata' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $blueprints as $blueprint ) : ?>
							<?php
							$blueprint_id          = (int) $blueprint['id'];
							$blueprint_field_ids   = $this->repository->get_field_ids( $blueprint_id );
							$blueprint_availability = $this->availability_repository->get_for_blueprint(
								$blueprint_id,
								'exception' === ( $blueprint['week_type'] ?? 'default' ) ? (int) $blueprint['week_number'] : null
							);
							$availability_by_field = $this->group_availability_rows_by_field_and_day( $blueprint_availability );
							?>
							<tr>
								<td><?php echo esc_html( $blueprint['name'] ); ?></td>
								<td><?php echo esc_html( 'default' === $blueprint['week_type'] ? __( 'Standaard', 'wp-plugin-stamdata' ) : __( 'Afwijkend', 'wp-plugin-stamdata' ) ); ?></td>
								<td><?php echo esc_html( 'default' === $blueprint['week_type'] ? __( 'Standard week', 'wp-plugin-stamdata' ) : sprintf( __( 'Week %d', 'wp-plugin-stamdata' ), (int) $blueprint['week_number'] ) ); ?></td>
								<td><?php $this->render_blueprint_readonly_schedule( $blueprint_field_ids, $availability_by_field, $fields_by_id, $locations_by_id ); ?></td>
								<td><a href="<?php echo esc_url( $this->get_edit_url( (int) $blueprint['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'wp-plugin-stamdata' ); ?></a> | <a href="<?php echo esc_url( $this->get_delete_url( (int) $blueprint['id'] ) ); ?>" style="color:#b32d2e;" onclick="return confirm('<?php echo esc_js( __( 'Delete this blueprint?', 'wp-plugin-stamdata' ) ); ?>');"><?php esc_html_e( 'Delete', 'wp-plugin-stamdata' ); ?></a></td>
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
			wp_die( esc_html__( 'You do not have permission to manage blueprints.', 'wp-plugin-stamdata' ) );
		}

		$blueprint_id  = isset( $_GET['blueprint_id'] ) ? absint( $_GET['blueprint_id'] ) : 0;
		$blueprint     = $blueprint_id ? $this->repository->get_by_id( $blueprint_id ) : null;
		$locations             = $this->location_repository->get_all();
		$fields                = $this->field_repository->get_all();
		$field_ids             = $blueprint ? $this->repository->get_field_ids( $blueprint_id ) : array();
		$availability_rows     = $blueprint ? $this->availability_repository->get_for_blueprint( $blueprint_id, 'exception' === ( $blueprint['week_type'] ?? 'default' ) ? (int) $blueprint['week_number'] : null ) : array();
		$availability_by_field = $this->group_availability_rows_by_field_and_day( $availability_rows );
		$fields_grouped        = array();
		$locations_by_id       = array();

		foreach ( $locations as $location ) {
			$locations_by_id[ (int) $location['id'] ] = $location;
		}

		foreach ( $fields as $field ) {
			$fields_grouped[ (int) $field['location_id'] ][] = $field;
		}

		if ( $blueprint_id > 0 && ! $blueprint ) {
			$this->redirect_to_list_with_notice( 'not_found' );
		}
		?>
		<div class="wrap">
			<h1><?php echo $blueprint ? esc_html__( 'Edit blueprint', 'wp-plugin-stamdata' ) : esc_html__( 'Add blueprint', 'wp-plugin-stamdata' ); ?></h1>
			<p><?php esc_html_e( 'Use a default blueprint for the normal recurring situation and create exception blueprints only for the weeks that differ.', 'wp-plugin-stamdata' ); ?></p>
			<?php $this->render_notices(); ?>
			<?php if ( empty( $locations ) ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'Create at least one locatie before creating a blueprint.', 'wp-plugin-stamdata' ); ?></p></div>
				<p><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wp-plugin-stamdata-locations', 'view' => 'edit' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add locatie', 'wp-plugin-stamdata' ); ?></a></p>
			<?php else : ?>
				<form method="post" action="">
					<?php wp_nonce_field( 'wp_plugin_stamdata_save_blueprint', 'wp_plugin_stamdata_blueprint_nonce' ); ?>
					<input type="hidden" name="page" value="wp-plugin-stamdata-blueprints" />
					<input type="hidden" name="view" value="edit" />
					<input type="hidden" name="stamdata_action" value="save_blueprint" />
					<?php if ( $blueprint ) : ?><input type="hidden" name="blueprint_id" value="<?php echo esc_attr( $blueprint['id'] ); ?>" /><?php endif; ?>
					<table class="form-table" role="presentation">
						<tbody>
							<tr><th><label for="blueprint_name"><?php esc_html_e( 'Name', 'wp-plugin-stamdata' ); ?></label></th><td><input name="name" id="blueprint_name" type="text" class="regular-text" required value="<?php echo esc_attr( $blueprint['name'] ?? '' ); ?>" /></td></tr>
							<tr>
								<th><label for="blueprint_week_type"><?php esc_html_e( 'Blueprint type', 'wp-plugin-stamdata' ); ?></label></th>
								<td>
									<select name="week_type" id="blueprint_week_type">
										<option value="default" <?php selected( $blueprint['week_type'] ?? 'default', 'default' ); ?>><?php esc_html_e( 'Standaard', 'wp-plugin-stamdata' ); ?></option>
										<option value="exception" <?php selected( $blueprint['week_type'] ?? '', 'exception' ); ?>><?php esc_html_e( 'Afwijkend', 'wp-plugin-stamdata' ); ?></option>
									</select>
								</td>
							</tr>
							<tr id="wp-plugin-stamdata-week-number-row" <?php if ( 'exception' !== ( $blueprint['week_type'] ?? 'default' ) ) : ?>style="display:none;"<?php endif; ?>><th><label for="blueprint_week_number"><?php esc_html_e( 'Week number', 'wp-plugin-stamdata' ); ?></label></th><td><select name="week_number" id="blueprint_week_number" <?php disabled( 'exception' !== ( $blueprint['week_type'] ?? 'default' ) ); ?>><option value="0"><?php esc_html_e( 'Choose a week', 'wp-plugin-stamdata' ); ?></option><?php for ( $week = 1; $week <= 53; $week++ ) : ?><option value="<?php echo esc_attr( $week ); ?>" <?php selected( isset( $blueprint['week_number'] ) ? (int) $blueprint['week_number'] : 0, $week ); ?>><?php echo esc_html( sprintf( __( 'Week %d', 'wp-plugin-stamdata' ), $week ) ); ?></option><?php endfor; ?></select><p class="description"><?php esc_html_e( 'Choose a specific week only for an exception blueprint.', 'wp-plugin-stamdata' ); ?></p></td></tr>
							<tr><th><label for="blueprint_notes"><?php esc_html_e( 'Notes', 'wp-plugin-stamdata' ); ?></label></th><td><textarea name="notes" id="blueprint_notes" class="large-text" rows="4"><?php echo esc_textarea( $blueprint['notes'] ?? '' ); ?></textarea></td></tr>
						</tbody>
					</table>
					<h2><?php esc_html_e( 'Available velden', 'wp-plugin-stamdata' ); ?></h2>
					<p><?php esc_html_e( 'Select the velden that are part of this blueprint week. Velden are grouped by locatie so you can still see where each veld belongs.', 'wp-plugin-stamdata' ); ?></p>
					<?php foreach ( $locations as $location ) : ?>
						<div style="padding:12px 0;border-top:1px solid #ddd;">
							<strong><?php echo esc_html( $location['name'] ); ?></strong>
							<?php if ( ! empty( $fields_grouped[ (int) $location['id'] ] ) ) : ?>
								<div style="padding:8px 0 0 24px;">
									<?php foreach ( $fields_grouped[ (int) $location['id'] ] as $field ) : ?>
										<label style="display:block;margin-bottom:4px;"><input type="checkbox" class="wp-plugin-stamdata-field-checkbox" name="field_ids[]" value="<?php echo esc_attr( $field['id'] ); ?>" <?php checked( in_array( (int) $field['id'], $field_ids, true ) ); ?> /> <?php echo esc_html( $field['name'] ); ?></label>
									<?php endforeach; ?>
								</div>
							<?php else : ?>
								<p style="padding-left:24px;"><?php esc_html_e( 'No velden exist for this locatie yet.', 'wp-plugin-stamdata' ); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					<h2><?php esc_html_e( 'Beschikbaarheid per veld', 'wp-plugin-stamdata' ); ?></h2>
					<p><?php esc_html_e( 'Each selected veld has its own weekly availability. Add one or more time slots per weekday for every selected veld.', 'wp-plugin-stamdata' ); ?></p>
					<div id="wp-plugin-stamdata-blueprint-availability">
						<p class="description" id="wp-plugin-stamdata-no-selected-fields" <?php if ( ! empty( $field_ids ) ) : ?>style="display:none;"<?php endif; ?>><?php esc_html_e( 'Select at least one veld above to manage its time slots.', 'wp-plugin-stamdata' ); ?></p>
						<?php foreach ( $fields as $field ) : ?>
							<?php
							$field_id    = (int) $field['id'];
							$location_id = (int) $field['location_id'];
							$location    = isset( $locations_by_id[ $location_id ] ) ? $locations_by_id[ $location_id ] : null;
							?>
							<div class="wp-plugin-stamdata-field-availability" data-field-id="<?php echo esc_attr( $field_id ); ?>" <?php if ( ! in_array( $field_id, $field_ids, true ) ) : ?>style="display:none;"<?php endif; ?>>
								<div style="padding:16px 0;border-top:1px solid #ddd;">
									<h3 style="margin:0 0 4px;"><?php echo esc_html( $field['name'] ); ?></h3>
									<p class="description" style="margin:0 0 12px;">
										<?php
										printf(
											/* translators: %s: location name */
											esc_html__( 'Locatie: %s', 'wp-plugin-stamdata' ),
											esc_html( $location['name'] ?? '' )
										);
										?>
									</p>
									<?php foreach ( $this->get_day_options() as $day_value => $day_label ) : ?>
										<div class="wp-plugin-stamdata-availability-day" style="padding:12px 0 0 16px;">
											<h4 style="margin:0 0 12px;"><?php echo esc_html( $day_label ); ?></h4>
											<div class="wp-plugin-stamdata-availability-day-rows" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-day="<?php echo esc_attr( $day_value ); ?>">
												<?php if ( ! empty( $availability_by_field[ $field_id ][ $day_value ] ) ) : ?>
													<?php foreach ( $availability_by_field[ $field_id ][ $day_value ] as $row ) : ?>
														<?php echo $this->get_availability_row_html( $field_id, $day_value, $row['start_time'], $row['end_time'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
													<?php endforeach; ?>
												<?php endif; ?>
											</div>
											<p class="description wp-plugin-stamdata-availability-empty" <?php if ( ! empty( $availability_by_field[ $field_id ][ $day_value ] ) ) : ?>style="display:none;"<?php endif; ?>><?php esc_html_e( 'No time slots for this weekday yet.', 'wp-plugin-stamdata' ); ?></p>
											<p><button type="button" class="button wp-plugin-stamdata-add-availability-row" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-day="<?php echo esc_attr( $day_value ); ?>"><?php esc_html_e( 'Add time slot', 'wp-plugin-stamdata' ); ?></button></p>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					<?php submit_button( $blueprint ? __( 'Update blueprint', 'wp-plugin-stamdata' ) : __( 'Add blueprint', 'wp-plugin-stamdata' ) ); ?>
					<a href="<?php echo esc_url( $this->get_list_url() ); ?>" class="button button-secondary"><?php esc_html_e( 'Back to blueprints', 'wp-plugin-stamdata' ); ?></a>
				</form>
				<script>
					document.addEventListener('DOMContentLoaded', function () {
						var container = document.getElementById('wp-plugin-stamdata-blueprint-availability');
						var weekTypeField = document.getElementById('blueprint_week_type');
						var weekNumberRow = document.getElementById('wp-plugin-stamdata-week-number-row');
						var weekNumberField = document.getElementById('blueprint_week_number');

						if (!container) {
							return;
						}

						var rowTemplates = <?php echo wp_json_encode( $this->get_availability_row_templates( $fields ) ); ?>;
						var defaultRowTemplates = <?php echo wp_json_encode( $this->get_default_availability_row_templates( $fields ) ); ?>;
						var fieldCheckboxes = document.querySelectorAll('.wp-plugin-stamdata-field-checkbox');
						var noSelectedFields = document.getElementById('wp-plugin-stamdata-no-selected-fields');

						function updateWeekNumberVisibility() {
							if (!weekTypeField || !weekNumberRow || !weekNumberField) {
								return;
							}

							var isException = weekTypeField.value === 'exception';

							weekNumberRow.style.display = isException ? '' : 'none';
							weekNumberField.disabled = !isException;

							if (!isException) {
								weekNumberField.value = '0';
							}
						}

						function toggleFieldAvailability(fieldId, isSelected) {
							var fieldSection = container.querySelector('.wp-plugin-stamdata-field-availability[data-field-id="' + fieldId + '"]');

							if (!fieldSection) {
								return;
							}

							fieldSection.style.display = isSelected ? '' : 'none';
						}

						function ensureDefaultAvailabilityForField(fieldId) {
							[1, 2, 3, 4, 5].forEach(function (day) {
								var templateKey = fieldId + ':' + day;
								var dayContainer = container.querySelector('.wp-plugin-stamdata-availability-day-rows[data-field-id="' + fieldId + '"][data-day="' + day + '"]');

								if (!dayContainer || dayContainer.querySelector('.wp-plugin-stamdata-availability-row') || !defaultRowTemplates[templateKey]) {
									return;
								}

								dayContainer.insertAdjacentHTML('beforeend', defaultRowTemplates[templateKey]);

								var daySection = dayContainer.closest('.wp-plugin-stamdata-availability-day');
								var emptyState = daySection ? daySection.querySelector('.wp-plugin-stamdata-availability-empty') : null;

								if (emptyState) {
									emptyState.style.display = 'none';
								}
							});
						}

						function updateEmptyBlueprintState() {
							var hasSelection = false;

							fieldCheckboxes.forEach(function (checkbox) {
								if (checkbox.checked) {
									hasSelection = true;
								}
							});

							if (noSelectedFields) {
								noSelectedFields.style.display = hasSelection ? 'none' : '';
							}
						}

						fieldCheckboxes.forEach(function (checkbox) {
							checkbox.addEventListener('change', function () {
								toggleFieldAvailability(this.value, this.checked);

								if (this.checked) {
									ensureDefaultAvailabilityForField(this.value);
								}

								updateEmptyBlueprintState();
							});
						});

						if (weekTypeField) {
							weekTypeField.addEventListener('change', updateWeekNumberVisibility);
						}

						container.addEventListener('click', function (event) {
							if (event.target.classList.contains('wp-plugin-stamdata-add-availability-row')) {
								var day = event.target.getAttribute('data-day');
								var fieldId = event.target.getAttribute('data-field-id');
								var templateKey = fieldId + ':' + day;
								var dayContainer = container.querySelector('.wp-plugin-stamdata-availability-day-rows[data-field-id="' + fieldId + '"][data-day="' + day + '"]');
								var emptyState = event.target.closest('.wp-plugin-stamdata-availability-day').querySelector('.wp-plugin-stamdata-availability-empty');

								if (!dayContainer || !rowTemplates[templateKey]) {
									return;
								}

								dayContainer.insertAdjacentHTML('beforeend', rowTemplates[templateKey]);

								if (emptyState) {
									emptyState.style.display = 'none';
								}

								return;
							}

							if (!event.target.classList.contains('wp-plugin-stamdata-remove-availability-row')) {
								return;
							}

							var availabilityDay = event.target.closest('.wp-plugin-stamdata-availability-day');
							var rowsContainer = event.target.closest('.wp-plugin-stamdata-availability-day-rows');
							var row = event.target.closest('.wp-plugin-stamdata-availability-row');

							if (!availabilityDay || !rowsContainer || !row) {
								return;
							}

							row.remove();

							if (!rowsContainer.querySelector('.wp-plugin-stamdata-availability-row')) {
								var emptyState = availabilityDay.querySelector('.wp-plugin-stamdata-availability-empty');

								if (emptyState) {
									emptyState.style.display = '';
								}
							}
						});

						updateWeekNumberVisibility();
						updateEmptyBlueprintState();
					});
				</script>
			<?php endif; ?>
		</div>
		<?php
	}

	public function handle_request() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';

		if ( 'wp-plugin-stamdata-blueprints' !== $page ) {
			return;
		}

		if ( isset( $_POST['stamdata_action'] ) && 'save_blueprint' === sanitize_key( wp_unslash( $_POST['stamdata_action'] ) ) ) {
			$this->handle_save();
		}

		if ( isset( $_GET['action'] ) && 'delete' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
			$this->handle_delete();
		}
	}

	private function handle_save() {
		check_admin_referer( 'wp_plugin_stamdata_save_blueprint', 'wp_plugin_stamdata_blueprint_nonce' );

		$blueprint_id = isset( $_POST['blueprint_id'] ) ? absint( $_POST['blueprint_id'] ) : 0;
		$week_type    = isset( $_POST['week_type'] ) ? sanitize_key( wp_unslash( $_POST['week_type'] ) ) : 'default';
		$week_number  = isset( $_POST['week_number'] ) ? absint( $_POST['week_number'] ) : 0;
		$field_ids    = isset( $_POST['field_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['field_ids'] ) ) : array();
		$availability_rows = $this->sanitize_availability_rows(
			isset( $_POST['availability_field_id'] ) ? (array) wp_unslash( $_POST['availability_field_id'] ) : array(),
			isset( $_POST['availability_day_of_week'] ) ? (array) wp_unslash( $_POST['availability_day_of_week'] ) : array(),
			isset( $_POST['availability_start_time'] ) ? (array) wp_unslash( $_POST['availability_start_time'] ) : array(),
			isset( $_POST['availability_end_time'] ) ? (array) wp_unslash( $_POST['availability_end_time'] ) : array(),
			$field_ids
		);
		$data         = array(
			'name'         => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'week_type'    => in_array( $week_type, array( 'default', 'exception' ), true ) ? $week_type : 'default',
			'week_number'  => 'exception' === $week_type ? $week_number : 0,
			'notes'        => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
			'data_version' => stamdata_get_active_data_version(),
		);

		if ( '' === $data['name'] ) {
			$this->redirect_to_editor_with_notice( 'invalid', $blueprint_id );
		}

		if ( 'exception' === $data['week_type'] && ( $data['week_number'] < 1 || $data['week_number'] > 53 ) ) {
			$this->redirect_to_editor_with_notice( 'invalid_week', $blueprint_id );
		}

		if ( 'default' === $data['week_type'] && 0 !== $data['week_number'] ) {
			$data['week_number'] = 0;
		}

		$valid_fields    = $this->field_repository->get_all();
		$field_map       = array();

		foreach ( $valid_fields as $field ) {
			$field_map[ (int) $field['id'] ] = (int) $field['location_id'];
		}

		$field_ids = array_values(
			array_filter(
				$field_ids,
				function ( $field_id ) use ( $field_map ) {
					return isset( $field_map[ $field_id ] );
				}
			)
		);
		$location_ids = array_values( array_unique( array_intersect_key( $field_map, array_flip( $field_ids ) ) ) );

		$result = $blueprint_id ? $this->repository->update( $blueprint_id, $data ) : $this->repository->create( $data );

		if ( is_wp_error( $result ) ) {
			$this->redirect_to_editor_with_notice( 'error', $blueprint_id );
		}

		$blueprint_id = $blueprint_id ? $blueprint_id : (int) $result;
		$this->repository->sync_locations( $blueprint_id, $location_ids, $data['data_version'] );
		$this->repository->sync_fields( $blueprint_id, $field_ids, $data['data_version'] );
		$this->availability_repository->sync_for_blueprint(
			$blueprint_id,
			$availability_rows,
			$data['week_type'],
			'exception' === $data['week_type'] ? (int) $data['week_number'] : null,
			$data['data_version']
		);

		$this->redirect_to_list_with_notice( $blueprint_id && isset( $_POST['blueprint_id'] ) && absint( $_POST['blueprint_id'] ) > 0 ? 'updated' : 'created' );
	}

	private function handle_delete() {
		$blueprint_id = isset( $_GET['blueprint_id'] ) ? absint( $_GET['blueprint_id'] ) : 0;
		check_admin_referer( 'wp_plugin_stamdata_delete_blueprint_' . $blueprint_id );

		$result = $this->repository->delete( $blueprint_id );

		if ( is_wp_error( $result ) ) {
			$this->redirect_to_list_with_notice( 'error' );
		}

		$this->redirect_to_list_with_notice( 'deleted' );
	}

	private function render_notices() {
		if ( ! isset( $_GET['message'] ) ) {
			return;
		}

		$message = sanitize_key( wp_unslash( $_GET['message'] ) );
		$type    = 'success';
		$text    = '';

		if ( 'created' === $message ) { $text = __( 'Blueprint created.', 'wp-plugin-stamdata' ); }
		elseif ( 'updated' === $message ) { $text = __( 'Blueprint updated.', 'wp-plugin-stamdata' ); }
		elseif ( 'deleted' === $message ) { $text = __( 'Blueprint deleted.', 'wp-plugin-stamdata' ); }
		elseif ( 'invalid' === $message ) { $type = 'error'; $text = __( 'Please provide valid blueprint data.', 'wp-plugin-stamdata' ); }
		elseif ( 'invalid_week' === $message ) { $type = 'error'; $text = __( 'Please choose a valid week number for an exception blueprint.', 'wp-plugin-stamdata' ); }
		elseif ( 'error' === $message ) { $type = 'error'; $text = __( 'Something went wrong while saving the blueprint.', 'wp-plugin-stamdata' ); }
		elseif ( 'not_found' === $message ) { $type = 'error'; $text = __( 'The requested blueprint could not be found.', 'wp-plugin-stamdata' ); }
		if ( '' === $text ) {
			return;
		}

		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
	}

	private function get_list_url() { return add_query_arg( array( 'page' => 'wp-plugin-stamdata-blueprints' ), admin_url( 'admin.php' ) ); }
	private function get_add_url() { return add_query_arg( array( 'page' => 'wp-plugin-stamdata-blueprints', 'view' => 'edit' ), admin_url( 'admin.php' ) ); }
	private function get_edit_url( $blueprint_id ) { return add_query_arg( array( 'page' => 'wp-plugin-stamdata-blueprints', 'view' => 'edit', 'blueprint_id' => $blueprint_id ), admin_url( 'admin.php' ) ); }
	private function get_delete_url( $blueprint_id ) { return wp_nonce_url( add_query_arg( array( 'page' => 'wp-plugin-stamdata-blueprints', 'action' => 'delete', 'blueprint_id' => $blueprint_id ), admin_url( 'admin.php' ) ), 'wp_plugin_stamdata_delete_blueprint_' . $blueprint_id ); }
	private function redirect_to_list_with_notice( $message ) { wp_safe_redirect( add_query_arg( array( 'page' => 'wp-plugin-stamdata-blueprints', 'message' => $message ), admin_url( 'admin.php' ) ) ); exit; }
	private function redirect_to_editor_with_notice( $message, $blueprint_id = 0 ) { $args = array( 'page' => 'wp-plugin-stamdata-blueprints', 'view' => 'edit', 'message' => $message ); if ( $blueprint_id > 0 ) { $args['blueprint_id'] = $blueprint_id; } wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) ); exit; }
	private function get_day_options() { return array( 1 => __( 'Monday', 'wp-plugin-stamdata' ), 2 => __( 'Tuesday', 'wp-plugin-stamdata' ), 3 => __( 'Wednesday', 'wp-plugin-stamdata' ), 4 => __( 'Thursday', 'wp-plugin-stamdata' ), 5 => __( 'Friday', 'wp-plugin-stamdata' ), 6 => __( 'Saturday', 'wp-plugin-stamdata' ), 7 => __( 'Sunday', 'wp-plugin-stamdata' ) ); }

	private function group_availability_rows_by_field_and_day( array $rows ) {
		$grouped = array();

		foreach ( $rows as $row ) {
			$field_id = isset( $row['field_id'] ) ? (int) $row['field_id'] : 0;
			$day      = isset( $row['day_of_week'] ) ? (int) $row['day_of_week'] : 0;

			if ( $field_id < 1 || $day < 1 || $day > 7 ) {
				continue;
			}

			if ( ! isset( $grouped[ $field_id ] ) ) {
				$grouped[ $field_id ] = array();
			}

			if ( ! isset( $grouped[ $field_id ][ $day ] ) ) {
				$grouped[ $field_id ][ $day ] = array();
			}

			$grouped[ $field_id ][ $day ][] = $row;
		}

		return $grouped;
	}

	private function sanitize_availability_rows( array $field_ids, array $days, array $start_times, array $end_times, array $selected_field_ids ) {
		$rows = array();
		$max  = max( count( $field_ids ), count( $days ), count( $start_times ), count( $end_times ) );
		$selected_field_ids = array_map( 'intval', $selected_field_ids );

		for ( $index = 0; $index < $max; $index++ ) {
			$field_id   = isset( $field_ids[ $index ] ) ? absint( $field_ids[ $index ] ) : 0;
			$day        = isset( $days[ $index ] ) ? absint( $days[ $index ] ) : 0;
			$start_time = isset( $start_times[ $index ] ) ? sanitize_text_field( $start_times[ $index ] ) : '';
			$end_time   = isset( $end_times[ $index ] ) ? sanitize_text_field( $end_times[ $index ] ) : '';

			if ( $field_id < 1 || ! in_array( $field_id, $selected_field_ids, true ) || $day < 1 || $day > 7 || '' === $start_time || '' === $end_time || $start_time >= $end_time ) {
				continue;
			}

			$rows[] = array(
				'field_id'    => $field_id,
				'day_of_week' => $day,
				'start_time'  => $start_time,
				'end_time'    => $end_time,
			);
		}

		return $rows;
	}

	private function get_availability_row_templates( array $fields ) {
		$templates = array();

		foreach ( $fields as $field ) {
			$field_id = (int) $field['id'];

			foreach ( array_keys( $this->get_day_options() ) as $day ) {
				$templates[ $field_id . ':' . $day ] = $this->get_availability_row_html( $field_id, $day );
			}
		}

		return $templates;
	}

	private function get_default_availability_row_templates( array $fields ) {
		$templates = array();

		foreach ( $fields as $field ) {
			$field_id = (int) $field['id'];

			foreach ( array( 1, 2, 3, 4, 5 ) as $day ) {
				$templates[ $field_id . ':' . $day ] = $this->get_availability_row_html( $field_id, $day, '17:00', '22:30' );
			}
		}

		return $templates;
	}

	private function get_availability_row_html( $field_id, $day_of_week, $start_time = '18:00', $end_time = '19:00' ) {
		$start_time = $this->normalize_time_input_value( $start_time );
		$end_time   = $this->normalize_time_input_value( $end_time );

		ob_start();
		?>
		<div class="wp-plugin-stamdata-availability-row" style="display:flex;gap:12px;align-items:flex-end;margin-bottom:12px;">
			<input type="hidden" name="availability_field_id[]" value="<?php echo esc_attr( $field_id ); ?>" />
			<input type="hidden" name="availability_day_of_week[]" value="<?php echo esc_attr( $day_of_week ); ?>" />
			<div>
				<label><?php esc_html_e( 'Start time', 'wp-plugin-stamdata' ); ?></label><br />
				<input type="time" name="availability_start_time[]" value="<?php echo esc_attr( $start_time ); ?>" />
			</div>
			<div>
				<label><?php esc_html_e( 'End time', 'wp-plugin-stamdata' ); ?></label><br />
				<input type="time" name="availability_end_time[]" value="<?php echo esc_attr( $end_time ); ?>" />
			</div>
			<div>
				<button type="button" class="button button-secondary wp-plugin-stamdata-remove-availability-row"><?php esc_html_e( 'Remove', 'wp-plugin-stamdata' ); ?></button>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function render_blueprint_readonly_schedule( array $field_ids, array $availability_by_field, array $fields_by_id, array $locations_by_id ) {
		if ( empty( $field_ids ) ) {
			echo '<p class="description">' . esc_html__( 'No velden selected.', 'wp-plugin-stamdata' ) . '</p>';
			return;
		}

		foreach ( $field_ids as $field_id ) {
			if ( ! isset( $fields_by_id[ $field_id ] ) ) {
				continue;
			}

			$field       = $fields_by_id[ $field_id ];
			$location_id = (int) $field['location_id'];
			$location    = isset( $locations_by_id[ $location_id ] ) ? $locations_by_id[ $location_id ] : null;

			echo '<div style="padding:12px 0;border-top:1px solid #ddd;">';
			echo '<strong>' . esc_html( $field['name'] ) . '</strong>';

			if ( $location ) {
				echo '<p class="description" style="margin:4px 0 12px;">' . esc_html( sprintf( __( 'Locatie: %s', 'wp-plugin-stamdata' ), $location['name'] ) ) . '</p>';
			}

			$has_any_slots = false;

			foreach ( $this->get_day_options() as $day_value => $day_label ) {
				$slots = isset( $availability_by_field[ $field_id ][ $day_value ] ) ? $availability_by_field[ $field_id ][ $day_value ] : array();

				echo '<div style="padding:0 0 8px 16px;">';
				echo '<span style="display:inline-block;min-width:100px;"><strong>' . esc_html( $day_label ) . '</strong></span>';

				if ( empty( $slots ) ) {
					echo '<span class="description">' . esc_html__( 'No slots', 'wp-plugin-stamdata' ) . '</span>';
				} else {
					$has_any_slots = true;
					$slot_labels   = array();

					foreach ( $slots as $slot ) {
						$slot_labels[] = $this->normalize_time_input_value( $slot['start_time'] ) . ' - ' . $this->normalize_time_input_value( $slot['end_time'] );
					}

					echo esc_html( implode( ', ', $slot_labels ) );
				}

				echo '</div>';
			}

			if ( ! $has_any_slots ) {
				echo '<p class="description" style="margin:8px 0 0 16px;">' . esc_html__( 'No availability slots configured for this veld.', 'wp-plugin-stamdata' ) . '</p>';
			}

			echo '</div>';
		}
	}

	private function normalize_time_input_value( $time_value ) {
		$time_value = (string) $time_value;

		if ( preg_match( '/^\d{2}:\d{2}$/', $time_value ) ) {
			return $time_value;
		}

		if ( preg_match( '/^\d{2}:\d{2}:\d{2}$/', $time_value ) ) {
			return substr( $time_value, 0, 5 );
		}

		return '';
	}
}
