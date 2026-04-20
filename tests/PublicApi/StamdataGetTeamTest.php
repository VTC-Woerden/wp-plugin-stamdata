<?php
/**
 * Tests for the stamdata_get_team() public API helper.
 */

use PHPUnit\Framework\TestCase;

final class StamdataGetTeamTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['wp_plugin_stamdata_test_options'] = array(
			'wp_plugin_stamdata_active_data_version' => 'live',
		);

		$GLOBALS['wpdb'] = new WP_Plugin_Stamdata_Test_WPDB();
	}

	public function test_it_returns_the_team_for_an_explicit_data_version(): void {
		$GLOBALS['wpdb']->team_rows['test'][15] = array(
			'id'              => 15,
			'name'            => 'VTC Woerden DS 5',
			'short_name'      => 'DS 5',
			'sortable_rank'   => '001-05',
			'image_id'        => null,
			'external_source' => 'nevobo',
			'external_id'     => 'team-15',
			'external_api_id' => '/competitie/teams/ckl9x7n/dames/5',
			'data_version'    => 'test',
			'created_at'      => '2026-04-20 12:00:00',
			'updated_at'      => '2026-04-20 12:00:00',
		);

		$team = stamdata_get_team( 15, 'test' );

		$this->assertIsArray( $team );
		$this->assertSame( 15, $team['id'] );
		$this->assertSame( 'VTC Woerden DS 5', $team['name'] );
		$this->assertSame( 'DS 5', $team['short_name'] );
		$this->assertSame( '001-05', $team['sortable_rank'] );
		$this->assertSame( 'test', $team['data_version'] );
	}

	public function test_it_uses_the_active_data_version_when_none_is_provided(): void {
		update_option( 'wp_plugin_stamdata_active_data_version', 'test' );

		$GLOBALS['wpdb']->team_rows['live'][7] = array(
			'id'           => 7,
			'name'         => 'Live Team',
			'data_version' => 'live',
		);
		$GLOBALS['wpdb']->team_rows['test'][7] = array(
			'id'              => 7,
			'name'            => 'Test Team',
			'short_name'      => 'Team',
			'sortable_rank'   => '002-07',
			'image_id'        => null,
			'external_source' => 'nevobo',
			'external_id'     => 'team-7',
			'external_api_id' => '/competitie/teams/ckl9x7n/heren/7',
			'data_version'    => 'test',
			'created_at'      => '2026-04-20 12:00:00',
			'updated_at'      => '2026-04-20 12:00:00',
		);

		$team = stamdata_get_team( 7 );

		$this->assertIsArray( $team );
		$this->assertSame( 'Test Team', $team['name'] );
		$this->assertSame( 'test', $team['data_version'] );
	}

	public function test_it_returns_null_when_the_team_does_not_exist(): void {
		$team = stamdata_get_team( 999, 'live' );

		$this->assertNull( $team );
	}
}
