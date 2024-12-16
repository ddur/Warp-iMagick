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

namespace ddur\Warp_iMagick\Base\Plugin\v1;

defined( 'ABSPATH' ) || die( -1 );

if ( ! class_exists( __NAMESPACE__ . '\Lib' ) ) {
	require __DIR__ . '/inc/getallheaders.php';

	/** Static helper class/methods for Plugin subpackage.*/
	class Lib {
		// phpcs:ignore
	# region Admin notices

		/** Transient (Short Persistent) Admin notice implementation.
		 * Used for Lib::debug/debug_var/error notices.
		 *
		 * @todo? multi-record for concurrent admin users?
		 * @param string $message to display.
		 * @param string $class_name to implement (CSS).
		 */
		private static function t_notice( $message, $class_name ) {
			$transient_id = self::get_namespace() . '-notices';
			$transient    = get_transient( $transient_id );
			$notices      = is_string( $transient ) ? $transient : '';
			$notices     .= '<div class="' . esc_attr( $class_name ) . '"><p style="white-space:pre"><strong>' . esc_html( $message ) . '</strong></p></div>';
			set_transient( $transient_id, $notices, HOUR_IN_SECONDS );
		}

		/** Show Transient admin notices.
		 * Every plugin has different namespace.
		 * This will show only transient messages
		 * for this Lib::implementation namespace.
		 */
		public static function do_transient_notices() {
			$transient_id = self::get_namespace() . '_notices';
			$transient    = get_transient( $transient_id );
			if ( false !== $transient ) {
				delete_transient( $transient_id );
				self::echo_html( $transient );

			}
		}

		/** Get main plugin namespace. */
		public static function get_namespace() {
			$namespace = array();
			foreach ( preg_split( '~\\\\~', __NAMESPACE__, 0, PREG_SPLIT_NO_EMPTY ) as $name ) {
				if ( in_array( $name, array( 'Base', 'Plugin' ), true ) ) {
					break;
				}
				$namespace[] = $name;
			}
			return strtolower( implode( '-', $namespace ) );
		}

		/** Echo html.
		 *
		 * @param string $html to echo.
		 */
		public static function echo_html( $html ) {
			// phpcs:disable
			echo $html;
			// phpcs:enable
		}

		/** Transient admin info notice.
		 *
		 * @param string $message to display.
		 */
		public static function t_notice_info( $message ) {
			self::t_notice( trim( $message ), 'notice notice-info is-dismissible' );
		}

		/** Transient admin error notice.
		 *
		 * @param string $message to display.
		 */
		public static function t_notice_error( $message ) {
			self::t_notice( $message, 'notice notice-error is-dismissible' );
		}

		/** Transient admin success notice.
		 *
		 * @param string $message to display.
		 */
		public static function t_notice_success( $message ) {
			self::t_notice( $message, 'notice notice-success is-dismissible' );
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Debug helpers

		/** Is WordPress in debug mode?
		 *
		 * @access public
		 * @return bool true if WP_DEBUG is defined and not false.
		 */
		public static function is_debug() {
			return defined( 'WP_DEBUG' ) && WP_DEBUG;
		}

		/** Is WordPress in wp-cli mode?
		 *
		 * @access public
		 * @return bool true if WP_CLI is defined, not false and class \WP_CLI exists.
		 */
		public static function is_wp_cli() {
			return defined( 'WP_CLI' ) && WP_CLI && class_exists( '\\WP_CLI' );
		}

		/** Debug helper, log and display debug message if WordPress is in debug mode.
		 *
		 * @param string $message to log & show.
		 * @param bool   $trace flag, set to true to dump calling function stack/list.
		 */
		public static function debug( $message, $trace = false ) {
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
				\error_log( 'Lib::debug: ' . $echo_message );

				if ( self::is_wp_cli() ) {
					\WP_CLI::debug( $echo_message );
				} else {
					self::t_notice_info( $echo_message );
				}
			}
		}

		/** Debug variable value, log & show if WordPress is in debug mode.
		 *
		 * @param mixed  $vref variable reference.
		 * @param string $name to prefix value.
		 */
		public static function debug_var( &$vref, $name = '' ) {
			if ( self::is_debug() ) {
				if ( ! is_string( $name ) || '' === trim( $name ) ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions
					$name = debug_backtrace()[1]['function'];
				}
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions
				$message = rtrim( trim( $name ), ':' ) . ': ' . print_r( $vref, true );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions
				error_log( 'Lib::debug_var: ' . $message );

				if ( self::is_wp_cli() ) {
					\WP_CLI::debug( $message );
				} else {
					self::t_notice_info( $message );
				}
			}
		}

		/** Error log and admin feedback.
		 *
		 * @param string $message error to log and show as admin notice.
		 */
		public static function error( $message ) {
			if ( self::is_debug() ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions
				error_log( 'Lib::error: ' . $message );

				if ( self::is_wp_cli() ) {
					\WP_CLI::debug( $message );
				} else {
					self::t_notice_error( $message );
				}
			}
		}

		/** Use(d) to make generated html readable by adding EOL+TABS (debug mode only).
		 *
		 * @access public
		 * @param int $n number of tab characters.
		 * @return string of one EOL and $n tab characters.
		 */
		public static function debug_eol_tabs( $n ) {
			if ( self::is_debug() && is_int( $n ) && $n >= 0 ) {
				return "\n" . str_repeat( "\t", $n );
			}
			return '';
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region System info

		/** Script is running on Apache server?
		 *
		 * @access public
		 * @return bool true if yes.
		 */
		public static function is_apache_server() {
			global $is_apache;
			return $is_apache;
		}

		/** Script is running on Nginx server?
		 *
		 * @access public
		 * @return bool true if yes.
		 */
		public static function is_nginx_server() {
			global $is_nginx;
			return $is_nginx;
		}

		/** Script is running on IIS server?
		 *
		 * @access public
		 * @return bool true if yes.
		 */
		public static function is_iis_server() {
			global $is_IIS;
			return $is_IIS;
		}

		/** Get the OS name (Apple|Linux|Windows).
		 *
		 * @access public
		 * @return string os-name (Apple|Linux|Windows).
		 */
		public static function os_name() {
			switch ( true ) {
				case stristr( PHP_OS, 'DAR' ):
					return 'Apple';
				case stristr( PHP_OS, 'WIN' ):
					return 'Windows';
				case stristr( PHP_OS, 'LINUX' ):
					return 'Linux';
			}
			return 'Unknown';
		}

		/** Operating System is Linux?
		 *
		 * @access public
		 * @return bool
		 */
		public static function is_linux_os() {
			return self::os_name() === 'Linux';
		}

		/** Operating System is Windows?
		 *
		 * @access public
		 * @return bool
		 */
		public static function is_windows_os() {
			return self::os_name() === 'Windows';
		}

		/** Operating System is Apple?
		 *
		 * @access public
		 * @return bool
		 */
		public static function is_apple_os() {
			return self::os_name() === 'Apple';
		}

		/** Php output is compressed?
		 *
		 * @access public
		 * @return bool
		 */
		public static function is_php_output_compressed() {
			return ( ini_get( 'zlib.output_compression' ) || 'ob_gzhandler' === ini_get( 'output_handler' ) );
		}

		/** Is Curl available?
		 *
		 * @access public
		 * @return bool
		 */
		public static function is_curl_available() {
			return ( extension_loaded( 'curl' ) && function_exists( 'curl_version' ) );
		}

		/** Is Exec available?
		 *
		 * @access public
		 * @return bool
		 */
		public static function is_exec_available() {
			static $available;
			if ( ! isset( $available ) ) {
				$available = true;
				$d         = ini_get( 'disable_functions' );
				$s         = ini_get( 'suhosin.executor.func.blacklist' );
				if ( "$d$s" ) {
					$an_array = preg_split( '/,\s*/', "$d,$s" );
					if ( in_array( 'exec', $an_array, true ) ) {
						$available = false;
					}
				}
			}
			return $available;
		}

		/** Is GD available?
		 *
		 * @access public
		 * @return bool
		 */
		public static function is_gd_available() {
			return ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) );
		}

		/** Is Imagick available?
		 *
		 * @access public
		 * @return bool
		 */
		public static function is_imagick_available() {
			if ( extension_loaded( 'imagick' ) && class_exists( '\\Imagick' ) ) {
				try {
					$magic_test = new \Imagick();
					$magic_test->clear();
					$magic_test->destroy();
					$magic_test = null;
					return true;
				} catch ( \Exception $e ) {
					return false;
				}
			}
			return false;
		}

		/** Is Zip available?
		 *
		 * @access public
		 * @return bool
		 */
		public static function is_zip_available() {
			return extension_loaded( 'zip' );
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region WP Hook helpers

		/** Is Hook active?
		 *
		 * @access public
		 * @param string $hook name.
		 * @return bool
		 */
		public static function is_hook_active( $hook ) {
			return isset( $GLOBALS['wp_filter'] )
			&& is_array( $GLOBALS['wp_filter'] )
			&& array_key_exists( $hook, $GLOBALS['wp_filter'] );
		}

		/** Is Hook hooked?
		 *
		 * @access public
		 * @param string $hook name.
		 * @param string $callback option.
		 * @return bool
		 */
		public static function is_hook_hooked( $hook, $callback = false ) {
			return \has_filter( $hook, $callback );
		}

		/** Auto-magically Use Dedicated Method Names As Handlers For WordPress Hooks (Actions/Filters).
		 * Recognized Method Names: on_(Required: {%HookName%})[Optional: {_%priority%}](Required: _{%hook|action|filter|event%})
		 * Ie 'init' action: on_init_action, on_init_hook, on_init_filter,
		 * on_init_20_action, on_init_plus100_hook, on_init_minus100_event .....
		 *
		 * @param object $an_object to wire to events.
		 * @param string $a_prefix filter, required not empty ctype_alnum string ('on').
		 * @param array  $a_suffix strings not empty array of not empty strings.
		 */
		public static function auto_hook( $an_object, $a_prefix = 'on', $a_suffix = array( 'hook', 'action', 'filter', 'event' ) ) {
			if ( ! is_object( $an_object ) ) {
				$msg = __METHOD__ . ': argument $an_object is not object-type.';
				self::error( $msg );
				wp_die( esc_html( $msg ) );
			}

			if ( ! is_string( $a_prefix ) || ! ctype_alnum( $a_prefix ) ) {
				$msg = __METHOD__ . ': argument $a_prefix is not valid.';
				self::error( $msg );
				wp_die( esc_html( $msg ) );
			}

			if ( ! is_array( $a_suffix ) || empty( $a_suffix ) ) {
				$msg = __METHOD__ . ': argument $a_suffix is not valid.';
				self::error( $msg );
				wp_die( esc_html( $msg ) );
			} else {
				foreach ( $a_suffix as $ends_with ) {
					if ( ! is_string( $ends_with ) || ! ctype_alnum( $ends_with ) ) {
						$msg = __METHOD__ . ': argument $a_suffix (ends_with) is not valid.';
						self::error( $msg );
						wp_die( esc_html( $msg ) );
					}
				}
			}

			$methods = get_class_methods( $an_object );

			foreach ( $methods as $method ) {
				$method_items = explode( '_', $method );

				if ( count( $method_items ) >= 3
				&& ( array_shift( $method_items ) === $a_prefix )
				&& in_array( strtolower( array_pop( $method_items ) ), $a_suffix, true ) ) {
					$priority = false;
					if ( count( $method_items ) > 1 ) {
						$priority_item = end( $method_items );
						if ( is_numeric( $priority_item ) ) {
							$priority = (int) array_pop( $method_items );
						} elseif ( strlen( $priority_item ) > 4
						&& self::starts_with( $priority_item, 'plus' )
						&& is_numeric( substr( $priority_item, 4 ) ) ) {
							$priority = (int) substr( array_pop( $method_items ), 4 );
						} elseif ( strlen( $priority_item ) > 5
						&& self::starts_with( $priority_item, 'minus' )
						&& is_numeric( substr( $priority_item, 5 ) ) ) {
							$priority = (int) substr( array_pop( $method_items ), 5 );
							$priority = -$priority;
						}
					}
					$hook = implode( '_', $method_items );
					if ( false === $priority ) {
						$priority = 10;
					}
					self::auto_hook_connect( $an_object, $method, $hook, $priority );
				}
			}
		}

		/**
		 * Connect method to hook.
		 *
		 * @param object  $an_object instance.
		 * @param string  $method name.
		 * @param string  $hook name.
		 * @param integer $priority level.
		 */
		private static function auto_hook_connect( $an_object, $method, $hook, $priority = 10 ) {
			$callback = self::auto_hook_get_callable( $an_object, $method );

			if ( false !== $callback && false === self::is_hook_hooked( $hook, $callback ) ) {
				$arg_count = self::auto_hook_get_arg_count( $an_object, $method );
				return add_filter( $hook, $callback, $priority, $arg_count );
			}
			return false;
		}

		/**
		 * Is method callable by WordPress action/filter?
		 *
		 * @param object $an_object instance.
		 * @param string $method name.
		 * @return mixed ReflectionMethod|bool
		 */
		private static function auto_hook_is_callable_method( $an_object, $method ) {
			if ( method_exists( $an_object, $method ) ) {
				$r = new \ReflectionMethod( $an_object, $method );
				return true === $r->isPublic()
					&& false === $r->isConstructor()
					&& false === $r->isDestructor() ?
						$r : false;
			}
			return false;
		}

		/**
		 * Return valid callable (array) if method is callable by WordPress action/filter.
		 * Return false if method in not callable by WordPress action/filter.
		 *
		 * @param object $an_object instance.
		 * @param string $method name.
		 * @return array|false
		 */
		private static function auto_hook_get_callable( $an_object, $method ) {
			$r = self::auto_hook_is_callable_method( $an_object, $method );
			if ( false === $r ) {
				return false;
			} elseif ( true === $r->isStatic() ) {
				return array( get_class( $an_object ), $method );
			} else {
				return array( $an_object, $method );
			}
		}

		/**
		 * Return number of arguments if method is callable by WordPress action/filter.
		 * Return false if method in not callable by WordPress action/filter.
		 *
		 * @param object $an_object instance.
		 * @param string $method name.
		 * @return mixed
		 */
		private static function auto_hook_get_arg_count( $an_object, $method ) {
			$r = self::auto_hook_is_callable_method( $an_object, $method );
			if ( false !== $r ) {
				return $r->getNumberOfParameters();
			}
			return 0;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region String helpers

		/** Test if string $haystack starts with string $needle.
		 *
		 * Returns true if string $haystack starts with string $needle.
		 *
		 * @access public
		 * @param string $haystack to search in.
		 * @param string $needle to search for.
		 * @return bool true if $haystack starts with $needle.
		 */
		public static function starts_with( $haystack, $needle ) {
			return ( is_string( $haystack ) && is_string( $needle ) && substr( $haystack, 0, strlen( $needle ) ) === $needle );
		}

		/** Test if string $haystack ends with string $needle.
		 * Returns true if string $haystack ends with string $needle.
		 *
		 * @access public
		 * @param string $haystack to test.
		 * @param string $needle to find.
		 * @return bool true if $haystack ends with $needle
		 */
		public static function ends_with( $haystack, $needle ) {
			$length = is_string( $needle ) ? strlen( $needle ) : 0;
			return ( 0 !== $length && is_string( $haystack ) && ( substr( $haystack, -$length ) === $needle ) );
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Array helpers

		/** Safe Get Array [$a_key] => $value.
		 *
		 * @param array $an_array to use $a_key on.
		 * @param mixed $a_key to use on array.
		 * @param mixed $an_example if not null used as type-check and as default value when key not present.
		 * @param mixed $a_default if not null used as default value (instead of $an_example) when key not present.
		 * @return mixed $value
		 */
		public static function safe_key_value( $an_array, $a_key, $an_example = null, $a_default = null ) {
			if ( isset( $an_array ) && is_array( $an_array ) ) {
				$safe_value = null;
				$safe_found = false;
				if ( ( is_string( $a_key ) || is_int( $a_key ) ) && array_key_exists( $a_key, $an_array ) ) {
					$safe_value = $an_array [ $a_key ];
					$safe_found = true;
				} elseif ( is_array( $a_key ) ) {
					$safe_value = $an_array;
					$safe_found = true;
					foreach ( $a_key as $a_key_item ) {
						if ( is_array( $safe_value ) && array_key_exists( $a_key_item, $safe_value ) ) {
							$safe_value = $safe_value [ $a_key_item ];
						} else {
							$safe_found = false;
							break;
						}
					}
				}
				if ( true === $safe_found && ( null === $an_example || gettype( $safe_value ) === gettype( $an_example ) ) ) {
					return $safe_value;
				}
			}

			return func_num_args() <= 3 ? $an_example : $a_default;
		}

		/** Array Difference.
		 * Excludes values found in $needle.
		 * Preserves $haystack keys.
		 *
		 * @param array $haystack to exclude from.
		 * @param mixed $needle array or single value to exclude.
		 * @return array $haystack, modified or not.
		 */
		public static function array_exclude_values( $haystack, $needle ) {
			if ( is_array( $haystack ) ) {
				$result = array();
				if ( is_array( $needle ) ) {
					$needles = array();
					foreach ( $needle as $a_key ) {
						$needles [ $a_key ] = null;
					}
					foreach ( $haystack as $a_key => $val ) {
						if ( ! array_key_exists( $val, $needles ) ) {
							$result [ $a_key ] = $val;
						}
					}
				} else {
					foreach ( $haystack as $a_key => $val ) {
						if ( $val !== $needle ) {
							$result [ $a_key ] = $val;
						}
					}
				}
				return $result;
			} else {
				return $haystack;
			}
		}

		// phpcs:ignore
	# endregion

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

				|| self::starts_with( $ip, '127.' ) ) {
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
				if ( self::starts_with( $ip, '127.' ) ) {
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

		// phpcs:ignore
	# region WordPress script/style enqueue wrappers

		/** Convert absolute path to wp-root relative path.
		 * Use to convert paths of scripts and styles for wp_enqueue/register
		 * If $path starts with $abspath, return $path - $abspath,
		 * else return $path.
		 *
		 * @access public
		 * @param string $path to convert to relative path.
		 * @return string relative path.
		 */
		public static function relative_path( $path ) {
			$abspath = wp_normalize_path( untrailingslashit( ABSPATH ) );
			$path    = wp_normalize_path( $path );
			if ( self::starts_with( $path, $abspath ) ) {
				return substr( $path, strlen( $abspath ) );
			}
			return $path;
		}

		/** Wrapper for wp_register_style.
		 * In debug/develop mode, modifies version string to short version of mtime
		 *
		 * @param string $handle for style.
		 * @param string $src url.
		 * @param string $deps dependencies.
		 * @param string $ver version.
		 * @param string $media media.
		 */
		public static function register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
			if ( self::is_debug() && is_string( $src ) && '' !== trim( $src ) && is_file( untrailingslashit( ABSPATH ) . $src ) ) {
				$ver = self::hash_time( filemtime( untrailingslashit( ABSPATH ) . $src ) );
			}
			wp_register_style( $handle, $src, $deps, $ver, $media );
		}

		/** Wrapper for wp_enqueue_style.
		 * In debug/develop mode, modifies version string to short version of mtime
		 *
		 * @param string $handle for style.
		 * @param string $src url.
		 * @param string $deps dependencies.
		 * @param string $ver version.
		 * @param string $media media.
		 */
		public static function enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
			if ( self::is_debug() && is_string( $src ) && '' !== trim( $src ) && is_file( untrailingslashit( ABSPATH ) . $src ) ) {
				$ver = self::hash_time( filemtime( untrailingslashit( ABSPATH ) . $src ) );
			}
			wp_enqueue_style( $handle, $src, $deps, $ver, $media );
		}

		/** Wrapper for wp_register_script.
		 * In debug/develop mode, modifies version string to short version of mtime
		 *
		 * @param string $handle for style.
		 * @param string $src url.
		 * @param string $deps dependencies.
		 * @param string $ver version.
		 * @param string $in_footer in footer.
		 */
		public static function register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
			if ( self::is_debug() && is_string( $src ) && '' !== trim( $src ) && is_file( untrailingslashit( ABSPATH ) . $src ) ) {
				/** When in debug mode, set fresh ?version for each new install/update  */
				$ver = self::hash_time( filemtime( untrailingslashit( ABSPATH ) . $src ) );
			}
			wp_register_script( $handle, $src, $deps, $ver, $in_footer );
		}

		/** Wrapper for wp_enqueue_script.
		 * In debug/develop mode, modifies version string to short version of mtime
		 *
		 * @param string $handle for style.
		 * @param string $src url.
		 * @param string $deps dependencies.
		 * @param string $ver version.
		 * @param string $in_footer in footer.
		 */
		public static function enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
			if ( self::is_debug() && is_string( $src ) && '' !== trim( $src ) && is_file( untrailingslashit( ABSPATH ) . $src ) ) {
				/** When in debug mode, set fresh ?version for each new install/update  */
				$ver = self::hash_time( filemtime( untrailingslashit( ABSPATH ) . $src ) );
			}
			wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
		}

		/** Computes short (hash) version of mtime.
		 *
		 * @param int $time from filemtime.
		 */
		public static function hash_time( $time ) {
			if ( $time ) {
				return hash( 'crc32', dechex( $time ) );
			}
			return $time;
		}

		// phpcs:ignore
	# endregion
	}
}
