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

use ddur\Warp_iMagick\Base\Plugin\v1\Lib;
use ddur\Warp_iMagick\Shared;

$class = __NAMESPACE__ . '\\Dbg';

if ( ! class_exists( $class ) ) {
	/** Debug & utility class */
	class Dbg {
		/** Is WordPress in debug mode?
		 *
		 * @access public
		 * @return bool true if WP_DEBUG is defined and not false.
		 */
		public static function is_debug() {
			return defined( 'WP_DEBUG' ) && WP_DEBUG;
		}

		/** Debug log and display debug message if option is enabled..
		 * Use for verbose debugs.
		 *
		 * @param string $message to log & show.
		 * @param bool   $trace flag, set to true to dump calling function list.
		 */
		public static function debug( $message, $trace = false ) {
			if ( Shared::get_option( 'verbose-debug-enabled', false ) ) {
				if ( self::is_debug() ) {
					$echo_message = '';
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions
					$stack = debug_backtrace();
					/** To prevent phpcs warning, must call debug_backtrace before using $trace argument. */
					if ( false === $trace ) {
						$stack = null;
					}
					if ( is_array( $stack ) && count( $stack ) ) {
						/** $stack is not empty array */
						foreach ( $stack as $traced ) {
							// phpcs:ignore WordPress.PHP.DevelopmentFunctions
							$echo_message = $traced ['function'] . '(' . print_r( $traced ['args'], true ) . ') File:' . $traced ['line'] . '/' . $traced ['file'] . PHP_EOL . $echo_message;
						}
						$echo_message = $message . PHP_EOL . $echo_message;
					} elseif ( is_string( $trace ) && trim( $trace ) ) {
						/** $trace is not empty string */
						$echo_message = rtrim( trim( $trace ), ':' ) . ': ' . $message;
					} else {
						$echo_message = $message;
					}
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions
					\error_log( 'debug: ' . $echo_message );

					if ( Hlp::is_wp_cli() ) {
						\WP_CLI::debug( $echo_message );
					} else {
						Plugin::t_notice_info( $echo_message );
					}
				}
			}
		}

		/** Debug variable value, log & show if option is enabled.
		 * Use for verbose debug_vars.
		 *
		 * @param mixed  $arg reference.
		 * @param string $name to prefix value.
		 */
		public static function debug_var( &$arg, $name = '' ) {
			if ( Shared::get_option( 'verbose-debug-enabled', false ) ) {
				if ( self::is_debug() ) {
					if ( ! is_string( $name ) || '' === trim( $name ) ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions
						$name = debug_backtrace()[1]['function'];
					}
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions
					$message = rtrim( trim( $name ), ':' ) . ': ' . print_r( $arg, true );
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions
					error_log( 'debug_var: ' . $message );

					if ( Hlp::is_wp_cli() ) {
						\WP_CLI::debug( $message );
					} else {
						Plugin::t_notice_info( $message );
					}
				}
			}
		}

		/** Error log and admin feedback if option is enabled.
		 * Used for verbose errors.
		 *
		 * @access public
		 * @param string $message error to log and show as admin notice.
		 */
		public static function error( $message ) {
			if ( Shared::get_option( 'verbose-debug-enabled', false ) ) {
				if ( self::is_debug() ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions
					error_log( 'error: ' . $message );

					if ( Hlp::is_wp_cli() ) {
						\WP_CLI::debug( $message );
					} else {
						Plugin::t_notice_error( $message );
					}
				}
			}
		}
	}

} else {
	Dbg::debug( "Class already exists: $class" );
}
