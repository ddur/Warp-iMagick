<?php
/** Polyfills missing getallheaders on Nginx.
 *
 * Slightly modified to comply with phpcs WordPress Coding Standards.
 *
 * @package getallheaders
 * @link https://github.com/ralouphie/getallheaders/
 * @license https://github.com/ralouphie/getallheaders/blob/develop/LICENSE
 */

if ( ! function_exists( 'getallheaders' ) ) {

	/**
	 * Get all HTTP header key/values as an associative array for the current request.
	 *
	 * @return array [string => string] The HTTP header key/value pairs.
	 */
	function getallheaders() {
		$headers = array();

		$copy_server = array(
			'CONTENT_TYPE'   => 'Content-Type',
			'CONTENT_LENGTH' => 'Content-Length',
			'CONTENT_MD5'    => 'Content-Md5',
		);

		$server_noslash = \wp_unslash( $_SERVER );
		foreach ( $server_noslash as $key => $value ) {
			if ( substr( $key, 0, 5 ) === 'HTTP_' ) {
				$key = substr( $key, 5 );
				if ( ! isset( $copy_server[ $key ] ) || ! isset( $server_noslash[ $key ] ) ) {
					$key             = str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', $key ) ) ) );
					$headers[ $key ] = $value;
				}
			} elseif ( isset( $copy_server[ $key ] ) ) {
				$headers[ $copy_server[ $key ] ] = $value;
			}
		}

		if ( ! isset( $headers['Authorization'] ) ) {
			if ( isset( $server_noslash['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
				$headers['Authorization'] = $server_noslash['REDIRECT_HTTP_AUTHORIZATION'];
			} elseif ( isset( $server_noslash['PHP_AUTH_USER'] ) ) {
				$basic_pass = isset( $server_noslash['PHP_AUTH_PW'] ) ? $server_noslash['PHP_AUTH_PW'] : '';
				// phpcs:ignore -- base64_encode is required here.
				$headers['Authorization'] = 'Basic ' . base64_encode( $server_noslash['PHP_AUTH_USER'] . ':' . $basic_pass );
			} elseif ( isset( $server_noslash['PHP_AUTH_DIGEST'] ) ) {
				$headers['Authorization'] = $server_noslash['PHP_AUTH_DIGEST'];
			}
		}

		return $headers;
	}
}
