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
	public $location_rows = array();
	public $field_rows = array();
	public $blueprint_rows = array();
	public $blueprint_location_rows = array();
	public $blueprint_field_rows = array();
	public $blueprint_availability_rows = array();

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
		$query = isset( $prepared['query'] ) ? $prepared['query'] : '';
		$args = isset( $prepared['args'] ) ? $prepared['args'] : array();

		if ( false !== strpos( $query, 'stamdata_teams' ) && false !== strpos( $query, 'WHERE id = %d AND data_version = %s LIMIT 1' ) ) {
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

		if ( false !== strpos( $query, 'stamdata_locations' ) && false !== strpos( $query, 'WHERE id = %d AND data_version = %s LIMIT 1' ) ) {
			return $this->find_row_by_id( $this->location_rows, $args );
		}

		if ( false !== strpos( $query, 'stamdata_fields' ) && false !== strpos( $query, 'WHERE id = %d AND data_version = %s LIMIT 1' ) ) {
			return $this->find_row_by_id( $this->field_rows, $args );
		}

		if ( false !== strpos( $query, 'stamdata_blueprints' ) && false !== strpos( $query, 'WHERE id = %d AND data_version = %s LIMIT 1' ) ) {
			return $this->find_row_by_id( $this->blueprint_rows, $args );
		}

		if ( false !== strpos( $query, 'stamdata_blueprints' ) && false !== strpos( $query, 'WHERE week_type = %s AND week_number = 0 AND data_version = %s LIMIT 1' ) ) {
			if ( count( $args ) < 2 ) {
				return null;
			}

			return $this->find_blueprint_by_week_type( (string) $args[0], 0, (string) $args[1] );
		}

		if ( false !== strpos( $query, 'stamdata_blueprints' ) && false !== strpos( $query, 'WHERE week_type = %s AND week_number = %d AND data_version = %s LIMIT 1' ) ) {
			if ( count( $args ) < 3 ) {
				return null;
			}

			return $this->find_blueprint_by_week_type( (string) $args[0], (int) $args[1], (string) $args[2] );
		}

		if ( false !== strpos( $query, 'stamdata_blueprint_availability' ) && false !== strpos( $query, 'WHERE id = %d AND data_version = %s LIMIT 1' ) ) {
			return $this->find_row_by_id( $this->blueprint_availability_rows, $args );
		}

		return null;
	}

	public function get_results( $prepared, $output = OBJECT ) {
		$query = isset( $prepared['query'] ) ? $prepared['query'] : '';
		$args  = isset( $prepared['args'] ) ? $prepared['args'] : array();

		if ( false !== strpos( $query, 'FROM wp_stamdata_teams WHERE data_version = %s ORDER BY sortable_rank ASC, name ASC' ) ) {
			$rows = $this->rows_for_version( $this->team_rows, (string) $args[0] );

			usort(
				$rows,
				function ( $left, $right ) {
					$rank_compare = strcmp( (string) ( $left['sortable_rank'] ?? '' ), (string) ( $right['sortable_rank'] ?? '' ) );

					if ( 0 !== $rank_compare ) {
						return $rank_compare;
					}

					return strcmp( (string) ( $left['name'] ?? '' ), (string) ( $right['name'] ?? '' ) );
				}
			);

			return array_values( $rows );
		}

		if ( false !== strpos( $query, 'FROM wp_stamdata_locations WHERE data_version = %s ORDER BY name ASC' ) ) {
			$rows = $this->rows_for_version( $this->location_rows, (string) $args[0] );

			usort(
				$rows,
				function ( $left, $right ) {
					return strcmp( (string) ( $left['name'] ?? '' ), (string) ( $right['name'] ?? '' ) );
				}
			);

			return array_values( $rows );
		}

		if ( false !== strpos( $query, 'FROM wp_stamdata_fields f') && false !== strpos( $query, 'LEFT JOIN wp_stamdata_locations l ON l.id = f.location_id AND l.data_version = f.data_version' ) ) {
			$data_version = (string) $args[0];
			$rows         = $this->rows_for_version( $this->field_rows, $data_version );

			foreach ( $rows as &$row ) {
				$location             = $this->find_stored_row( $this->location_rows, $data_version, (int) $row['location_id'] );
				$row['location_name'] = $location['name'] ?? null;
			}
			unset( $row );

			usort(
				$rows,
				function ( $left, $right ) {
					$location_compare = strcmp( (string) ( $left['location_name'] ?? '' ), (string) ( $right['location_name'] ?? '' ) );

					if ( 0 !== $location_compare ) {
						return $location_compare;
					}

					$sort_compare = (int) ( $left['sort_order'] ?? 0 ) <=> (int) ( $right['sort_order'] ?? 0 );

					if ( 0 !== $sort_compare ) {
						return $sort_compare;
					}

					return strcmp( (string) ( $left['name'] ?? '' ), (string) ( $right['name'] ?? '' ) );
				}
			);

			return array_values( $rows );
		}

		if ( false !== strpos( $query, 'FROM wp_stamdata_fields WHERE location_id = %d AND data_version = %s ORDER BY sort_order ASC, name ASC' ) ) {
			if ( count( $args ) < 2 ) {
				return array();
			}

			$location_id  = (int) $args[0];
			$data_version = (string) $args[1];
			$rows         = array_values(
				array_filter(
					$this->rows_for_version( $this->field_rows, $data_version ),
					function ( $row ) use ( $location_id ) {
						return (int) ( $row['location_id'] ?? 0 ) === $location_id;
					}
				)
			);

			usort(
				$rows,
				function ( $left, $right ) {
					$sort_compare = (int) ( $left['sort_order'] ?? 0 ) <=> (int) ( $right['sort_order'] ?? 0 );

					if ( 0 !== $sort_compare ) {
						return $sort_compare;
					}

					return strcmp( (string) ( $left['name'] ?? '' ), (string) ( $right['name'] ?? '' ) );
				}
			);

			return $rows;
		}

		if ( false !== strpos( $query, 'FROM wp_stamdata_blueprints WHERE data_version = %s ORDER BY week_type ASC, week_number ASC, name ASC' ) ) {
			$rows = $this->rows_for_version( $this->blueprint_rows, (string) $args[0] );

			usort(
				$rows,
				function ( $left, $right ) {
					$week_type_compare = strcmp( (string) ( $left['week_type'] ?? '' ), (string) ( $right['week_type'] ?? '' ) );

					if ( 0 !== $week_type_compare ) {
						return $week_type_compare;
					}

					$week_compare = (int) ( $left['week_number'] ?? 0 ) <=> (int) ( $right['week_number'] ?? 0 );

					if ( 0 !== $week_compare ) {
						return $week_compare;
					}

					return strcmp( (string) ( $left['name'] ?? '' ), (string) ( $right['name'] ?? '' ) );
				}
			);

			return array_values( $rows );
		}

		if ( false !== strpos( $query, 'FROM wp_stamdata_blueprint_availability WHERE blueprint_id = %d AND week_type = %s AND week_number IS NULL AND data_version = %s ORDER BY field_id ASC, day_of_week ASC, start_time ASC' ) ) {
			return $this->find_blueprint_availability_rows(
				(int) $args[0],
				(string) $args[1],
				null,
				(string) $args[2]
			);
		}

		if ( false !== strpos( $query, 'FROM wp_stamdata_blueprint_availability WHERE blueprint_id = %d AND week_type = %s AND week_number = %d AND data_version = %s ORDER BY field_id ASC, day_of_week ASC, start_time ASC' ) ) {
			return $this->find_blueprint_availability_rows(
				(int) $args[0],
				(string) $args[1],
				(int) $args[2],
				(string) $args[3]
			);
		}

		return array();
	}

	public function get_col( $prepared ) {
		$query = isset( $prepared['query'] ) ? $prepared['query'] : '';
		$args  = isset( $prepared['args'] ) ? $prepared['args'] : array();

		if ( false !== strpos( $query, 'SELECT location_id FROM wp_stamdata_blueprint_locations WHERE blueprint_id = %d AND data_version = %s ORDER BY location_id ASC' ) ) {
			return $this->find_link_ids( $this->blueprint_location_rows, 'location_id', (int) $args[0], (string) $args[1] );
		}

		if ( false !== strpos( $query, 'SELECT field_id FROM wp_stamdata_blueprint_fields WHERE blueprint_id = %d AND data_version = %s ORDER BY field_id ASC' ) ) {
			return $this->find_link_ids( $this->blueprint_field_rows, 'field_id', (int) $args[0], (string) $args[1] );
		}

		return array();
	}

	public function get_var( $prepared ) {
		$query = isset( $prepared['query'] ) ? $prepared['query'] : '';
		$args  = isset( $prepared['args'] ) ? $prepared['args'] : array();

		if ( false !== strpos( $query, 'SELECT COUNT(*) FROM wp_stamdata_fields WHERE location_id = %d AND data_version = %s' ) ) {
			$location_id  = (int) $args[0];
			$data_version = (string) $args[1];
			$count        = 0;

			foreach ( $this->rows_for_version( $this->field_rows, $data_version ) as $row ) {
				if ( (int) ( $row['location_id'] ?? 0 ) === $location_id ) {
					++$count;
				}
			}

			return $count;
		}

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

	private function find_row_by_id( array $store, array $args ) {
		if ( count( $args ) < 2 ) {
			return null;
		}

		$row_id       = (int) $args[0];
		$data_version = (string) $args[1];

		return $this->find_stored_row( $store, $data_version, $row_id );
	}

	private function find_stored_row( array $store, $data_version, $row_id ) {
		if ( isset( $store[ $data_version ][ $row_id ] ) ) {
			return $store[ $data_version ][ $row_id ];
		}

		return null;
	}

	private function rows_for_version( array $store, $data_version ) {
		if ( empty( $store[ $data_version ] ) ) {
			return array();
		}

		return array_values( $store[ $data_version ] );
	}

	private function find_blueprint_by_week_type( $week_type, $week_number, $data_version ) {
		foreach ( $this->rows_for_version( $this->blueprint_rows, $data_version ) as $row ) {
			if ( $week_type === ( $row['week_type'] ?? '' ) && (int) $week_number === (int) ( $row['week_number'] ?? 0 ) ) {
				return $row;
			}
		}

		return null;
	}

	private function find_link_ids( array $store, $column, $blueprint_id, $data_version ) {
		if ( empty( $store[ $data_version ] ) ) {
			return array();
		}

		$ids = array();

		foreach ( $store[ $data_version ] as $row ) {
			if ( (int) ( $row['blueprint_id'] ?? 0 ) === $blueprint_id ) {
				$ids[] = (int) $row[ $column ];
			}
		}

		sort( $ids, SORT_NUMERIC );

		return $ids;
	}

	private function find_blueprint_availability_rows( $blueprint_id, $week_type, $week_number, $data_version ) {
		if ( empty( $this->blueprint_availability_rows[ $data_version ] ) ) {
			return array();
		}

		$rows = array_values(
			array_filter(
				$this->blueprint_availability_rows[ $data_version ],
				function ( $row ) use ( $blueprint_id, $week_type, $week_number ) {
					$row_week_number = array_key_exists( 'week_number', $row ) ? $row['week_number'] : null;

					return (int) ( $row['blueprint_id'] ?? 0 ) === $blueprint_id
						&& $week_type === ( $row['week_type'] ?? '' )
						&& ( null === $week_number ? null === $row_week_number : (int) $week_number === (int) $row_week_number );
				}
			)
		);

		usort(
			$rows,
			function ( $left, $right ) {
				$field_compare = (int) ( $left['field_id'] ?? 0 ) <=> (int) ( $right['field_id'] ?? 0 );

				if ( 0 !== $field_compare ) {
					return $field_compare;
				}

				$day_compare = (int) ( $left['day_of_week'] ?? 0 ) <=> (int) ( $right['day_of_week'] ?? 0 );

				if ( 0 !== $day_compare ) {
					return $day_compare;
				}

				return strcmp( (string) ( $left['start_time'] ?? '' ), (string) ( $right['start_time'] ?? '' ) );
			}
		);

		return $rows;
	}
}

$GLOBALS['wpdb'] = new WP_Plugin_Stamdata_Test_WPDB();

require_once dirname( __DIR__ ) . '/wp-plugin-stamdata.php';
require_once __DIR__ . '/PublicApi/StamdataPublicApiTestCase.php';
