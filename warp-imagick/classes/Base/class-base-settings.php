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

use \ddur\Warp_iMagick\Shared;
use \ddur\Warp_iMagick\Base\Plugin\v1\Lib;
use \ddur\Warp_iMagick\Base\Plugin\v1\Abstract_Settings;
use \ddur\Warp_iMagick\Base\Plugin\v1\Abstract_Plugin;

if ( ! class_exists( __NAMESPACE__ . '\Base_Settings' ) ) {

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
				} catch ( Exception $e ) {
					self::$me = null;
					Lib::error( $e->getMessage() );
				}
			}
			if ( null === self::$me ) {
				if ( null === $plugin ) {
					Lib::error( 'Missing $plugin argument' );
				} elseif ( ! $plugin instanceof Abstract_Plugin ) {
					Lib::error( 'Invalid $plugin argument' );
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

			$current_option = $this->read_options();
			if ( empty( $current_option ) ) {

				$current_option = $this->get_all_fields_defaults();

				if ( is_array( $setup ) && ! empty( $setup ) ) {
					foreach ( $setup as $option => $value ) {
						$current_option [ $option ] = $value;
					}
				}

				$current_option = $this->validate_options( $current_option );
				$this->save_options( $current_option );

			} else {

				$update_setting = false;
				$plugin_version = $this->get_plugin()->read_plugin_version();
				$option_version = isset( $current_option ['plugin-version'] ) ? $current_option ['plugin-version'] : '';

				if ( empty( $option_version ) ) {

					delete_option( $option_slug_id );

					set_transient(
						$this->pageslug . '-update-notices',
						array(
							array( 'notice' => 'Warp iMagick: New plugin version (1.0.1+) activated. Plugin settings are cleared to default values.' ),
							array( 'error' => 'Warp iMagick: Please configure and update settings to disable this warning message.' ),
						)
					);
					return;
				}

				if ( $option_version !== $plugin_version ) {
					$current_option ['plugin-version'] = $plugin_version;
					$update_setting                    = true;
				}

				if ( ! isset( $current_option ['jpeg-colorspace'] )
				|| 0 !== (int) $current_option ['jpeg-colorspace'] ) {

					$current_option ['jpeg-colorspace'] = Shared::jpeg_colorspace_default();
					$update_setting                     = true;
				}

				if ( $update_setting ) {
					$current_option = $this->validate_options( $current_option );
					$this->save_options( $current_option );
				}
			}
		}

		/** Read all options
		 *
		 * @return array of options or empty array.
		 */
		public function read_options() {
			$option_values = get_option( $this->optionid, null );
			if ( ! is_array( $option_values ) ) {
				$option_values = array();
			}
			return $option_values;
		}

		/** Save but without form validation
		 *
		 * @param array $option_values to save.
		 */
		public function save_options( $option_values ) {

			remove_filter( "pre_update_option_{$this->optionid}", array( $this, 'on_abstract_validate_form_input' ), 10 );

			update_option( $this->optionid, $option_values, true );

			add_filter( "pre_update_option_{$this->optionid}", array( $this, 'on_abstract_validate_form_input' ), 10, 3 );
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
				$values ['configuration']['menu']['position']    = null;
			}

		}

		/** Plugin dynamic menu position
		 *
		 * @param array $values - reference to options values.
		 */
		protected function set_dynamic_menu_position( &$values ) {

			$this->set_dynamic_configuration( $values );

			$config_menu_parent = Lib::safe_key_value( $this->settings, array( 'menu', 'parent-slug' ), '' );

			if ( ! self::is_valid_menu_parent_slug( $config_menu_parent ) ) {

				$config_menu_parent = abs( Lib::safe_key_value( $this->settings, array( 'menu', 'position' ), 0 ) );
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
				$values ['configuration']['menu']['position']    = $menu_input;

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

}
