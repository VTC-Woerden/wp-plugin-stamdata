<?php
/**
 * Test bootstrap for lightweight public API contract tests.
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;

		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		public function get_error_message() {
			return $this->message;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

$GLOBALS['wp_plugin_stamdata_test_options'] = array();

function plugin_dir_path( $file ) {
	return dirname( $file ) . '/';
}

function plugin_dir_url( $file ) {
	return 'http://example.test/' . basename( dirname( $file ) ) . '/';
}

function register_activation_hook( $file, $callback ) {}
function add_action( $hook, $callback ) {}
function add_menu_page() {}
function add_submenu_page() {}
function wp_enqueue_media() {}
function wp_die( $message = '' ) {
	throw new RuntimeException( (string) $message );
}
function current_time( $type ) {
	return '2026-04-20 12:00:00';
}
function is_admin() {
	return false;
}
function current_user_can( $capability ) {
	return true;
}
function admin_url( $path = '' ) {
	return 'http://example.test/wp-admin/' . ltrim( $path, '/' );
}
function add_query_arg( $args, $url ) {
	return $url;
}
function wp_safe_redirect( $url ) {}
function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) {}
function wp_nonce_field( $action = -1, $name = '_wpnonce' ) {}
function wp_nonce_url( $url, $action = -1, $name = '_wpnonce' ) {
	return $url;
}
function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = array() ) {
	return '';
}
function submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) {}
function wp_kses_post( $content ) {
	return $content;
}
function esc_html_e( $text, $domain = null ) {}
function esc_html__( $text, $domain = null ) {
	return $text;
}
function __( $text, $domain = null ) {
	return $text;
}
function esc_html( $text ) {
	return (string) $text;
}
function esc_attr( $text ) {
	return (string) $text;
}
function esc_url( $url ) {
	return (string) $url;
}
function esc_url_raw( $url ) {
	return (string) $url;
}
function esc_js( $text ) {
	return (string) $text;
}
function esc_textarea( $text ) {
	return (string) $text;
}
function sanitize_key( $key ) {
	$key = strtolower( (string) $key );
	return preg_replace( '/[^a-z0-9_\-]/', '', $key );
}
function wp_unslash( $value ) {
	return $value;
}
function absint( $value ) {
	return abs( (int) $value );
}
function sanitize_text_field( $value ) {
	return trim( (string) $value );
}
function sanitize_textarea_field( $value ) {
	return trim( (string) $value );
}
function sanitize_title( $value ) {
	$value = strtolower( trim( (string) $value ) );
	$value = preg_replace( '/[^a-z0-9]+/', '-', $value );
	return trim( $value, '-' );
}
function checked( $checked, $current = true, $display = true ) {
	return $checked === $current ? 'checked="checked"' : '';
}
function selected( $selected, $current = true, $display = true ) {
	return $selected === $current ? 'selected="selected"' : '';
}
function disabled( $disabled, $current = true, $display = true ) {
	return $disabled === $current ? 'disabled="disabled"' : '';
}
function wp_json_encode( $value ) {
	return json_encode( $value );
}
function wp_is_numeric_array( $data ) {
	if ( ! is_array( $data ) ) {
		return false;
	}

	return array_keys( $data ) === range( 0, count( $data ) - 1 );
}
function wp_remote_get( $url, $args = array() ) {
	return new WP_Error( 'not_available', 'HTTP not available in unit test bootstrap.' );
}
function wp_remote_retrieve_response_code( $response ) {
	return 500;
}
function wp_remote_retrieve_body( $response ) {
	return '';
}
function get_option( $option, $default = false ) {
	return array_key_exists( $option, $GLOBALS['wp_plugin_stamdata_test_options'] ) ? $GLOBALS['wp_plugin_stamdata_test_options'][ $option ] : $default;
}
function update_option( $option, $value ) {
	$GLOBALS['wp_plugin_stamdata_test_options'][ $option ] = $value;
	return true;
}
function add_option( $option, $value ) {
	if ( ! array_key_exists( $option, $GLOBALS['wp_plugin_stamdata_test_options'] ) ) {
		$GLOBALS['wp_plugin_stamdata_test_options'][ $option ] = $value;
	}

	return true;
}

class WP_Plugin_Stamdata_Test_WPDB {
	public $prefix = 'wp_';
	public $last_prepared;
	public $team_rows = array();

	public function get_charset_collate() {
		return '';
	}

	public function prepare( $query, ...$args ) {
		$this->last_prepared = array(
			'query' => $query,
			'args'  => $args,
		);

		return $this->last_prepared;
	}

	public function get_row( $prepared, $output = OBJECT ) {
		$args = isset( $prepared['args'] ) ? $prepared['args'] : array();

		if ( count( $args ) < 2 ) {
			return null;
		}

		$team_id      = (int) $args[0];
		$data_version = (string) $args[1];

		if ( isset( $this->team_rows[ $data_version ][ $team_id ] ) ) {
			return $this->team_rows[ $data_version ][ $team_id ];
		}

		return null;
	}

	public function get_results( $prepared, $output = OBJECT ) {
		return array();
	}

	public function get_col( $prepared ) {
		return array();
	}

	public function get_var( $prepared ) {
		return null;
	}

	public function insert( $table, $data, $format = null ) {
		return true;
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		return true;
	}

	public function delete( $table, $where, $where_format = null ) {
		return true;
	}

	public function query( $query ) {
		return true;
	}
}

$GLOBALS['wpdb'] = new WP_Plugin_Stamdata_Test_WPDB();

require_once dirname( __DIR__ ) . '/wp-plugin-stamdata.php';
