<?php
/**
 * Tests for field public API helpers.
 */

final class StamdataFieldApiTest extends StamdataPublicApiTestCase {

	public function test_it_returns_a_field_for_an_explicit_data_version(): void {
		$GLOBALS['wpdb']->field_rows['test'][11] = array(
			'id'           => 11,
			'location_id'  => 4,
			'name'         => 'Veld 2',
			'sort_order'   => 20,
			'data_version' => 'test',
		);

		$field = stamdata_get_field( 11, 'test' );

		$this->assertIsArray( $field );
		$this->assertSame( 11, $field['id'] );
		$this->assertSame( 4, $field['location_id'] );
		$this->assertSame( 'Veld 2', $field['name'] );
	}

	public function test_it_returns_all_fields_with_location_names_for_the_active_dataset(): void {
		update_option( 'wp_plugin_stamdata_active_data_version', 'test' );

		$GLOBALS['wpdb']->location_rows['test'][1] = array(
			'id'           => 1,
			'name'         => 'Locatie A',
			'data_version' => 'test',
		);
		$GLOBALS['wpdb']->location_rows['test'][2] = array(
			'id'           => 2,
			'name'         => 'Locatie B',
			'data_version' => 'test',
		);
		$GLOBALS['wpdb']->field_rows['test'][10] = array(
			'id'           => 10,
			'location_id'  => 2,
			'name'         => 'Veld 2',
			'sort_order'   => 20,
			'data_version' => 'test',
		);
		$GLOBALS['wpdb']->field_rows['test'][9] = array(
			'id'           => 9,
			'location_id'  => 1,
			'name'         => 'Veld 1',
			'sort_order'   => 10,
			'data_version' => 'test',
		);

		$fields = stamdata_get_fields();

		$this->assertSame( array( 9, 10 ), array_column( $fields, 'id' ) );
		$this->assertSame( array( 'Locatie A', 'Locatie B' ), array_column( $fields, 'location_name' ) );
	}

	public function test_it_returns_only_fields_for_the_requested_location(): void {
		$GLOBALS['wpdb']->field_rows['live'][5] = array(
			'id'           => 5,
			'location_id'  => 3,
			'name'         => 'Veld B',
			'sort_order'   => 2,
			'data_version' => 'live',
		);
		$GLOBALS['wpdb']->field_rows['live'][4] = array(
			'id'           => 4,
			'location_id'  => 3,
			'name'         => 'Veld A',
			'sort_order'   => 1,
			'data_version' => 'live',
		);
		$GLOBALS['wpdb']->field_rows['live'][8] = array(
			'id'           => 8,
			'location_id'  => 9,
			'name'         => 'Other Field',
			'sort_order'   => 1,
			'data_version' => 'live',
		);

		$fields = stamdata_get_fields_by_location( 3, 'live' );

		$this->assertSame( array( 4, 5 ), array_column( $fields, 'id' ) );
		$this->assertSame( array( 'Veld A', 'Veld B' ), array_column( $fields, 'name' ) );
	}
}
