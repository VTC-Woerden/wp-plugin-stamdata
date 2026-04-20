<?php
/**
 * Tests for team collection public API helpers.
 */

final class StamdataTeamCollectionApiTest extends StamdataPublicApiTestCase {

	public function test_it_returns_all_teams_for_the_active_dataset_sorted_by_sortable_rank_then_name(): void {
		update_option( 'wp_plugin_stamdata_active_data_version', 'test' );

		$GLOBALS['wpdb']->team_rows['test'][4] = array(
			'id'            => 4,
			'name'          => 'Team B',
			'short_name'    => 'm B',
			'sortable_rank' => '002-02',
			'data_version'  => 'test',
		);
		$GLOBALS['wpdb']->team_rows['test'][2] = array(
			'id'            => 2,
			'name'          => 'Team A',
			'short_name'    => 'm A',
			'sortable_rank' => '001-01',
			'data_version'  => 'test',
		);
		$GLOBALS['wpdb']->team_rows['live'][1] = array(
			'id'            => 1,
			'name'          => 'Live Team',
			'short_name'    => 'Team',
			'sortable_rank' => '999-99',
			'data_version'  => 'live',
		);

		$teams = stamdata_get_teams();

		$this->assertSame( array( 2, 4 ), array_column( $teams, 'id' ) );
		$this->assertSame( array( 'Team A', 'Team B' ), array_column( $teams, 'name' ) );
	}
}
