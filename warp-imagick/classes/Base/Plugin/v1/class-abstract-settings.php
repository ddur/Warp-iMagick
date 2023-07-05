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

namespace ddur\Warp_iMagick\Base\Plugin\v1;

defined( 'ABSPATH' ) || die( -1 );

use \ddur\Warp_iMagick\Base\Plugin\v1\Lib;

if ( ! class_exists( __NAMESPACE__ . '\Abstract_Settings' ) ) {
	/** Abstract Settings Class
	 *
	 * Renders Admin Settings Page and handles Options
	 */
	abstract class Abstract_Settings {
		// phpcs:ignore
	# region Properties (private with getters)

		/** Default values for all fields.
		 * Extracted from Settings. See get_all_fields_defaults ().
		 *
		 * @var array $defaults
		 */
		private $defaults;

		/** Fields and properties.
		 * Extracted from Settings. See get_all_fields_settings().
		 *
		 * @var array $fldprops
		 */
		private $fldprops;

		/** Callable (array) to validate fields on submit.
		 * Null if not configured in Settings.
		 *
		 * @var array $validate
		 */
		private $validate;

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Properties (protected)

		/** Plugin object instance derived from Abstract_Plugin.
		 *
		 * @var object $plugin
		 */
		protected $plugin;

		/** Plugin prefix.
		 *
		 * @var object $prefix
		 */
		protected $prefix;

		/** Plugin's directory absolute path.
		 *
		 * @var object $path
		 */
		protected $path;

		/** Plugin's Settings Configuration.
		 *
		 * @var array $settings
		 */
		protected $settings;

		/** Plugin settings page slug == $plugin->get_slug().
		 *
		 * @var string $pageslug
		 */
		protected $pageslug;

		/** Plugin Option API ID.
		 * Replaces call to $plugin->get_option_id().
		 * Caches return value
		 *
		 * @var string $optionid
		 */
		protected $optionid;

		/** Plugin Basename == $plugin->get_basename().
		 *
		 * @var string $basename
		 */
		protected $basename;

		/** Plugin Menu slug. Value returned from add_menu_page().
		 *
		 * @var string $menuslug
		 */
		protected $menuslug;

		/** Plugin Settings Screen instance. Value returned from get_current_screen().
		 *
		 * @var object $myscreen
		 */
		protected $myscreen;

		/** Object instance with custom render-settings methods.
		 * Renderer implements render callback methods defined in Settings Configuration.
		 *
		 * @var object $renderer
		 */
		protected $renderer;

		/** User capability.
		 * https://codex.wordpress.org/Roles_and_Capabilities.
		 *
		 * @access protected
		 * @var string $usercaps Required user (wp) capabilities to access admin page.
		 */
		protected $usercaps = 'manage_options';

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Input Field Types

		/** Field Types.
		 *
		 * @access protected
		 * @var array $input_field_types.
		 */
		protected $input_field_types = array(
			'text'                => null,
			'password'            => null,
			'text-password'       => null,
			'color'               => null,
			'text-color'          => null,
			'date'                => null,
			'text-date'           => null,
			'datetime-local'      => null,
			'text-datetime-local' => null,
			'email'               => null,
			'text-email'          => null,
			'month'               => null,
			'text-month'          => null,
			'number'              => null,
			'text-number'         => null,
			'range'               => 'render_range_slider_input_field',
			'text-range'          => null,
			'search'              => null,
			'text-search'         => null,
			'tel'                 => null,
			'text-tel'            => null,
			'time'                => null,
			'text-time'           => null,
			'url'                 => null,
			'text-url'            => null,
			'week'                => null,
			'text-week'           => null,
			'textarea'            => 'render_textarea_input_field',
			'checkbox'            => 'render_checkbox_input_field',
			'hidden'              => 'render_hidden_input_field',
			'file'                => 'render_upload_file_input_field',
			'select'              => 'render_select_option_input_field',
		);

		/** Input field default (text) renderer.
		 * Handles browser rendered HTML5 (to HTML4 text-fallback) input field types.
		 * Same for 'text-' prefixed types when original text type handler is replaced (like 'range').
		 *
		 * @access protected
		 * @var string $input_field_default_renderer.
		 */
		protected $input_field_default_renderer = 'render_text_input_field';

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Construction and Initialization

		/** Check constructor arguments.
		 *
		 * @access public
		 * @param object $plugin object instance.
		 * @param object $renderer object instance.
		 * @return bool true. At debug time true/false if arguments are valid or not.
		 */
		public static function is_valid_constructor_args( $plugin, $renderer ) {
			if ( ! is_object( $plugin ) ) {
				Lib::error( '$plugin argument must be an object instance' );
				return false;
			}
			if ( ! is_subclass_of( $plugin, __NAMESPACE__ . '\\Abstract_Plugin' ) ) {
				Lib::error( '$plugin argument must be derived from ' . __NAMESPACE__ . '\\Abstract_Plugin' );
				return false;
			}
			if ( isset( $renderer ) && ! is_object( $renderer ) ) {
				Lib::error( '$renderer argument must be an object instance' );
				return false;
			}
			return true;
		}

		/** Validate Configuration Settings.
		 * Check if Configuration Settings complies with (minimal) requirements.
		 * Minimal requirements is to have menu and page configured.
		 * Returns true|false.
		 *
		 * @access public
		 * @param array  $settings configuration.
		 * @param object $renderer object instance.
		 * @return bool true|false.
		 */
		public static function is_valid_settings_configuration( $settings, $renderer ) {
			if ( is_array( $settings ) ) {
				if ( array_key_exists( 'menu', $settings ) ) {
					if ( ! is_string( $settings ['menu']['title'] ) ||
						trim( $settings ['menu']['title'] ) === '' ) {
						Lib::debug( '$settings[menu][title] is required not-empty string' );
						return false;
					}

					if ( array_key_exists( 'parent-slug', $settings ['menu'] ) ) {
						if ( ! is_string( $settings ['menu']['parent-slug'] ) ||
							trim( $settings ['menu']['parent-slug'] ) === '' ) {
							Lib::debug( '$settings[menu][parent-slug] must be not-empty string' );
							return false;
						}
					}
				} else {
					Lib::debug( '$settings[menu] array is required' );
					return false;
				}

				if ( array_key_exists( 'page', $settings ) ) {
					if ( ! is_string( $settings ['page']['title'] ) ||
						trim( $settings ['page']['title'] ) === '' ) {
						Lib::debug( '$settings[page][title] must be not-empty string' );
						return false;
					}
					if ( array_key_exists( 'render', $settings['page'] ) ) {
						if ( ! is_string( $settings ['page']['render'] ) ||
							trim( $settings ['page']['render'] ) === '' ) {
							Lib::debug( '$settings[page][render] must be not-empty string' );
							return false;
						}
						if ( ! is_callable( array( $renderer, $settings ['page']['render'] ) ) ) {
							Lib::debug( '($renderer, $settings[page][render]) is not callable' );
							return false;
						}
					}
				} else {
					Lib::debug( '$settings[page] array is required' );
					return false;
				}
			} else {
				Lib::debug( '$settings is not an array type' );
				return false;
			}
			return true;
		}

		/** Class constructor.
		 *
		 * @access protected
		 * @param object $plugin instance derived from Abstract_Plugin Class.
		 * @param object $renderer instance that implements rendering methods.
		 */
		protected function __construct( $plugin, $renderer = null ) {
			if ( self::is_valid_constructor_args( $plugin, $renderer ) ) {
				$this->plugin   = $plugin;
				$this->path     = $this->plugin->get_path();
				$this->prefix   = $this->plugin->get_prefix();
				$this->optionid = $this->plugin->get_option_id();
				$this->basename = $this->plugin->get_basename();
				$this->pageslug = $this->plugin->get_slug();

				$this->renderer = is_object( $renderer ) ? $renderer : $this;

				$this->settings = $this->read_configuration();

				if ( $this->is_valid_settings_configuration( $this->settings, $this->renderer ) ) {
					$this->usercaps = ( array_key_exists( 'capability', $this->settings )
					&& is_string( $this->settings ['capability'] ) )
					? $this->settings ['capability'] : $this->usercaps;

					$file = $this->plugin->get_file();
					register_activation_hook( $file, array( $this, 'on_user_activate_plugin' ) );
					register_deactivation_hook( $file, array( $this, 'on_user_deactivate_plugin' ) );
					register_uninstall_hook( $file, array( get_class( $this ), 'on_user_uninstall_plugin' ) );

					add_action( 'init', array( $this, 'on_abstract_init_action' ) );

				} else {
					Lib::error( 'Invalid settings configuration.' );
				}
			} else {
				Lib::error( 'Invalid constructor arguments.' );
			}
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Activation requirements, activate, deactivate, uninstall

		/** User Activate Plugin.
		 *
		 * Final, not intended to be overridden from derived class.
		 * Access is public because it is registered via register_activation_hook.
		 * Activation is triggered on 'activate_{$plugin}' action.
		 *
		 * @see https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/plugin.php
		 * @access public
		 * @internal callback
		 * @param bool $networkwide flag.
		 * @return void
		 */
		public function on_user_activate_plugin( $networkwide ) {
			if ( ! self::current_user_can_activate_plugins() ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to activate plugin.', 'warp-imagick' ) );
			}

			$this->on_abstract_activate_plugin( $networkwide, \wp_get_current_user() );
		}

		/** Abstract Activate Plugin.
		 *
		 * Final, not intended to be overridden from derived class.
		 * Access is public because it may be called from plugin class.
		 *
		 * @param bool   $networkwide flag.
		 * @param object $user by which plugin has been manually activated.
		 * @return void
		 */
		public function on_abstract_activate_plugin( $networkwide, $user = false ) {
			$fail = $this->check_activate_requirements( $networkwide, $this->settings );

			$custom_fail = $this->on_check_activate_requirements( $networkwide, $this->settings );
			if ( is_array( $custom_fail ) && ! empty( $custom_fail ) ) {
				$fail = array_merge( $fail, $custom_fail );
			}
			if ( is_array( $fail ) && ! empty( $fail ) ) {
				$this->on_activate_plugin_failure( $fail );
			} else {
				$this->on_activate_plugin_success( $networkwide, $user );
			}
		}

		/** Test if current user can activate plugins.
		 * Returns true|false.
		 *
		 * @access public
		 * @return bool true|false.
		 */
		public static function current_user_can_activate_plugins() {
			return function_exists( '\wp_get_current_user' )
			&& function_exists( '\current_user_can' )
			&& \current_user_can( 'activate_plugins' );
		}

		/** Check plugin activation requirements.
		 *
		 * @access public
		 * @param bool  $networkwide activation.
		 * @param array $settings (configuration).
		 * @return array with errors or empty.
		 */
		public function check_activate_requirements( $networkwide, $settings ) {
			$fail = array();
			if ( ! is_array( $settings ) ) {
				$fail [] = __( 'Invalid settings [].', 'warp-imagick' );
			} elseif ( array_key_exists( 'plugin', $settings ) ) {
				if ( ! is_array( $settings ['plugin'] ) ) {
					$fail [] = __( 'Invalid settings [plugin] value.', 'warp-imagick' );
				} elseif ( array_key_exists( 'requires', $settings ['plugin'] ) ) {
					$requires = $settings ['plugin']['requires'];
					if ( ! is_array( $requires ) ) {
						$fail [] = __( 'Invalid settings [plugin][requires] value.', 'warp-imagick' );
					} else {
						if ( array_key_exists( 'php', $requires ) && is_string( $requires ['php'] ) ) {
							if ( ! version_compare( phpversion(), $requires ['php'], '>=' ) ) {
								// Translators: %s is PHP version string.
								$fail [] = sprintf( __( 'Please upgrade PHP to version %s or higher.', 'warp-imagick' ), $requires ['php'] );
							}
						}
						if ( array_key_exists( 'wp', $requires ) ) {
							if ( ! is_string( $requires ['wp'] ) ) {
								$fail [] = __( 'Invalid settings [plugin][requires][wp] value.', 'warp-imagick' );
							} else {
								if ( 'latest' === $requires ['wp'] ) {
									$version = ( json_decode( wp_safe_remote_get( 'https://api.wordpress.org/core/version-check/1.7/' )['body'] )->offers[0]->version );
									if ( is_string( $version ) && ! version_compare( get_bloginfo( 'version' ), $version, '>=' ) ) {
										// Translators: %s is WordPress version.
										$fail [] = sprintf( __( 'Please upgrade WordPress to latest release version (%s).', 'warp-imagick' ), $version );
									}
								} elseif ( ! version_compare( get_bloginfo( 'version' ), $requires ['wp'], '>=' ) ) {
										// Translators: %s is WordPress version.
										$fail [] = sprintf( __( 'Please upgrade WordPress to version %s or higher.', 'warp-imagick' ), $requires ['wp'] );
								}
							}
						}
						if ( array_key_exists( 'timeout', $requires ) ) {
							if ( ! is_int( $requires ['timeout'] ) ) {
								$fail [] = __( 'Invalid settings [plugin][requires][timeout] value.', 'warp-imagick' );
							} else {
								$timeout = (int) ini_get( 'max_execution_time' );
								if ( set_time_limit( $requires ['timeout'] ) === true ) {
									set_time_limit( $timeout );
								} else {
										// Translators: %d is number of seconds.
										$fail [] = sprintf( __( 'Timeout is limited to %d seconds and cannot be changed.', 'warp-imagick' ), $timeout );
								}
							}
						}
						if ( array_key_exists( 'extensions', $requires ) ) {
							if ( ! is_array( $requires ['extensions'] ) ) {
								$fail [] = __( 'Invalid settings [plugin][requires][extensions] value.', 'warp-imagick' );
							} else {
								foreach ( $requires ['extensions'] as $ext_name => $require_name ) {
									if ( ! is_string( $ext_name ) ) {
										$fail [] = __( 'Invalid settings [plugin][requires][extensions] item.' );
									} elseif ( ! extension_loaded( $ext_name ) ) {
										// Translators: %s is PHP extension name as used in line above.
										$fail [] = sprintf( __( 'Required PHP extension: "%s" is missing.', 'warp-imagick' ), $require_name );
									}
								}
							}
						}
						if ( array_key_exists( 'active-plugins', $requires ) ) {
							if ( ! is_array( $requires ['active-plugins'] ) ) {
								$fail [] = __( 'Invalid settings [plugin][requires][active-plugins] value.', 'warp-imagick' );
							} else {
								$requires_plugins = $requires ['active-plugins'];
								$active_basenames = $networkwide ? get_site_option( 'active_sitewide_plugins' ) : get_option( 'active_plugins' );
								if ( is_array( $active_basenames ) ) {
									foreach ( $requires_plugins as $basename => $plugin_name ) {
										if ( ! is_string( $basename ) ) {
											$fail [] = __( 'Invalid settings [plugin][requires][active-plugins][key] value.', 'warp-imagick' );
										} elseif ( ! is_string( $plugin_name ) ) {
											// Translators: %s is plugin basename.
											$fail [] = sprintf( __( 'Invalid settings [plugin][requires][active-plugins][%s] value.', 'warp-imagick' ), $basename );
										} elseif ( ! in_array( $basename, $active_basenames, true ) ) {
											// Translators: %s is plugin name/slug.
											$fail [] = sprintf( __( 'Missing active basename "%1$s". Please install and/or activate "%2$s" plugin.', 'warp-imagick' ), $basename, $plugin_name );
										}
									}
								}
							}
						}
						if ( array_key_exists( 'classes', $requires ) ) {
							if ( ! is_array( $requires ['classes'] ) ) {
								$fail [] = 'Invalid settings [plugin][requires][classes] value.';
							} else {
								foreach ( $requires ['classes'] as $class_name => $require_name ) {
									if ( ! is_string( $class_name ) ) {
										$fail [] = __( 'Invalid settings [plugin][requires][classes][key] value.', 'warp-imagick' );
									} elseif ( ! is_string( $require_name ) ) {
										// Translators: %s is class name.
										$fail [] = sprintf( __( 'Invalid settings [plugin][requires][classes][%s] value.', 'warp-imagick' ), $class_name );
									} elseif ( ! class_exists( $class_name, true ) ) {
										// Translators: %1$s is class name, %2$s is require name.
										$fail [] = sprintf( __( 'Missing class "%1$s". Please install or activate "%2$s".', 'warp-imagick' ), $class_name, $require_name );
									}
								}
							}
						}
						if ( array_key_exists( 'constants', $requires ) ) {
							if ( ! is_array( $requires ['constants'] ) ) {
								$fail [] = __( 'Invalid settings [plugin][requires][constants] value.', 'warp-imagick' );
							} else {
								foreach ( $requires ['constants'] as $constant => $require_name ) {
									if ( ! is_string( $constant ) ) {
										$fail [] = __( 'Invalid settings [plugin][requires][constants][key] value.', 'warp-imagick' );
									} elseif ( ! is_string( $require_name ) ) {
										// Translators: %s is PHP constant name.
										$fail [] = sprintf( __( 'Invalid settings [plugin][requires][constants][%s] value.', 'warp-imagick' ), $constant );
									} elseif ( ! defined( $constant ) ) {
										if ( stripos( 'imagick::', $constant ) > 0 ) {
											// Translators: %1$s is PHP constant name, %2$s is requirement name.
											$fail [] = sprintf( __( 'Missing PHP-Imagick constant "%1$s". Please ask your host service provider for help to install "%2$s" module linked with ImageMagick library version 6', 'warp-imagick' ), $constant, $require_name );
										} else {
											// Translators: %1$s is PHP constant name, %2$s is requirement name.
											$fail [] = sprintf( __( 'Missing constant "%1$s". Please install and/or activate "%2$s".', 'warp-imagick' ), $constant, $require_name );
										}
									}
								}
							}
						}
						if ( array_key_exists( 'functions', $requires ) ) {
							if ( ! is_array( $requires ['functions'] ) ) {
								$fail [] = __( 'Invalid settings [plugin][requires][functions] value.', 'warp-imagick' );
							} else {
								foreach ( $requires ['functions'] as $function => $require_name ) {
									if ( ! is_string( $function ) ) {
										$fail [] = __( 'Invalid settings [plugin][requires][functions][key] value.', 'warp-imagick' );
									} elseif ( ! is_string( $require_name ) ) {
										// Translators: %s is PHP function name.
										$fail [] = sprintf( __( 'Invalid settings [plugin][requires][functions][%s] value.', 'warp-imagick' ), $function );
									} elseif ( ! function_exists( $function ) ) {
										// Translators: %1$s is PHP function name, %2$s is require name.
										$fail[] = sprintf( __( 'Missing function "%1$s". Please install or activate "%2$s".', 'warp-imagick' ), $function, $require_name );
									}
								}
							}
						}
						if ( array_key_exists( 'files', $requires ) ) {
							if ( ! is_array( $requires ['files'] ) ) {
								$fail [] = __( 'Invalid settings [plugin][requires][files] value.', 'warp-imagick' );
							} else {
								foreach ( $requires ['files'] as $file_name => $require_name ) {
									if ( ! is_string( $file_name ) ) {
										$fail [] = __( 'Invalid settings [plugin][requires][files][key] value.', 'warp-imagick' );
									} elseif ( ! is_string( $require_name ) ) {
										// Translators: %s is file name.
										$fail [] = sprintf( __( 'Invalid settings [plugin][requires][files][%s] value.', 'warp-imagick' ), $file_name );
									} elseif ( ! file_exists( WP_CONTENT_DIR . $file_name ) ) {
										// Translators: %1$s is file name, %2$s is require name.
										$fail [] = sprintf( __( 'Missing file "%1$s". Please install or activate "%2$s".', 'warp-imagick' ), $file_name, $require_name );
									}
								}
							}
						}
						if ( array_key_exists( 'no-files', $requires ) ) {
							if ( ! is_array( $requires ['no-files'] ) ) {
								$fail [] = __( 'Invalid settings [plugin][requires][no-files] value.', 'warp-imagick' );
							} else {
								foreach ( $requires ['no-files'] as $file_name => $require_name ) {
									if ( ! is_string( $file_name ) ) {
										$fail [] = __( 'Invalid settings [plugin][requires][no-files][key] key.', 'warp-imagick' );
									} elseif ( ! is_string( $require_name ) ) {
										// Translators: %s is file name.
										$fail [] = sprintf( __( 'Invalid settings [plugin][requires][no-files][%s] value.', 'warp-imagick' ), $file_name );
									} elseif ( file_exists( WP_CONTENT_DIR . $file_name ) ) {
										// Translators: %1$s is file name, %2$s is require name.
										$fail [] = sprintf( __( 'File exists "%1$s". Please remove "%2$s".', 'warp-imagick' ), $file_name, $require_name );
									}
								}
							}
						}
						if ( array_key_exists( 'local-ip', $requires ) ) {
							$local_ip = $requires ['local-ip'];
							if ( is_bool( $local_ip ) ) {
								if ( true === $local_ip ) {
									$local_ip = '127.0.0.1';
								}
							} elseif ( is_string( $local_ip ) ) {
								if ( filter_var( $local_ip, FILTER_VALIDATE_IP ) ) {
									if ( ! Lib::is_ip_private( $local_ip ) ) {
										$fail []  = __( 'Invalid settings [plugin][requires][local-ip] value (not private IPv4).', 'warp-imagick' );
										$local_ip = false;
									}
								} else {
									$fail []  = __( 'Invalid settings [plugin][requires][local-ip] value (IPv4).', 'warp-imagick' );
									$local_ip = false;
								}
							} else {
								$fail []  = __( 'Invalid settings [plugin][requires][local-ip] value type.', 'warp-imagick' );
								$local_ip = false;
							}
							if ( false !== $local_ip ) {
								$response = Lib::private_ajax_request( 'heartbeat', $local_ip );
								if ( is_array( $response )
								&& ! is_wp_error( $response )
								&& 200 === $response ['response']['code'] ) {
									$body = json_decode( $response ['body'], true );

									if ( ! array_key_exists( 'server_time', $body )
									|| ! array_key_exists( 'wp-auth-check', $body )
									|| false !== $body ['wp-auth-check'] ) {
										// Translators: %s is IPv4 number.
										$fail [] = sprintf( __( 'Access to "heartbeat" via %s is not available.', 'warp-imagick' ), $local_ip );
									}
								} else {
									// Translators: %s is IPv4 number.
									$fail [] = sprintf( __( 'Access to "admin-ajax.php" via %s is not available.', 'warp-imagick' ), $local_ip );
								}
							}
						}
						if ( array_key_exists( 'plugin', $requires ) ) {
							$requires_plugin = $requires ['plugin'];
							if ( is_array( $requires_plugin ) ) {
								$plugin_name = '';
								$plugin_data = array();
								if ( ! array_key_exists( 'name', $requires_plugin ) && ! array_key_exists( 'basename', $requires_plugin ) ) {
									$fail [] = __( 'Invalid [plugin][requires][plugin] settings. Define either "name" or "basename" or both', 'warp-imagick' );
								} else {
									$plugins = get_plugins();
									if ( array_key_exists( 'name', $requires_plugin ) ) {
										foreach ( $plugins as $base => $data ) {
											if ( is_array( $data ) && $data ['Name'] === $requires_plugin ['name'] ) {
												$plugin_data = $data;
												$plugin_name = $data ['Name'];
												break;
											}
										}
										if ( empty( $plugin_data ) ) {
											// Translators: %s is plugin name/slug or basename.
											$fail [] = sprintf( __( 'Please install and/or activate "%s" plugin', 'warp-imagick' ), $requires_plugin['name'] );
										}
									}
									if ( array_key_exists( 'basename', $requires_plugin ) ) {
										if ( ! array_key_exists( $requires_plugin ['basename'], $plugins ) ) {
											if ( empty( $plugin_data ) ) {
												// Translators: %s is plugin name/slug or basename.
												$fail[] = sprintf( __( 'Please install and/or activate "%s" plugin', 'warp-imagick' ), ( array_key_exists( 'name', $requires_plugin ) ? $requires_plugin ['name'] : $requires_plugin ['basename'] ) );
											} else {
												$fail [] = __( 'Invalid [plugin][requires][plugin] settings. Plugins for "basename" and "name" mismatch', 'warp-imagick' );
											}
										} elseif ( ! array_key_exists( 'name', $requires_plugin ) ) {
											$data        = $plugins [ $requires_plugin ['basename'] ];
											$plugin_data = $data;
											$plugin_name = $data ['Name'];
										} elseif ( $plugin_data !== $plugins [ $requires_plugin ['basename'] ] ) {
											if ( empty( $plugin_data ) ) {
												$fail [] = __( 'Invalid [plugin][requires][plugin] settings. Plugin "name" fails to match "basename".', 'warp-imagick' );
											} else {
												$fail [] = __( 'Invalid [plugin][requires][plugin] settings. Plugins for "name" and "basename" mismatch', 'warp-imagick' );
											}
										}
									}
								}
								if ( '' === $plugin_name ) {
									if ( ! array_key_exists( 'name', $requires_plugin ) || trim( $requires_plugin ['name'] ) === '' ) {
										$plugin_name = 'Unknown';
									} else {
										$plugin_name = $requires_plugin ['name'];
									}
								}
								if ( array_key_exists( 'version', $requires_plugin ) ) {
									if ( ! is_string( $requires_plugin ['version'] ) ) {
										$fail [] = __( 'Invalid [plugin][requires][plugin][version] settings value.', 'warp-imagick' );
									} else {
										if ( empty( $plugin_data ) ) {
											$fail [] = __( 'Invalid [plugin][requires][plugin] settings. No plugin version found for "name" or "basename".', 'warp-imagick' );
										} else {
											$version = '0';
											if ( array_key_exists( 'Version', $plugin_data ) ) {
												$version = $data ['Version'];
											}
											if ( ! version_compare( $version, $requires_plugin ['version'], '>=' ) ) {
												// Translators: %1$s is plugin name/slug, %2$s is current version, %3$s is required version.
												$fail [] = sprintf( __( 'Please update "%1$s" plugin from version "%2$s" to version "%3$s" or higher', 'warp-imagick' ), $plugin_name, $version, $requires_plugin ['version'] );
											}
										}
									}
								}
								if ( array_key_exists( 'class', $requires_plugin ) ) {
									if ( ! is_string( $requires_plugin ['class'] ) ) {
										$fail [] = __( 'Invalid [plugin][requires][plugin][class] settings.', 'warp-imagick' );
									} else {
										if ( ! class_exists( $requires_plugin ['class'], true ) ) {
											// Translators: %1$s is plugin name/slug, %2$s is class name.
											$fail [] = sprintf( __( 'Please install and/or activate "%1$s" plugin providing required class: "%2$s"', 'warp-imagick' ), $plugin_name, $requires_plugin ['class'] );
										}
									}
								}
								if ( array_key_exists( 'constant', $requires_plugin ) ) {
									if ( ! is_string( $requires_plugin ['constant'] ) ) {
										$fail [] = __( 'Invalid [plugin][requires][plugin][constant] settings value.', 'warp-imagick' );
									} else {
										if ( ! defined( $requires_plugin ['constant'] ) ) {
											// Translators: %1$s is plugin name/slug, %2$s is constant name.
											$fail [] = sprintf( __( 'Please install and/or activate "%1$s" plugin providing required constant: "%2$s"', 'warp-imagick' ), $plugin_name, $requires_plugin ['constant'] );
										}
									}
								}
								if ( array_key_exists( 'function', $requires_plugin ) ) {
									if ( ! is_string( $requires_plugin ['function'] ) ) {
										$fail [] = __( 'Invalid [plugin][requires][plugin][function] settings value.', 'warp-imagick' );
									} else {
										if ( ! function_exists( $requires_plugin ['function'] ) ) {
											// Translators: %1$s is plugin name/slug, %2$s is function name.
											$fail [] = sprintf( __( 'Please install and/or activate "%1$s" plugin providing required function: "%2$s"', 'warp-imagick' ), $plugin_name, $requires_plugin ['function'] );
										}
									}
								}
								if ( array_key_exists( 'file', $requires_plugin ) ) {
									if ( ! is_string( $requires_plugin ['file'] ) ) {
										$fail [] = __( 'Invalid [plugin][requires][plugin][file] settings.', 'warp-imagick' );
									} else {
										if ( ! file_exists( WP_CONTENT_DIR . $requires_plugin ['file'] ) ) {
											// Translators: %1$s is plugin (name) that requires %2$s file (name) in wp-content directory.
											$fail [] = sprintf( __( 'Please install and/or activate "%1$s" plugin providing required file: "%2$s"', 'warp-imagick' ), $plugin_name, $requires_plugin ['file'] );
										}
									}
								}
							}
						}
					}
				}
			}
			return $fail;
		}

		/** On User Deactivate plugin.
		 *
		 * Final, not intended to be overridden from derived class.
		 * Access is public because it is registered via register_deactivation_hook.
		 * Deactivation is triggered on 'deactivate_{$plugin}' action
		 *
		 * @see https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/plugin.php
		 * @access public
		 * @internal callback
		 * @param bool $networkwide flag.
		 * @return void
		 */
		public function on_user_deactivate_plugin( $networkwide ) {
			if ( self::current_user_can_deactivate_plugins() ) {
				$this->on_deactivate_plugin( $networkwide );
			} else {
				wp_die( esc_html__( 'You do not have sufficient permissions to deactivate plugin.', 'warp-imagick' ) );
			}
		}

		/** Test if current user can deactivate plugins.
		 * Returns true|false.
		 *
		 * @access public
		 * @return bool true|false.
		 */
		public static function current_user_can_deactivate_plugins() {
			return self::current_user_can_activate_plugins();
		}

		/** On User Uninstall plugin.
		 *
		 * Static, not intended to be overridden from derived class.
		 * Access is public because it must be for register_uninstall_hook.
		 * Uninstall is triggered on 'uninstall_{$file}' action
		 *
		 * @see https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/plugin.php
		 * @access public
		 * @internal callback
		 * @return void
		 */
		public static function on_user_uninstall_plugin() {
			if ( self::current_user_can_uninstall_plugins() ) {
				static::on_uninstall_plugin();
			} else {
				wp_die( esc_html__( 'You do not have sufficient permissions to uninstall plugins.' ) );
			}
		}

		/** Test if current user can uninstall plugins.
		 * Returns true|false.
		 *
		 * @access public
		 * @return bool true|false.
		 */
		public static function current_user_can_uninstall_plugins() {
			return \function_exists( '\wp_get_current_user' )
			&& \function_exists( '\current_user_can' )
			&& \current_user_can( 'delete_plugins' );
		}

		/** Override to implement custom code on plugin activation requirements
		 *
		 * At this point, plugin PHP/WP requirements are already checked, maybe invalid (if configured).
		 *
		 * @access protected
		 * @param bool  $networkwide flag.
		 * @param array $settings - Plugin Settings (configured activate requirements).
		 * @return void or exit([$message]) to abort activation
		 */
		protected function on_check_activate_requirements( $networkwide, $settings ) {}

		/** Override to implement custom activation failure
		 *
		 * @access protected
		 * @param array $fail an array or strings, requirements missing.
		 *
		 * @return void or exit/die([$message]) to abort activation.
		 */
		protected function on_activate_plugin_failure( $fail ) {
			if ( is_array( $fail ) ) {
				Lib::error( implode( PHP_EOL, $fail ) );
				foreach ( $fail as $fail_reason ) {
					echo '<p>' . esc_html( $fail_reason ) . '</p>';
				}
			}
			// phpcs:ignore -- Using Debug and Silencing notice and warning is intentional.
			@trigger_error( esc_html( 'Activation failed due to missing requirement(s).' ) );
		}

		/** Override to implement custom activation code.
		 * At this point, plugin PHP/WP requirements are already checked and valid (if configured).
		 *
		 * @access protected
		 * @param bool   $networkwide flag.
		 * @param object $user by which plugin has been manually activated.
		 * @return void or exit([$message]) to abort activation
		 */
		protected function on_activate_plugin_success( $networkwide, $user = false ) {}

		/** Override to implement custom deactivation code.
		 *
		 * @access protected
		 * @param bool $networkwide flag.
		 * @return void or exit([$message]) to abort deactivation
		 */
		protected function on_deactivate_plugin( $networkwide ) {}

		/** Static! Override to implement custom uninstall code.
		 *
		 * @access protected
		 * @return mixed or exit([$message]) to abort uninstall
		 */
		protected static function on_uninstall_plugin() {}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Initialization, Enqueue, Setup ....

		/** On wp-init action handler. Adds Admin-Page actions/filters
		 *
		 * @ignore
		 * @access public
		 * @internal callback
		 * @return void
		 */
		public function on_abstract_init_action() {
			if ( ! $this->authorized() ) {
				return;
			}

			$server = wp_unslash( $_SERVER );
			if ( is_array( $server )
			&& array_key_exists( 'QUERY_STRING', $server )
			&& false !== strpos( $server['QUERY_STRING'], 'page=' . $this->pageslug ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'on_abstract_admin_enqueue_scripts' ) );
			}

			add_action( 'admin_init', array( $this, 'on_abstract_register_settings_page' ) );

			add_action( 'admin_menu', array( $this, 'on_abstract_register_settings_menu' ) );

			add_filter(
				'plugin_action_links_' . $this->basename,
				function ( $links ) {
					$menu_parent   = $this->get_menu_parent_slug();
					$settings_link = add_query_arg(
						'page',
						$this->pageslug,
						admin_url() . ( $menu_parent ? $menu_parent : 'admin.php' )
					);

					$icon = array_key_exists( 'settings-icon', $this->settings ['menu'] ) ?
						trim( $this->settings ['menu']['settings-icon'] ) :
						'⚙';
					$icon = trim( $icon ) ? $icon . ' ' : '';
					$name = array_key_exists( 'settings-name', $this->settings ['menu'] ) ?
						trim( $this->settings ['menu']['settings-name'] ) : 'Settings';
					$link = '<a href="' . $settings_link . '">' . $icon . $name . '</a>';
					array_unshift( $links, $link );
					return $links;
				}
			);

			$this->authorized_init_action();
		}

		/** Enqueue Admin-Page styles and scripts.
		 *
		 * @access public
		 * @internal callback
		 * @return void
		 */
		public function on_abstract_admin_enqueue_scripts() {
			if ( ! $this->authorized() ) {
				return;
			}

			$relative_path = Lib::relative_path( __DIR__ );

			Lib::enqueue_style( 'abstract-settings-admin', $relative_path . '/assets/admin.css', array(), $this->plugin->get_abstract_version(), 'screen' );

			Lib::register_style( 'abstract-settings-jquery-chosen', $relative_path . '/assets/chosen/chosen.min.css', array(), '1.8.2', 'screen' );

			Lib::register_style( 'abstract-settings-admin-styled', $relative_path . '/assets/admin-styled.css', array( 'abstract-settings-jquery-chosen' ), $this->plugin->get_abstract_version(), 'screen' );

			$wp_script_dependencies = array(
				'jquery',
				'utils',
				'common',
				'postbox',
				'accordion',
				'jquery-ui-draggable',
				'jquery-ui-droppable',
				'jquery-ui-sortable',
				'wp-lists',
			);

			foreach ( $wp_script_dependencies as $script_identifier ) {
				Lib::enqueue_script( $script_identifier );
			}

			Lib::enqueue_script( 'abstract-settings-admin', $relative_path . '/assets/admin.js', $wp_script_dependencies, $this->plugin->get_abstract_version(), $in_footer = true );

			$wp_script_dependencies = array( 'jquery' );

			Lib::register_script( 'abstract-settings-jquery-are-you-sure', $relative_path . '/assets/ays/jquery.are-you-sure.js', array( 'jquery' ), '1.9.0' );

			$wp_script_dependencies [] = 'abstract-settings-jquery-are-you-sure';

			Lib::register_script( 'abstract-settings-jquery-chosen', $relative_path . '/assets/chosen/chosen.jquery.min.js', array( 'jquery' ), '1.8.2' );

			$wp_script_dependencies [] = 'abstract-settings-jquery-chosen';
			$wp_script_dependencies [] = 'abstract-settings-admin';

			Lib::register_script( 'abstract-settings-admin-styled', $relative_path . '/assets/admin-styled.js', $wp_script_dependencies, $this->plugin->get_abstract_version(), $in_footer = true );

			$this->enqueue_page_scripts();
		}

		/** Get menu parent slug.
		 *
		 * @access public
		 * @return bool|string - false or valid menu-parent string
		 */
		public function get_menu_parent_slug() {
			$menu_parent = Lib::safe_key_value( $this->plugin->get_option(), array( 'configuration', 'menu', 'parent-slug' ), '', false );
			if ( false === $menu_parent && array_key_exists( 'parent-slug', $this->settings ['menu'] ) ) {
				$menu_parent = $this->settings ['menu']['parent-slug'];
			}
			return self::is_valid_menu_parent_slug( $menu_parent ) ? $menu_parent : false;
		}

		/** Check menu parent slug.
		 *
		 * @access public
		 * @param string $menu_parent_slug to validate.
		 * @return bool
		 */
		public static function is_valid_menu_parent_slug( $menu_parent_slug ) {
			return in_array(
				$menu_parent_slug,
				array(
					'index.php',
					'tools.php',
					'options-general.php',
					'plugins.php',
					'users.php',
					'profile.php',
					'edit.php',
					'edit-comments.php',
					'upload.php',
					'themes.php',
				),
				true
			);
		}

		/** Add menu-page.
		 * Handler for 'admin-menu' action.
		 *
		 * @ignore
		 * @access public
		 * @internal callback
		 * @return void
		 */
		public function on_abstract_register_settings_menu() {
			if ( ! $this->authorized() ) {
				return;
			}

			$this->menuslug = false;

			$page_render = array( $this, 'render_settings_page' );
			if ( array_key_exists( 'render', $this->settings ['page'] )
				&& is_string( $this->settings ['page']['render'] )
			) {
				$page_render_callback = array( $this->renderer, $this->settings ['page']['render'] );
				if ( is_callable( $page_render_callback ) ) {
					$page_render = $page_render_callback;
				} else {
					Lib::debug( '$this->settings[page][render] is not callable' );
				}
			}

			$menu_parent = $this->get_menu_parent_slug();
			if ( $menu_parent ) {
				$this->menuslug = add_submenu_page(
					$menu_parent,
					$this->settings ['page']['title'],
					$this->settings ['menu']['title'],
					$this->usercaps,
					$this->pageslug,
					$page_render
				);

			} else {
				$menu_position = Lib::safe_key_value( $this->plugin->get_option(), array( 'configuration', 'menu', 'position' ), 0, false );
				if ( false === $menu_position ) {
					if ( array_key_exists( 'position', $this->settings ['menu'] ) ) {
						$menu_position = abs( intval( $this->settings ['menu']['position'] ) );
					} else {
						$menu_position = 0;
					}
				}
				$menu_icon = Lib::safe_key_value( $this->settings ['menu'], 'menu-icon', 'none' );

				$this->menuslug = add_menu_page(
					$this->settings ['page']['title'],
					$this->settings ['menu']['title'],
					$this->usercaps,
					$this->pageslug,
					$page_render,
					$menu_icon,
					$menu_position
				);
			}

			if ( false === $this->menuslug ) {
				$this->menuslug = add_options_page(
					$this->settings ['page']['title'],
					$this->settings ['menu']['title'],
					$this->usercaps,
					$this->pageslug,
					$page_render
				);
			}
			if ( false === $this->menuslug ) {
				Lib::error( 'Failed to add menu!' );
			} else {
				add_action( "load-{$this->menuslug}", array( $this, 'on_abstract_prepare_settings_page' ) );
			}
		}

		/** Register Settings Page, on 'admin-init' action.
		 *
		 * Register Settings Page, OptionId & validation callback, on 'admin-init' action.
		 *
		 * @ignore
		 * @access public
		 * @internal callback
		 * @return void
		 */
		public function on_abstract_register_settings_page() {
			if ( ! $this->authorized() ) {
				return;
			}

			if ( array_key_exists( 'validate', $this->settings ) ) {
				$config_validate_method = array( $this, $this->settings ['validate'] );
				if ( is_callable( $config_validate_method ) ) {
					$this->validate = $config_validate_method;
				} else {
					$this->validate = null;
					Lib::debug( '($this, $this->settings[validate]) is not callable!' );
				}
			}

			register_setting(
				$this->pageslug,
				$this->optionid,
				array(
					'type'              => 'array',
					'group'             => $this->pageslug,
					'description'       => 'Plugin Settings',
					'sanitize_callback' => array( $this, 'on_sanitize_callback' ),
					'show_in_rest'      => false,

				)
			);

			// phpcs:ignore
			if ( isset( $_POST ) ) {
				\add_filter(
					"pre_update_option_{$this->optionid}",
					array( $this, 'on_abstract_validate_form_input' ),
					10,
					3
				);
			}
		}

		/** Add Settings Section.
		 * Register section with WP (add_settings_section).
		 *
		 * @access protected
		 * @uses add_settings_section
		 * @param string $section_id - section id.
		 * @param array  $section_settings ('title' => 'Title', ['render'=>'method_name',...]).
		 * @return void
		 */
		protected function add_settings_section( $section_id, $section_settings ) {
			if ( ! $this->authorized() ) {
				return;
			}

			if ( ! is_string( $section_id ) || trim( $section_id ) === '' ) {
				Lib::debug( '$section_id argument type error' );
				return;
			}

			if ( ! is_array( $section_settings ) ) {
				Lib::debug( '$section_settings is not array!' );
				return;
			}

			$section_title = 'Section Fields';
			if ( array_key_exists( 'title', $section_settings ) && is_string( $section_settings ['title'] ) ) {
				$section_title = $section_settings ['title'];
			} else {
				Lib::debug( '$section_settings [title] is required string!' );
			}

			$section_callback = null;
			if ( array_key_exists( 'render', $section_settings ) ) {
				if ( is_string( $section_settings ['render'] ) ) {
					if ( is_callable( array( $this->renderer, $section_settings ['render'] ) ) ) {
						$section_callback = array( $this->renderer, $section_settings ['render'] );
					} else {
						Lib::error( 'Section "render" function name is not callable: ' . $section_settings ['render'] );
					}
				} elseif ( ! in_array( $section_settings ['render'], array( null, false ), true ) ) {
					Lib::debug( 'Section "render" function name is not a string. Section: ' . $section_id );
				}
			}

			add_settings_section(
				$section_id,
				$section_title,
				$section_callback,
				$this->pageslug
			);
		}

		/** Add settings-field to settings-section.
		 * Register field with WP-section (add_settings_field).
		 * Uses $field_settings[type] key to determine default field renderer,
		 * or $field_settings[render] for custom renderer.
		 *
		 * @access protected
		 * @uses \add_settings_field
		 * @param string $field_name (field $key from Config-Settings).
		 * @param array  $field_settings (field $val from Config-Settings).
		 * @param string $section_id (parent section from Config-Settings).
		 * @return bool $success
		 */
		protected function add_settings_field( $field_name, $field_settings, $section_id = 'default' ) {
			if ( ! $this->authorized() ) {
				return false;
			}

			if ( ! is_string( $field_name ) || trim( $field_name ) === '' ) {
				Lib::debug( '$field_name argument type or value error' );
				return false;
			}

			if ( ! is_array( $field_settings ) || count( $field_settings ) === 0 ) {
				Lib::debug( '$field_settings argument type or value error' );
				return false;
			}

			if ( ! is_string( $section_id ) || trim( $section_id ) === '' ) {
				Lib::debug( '$section_id argument type or value error' );
				return false;
			}

			$render_callable = false;

			if ( array_key_exists( 'render', $field_settings )
			&& is_callable( array( $this->renderer, $field_settings ['render'] ) ) ) {
				$render_callable = array( $this->renderer, $field_settings ['render'] );

			} elseif ( array_key_exists( 'type', $field_settings )
				&& is_string( $field_settings ['type'] ) ) {
				$field_settings_type = strtolower( $field_settings ['type'] );
				if ( array_key_exists( $field_settings_type, $this->input_field_types ) ) {
					if ( is_string( $this->input_field_types [ $field_settings_type ] ) ) {
						$render_callable = array( $this, $this->input_field_types [ $field_settings_type ] );
					} else {
						$render_callable = array( $this, $this->input_field_default_renderer );
					}
				}
			}

			if ( false === $render_callable ) {
				Lib::debug( 'Render for "' . $field_name . '" is not defined!' );
				return false;

			} elseif ( ! is_callable( $render_callable ) ) {
				Lib::debug( 'Render for "' . $field_name . '" is not callable!' );
				return false;
			}

			$args_settings = array();

			$args_settings ['fn'] = $field_name;

			$args_settings ['id']   = $this->pageslug . '-' . $field_name;
			$args_settings ['name'] = $this->optionid . '[' . $field_name . ']';

			$args_settings ['type']    = ( array_key_exists( 'type', $field_settings ) && is_string( $field_settings ['type'] ) && trim( $field_settings ['type'] ) !== '' ) ? $field_settings ['type'] : 'unknown';
			$args_settings ['label']   = ( array_key_exists( 'label', $field_settings ) && is_string( $field_settings ['label'] ) && trim( $field_settings ['label'] ) !== '' ) ? $field_settings ['label'] : $field_name;
			$args_settings ['default'] = ( array_key_exists( 'default', $field_settings ) ) ? $field_settings ['default'] : null;

			if ( array_key_exists( 'class', $field_settings ) && is_string( $field_settings ['class'] ) ) {
				$args_settings ['class'] = $field_settings ['class'];
			}
			if ( array_key_exists( 'title', $field_settings ) && is_string( $field_settings ['title'] ) ) {
				$args_settings ['title'] = $field_settings ['title'];
				$args_settings ['label'] =
					'<div title="' . esc_attr( $args_settings ['title'] ) . '">' .
					$args_settings ['label'] .
					'</div>';
			}
			if ( array_key_exists( 'placeholder', $field_settings ) && is_string( $field_settings ['placeholder'] ) ) {
				$args_settings ['placeholder'] = $field_settings ['placeholder'];
			}
			if ( array_key_exists( 'style', $field_settings ) && is_string( $field_settings ['style'] ) ) {
				$args_settings ['style'] = $field_settings ['style'];
			}
			if ( array_key_exists( 'options', $field_settings ) && is_array( $field_settings ['options'] ) ) {
				$args_settings ['options'] = $field_settings ['options'];
			}

			$args               = array( 'settings' => $args_settings );
			$args ['label_for'] = $args_settings ['id'];
			$args ['class']     = $args_settings ['id'];
			add_settings_field(
				$this->optionid . '_' . $field_name,
				'hidden' === $args_settings ['type'] ? '' : $args_settings ['label'],
				$render_callable,
				$this->pageslug,
				$section_id,
				$args
			);
			return true;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Render Fields.

		/** Get (extract and check) field settings $args [settings].
		 *
		 * @ignore
		 * @access private
		 * @param array $args - field arguments.
		 * @return array $args [settings]
		 */
		private function get_field_settings_args( $args ) {
			if ( Lib::is_debug() ) {
				if ( ! is_array( $args ) ) {
					Lib::debug( '$args is not an array' );
					return array();
				}

				if ( array_key_exists( 'settings', $args ) ) {
					if ( ! is_array( $args ['settings'] ) ) {
						Lib::debug( '$args[settings] is not an array' );
						return array();
					}
				} else {
					Lib::debug( '$args[settings] key does not exists' );
					return array();
				}

				if ( ! array_key_exists( 'fn', $args ['settings'] ) ) {
					Lib::debug( 'No key "fn" found in $args array!' );
					return;
				}
				if ( ! is_string( $args ['settings']['fn'] ) ) {
					Lib::debug( 'Value of key "fn" in $args array is not an string!' );
					return;
				}
				if ( trim( $args ['settings']['fn'] ) === '' ) {
					Lib::debug( 'Value of key "fn" in $args array is empty!' );
					return;
				}

				if ( ! array_key_exists( 'id', $args ['settings'] ) ) {
					Lib::debug( 'No key "id" found in $args array!' );
					return;
				}
				if ( ! is_string( $args ['settings']['id'] ) ) {
					Lib::debug( 'Value of key "id" in $args array is not an string!' );
					return;
				}
				if ( trim( $args ['settings']['id'] ) === '' ) {
					Lib::debug( 'Value of key "id" in $args array is empty!' );
					return;
				}

				if ( ! array_key_exists( 'name', $args ['settings'] ) ) {
					Lib::debug( 'No key "name" found in $args array!' );
					return;
				}
				if ( ! is_string( $args ['settings']['name'] ) ) {
					Lib::debug( 'Value of key "name" in $args array is not an string!' );
					return;
				}
				if ( trim( $args ['settings']['name'] ) === '' ) {
					Lib::debug( 'Value of key "name" in $args array is empty!' );
					return;
				}
			}

			return $args ['settings'];
		}

		/** Get form field Option API value.
		 * It is set to default if option is null (not set) and default exists.
		 *
		 * @ignore
		 * @access private
		 * @param string $fn - field name.
		 * @param array  $args - arguments.
		 * @return mixed $value - value.
		 */
		private function get_form_field_option_value( $fn, $args ) {
			$value = $this->get_form_field_value( $fn, $this->get_option( $fn ) );
			return ( null === $value && array_key_exists( 'default', $args ) ? $args ['default'] : $value );
		}

		/** CheckBox input-type renderer
		 *
		 * @access public
		 * @param array $field_args : [fn: id: name: default: placeholder: title: class: style: options[...]].
		 * @return void (echo)
		 */
		public function render_checkbox_input_field( $field_args ) {
			$args = $this->get_field_settings_args( $field_args );

			$fn   = $args ['fn'];
			$id   = esc_attr( $args ['id'] );
			$name = esc_attr( $args ['name'] ) . '[]';

			$field_options = array_key_exists( 'options', $args ) ? $args ['options'] : array();
			$disabled      = array_key_exists( 'disabled', $field_options ) ? true === $field_options ['disabled'] : false;
			$readonly      = array_key_exists( 'readonly', $field_options ) ? true === $field_options ['readonly'] : false;

			$args ['value'] = $this->get_form_field_option_value( $fn, $args );

			$args = $this->apply_field_args_filters( $args, $fn );

			$value = isset( $args ['value'] ) && in_array( $args ['value'], array( 'on', true ), true ) ? ' value="' . esc_attr( $args ['value'] ) . '" checked' : '';

			$title = esc_attr( array_key_exists( 'title', $args ) ? $args ['title'] : null );
			$class = esc_attr( array_key_exists( 'class', $args ) ? $args ['class'] : null );
			$style = esc_attr( array_key_exists( 'style', $args ) ? $args ['style'] : null );

			$attrs = array();

			$title ? $attrs[] = "title=\"$title\"" : null;
			$class ? $attrs[] = "class=\"$class\"" : null;
			$style ? $attrs[] = "style=\"$style\"" : null;

			$disabled ? $attrs[] = 'disabled' : null;
			$readonly ? $attrs[] = 'readonly' : null;

			$attrs = trim( implode( ' ', $attrs ) );
			$attrs = '' === $attrs ? '' : ' ' . $attrs;

			$html = "<input id=\"$id\" type=\"checkbox\" name=\"$name\"$value$attrs/>";

			$html = $this->apply_field_html_filters( $html, $args, $fn );

			$html .= "<input id=\"$id-hidden\" type=\"hidden\" name=\"$name\" value=\"\"/>";

			Lib::echo_html( $html );
		}

		/** RangeSlider input-type renderer.
		 * Does not support 'multiple' option.
		 *
		 * @access public
		 * @param array $field_args : [fn: id: name: default: placeholder: title: class: style: options[...]].
		 * @return void (echo)
		 */
		public function render_range_slider_input_field( $field_args ) {
			$args = $this->get_field_settings_args( $field_args );

			$fn   = $args ['fn'];
			$id   = esc_attr( $args ['id'] );
			$name = esc_attr( $args ['name'] );

			$field_options = array_key_exists( 'options', $args ) ? $args ['options'] : array();
			$disabled      = array_key_exists( 'disabled', $field_options ) ? true === $field_options ['disabled'] : false;
			$readonly      = array_key_exists( 'readonly', $field_options ) ? true === $field_options ['readonly'] : false;
			$range_min     = array_key_exists( 'min', $field_options ) ? $field_options['min'] : 0;
			$range_max     = array_key_exists( 'max', $field_options ) ? $field_options['max'] : 100;
			$range_step    = array_key_exists( 'step', $field_options ) ? $field_options['step'] : 1;

			$args ['value'] = $this->get_form_field_option_value( $fn, $args );

			$args = $this->apply_field_args_filters( $args, $fn );

			$value = esc_attr( $args ['value'] );

			$title = esc_attr( array_key_exists( 'title', $args ) ? $args ['title'] : null );
			$class = esc_attr( array_key_exists( 'class', $args ) ? $args ['class'] : null );
			$style = esc_attr( array_key_exists( 'style', $args ) ? $args ['style'] : null );

			$attrs = array();

			$title ? $attrs[] = "title=\"$title\"" : null;
			$class ? $attrs[] = "class=\"$class\"" : null;
			$style ? $attrs[] = "style=\"$style\"" : null;

			$disabled ? $attrs[] = 'disabled' : null;
			$readonly ? $attrs[] = 'readonly' : null;

			\is_int( $range_min ) ? $attrs[]  = "min=\"$range_min\"" : null;
			\is_int( $range_max ) ? $attrs[]  = "max=\"$range_max\"" : null;
			\is_int( $range_step ) ? $attrs[] = "step=\"$range_step\"" : null;

			$attrs = \trim( \implode( ' ', $attrs ) );
			$attrs = '' === $attrs ? '' : ' ' . $attrs;

			$html = "<input type=\"range\" id=\"$id\" name=\"$name\" value=\"$value\" oninput=\"document.getElementById('{$id}_out').value=this.value;\"$attrs>";

			$html = $this->apply_field_html_filters( $html, $args, $fn );

			Lib::echo_html( $html );
		}

		/** Hidden input-type renderer.
		 * Does not support 'multiple' option.
		 *
		 * @access public
		 * @param array $field_args : [fn: id: name: default: placeholder: title: class: style: options[...]].
		 * @return void (echo)
		 */
		public function render_hidden_input_field( $field_args ) {
			$args = $this->get_field_settings_args( $field_args );

			$fn   = $args ['fn'];
			$id   = esc_attr( $args ['id'] );
			$name = esc_attr( $args ['name'] );

			$args ['value'] = $this->get_form_field_option_value( $fn, $args );

			$args = $this->apply_field_args_filters( $args, $fn );

			$value = esc_attr( $args ['value'] );

			$html = "<input type=\"hidden\" id=\"$id\" name=\"$name\" value=\"$value\">";

			$html = $this->apply_field_html_filters( $html, $args, $fn );

			Lib::echo_html( $html );
		}

		/** TextField input-type renderer.
		 * Supports 'multiple' option.
		 *
		 * @access public
		 * @param array $field_args : [fn: id: name: default: placeholder: title: class: style: options[...]].
		 * @return void (echo)
		 */
		public function render_text_input_field( $field_args ) {
			$args = $this->get_field_settings_args( $field_args );

			$fn   = $args ['fn'];
			$id   = esc_attr( $args ['id'] );
			$name = esc_attr( $args ['name'] );

			if ( Lib::starts_with( $args ['type'], 'text-' ) ) {
				$type = esc_attr( substr( $args ['type'], 5 ) );
			} else {
				$type = esc_attr( $args ['type'] );
			}

			$field_options = array_key_exists( 'options', $args ) ? $args ['options'] : array();
			$autofocus     = array_key_exists( 'autofocus', $field_options ) ? true === $field_options ['autofocus'] : false;
			$disabled      = array_key_exists( 'disabled', $field_options ) ? true === $field_options ['disabled'] : false;
			$readonly      = array_key_exists( 'readonly', $field_options ) ? true === $field_options ['readonly'] : false;
			$required      = array_key_exists( 'required', $field_options ) ? true === $field_options ['required'] : false;
			$multiple      = array_key_exists( 'multiple', $field_options ) ? true === $field_options ['multiple'] : false;
			$sortable      = array_key_exists( 'sortable', $field_options ) ? true === $field_options ['sortable'] : false;
			$range_min     = false;
			$range_max     = false;
			$range_step    = false;
			if ( 'range' === $type || 'number' === $type ) {
				$range_min  = array_key_exists( 'min', $field_options ) ? $field_options ['min'] : 0;
				$range_max  = array_key_exists( 'max', $field_options ) ? $field_options ['max'] : 100;
				$range_step = array_key_exists( 'step', $field_options ) ? $field_options ['step'] : 1;
			}

			$args ['value'] = $this->get_form_field_option_value( $fn, $args );

			if ( $multiple ) {
				$name         .= '[]';
				$args ['name'] = $name;

				if ( ! is_array( $args ['value'] ) ) {
					$args ['value'] = array( $args ['value'] );
				}
			} else {
				if ( is_array( $args ['value'] ) ) {
					$args ['value'] = array_shift( $args ['value'] );
				}
			}

			$args = $this->apply_field_args_filters( $args, $fn );

			$value = $args ['value'];

			$place = esc_attr( array_key_exists( 'placeholder', $args ) ? $args ['placeholder'] : null );
			$title = esc_attr( array_key_exists( 'title', $args ) ? $args ['title'] : null );
			$class = esc_attr( array_key_exists( 'class', $args ) ? $args ['class'] : null );
			$style = esc_attr( array_key_exists( 'style', $args ) ? $args ['style'] : null );

			$attrs = array();

			$place ? $attrs[] = "placeholder=\"$place\"" : null;
			$title ? $attrs[] = "title=\"$title\"" : null;
			$class ? $attrs[] = "class=\"$class\"" : null;
			$style ? $attrs[] = "style=\"$style\"" : null;

			$autofocus ? $attrs[] = 'autofocus' : null;
			$disabled ? $attrs[]  = 'disabled' : null;
			$readonly ? $attrs[]  = 'readonly' : null;
			$required ? $attrs[]  = 'required' : null;

			\is_int( $range_min ) ? $attrs[]  = "min=\"$range_min\"" : null;
			\is_int( $range_max ) ? $attrs[]  = "max=\"$range_max\"" : null;
			\is_int( $range_step ) ? $attrs[] = "step=\"$range_step\"" : null;

			$attrs = trim( implode( ' ', $attrs ) );
			$attrs = '' === $attrs ? '' : ' ' . $attrs;

			$html = '';
			if ( $multiple ) {
				$html_value = esc_attr( array_shift( $value ) );
				$item_html  = $this->apply_field_html_filters( "<input type=\"$type\" id=\"$id\" name=\"$name\" value=\"$html_value\"$attrs>", $args, $fn );

				if ( $sortable ) {
					$item_head  = '<li style="margin:initial;padding:initial;cursor:pointer">';
					$item_head .= '<span title="Double click to dismiss" class="multiple-input multiple-remove dashicons-before dashicons-trash"></span>';
					$item_tail  = '<span title="Drag&amp;drop up/down" class="multiple-input dashicons-before dashicons-sort"></span></li>';
				} else {
					$item_head = '<div>';
					$item_tail = '</div>';
				}

				if ( $sortable ) {
					$item_data = $item_head . "<input type=\"$type\" name=\"$name\"$attrs>" . $item_tail;

					$html .= '<span class="multiple-input multiple-append dashicons-before dashicons-plus" style="margin:initial;padding:initial;cursor:pointer" data-append="' . esc_attr( $item_data ) . '"></span>' . $item_html;
				} else {
					$html .= $item_head . $item_html . $item_tail;
				}

				if ( $sortable ) {
					$html .= '<ul class="multiple-input ui-sortable" style="margin:initial;padding:initial">';
				}
				foreach ( $value as $item_value ) {
					$html_value = esc_attr( $item_value );
					$item_html  = $this->apply_field_html_filters( "<input type=\"$type\" name=\"$name\" value=\"$html_value\"$attrs>", $args, $fn );
					$html      .= $item_head . $item_html . $item_tail;
				}
				if ( $sortable ) {
					$html .= '</ul>';
				}
			} else {
				$value = esc_attr( $args ['value'] );
				$html .= $this->apply_field_html_filters( "<input type=\"$type\" id=\"$id\" name=\"$name\" value=\"$value\"$attrs>", $args, $fn );
			}

			Lib::echo_html( $html );
		}

		/** TextArea input-type renderer.
		 * Does not support 'multiple' option.
		 *
		 * @access public
		 * @param array $field_args : [fn: id: name: default: placeholder: title: class: style: options[...]].
		 * @return void (echo)
		 */
		public function render_textarea_input_field( $field_args ) {
			$args = $this->get_field_settings_args( $field_args );

			$fn   = $args ['fn'];
			$id   = esc_attr( $args ['id'] );
			$name = esc_attr( $args ['name'] );

			$field_options = array_key_exists( 'options', $args ) ? $args ['options'] : array();
			$autofocus     = array_key_exists( 'autofocus', $field_options ) ? true === $field_options ['autofocus'] : false;
			$disabled      = array_key_exists( 'disabled', $field_options ) ? true === $field_options ['disabled'] : false;
			$readonly      = array_key_exists( 'readonly', $field_options ) ? true === $field_options ['readonly'] : false;
			$required      = array_key_exists( 'required', $field_options ) ? true === $field_options ['required'] : false;

			$args ['value'] = $this->get_form_field_option_value( $fn, $args );

			$args = $this->apply_field_args_filters( $args, $fn );

			$value = esc_textarea( $args ['value'] );

			$place = esc_attr( array_key_exists( 'placeholder', $args ) ? $args ['placeholder'] : null );
			$title = esc_attr( array_key_exists( 'title', $args ) ? $args ['title'] : null );
			$class = esc_attr( array_key_exists( 'class', $args ) ? $args ['class'] : null );
			$style = esc_attr( array_key_exists( 'style', $args ) ? $args ['style'] : 'min-height:60px;max-height:300px' );

			$attrs = array();

			$place ? $attrs[] = "placeholder=\"$place\"" : null;
			$title ? $attrs[] = "title=\"$title\"" : null;
			$class ? $attrs[] = "class=\"$class\"" : null;
			$style ? $attrs[] = "style=\"$style\"" : null;

			$autofocus ? $attrs[] = 'autofocus' : null;
			$disabled ? $attrs[]  = 'disabled' : null;
			$readonly ? $attrs[]  = 'readonly' : null;
			$required ? $attrs[]  = 'required' : null;

			$attrs = trim( implode( ' ', $attrs ) );
			$attrs = '' === $attrs ? '' : ' ' . $attrs;

			$html = "<textarea id=\"$id\" name=\"$name\"$attrs>$value</textarea>";

			$html = $this->apply_field_html_filters( $html, $args, $fn );

			Lib::echo_html( $html );
		}

		/** UploadFile input-type renderer
		 * Supports 'multiple' option.
		 *
		 * @access public
		 * @param array $field_args : [fn: id: name: default: placeholder: title: class: style: options[...]].
		 * @return void (echo)
		 */
		public function render_upload_file_input_field( $field_args ) {
			$args = $this->get_field_settings_args( $field_args );

			$fn   = $args ['fn'];
			$id   = esc_attr( $args ['id'] );
			$name = esc_attr( $args ['name'] );

			$field_options = array_key_exists( 'options', $args ) ? $args ['options'] : array();
			$autofocus     = array_key_exists( 'autofocus', $field_options ) ? true === $field_options ['autofocus'] : false;
			$disabled      = array_key_exists( 'disabled', $field_options ) ? true === $field_options ['disabled'] : false;
			$readonly      = array_key_exists( 'readonly', $field_options ) ? true === $field_options ['readonly'] : false;
			$required      = array_key_exists( 'required', $field_options ) ? true === $field_options ['required'] : false;
			$multiple      = array_key_exists( 'multiple', $field_options ) ? true === $field_options ['multiple'] : false;
			$accept        = array_key_exists( 'accept', $field_options ) ? $accept = ( is_string( $field_options ['accept'] ) ? $field_options ['accept'] : '' ) : '';

			if ( $multiple ) {
				$name         .= '[]';
				$args ['name'] = $name;
			}

			unset( $args ['value'] );

			$args = $this->apply_field_args_filters( $args, $fn );

			$value = null;

			$place = esc_attr( array_key_exists( 'placeholder', $args ) ? $args ['placeholder'] : null );
			$title = esc_attr( array_key_exists( 'title', $args ) ? $args ['title'] : null );
			$class = esc_attr( array_key_exists( 'class', $args ) ? $args ['class'] : null );
			$style = esc_attr( array_key_exists( 'style', $args ) ? $args ['style'] : null );

			$attrs = array();

			$place ? $attrs[] = "placeholder=\"$place\"" : null;
			$title ? $attrs[] = "title=\"$title\"" : null;
			$class ? $attrs[] = "class=\"$class\"" : null;
			$style ? $attrs[] = "style=\"$style\"" : null;

			$autofocus ? $attrs[] = 'autofocus' : null;
			$disabled ? $attrs[]  = 'disabled' : null;
			$readonly ? $attrs[]  = 'readonly' : null;
			$required ? $attrs[]  = 'required' : null;
			$multiple ? $attrs[]  = 'multiple' : null;
			$accept ? $attrs[]    = "accept=\"$accept\"" : null;

			$attrs = trim( implode( ' ', $attrs ) );
			$attrs = '' === $attrs ? '' : ' ' . $attrs;

			$html = "<input type=\"file\" id=\"$id\" name=\"$name\"$attrs>";

			$html = $this->apply_field_html_filters( $html, $args, $fn );

			Lib::echo_html( $html );
		}

		/** SelectOption input-type renderer.
		 * Supports 'multiple' option.
		 *
		 * @access public
		 * @param array $field_args : [fn: id: name: default: placeholder: title: class: style: options[...]].
		 * @return void (echo)
		 */
		public function render_select_option_input_field( $field_args ) {
			$args = $this->get_field_settings_args( $field_args );

			$fn   = $args ['fn'];
			$id   = esc_attr( $args ['id'] );
			$name = esc_attr( $args ['name'] );

			$field_options = array_key_exists( 'options', $args ) ? $args ['options'] : array();
			$autofocus     = array_key_exists( 'autofocus', $field_options ) ? true === $field_options ['autofocus'] : false;
			$disabled      = array_key_exists( 'disabled', $field_options ) ? true === $field_options ['disabled'] : false;
			$readonly      = array_key_exists( 'readonly', $field_options ) ? true === $field_options ['readonly'] : false;
			$required      = array_key_exists( 'required', $field_options ) ? true === $field_options ['required'] : false;
			$multiple      = array_key_exists( 'multiple', $field_options ) ? true === $field_options ['multiple'] : false;

			$args ['value'] = $this->get_form_field_option_value( $fn, $args );

			if ( $multiple ) {
				$name         .= '[]';
				$args ['name'] = $name;

				if ( ! is_array( $args ['value'] ) ) {
					$args ['value'] = array( $args ['value'] );
				}
			} else {
				if ( is_array( $args ['value'] ) ) {
					$args ['value'] = array_shift( $args ['value'] );
				}
			}

			$args = $this->apply_field_args_filters( $args, $fn );

			$value         = $args ['value'];
			$options       = array();
			$field_options = is_array( $field_options ) ? $field_options : array();
			$source_key    = array_key_exists( 'source', $field_options ) ? $field_options ['source'] : 'not found';
			$source_data   = array_key_exists( $source_key, $field_options ) ? $field_options [ $source_key ] : null;
			switch ( $source_key ) {
				case 'plugins':
					$plugins = get_plugins();
					foreach ( $plugins as $base => $data ) {
						$options [ $base ] = $data ['Name'];
					}
					if ( array_key_exists( $this->basename, $options ) ) {
						unset( $options [ $this->basename ] );
					}
					break;
				case 'active-plugins':
					$activated = get_option( 'active_plugins' );
					$plugins   = get_plugins();
					foreach ( $activated as $base ) {
						$options [ $base ] = $plugins [ $base ]['Name'];
					}
					if ( array_key_exists( $this->basename, $options ) ) {
						unset( $options [ $this->basename ] );
					}
					break;
				case 'pages':
					$options = $this->get_select_pages( $source_data );
					break;
				case 'posts':
					$options = $this->get_select_posts( $source_data );
					break;
				case 'images':
					$options = $this->get_select_images( $source_data );
					break;
				case 'terms':
					$options = $this->get_select_terms( $source_data );
					break;
				case 'categories':
					$options = $this->get_select_categories( $source_data );
					break;
				case 'tags':
					$options = $this->get_select_tags( $source_data );
					break;
				case 'values':
					$options = $source_data;
					break;
				case 'callback':
					if ( is_callable( array( $this, $source_data ) ) ) {
						if ( array_key_exists( $source_data, $field_options ) ) {
							$options = call_user_func( array( $this, $source_data ), $field_options [ $source_data ] );
						} else {
							$options = call_user_func( array( $this, $source_data ) );
						}
					} else {
						Lib::debug( 'Not callable $source_data.' );
					}
					break;
				default:
					Lib::debug( "Unknown source \"$source_key\"." );
					break;
			}
			if ( ! is_array( $options ) ) {
				Lib::debug( "Variable \$options from source \"$source_key\" is not an array." );
				$options = array();
			} elseif ( empty( $options ) ) {
				Lib::debug( "Variable \$options from source \"$source_key\" is an empty array." );
			}

			$place = esc_attr( array_key_exists( 'placeholder', $args ) ? $args ['placeholder'] : null );
			$title = esc_attr( array_key_exists( 'title', $args ) ? $args ['title'] : null );
			$class = esc_attr( array_key_exists( 'class', $args ) ? $args ['class'] : null );
			$style = esc_attr( array_key_exists( 'style', $args ) ? $args ['style'] : null );

			$attrs = array();

			$place ? $attrs[] = "placeholder=\"$place\"" : null;
			$title ? $attrs[] = "title=\"$title\"" : null;
			$class ? $attrs[] = "class=\"$class\"" : null;
			$style ? $attrs[] = "style=\"$style\"" : null;

			$autofocus ? $attrs[] = 'autofocus' : null;
			$disabled ? $attrs[]  = 'disabled' : null;
			$readonly ? $attrs[]  = 'readonly' : null;
			$required ? $attrs[]  = 'required' : null;
			$multiple ? $attrs[]  = 'multiple' : null;

			$attrs = trim( implode( ' ', $attrs ) );
			$attrs = '' === $attrs ? '' : ' ' . $attrs;

			$html = "<select id=\"$id\" name=\"$name\"$attrs>";
			foreach ( $options as $option_key => $option_val ) {
				switch ( gettype( $option_val ) ) {
					case 'array':
						$html .= "<optgroup label=\"$option_key\">";
						foreach ( $option_val as $option_key_in_group => $option_val_in_group ) {
							$html .= '<option value="' . esc_attr( $option_key_in_group ) . '"';
							if ( $multiple ) {
								$html .= ( in_array( $option_key_in_group, $value, true ) ? ' selected' : '' );
							} else {
								$html .= ( $option_key_in_group === $value ? ' selected' : '' );
							}
							$html .= '>' . esc_html( $option_val_in_group ) . '</option>';
						}
						$html .= '</optgroup>';
						break;

					default:
						$html .= '<option value="' . esc_attr( $option_key ) . '"';
						if ( $multiple ) {
							$html .= ( in_array( $option_key, $value, true ) ? ' selected' : '' );
						} else {
							$html .= ( $option_key === $value ? ' selected' : '' );
						}
						$html .= '>' . esc_html( $option_val ) . '</option>';
						break;
				}
			}
			$html .= '</select>';

			$html = $this->apply_field_html_filters( $html, $args, $fn );

			if ( $multiple ) {
				$html .= "<input id=\"$id-hidden\" type=\"hidden\" name=\"$name\" value=\"\"/>";
			}

			Lib::echo_html( $html );
		}

		/** Render Submit field/button.
		 *
		 * @access public
		 * @param int    $tab_index - current tab index.
		 * @param string $section_id - default 'tab'.
		 * @param array  $args - default arguments.
		 */
		public function render_submit_field( $tab_index = 0, $section_id = 'tab', $args = array(
			'fn'    => 'submit',
			'id'    => 'submit',
			'name'  => 'submit',
			'type'  => 'submit',
			'title' => 'Save All Changes in All Tabs And Sections.',
			'value' => 'Save Changes',
		) ) {
			$fn          = $args ['fn'];
			$args ['id'] = esc_attr( $args ['id'] . uniqid( '-' ) );
			$id          = $args ['id'];
			$name        = esc_attr( $this->optionid . '[' . $args ['name'] . '][' . $tab_index . '][' . $section_id . ']' );

			$field_options = array_key_exists( 'options', $args ) ? $args ['options'] : array();
			$disabled      = array_key_exists( 'disabled', $field_options ) ? true === $field_options ['disabled'] : false;
			$readonly      = array_key_exists( 'readonly', $field_options ) ? true === $field_options ['readonly'] : false;

			$args ['value'] = isset( $args ['value'] ) && is_string( $args ['value'] ) ? $args ['value'] : 'Save Changes';

			$args ['class'] = 'button button-primary ' . $this->pageslug;
			$args           = $this->apply_field_args_filters( $args, $fn );

			$value = esc_attr( $args ['value'] );

			$title = esc_attr( array_key_exists( 'title', $args ) ? $args ['title'] : null );
			$class = esc_attr( array_key_exists( 'class', $args ) ? $args ['class'] : null );
			$style = esc_attr( array_key_exists( 'style', $args ) ? $args ['style'] : null );

			$attrs               = array();
			$title ? $attrs[]    = "title=\"$title\"" : null;
			$class ? $attrs[]    = "class=\"$class\"" : null;
			$style ? $attrs[]    = "style=\"$style\"" : null;
			$disabled ? $attrs[] = 'disabled' : null;
			$readonly ? $attrs[] = 'readonly' : null;

			$attrs = trim( implode( ' ', $attrs ) );
			$attrs = '' === $attrs ? '' : ' ' . $attrs;

			$html = "<input id=\"$id\" name=\"$name\" type=\"submit\" value=\"$value\"$attrs/>";

			Lib::echo_html( '<div class=submit-button>' . $this->apply_field_html_filters( $html, $args, $fn ) . '</div>' );
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Render Sections

		/** Renders section (accordion) and content (section-fields)
		 *
		 * @ignore
		 * @access private
		 * @param array $wp_section - $wp_settings_sections[page][section].
		 * @param bool  $section_is_open - accordion expanded/collapsed.
		 * @param int   $sections_todo - flag to skip Submit button for last section.
		 * @param int   $tab_index - current tab index.
		 * @param int   $push_tabs - (debug only) formatting.
		 * @uses $wp_settings_fields
		 * @return void
		 */
		private function render_section_elements( $wp_section, $section_is_open, $sections_todo, $tab_index, $push_tabs = 0 ) {
			global $wp_settings_fields;
			$section_id = $wp_section ['id'];

			$section_id_attr = esc_attr( $section_id );

			Lib::echo_html( Lib::debug_eol_tabs( 0 + $push_tabs ) . "<div id=\"section-$section_id_attr\" class=\"accordion-container\">" );

			$section_attrs = 'class="accordion-section' . ( false !== $section_is_open ? ' open" aria-expanded=true' : '" aria-expanded=false' );

			Lib::echo_html( Lib::debug_eol_tabs( 1 + $push_tabs ) . "<div $section_attrs>" );

			$section_title = 'Section';
			if ( isset( $wp_section ['title'] ) && is_string( $wp_section ['title'] ) ) {
				$section_title = esc_html( $wp_section['title'] );
			}

			Lib::echo_html( Lib::debug_eol_tabs( 2 + $push_tabs ) . "<h3 class=\"accordion-section-title\">$section_title</h3>" );

			Lib::echo_html( Lib::debug_eol_tabs( 2 + $push_tabs ) . '<div class="accordion-section-content">' );

			if ( isset( $wp_section ['callback'] ) && is_callable( $wp_section ['callback'] ) ) {
				call_user_func( $wp_section ['callback'], $wp_section );
			}

			$fields_sections   = Lib::safe_key_value( $wp_settings_fields, $this->pageslug, array() );
			$fields_in_section = Lib::safe_key_value( $fields_sections, $section_id, array() );
			if ( count( $fields_in_section ) > 0 ) {
				$fields_in_section_hidden = array();
				foreach ( $fields_in_section as $field_in_section_key => $field_in_section_val ) {
					if ( isset( $field_in_section_val ['args']['settings']['type'] )
					&& 'hidden' === $field_in_section_val ['args']['settings']['type'] ) {
						$fields_in_section_hidden [ $field_in_section_key ] = $field_in_section_val;
					}
				}
				foreach ( $fields_in_section_hidden as $field_in_section_key => $field_in_section_val ) {
					call_user_func( $field_in_section_val ['callback'], $field_in_section_val ['args'] );
					unset( $wp_settings_fields [ $this->pageslug ][ $section_id ][ $field_in_section_key ] );
				}

				Lib::echo_html( Lib::debug_eol_tabs( 3 + $push_tabs ) . '<table class="form-table">' );
				do_settings_fields( $this->pageslug, $section_id );
				Lib::echo_html( Lib::debug_eol_tabs( 3 + $push_tabs ) . '</table>' );
			}

			$section_settings = $this->settings ['sections'][ $section_id ];
			if ( array_key_exists( 'submit', $section_settings ) ) {
				$submit_value = $section_settings ['submit'];
				if ( $submit_value ) {
					$args = array(
						'fn'   => 'submit',
						'id'   => 'submit',
						'name' => 'submit',
						'type' => 'submit',
					);
					if ( is_string( $submit_value ) ) {
						$args ['value'] = $submit_value;
					}
					Lib::echo_html( Lib::debug_eol_tabs( 3 + $push_tabs ) );
					$this->render_submit_field( $tab_index, $section_id, $args );
				}
			} elseif ( 1 !== $sections_todo ) {
				Lib::echo_html( Lib::debug_eol_tabs( 3 + $push_tabs ) );
				$this->render_submit_field( $tab_index, $section_id );
			}

			Lib::echo_html( Lib::debug_eol_tabs( 2 + $push_tabs ) . '</div>' );
			Lib::echo_html( Lib::debug_eol_tabs( 1 + $push_tabs ) . '</div>' );
			Lib::echo_html( Lib::debug_eol_tabs( 0 + $push_tabs ) . '</div>' );
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Render Tab-Page & Elements

		/** Render Tab-Page Wrap-Open Elements
		 *
		 * @ignore
		 * @access private
		 * @param int   $tab_index - current tab index.
		 * @param array $tab_settings - current tab settings.
		 * @param array $tab_sections - current tab sections.
		 * @param int   $push_tabs - add X tabs for debug formatting.
		 * @return void
		 */
		private function render_tab_open_elements( $tab_index, $tab_settings, $tab_sections = false, $push_tabs = 0 ) {
			$current_index = (int) $tab_index;
			$tab_class_id  = rtrim( ' ' . Lib::safe_key_value( $tab_settings, 'key_index', '' ) );
			Lib::echo_html( Lib::debug_eol_tabs( 0 + $push_tabs ) . "<div id=\"nav-tab-page-$current_index\" class=\"nav-tab-page$tab_class_id\" role=\"tabpanel\" aria-labelledby=\"nav-tab-$tab_index\">" );

			$render_method_name = array_key_exists( 'render', $tab_settings ) && is_string( $tab_settings ['render'] ) ? $tab_settings ['render'] : false;
			if ( $render_method_name && is_callable( array( $this->renderer, $render_method_name ) ) ) {
				$ob_level = ob_get_level();
				try {
					ob_start();
					call_user_func( array( $this->renderer, $render_method_name ) );
					$tab_contents = ob_get_clean();
					if ( strlen( $tab_contents ) > 0 ) {
						Lib::echo_html( Lib::debug_eol_tabs( 2 + $push_tabs ) . "<div id=\"nav-tab-page-header-$current_index\" class=\"nav-tab-page-header$tab_class_id\">" );
						Lib::echo_html( Lib::debug_eol_tabs( 2 + $push_tabs ) . $tab_contents );
						Lib::echo_html( Lib::debug_eol_tabs( 2 + $push_tabs ) . '</div>' );
					}
				} catch ( \Exception $e ) {
					while ( ob_get_level() > $ob_level ) {
						ob_end_clean();
					}
					Lib::error( "Exception \"{$e->getMessage()}\" caught while calling tab-render method \"$render_method_name\"" );
				}
			} else {
				$render_method_name && Lib::debug( 'Tab render method name is not callable: ' . $render_method_name );
			}
			Lib::echo_html( Lib::debug_eol_tabs( 2 + $push_tabs ) . "<div id=\"nav-tab-page-content-$current_index\" class=\"nav-tab-page-content$tab_class_id\">" );
		}

		/** Render Tab-Page Wrap-Close Elements.
		 *
		 * @ignore
		 * @access private
		 * @param int   $tab_index - current tab index.
		 * @param array $tab_settings - current tab settings.
		 * @param array $tab_sections - current tab sections.
		 * @param int   $push_tabs - add X tabs for debug formatting.
		 */
		private function render_tab_close_elements( $tab_index, $tab_settings, $tab_sections, $push_tabs = 0 ) {
			Lib::echo_html( Lib::debug_eol_tabs( 2 + $push_tabs ) . '</div><!--nav-tab-page-content-->' );
			Lib::echo_html( Lib::debug_eol_tabs( 2 + $push_tabs ) . '<div class="nav-tab-page-footer">' );

			if ( array_key_exists( 'submit', $tab_settings ) ) {
				$submit_value = $tab_settings['submit'];
				if ( is_string( $submit_value ) && trim( $submit_value ) !== '' ) {
					$args = array(
						'fn'    => 'submit',
						'id'    => 'submit',
						'name'  => 'submit',
						'type'  => 'submit',
						'value' => $submit_value,
					);
					Lib::echo_html( Lib::debug_eol_tabs( 3 + $push_tabs ) );
					$this->render_submit_field( $tab_index, 'tab', $args );
				}
			} elseif ( $tab_sections ) {
				Lib::echo_html( Lib::debug_eol_tabs( 2 + $push_tabs ) );
				$this->render_submit_field( $tab_index );
			}

			Lib::echo_html( Lib::debug_eol_tabs( 2 + $push_tabs ) . '</div><!--nav-tab-page-footer-->' );
			Lib::echo_html( Lib::debug_eol_tabs( 0 + $push_tabs ) . '</div><!--nav-tab-page-->' );
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Render Settings Page

		/** Prepare settings-page.
		 *
		 * Invoked at load-$this->menuslug, before admin headers.
		 *
		 * @access public
		 * @internal callback
		 * @return void
		 */
		public function on_abstract_prepare_settings_page() {
			if ( ! $this->authorized() ) {
				Lib::debug( 'Not authorized!' );
				return;
			}

			$this->on_prepare_settings_page();

			$this->myscreen = get_current_screen();

			$this->add_meta_boxes( $this->menuslug, $this->myscreen );

			do_action( 'add_meta_boxes', $this->menuslug, $this->myscreen );

			$counter1 = 0;
			foreach ( $this->settings ['sections'] as $section_id => $section_settings ) {
				do_action( $this->prefix . '_before_add_section', $section_id, $counter1++ );
				$this->add_settings_section( $section_id, $section_settings );

				if ( isset( $section_settings ['fields'] ) && is_array( $section_settings ['fields'] ) ) {
					$counter2 = 0;
					foreach ( $section_settings ['fields'] as $field_name => $field_settings ) {
						do_action( $this->prefix . '_before_add_section_field', $section_id, $field_name, $counter2++ );
						$this->add_settings_field( $field_name, $field_settings, $section_id );
					}
				}
				do_action( $this->prefix . '_after_add_section_fields', $section_id );
			}
			do_action( $this->prefix . '_after_add_sections' );

			if ( $this->myscreen && array_key_exists( 'help-tabs', $this->settings ['page'] ) && is_array( $this->settings ['page']['help-tabs'] ) ) {
				foreach ( $this->settings ['page']['help-tabs'] as $help_tab_args ) {
					if ( is_array( $help_tab_args )

					&& array_key_exists( 'id', $help_tab_args ) && is_string( $help_tab_args ['id'] )
					&& array_key_exists( 'title', $help_tab_args ) && is_string( $help_tab_args ['title'] )
					) {
						$content  = array_key_exists( 'content', $help_tab_args ) && is_string( $help_tab_args ['content'] )
							? $help_tab_args ['content'] : '';
						$callback = array_key_exists( 'callback', $help_tab_args ) && is_string( $help_tab_args ['callback'] )
							? array( $this->renderer, $help_tab_args ['callback'] ) : false;
						$priority = array_key_exists( 'priority', $help_tab_args ) && is_int( $help_tab_args ['priority'] )
						? $help_tab_args ['priority'] : false;

						$args             = array();
						$args ['id']      = $help_tab_args ['id'];
						$args ['title']   = $help_tab_args ['title'];
						$args ['content'] = $content;
						if ( is_callable( $callback ) ) {
							$args ['callback'] = $callback;
						}
						if ( $priority ) {
							$args ['priority'] = $priority;
						}

						$this->myscreen->add_help_tab( $args );
					}
				}
			}

			add_filter(
				'screen_options_show_screen',
				function ( $show_screen, $screen ) {
					return $show_screen;
				},
				10,
				2
			);

		}

		/** Method hook for the derived class */
		protected function add_metabox_nonces() {
			return array();
		}

		/** Method hook for the derived class */
		protected function on_prepare_settings_page() {}

		/** Method hook for the derived class */
		protected function on_render_settings_init() {}

		/** Method hook for the derived class */
		protected function on_render_settings_page() {}

		/** Render complete settings-page (form/tabs/sections/fields).
		 *
		 * @access public
		 * @internal callback
		 * @return void
		 */
		public function render_settings_page() {
			global $wp_settings_sections, $parent_file;

			if ( ! $this->authorized() ) {
				Lib::debug( 'Not authorized!' );
				return;
			}

			$this->on_render_settings_init();

			$ui_state = array(
				'tabindex' => 0,
				'sections' => '',
			);
			$cookies  = wp_unslash( $_COOKIE );
			if ( isset( $cookies[ $this->pageslug ] ) ) {
				$values = preg_replace( '/[^A-Za-z0-9=&_-]/', '', $cookies[ $this->pageslug ] );
				if ( strpos( $values, '=' ) ) {
					parse_str( $values, $ui_state );
				}

				$ui_state['tabindex'] = is_string( $ui_state['tabindex'] ) ? (int) $ui_state['tabindex'] : 0;
			}

			Lib::echo_html( Lib::debug_eol_tabs( 1 ) );
			wp_referer_field( true );
			Lib::echo_html( Lib::debug_eol_tabs( 1 ) );
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
			Lib::echo_html( Lib::debug_eol_tabs( 1 ) );
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

			$add_nonces = $this->add_metabox_nonces();
			if ( is_array( $add_nonces ) ) {
				foreach ( $add_nonces as $action => $name ) {
					Lib::echo_html( Lib::debug_eol_tabs( 1 ) );
					wp_nonce_field( $action, $name, false );
				}
			}

			Lib::echo_html( Lib::debug_eol_tabs( 1 ) . "<div id=settings-page class=wrap data-page=$this->pageslug data-action=$this->prefix>" );

			$page_title = Lib::safe_key_value( $this->settings ['page'], 'title', '' );
			$page_title = '' !== trim( $page_title ) ? $page_title : __( 'Settings' );

			Lib::echo_html( Lib::debug_eol_tabs( 2 ) . "<h1 class=\"wp-heading-inline\">$page_title</h1>" );
			Lib::echo_html( Lib::debug_eol_tabs( 2 ) . '<hr class="wp-header-end">' );

			$page_style = Lib::safe_key_value( $this->settings ['page'], 'style', '', false );
			if ( $page_style ) {
				Lib::echo_html( Lib::debug_eol_tabs( 2 ) . "<style>$page_style</style>" );
			}

			Lib::echo_html( Lib::debug_eol_tabs( 1 ) );

			Lib::do_transient_notices();

			if ( 'options-general.php' !== $parent_file ) {
				Lib::echo_html( Lib::debug_eol_tabs( 1 ) );
				settings_errors( $this->pageslug );
			}

			$this->on_render_settings_page();

			$columns = absint( $this->myscreen->get_columns() );
			if ( ! $columns ) {
				$columns = 2; }
			$columns_css = " columns-$columns";

			Lib::echo_html( Lib::debug_eol_tabs( 2 ) . '<div id=poststuff>' );
			Lib::echo_html( Lib::debug_eol_tabs( 3 ) . '<div id=post-body class="metabox-holder' . $columns_css . '">' );

			$tabs_settings = array_key_exists( 'tabs', $this->settings )
			&& is_array( $this->settings ['tabs'] )
				? $this->settings ['tabs'] : array();
			$tabs_keyindex = array_keys( $tabs_settings );

			$last_tab_index = count( $tabs_settings ) - 1;

			if ( count( $tabs_settings ) === 0 ) {
				Lib::echo_html( Lib::debug_eol_tabs( 5 ) . '<div class="nav-tab-wrapper" role="tablist"></div>' );

			} else {
				Lib::echo_html( Lib::debug_eol_tabs( 5 ) . '<style>' );
				Lib::echo_html( Lib::debug_eol_tabs( 6 ) . '.nav-tab-page{display:none}' );
				Lib::echo_html( Lib::debug_eol_tabs( 6 ) . 'input[type=radio][name=nav-tab-state].nav-tab-state{display:none!important;position:absolute;left:-9999px}' );

				Lib::echo_html( Lib::debug_eol_tabs( 6 ) );
				for ( $i = 0;$i < $last_tab_index;$i++ ) {
					Lib::echo_html( "#nav-tab-state-$i:checked~.nav-tab-wrapper #nav-tab-$i," );
				}
				Lib::echo_html( "#nav-tab-state-$i:checked~.nav-tab-wrapper #nav-tab-$i" );

				Lib::echo_html( '{-webkit-box-shadow:none;box-shadow:none;margin-bottom:-1px;border-bottom:1px solid #f1f1f1;background:#f1f1f1;color:#000}' );

				Lib::echo_html( Lib::debug_eol_tabs( 6 ) );
				for ( $i = 0;$i < $last_tab_index;$i++ ) {
					Lib::echo_html( "#nav-tab-state-$i:checked~div#post-body-content>form#{$this->pageslug}-form .nav-tab-pages #nav-tab-page-$i," );
				}
				Lib::echo_html( "#nav-tab-state-$i:checked~div#post-body-content>form#{$this->pageslug}-form .nav-tab-pages #nav-tab-page-$i{display:block}" );

				Lib::echo_html( Lib::debug_eol_tabs( 5 ) . '</style>' );

				$open_tab_index = isset( $ui_state['tabindex'] ) ? $ui_state['tabindex'] : 0;

				$tab_index = 0;
				foreach ( $tabs_settings as $ignore ) {
					Lib::echo_html( Lib::debug_eol_tabs( 5 ) . "<input type=\"radio\" class=\"nav-tab-state\" name=\"nav-tab-state\" style=\"display:none\" id=\"nav-tab-state-$tab_index\" data-ays-ignore=\"true\"" . ( $open_tab_index === $tab_index ? ' checked' : '' ) . '/>' );
					++ $tab_index;
				}

				Lib::echo_html( Lib::debug_eol_tabs( 5 ) . '<div class="nav-tab-wrapper" role="tablist">' );
				$tab_index = 0;
				foreach ( $tabs_settings as $tab_settings ) {
					$tab_title = ( is_string( $tab_settings ['title'] ) ? trim( $tab_settings ['title'] ) : 'Tab ' . $tab_index );
					Lib::echo_html( Lib::debug_eol_tabs( 6 ) . "<label for=\"nav-tab-state-$tab_index\" id=\"nav-tab-$tab_index\" class=\"nav-tab\" role=\"tab\" area-controls=\"nav-tab-page-$tab_index\">$tab_title</label>" );
					++ $tab_index;
				}
				Lib::echo_html( Lib::debug_eol_tabs( 5 ) . '</div>' );

			}

			Lib::echo_html( Lib::debug_eol_tabs( 5 ) . '<div id="post-body-content">' );

			Lib::echo_html( Lib::debug_eol_tabs( 6 ) . "<form id=\"$this->pageslug-form\" action=options.php method=post enctype=multipart/form-data>" );
			Lib::echo_html( Lib::debug_eol_tabs( 7 ) . settings_fields( $this->pageslug ) );

			$wp_sections = (array) $wp_settings_sections [ $this->pageslug ];

			$open_sections = array();
			if ( count( $wp_sections ) > 0 ) {
				$open_sections = array_pad( $open_sections, count( $wp_sections ), true );
			}

			$open_sections_state = isset( $ui_state['sections'] ) ? $ui_state['sections'] : '';
			$open_sections_items = strlen( $open_sections_state );
			for ( $i = 0; $i < $open_sections_items; $i++ ) {
				$open_sections [ $i ] = ( '1' === $open_sections_state [ $i ] ? true : false );
			}

			if ( count( $tabs_settings ) !== 0 ) {
				$tab_index     = 0;
				$section_index = 0;
				$tab_settings  = $tabs_settings [ $tabs_keyindex[ $tab_index ] ];

				$tab_settings ['key_index'] = $tabs_keyindex[ $tab_index ];

				$sections_done = 0;
				$sections_todo = array_key_exists( 'sections', $tab_settings )
				&& is_int( $tab_settings ['sections'] )
				? abs( $tab_settings ['sections'] ) : 0;

				if ( $tab_index === $last_tab_index && 0 === $sections_todo ) {
					$sections_todo = count( $wp_sections );
				}

				$tab_sections = 0 !== $sections_todo;

				Lib::echo_html( Lib::debug_eol_tabs( 7 ) . '<div class="nav-tab-pages">' );
				$this->render_tab_open_elements( $tab_index, $tab_settings, $tab_sections, 8 );

				foreach ( $wp_sections as $wp_section ) {
					while ( $sections_todo <= 0 && $tab_index < $last_tab_index ) {
						$this->render_tab_close_elements( $tab_index, $tab_settings, $tab_sections, 8 );

						++ $tab_index;
						$tab_settings = $tabs_settings [ $tabs_keyindex[ $tab_index ] ];

						$tab_settings ['key_index'] = $tabs_keyindex[ $tab_index ];

						$sections_todo = array_key_exists( 'sections', $tab_settings )
						&& is_int( $tab_settings ['sections'] )
						? $tab_settings ['sections'] : 0;

						if ( $tab_index === $last_tab_index ) {
							if ( 0 === $sections_todo ) {
								$sections_todo = count( $wp_sections ) - $sections_done;
							} elseif ( $sections_todo < 0 ) {
								$sections_todo = 0;
							}
						}
						$sections_todo = abs( $sections_todo );

						$tab_sections = 0 !== $sections_todo;

						$this->render_tab_open_elements( $tab_index, $tab_settings, $tab_sections, 8 );
					}

					if ( $sections_todo > 0 ) {
						$section_is_open = ( isset( $open_sections [ $section_index ] ) ? $open_sections [ $section_index ] : true );
						$this->render_section_elements( $wp_section, $section_is_open, $sections_todo, $tab_index, 9 );
						++ $section_index;
						-- $sections_todo;
						++ $sections_done;
					}
				}

				while ( $tab_index < ( $last_tab_index ) ) {
					$this->render_tab_close_elements( $tab_index, $tab_settings, $tab_sections, 8 );
					++ $tab_index;
					$tab_sections = false;
					$tab_settings = $tabs_settings [ $tabs_keyindex[ $tab_index ] ];

					$tab_settings ['key_index'] = $tabs_keyindex[ $tab_index ];

					$this->render_tab_open_elements( $tab_index, $tab_settings, $tab_sections, 8 );
				}
				$this->render_tab_close_elements( $tab_index, $tab_settings, $tab_sections, 8 );

				Lib::echo_html( Lib::debug_eol_tabs( 7 ) . '</div><!--nav-tab-pages-->' );

			} else {
				$tab_index     = 0;
				$section_index = 0;
				$sections_todo = 0;
				foreach ( $wp_sections as $wp_section ) {
					$section_is_open = ( isset( $open_sections [ $section_index ] ) ? $open_sections [ $section_index ] : true );
					$this->render_section_elements( $wp_section, $section_is_open, $sections_todo, $tab_index, 7 );
					++$section_index;
				}
			}

			Lib::echo_html( Lib::debug_eol_tabs( 6 ) . '</form>' );
			Lib::echo_html( Lib::debug_eol_tabs( 5 ) . '</div>' );

			Lib::echo_html( Lib::debug_eol_tabs( 4 ) . '<div id="postbox-container-1" class="postbox-container">' );
			Lib::echo_html( Lib::debug_eol_tabs( 5 ) );
			if ( $this !== $this->renderer && is_callable( array( $this->renderer, 'render_settings_page_sidebar' ) ) ) {
				$this->renderer->render_settings_page_sidebar();
			} else {
				$this->render_settings_page_sidebar();
			}
			Lib::echo_html( Lib::debug_eol_tabs( 5 ) );
			do_meta_boxes( $this->menuslug, 'side', $this );
			Lib::echo_html( Lib::debug_eol_tabs( 4 ) . '</div>' );

			Lib::echo_html( Lib::debug_eol_tabs( 4 ) . '<div id="postbox-container-2" class="postbox-container">' );
			Lib::echo_html( Lib::debug_eol_tabs( 5 ) );
			if ( $this !== $this->renderer && is_callable( array( $this->renderer, 'render_settings_page_footbar' ) ) ) {
				$this->renderer->render_settings_page_footbar();
			} else {
				$this->render_settings_page_footbar();
			}
			Lib::echo_html( Lib::debug_eol_tabs( 5 ) );
			do_meta_boxes( $this->menuslug, 'normal', $this );
			Lib::echo_html( Lib::debug_eol_tabs( 5 ) );
			do_meta_boxes( $this->menuslug, 'advanced', $this );
			Lib::echo_html( Lib::debug_eol_tabs( 4 ) . '</div>' );
			Lib::echo_html( Lib::debug_eol_tabs( 3 ) . '</div>' );
			Lib::echo_html( Lib::debug_eol_tabs( 2 ) . '</div>' );

			Lib::echo_html( Lib::debug_eol_tabs( 1 ) . '</div>' );
		}

		/** Method hook for the derived class */
		protected function render_settings_page_sidebar() {}

		/** Method hook for the derived class */
		protected function render_settings_page_footbar() {}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Form Submit handlers (Validation & File Uploads)

		/** On Sanitize Callback, after Form Submitted.
		 *  By WP obsoleted and added for back-compatibility.
		 *  It actually calls here by:
		 *      add_filter(
		 *         "sanitize_option_{$option_name}",
		 *          array( $this, 'on_sanitize_callback' )
		 *      );
		 *  with only one argument (default one).
		 *
		 * Form input values are sanitized later, just before saving.
		 * Hook form validation filter later, when is actually needed.
		 *
		 * @see register_setting
		 * @access public
		 * @internal callback
		 * @param string|array $value of form-submitted option(s).
		 * @return string|array: $value unchanged.
		 */
		public function on_sanitize_callback( $value ) {
			return $value;
		}

		/** Form-submit (pre_update_option_{$option}) event handler.
		 *
		 * Validates form submit keys (field-name=>value).
		 * Validates $upload file descriptors from $_FILES and stores them into fields.
		 * Invokes derived&custom validation callbacks (if configured and is_callable).
		 *
		 * @ignore
		 * @see \register_setting
		 * @uses get_all_fields_defaults
		 * @uses $this->validate (callable)
		 * @access public
		 * @internal callback
		 * @param array  $new_value from submitted form.
		 * @param array  $old_value from saved options.
		 * @param string $option_id of saved options.
		 * @return array: $new_value validated
		 */
		public function on_abstract_validate_form_input( $new_value, $old_value, $option_id ) {
			if ( $this->optionid !== $option_id ) {
				Lib::error( 'Option id mismatch' );
				return $new_value;
			}

			\remove_filter(
				"pre_update_option_{$this->optionid}",
				array( $this, 'on_abstract_validate_form_input' ),
				10,
			);

			if ( ! is_array( $new_value ) ) {
				Lib::error( 'Options Input is not an array' );
				$new_value = array();
			}

			if ( ! is_array( $old_value ) ) {
				Lib::debug( 'Stored options value is not an array' );
				$old_value = array();
			}

			$fields = $this->get_all_fields_settings();

			$output = $this->get_all_fields_defaults();

			/** Replace $output [$field_name] only with validated $new_value [$field_name].
			 *
			 * Attn: $old_value will replace any value not sent/set by the form.
			 * Note: Shadow fields (checkbox/select) forces browser to send key even if empty.
			 * Prevents accepting unknown input fields from modified (hacked) html <form>.
			 * Makes sure that only sections/extended fields can pass into options (and db).
			 * Sanitizes field values.
			 */
			foreach ( array_keys( $output ) as $field_name ) {
				$input_value  = null;
				$input_accept = false;

				if ( array_key_exists( $field_name, $new_value )
				&& array_key_exists( $field_name, $fields ) ) {
					$field_type = array_key_exists( 'type', $fields [ $field_name ] )
						? $fields [ $field_name ]['type'] : false;

					if ( false !== $field_type ) {
						$input_value = $new_value [ $field_name ];
						$input_type  = gettype( $input_value );

						if ( false === \is_string( $input_value ) && false === \is_array( $input_value ) ) {
							Lib::debug( "Input Field '$field_name' is not a string or array but '$input_type'?" );
						}

						$field_options = \array_key_exists( 'options', $fields [ $field_name ] )
						&& is_array( $fields [ $field_name ] ['options'] )
						? $fields [ $field_name ] ['options'] : array();

						$is_multiple = \array_key_exists( 'multiple', $field_options )
						&& true === $field_options ['multiple']
						? true : false;

						$is_integer = \array_key_exists( 'value-type', $field_options )
						&& \in_array( ( $field_options ['value-type'] ), array( 'id', 'int', 'integer' ), true )
						? true : false;

						switch ( $field_type ) {
							case 'checkbox':
								if ( true === $is_multiple ) {
									$input_accept = true;
									foreach ( $input_value as &$input_item_value ) {
										if ( \is_array( $input_item_value ) ) {
											$input_item_value = \count( $input_item_value ) === 2 ? true : false;
										} else {
											Lib::debug( "Multiplied Checkbox '$field_name' value is not an array but '$input_type'?" );
											$input_accept = false;
										}
									}
								} else {
									if ( \is_array( $input_value ) ) {
										$input_value  = \count( $input_value ) === 2 ? true : false;
										$input_accept = true;
									} else {
										Lib::debug( "Checkbox '$field_name' value is not an array but '$input_type'?" );
									}
								}
								break;

							case 'select':
								if ( true === $is_multiple ) {
									if ( \is_array( $input_value ) ) {
										\array_pop( $input_value );

										if ( true === $is_integer ) {
											foreach ( $input_value as &$input_item_value ) {
												$input_item_value = \intval( $input_item_value );
											}
										} else {
											foreach ( $input_value as &$input_item_value ) {
												if ( is_string( $input_item_value ) ) {
													$input_item_value = \sanitize_text_field( $input_item_value );
												} else {
													$input_item_value = '';
												}
											}
										}
										$input_accept = true;
									} else {
										Lib::debug( "Select multiple '$field_name' value is not an array but '$input_type'?" );
									}
								} else {
									if ( \is_string( $input_value ) ) {
										if ( true === $is_integer ) {
											$input_value = \intval( $input_value );
										} else {
											$input_value = \sanitize_text_field( $input_value );
										}
										$input_accept = true;

									} else {
										Lib::debug( "Select single '$field_name' value is not a string but '$input_type'?" );
									}
								}
								break;

							case 'textarea':
								if ( true === $is_multiple ) {
									if ( \is_array( $input_value ) ) {
										foreach ( $input_value as &$input_item_value ) {
											$input_item_value = \sanitize_textarea_field( $input_item_value );
										}
										$input_accept = true;
									} else {
										Lib::debug( "Multiplied Textarea '$field_name' is not an array but '$input_type'?" );
									}
								} else {
									if ( \is_string( $input_value ) ) {
										$input_value  = \sanitize_textarea_field( $input_value );
										$input_accept = true;
									} else {
										Lib::debug( "Textarea '$field_name' is not a string but '$input_type'?" );
									}
								}
								break;

							case 'range':
							case 'text-range':
								$min_value = \array_key_exists( 'min', $field_options )
								&& \is_int( ( $field_options ['min'] ) )
								? $field_options ['min'] : 0;

								$max_value = \array_key_exists( 'max', $field_options )
								&& \is_int( ( $field_options ['max'] ) )
								? $field_options ['max'] : 100;

								if ( $min_value >= $max_value ) {
									$min_value = 0;
									$max_value = 100;
								}

								if ( \is_string( $input_value ) ) {
									$input_value = \sanitize_text_field( $input_value );

									$input_value  = \intval( $input_value );
									$input_accept = $input_value >= $min_value && $input_value <= $max_value;
								} else {
									Lib::debug( "Range '$field_name' is not a string but '$input_type'?" );
								}

								break;

							default:
								if ( true === $is_multiple ) {
									if ( \is_array( $input_value ) ) {
										if ( true === $is_integer ) {
											foreach ( $input_value as &$input_item_value ) {
												$input_item_value = \intval( $input_item_value );
											}
										} else {
											foreach ( $input_value as &$input_item_value ) {
												if ( is_string( $input_item_value ) ) {
													$input_item_value = \sanitize_text_field( $input_item_value );
												} else {
													$input_item_value = '';
												}
											}
										}
										$input_accept = true;
									} else {
										Lib::debug( "Multiplied Input Field '$field_name' is not an array but '$input_type'?" );
									}
								} else {
									if ( \is_string( $input_value ) ) {
										if ( true === $is_integer ) {
											$input_value = \intval( $input_value );
										} else {
											$input_value = \sanitize_text_field( $input_value );
										}
										$input_accept = true;
									} else {
										Lib::debug( "Input Field '$field_name' is not a string but '$input_type'?" );
									}
								}
								break;
						}
					}
				}

				if ( true === $input_accept && null !== $input_value ) {
					$output [ $field_name ] = $input_value;
				} elseif ( \array_key_exists( $field_name, $old_value ) ) {
					$output [ $field_name ] = $old_value [ $field_name ];
				}
			}

			if ( isset( $_FILES ) ) {
				$unslash_files = \wp_unslash( $_FILES );
				if ( array_key_exists( $this->optionid, $unslash_files ) ) {
					$uploads = $unslash_files [ $this->optionid ];
					if ( array_key_exists( 'name', $uploads ) && is_array( $uploads ['name'] ) ) {
						foreach ( $uploads ['name'] as $field_name => $file_name ) {
							if ( ! array_key_exists( $field_name, $output ) ) {
								Lib::debug( 'Unregistered field name: ' . $field_name );
								continue;
							}

							$results = array();
							if ( is_array( $file_name ) ) {
								$count_files = count( $file_name );
								for ( $index = 0; $index < $count_files; $index++ ) {
									$upload_info              = array();
									$upload_info ['name']     = sanitize_file_name( $file_name [ $index ] );
									$upload_info ['type']     = sanitize_mime_type( $uploads ['type'][ $field_name ][ $index ] );
									$upload_info ['tmp_name'] = sanitize_file_name( $uploads ['tmp_name'][ $field_name ][ $index ] );
									$upload_info ['size']     = intval( $uploads ['size'][ $field_name ][ $index ] );
									$upload_info ['error']    = intval( $uploads ['error'][ $field_name ][ $index ] );
									$upload_info              = $this->validate_single_file_upload( $upload_info );
									$results[]                = $this->handle_single_file_upload( $upload_info, $field_name, $output );
								}
							} else {
								$upload_info              = array();
								$upload_info ['name']     = sanitize_file_name( $file_name );
								$upload_info ['type']     = sanitize_mime_type( $uploads ['type'][ $field_name ] );
								$upload_info ['size']     = intval( $uploads ['size'][ $field_name ] );
								$upload_info ['error']    = intval( $uploads ['error'][ $field_name ] );
								$upload_info ['tmp_name'] = sanitize_file_name( $uploads ['tmp_name'][ $field_name ] );
								$upload_info              = $this->validate_single_file_upload( $upload_info );
								$results[]                = $this->handle_single_file_upload( $upload_info, $field_name, $output );
							}
							$output [ $field_name ] = $results;
						}
					} else {
						Lib::debug( 'Value not found: $uploads[name]' );
					}
				}
			}

			$output = $this->validate_options( $output );

			if ( is_callable( $this->validate ) ) {
				$output = call_user_func( $this->validate, $output );
			}

			count( \get_settings_errors() ) || $this->add_settings_update_notice();

			return $output;
		}

		/** Validates single file upload.
		 * Checks for php reported errors.
		 * Compares reported file size with real file size.
		 * Sanitizes to WordPress allowed mime-file-types.
		 *
		 * @param array $upload_info - upload info array.
		 */
		private function validate_single_file_upload( $upload_info ) {
			$php_file_upload_errors = array(
				0 => 'There is no error, the file uploaded with success',
				1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
				2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
				3 => 'The uploaded file was only partially uploaded',
				4 => 'No file was uploaded',
				6 => 'Missing a temporary folder',
				7 => 'Failed to write file to disk.',
				8 => 'A PHP extension stopped the file upload.',
			);
			$err_msg                = 'Upload error code not found.';
			$err_num                = 999;
			if ( array_key_exists( 'error', $upload_info ) && array_key_exists( $upload_info ['error'], $php_file_upload_errors ) ) {
				$err_num = $upload_info ['error'];
				$err_msg = $php_file_upload_errors [ $err_num ];
				if ( 0 === $err_num ) {
					if ( 0 === $upload_info ['size'] ) {
						$err_num = 999;
						$err_msg = 'Empty upload file: "' . $upload_info ['tmp_name'] . '".';
					} else {
						if ( is_uploaded_file( $upload_info ['tmp_name'] ) ) {
							if ( filesize( $upload_info ['tmp_name'] ) === $upload_info ['size'] ) {
								$allowed_mime_types = get_allowed_mime_types();
								if ( in_array( $upload_info ['type'], \array_values( $allowed_mime_types ), true ) ) {
									$file_extension = pathinfo( $upload_info ['name'], PATHINFO_EXTENSION );
									if ( ! empty( $file_extension )
									&& false !== strpos( $allowed_mime_types[ $upload_info ['type'] ], $file_extension ) ) {
										unset( $upload_info ['error'] );
										return $upload_info;
									} else {
										$err_num = 999;
										$err_msg = 'File extension does not match mime-type: "' . $file_extension . '".';
									}
								} else {
									$err_num = 999;
									$err_msg = 'Uploaded mime-type is not allowed: ' . $upload_info ['type'] . '.';
								}
							} else {
								$err_num = 999;
								$err_msg = 'Real file size does not match to reported file size: ' . $upload_info ['size'] . '.';
							}
						} else {
							$err_num = 999;
							$err_msg = 'Invalid upload file: "' . $upload_info ['tmp_name'] . '".';
						}
					}
				}
			}
			return array(
				'error'   => $err_num,
				'message' => $err_msg,
			);
		}

		/** Get registered fields and default values from Configuration Settings.
		 * Returns array of $field (name) => $default (value) pairs from Configuration Settings.
		 *
		 * @access public
		 * @param array $settings - configuration.
		 * @return array
		 */
		public static function get_settings_fields_and_default_values( $settings ) {
			$defaults = array();
			foreach ( self::get_settings_fields_and_properties( $settings ) as $field_name => $field_property ) {
				$defaults [ $field_name ] = isset( $field_property ['default'] ) ? $field_property ['default'] : null;
			}
			return $defaults;
		}

		/** Get all registered fields and properties from Configuration Settings.
		 * Returns array of $field [names] => array(prop-key=>prop-val) pairs from Configuration Settings.
		 *
		 * @access public
		 * @param array $settings - configuration.
		 * @return array
		 */
		public static function get_settings_fields_and_properties( $settings ) {
			$fldprops = array();
			if ( is_array( $settings ) ) {
				if ( isset( $settings ['sections'] ) && is_array( $settings ['sections'] ) ) {
					foreach ( $settings ['sections'] as $section_name => $section_settings ) {
						if ( isset( $section_settings ['fields'] ) && is_array( $section_settings ['fields'] ) ) {
							foreach ( $section_settings ['fields'] as $field_name => $field_property ) {
								if ( isset( $field_name ) && is_string( $field_name ) ) {
									$fldprops [ $field_name ] = is_array( $field_property ) ? $field_property : array();
								}
							}
						}
					}
				}
			}
			return $fldprops;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Extension Helpers & Utilities

		/** Send settings 'error' message to admin (on submit).
		 * Intended for use/call within input validation callbacks.
		 *
		 * @access public
		 * @param string $msg - error message, optional.
		 * @param string $code - error code, optional.
		 */
		public function add_settings_update_error( $msg = '', $code = '' ) {
			$msg = ( is_string( $msg ) && '' !== trim( $msg ) ? $msg : __( 'Settings error.' ) );
			$this->add_settings_update_message( $msg, $code );
		}

		/** Send settings 'updated' message to admin (on submit).
		 * Intended for use/call within input validation callbacks.
		 *
		 * @access public
		 * @param string $msg - error message, optional.
		 * @param string $code - error code, optional.
		 */
		public function add_settings_update_notice( $msg = '', $code = '' ) {
			$msg = ( is_string( $msg ) && '' !== trim( $msg ) ? $msg : __( 'Settings saved.' ) );
			$this->add_settings_update_message( $msg, $code, 'updated' );
		}

		/** Send settings message to admin (on submit).
		 * Intended for use/call within input validation callbacks.
		 *
		 * @access private
		 * @param string $msg - message, empty message defaults to ucfirst( $type ).
		 * @param string $code - error code, optional, random code generated if empty or omitted.
		 * @param string $type - default 'error', anything else defaults to 'updated'.
		 */
		private function add_settings_update_message( $msg, $code = '', $type = 'error' ) {
			$type = is_string( $type ) && 'error' === trim( $type ) ? $type : 'updated';
			$msg  = is_string( $msg ) && '' !== trim( $msg ) ? trim( $msg ) : ucfirst( $type );
			$code = is_string( $code ) && '' !== trim( $code ) ? $code : uniqid();
			add_settings_error(
				$this->pageslug,
				$code,
				$msg,
				$type
			);
		}

		/** Return true if settings update/validation added errors (add_settings_error).*/
		public function settings_update_has_errors() {
			global $wp_settings_errors;

			if ( is_array( $wp_settings_errors ) ) {
				foreach ( $wp_settings_errors as $error ) {
					if ( array_key_exists( 'setting', $error ) ) {
						if ( array_key_exists( 'type', $error ) ) {
							if ( $error ['setting'] === $this->pageslug
							&& 'error' === $error ['type'] ) {
								return true;
							}
						}
					}
				}
			}
			return false;
		}

		/** GetPlugin, get-method for protected plugin instance.
		 *
		 * @see Abstract_Plugin
		 * @access public
		 * @return object $this->plugin
		 */
		public function get_plugin() {
			return $this->plugin;
		}

		/** Shortcut to $this->plugin->get_option().
		 *
		 * @access protected
		 * @param mixed $key - string or null for all (array) options.
		 * @param mixed $default - optional.
		 * @return mixed or $default when [$key] has no value.
		 */
		protected function get_option( $key = null, $default = null ) {
			return $this->plugin->get_option( $key, $default );
		}

		/** Shortcut to $this->plugin->set_option().
		 *
		 * @access protected
		 * @param string $key to value.
		 * @param string $value of key.
		 * @param array  $options to modify, if omitted use get_option().
		 * @return array updated $options.
		 */
		protected function set_option( $key, $value = null, $options = null ) {
			return $this->plugin->set_option( $key, $value, $options );
		}

		/** Get private plugin $path variable.
		 * Set to $this->plugin->get_path() (by constructor).
		 *
		 * @access protected
		 * @return string $path of plugin
		 */
		protected function get_path() {
			return $this->path;
		}

		/** Get option fields names and default values.
		 * Cached, extracts all fields (name/default) from Configuration Settings.
		 * To extend configuration-settings fields,
		 * either override get_all_fields_extended()
		 * or configure 'fields-extended' Settings.
		 *
		 * @access public
		 * @return array of string => mixed (field-name => default-value)
		 */
		public function get_all_fields_defaults() {
			if ( null === $this->defaults ) {
				$this->defaults = array( 'submit' => null );

				foreach ( $this->get_all_fields_extended() as $field_name => $field_value ) {
					$this->defaults [ $field_name ] = $field_value;
				}

				if ( array_key_exists( 'fields-extended', $this->settings ) && is_array( $this->settings ['fields-extended'] ) ) {
					foreach ( $this->settings ['fields-extended'] as $field_name => $field_property ) {
						$this->defaults [ $field_name ] = $field_property;
					}
				}

				foreach ( $this->get_all_fields_settings() as $field_name => $field_property ) {
					$this->defaults [ $field_name ] = isset( $field_property ['default'] ) ? $field_property ['default'] : null;
				}
			}
			return $this->defaults;
		}

		/** Get fields names and properties of Plugin Configuration Settings.
		 * Cached output of self::get_settings_fields_and_properties.
		 *
		 * @access public
		 * @return array of string => mixed (field-name => default-value)
		 */
		public function get_all_fields_settings() {
			if ( null === $this->fldprops ) {
				$this->fldprops = self::get_settings_fields_and_properties( $this->settings );
			}
			return $this->fldprops;
		}

		/** Read Plugin Configuration Settings.
		 * Abstract, required to implement in derived class.
		 * Returns Plugin Configuration Settings.
		 *
		 * @access public
		 * @return array Plugin Configuration Settings compatible array.
		 */
		abstract public function read_configuration ();

		/** Get Plugin Configuration Settings.
		 * Returns Plugin Configuration Settings.
		 *
		 * @access public
		 * @return array Plugin Configuration Settings compatible array.
		 */
		public function get_settings() {
			return is_array( $this->settings ) ? $this->settings : array();
		}

		/** Get single field configuration-settings.
		 *
		 * @access public
		 * @param string $fn - field name.
		 * @return array of single field settings or empty array.
		 */
		public function get_field_settings( $fn ) {
			$all_fields_settings = $this->get_all_fields_settings();
			if ( array_key_exists( $fn, $all_fields_settings ) ) {
				return $all_fields_settings [ $fn ];
			}
			return array();
		}

		/** Return prefixed suffix string.
		 *
		 * @access public
		 * @param string $suffix - string to add prefix to.
		 * @return string $prefix().'_'.$suffix.
		 */
		public function add_prefix( $suffix ) {
			if ( ! is_string( $suffix ) ) {
				Lib::debug( 'Invalid_suffix: ' . ( is_string( $suffix ) ? $suffix : gettype( $suffix ) ) );
				$suffix = 'invalid_suffix';
			}
			return $this->prefix . '_' . $suffix;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Extension Methods (override in derived class, if needed)

		/** Check user authorization.
		 * To modify, override this method or modify $this->usercaps or modify config-settings.
		 *
		 * @access protected
		 * @return bool true if user is authorized to manage settings.
		 */
		protected function authorized() {
			return function_exists( '\get_current_user' )
			&& function_exists( '\current_user_can' )
			&& \current_user_can( $this->usercaps );
		}

		/** Authorized WP "init" action hook.
		 * Invoked for authorized users only.
		 * Override to apply init actions.
		 *
		 * @access protected
		 * @return void
		 */
		protected function authorized_init_action() {}

		/** Enqueue Admin-Page styles and scripts.
		 * Invoked only on Settings Page screen.
		 * Invoked just after enqueuing & registering styles/scripts.
		 * Override to enqueue more styles/scripts.
		 *
		 * @access protected
		 * @return void
		 */
		protected function enqueue_page_scripts() {}

		/** Add Meta Boxes for derived Admin Settings page.
		 * Invoked on setup of Settings Page Screen.
		 * Override to add custom Meta Boxes.
		 *
		 * @access protected
		 * @param string $menuslug - string returned by adding page to menu.
		 * @param object $screen - admin screen object instance.
		 * @return void
		 */
		protected function add_meta_boxes( $menuslug, $screen ) {}

		/** Get field display value.
		 * Invoked just before rendering input field.
		 * Override to check/replace/transform current $value (from wp-options).
		 *
		 * @access protected
		 * @param string $fn - file name of the field.
		 * @param mixed  $value of the field.
		 * @return mixed $value
		 */
		protected function get_form_field_value( $fn, $value ) {
			return $value;
		}

		/** Get extended option fields names and default values.
		 *
		 * Get extended option fields names and default values.
		 * Override and return fields not defined in configuration settings.
		 * Use to extend fields defined in configuration settings.
		 *
		 * @access protected
		 * @return array of string => mixed (field-name => default-value)
		 */
		protected function get_all_fields_extended() {
			return array();
		}

		/** Validate Form Input $options.
		 *
		 * Validate Form Input $options.
		 * Override to implement custom form-input validation.
		 * If 'validate' method is configured, it is called after this method.
		 *
		 * @access protected
		 * @param array $options - plugin options.
		 * @return array $options
		 */
		protected function validate_options( $options ) {
			return $options;
		}

		/** On form-submit & file upload (default) handler.
		 *
		 * On form-submit & file upload (default) handler.
		 * Called for each validated single-file upload.
		 * $upload_info is file descriptor extracted from from $_FILES and validated
		 * Success:
		 *     $upload_info array (
		 *     'name' = 'file name'
		 *     'size' = 'file size'
		 *     'type' = 'mime type'
		 *     ['error' = 0]
		 *     'tmp_name' = 'temporary file name'
		 *     )
		 * Failure:
		 *     $upload_info array (
		 *       'error' = code # int
		 *       'message' = 'error massage'
		 *     )
		 * Override to handle each uploaded file (move to destination?).
		 * Alternative: Override validate_form_input and handle upload-field [$upload_infos] values.
		 *
		 * @access protected
		 * @param array  $upload_info - file descriptor.
		 * @param string $field_name - $options[KEY:$field_name] (input type=file name=$field_name).
		 * @param array  $options - plugin options.
		 * @return array|mixed: returned $result will be stored into $option[$field_name].
		 */
		protected function handle_single_file_upload( $upload_info, $field_name, $options ) {
			return $upload_info;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Extension Hooks (filters/actions to hook into)

		/** Apply render filters for field arguments, just before html generated.
		 *
		 * @ignore
		 * @access private
		 * @param array  $args - field args.
		 * @param string $fn - field name.
		 * @return array $args
		 */
		private function apply_field_args_filters( $args, $fn ) {
			return apply_filters( $this->prefix . '_field_args', $args, $fn );
		}

		/** Apply render filters for field-html, just before html echo.
		 *
		 * @ignore
		 * @access private
		 * @param string $html - field html.
		 * @param string $args - field args.
		 * @param string $fn - field name.
		 * @return string $html
		 */
		private function apply_field_html_filters( $html, $args, $fn ) {
			return apply_filters( $this->prefix . '_field_html', $html, $args, $fn );
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Get Select Options Callbacks

		/** Return tags for <select/option>.
		 *
		 * @see: https://developer.wordpress.org/reference/classes/wp_term_query/__construct/.
		 * @param array $args - get_tags( $args ).
		 */
		public function get_select_tags( $args ) {
			$term_ids = array();
			$terms    = null === $args ? get_tags() : get_tags( $args );
			foreach ( $terms as $term ) {
				$term_ids [ $term->term_id ] = $term->name;
			}
			return $term_ids;
		}

		/** Return categories for <select/option>.
		 *
		 * @see: https://developer.wordpress.org/reference/classes/wp_term_query/__construct/.
		 * @param array $args - get_categories( $args ).
		 */
		public function get_select_categories( $args ) {
			$term_ids = array();
			$terms    = null === $args ? get_categories() : get_categories( $args );
			foreach ( $terms as $term ) {
				$term_ids [ $term->term_id ] = $term->name;
			}
			return $term_ids;
		}

		/** Return terms for <select/option>.
		 *
		 * @see: https://developer.wordpress.org/reference/classes/wp_term_query/__construct/.
		 * @param array $args - get_terms( $args ).
		 */
		public function get_select_terms( $args ) {
			$term_ids = array();
			$terms    = null === $args ? get_terms() : get_terms( $args );
			foreach ( $terms as $term ) {
				$term_ids [ $term->term_id ] = $term->name;
			}
			return $term_ids;
		}

		/** Return pages for <select/option>.
		 *
		 * @see: https://developer.wordpress.org/reference/functions/get_pages/.
		 * @param array $args - get_post( $args ).
		 */
		public function get_select_pages( $args = null ) {
			$pageids = array();
			$default = array(
				'numberposts' => 100,
				'post_type'   => 'page',
				'post_status' => 'publish',
			);
			if ( ! is_array( $args ) ) {
				$args = $default;
			} else {
				$args ['post_type'] = 'page';
				$args               = array_merge( $default, $args );
			}
			$pages = get_posts( $args );
			foreach ( $pages as $page ) {
				$pageids [ $page->ID ] = $page->post_title;
			}
			return $pageids;
		}

		/** Return posts for <select/option>.
		 *
		 * @see: https://developer.wordpress.org/reference/functions/get_posts/.
		 * @param array $args - get_post( $args ).
		 */
		public function get_select_posts( $args = null ) {
			$pageids = array();
			$default = array(
				'numberposts' => 100,
				'post_type'   => 'post',
				'post_status' => 'publish',
			);
			if ( ! is_array( $args ) ) {
				$args = $default;
			} else {
				$args ['post_type'] = 'post';
				$args               = array_merge( $default, $args );
			}
			$posts = get_posts( $args );
			foreach ( $posts as $post ) {
				$pageids [ $post->ID ] = $post->post_title;
			}
			return $pageids;
		}

		/** Return attachment image post types for <select/option>.
		 *
		 * @see https://developer.wordpress.org/reference/functions/get_posts/.
		 * @param array $args - get_post( $args ).
		 */
		public function get_select_images( $args = null ) {
			$pageids    = array();
			$mime_types = array();
			foreach ( get_allowed_mime_types() as $mime ) {
				if ( Lib::starts_with( $mime, 'image/' ) ) {
					$mime_types [] = $mime;
				}
			}
			$default = array(
				'numberposts'    => 100,
				'post_type'      => 'attachment',
				'post_mime_type' => $mime_types,
			);
			if ( ! is_array( $args ) ) {
				$args = $default;
			} else {
				$args ['post_type']      = 'attachment';
				$args ['post_mime_type'] = $mime_types;
				$args                    = array_merge( $default, $args );
			}
			$posts = get_posts( $args );
			foreach ( $posts as $post ) {
				$pageids [ $post->ID ] = $post->post_title;
			}
			return $pageids;
		}

		/** Return all custom post types for <select/option>.*/
		public function get_select_custom_post_types() {
			return get_post_types(
				array(
					'_builtin'            => false,
					'show_ui'             => true,
					'publicly_queryable'  => true,
					'exclude_from_search' => false,
				)
			);
		}

		/** Return all custom type posts for <select/option>, group per type.
		 *
		 * @param array $args - get_post( $args ).
		 */
		public function get_select_custom_type_posts( $args = null ) {
			$custom_posts      = array();
			$custom_post_types = $this->get_select_custom_post_types();
			$default           = array(
				'numberposts' => 100,
				'post_status' => 'publish',
			);
			if ( ! is_array( $args ) ) {
				$args = $default;
			} else {
				$args = array_merge( $default, $args );
			}
			if ( array_key_exists( 'post_type', $args ) ) {
				if ( is_string( $args ['post_type'] ) && in_array( $args ['post_type'], $custom_post_types, true ) ) {
					$custom_post_types = array( $args ['post_type'] => $args ['post_type'] );
				}

				if ( is_array( $args ['post_type'] ) ) {
					$valid_cpts = array();
					foreach ( $args ['post_type'] as $cpt ) {
						if ( in_array( $cpt, $custom_post_types, true ) ) {
							$valid_cpts [ $cpt ] = $cpt;
						}
					}
					if ( count( $valid_cpts ) > 0 ) {
						$custom_post_types = $valid_cpts;
					}
				}
			}
			foreach ( $custom_post_types as $k => $v ) {
				$args ['post_type'] = $k;
				$posts              = get_posts( $args );
				if ( count( $posts ) > 0 ) {
					$custom_posts [ $v ] = array();
					foreach ( $posts as $post ) {
						$custom_posts [ $v ][ $post->ID ] = $post->post_title;
					}
				}
			}
			return $custom_posts;
		}

		/** Return Uploads Directory and Uploads Top Sub Directories if found.*/
		public function get_select_uploads_directories() {
			$uploads_dirs                   = array();
			$uploads_path                   = wp_upload_dir() ['basedir'];
			$uploads_dirs [ $uploads_path ] = basename( $uploads_path );
			$uploads_subdirs                = self::scandirs_top( $uploads_path );
			foreach ( $uploads_subdirs as $subdir_path ) {
				$uploads_dirs [ $subdir_path ] = '/' . basename( $subdir_path );
			}
			return $uploads_dirs;
		}

		/** Get Select Plugins Directories.*/
		public function get_select_plugins_directories() {
			$plugins_dirs = array();
			foreach ( self::scandirs_top( WP_PLUGIN_DIR ) as $subdir_path ) {
				$plugins_dirs [ $subdir_path ] = '/' . basename( $subdir_path );
			}
			return $plugins_dirs;
		}

		/** Get Select Themes Directories.*/
		public function get_select_themes_directories() {
			$themes_dirs  = array();
			$themes_roots = $this->get_themes_roots();
			foreach ( $themes_roots as $themes_root ) {
				foreach ( self::scandirs_top( WP_CONTENT_DIR . $themes_root ) as $theme_dir ) {
					$themes_dirs [ $theme_dir ] = '/' . \basename( $theme_dir );
				}
			}
			return $themes_dirs;
		}

		/** Get all root directories for themes.*/
		public function get_themes_roots() {
			$themes_temp  = \get_theme_roots();
			$themes_roots = array();
			if ( is_string( $themes_temp ) ) {
				$themes_roots [] = $themes_temp;
			} elseif ( is_array( $themes_temp ) ) {
				$themes_roots = $themes_temp;
			} else {
				$themes_roots [] = '/themes';
			}
			return $themes_roots;
		}

		/** Scandirs Top.
		 *
		 * @param string $dir - directory to scan.
		 * @param array  $callback_filter - filter to match directory.
		 */
		private static function scandirs_top( $dir, $callback_filter = false ) {
			$files = array();

			if ( ! is_string( $dir ) ) {
				Lib::debug( 'Invalid Argument $dir type: ' . gettype( $dir ) );
				return $files;
			}
			if ( ! file_exists( $dir ) ) {
				Lib::debug( 'Argument $dir, file does not exists: ' . $dir );
				return $files;
			}

			if ( false !== $callback_filter && ! is_callable( $callback_filter ) ) {
				Lib::debug( 'Argument $callback_filter is not callable.' );
				$callback_filter = false;
			}

			if ( ! is_link( $dir ) && is_dir( $dir ) ) {
				foreach ( scandir( $dir ) as $file_name ) {
					if ( '.' === $file_name || '..' === $file_name ) {
						continue;
					}
					$file_path = $dir . DIRECTORY_SEPARATOR . $file_name;
					if ( ! is_link( $file_path ) ) {
						if ( is_dir( $file_path ) ) {
							if ( ! $callback_filter || call_user_func( $callback_filter, $file_path ) ) {
								$files [] = $file_path;
							}
						}
					}
				}
			}
			return $files;
		}

		// phpcs:ignore
	# endregion

	}
}
