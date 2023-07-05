<?php
/**
 * Copyright © 2017-2023 Dragan Đurić. All rights reserved.
 *
 * @package warp-imagick
 * @license GNU General Public License Version 2.
 * @copyright © 2017-2023. All rights reserved.
 * @author Dragan Đurić
 * @link https://warp-imagick.pagespeed.club/
 *
 * This copyright notice, source files, licenses and other included
 * materials are protected by U.S. and international copyright laws.
 * You are not allowed to remove or modify this or any other
 * copyright notice contained within this software package.
 */

namespace ddur\Warp_iMagick\Base;

defined( 'ABSPATH' ) || die( -1 );

require_once __DIR__ . '/plugin-update-checker-5.1/load-v5p1.php';

use \YahnisElsts\PluginUpdateChecker\v5\PucFactory;
use \ddur\Warp_iMagick\Base\Plugin\v1\Lib;
use \ddur\Warp_iMagick\Base\Base_Plugin;
use \ddur\Warp_iMagick\Settings;
use \ddur\Warp_iMagick\Shared;

if ( ! class_exists( __NAMESPACE__ . '\Meta_Plugin' ) ) {
	/** Meta Plugin Class.
	 *
	 * Class between Plugin and abstract Base_Plugin class.
	 */
	abstract class Meta_Plugin extends Base_Plugin {
		// phpcs:ignore
	# region Plugin Class construction.

		/** Plugin init. Called immediately after plugin class is constructed. */

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Plugin install and update infrastructure.

		/** On Install/Upgrade reactivated flag.
		 * Prevent reactivating plugin twice.
		 *
		 * @var bool $my_is_reactivated flag.
		 */
		protected $my_is_reactivated = false;

		/** Reactivate plugin on "live" upgrade events.
		 * When plugin is already active and now upgraded,
		 * either via "Upload Plugin" upgrade or downgrade
		 * or via Plugins Page [batch|single] "update now"
		 * or via (enabled) auto-update for this plugin.
		 */
		private function reactivate_when_active() {
			$that = Settings::once( $this );
			$that->on_abstract_activate_plugin( \is_multisite() );
		}

		/** Install or Update Handler. */
		protected function install_update_handler() {
			/** Upgrader Post Install
			 *
			 * On [Add New][Upload Plugin].
			 * On plugins page [bulk] GUI "update now".
			 */
			add_filter(
				'upgrader_post_install',
				function( $success = false, $hook_extra = false, $result = false ) {
					if ( true === $this->my_is_reactivated ) {
						return $success;
					}

					if ( true !== $success ) {
						return $success;
					}

					if ( is_wp_error( $result ) ) {
						return $success;
					}

					if ( ! is_array( $result ) ) {
						return $success;
					}

					if ( ! is_array( $hook_extra ) ) {
						return $success;
					}

					if ( 'plugin' !== Lib::safe_key_value( $hook_extra, 'type', '', false ) ) {
						if ( $this->get_basename() !== Lib::safe_key_value( $hook_extra, 'plugin', '', false ) ) {
							return $success;

						}
					}

					$destination_name = Lib::safe_key_value( $result, 'destination_name', '', '' );
					if ( $this->get_slug() !== $destination_name ) {
						if ( empty( $destination_name ) ) {
							$destination = Lib::safe_key_value( $result, 'destination', '', false );
							if ( $this->get_path() !== $destination ) {
								return $success;
							}
						} else {
							return $success;
						}
					}

					if ( true !== $this->my_is_reactivated ) {
						$this->reactivate_when_active();
						$this->my_is_reactivated = true;
					} else {
						;
					}

					return $success;
				},
				10,
				3
			);

			/** Upgrader Process Complete
			 *
			 * On [Add New][Upload Plugin].
			 * On plugins page [bulk] GUI "update now".
			 */
			add_action(
				'upgrader_process_complete',
				function( $upgrader_instance = null, $hook_extra = null ) {
					if ( true === $this->my_is_reactivated ) {
						return;
					}

					if ( ! is_object( $upgrader_instance ) ) {
						return;
					}

					if ( ! $upgrader_instance instanceof \Plugin_Upgrader ) {
						return;
					}

					if ( ! is_array( $hook_extra ) ) {
						if ( null !== $hook_extra ) {
							;
						}

						return;
					}

					if ( 'plugin' !== Lib::safe_key_value( $hook_extra, 'type', '', false ) ) {
						return;
					}

					$process_action = Lib::safe_key_value( $hook_extra, 'action', '', 'unset' );

					switch ( $process_action ) {
						case 'update':
							$process_plugins = Lib::safe_key_value( $hook_extra, 'plugins', array(), false );

							if ( ! is_array( $process_plugins ) ) {
								return;
							}

							$plugin_basename = $this->get_basename();

							if ( ! in_array( $plugin_basename, $process_plugins, true ) ) {
								return;
							}

							break;

						case 'install':
							$result = Lib::safe_key_value( (array) $upgrader_instance, 'result', array(), false );

							if ( is_wp_error( $result ) ) {
								return;
							}

							if ( ! is_array( $result ) ) {
								return;
							}

							$destination_name = Lib::safe_key_value( $result, 'destination_name', '', '' );
							if ( $this->get_slug() !== $destination_name ) {
								if ( empty( $destination_name ) ) {
									$destination = Lib::safe_key_value( $result, 'destination', '', false );
									if ( $this->get_path() !== $destination ) {
										return;
									}
								} else {
									return;
								}
							}

							break;

						default:
							return;

					}

					if ( true !== $this->my_is_reactivated ) {
						$this->reactivate_when_active();
						$this->my_is_reactivated = true;
					} else {
						;
					}

					return;
				},
				10,
				2
			);
		}

		/** Upgrade checker client.
		 *
		 * Since 1.10.4:
		 * 1) Using PUC 5.11 (via PUC autoload Namespace.
		 * 2) Using Test Update Server when Test Environment is available.
		 *
		 * @param string $uplink is link to updates server (https://[host.]domain.tld/[updates/]).
		 */
		protected function plugin_upgrade_checker( $uplink = '' ) {
			if ( ! is_string( $uplink ) ) {
				Lib::error( 'Argument $uplink is not a string ( ' . gettype( $uplink ) . ' ).' );
				return;
			}

			$plugin_host = wp_parse_url( home_url(), PHP_URL_HOST );

			/** Argument $uplink is omitted. */
			if ( '' === $uplink ) {
				$protocol = 'https://';
				$endpoint = \trailingslashit( '/updates' );

				/** Set default updates server site */
				$uplink = $protocol . $this->get_slug() . '.pagespeed.club' . $endpoint;

				if ( Lib::ends_with( gethostname(), '.host.lan' ) ) {
					/** This site is running on private LAN hostname */

					if ( Lib::ends_with( $plugin_host, '.site.lan' )
					|| Lib::ends_with( $plugin_host, '.host.lan' ) ) {
						/** This site is running on private LAN test environment
						 * Set $uplink to private LAN test updates server.
						*/
						$uplink = 'http://test.site.lan' . $endpoint;

					}
				}
			}

			$uplink = \trailingslashit( $uplink );
			$server = \add_query_arg(
				\urlencode_deep(
					array(
						'action' => 'get_metadata',
						'slug'   => $this->get_slug(),
					)
				),
				$uplink
			);

			$my_update_checker = PucFactory::buildUpdateChecker(
				$server,
				$this->get_file(),
				$this->get_slug()
			);

			\add_filter(
				$my_update_checker->getUniqueName( 'request_metadata_http_result' ),
				function( $result, $url = '', $options = '' ) use ( $uplink ) {
					if ( ! \is_wp_error( $result ) ) {
						switch ( $result['response']['code'] ) {
							case 404:
								$result = new \WP_Error(
									'404',
									'Plugin ' . $this->get_slug() . ' is not available on Updates Server (' . $uplink . ').'
								);
						}
					}
					return $result;
				},
				10,
				1
			);

			\add_filter(
				$my_update_checker->getUniqueName( 'manual_check_message' ),
				function( $message, $status = '' ) use ( $uplink ) {
					switch ( $status ) {
						case 'no_update':
							break;

						case 'update_available':
							break;

						case 'error':
							break;
					}
					return $message;
				},
				10,
				2
			);

			\add_filter(
				$my_update_checker->getUniqueName( 'request_update_result' ),
				function( $update, $http_result = null ) {
					$plugin_version = Shared::get_plugin_version();

					if ( ! is_string( $plugin_version ) ) {
						delete_transient( $this->get_slug() . '-update-version' );
						return $update;
					}

					if ( empty( $plugin_version ) ) {
						delete_transient( $this->get_slug() . '-update-version' );
						return $update;
					}

					$option_version = $this->get_option( 'plugin-version' );

					if ( $option_version !== $plugin_version ) {
						$updated_options = $this->set_option( 'plugin-version', $plugin_version );

						global $wp_filter;
						$hook_name = "pre_update_option_{$this->get_option_id()}";
						if ( isset( $wp_filter[ $hook_name ] ) && $wp_filter[ $hook_name ]->has_filters() ) {
							\remove_all_filters( $hook_name );
						}

						\update_option( $this->get_option_id(), $updated_options, true );
					}

					if ( ! is_string( $update->version ) ) {
						delete_transient( $this->get_slug() . '-update-version' );
						return $update;
					}

					if ( empty( $update->version ) ) {
						delete_transient( $this->get_slug() . '-update-version' );
						return $update;
					}

					if ( ! version_compare( $update->version, $plugin_version, '>' ) ) {
						delete_transient( $this->get_slug() . '-update-version' );
					} else {
						set_transient( $this->get_slug() . '-update-version', $update->version, DAY_IN_SECONDS );
					}

					return $update;
				},
				10,
				2
			);

			$uplink_host = wp_parse_url( $uplink, PHP_URL_HOST );
			$plugin_pass = preg_replace( '/[^a-z\d]/i', '', $this->get_option( 'plugin-app-update-password', 'xxxx xxxx xxxx xxxx xxxx xxxx' ) );
			$header_auth = 'Basic ' . base64_encode( $plugin_host . ':' . $plugin_pass ); // phpcs:ignore

			\add_filter(
				'http_request_args',
				function ( $parsed_args, $url ) use ( $plugin_host, $uplink_host, $header_auth ) {
					$remote_host = wp_parse_url( $url, PHP_URL_HOST );

					if ( $remote_host !== $uplink_host ) {
						return $parsed_args;
					}

					if ( $remote_host === $plugin_host ) {
						;
					} else {
						if ( false === strpos( $url, '?action=' ) ) {
							return $parsed_args;
						}

						if ( false === strpos( $url, '&slug=' ) ) {
							return $parsed_args;
						}
					}

					$parsed_args ['headers'] = array_merge(
						is_array( $parsed_args ['headers'] ) ? $parsed_args ['headers'] : array(),
						array( 'Authorization' => $header_auth )
					);

					return $parsed_args;
				},
				10,
				2
			);
		}

		/** Show admin notice when update version transient is set. */
		protected function next_version_available() {
			if ( ! is_admin() ) {
				return;
			}

			$update_version = get_transient( $this->get_slug() . '-update-version' );
			if ( ! is_string( $update_version ) ) {
				return;
			}
			if ( empty( $update_version ) ) {
				return;
			}

			if ( is_array( $_SERVER ) && array_key_exists( 'REQUEST_URI', $_SERVER ) ) {
				$_server = wp_unslash( $_SERVER );
				$request = home_url( strtok( $_server['REQUEST_URI'], '?' ) );
				if ( admin_url( 'plugins.php' ) === $request ) {
					return;
				}
				if ( admin_url( 'index.php' ) === $request ) {
					return;
				}
				if ( admin_url( 'admin-ajax.php' ) === $request ) {
					return;
				}
			}

			$option_version = $this->get_option( 'plugin-version', false );
			if ( ! is_string( $option_version ) ) {
				return;
			}
			if ( empty( $option_version ) ) {
				return;
			}

			if ( ! version_compare( $update_version, $option_version, '>' ) ) {
				delete_transient( $this->get_slug() . '-update-version' );
				return;
			}

			if ( $this->is_auto_update_enabled() ) {
				return;
			}

			// Translators: %s is next plugin update version.
			$message = sprintf( __( 'Warp iMagick: New version is available. Please upgrade to version %s.', 'warp-imagick' ), $update_version );

			\add_action(
				'admin_notices',
				function() use ( $message ) {
					$this->echo_admin_notice(
						$message,
						'notice notice-info is-dismissible',
						true
					);
				}
			);
		}

		/** Is auto update enabled? */
		protected function is_auto_update_enabled() {
			$basename = $this->get_basename();

			if ( \defined( 'EASY_UPDATES_MANAGER_SLUG' ) ) {
				$e_u_m_options = get_site_option( 'MPSUM' );

				if ( is_array( $e_u_m_options ) ) {
					$e_u_m_options_updates = Lib::safe_key_value( $e_u_m_options, array( 'core', 'plugin_updates' ), '' );
					switch ( $e_u_m_options_updates ) {
						case 'automatic':
							return true;

						case 'individual':
							$e_u_m_options_automatic = Lib::safe_key_value( $e_u_m_options, 'plugins_automatic', array() );
							if ( is_array( $e_u_m_options_automatic )
								&& count( $e_u_m_options_automatic ) ) {
								if ( in_array( $basename, $e_u_m_options_automatic, true ) ) {
									return true;
								}
							}
					};
				}
				return false;
			} else {
				require_once ABSPATH . 'wp-admin/includes/update.php';

				if ( function_exists( '\\wp_is_auto_update_enabled_for_type' ) ) {
					if ( wp_is_auto_update_enabled_for_type( 'plugin' ) ) {
						$auto_updates = (array) get_site_option( 'auto_update_plugins', array() );

						if ( 0 !== count( $auto_updates ) ) {
							if ( in_array( $basename, $auto_updates, true ) ) {
								return true;
							}
						};
					} else {
						;
					}
				} else {
					;
				}
				return false;
			}
			return false;
		}

		// phpcs:ignore
	# endregion

	}
}
