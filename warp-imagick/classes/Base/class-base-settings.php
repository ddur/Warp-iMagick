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

namespace ddur\Warp_iMagick\Base;

defined( 'ABSPATH' ) || die( -1 );

use ddur\Warp_iMagick\Shared;
use ddur\Warp_iMagick\Base\Plugin\v1\Abstract_Settings;
use ddur\Warp_iMagick\Base\Plugin\v1\Abstract_Plugin;
use ddur\Warp_iMagick\Hlp;
use ddur\Warp_iMagick\Dbg;

$class_name = __NAMESPACE__ . '\\Base_Settings';

if ( ! class_exists( $class_name ) ) {
	/** Settings base class. */
	abstract class Base_Settings extends Abstract_Settings {
		// phpcs:ignore
	# region Construction and Instance

		/** Static singleton object.
		 *
		 * @var object $me contains this class singleton.
		 */
		private static $me = null;

		/** Once constructor.
		 * Static Singleton Class Constructor.
		 *
		 * @param object $plugin instance.
		 * @return mixed this object singleton or null on error.
		 */
		public static function once( $plugin = null ) {
			if ( null === self::$me && null !== $plugin && $plugin instanceof Abstract_Plugin ) {
				try {
					self::$me = new static( $plugin );
					self::$me->init();

				} catch ( \Exception $e ) {
					self::$me = null;
					Dbg::error( $e->getMessage() );
				}
			}
			if ( null === self::$me ) {
				if ( null === $plugin ) {
					Dbg::error( 'Missing $plugin argument' );
				} elseif ( ! $plugin instanceof Abstract_Plugin ) {
					Dbg::error( 'Invalid $plugin argument' );
				}
			}
			return self::$me;
		}

		/** Class initialization. */
		protected function init() {}

		/** Class instance.
		 *
		 * Static access to Class instance.
		 */
		public static function instance() {
			return self::$me;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Helper functions.

		/** New Options Initialized.
		 *
		 * @var bool $new_options is true when default options is created.
		 */
		protected $new_options = false;

		/** Initialize plugin options if do not exists in database.
		 * Upgrade/maintain plugin options if already exists in database.
		 *
		 * Called on activate, for each site if multisite
		 * and on upgrade if plugin is active.
		 *
		 * On upgrade active plugin, this is called twice,
		 * once for old version and once for new version.
		 *
		 * @param array $setup Custom fields to initialize.
		 */
		public function init_options( $setup = array() ) {
			$current_options = $this->get_option();
			if ( empty( $current_options ) ) {
				$current_options = $this->get_all_fields_defaults();

				if ( is_array( $setup ) && ! empty( $setup ) ) {
					foreach ( $setup as $option => $value ) {
						$current_options [ $option ] = $value;
					}
				}
				global $wp_filter;
				$hook_name = "pre_update_option_{$this->optionid}";
				if ( isset( $wp_filter[ $hook_name ] ) && $wp_filter[ $hook_name ]->has_filters() ) {
					\remove_all_filters( $hook_name );
					sleep( 0 );

				}
				sleep( 0 );

				\update_option( $this->optionid, $current_options, true );
				$this->new_options = true;
			}

			// phpcs:enable

			$current_options = $this->get_option();
			$update_settings = false;

			$plugin_version = Shared::get_plugin_version();
			$option_version = $this->get_option( 'plugin-version' );

			if ( ! empty( $plugin_version ) && $option_version !== $plugin_version ) {
				$current_options = $this->set_option( 'plugin-version', $plugin_version, $current_options );
				$update_settings = true;
			}

			// phpcs:enable

			if ( true === $update_settings ) {
				/** Save raw options data, already (form) validated.*/
				global $wp_filter;
				$hook_name = "pre_update_option_{$this->optionid}";
				if ( isset( $wp_filter[ $hook_name ] ) && $wp_filter[ $hook_name ]->has_filters() ) {
					\remove_all_filters( $hook_name );
					sleep( 0 );

				}
				sleep( 0 );

				\update_option( $this->optionid, $current_options, true );
			}
		}

		/** Initialize plugin (multisite) options.
		 *
		 * Use in 'on_activate_plugin' method
		 *
		 * @access public
		 * @internal callback on activate and upgrade.
		 * @param bool  $networkwide flag.
		 * @param array $setup - extended/custom fields to initialize.
		 * @return void
		 */
		public function init_all_options( $networkwide = true, $setup = array() ) {
			if ( is_multisite() && $networkwide ) {
				$sites = \get_sites();
				foreach ( $sites as $site ) {
					\switch_to_blog( $site->blog_id );
					$this->init_options( $setup );
				}
				\restore_current_blog();
			} else {
				$this->init_options( $setup );
			}
		}

		/** Remove all (&multisite) options.
		 *
		 * Use in 'on_uninstall_plugin' method
		 *
		 * @access protected
		 * @param string $option_id - Option API ID.
		 * @return void
		 */
		protected static function remove_all_options( $option_id ) {
			if ( is_multisite() ) {
				delete_site_option( $option_id );

				$sites = get_sites();
				foreach ( $sites as $site ) {
					switch_to_blog( $site->blog_id );
					delete_option( $option_id );

				}
				restore_current_blog();
			} else {
				delete_option( $option_id );

			}
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Plugin Dynamic Menu or Submenu Position

		/** Initialize plugin dynamic configuration
		 *
		 * @param array $values - reference to options values.
		 */
		protected function set_dynamic_configuration( &$values ) {
			if ( ! array_key_exists( 'configuration', $values ) ) {
				$values ['configuration'] = array();
			}
			if ( ! array_key_exists( 'menu', $values ['configuration'] ) ) {
				$values ['configuration']['menu'] = array();
			}
			if ( ! array_key_exists( 'parent-slug', $values ['configuration']['menu'] ) ) {
				$values ['configuration']['menu']['parent-slug'] = null;

				$values ['configuration']['menu']['position'] = null;

			}
		}

		/** Plugin dynamic menu position
		 *
		 * @param array $values - reference to options values.
		 */
		protected function set_dynamic_menu_position( &$values ) {
			$this->set_dynamic_configuration( $values );

			$config_menu_parent = Hlp::safe_key_value( $this->settings, array( 'menu', 'parent-slug' ), '' );

			if ( ! self::is_valid_menu_parent_slug( $config_menu_parent ) ) {
				$config_menu_parent = abs( Hlp::safe_key_value( $this->settings, array( 'menu', 'position' ), 0 ) );
			}

			$menu_input = $values ['menu-parent-slug'];

			if ( empty( $menu_input )

			|| ( ! self::is_valid_menu_parent_slug( $menu_input )
			&& ! is_numeric( $menu_input ) ) ) {
				$menu_input = $config_menu_parent;
			}

			if ( is_numeric( $menu_input ) ) {
				$menu_input = abs( intval( $menu_input ) );

				$values ['configuration']['menu']['parent-slug'] = '';

				$values ['configuration']['menu']['position'] = $menu_input;

				$admin_page = '';

			} else {
				$values ['configuration']['menu']['parent-slug'] = $menu_input;
				$values ['configuration']['menu']['position']    = 0;

				$admin_page = $menu_input;
			}

			$_REQUEST ['_wp_http_referer'] = add_query_arg(
				'page',
				$this->pageslug,
				admin_url() . ( trim( $admin_page ) ? $admin_page : 'admin.php' )
			);

			if ( $menu_input === $config_menu_parent ) {
				$menu_input = '';
			}

			$values ['menu-parent-slug'] = $menu_input;
		}

		// phpcs:ignore
	# endregion
	}
} else {
	Dbg::debug( "Class already exists: $class_name " );
}
