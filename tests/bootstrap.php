<?php
/**
 * Test bootstrap: autoload plus minimal WordPress function stubs so pure-logic
 * units can be exercised without a full WordPress runtime.
 *
 * @package Soderlind\KjeksAiReviewer
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/stubs/kjeks-stubs.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'KJEKS_AI_DIR' ) ) {
	define( 'KJEKS_AI_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $str ): string {
		return trim( preg_replace( '/[\r\n\t ]+/', ' ', wp_strip_all_tags( $str ) ) ?? '' );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( string $str ): string {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url ): string {
		return $url;
	}
}

if ( ! function_exists( 'wp_http_validate_url' ) ) {
	function wp_http_validate_url( string $url ) {
		return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : false;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		/** @var array<string, array<int, string>> */
		private array $errors = array();

		public function __construct( string $code = '', string $message = '' ) {
			if ( '' !== $code ) {
				$this->errors[ $code ][] = $message;
			}
		}

		public function get_error_code(): string {
			$codes = array_keys( $this->errors );

			return $codes[0] ?? '';
		}

		public function get_error_message(): string {
			$code = $this->get_error_code();

			return $this->errors[ $code ][0] ?? '';
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}
