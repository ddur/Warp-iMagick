<?php
/**
 * Copyright © 2017-2022 Dragan Đurić. All rights reserved.
 *
 * @package warp-imagick
 * @license GNU General Public License Version 2.
 * @copyright © 2017-2022. All rights reserved.
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

if ( ! class_exists( __NAMESPACE__ . '\Abstract_Plugin' ) ) {

	/** Derive your plugin class from this abstract class.
	 */
	abstract class Abstract_Plugin {

		const VERSION = '1';

		/** __FILE__ constant from plugin entry file.
		 *
		 * @var string $file contains __FILE__ constant from plugin entry file.
		 */
		private $file = null;

		/** Path to plugin directory.
		 *
		 * @var string $path to plugin directory.
		 */
		private $path = null;

		/** Url path to plugin directory.
		 *
		 * @var string $url path to plugin directory.
		 */
		private $url = null;

		/** Plugin slug (-)
		 *
		 * @var string $slug for plugin.
		 */
		private $slug = null;

		/** Plugin prefix (_)
		 *
		 * @var string $prefix for plugin.
		 */
		private $prefix = null;

		/** Plugin option ID
		 *
		 * @var string $optionid for plugin Options API.
		 */
		private $optionid = null;

		/** Plugin basename
		 *
		 * @var string $basename of plugin.
		 */
		private $basename = null;

		/** Plugin languages directory
		 *
		 * @var string $language_dir for plugin languages (dirname($basename)/languages/).
		 */
		private $language_dir = null;

		/** Plugin textdomain loaded
		 *
		 * @var string $language_dir for plugin languages (dirname($basename)/languages/).
		 */
		private $textdomain = null;

		/** Abstract Plugin version
		 *
		 * @var string $abstract_version of abstract plugin class/library (=== namespace version).
		 */
		private $abstract_version = null;

		/** Get abstract class/library version.
		 * Extracts version from namespace.
		 *
		 * @return string abstract class/library version.
		 */
		public function get_abstract_version() {
			if ( null === $this->abstract_version ) {
				$namespace              = explode( '\\', __NAMESPACE__ );
				$namespace_version      = array_pop( $namespace );
				$this->abstract_version = substr( $namespace_version, 1 );
				$this->abstract_version = ctype_digit( $this->abstract_version [0] ) ? $this->abstract_version : '';
			}
			return $this->abstract_version;
		}

		/** Class constructor method.
		 *
		 * @access protected
		 * @param string $file Magic __FILE__ constant from/of plugin-entry file.
		 */
		protected function __construct( $file ) {

			$this->file = $file;

			if ( Lib::is_debug() ) {

				$msg = array();
				if ( ! is_string( $file ) ) {
					$msg [] = __METHOD__ . ': Argument $file is not a string (filename)';
				} elseif ( ! file_exists( $file ) ) {
					$msg [] = __METHOD__ . ": File $file does not exists";
				}
				if ( ! empty( $msg ) ) {
					Lib::error( implode( PHP_EOL, $msg ) );
					foreach ( $msg as $msg_line ) {
						echo '<p>';
						echo esc_html( $msg_line );
						echo '</p>';
					}
					wp_die();
				}
			}

		}

		/** Get plugin entry-file name.
		 *
		 * Get plugin entry-file magic __FILE__ constant.
		 *
		 * @access public
		 * @return string magic __FILE__ constant given in class constructor
		 */
		public function get_file() {
			return $this->file; }

		/** Get plugin's directory absolute path. Lazy & cached.
		 *
		 * Get plugin's directory absolute path, derived from magic __FILE__ constant.
		 *
		 * @access public
		 * @return string plugin path derived from magic __FILE__ constant given in class constructor
		 */
		public function get_path() {
			if ( null === $this->path ) {
				$this->path = dirname( $this->get_file() );
			}
			return $this->path;
		}

		/** Get plugin's url-path. Lazy & cached.
		 * Get plugin directory url-path (relative to site root).
		 *
		 * @access public
		 * @return string plugin directory url-path.
		 */
		public function get_url_path() {
			if ( null === $this->url ) {
				$this->url = untrailingslashit( plugin_dir_url( $this->get_file() ) );
			}
			return $this->url;
		}

		/** Get plugin basename. Lazy & cached.
		 * Get plugin basename as returned from plugin_basename wp-function.
		 *
		 * @access public
		 * @return string plugin basename, returned from plugin_basename(__FILE__) wp-function.
		 */
		public function get_basename() {
			if ( null === $this->basename ) {
				$this->basename = plugin_basename( $this->get_file() );
			}
			return $this->basename;
		}

		/** Get plugin directory name (dirname(basename)).
		 * Get plugin directory name as returned from dirname(plugin_basename).
		 *
		 * @access public
		 * @return string plugin dirname as returned from dirname(plugin_basename(__FILE__)).
		 */
		public function get_dirname() {
			return dirname( $this->get_basename() );
		}

		/** Get plugin $slug. Lazy & cached. Override to customize.
		 *
		 * Slug is identical to plugin directory name (path excluded),
		 * except underscore characters that are replaced with dash (-).
		 * Use 'slug' for settings page and/or text-domain identifier (i10n).
		 *
		 * @access public
		 * @return string
		 */
		public function get_slug() {
			if ( null === $this->slug ) {
				$this->slug = str_replace( '_', '-', $this->get_dirname() );
			}
			return $this->slug;
		}

		/** Get plugin $prefix. Lazy & cached. Override to customize.
		 *
		 * Prefix is identical to plugin directory name (path excluded),
		 * except not alphanumeric 7-bit characters that are replaced with '_' (underscore).
		 * Use 'prefix' for valid PHP (and maybe JavaScript) identifiers.
		 *
		 * @see http://php.net/manual/en/language.variables.basics.php
		 *
		 * @access public
		 * @return string
		 */
		public function get_prefix() {
			if ( null === $this->prefix ) {

				$this->prefix = preg_replace( '/^[^_a-zA-Z\x80-\xff]/', '_', $this->get_dirname() );
				$this->prefix = preg_replace( '/[^_a-zA-Z\x80-\xff\d]/', '_', $this->prefix );
			}
			return $this->prefix;
		}

		/** Plugin's Option API ID. Lazy and cached.
		 *
		 * @return string
		 */
		public function get_option_id() {
			if ( null === $this->optionid ) {
				$this->optionid = $this->get_prefix() . '_options';
			}
			return $this->optionid;
		}

		/** Plugin's language(s) directory. Lazy and cached.
		 *
		 * @return string
		 */
		public function get_language_dir() {
			if ( null === $this->language_dir ) {
				$this->language_dir = $this->get_dirname() . '/languages';
			}
			return $this->language_dir;
		}

		/** Get plugin option(s) array.
		 *
		 * @param mixed $key value or null for all (array) options.
		 * @param mixed $default value to return when [$key] does not exists.
		 * @return mixed value or $default when [$key] has no value.
		 */
		public function get_option( $key = null, $default = null ) {
			$options = get_option( $this->get_option_id(), array() );
			if ( null === $key ) {
				return $options;
			} elseif ( array_key_exists( $key, $options ) ) {
				return $options [ $key ];
			}
			return $default;
		}

		/** Load Plugin Text Domain
		 *
		 * @access public
		 * @return void
		 */
		public function load_textdomain() {
			if ( true !== $this->textdomain ) {
				$this->textdomain = load_plugin_textdomain( $this->get_slug(), false, $this->get_language_dir() );
			}
		}

	}
}
