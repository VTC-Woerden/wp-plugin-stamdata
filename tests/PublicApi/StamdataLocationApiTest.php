<?php
/**
 * Tests for location public API helpers.
 */

final class StamdataLocationApiTest extends StamdataPublicApiTestCase {

	public function test_it_returns_a_location_for_an_explicit_data_version(): void {
		$GLOBALS['wpdb']->location_rows['test'][4] = array(
			'id'           => 4,
			'name'         => 'Sportcentrum De Kroon',
			'address'      => 'Kroonlaan 10',
			'city'         => 'Woerden',
			'data_version' => 'test',
		);

		$location = stamdata_get_location( 4, 'test' );

		$this->assertIsArray( $location );
		$this->assertSame( 4, $location['id'] );
		$this->assertSame( 'Sportcentrum De Kroon', $location['name'] );
		$this->assertSame( 'Woerden', $location['city'] );
	}

	public function test_it_returns_null_when_the_location_does_not_exist(): void {
		$this->assertNull( stamdata_get_location( 999, 'live' ) );
	}

	public function test_it_returns_all_locations_for_the_active_dataset_sorted_by_name(): void {
		update_option( 'wp_plugin_stamdata_active_data_version', 'test' );

		$GLOBALS['wpdb']->location_rows['test'][8] = array(
			'id'           => 8,
			'name'         => 'Sporthal B',
			'data_version' => 'test',
		);
		$GLOBALS['wpdb']->location_rows['test'][3] = array(
			'id'           => 3,
			'name'         => 'Sporthal A',
			'data_version' => 'test',
		);
		$GLOBALS['wpdb']->location_rows['live'][1] = array(
			'id'           => 1,
			'name'         => 'Live Sporthal',
			'data_version' => 'live',
		);

		$locations = stamdata_get_locations();

		$this->assertSame( array( 3, 8 ), array_column( $locations, 'id' ) );
		$this->assertSame( array( 'Sporthal A', 'Sporthal B' ), array_column( $locations, 'name' ) );
	}
}
