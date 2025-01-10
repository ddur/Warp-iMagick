<?php
/**
 * Copyright © 2017-2025 Dragan Đurić. All rights reserved.
 *
 * @package warp-imagick
 * @license GNU General Public License Version 2.
 * @copyright © 2017-2025. All rights reserved.
 * @author Dragan Đurić
 * @link https://warp-imagick.pagespeed.club/
 *
 * This copyright notice, source files, licenses and other included
 * materials are protected by U.S. and international copyright laws.
 * You are not allowed to remove or modify this or any other
 * copyright notice contained within this software package.
 */

namespace ddur\Warp_iMagick;

defined( 'ABSPATH' ) || die( -1 );

use ddur\Warp_iMagick\Dbg;

$class = __NAMESPACE__ . '\\Net';

if ( ! class_exists( $class ) ) {
	/** Network helper class */
	class Net {
		// phpcs:ignore
	# region private IP requests

		/** Is IP in the IP-Range?
		 *
		 * @param string $ip IP to check in IPV4 format eg. 127.0.0.1.
		 * @param string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed.
		 * @return boolean true if the ip is in $range, false if not.
		 */
		public static function is_ip_in_range( $ip, $range ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				if ( strpos( $range, '/' ) === false ) {
					$range .= '/32';
				}

				list ($range, $netmask) = explode( '/', $range, 2 );
				if ( filter_var( $range, FILTER_VALIDATE_IP ) && ctype_digit( $netmask ) ) {
					$netmask = (int) $netmask;
					if ( $netmask <= 32 ) {
						$range_long    = ip2long( $range );
						$ip_long       = ip2long( $ip );
						$wildcard_long = pow( 2, ( 32 - $netmask ) ) - 1;
						$netmask_long  = ~$wildcard_long;
						return ( ( $ip_long & $netmask_long ) === ( $range_long & $netmask_long ) );
					}
				}
			}
			return false;
		}

		/** Is IP private or reserved?
		 *
		 * @param string $ip to test.
		 * @return bool true if answer is yes.
		 */
		public static function is_ip_private( $ip ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )

				|| Hlp::starts_with( $ip, '127.' ) ) {
					return true;
				}
			}
			return false;
		}

		/** Is IP local to host?
		 *
		 * @param string $ip to test.
		 * @return bool true if answer is yes.
		 */
		public static function is_ip_local( $ip ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				if ( Hlp::starts_with( $ip, '127.' ) ) {
					return true;
				}
			}
			return false;
		}

		/** Private Response Echoes IP number if server IP is private or reserved.
		 * Request from public IP gets Http 404 - Not found error.
		 *
		 * @return void
		 */
		public static function private_response() {
			if ( is_array( $_SERVER ) && array_key_exists( 'SERVER_ADDR', $_SERVER ) ) {
				$server = wp_unslash( $_SERVER );
				$ip     = $server['SERVER_ADDR'];
				if ( self::is_ip_private( $ip ) ) {
					wp_die( esc_html( $ip ) );
				}
			}
			header( 'HTTP/1.1 404 Not Found' );
			wp_die();
		}

		/** Execute private request to url, replacing host with private IP (loopback).
		 *
		 * @param string $url to request.
		 * @param string $ip to use as host. Public or invalid $ip will be replaced with '127.0.0.1'.
		 * @return string $response.
		 */
		public static function private_request( $url, $ip = '127.0.0.1' ) {
			$url_parts = wp_parse_url( $url );
			$scheme    = $url_parts ['scheme'];
			$host      = self::is_ip_private( $ip ) ? $ip : '127.0.0.1';
			$request   = '';
			$request  .= array_key_exists( 'port', $url_parts ) ? ':' . $url_parts ['port'] : '';
			$request  .= array_key_exists( 'path', $url_parts ) ? $url_parts ['path'] : '';
			$request  .= array_key_exists( 'query', $url_parts ) ? '?' . $url_parts ['query'] : '';
			$request  .= array_key_exists( 'fragment', $url_parts ) ? '#' . $url_parts ['fragment'] : '';

			$headers          = array();
			$headers ['Host'] = $url_parts ['host'];

			$args = array(
				'blocking'  => true,
				'headers'   => $headers,
				'sslverify' => false,
			);

			$url = $scheme . '://' . $host . $request;

			self::close_php_session();
			return wp_remote_get( esc_url_raw( $url ), $args );
		}

		/** Execute private ajax request, replacing host with private IP (loopback).
		 *
		 * @param string $action to request from ajax.
		 * @param string $ip to use as host. Public or invalid $ip will be replaced with '127.0.0.1'.
		 * @return string $response.
		 */
		public static function private_ajax_request( $action, $ip = '127.0.0.1' ) {
			$ajax_url = admin_url( 'admin-ajax.php' ) . '?' . http_build_query( array( 'action' => $action ) );

			$url_parts = wp_parse_url( $ajax_url );
			$scheme    = $url_parts ['scheme'];
			$host      = self::is_ip_private( $ip ) ? $ip : '127.0.0.1';
			$request   = '';
			$request  .= array_key_exists( 'port', $url_parts ) ? ':' . $url_parts ['port'] : '';
			$request  .= array_key_exists( 'path', $url_parts ) ? $url_parts ['path'] : '';
			$request  .= array_key_exists( 'query', $url_parts ) ? '?' . $url_parts ['query'] : '';
			$request  .= array_key_exists( 'fragment', $url_parts ) ? '#' . $url_parts ['fragment'] : '';

			$headers          = array();
			$headers ['Host'] = $url_parts ['host'];

			$args = array(
				'blocking'  => true,
				'headers'   => $headers,
				'sslverify' => false,
			);

			$url = $scheme . '://' . $host . $request;

			self::close_php_session();
			return wp_remote_get( esc_url_raw( $url ), $args );
		}

		/** Close PHP session to prevent server locking at loopback.
		 *
		 * @return void
		 */
		public static function close_php_session() {
			if ( function_exists( '\session_status' ) ) {
				if ( session_status() === 2 ) {
					session_write_close();
				}
			} elseif ( ! empty( session_id() ) ) {
				session_write_close();
			}
		}

		// phpcs:ignore
	# endregion
	}

} else {
	Dbg::debug( "Class already exists: $class" );
}
