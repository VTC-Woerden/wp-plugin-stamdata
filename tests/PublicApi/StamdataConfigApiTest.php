<?php
/**
 * Tests for configuration-style public API helpers.
 */

final class StamdataConfigApiTest extends StamdataPublicApiTestCase {

	public function test_it_returns_the_active_data_version_and_falls_back_to_live_for_invalid_values(): void {
		update_option( 'wp_plugin_stamdata_active_data_version', 'test' );
		$this->assertSame( 'test', stamdata_get_active_data_version() );

		update_option( 'wp_plugin_stamdata_active_data_version', 'staging' );
		$this->assertSame( 'live', stamdata_get_active_data_version() );
	}

	public function test_it_returns_the_default_nevobo_endpoint_when_none_is_stored(): void {
		$this->assertSame(
			'https://api.nevobo.nl/competitie/teams.jsonld',
			stamdata_get_nevobo_teams_endpoint()
		);
	}

	public function test_it_returns_the_stored_nevobo_endpoint_when_present(): void {
		update_option( 'wp_plugin_stamdata_nevobo_teams_endpoint', 'https://example.test/custom.jsonld' );

		$this->assertSame( 'https://example.test/custom.jsonld', stamdata_get_nevobo_teams_endpoint() );
	}

	public function test_it_returns_the_default_nevobo_vereniging_path_when_none_is_stored(): void {
		$this->assertSame(
			'/relatiebeheer/verenigingen/ckl9x7n',
			stamdata_get_nevobo_vereniging_path()
		);
	}

	public function test_it_returns_the_stored_nevobo_vereniging_path_when_present(): void {
		update_option( 'wp_plugin_stamdata_nevobo_vereniging_path', '/relatiebeheer/verenigingen/testclub' );

		$this->assertSame( '/relatiebeheer/verenigingen/testclub', stamdata_get_nevobo_vereniging_path() );
	}

	public function test_it_returns_the_default_nevobo_limit_when_missing_or_invalid(): void {
		$this->assertSame( 100, stamdata_get_nevobo_teams_limit() );

		update_option( 'wp_plugin_stamdata_nevobo_teams_limit', 0 );
		$this->assertSame( 100, stamdata_get_nevobo_teams_limit() );
	}

	public function test_it_returns_the_stored_nevobo_limit_when_positive(): void {
		update_option( 'wp_plugin_stamdata_nevobo_teams_limit', 250 );

		$this->assertSame( 250, stamdata_get_nevobo_teams_limit() );
	}
}
