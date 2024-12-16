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
use ddur\Warp_iMagick\Base\Meta_Settings;
use ddur\Warp_iMagick\Settings_Renderer;
use ddur\Warp_iMagick\Shared;

require_once ABSPATH . 'wp-admin/includes/file.php';

$class = __NAMESPACE__ . '\\Settings';

if ( ! class_exists( $class ) ) {
	/** Implementation of Plugin Settings. */
	class Settings extends Meta_Settings {
		// phpcs:ignore
	# region Configuration and Construction

		/** Get Plugin Settings/Configuration, abstract method, must implement.
		 *
		 * @return array Plugin Configuration Settings compliant array.
		 */
		public function read_configuration() {
			return require __DIR__ . '/configuration.php';
		}

		/** Settings init. Called immediately after plugin class is constructed. */
		protected function init() {
			parent::init();

			/** Instantiate custom Settings_Renderer. */
			$this->renderer = new Settings_Renderer( $this );
		}

		/** Enqueue admin page scripts. Derived & Overridden. */
		public function enqueue_page_scripts() {
			Lib::enqueue_style( 'abstract-settings-admin-styled' );
			Lib::enqueue_script( 'abstract-settings-admin-styled' );

			$dependencies   = array( 'abstract-settings-admin-styled' );
			$plugin_version = $this->get_option( 'plugin-version', false );
			$relative_path  = Lib::relative_path( $this->plugin->get_path() );

			Lib::enqueue_style( $this->pageslug, $relative_path . '/assets/style.css', array(), $style_version = $plugin_version, 'screen' );

			Lib::enqueue_script( $this->pageslug, $relative_path . '/assets/script.js', $dependencies, $script_version = $plugin_version, $in_footer = true );

			// phpcs:enable
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Form Display and Validation

		/** Get form/display field value. Overridden.
		 *
		 * From Options API to Settings UI (page&form).
		 * Validate (&transform) options.
		 *
		 * @param string $name of the field.
		 * @param mixed  $value of the field.
		 * @return mixed $value for display.
		 */
		protected function get_form_field_value( $name, $value ) {
			return $value;
		}

		/** Validate form submitted checkbox by key (field name).
		 *
		 * @param string $key - name of the checkbox field.
		 * @param array  $values - all submitted values.
		 */
		public static function validate_checkbox_key( $key, &$values ) {
			if ( ! array_key_exists( $key, $values ) ) {
				$values [ $key ] = false;
			} else {
				$values [ $key ] = self::validate_checkbox_value( $values [ $key ] );
			}
		}

		/** Validate form checkbox value.
		 *
		 * @param mixed $value of submitted checkbox field.
		 * @return bool $value validated to bool.
		 */
		public static function validate_checkbox_value( $value ) {
			switch ( $value ) {
				case true:
				case 'on':
					$value = true;
					break;
				default:
					if ( ! is_bool( $value ) ) {
						$value = false;
					}
			}
			return $value;
		}

		/** Validate (&transform) input options(values). Overridden.
		 *
		 * From Settings UI (form&submit) to Options API.
		 *
		 * @param array $options to validate.
		 * @return array $options validated.
		 */
		public function validate_options( $options ) {
			if ( ! is_array( $options ) ) {
				$gettype = gettype( $options );
				$options = array();
				$this->add_settings_update_error( 'Invalid options type: ' . $gettype );
			}

			self::validate_checkbox_key( 'jpeg-colorspace-force', $options );

			self::validate_checkbox_key( 'png-reduce-colors-enable', $options );

			self::validate_checkbox_key( 'png-reduce-colors-dither', $options );

			self::validate_checkbox_key( 'webp-images-create', $options );

			self::validate_checkbox_key( 'webp-cwebp-on-demand', $options );

			self::validate_checkbox_key( 'wp-big-image-size-threshold-disabled', $options );

			self::validate_checkbox_key( 'wp-maybe-exif-rotate-disabled', $options );

			self::validate_checkbox_key( 'compress-jpeg-original-disabled', $options );

			self::validate_checkbox_key( 'image-max-width-enabled', $options );

			self::validate_checkbox_key( 'image-max-width-backup', $options );

			self::validate_checkbox_key( 'extra-size-same-width', $options );

			self::validate_checkbox_key( 'remove-settings', $options );

			self::validate_checkbox_key( 'verbose-debug-enabled', $options );

			if ( ! array_key_exists( 'image-max-width-pixels', $options ) ) {
				$options ['image-max-width-pixels'] = Shared::max_width_value_default();
			} else {
				$options ['image-max-width-pixels'] = (int) $options ['image-max-width-pixels'];
				if ( $options ['image-max-width-pixels'] < Shared::max_width_value_min() || $options ['image-max-width-pixels'] > Shared::max_width_value_max() ) {
					$options ['image-max-width-pixels'] = Shared::max_width_value_default();
				}
			}

			if ( ! array_key_exists( 'jpeg-sharpen-image', $options ) ) {
				$options ['jpeg-sharpen-image'] = Shared::jpeg_sharpen_image_value_default();
			} else {
				$options ['jpeg-sharpen-image'] = (int) $options ['jpeg-sharpen-image'];
				if ( $options ['jpeg-sharpen-image'] < Shared::sharpen_value_min() || $options ['jpeg-sharpen-image'] > Shared::sharpen_value_max() ) {
					$options ['jpeg-sharpen-image'] = Shared::jpeg_sharpen_image_value_default();
				}
			}

			if ( ! array_key_exists( 'jpeg-sharpen-thumbnails', $options ) ) {
				$options ['jpeg-sharpen-thumbnails'] = Shared::jpeg_sharpen_thumbnails_value_default();
			} else {
				$options ['jpeg-sharpen-thumbnails'] = (int) $options ['jpeg-sharpen-thumbnails'];
				if ( $options ['jpeg-sharpen-thumbnails'] < Shared::sharpen_value_min() || $options ['jpeg-sharpen-thumbnails'] > Shared::sharpen_value_max() ) {
					$options ['jpeg-sharpen-thumbnails'] = Shared::jpeg_sharpen_thumbnails_value_default();
				}
			}

			if ( ! array_key_exists( 'jpeg-compression-quality', $options ) ) {
				$options ['jpeg-compression-quality'] = Shared::jpeg_quality_default();
			} else {
				$options ['jpeg-compression-quality'] = (int) $options ['jpeg-compression-quality'];
				if ( $options ['jpeg-compression-quality'] < Shared::jpeg_quality_value_min() ) {
					$options ['jpeg-compression-quality'] = Shared::jpeg_quality_default();
				}
				if ( $options ['jpeg-compression-quality'] > Shared::jpeg_quality_value_max() ) {
					$options ['jpeg-compression-quality'] = Shared::jpeg_quality_default();
				}
			}

			// phpcs:enable

			if ( ! array_key_exists( 'jpeg-colorspace', $options ) ) {
				$options ['jpeg-colorspace'] = Shared::jpeg_colorspace_default();
			} else {
				$options ['jpeg-colorspace'] = (int) $options ['jpeg-colorspace'];
				if ( ! array_key_exists( $options ['jpeg-colorspace'], $this->get_form_jpeg_colorspaces() ) ) {
					$options ['jpeg-colorspace'] = Shared::jpeg_colorspace_default();
				}
			}

			if ( ! array_key_exists( 'jpeg-interlace-scheme', $options ) ) {
				$options ['jpeg-interlace-scheme'] = Shared::jpeg_interlace_scheme_default();
			} else {
				$options ['jpeg-interlace-scheme'] = (int) $options ['jpeg-interlace-scheme'];
				if ( ! array_key_exists( $options ['jpeg-interlace-scheme'], $this->get_form_jpeg_interlace_types() ) ) {
					$options ['jpeg-interlace-scheme'] = Shared::jpeg_interlace_scheme_default();
				}
			}

			if ( ! array_key_exists( 'jpeg-sampling-factor', $options ) ) {
				$options ['jpeg-sampling-factor'] = Shared::jpeg_sampling_factor_default();
			} else {
				$valid_sampling_factors = $this->get_form_jpeg_sampling_factors();
				if ( ! array_key_exists( $options ['jpeg-sampling-factor'], $valid_sampling_factors ) ) {
					$options ['jpeg-sampling-factor'] = Shared::jpeg_sampling_factor_default();
				}
			}

			if ( ! array_key_exists( 'jpeg-strip-meta', $options ) ) {
				$options ['jpeg-strip-meta'] = Shared::jpeg_strip_meta_default();
			} else {
				$options ['jpeg-strip-meta'] = (int) $options ['jpeg-strip-meta'];
				if ( ! array_key_exists( $options ['jpeg-strip-meta'], $this->get_form_strip_metadata() ) ) {
					$options ['jpeg-strip-meta'] = Shared::jpeg_strip_meta_default();
				}
			}

			if ( ! array_key_exists( 'png-sharpen-thumbnails', $options ) ) {
				$options ['png-sharpen-thumbnails'] = Shared::png_sharpen_thumbnails_value_default();
			} else {
				$options ['png-sharpen-thumbnails'] = (int) $options ['png-sharpen-thumbnails'];
				if ( $options ['png-sharpen-thumbnails'] < Shared::sharpen_value_min() || $options ['png-sharpen-thumbnails'] > Shared::sharpen_value_max() ) {
					$options ['png-sharpen-thumbnails'] = Shared::png_sharpen_thumbnails_value_default();
				}
			}

			if ( ! array_key_exists( 'png-reduce-max-colors-count', $options ) ) {
				$options ['png-reduce-max-colors-count'] = Shared::png_max_colors_value_default();
			} else {
				$options ['png-reduce-max-colors-count'] = (int) $options ['png-reduce-max-colors-count'];
				if ( $options ['png-reduce-max-colors-count'] < Shared::png_max_colors_value_min() ) {
					$options ['png-reduce-max-colors-count'] = Shared::png_max_colors_value_default();
				}
				if ( $options ['png-reduce-max-colors-count'] > Shared::png_max_colors_value_max() ) {
					$options ['png-reduce-max-colors-count'] = Shared::png_max_colors_value_default();
				}
			}

			if ( ! array_key_exists( 'png-strip-meta', $options ) ) {
				$options ['png-strip-meta'] = Shared::png_strip_meta_default();
			} else {
				$options ['png-strip-meta'] = (int) $options ['png-strip-meta'];
				if ( ! array_key_exists( $options ['png-strip-meta'], $this->get_form_strip_metadata() ) ) {
					$options ['png-strip-meta'] = Shared::png_strip_meta_default();
				}
			}

			if ( ! array_key_exists( 'webp-compression-quality', $options ) ) {
				$options ['webp-compression-quality'] = Shared::webp_quality_default();
			} else {
				$options ['webp-compression-quality'] = (int) $options ['webp-compression-quality'];
				if ( $options ['webp-compression-quality'] < Shared::webp_quality_value_min() ) {
					$options ['webp-compression-quality'] = Shared::webp_quality_default();
				}
				if ( $options ['webp-compression-quality'] > Shared::webp_quality_value_max() ) {
					$options ['webp-compression-quality'] = Shared::webp_quality_default();
				}
			}

			if ( ! array_key_exists( 'webp-jpeg-compression-quality', $options ) ) {
				$options ['webp-jpeg-compression-quality'] = Shared::webp_jpeg_quality_default();
			} else {
				$options ['webp-jpeg-compression-quality'] = (int) $options ['webp-jpeg-compression-quality'];
				if ( ! array_key_exists( $options ['webp-jpeg-compression-quality'], $this->get_form_jpeg_to_webp_compression_quality() ) ) {
					$options ['webp-jpeg-compression-quality'] = Shared::webp_jpeg_quality_default();
				}
			}

			if ( ! array_key_exists( 'wp-big-image-size-threshold-value', $options ) ) {
				$options ['wp-big-image-size-threshold-value'] = Shared::big_image_size_threshold_value_default();
			} else {
				$options ['wp-big-image-size-threshold-value'] = (int) $options ['wp-big-image-size-threshold-value'];
				if ( $options ['wp-big-image-size-threshold-value'] < Shared::big_image_size_threshold_value_min() ) {
					$options ['wp-big-image-size-threshold-value'] = Shared::big_image_size_threshold_value_default();
				}
				if ( $options ['wp-big-image-size-threshold-value'] > Shared::big_image_size_threshold_value_max() ) {
					$options ['wp-big-image-size-threshold-value'] = Shared::big_image_size_threshold_value_default();
				}
			}

			$options ['image-max-width-backup'] = false;
			$options ['extra-size-same-width']  = false;

			$this->set_dynamic_menu_position( $options );

			self::set_cwebp_on_demand( $options['webp-cwebp-on-demand'] );

			delete_transient( $this->pageslug . '-update-notices' );

			return $options;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Generate Form Inputs / Validation Arrays

		/** Get possible JPEG Sharpen Image values for the form */
		public function get_form_sharpen_image() {
			$values = array();

			$values [0] = __( 'WP Default (unsharpMaskImage) *', 'warp-imagick' );

			$magic_test = false;
			if ( class_exists( '\\Imagick' ) ) {
				try {
					$magic_test = new \Imagick();
				} catch ( \Exception $e ) {
					return $values;
				}
			}

			if ( is_callable( array( $magic_test, 'sharpenImage' ) ) ) {
				$values [5]  = __( 'SharpenImage Sigma 0.5', 'warp-imagick' );
				$values [6]  = __( 'SharpenImage Sigma 0.6', 'warp-imagick' );
				$values [7]  = __( 'SharpenImage Sigma 0.7', 'warp-imagick' );
				$values [8]  = __( 'SharpenImage Sigma 0.8', 'warp-imagick' );
				$values [9]  = __( 'SharpenImage Sigma 0.9', 'warp-imagick' );
				$values [10] = __( 'SharpenImage Sigma 1.0', 'warp-imagick' );
				$values [11] = __( 'SharpenImage Sigma 1.1', 'warp-imagick' );
				$values [12] = __( 'SharpenImage Sigma 1.2', 'warp-imagick' );
				$values [13] = __( 'SharpenImage Sigma 1.3', 'warp-imagick' );
				$values [14] = __( 'SharpenImage Sigma 1.4', 'warp-imagick' );
				$values [15] = __( 'SharpenImage Sigma 1.5', 'warp-imagick' );
			}

			if ( $magic_test ) {
				$magic_test->clear();
				$magic_test->destroy();
				$magic_test = null;
			}

			return $values;
		}

		/** Get possible JPEG Sharpen Thumbnail values for the form */
		public function get_form_sharpen_thumbnails() {
			$values = array();

			$values [0] = __( 'WP Default (unsharpMaskImage) *', 'warp-imagick' );

			$magic_test = false;
			if ( class_exists( '\\Imagick' ) ) {
				try {
					$magic_test = new \Imagick();
				} catch ( \Exception $e ) {
					return $values;
				}
			}

			if ( is_callable( array( $magic_test, 'sharpenImage' ) ) ) {
				$values [5]  = __( 'SharpenImage Sigma 0.5', 'warp-imagick' );
				$values [6]  = __( 'SharpenImage Sigma 0.6', 'warp-imagick' );
				$values [7]  = __( 'SharpenImage Sigma 0.7', 'warp-imagick' );
				$values [8]  = __( 'SharpenImage Sigma 0.8', 'warp-imagick' );
				$values [9]  = __( 'SharpenImage Sigma 0.9', 'warp-imagick' );
				$values [10] = __( 'SharpenImage Sigma 1.0', 'warp-imagick' );
				$values [11] = __( 'SharpenImage Sigma 1.1', 'warp-imagick' );
				$values [12] = __( 'SharpenImage Sigma 1.2', 'warp-imagick' );
				$values [13] = __( 'SharpenImage Sigma 1.3', 'warp-imagick' );
				$values [14] = __( 'SharpenImage Sigma 1.4', 'warp-imagick' );
				$values [15] = __( 'SharpenImage Sigma 1.5', 'warp-imagick' );
			}

			if ( $magic_test ) {
				$magic_test->clear();
				$magic_test->destroy();
				$magic_test = null;
			}

			return $values;
		}

		/** Get possible JPEG compression types for the form */
		public function get_form_jpeg_compression_types() {
			$values = array();

			$values [0] = __( 'WordPress Default', 'warp-imagick' );

			if ( defined( '\\Imagick::COMPRESSION_JPEG' ) ) {
				$values [ \Imagick::COMPRESSION_JPEG ] = __( 'Imagick Default *', 'warp-imagick' );
			}

			return $values;
		}

		/** Get some available JPEG colorspaces for the form */
		public function get_form_jpeg_colorspaces() {
			$values = array();

			$values [0] = __( 'OFF (WP Default)', 'warp-imagick' );

			if ( defined( '\\Imagick::COLORSPACE_SRGB' ) ) {
				$values [ \Imagick::COLORSPACE_SRGB ] = __( 'sRGB *', 'warp-imagick' );
			}

			// phpcs:enable

			return $values;
		}

		/** Get sampling factor choices for the form */
		public function get_form_jpeg_sampling_factors() {
			$is_callable_set_image_property = false;

			$magic_test = false;
			if ( class_exists( '\\Imagick' ) ) {
				try {
					$magic_test = new \Imagick();
				} catch ( \Exception $e ) {
					return array(
						'' => 'ImageMagick Is Not Available',
					);
				}
			}

			if ( is_callable( array( $magic_test, 'setImageProperty' ) ) ) {
				$is_callable_set_image_property = true;
			}

			if ( $magic_test ) {
				$magic_test->clear();
				$magic_test->destroy();
				$magic_test = null;
			}

			if ( $is_callable_set_image_property ) {
				return array(
					''      => 'WordPress Default',
					'4:1:0' => '4:1:0 - Smallest file size',
					'4:1:1' => '4:1:1',
					'4:2:0' => '4:2:0 * Recommended by Google',
					'4:2:1' => '4:2:1',
					'4:2:2' => '4:2:2',
					'4:4:0' => '4:4:0',
					'4:4:1' => '4:4:1',
					'4:4:4' => '4:4:4 - Best color resolution',
				);

			} else {
				return array(
					'' => 'Not Available. ImageMagick is missing or below version 6.3.2',
				);
			}
		}

		/** Get strip metadata options for the form */
		public function get_form_strip_metadata() {
			$values = array();

			$values [0] = __( 'WordPress Default', 'warp-imagick' );
			$values [1] = __( 'Do Not Strip At All', 'warp-imagick' );
			$values [2] = __( 'Preserve Important', 'warp-imagick' );

			$magic_test = false;
			if ( class_exists( '\\Imagick' ) ) {
				try {
					$magic_test = new \Imagick();
				} catch ( \Exception $e ) {
					return $values;
				}
			}

			if ( is_callable( array( $magic_test, 'stripImage' ) ) ) {
				$values [3] = __( 'Strip All Metadata *', 'warp-imagick' );
			}

			if ( $magic_test ) {
				$magic_test->clear();
				$magic_test->destroy();
				$magic_test = null;
			}

			return $values;
		}

		/** Get available JPEG interlace types for the form */
		public function get_form_jpeg_interlace_types() {
			$values = array();

			$values [0] = __( 'WordPress Default', 'warp-imagick' );

			if ( defined( '\\Imagick::INTERLACE_JPEG' ) ) {
				$values [ \Imagick::INTERLACE_JPEG ] = __( 'Imagick Default', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::INTERLACE_NO' ) ) {
				$values [ \Imagick::INTERLACE_NO ] = __( 'No Interlace', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::INTERLACE_PLANE' ) ) {
				$values [ \Imagick::INTERLACE_PLANE ] = __( 'Progressive (PLANE)', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::INTERLACE_PLANE' ) && defined( '\\Imagick::INTERLACE_NO' ) ) {
				$values [-1] = __( 'Auto Select *', 'warp-imagick' );
			}

			return $values;
		}

		/** Get sampling factor choices for the form */
		public function get_form_jpeg_to_webp_compression_quality() {
			$values      = array();
			$values [-3] = __( 'Use JPEG quality -15% ', 'warp-imagick' );
			$values [-2] = __( 'Use JPEG quality -10%', 'warp-imagick' );
			$values [-1] = __( 'Use JPEG quality -5%', 'warp-imagick' );
			$values [0]  = __( 'Use JPEG quality *', 'warp-imagick' );
			$values [1]  = __( 'Use WebP quality', 'warp-imagick' );

			return $values;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Settings Extended

		/** Extended option fields.
		 * Fields that will be added/accepted by update options.
		 * Fields not defined in configuration.php.
		 * Fields with value modified or forced.
		 * With optional default values.
		 *
		 * Called on POST/Form and on Activation Success.
		 */
		protected function get_all_fields_extended() {
			return array(
				'configuration'  => array(),

				'plugin-version' => Shared::get_plugin_version(),
			);
		}

		/** On prepare settings page. Overridden. */
		protected function on_prepare_settings_page() {
			$actual_wp_version = get_bloginfo( 'version' );

			$tested_wp_version = '6.7.1';
			if ( version_compare( $actual_wp_version, $tested_wp_version, '<=' ) ) {
				return;

			}
			\add_action(
				'admin_notices',
				function () use ( $tested_wp_version, $actual_wp_version ) {
					$this->plugin->echo_admin_notice(
						// Translators: %s is tested and current WordPress version.
						sprintf( __( 'This version of plugin is tested up to WP %1$s (Now %2$s).', 'warp-imagick' ), $tested_wp_version, $actual_wp_version ),
						'notice notice-warning is-dismissible',
						true
					);
				}
			);
		}

		/** Hook admin error-notices when conflicting plugins detected. */
		public function check_conflicting_plugins() {
			$known_conflict_slugs = array(
				'wp-email-users',
				'ajax-search-lite',
				'circles-gallery',
			);

			$conflict_errors = array();

			$active_plugins = (array) get_option( 'active_plugins', array() );

			$conflicts_active = array();
			foreach ( $active_plugins as $active_plugin ) {
				$parts = explode( '/', $active_plugin );
				$slug  = reset( $parts );
				if ( in_array( $slug, $known_conflict_slugs, true ) ) {
					$conflicts_active [ $active_plugin ] = $slug;
				}
			}

			if ( empty( $conflicts_active ) ) {
				if ( get_transient( $this->pageslug . '-conflict-errors' ) ) {
					delete_transient( $this->pageslug . '-conflict-errors' );
				}
				return;
			}

			if ( ! \function_exists( '\\get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugins = \get_plugins();

			foreach ( $conflicts_active as $conflict_path => $conflict_slug ) {
				$conflict_pl_data = Lib::safe_key_value( $plugins, $conflict_path, array(), array() );

				$conflict_pl_name = Lib::safe_key_value( $conflict_pl_data, 'Name', '', 'WP Email Users' );
				$conflict_message = "Plugin ($conflict_slug) or Warp iMagick may not work properly when used along.";

				switch ( $conflict_slug ) {
					case 'wp-email-users':
						$conflict_pl_name = Lib::safe_key_value( $conflict_pl_data, 'Name', '', 'WP Email Users' );
						$conflict_message = "If you get error when saving Warp-iMagick plugin settings, please deactivate '$conflict_pl_name' plugin.";
						break;
					case 'ajax-search-lite':
						$conflict_pl_name = Lib::safe_key_value( $conflict_pl_data, 'Name', '', 'Ajax Search Lite' );
						$conflict_message = "Plugin '$conflict_pl_name' or Warp iMagick may not work properly when used along.";
						break;
					case 'circles-gallery':
						$conflict_pl_name = Lib::safe_key_value( $conflict_pl_data, 'Name', '', 'Circles Gallery' );
						$conflict_message = "Plugin '$conflict_pl_name' or Warp iMagick may not work properly when used along.";
						break;
					default:
						$conflict_pl_name = Lib::safe_key_value( $conflict_pl_data, 'Name', '', $conflict_slug );
						$conflict_message = "Plugin '$conflict_pl_name ($conflict_slug) or Warp iMagick, may not work properly when used along.";
						break;

				}

				$conflict_errors [] = array( 'error' => $conflict_message );
			}

			// phpcs:enable

			if ( ! empty( $conflict_errors ) ) {
				\set_transient( $this->pageslug . '-conflict-errors', $conflict_errors );
			}
		}

		/** Before render settings page. Overridden. */
		protected function on_render_settings_page() {
			$this->renderer->render_page_subtitle();
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Plugin Manager

		/** Overridden to implement custom code on plugin activation requirements
		 *
		 * At this point, plugin PHP/WP requirements are already checked, maybe invalid (if configured).
		 *
		 * @access protected
		 * @param bool  $networkwide flag.
		 * @param array $settings - Plugin Settings (configured activate requirements).
		 * @return array empty or containing (exit) [$message/s] to abort activation
		 */
		protected function on_check_activate_requirements( $networkwide, $settings ) {
			delete_option( $this->optionid . '-disabled' );

			$fail = array();

			$imagick_required = false;

			if ( class_exists( '\Imagick' ) ) {
				try {
					$imagick_required = new \Imagick();
				} catch ( \Exception $e ) {
					$fail[] = 'Exception thrown on creating PHP \\Imagick class: ' . $e->getMessage();
				}
			}

			if ( false === $imagick_required ) {
				$fail[] = 'PHP \\Imagick class does not exists.';
			} elseif ( ! $imagick_required instanceof \Imagick ) {
				$fail[] = 'PHP \\Imagick class is not available.';
			}

			if ( empty( $fail ) ) {
				$current_version = $imagick_required->getVersion();
				if ( is_array( $current_version ) ) {
					$module_version  = $current_version ['versionNumber'];
					$library_version = $current_version ['versionString'];

					/**
					 * --------------------------------------------------------------
					 * Imagick::setImageProperty requires ImageMagick 6.3.2 or newer.
					 * --------------------------------------------------------------
					 */
					$compare_version = '0.0.0';
					$partial_version = preg_split( '~[\s]+~', $library_version, 0, PREG_SPLIT_NO_EMPTY );
					foreach ( $partial_version as $part_version ) {
						if ( preg_match( '~^\d+\.\d+\.\d+~', $part_version ) ) {
							$compare_version = $part_version;
							break;

						}
					}
					if ( version_compare( $compare_version, '6.3.2', '<' ) ) {
						$fail[] = 'PHP Imagick module version is : ' . $module_version . '.';
						$fail[] = 'ImageMagick library version is : ' . $library_version . '.';
						$fail[] = 'This plugin is not compatible with ImageMagick library version below 6.3.2 or older.';
						$fail[] = 'Please ask your host service provider to upgrade ImageMagick library version to 6.3.2 or newer.';
					}
					if ( version_compare( $compare_version, '8', '>=' ) ) {
						$fail[] = 'PHP Imagick module version is : ' . $module_version . '.';
						$fail[] = 'ImageMagick library version is : ' . $library_version . '.';
						$fail[] = 'This plugin is not compatible nor tested with ImageMagick library version 8 or greater.';
						$fail[] = 'Please ask your host service provider to downgrade ImageMagick library to version lower than 8 (6.3.2+ or 7+).';
					}
				} elseif ( is_string( $current_version ) ) {
					$fail[] = 'ImageMagick library version is unknown.';
				} else {
					$fail[] = 'ImageMagick library version is not available.';
				}

				if ( ! is_callable( array( $imagick_required, 'setImageProperty' ) ) ) {
					$fail[] = 'Imagick method "setImageProperty" is not available.';
				}

				$constants = array(

					'\\Imagick::COMPRESSION_JPEG',
					'\\Imagick::COLORSPACE_SRGB',
					'\\Imagick::INTERLACE_PLANE',
					'\\Imagick::INTERLACE_JPEG',
				);
				foreach ( $constants as $constant ) {
					if ( ! defined( $constant ) ) {
						$fail [] = "Missing required PHP-Imagick constant '$constant'.";
					}
				}
			}

			if ( ! empty( $fail ) ) {
				$fail [] = 'Please read plugin FAQ and ask your host service provider for help to install/enable or upgrade PHP-imagick module linked with ImageMagick library version 6';
			}

			if ( empty( $fail ) ) {
				add_filter(
					'wp_image_editors',
					function ( $editors ) {
						$wp_editors = array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' );

						if ( class_exists( 'Warp_Image_Editor_Imagick' ) ) {
							$wp_editors = array( 'Warp_Image_Editor_Imagick', 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' );
						}

						foreach ( $editors as $editor ) {
							if ( ! in_array( $editor, $wp_editors, true ) ) {
								array_push( $wp_editors, $editor );
							}
						}
						return $wp_editors;
					},
					9999
				);

				if ( ! in_array( _wp_image_editor_choose(), array( 'WP_Image_Editor_Imagick', 'Warp_Image_Editor_Imagick' ), true ) ) {
					$fail[] = 'Default image editor is not Imagick Editor but: "' . _wp_image_editor_choose() . '".';
					$fail[] = 'Please check if other plugin has conflict with WordPress Imagick Editor.';
				}
				remove_all_filters( 'wp_image_editors', 9999 );
			}

			if ( $imagick_required instanceof \Imagick ) {
				try {
					$imagick_required->clear();
					$imagick_required->destroy();
					$imagick_required = null;

				} catch ( \Exception $e ) {
					sleep( 0 );

					$fail [] = $e->getMessage();
				}
			} elseif ( is_object( $imagick_required ) ) {
				try {
					is_callable( array( $imagick_required, '__destruct' ) ) && $imagick_required->__destruct();
					unset( $imagick_required );

				} catch ( \Exception $e ) {
					sleep( 0 );

					$fail [] = $e->getMessage();
				}
			}

			return $fail;
		}

		/** On plugin activation failed.
		 *
		 * Gracefully handle failure. Do not trigger_errore(), as parent method does by default.
		 *
		 * @access protected
		 * @param array $fail is an array of missing requirements - strings.
		 * @return void
		 */
		protected function on_activate_plugin_failure( $fail ) {
			if ( ! is_array( $fail ) ) {
				sleep( 0 );

				$fail = array( 'Unknown reason.' );
			}
			\update_option( $this->optionid . '-disabled', $fail, $autoload = true );

			\flush_rewrite_rules();

			self::set_cwebp_on_demand();

			self::on_manage_plugin( 'activate-failure' );
		}

		/** On plugin activation (success).
		 *
		 * Initialize (multi)site options.
		 *
		 * @access protected
		 * @param bool   $networkwide flag.
		 * @param object $user by which plugin has been manually activated.
		 * @return void
		 */
		protected function on_activate_plugin_success( $networkwide, $user = false ) {
			$that = $this->get_plugin();

			delete_option( $this->optionid . '-disabled' );

			delete_transient( $that->get_slug() . '-update-version' );

			self::clear_ui_state_user_settings( $that->get_slug() );

			$this->init_all_options( $networkwide );

			if ( is_object( $user ) ) {
				/** On manual activate: whatever required */
				$option_values = $this->get_option();

				// phpcs:enable
			}

			self::copy_test_images_to_test_dir( $that->get_path() . '/assets/clone-test', 'warp-imagick' );

			\flush_rewrite_rules();

			self::set_cwebp_on_demand( $this->get_option( 'webp-cwebp-on-demand', false ) );

			self::on_manage_plugin( 'activate-success' );
		}

		/** On Plugin deactivate, action handler
		 *
		 * @param bool $networkwide flag.
		 */
		protected function on_deactivate_plugin( $networkwide ) {
			\delete_option( $this->optionid . '-disabled' );

			\delete_transient( $this->pageslug . '-reactivate' );
			\delete_transient( $this->pageslug . '-reactivate-todo' );
			\delete_transient( $this->pageslug . '-conflict-errors' );

			self::clear_ui_state_user_settings( $this->pageslug );

			self::remove_test_images_from_site( $this->pageslug );

			\flush_rewrite_rules();

			self::set_cwebp_on_demand();

			self::on_manage_plugin( 'deactivate' );
		}

		/** On Plugin uninstall, action handler */
		protected static function on_uninstall_plugin() {
			$settings = self::instance();
			if ( $settings ) {
				if ( $settings->get_option( 'remove-settings', Shared::remove_plugin_settings_default() ) ) {
					self::remove_all_options( $settings->optionid );
				}

				if ( \is_multisite() ) {
					$sites = get_sites();
					foreach ( $sites as $site ) {
						\switch_to_blog( $site->blog_id );

						\delete_option( $settings->optionid . '-disabled' );

						\delete_transient( $settings->pageslug . '-reactivate' );
						\delete_transient( $settings->pageslug . '-reactivate-todo' );
						\delete_transient( $settings->pageslug . '-conflict-errors' );
						\delete_transient( $settings->pageslug . '-update-version' );
						\delete_transient( $settings->pageslug . '-update-notices' );

						self::clear_ui_state_user_settings( $settings->pageslug );

						\delete_transient( $settings->pageslug . '-obsolete-usermeta-cleared' );

						\delete_transient( Lib::get_namespace() . '-notices' );

						\flush_rewrite_rules();

						self::set_cwebp_on_demand();
					}
					\restore_current_blog();
				} else {
					\delete_option( $settings->optionid . '-disabled' );

					\delete_transient( $settings->pageslug . '-reactivate' );
					\delete_transient( $settings->pageslug . '-reactivate-todo' );
					\delete_transient( $settings->pageslug . '-conflict-errors' );

					self::clear_ui_state_user_settings( $settings->pageslug );

					\delete_transient( $settings->pageslug . '-obsolete-usermeta-cleared' );

					\flush_rewrite_rules();

					self::set_cwebp_on_demand();
				}

				self::remove_test_images_from_site( $settings->pageslug );
			}

			\flush_rewrite_rules();

			self::set_cwebp_on_demand();

			self::on_manage_plugin( 'uninstall' );
		}

		/** Clear plugin settings-page ui-state user-settings.
		 *
		 * @param string $pageslug page slug.
		 */
		protected static function clear_ui_state_user_settings( $pageslug ) {
			/** Force clear/remove user's settings page ui-state cookie.
			 *
			 * Not really needed to clear the cookie because it is a session cookie
			 * which will anyways expire at the end of current browser session,
			 * and won't be set again after deactivation and/or uninstall.
			 */
			unset( $_COOKIE[ $pageslug ] );
			/** Since 1.10.4: check headers_sent() as PHP >= 8.0 may report fatal error. */
			if ( ! headers_sent() ) {
				setcookie( $pageslug, ' ', time() - YEAR_IN_SECONDS );

			}

			/** Clear obsolete wp_usermeta, set by user using settings-page of older versions of this plugin.
			 *
			 * Remove settings-page ui-state keys
			 * from 'wp_user-settings' key in 'wp_usermeta' table.
			 */

			if ( \get_transient( $pageslug . '-obsolete-usermeta-cleared' ) ) {
				return;
			}

			$user_settings_keys_to_remove = ( array(
				'warpimagicksections',
				'warpimagicktabindex',
				'warp-imagick-sections',
				'warp-imagick-tabindex',
			) );

			global $wpdb;

			// phpcs:ignore
			$user_ids_to_clear_ui_settings = $wpdb->get_col( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'wp_user-settings'" );

			foreach ( $user_ids_to_clear_ui_settings as $user_id ) {
				if ( ! $user_id ) {
					continue;
				}

				$option = get_user_option( 'user-settings', $user_id );
				if ( $option && is_string( $option ) ) {
					parse_str( $option, $user_settings );
				}

				$update_user_settings  = false;
				$updated_user_settings = array();

				foreach ( $user_settings as $key => $val ) {
					if ( in_array( $key, $user_settings_keys_to_remove, true ) ) {
						$update_user_settings = true;
					} else {
						$updated_user_settings [ $key ] = $val;
					}
				}

				if ( true === $update_user_settings ) {
					$settings = '';
					foreach ( $updated_user_settings as $name => $value ) {
						$_name  = preg_replace( '/[^A-Za-z0-9_-]+/', '', $name );
						$_value = preg_replace( '/[^A-Za-z0-9_-]+/', '', $value );

						if ( ! empty( $_name ) ) {
							$settings .= $_name . '=' . $_value . '&';
						}
					}

					$settings = rtrim( $settings, '&' );

					update_user_option( $user_id, 'user-settings', $settings, false );
					update_user_option( $user_id, 'user-settings-time', time(), false );

				}
			}

			\set_transient( $pageslug . '-obsolete-usermeta-cleared', true );
		}

		/** Copy "clone redirect test" images to site test directory.
		 *
		 * @param string $source directory to copy from.
		 * @param string $subdir to copy to wp-content/uploads/$subdir.
		 */
		private static function copy_test_images_to_test_dir( $source, $subdir ) {
			if ( ! is_dir( $source ) ) {
				return;
			}

			$source_dir = $source;
			$target_dir = path_join( wp_upload_dir()['basedir'], $subdir );

			if ( ! is_dir( $target_dir ) ) {
				wp_mkdir_p( $target_dir );
				if ( ! is_dir( $target_dir ) ) {
					return;
				}
			}

			WP_Filesystem();

			ob_start();

			$error = copy_dir( $source_dir, $target_dir );
			ob_end_clean();

			if ( is_wp_error( $error ) ) {
				sleep( 0 );

			}

			// phpcs:enable
		}

		/** Remove "clone redirect test" images from site test directory
		 *
		 * @param string $subdir in wp-content/uploads/$subdir.
		 */
		private static function remove_test_images_from_site( $subdir ) {
			$target_dir = path_join( wp_upload_dir()['basedir'], $subdir );

			if ( ! is_dir( $target_dir ) ) {
				return;
			}

			WP_Filesystem();

			global $wp_filesystem;
			$wp_filesystem->rmdir(
				$target_dir,
				$recursive = true
			);
		}

		/** Enable or Disable WebP On Demand via server flag.
		 * Set (insert/create or remove/delete/unlink) server redirect file-flag in home path.
		 *
		 * @param bool $set Server File Flag: create (true) or unlink (!true/false/omit-arg).
		 */
		private static function set_cwebp_on_demand( $set = false ) {
			/** CWEBP: Code is missing here. */

			// phpcs:enable
		}

		// phpcs:ignore
	# endregion
	}

} else {
	Shared::debug( "Class already exists: $class" );
}
