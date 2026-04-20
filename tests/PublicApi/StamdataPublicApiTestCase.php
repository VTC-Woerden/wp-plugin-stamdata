<?php
/**
 * Shared base test case for public API tests.
 */

use PHPUnit\Framework\TestCase;

abstract class StamdataPublicApiTestCase extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['wp_plugin_stamdata_test_options'] = array(
			'wp_plugin_stamdata_active_data_version' => 'live',
		);

		$GLOBALS['wpdb'] = new WP_Plugin_Stamdata_Test_WPDB();
	}
}
