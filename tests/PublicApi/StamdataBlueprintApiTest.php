<?php
/**
 * Tests for blueprint public API helpers.
 */

final class StamdataBlueprintApiTest extends StamdataPublicApiTestCase {

	public function test_it_returns_a_blueprint_by_id(): void {
		$GLOBALS['wpdb']->blueprint_rows['test'][21] = array(
			'id'           => 21,
			'name'         => 'Afwijkend Week 12',
			'week_type'    => 'exception',
			'week_number'  => 12,
			'notes'        => 'Schoolvakantie',
			'data_version' => 'test',
		);

		$blueprint = stamdata_get_blueprint( 21, 'test' );

		$this->assertIsArray( $blueprint );
		$this->assertSame( 21, $blueprint['id'] );
		$this->assertSame( 'exception', $blueprint['week_type'] );
		$this->assertSame( 12, $blueprint['week_number'] );
	}

	public function test_it_returns_all_blueprints_for_the_active_dataset_sorted_by_week_type_week_number_and_name(): void {
		update_option( 'wp_plugin_stamdata_active_data_version', 'test' );

		$GLOBALS['wpdb']->blueprint_rows['test'][1] = array(
			'id'           => 1,
			'name'         => 'Standaard',
			'week_type'    => 'default',
			'week_number'  => 0,
			'data_version' => 'test',
		);
		$GLOBALS['wpdb']->blueprint_rows['test'][3] = array(
			'id'           => 3,
			'name'         => 'Week 14',
			'week_type'    => 'exception',
			'week_number'  => 14,
			'data_version' => 'test',
		);
		$GLOBALS['wpdb']->blueprint_rows['test'][2] = array(
			'id'           => 2,
			'name'         => 'Week 12',
			'week_type'    => 'exception',
			'week_number'  => 12,
			'data_version' => 'test',
		);

		$blueprints = stamdata_get_blueprints();

		$this->assertSame( array( 1, 2, 3 ), array_column( $blueprints, 'id' ) );
	}

	public function test_it_returns_the_exception_blueprint_for_a_specific_week_when_present(): void {
		$GLOBALS['wpdb']->blueprint_rows['live'][1] = array(
			'id'           => 1,
			'name'         => 'Standaard',
			'week_type'    => 'default',
			'week_number'  => 0,
			'data_version' => 'live',
		);
		$GLOBALS['wpdb']->blueprint_rows['live'][2] = array(
			'id'           => 2,
			'name'         => 'Week 12',
			'week_type'    => 'exception',
			'week_number'  => 12,
			'data_version' => 'live',
		);

		$blueprint = stamdata_get_blueprint_for_week( 12, 'live' );

		$this->assertSame( 2, $blueprint['id'] );
		$this->assertSame( 'exception', $blueprint['week_type'] );
	}

	public function test_it_falls_back_to_the_default_blueprint_when_no_exception_exists(): void {
		$GLOBALS['wpdb']->blueprint_rows['live'][1] = array(
			'id'           => 1,
			'name'         => 'Standaard',
			'week_type'    => 'default',
			'week_number'  => 0,
			'data_version' => 'live',
		);

		$blueprint = stamdata_get_blueprint_for_week( 33, 'live' );

		$this->assertSame( 1, $blueprint['id'] );
		$this->assertSame( 'default', $blueprint['week_type'] );
	}

	public function test_it_returns_blueprint_location_ids_sorted_ascending(): void {
		$GLOBALS['wpdb']->blueprint_location_rows['live'] = array(
			array(
				'blueprint_id' => 8,
				'location_id'  => 5,
				'data_version' => 'live',
			),
			array(
				'blueprint_id' => 8,
				'location_id'  => 2,
				'data_version' => 'live',
			),
			array(
				'blueprint_id' => 9,
				'location_id'  => 1,
				'data_version' => 'live',
			),
		);

		$this->assertSame( array( 2, 5 ), stamdata_get_blueprint_location_ids( 8, 'live' ) );
	}

	public function test_it_returns_blueprint_field_ids_sorted_ascending(): void {
		$GLOBALS['wpdb']->blueprint_field_rows['live'] = array(
			array(
				'blueprint_id' => 8,
				'field_id'     => 9,
				'data_version' => 'live',
			),
			array(
				'blueprint_id' => 8,
				'field_id'     => 3,
				'data_version' => 'live',
			),
			array(
				'blueprint_id' => 7,
				'field_id'     => 1,
				'data_version' => 'live',
			),
		);

		$this->assertSame( array( 3, 9 ), stamdata_get_blueprint_field_ids( 8, 'live' ) );
	}

	public function test_it_returns_default_blueprint_availability_rows(): void {
		$GLOBALS['wpdb']->blueprint_availability_rows['live'] = array(
			array(
				'id'           => 1,
				'blueprint_id' => 2,
				'field_id'     => 10,
				'week_type'    => 'default',
				'week_number'  => null,
				'day_of_week'  => 1,
				'start_time'   => '18:00:00',
				'end_time'     => '19:30:00',
				'data_version' => 'live',
			),
			array(
				'id'           => 2,
				'blueprint_id' => 2,
				'field_id'     => 10,
				'week_type'    => 'default',
				'week_number'  => null,
				'day_of_week'  => 1,
				'start_time'   => '20:00:00',
				'end_time'     => '21:30:00',
				'data_version' => 'live',
			),
			array(
				'id'           => 3,
				'blueprint_id' => 2,
				'field_id'     => 12,
				'week_type'    => 'exception',
				'week_number'  => 12,
				'day_of_week'  => 2,
				'start_time'   => '19:00:00',
				'end_time'     => '20:30:00',
				'data_version' => 'live',
			),
		);

		$rows = stamdata_get_blueprint_availability( 2, null, 'live' );

		$this->assertCount( 2, $rows );
		$this->assertSame( array( 1, 2 ), array_column( $rows, 'id' ) );
	}

	public function test_it_returns_availability_rows_for_a_single_field(): void {
		$GLOBALS['wpdb']->blueprint_availability_rows['live'] = array(
			array(
				'id'           => 1,
				'blueprint_id' => 2,
				'field_id'     => 10,
				'week_type'    => 'default',
				'week_number'  => null,
				'day_of_week'  => 1,
				'start_time'   => '18:00:00',
				'end_time'     => '19:30:00',
				'data_version' => 'live',
			),
			array(
				'id'           => 2,
				'blueprint_id' => 2,
				'field_id'     => 11,
				'week_type'    => 'default',
				'week_number'  => null,
				'day_of_week'  => 1,
				'start_time'   => '18:00:00',
				'end_time'     => '19:30:00',
				'data_version' => 'live',
			),
		);

		$rows = stamdata_get_blueprint_availability_for_field( 2, 10, null, 'live' );

		$this->assertCount( 1, $rows );
		$this->assertSame( 10, $rows[0]['field_id'] );
	}

	public function test_it_returns_timeslots_for_a_weekday_using_the_effective_blueprint(): void {
		$GLOBALS['wpdb']->blueprint_rows['live'][1] = array(
			'id'           => 1,
			'name'         => 'Standaard',
			'week_type'    => 'default',
			'week_number'  => 0,
			'data_version' => 'live',
		);
		$GLOBALS['wpdb']->blueprint_rows['live'][2] = array(
			'id'           => 2,
			'name'         => 'Week 12',
			'week_type'    => 'exception',
			'week_number'  => 12,
			'data_version' => 'live',
		);
		$GLOBALS['wpdb']->blueprint_availability_rows['live'] = array(
			array(
				'id'           => 11,
				'blueprint_id' => 2,
				'field_id'     => 8,
				'week_type'    => 'exception',
				'week_number'  => 12,
				'day_of_week'  => 1,
				'start_time'   => '17:00:00',
				'end_time'     => '18:30:00',
				'data_version' => 'live',
			),
			array(
				'id'           => 12,
				'blueprint_id' => 2,
				'field_id'     => 8,
				'week_type'    => 'exception',
				'week_number'  => 12,
				'day_of_week'  => 2,
				'start_time'   => '19:00:00',
				'end_time'     => '20:30:00',
				'data_version' => 'live',
			),
		);

		$rows = stamdata_get_blueprint_timeslots_for_day( 12, 0, 'live' );

		$this->assertCount( 1, $rows );
		$this->assertSame( 11, $rows[0]['id'] );
		$this->assertSame( 1, $rows[0]['day_of_week'] );
	}

	public function test_it_returns_an_empty_array_for_invalid_day_numbers(): void {
		$this->assertSame( array(), stamdata_get_blueprint_timeslots_for_day( 12, 9, 'live' ) );
	}
}
