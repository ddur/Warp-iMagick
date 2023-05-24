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

namespace ddur\Warp_iMagick;

defined( 'ABSPATH' ) || die( -1 );

use \ddur\Warp_iMagick\Base\Plugin\v1\Lib;
use \ddur\Warp_iMagick\Base\Meta_Plugin;
use \ddur\Warp_iMagick\Shared;

if ( ! class_exists( __NAMESPACE__ . '\\Plugin' ) ) {

	/** Plugin class */
	class Plugin extends Meta_Plugin {

		// phpcs:ignore
	# region Main Properties

		/** Plugin Disabled
		 *
		 * Is plugin disabled due failed requirements?
		 *
		 * @var bool|array $my_is_disabled false or array of strings - requirements failed.
		 */
		private $my_is_disabled = false;

		/** Mime Type.
		 *
		 * Stores mime type.
		 *
		 * @var string $my_mime_type stored.
		 */
		private $my_mime_type = '';

		/** Can Do Generate WebP.
		 *
		 * @var bool $my_can_do_webp property.
		 */
		private $my_can_do_webp = null;

		/** Advanced Intermediate Metadata.
		 *
		 * Metadata contains failure or success of the
		 * 'intermediate_image_sizes_advanced' filter as
		 * used in 'wp_generate_attachment_metadata' filter.
		 *
		 * @var bool|array $my_metadata_done value.
		 */
		private $my_metadata_done = false;

		/** Generating Intermediate Metadata.
		 *
		 * @var bool $my_generate_meta flag.
		 */
		private $my_generate_meta = false;

		/** Internal Updating Intermediate Metadata.
		 *
		 * @var bool $my_updating_meta flag.
		 */
		private $my_updating_meta = false;


		/** Current metadata state of the attachment image.
		 * Possible states:
		 * "" (default, unset)
		 * "edited" (user-edited)
		 * "scaled" (BIS - resized & compressed & maybe 'rotated')
		 * "rotated" (but not compressed)
		 * "lossless" (PNG, not JPEG)
		 * "lossy" (JPEG to compress)
		 * "unknown" (error?)
		 *
		 * @var string $my_image_state detected.
		 */
		private $my_image_state = '';

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Handle active plugin upgrade & reactivation.

		/** Upgrade handler. */
		private function plugin_upgrade_handler() {

			if ( get_transient( $this->get_slug() . '-reactivate' ) ) {

				delete_transient( $this->get_slug() . '-reactivate' );
				set_transient( $this->get_slug() . '-reactivate-todo', true, 7 * DAY_IN_SECONDS );
				require_once __DIR__ . '/class-settings.php';
				$that = Settings::once( $this );
				if ( is_callable( array( $that, 'on_abstract_activate_plugin' ) ) ) {
					$that->on_abstract_activate_plugin( \is_multisite() );
					delete_transient( $this->get_slug() . '-reactivate-todo' );
				} else {
					Lib::debug( 'Function "on_abstract_activate_plugin" is not callable.' );
				}
			} else {

				add_filter(
					'upgrader_post_install',
					function( $success = false, $hook_extra = array(), $result = array() ) {

						if ( $success ) {
							if ( 'plugin' === Lib::safe_key_value( $hook_extra, 'type', '', false ) ) {
								if ( $this->get_slug() === Lib::safe_key_value( $result, 'destination_name', '', false ) ) {

									set_transient( $this->get_slug() . '-reactivate', true, 5 * MINUTE_IN_SECONDS );
								}
							}
						}
						return $success;
					},
					10,
					3
				);

				add_action(
					'upgrader_process_complete',
					function( $upgrader_instance = null, $hook_extra = array() ) {

						if ( 'plugin' === Lib::safe_key_value( $hook_extra, 'type', '', false ) ) {
							if ( $this->get_slug() === Lib::safe_key_value( (array) $upgrader_instance, array( 'result', 'destination_name' ), '', false ) ) {

								set_transient( $this->get_slug() . '-reactivate', true, 5 * MINUTE_IN_SECONDS );
							}
						}
						return;
					},
					10,
					2
				);
			}
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Upload/Regenerate Detection Properties and Flags.

		/** Get and store wp_upload_dir() ['basedir'] result once.
		 *
		 * Set by <init> action handler.
		 *
		 * @var array $my_wp_upload_basedir result.
		 */
		private $my_wp_upload_basedir = '';

		/** Is this Upload Request?
		 *
		 * Set by <wp_handle_upload> filter handler.
		 *
		 * @var bool $my_is_upload flag.
		 */
		private $my_is_upload = false;

		/** Upload Data Stored
		 *
		 * Set by <wp_handle_upload> filter handler.
		 *
		 * Contains empty array or array(
		 *  'file' => $new_file, # file path
		 *  'url'  => $url,      # file url
		 *  'type' => $type,     # file mime type
		 * )
		 *
		 * @var array $my_upload_args stored.
		 */
		private $my_upload_args = array();

		/** Last Upload Action.
		 *
		 * Set by <wp_handle_upload> filter handler.
		 *
		 * Contains empty string or 'upload' or 'sideload'
		 *
		 * @var string $my_upload_action 'upload'/'sideload' stored.
		 */
		private $my_upload_action = '';

		/** Is this Regenerate of image uploaded prior this plugin version (v2 and up) installed?
		 *
		 * Set by <intermediate_image_sizes_advanced> filter handler.
		 * Detected by lack of metadata set/backed-up by this plugin (v2 and up)?
		 *
		 * @var bool $my_is_conversion flag.
		 */
		private $my_is_conversion = false;


		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Detect `wp_create_image_subsizes` arguments and saved metadata on regenerate.

		/** `wp_create_image_subsizes( $file , _ )
		 *
		 * @var string $my_gen_file_path '$file' argument.
		 */
		private $my_gen_file_path = '';

		/** `wp_create_image_subsizes( _ , $attachment_id )
		 *
		 * @var int $my_gen_attach_id '$attachment_id' argument.
		 */
		private $my_gen_attach_id = 0;

		/** `wp_create_image_subsizes` when metadate cleared.
		 *
		 * @var string $my_save_metadata existing attachment_id-metadata saved on regenerate.
		 */
		private $my_save_metadata = array();

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Upload vs Regenerate Detection & handling Methods.

		/** On wp_handle_upload filter.
		 *
		 * Catch & flag upload success event.
		 * Use flag to find out whether plugin is compressing
		 * images for 'upload' or for 'regenerate' request.
		 *
		 * @param array  $args - upload hook arguments.
		 * @param string $action 'sideload'/'upload'.
		 *
		 * @return array $args.
		 */
		public function on_wp_handle_upload_filter( $args, $action ) {

			if ( 0 === strpos( $args ['type'], 'image/' ) ) {

				$this->my_is_upload     = true;
				$this->my_upload_args   = $args;
				$this->my_upload_action = $action;

				switch ( $args ['type'] ) {
					case 'image/jpeg':
					case 'image/png':
						$max_image_width  = $this->get_max_image_width();
						$source_file_path = isset( $args ['file'] ) && \file_exists( $args ['file'] ) ? $args ['file'] : false;
						if ( $max_image_width && $source_file_path ) {
							if ( false !== Shared::check_resize_image_width( $source_file_path, $source_file_path, $max_image_width ) ) {
								Lib::debug( 'Resized on upload.' );
							}
						}
						break;

					default:
						break;
				}
			}
			return $args;
		}

		/**
		 * In case Performance Lab webp-uploads module is active.
		 * Remove all transforms or return invalid transforms
		 */
		public function on_webp_uploads_upload_image_mime_transforms_99_filter() {
			return array();
		}

		/**
		 * In case Performance Lab webp-uploads module changed code handling of above filter.
		 */
		public function on_webp_uploads_pre_generate_additional_image_source_99_filter() {
			$msg = __( 'Transforming mime-types is not allowed.', 'warp-imagick' );
			return new \WP_Error( 'warp-imagick ', $msg );
		}

		/**
		 * In case Performance Lab webp-uploads module changed code handling of above filter(s).
		 */
		public function on_webp_uploads_content_image_mimes_99_filter() {
			return array();
		}

		/** WP5.3+ Get wp_create_image_subsizes ( args ) & backup attachment metadata.
		 * Just before attachment metadata is cleared/destroyed.
		 *
		 * *** From /wp-includes/post.php ***
		 * Filters the updated attachment meta data.
		 *
		 * @since 2.1.0
		 *
		 * @param array $metadata      Array of updated attachment meta data.
		 * @param int   $attachment_id Attachment post ID (optional).
		 */
		public function save_attachment_metadata_backup( $metadata, $attachment_id = 0 ) {

			if ( $this->my_updating_meta ) {

				return $metadata;
			}

			if ( 0 === $attachment_id ) {

				Lib::debug( 'No $attachment_id arg provided.' );
				return $metadata;
			}

			if ( ! self::is_valid_metadata( $metadata ) ) {
				Lib::debug( 'Invalid $metadata - $attachment_id: ' . $attachment_id );
				return $metadata;
			}

			if ( ! is_string( $metadata['file'] )
			|| empty( $metadata['file'] ) ) {
				Lib::debug( 'Invalid $metadata[file] - $attachment_id: ' . $attachment_id );
				return $metadata;
			}

			// phpcs:ignore
			$trace_stack = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );
			$calls_stack = array();

			foreach ( array_reverse( $trace_stack ) as $item ) {
				if ( isset( $item ['function'] ) ) {
					$calls_stack[] = $item ['function'];
				}
			}

			$trace_stack = array();

			$scope = in_array( 'wp_create_image_subsizes', $calls_stack, true );

			if ( true === $scope ) {
				Lib::debug( 'Metadata update within "wp_create_image_subsizes" scope - $attachment_id: ' . $attachment_id );
			} else {
				Lib::debug( 'Metadata update out of "wp_create_image_subsizes" scope - $attachment_id: ' . $attachment_id );
			}

			Lib::debug_var( $calls_stack, 'Calls:' );
			Lib::debug_var( $metadata, '$metadata:' );

			if ( true !== $scope ) {
				return $metadata;
			}

			if ( $this->my_gen_attach_id !== $attachment_id ) {

				Lib::debug( "Attachment ($attachment_id) metadata change detected" );

				$this->my_gen_attach_id = $attachment_id;
				$this->my_gen_file_path = $this->get_absolute_upload_file_path( $metadata['file'] );

				$this->my_save_metadata = array();

				$old_metadata = get_post_meta( $attachment_id, '_wp_attachment_metadata', $single = true );

				if ( isset( $old_metadata['sizes'] ) && count( $old_metadata['sizes'] ) ) {
					Lib::debug( 'Save metadata backup - $attachment_id: ' . $attachment_id );
					$this->store_attachment_metadata( $attachment_id );
				} else {

					Lib::debug( 'Skip metadata backup [empty-sizes] - $attachment_id: ' . $attachment_id );
				}
			} else {

				Lib::debug( 'Skip metadata backup [no-overwrite] - $attachment_id: ' . $attachment_id );
			}

			return $metadata;
		}

		/** Store attachment metadata.
		 *
		 * @param int $attachment_id Attachment post ID.
		 */
		public function store_attachment_metadata( $attachment_id ) {

			$this->my_save_metadata['_wp_attachment_metadata']     = get_post_meta( $attachment_id, '_wp_attachment_metadata', $single = true );
			$this->my_save_metadata['_wp_attached_file']           = get_post_meta( $attachment_id, '_wp_attached_file', $single = true );
			$this->my_save_metadata['_wp_attachment_backup_sizes'] = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', $single = true );
		}

		/** Restore attachment metadata.
		 *
		 * @param int $attachment_id Attachment post ID.
		 */
		public function restore_attachment_metadata( $attachment_id ) {
			update_post_meta( $attachment_id, '_wp_attachment_metadata', $this->my_save_metadata['_wp_attachment_metadata'] );
			update_post_meta( $attachment_id, '_wp_attached_file', $this->my_save_metadata['_wp_attached_file'] );
			if ( $this->my_save_metadata['_wp_attachment_backup_sizes'] ) {
				update_post_meta( $attachment_id, '_wp_attachment_backup_sizes', $this->my_save_metadata['_wp_attachment_backup_sizes'] );
			} else {
				delete_post_meta( $attachment_id, '_wp_attachment_backup_sizes' );
			}
		}

		/** Set/cache current upload directory/files as absolute path.
		 *  Returns empty string if file is not found to exists.
		 */
		private function set_my_wp_upload_basedir() {
			$upload_dir = wp_upload_dir();
			$base_dir   = isset( $upload_dir['basedir'] )
			&& is_string( $upload_dir['basedir'] ) ?
			$upload_dir['basedir'] : '';
			if ( is_dir( $base_dir ) ) {
				$this->my_wp_upload_basedir = $base_dir;
			} else {
				wp_die( esc_html( 'Upload directory doesn\'t exists: ' . $base_dir ) );
			}
		}

		/** Get upload file name as absolute path.
		 *  Returns empty string if file is not found to exists.
		 *
		 * @param string $arg_upload_file_path - absolute or [UPLOAD] relative upload file name.
		 * @param bool   $arg_file_exists - check if file exists.
		 *
		 * @return string absolute upload file name or empty string when file does not exists.
		 */
		public function get_absolute_upload_file_path( $arg_upload_file_path, $arg_file_exists = false ) {
			if ( ! is_string( $arg_upload_file_path ) || empty( $arg_upload_file_path ) ) {
				return '';
			}
			if ( empty( $this->my_wp_upload_basedir ) ) {
				$this->set_my_wp_upload_basedir();
			}

			$arg_upload_file_path = \path_join( $this->my_wp_upload_basedir, $arg_upload_file_path );

			return true === $arg_file_exists ? Shared::normalize_path_name( $arg_upload_file_path ) : $arg_upload_file_path;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Plugin Class Initialization.

		/** Plugin init. Called immediately after plugin class is constructed. */
		protected function init() {

			\add_action( 'init', array( $this, 'handle_wordpress_init' ) );

			if ( is_admin() ) {
				$this->load_textdomain();

				require_once __DIR__ . '/class-settings.php';
				$that = Settings::once( $this );
				if ( is_callable( array( $that, 'check_conflicting_plugins' ) ) ) {
					$that->check_conflicting_plugins();
				} else {
					Lib::debug( 'Function "check_conflicting_plugins" is not callable.' );
				}
			}

		}

		/** WordPress Init */
		public function handle_wordpress_init() {

			$this->plugin_upgrade_handler();
			$this->plugin_upgrade_checker();
			$this->convert_webp_on_demand();
			$this->disable_perflab_upload();

			/** Check if plugin activation requirements failed?
			 *
			 * Property $this->my_is_disabled will
			 * be set to false or array of strings.
			 */
			$this->set_disabled( get_option( $this->get_option_id() . '-disabled', false ) );

			\add_action( 'admin_notices', array( $this, 'handle_admin_notices' ) );

			if ( $this->is_disabled() ) {
				return;
			}

			\add_action(
				'wp_update_attachment_metadata',
				array( $this, 'save_attachment_metadata_backup' ),
				$priority      = 0,
				$accepted_args = 2
			);

			if ( class_exists( '\\RegenerateThumbnails' ) ) {

				\add_action( 'regenerate_thumbnails_options_onlymissingthumbnails', '__return_false' );

				\add_action(
					'admin_head',
					function() {
						?>
<style type="text/css">#regenthumbs-regenopt-onlymissing{display:none!important}</style>
<style type="text/css">#regenerate-thumbnails-app div:first-child p:nth-of-type(3) label{display:none!important}</style>
<style type="text/css">#regenerate-thumbnails-app div:first-child div:first-child p:nth-of-type(2) label{display:none!important}</style>
						<?php
					}
				);
			}

			/** User has access to 'upload_files' ? */
			if ( $this->get_current_user_can( 'upload_files' )
			|| Lib::is_wp_cli() ) {
				$this->add_preview_thumbnails_template();
				$this->set_my_wp_upload_basedir();
				Lib::auto_hook( $this );
			}
		}

		/** On admin notices action */
		public function handle_admin_notices() {

			if ( $this->is_disabled() ) {
				$counter = 0;
				$reasons = $this->why_is_disabled();
				foreach ( $reasons as $message ) {
					if ( is_string( $message ) && trim( $message ) ) {
						self::echo_error_notice( $message );
						$counter++;
					}
				}
				$plugin_name = $this->get_slug();
				$s_in_plural = 1 < $counter ? 's' : '';
				if ( 0 === $counter ) {
					self::echo_error_notice( "Plugin '$plugin_name': is DISABLED, no reason given." );
				} else {
					self::echo_error_notice( "Plugin '$plugin_name' is DISABLED due to activation failure$s_in_plural given above." );
				}
				self::echo_error_notice( "Please deactivate '$plugin_name' plugin and activate again when your site meets missing requirement$s_in_plural." );
			}

			$update_notices = \get_transient( $this->get_slug() . '-update-notices' );
			if ( is_array( $update_notices ) ) {
				foreach ( $update_notices as $update_notice ) {
					foreach ( $update_notice as $type => $message ) {
						switch ( $type ) {
							case 'error':
								self::echo_error_notice( $message );
								break;
							default:
								self::echo_admin_notice( $message );
								break;
						}
					}
				}
			}

			$conflict_errors = \get_transient( $this->get_slug() . '-conflict-errors' );
			delete_transient( $this->get_slug() . '-conflict-errors' );
			if ( ! empty( $conflict_errors ) && is_array( $conflict_errors ) ) {
				foreach ( $conflict_errors as $conflict_error ) {
					foreach ( $conflict_error as $type => $message ) {
						switch ( $type ) {
							case 'error':
								self::echo_error_notice( $message );
								break;
							default:
								self::echo_admin_notice( $message );
								break;
						}
					}
				}
			}
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Image Upload & Regenerate Hooks

		/** On 'big_image_size_threshold' filter
		 *
		 * Priority is high/late to override other filters.
		 *
		 * @param int $threshold is value in pixels. Default 2560.
		 */
		public function on_big_image_size_threshold_99_filter( $threshold ) {

			if ( true === $this->get_option( 'wp-big-image-size-threshold-disabled', Shared::big_image_size_threshold_disabled_default() ) ) {
				$threshold = 0;
			} else {
				$threshold = (int) $this->get_option( 'wp-big-image-size-threshold-value', Shared::big_image_size_threshold_value_default() );
			}
			return $threshold;
		}

		/** On 'wp_image_maybe_exif_rotate' filter
		 *
		 * Priority is high/late to override other filters.
		 *
		 * @param int $orientation is exif image orientation.
		 */
		public function on_wp_image_maybe_exif_rotate_99_filter( $orientation ) {

			if ( true === $this->get_option( 'wp-maybe-exif-rotate-disabled', Shared::maybe_exif_rotate_disabled_default() ) ) {
				$orientation = false;
			}
			return $orientation;
		}
		/** On 'wp_image_editors' filter.
		 *
		 * Prepend Warp iMagick editor class to editors list.
		 *
		 * @param array $editors - image editors.
		 */
		public function on_wp_image_editors_99_filter( $editors ) {

			require_once __DIR__ . '/class-warp-image-editor-imagick.php';

			/** If default/original WordPress editors (classes) are missing,
			 * restore WP original classes as the last values in the array.
			 *
			 * Modified Since 1.10.2
			 * DONE: Performance Lab renames WP editors, so add WP classes at the end!
			 */
			$wp_editors = array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' );
			foreach ( array_reverse( $wp_editors ) as $wp_editor ) {
				if ( ! in_array( $wp_editor, $editors, true ) ) {
					$editors = array_merge( $editors, array( $wp_editor ) );
				}
			}

			if ( 'Warp_Image_Editor_Imagick' !== reset( $editors ) ) {

				if ( in_array( 'Warp_Image_Editor_Imagick', $editors, true ) ) {

					$filtered = array();
					foreach ( $editors as $editor ) {
						if ( 'Warp_Image_Editor_Imagick' === $editor ) {
							continue;
						}
						$filtered[] = $editor;
					}
					$editors = $filtered;
				}

				/** Prepend Warp_Image_Editor_Imagick as first/default priority editor. */
				$editors = array_merge( array( 'Warp_Image_Editor_Imagick' ), $editors );

			}

			return $editors;
		}

		/** On intermediate_image_sizes_advanced filter.
		 *
		 * Because compression settings may change, all thumbnail sizes should be regenerated.
		 * RT plugin 'Skip regenerating existing correctly sized thumbnails (faster).' is forced to off.
		 *
		 * @param array $sizes - New sizes to generate.
		 * @param array $metadata - Initialized $metadata.
		 * @param int   $attachment_id - attachment ID.
		 */
		public function on_intermediate_image_sizes_advanced_99_filter( $sizes, $metadata = false, $attachment_id = false ) {

			/** Since 1.1.12, only WP version >= 5.3 supported */
			if ( ! \function_exists( '\\wp_get_original_image_path' ) ) {
				Lib::error( 'WordPress versions below 5.3 are not supported' );
				return $sizes;
			}

			if ( ! is_array( $sizes ) ) {
				/**
				 * Not an error because this may be a valid filter call.
				 * See: wp-admin/includes/ajax-actions.php:3901!
				 */
				Lib::debug( '$sizes is not an array.' );

				return $sizes;
			}

			if ( ! is_array( $metadata ) ) {
				/**
				 * Not an error because this may be a valid filter call.
				 * See: wp-admin/includes/ajax-actions.php:3901!
				 */
				Lib::debug( '$metadata is not an array.' );
				return $sizes;
			}

			if ( false === $attachment_id ) {
				/** Required parameter $attachment_id is omitted (or has false value).
				 *
				 * ATTN: This filter is called directly, out of WordPress context,
				 * from WP-CLI media regenerate private function get_intermediate_sizes( $is_pdf, $metadata ),
				 * without here required @param $attachment_id, which hopefully wont be changed soon or ever,
				 * because WP-CLI media regenerate should work with WordPress versions less than 5.3.
				 */
				Lib::debug( 'Argument $attachment_id === false' );
				return $sizes;
			}

			if ( ! is_int( $attachment_id ) || 0 >= $attachment_id ) {
				/** Required parameter $attachment_id is not an integer with value greater than 0 (zero). */
				Lib::debug( 'Argument $attachment_id has invalid type or value' );
				return $sizes;
			}

			/** Check wp_create_image_subsizes( _, $attachment_id ) argument
			 * is equal to previously caught $this->my_gen_attach_id.
			 *
			 * WP-CLI 2.7.1 (> 2.5.0) media regenerate
			 * may call this filter twice. Once with previous attachment ID,
			 * just before calling wp_create_image_subsizes with next attachment ID.
			 *
			 * WP-CLI 2.7.1 (> 2.5.0) media regenerate
			 * may call this filter twice. Once with previous attachment ID,
			 * just before calling wp_create_image_subsizes with next attachment ID.
			 */
			if ( $this->my_gen_attach_id !== $attachment_id ) {
				if ( Lib::is_wp_cli() ) {
					Lib::debug( 'WP CLI (media regenerate?)' );
				}
				Lib::debug( 'Calling "intermediate_image_sizes_advanced" outside of function scope: wp_create_image_subsizes( _, $attachment_id ) is ' . $attachment_id . ', expected: ' . $this->my_gen_attach_id . ' !' );
				return $sizes;
			}

			if ( ! array_key_exists( 'file', $metadata ) ) {
				Lib::debug( 'Image [file] is missing from $metadata.' );
				return $sizes;
			}

			if ( ! array_key_exists( 'width', $metadata ) ) {
				Lib::debug( 'Image [width] is missing from $metadata.' );
				return $sizes;
			}

			if ( ! array_key_exists( 'height', $metadata ) ) {
				Lib::debug( 'Image [height] is missing from $metadata.' );
				return $sizes;
			}

			if ( ! array_key_exists( 'sizes', $metadata ) || ! is_array( $metadata['sizes'] ) ) {
				Lib::debug( 'Image [sizes] is missing from $metadata.' );
				return $sizes;
			}

			$new_file_path = $this->get_absolute_upload_file_path( $metadata['file'] );
			if ( ! file_exists( $new_file_path ) ) {
				Lib::debug( 'File ' . $metadata['file'] . ' not found in upload directory.' );
				return $sizes;
			}

			$new_orig_path = false;
			if ( ! empty( $metadata['original_image'] ) ) {
				$new_orig_path = \path_join( dirname( $new_file_path ), $metadata['original_image'] );
				$new_orig_path = \file_exists( $new_orig_path ) ? $new_orig_path : false;
			}

			/** Check if wp_create_image_subsizes( $file, _ ) argument
			 * is equal to [file] or [original_image].
			 */
			switch ( $this->my_gen_file_path ) {
				case $new_file_path:
				case $new_orig_path:
					Lib::debug( 'Regenerate wp_create_image_subsizes( $file, _ ) does match [file] or [original_image]' );
					break;
				default:
					Lib::debug( 'Regenerate wp_create_image_subsizes( $file, _ ) doesn\'t match [file] or [original_image]' );

					return $sizes;
			}

			$attachment = get_post( $attachment_id );
			if ( ! is_a( $attachment, '\\WP_Post' ) ) {
				Lib::debug( 'Invalid attachment ID (can\'t get WP_Post).' );
				return $sizes;
			}

			if ( 'attachment' !== $attachment->post_type ) {
				Lib::debug( 'Invalid attached post-type (not an \'attachment\' but \'' . $attachment->post_type . '\').' );
				return $sizes;
			}

			$post_mime_type = $attachment->post_mime_type;
			if ( empty( $post_mime_type ) || 0 !== strpos( $post_mime_type, 'image/' ) ) {
				Lib::debug( 'Attachment mime-type is not image mime-type: ' . $post_mime_type . '.' );
				return $sizes;
			}

			$image_mime_type = wp_check_filetype( $metadata['file'] );
			$image_mime_type = $image_mime_type['type'];

			if ( $post_mime_type !== $image_mime_type ) {
				Lib::error( 'Post mime-type doesn\'t match main/attached image mime-type :' . $post_mime_type . '.' );
				return $sizes;
			}

			switch ( $image_mime_type ) {
				case 'image/jpeg':
				case 'image/png':
					$this->my_mime_type = $image_mime_type;
					break;
				default:
					Lib::debug( 'Post mime-type is not equal to JPEG or PNG image type:' . $post_mime_type . '.' );
					return $sizes;
			}

			$this->my_metadata_done = false;
			$this->my_generate_meta = false;

			Lib::debug_var( $sizes, 'argument $sizes' );

			$this->my_image_state = '';

			$old_metadata = Lib::safe_key_value( $this->my_save_metadata, '_wp_attachment_metadata', array(), false );
			$old_attached = Lib::safe_key_value( $this->my_save_metadata, '_wp_attached_file', '', false );
			$backup_sizes = Lib::safe_key_value( $this->my_save_metadata, '_wp_attachment_backup_sizes', array(), false );

			$is_regenerate = false !== $old_metadata;
			if ( Lib::is_debug() && $is_regenerate === $this->my_is_upload ) {
				Lib::debug( "is_regenerate: $is_regenerate, is_upload: $this->my_is_upload." );
			}

			$old_file_name = false;
			$old_file_path = false;
			$old_orig_name = false;
			$old_orig_path = false;

			$thumbs_source = false;
			$edited_source = false;

			$tracked_files = array();
			$tracked_saved = get_post_meta( $attachment_id, '_warp_imagick_files', $single = true );
			if ( is_array( $tracked_saved ) ) {
				$tracked_files = $tracked_saved;
			} else {
				update_post_meta( $attachment_id, '_warp_imagick_files', $tracked_files );
			}

			if ( ! empty( $metadata['file'] ) ) {
				$tracked_files[ wp_basename( $metadata['file'] ) ] = null;
			}
			if ( ! empty( $metadata['original_image'] ) ) {
				$tracked_files[ wp_basename( $metadata['original_image'] ) ] = null;
			}

			if ( $is_regenerate ) {

				if ( empty( $old_metadata ) ) {
					Lib::debug_var( $this->my_save_metadata, '$this->my_save_metadata' );
					Lib::debug_var( $old_metadata, '$old_metadata' );
					Lib::error( 'Old metadata is not saved on regenerate!' );
					return $sizes;
				}

				$old_file_name = Lib::safe_key_value( $old_metadata, 'file', '', false );
				$old_file_path = $old_file_name ? $this->get_absolute_upload_file_path( $old_file_name ) : false;

				if ( $old_file_name && ! \file_exists( $old_file_path ) ) {

					Lib::error( "Old metadata[file] does not exists( $old_file_name )." );
					return $sizes;
				}

				$old_orig_name = Lib::safe_key_value( $old_metadata, 'original_image', '', false );
				$old_orig_path = $old_orig_name ? \path_join( dirname( $old_file_path ), $old_orig_name ) : false;

				if ( $old_orig_name && ! \file_exists( $old_orig_path ) ) {

					Lib::error( "Old metadata[original_image] does not exists( $old_orig_name )." );
					return $sizes;
				}

				if ( ! empty( $old_metadata['file'] ) ) {
					$tracked_files[ wp_basename( $old_metadata['file'] ) ] = null;
				}
				if ( ! empty( $old_metadata['original_image'] ) ) {
					$tracked_files[ wp_basename( $old_metadata['original_image'] ) ] = null;
				}
				foreach ( $old_metadata['sizes'] as $size_name => $size_data ) {
					if ( ! empty( $size_data['file'] ) ) {
						$tracked_files[ wp_basename( $size_data['file'] ) ] = null;
					}
				}
				if ( is_array( $backup_sizes ) ) {
					foreach ( $backup_sizes as $backup_size ) {
						if ( ! empty( $backup_size['file'] ) ) {
							$tracked_files[ wp_basename( $backup_size['file'] ) ] = null;
						}
					}
				}

				update_post_meta( $attachment_id, '_warp_imagick_files', $tracked_files );

				if ( Shared::is_edited( $old_file_name ) && ! empty( $backup_sizes ) ) {

					$this->my_image_state = 'edited';
				}
			}

			$warp_original = get_post_meta( $attachment_id, '_warp_imagick_source', $single = true );

			if ( empty( $warp_original ) ) {

				if ( ! $is_regenerate ) {

					$warp_original = wp_basename( $this->my_gen_file_path );
					Lib::debug( 'Warp original image path found on new or upload attachment: ' . $warp_original );

				} elseif ( 'edited' === $this->my_image_state ) {

					if ( ! empty( $backup_sizes['full-orig']['file'] ) ) {

						$full_original = wp_basename( $backup_sizes['full-orig']['file'] );

						if ( empty( $warp_original ) ) {

							$suffix = '-rotated.';
							if ( strpos( $full_original, $suffix ) ) {
								$test_original = str_replace( $suffix, '.', $full_original );
								if ( \file_exists( \path_join( dirname( $new_file_path ), $test_original ) ) ) {
									$warp_original = wp_basename( $test_original );
								} else {
									Lib::debug( 'Full-orig "-rotated" found but original file does not exists?' );
								}
							}
						}

						if ( empty( $warp_original ) ) {

							$suffix = '-scaled.';
							if ( strpos( $full_original, $suffix ) ) {
								$test_original = str_replace( $suffix, '.', $full_original );
								if ( \file_exists( \path_join( dirname( $new_file_path ), $test_original ) ) ) {
									$warp_original = wp_basename( $test_original );
								} else {
									Lib::debug( 'Full-orig "-scaled" found, but original file does not exists?' );
								}
							}
						}

						if ( empty( $warp_original ) ) {

							$suffix = '-' . $backup_sizes['full-orig']['width'] . 'x' . $backup_sizes['full-orig']['height'];
							if ( strpos( $full_original, $suffix ) ) {
								$test_original = str_replace( '-' . $suffix . '.', '.', $full_original );
								if ( \file_exists( \path_join( dirname( $new_file_path ), $test_original ) ) ) {
									$warp_original = wp_basename( $test_original );
								} else {
									Lib::debug( 'Full-orig "-WxH" found, but original file does not exists?' );
								}
							}
						}

						if ( empty( $warp_original ) ) {

							$warp_original = wp_basename( $full_original );
							if ( ! \file_exists( \path_join( dirname( $new_file_path ), $full_original ) ) ) {
								Lib::debug( '$backup_sizes[full-orig][file] does not exists?' );
							}
						}
					} else {

						Lib::error( 'Image is user-edited but $backup_sizes[full-orig][file] is empty?' );
						$warp_original = wp_basename( $this->my_gen_file_path );
					}
				} else {

					$warp_original = wp_basename( $this->my_gen_file_path );
				}

				if ( ! empty( $warp_original ) ) {

					$shortest_basename = $warp_original;
					foreach ( $tracked_files as $basename => $flag ) {
						if ( strlen( $basename ) < strlen( $shortest_basename ) ) {
							$shortest_basename = $basename;
						}
					}

					if ( $shortest_basename !== $warp_original ) {
						Lib::debug( 'Filename shorter than warp original found: ' . $shortest_basename );
						$warp_original = $shortest_basename;
					}

					foreach ( $tracked_files as $basename => $flag ) {
						if ( 0 !== strpos( pathinfo( $basename, PATHINFO_FILENAME ), pathinfo( $warp_original, PATHINFO_FILENAME ) ) ) {
							Lib::debug( 'Filename not derived from warp-original: ' . $warp_original . ' / ' . $basename );
						}
					}

					if ( Shared::is_edited( $warp_original ) && 'edited' !== $this->my_image_state ) {

						Lib::debug( 'Warp original image path is matching user-edited regex-pattern: ' . $warp_original );
					}

					update_post_meta( $attachment_id, '_warp_imagick_source', $warp_original );

					Lib::debug( 'Warp original image path saved: ' . $warp_original );

				} else {
					Lib::debug( 'Warp original NOT found!' );
				}
			}

			$warp_exifmeta = get_post_meta( $attachment_id, '_warp_imagick_exifmeta', $single = true );

			if ( empty( $warp_exifmeta ) ) {

				if ( ! empty( $old_metadata['image_meta'] ) ) {

					update_post_meta( $attachment_id, '_warp_imagick_exifmeta', $old_metadata['image_meta'] );
				} elseif ( ! empty( $metadata['image_meta'] ) ) {

					update_post_meta( $attachment_id, '_warp_imagick_exifmeta', $metadata['image_meta'] );
				}
			} elseif ( ! $is_regenerate ) {
				// phpcs:ignore
				Lib::debug( 'Warp exifmeta found for new attachment? ' . print_r( $warp_exifmeta, true ) );
			}

			if ( '' === $this->my_image_state ) {

				if ( ! empty( $metadata['original_image'] ) ) {
					$orig_name = pathinfo( $metadata['original_image'], PATHINFO_FILENAME );
					$file_name = pathinfo( $metadata['file'], PATHINFO_FILENAME );

					if ( Lib::starts_with( $file_name, $orig_name ) ) {

						if ( $orig_name . '-scaled' === $file_name ) {
							$this->my_image_state = 'scaled';
						} elseif ( $orig_name . '-rotated' === $file_name ) {
							$this->my_image_state = 'rotated';
						} else {

							Lib::debug( 'Unexpected: [file]-suffix is unknown.' );
							Lib::debug( '$orig_name: ' . $orig_name );
							Lib::debug( '$file_name: ' . $file_name );
							Lib::debug( '$name_suffix: ' . substr( $file_name, strlen( $orig_name ) ) );
							Lib::debug( '$attachment_id: ' . $attachment_id );
							Lib::debug( '$metadata[file]: ' . $metadata['file'] );
							Lib::debug( '$metadata[original_image]: ' . $metadata['original_image'] );
							return $sizes;
						}
					} else {

						Lib::debug( 'Unexpected: $file_name does not start with $orig_name.' );
						Lib::debug( '$attachment_id: ' . $attachment_id );
						Lib::debug( '$metadata[file]: ' . $metadata['file'] );
						Lib::debug( '$metadata[original_image]: ' . $metadata['original_image'] );
						return $sizes;

					}
				} else {

					switch ( $this->my_mime_type ) {
						case 'image/jpeg':
							$this->my_image_state = 'lossy';
							break;
						case 'image/png':
							$this->my_image_state = 'lossless';
							break;
						default:
							Lib::debug( 'Unexpected mime-type: ' . $this->my_mime_type );
							return $sizes;
					}
				}
			}

			switch ( $this->my_image_state ) {

				case 'edited':
					/** Case "edited"
					 *
					 * $old_file_name matches user-edited file name pattern.
					 * Implies: This is a regenerate request.
					 * Implies: Backup metadata is found.
					 *
					 * Q: Why is this a special case?
					 * A: Function wp_get_original_image_path will return [original_image]
					 * instead of attached user-edited file name.
					 *
					 * Plugin can't regenerate user-edited image & thumbnails from [original_image],
					 * because user-edited image can't be reconstructed from [original_image].
					 */

					if ( $metadata['file'] !== $old_metadata['file'] ) {

						$metadata['file']   = $old_metadata['file'];
						$metadata['width']  = $old_metadata['width'];
						$metadata['height'] = $old_metadata['height'];
					}

					$edited_source = $old_file_path;

					$this->webp_clone_image( $edited_source, $this->my_mime_type );

					break;

				case 'scaled':
					/** Case 'scaled'
					 *
					 * The method $editor->resize (to 'scaled') enforces thumbnail quality,
					 * therefore 'scaled' already has current thumbnail quality.
					 *
					 * [original_image] is set by wp_create_image_subsizes.
					 * [file] always equals to [original_image]+'scaled',
					 * [file] is scaled and then optionally rotated.
					 * [file] has a thumbnail quality.
					 * on upload and on regenerate.
					 */

					$source = $this->get_absolute_upload_file_path( $metadata['file'] );
					$this->webp_clone_image( $source, $this->my_mime_type );

					break;

				case 'rotated':
					/** Case 'rotated'
					 *
					 * Why? The method $editor->maybe_exif_rotate()
					 * does not implement compression, [file] is rotated
					 * but has no thumbnail quality.
					 *
					 * [original_image] is set.
					 * [file] always equals to [original_image]+'rotated',
					 * on upload and on regenerate.
					 */

					$source = $this->get_absolute_upload_file_path( $metadata['file'] );

					/**
					 * For larger images create WebP clone and skip compression.
					 * Because compressing larger images may exhaust Imagick
					 * resources and hang response until timeout.
					 */
					if ( 2500 < $metadata['width'] || 2500 < $metadata['height'] ) {
						Lib::debug( 'Rotated image can\'t fit within 2500x2500 pixels (' . $metadata['file'] . '.' );
						$this->webp_clone_image( $source, $this->my_mime_type );
						break;
					}

					try {
						$editor = Shared::get_warp_editor( $source );
						if ( \is_wp_error( $editor ) ) {
							Lib::error( 'Function get_warp_editor() returned an error: ' . $editor->get_error_message() );
						} else {
							$pressed = $editor->compress_image( $metadata['width'], $metadata['height'] );
							if ( \is_wp_error( $pressed ) ) {
								Lib::error( '$editor::compress_image() failed with error: ' . $pressed->get_error_message() );
							} else {
								$saved = $editor->save( $source );
								if ( \is_wp_error( $saved ) ) {
									Lib::error( '$editor::save() failed with error: ' . $saved->get_error_message() );
								}
							}
						}
					} catch ( Exception $e ) {
						Lib::error( 'Exception caught: ' . $e->getMessage() );
					}

					if ( isset( $editor ) ) {
						is_callable( array( $editor, '__destruct' ) ) && $editor->__destruct();
						unset( $editor );
					}
					break;

				case 'lossless':
					/** Case 'lossless'
					 *
					 * Mime Type is image/png (lossless)
					 * Mime image/png is never scaled/rotated by wp_create_image_subsizes.
					 *
					 * [orig file] is empty.
					 * [file] name is
					 * - on upload: original/upload.
					 * - on regenerate: wp_get_original_image_path
					 */

					$source = $this->get_absolute_upload_file_path( $metadata['file'] );
					$this->webp_clone_image( $source, $this->my_mime_type );
					break;

				case 'lossy':
					/** Case 'lossy'
					 *
					 * [original_image] is not set.
					 * [file] name is
					 * - on upload: original/upload.
					 * - on regenerate: wp_get_original_image_path
					 */

					$source = $this->get_absolute_upload_file_path( $metadata['file'] );

					if ( $this->get_option( 'compress-jpeg-original-disabled', Shared::compress_jpeg_original_disabled_default() ) ) {

						$this->webp_clone_image( $source, $this->my_mime_type );
						break;
					}

					/**
					 * For larger images create WebP clone and skip compression.
					 * Because compressing larger images may exhaust Imagick
					 * resources and hang response until timeout.
					 */
					if ( 2500 < $metadata['width'] || 2500 < $metadata['height'] ) {
						Lib::debug( 'Source image can\'t fit within 2500x2500 pixels (' . $metadata['file'] . '.' );
						$this->webp_clone_image( $source, $this->my_mime_type );
						break;
					}

					try {

						$editor = Shared::get_warp_editor( $source );
						if ( \is_wp_error( $editor ) ) {
							Lib::error( 'Function get_warp_editor() returned an error: ' . $editor->get_error_message() );
							return $sizes;
						}

						$pressed = $editor->compress_image( $metadata['width'], $metadata['height'] );
						if ( \is_wp_error( $pressed ) ) {
							Lib::error( '$editor::compress_image() failed with error: ' . $pressed->get_error_message() );
							return $sizes;
						}

						$saved = $editor->save();
						if ( \is_wp_error( $saved ) ) {
							Lib::error( '$editor::save() failed with error: ' . $saved->get_error_message() );
							return $sizes;
						}

						$metadata['file']           = _wp_relative_upload_path( $saved['path'] );
						$metadata['original_image'] = wp_basename( $source );

						if ( filesize( $saved['path'] ) > filesize( $source ) ) {

							Lib::debug( 'Attached file-size > Original file-size ( ' . filesize( $saved['path'] ) . ' > ' . filesize( $source ) . '): ' . _wp_relative_upload_path( $source ) );
							Shared::copy_file( $source, $saved['path'], $overwrite = true );
							Lib::debug( 'Attached file is overwritten with Original file.' );
						}
						$this->webp_clone_image( $saved['path'], $this->my_mime_type );

					} catch ( Exception $e ) {
						Lib::error( 'Exception caught: ' . $e->getMessage() );
					}

					if ( isset( $editor ) ) {
						is_callable( array( $editor, '__destruct' ) ) && $editor->__destruct();
						unset( $editor );
					}

					break;

				default:
					Lib::error( 'Invalid image/state (value) detected: ' . $this->my_image_state );
					return $sizes;

			}

			if ( ! empty( $metadata['file'] ) ) {
				$tracked_files[ wp_basename( $metadata['file'] ) ] = null;
			}
			if ( ! empty( $metadata['original_image'] ) ) {
				$tracked_files[ $metadata['original_image'] ] = null;
			}

			update_post_meta( $attachment_id, '_warp_imagick_files', $tracked_files );

			$new_file_path = $this->get_absolute_upload_file_path( $metadata['file'] );
			if ( ! file_exists( $new_file_path ) ) {
				Lib::error( 'File ' . $metadata['file'] . ' is not found in upload directory.' );
				return $sizes;
			}

			$new_orig_path = false;
			if ( ! empty( $metadata['original_image'] ) ) {
				$new_orig_path = \path_join( dirname( $new_file_path ), $metadata['original_image'] );
				$new_orig_path = \file_exists( $new_orig_path ) ? $new_orig_path : false;
			}

			$thumbs_source = $new_orig_path ? $new_orig_path : $new_file_path;

			switch ( $this->my_image_state ) {

				case 'edited':
					$thumbs_source = $edited_source ? $edited_source : $thumbs_source;
					break;

				case 'scaled':
				case 'rotated':
				case 'lossless':
				case 'lossy':
					break;

				default:
					Lib::error( 'Invalid image state value: ' . $this->my_image_state );
					return $sizes;
			}

			if ( empty( $thumbs_source ) ) {
				Lib::error( 'Thumbnails source path is empty.' );
				return $sizes;
			}

			if ( ! \file_exists( $thumbs_source ) ) {
				Lib::error( 'Thumbnails source ' . $thumbs_source . ', file not found.' );
				return $sizes;
			}

			if ( $new_orig_path ) {
				$this->webp_clone_image( $new_orig_path, $this->my_mime_type );

			}

			if ( $is_regenerate && ! empty( $warp_exifmeta ) ) {

				$metadata['image_meta'] = $warp_exifmeta;
			}

			if ( wp_basename( $metadata['file'] ) !== $warp_original ) {

				if ( empty( $metadata['original_image'] )
				|| $metadata['original_image'] !== $warp_original ) {
					Lib::debug(
						'Updating [original_image] from '
						. ( empty( $metadata['original_image'] ) ? 'empty' : $metadata['original_image'] )
						. ' to ' . $warp_original
					);
					$metadata['original_image'] = $warp_original;
				}
			}

			$curr_attached_path = \get_attached_file( $attachment_id );
			$todo_attached_path = $this->get_absolute_upload_file_path( $metadata['file'] );

			if ( $curr_attached_path !== $todo_attached_path ) {
				if ( ! \update_attached_file( $attachment_id, $todo_attached_path ) ) {

					Lib::error( 'Failed to sync/update_attached_file(). Attached file may be inconsistent for post ID: ' . $attachment_id . '.' );
				}
			}

			$metadata['sizes'] = array();

			$this->my_updating_meta = true;
			\wp_update_attachment_metadata( $attachment_id, $metadata );
			$this->my_updating_meta = false;
			$this->my_metadata_done = $metadata;

			if ( ! empty( $sizes ) ) {

				$editor = Shared::get_warp_editor( $thumbs_source );

				if ( is_wp_error( $editor ) ) {
					Lib::error( 'Function get_warp_editor() returned an error: ' . $editor->get_error_message() );
				} else {

					if ( 'image/jpeg' === $this->my_mime_type && 'edited' !== $this->my_image_state ) {
						$rotated = $editor->maybe_exif_rotate();

						if ( \is_wp_error( $rotated ) ) {
							Lib::error( '$editor->maybe_exif_rotate() failed with error: ' . $rotated->get_error_message() );
						}
					}

					if ( \method_exists( $editor, 'make_subsize' ) ) {

						Lib::debug_var( $sizes, 'Sizes to create' );
						Lib::debug( 'Method: $editor->make_subsize' );

						foreach ( $sizes as $new_size_name => $new_size_data ) {
							$new_size_meta = $editor->make_subsize( $new_size_data );

							if ( is_wp_error( $new_size_meta ) ) {
								Lib::debug_var( $new_size_data, 'Size request' );
								Lib::debug_var( $new_size_meta, 'Size failure' );
								continue;
							} else {

								$metadata['sizes'][ $new_size_name ] = $new_size_meta;

								$this->my_updating_meta = true;
								\wp_update_attachment_metadata( $attachment_id, $metadata );
								$this->my_updating_meta = false;

								$this->my_metadata_done = $metadata;
							}
						}
						$sizes_generated = $metadata['sizes'];
						Lib::debug_var( $sizes_generated, 'Sizes generated' );
					} elseif ( \method_exists( $editor, 'multi_resize' ) ) {

						/** Clean, one-time update of metadata. Anyways, in case of fatal error or timeout,
						 * function wp_update_image_subsizes() won't be able to use right source ("edited").
						 * For each size, execution time is extended to prevent timeout. See class.
						 */

						Lib::debug_var( $sizes, 'Sizes to create' );
						Lib::debug( 'Method: $editor->multi_resize' );
						$metadata['sizes'] = $editor->multi_resize( $sizes );
						$sizes_generated   = $metadata['sizes'];
						Lib::debug_var( $sizes_generated, 'Sizes generated' );

						$this->my_updating_meta = true;
						\wp_update_attachment_metadata( $attachment_id, $metadata );
						$this->my_updating_meta = false;

						$this->my_metadata_done = $metadata;

					} else {

						Lib::error( 'Methods multi_resize/make_subsize not found in $editor (' . get_class( $editor ) . ')' );
					}
				}

				foreach ( $metadata['sizes'] as $size_name => $size_data ) {
					if ( ! empty( $size_data['file'] ) ) {
						$tracked_files[ wp_basename( $size_data['file'] ) ] = null;
					}
				}

				update_post_meta( $attachment_id, '_warp_imagick_files', $tracked_files );

			}

			switch ( $this->do_generate_webp_clones() ) {
				case false:
				case 0:
				case 2:
					foreach ( $tracked_files as $collected_name => $ignore ) {
						$item_path = \path_join( dirname( $metadata['file'] ), $collected_name );
						$item_path = $this->get_absolute_upload_file_path( $item_path );
						$this->webp_clone_image( $item_path, $this->my_mime_type );
					}
					break;
			}

			$this->my_gen_attach_id = 0;
			$this->my_gen_file_path = '';

			$this->my_generate_meta = true;

			return array();

		}

		/** On wp_generate_attachment_metadata filter.
		 *
		 * Replace wp_generate_attachment_metadata() functionality for JPEG/PNG images between
		 * intermediate_image_sizes_advanced and wp_generate_attachment_metadata hooks
		 * Late priority (+99) will allow other hookoverwrite RT plugin returned sizes.
		 *
		 * @param array  $metadata - attachment meta data.
		 * @param int    $attachment_id - number.
		 * @param string $context - caller context.
		 */
		public function on_wp_generate_attachment_metadata_99_filter( $metadata, $attachment_id = false, $context = false ) {

			if ( 'create' !== $context ) {
				return $metadata;
			}

			$this->my_generate_meta = false;

			if ( is_array( $this->my_metadata_done ) ) {

				$metadata = $this->my_metadata_done;
			}
			$this->my_metadata_done = false;

			return $metadata;

		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Delete Attachment & Delete File Hooks

		/** On 'wp_delete_file' filter.
		 *
		 * This should be available when third user-edit is deleted?
		 * Make sure webp clone is deleted when image file is deleted.
		 * Applies everywhere, not only after on_delete_attachment_action.
		 *
		 * @param string $path of file to delete.
		 */
		public function on_wp_delete_file_filter( $path ) {

			$mime_type = wp_get_image_mime( $path );

			switch ( $mime_type ) {
				case 'image/jpeg':
					$delete_webp = Shared::append_suffix_to_file_name( $path, '-jpg' );
					$delete_webp = Shared::replace_file_name_extension( $delete_webp, 'webp' );
					if ( \file_exists( $delete_webp ) ) {
						\unlink( $delete_webp );
					}
					/** Fall through */
				case 'image/png':
					$delete_webp = Shared::get_webp_file_name( $path );
					if ( \file_exists( $delete_webp ) ) {
						\unlink( $delete_webp );
					}
					break;
			}
			return $path;
		}

		/** On 'delete_attachment' action.
		 *
		 * Make sure all files are deleted when attachment deleted.
		 *
		 * @param int     $post_id Attachment ID.
		 * @param WP_Post $wp_post Post object.
		 */
		public function on_delete_attachment_action( $post_id, $wp_post ) {

			if ( 'attachment' !== get_post_type( $wp_post ) ) {
				return;
			}

			switch ( $wp_post->post_mime_type ) {
				case 'image/jpeg':
				case 'image/png':
					break;
				default:
					return;
			}

			$metadata = \wp_get_attachment_metadata( $post_id );
			$tracked  = \get_post_meta( $post_id, '_warp_imagick_files', true );
			if ( ! is_array( $tracked ) ) {
				$tracked = array();
			}

			\add_action(
				'deleted_post',
				function ( $deleted_post_id, $wp_post ) use ( $post_id, $metadata, $tracked ) {

					if ( $deleted_post_id !== $post_id ) {
						return;
					}

					if ( ! isset( $metadata['file'] ) ) {
						return;
					}

					$uploadpath = \wp_get_upload_dir();
					$directory  = \path_join( $uploadpath['basedir'], dirname( $metadata['file'] ) );
					foreach ( $tracked as $basename => $flag ) {
						if ( \wp_delete_file_from_directory( \path_join( $directory, $basename ), $directory ) ) {
							$tracked[ $basename ] = true;
							continue;
						}
						$tracked[ $basename ] = false;
						Lib::debug( 'Failed to delete: ' . $basename );
					}

				},
				10,
				2
			);
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Helper functions

		/** Read/extract version from plugin header */
		public function read_plugin_version() {
			return get_file_data( $this->get_file(), array( 'version' => 'Version' ) )['version'];
		}

		/** Set disabled property.
		 *
		 * @param bool|array $disabled to set.
		 */
		private function set_disabled( $disabled ) {

			if ( false === $disabled ) {
				$this->my_is_disabled = $disabled;
			} elseif ( is_array( $disabled ) ) {
				$this->my_is_disabled = $disabled;
			} elseif ( true === $disabled ) {
				$this->my_is_disabled = array( 'Disabled by no reason given.' );
			} else {
				$this->my_is_disabled = array( 'Disabled by unsupported argument value/type.' );
			}
		}

		/** Plugin is disabled due activation failures (missing requirements). */
		public function is_disabled() {

			return false !== $this->my_is_disabled;

		}

		/** Returns array of strings (messages): activation failures (missing requirements). */
		public function why_is_disabled() {

			return false !== $this->my_is_disabled && is_array( $this->my_is_disabled ) ? $this->my_is_disabled : array();

		}

		/** Return max image width (if enabled by option) */
		public function get_max_image_width() {
			if ( $this->get_option( 'image-max-width-enabled', Shared::max_width_enabled_default() ) ) {
				$max_image_width = $this->get_option( 'image-max-width-pixels', Shared::max_width_value_default() );

				if ( $max_image_width >= Shared::max_width_value_min() && $max_image_width <= Shared::max_width_value_max() ) {
					return $max_image_width;
				}
			}
			return false;
		}

		/** Generate webp clones? */
		public function do_generate_webp_clones() {
			if ( true === $this->can_generate_webp_clones() ) {

				return $this->get_option( 'webp-images-create', Shared::webp_images_create_default() );
			}
			return 3;
		}

		/** Can Generate webp clones? */
		public function can_generate_webp_clones() {
			if ( null === $this->my_can_do_webp ) {
				$functions = array(
					'\\imagewebp',
					'\\imagecreatefromjpeg',
					'\\imagecreatefrompng',
					'\\imageistruecolor',

					'\\imagealphablending',
					'\\imagecolorallocatealpha',
					'\\imagecreatetruecolor',
					'\\imagefilledrectangle',
					'\\imagedestroy',
					'\\imagecopy',
					'\\imagesx',
					'\\imagesy',
				);

				$this->my_can_do_webp = true;
				foreach ( $functions as $function ) {
					if ( true !== function_exists( $function ) || true !== is_callable( $function ) ) {
						$this->my_can_do_webp = false;
						Lib::debug( 'php-gd can\'t generate webp.' );
						break;
					}
				}
			}
			return $this->my_can_do_webp;
		}

		/** Create webp clone.
		 *
		 * @param string   $image_path to clone.
		 * @param string   $mime_type of $image_path.
		 * @param bool|int $do_generate_webp_clones status/choice.
		 */
		public function webp_clone_image( $image_path, $mime_type = '', $do_generate_webp_clones = null ) {

			if ( ! is_string( $image_path ) || empty( trim( $image_path ) ) ) {
				return false;
			}

			$webp_path = Shared::get_webp_file_name( $image_path );

			if ( null === $do_generate_webp_clones ) {
				$do_generate_webp_clones = $this->do_generate_webp_clones();
			}

			switch ( $do_generate_webp_clones ) {

				case false:
				case 0:
					if ( \file_exists( $webp_path ) ) {
						\wp_delete_file( $webp_path );
					}
					return false;

				case true:
				case 1:
					break;

				case 2:
					if ( \file_exists( $webp_path ) ) {
						return $webp_path;
					}
					break;

				case 3:
					return false;
			}

			if ( false === $this->can_generate_webp_clones() ) {

				return false;
			}

			if ( ! is_readable( $image_path ) ) {

				Lib::debug( 'Image file is not readable: ' . _wp_relative_upload_path( $image_path ) );
				return false;
			}

			if ( ! is_string( $mime_type ) || empty( trim( $mime_type ) ) ) {
				$mime_type = wp_get_image_mime( $image_path );
			}

			switch ( $mime_type ) {
				case 'image/jpeg':
				case 'image/png':
					break;

				default:
					Lib::debug( 'webp_clone_image: mime-type is not JPEG/PNG, but: ' . $mime_type );
					return false;
			}

			$webp_quality = $this->get_option( 'webp-compression-quality', Shared::webp_quality_default() );
			if ( $webp_quality > Shared::webp_quality_value_max() ) {
				$webp_quality = Shared::webp_quality_value_max();
			}
			if ( $webp_quality < Shared::webp_quality_value_min() ) {
				$webp_quality = Shared::webp_quality_value_min();
			}

			$gd_convert   = false;
			$gd_jpeg      = false;
			$gd_png       = false;
			$gd_truecolor = false;

			switch ( $mime_type ) {
				case 'image/jpeg':
					$gd_jpeg = \imagecreatefromjpeg( $image_path );
					if ( false === $gd_jpeg ) {
						Lib::debug( 'Failed imagecreatefromjpeg: ' . _wp_relative_upload_path( $image_path ) );
						break;
					}
					if ( 0 === $this->get_option( 'webp-jpeg-compression-quality', Shared::webp_jpeg_quality_default() ) ) {
						$webp_quality = $this->get_option( 'jpeg-compression-quality', Shared::jpeg_quality_default() );
					}
					if ( $webp_quality > Shared::webp_quality_value_max() ) {
						$webp_quality = Shared::webp_quality_value_max();
					}
					if ( $webp_quality < Shared::webp_quality_value_min() ) {
						$webp_quality = Shared::webp_quality_value_min();
					}
					if ( $gd_jpeg ) {
						$gd_convert = $gd_jpeg;
						$gd_jpeg    = false;
					}
					break;

				case 'image/png':
					$gd_png = \imagecreatefrompng( $image_path );
					if ( false === $gd_png ) {
						Lib::error( 'Failed imagecreatefrompng: ' . _wp_relative_upload_path( $image_path ) );
						break;
					}
					if ( \imageistruecolor( $gd_png ) ) {
						$gd_convert = $gd_png;
						$gd_png     = false;
						break;
					}
					if ( function_exists( 'imagepalettetotruecolor' )
					&& \imagepalettetotruecolor( $gd_png ) ) {
						$gd_convert = $gd_png;
						$gd_png     = false;
						break;
					}

					$gd_truecolor = \imagecreatetruecolor( \imagesx( $gd_png ), \imagesy( $gd_png ) );
					if ( false === $gd_truecolor ) {
						Lib::debug( 'Failed imagecreatetruecolor: ' . _wp_relative_upload_path( $image_path ) );
						if ( false !== $gd_png ) {
							\imagedestroy( $gd_png );
							$gd_png = false;
						}
						break;
					}

					if ( false === \imagealphablending( $gd_truecolor, false ) ) {
						Lib::debug( 'Failed imagealphablending: ' . _wp_relative_upload_path( $image_path ) );
						if ( false !== $gd_png ) {
							\imagedestroy( $gd_png );
							$gd_png = false;
						}
						if ( false !== $gd_truecolor ) {
							\imagedestroy( $gd_truecolor );
							$gd_truecolor = false;
						}
						break;
					}

					$is_allocated = \imagecolorallocatealpha( $gd_truecolor, 255, 255, 255, 127 );
					if ( false === $is_allocated ) {
						if ( false !== $gd_png ) {
							\imagedestroy( $gd_png );
							$gd_png = false;
						}
						if ( false !== $gd_truecolor ) {
							\imagedestroy( $gd_truecolor );
							$gd_truecolor = false;
						}
						break;
					}

					if ( false === \imagefilledrectangle( $gd_truecolor, 0, 0, imagesx( $gd_png ), imagesy( $gd_png ), $is_allocated ) ) {
						Lib::debug( 'Failed imagefilledrectangle: ' . _wp_relative_upload_path( $image_path ) );
						if ( false !== $gd_png ) {
							\imagedestroy( $gd_png );
							$gd_png = false;
						}
						if ( false !== $gd_truecolor ) {
							\imagedestroy( $gd_truecolor );
							$gd_truecolor = false;
						}
						break;
					}

					if ( false === \imagealphablending( $gd_truecolor, true ) ) {
						Lib::debug( 'Failed imagealphablending 2: ' . _wp_relative_upload_path( $image_path ) );
						if ( false !== $gd_png ) {
							\imagedestroy( $gd_png );
							$gd_png = false;
						}
						if ( false !== $gd_truecolor ) {
							\imagedestroy( $gd_truecolor );
							$gd_truecolor = false;
						}
						break;
					};
					if ( false === \imagecopy( $gd_truecolor, $gd_png, 0, 0, 0, 0, \imagesx( $gd_png ), \imagesy( $gd_png ) ) ) {
						Lib::debug( 'Failed imagecopy: ' . _wp_relative_upload_path( $image_path ) );
						if ( false !== $gd_png ) {
							\imagedestroy( $gd_png );
							$gd_png = false;
						}
						if ( false !== $gd_truecolor ) {
							\imagedestroy( $gd_truecolor );
							$gd_truecolor = false;
						}
						break;
					}
					if ( false !== $gd_png ) {
						Lib::debug( '$gd_png reference not released: ' . _wp_relative_upload_path( $image_path ) );
						$gd_png = false;
					}
					if ( false !== $gd_truecolor ) {
						$gd_convert   = $gd_truecolor;
						$gd_truecolor = false;
					}
					break;
			}

			if ( $gd_jpeg ) {
				Lib::debug( 'Var $gd_jpeg is not released at: ' . _wp_relative_upload_path( $image_path ) );
				$gd_jpeg = false;
			}

			if ( $gd_png ) {
				Lib::debug( 'Var $gd_png is not released at: ' . _wp_relative_upload_path( $image_path ) );
				$gd_png = false;
			}

			if ( $gd_truecolor ) {
				Lib::debug( 'Var $gd_truecolor is not released at: ' . _wp_relative_upload_path( $image_path ) );
				$gd_truecolor = false;
			}

			if ( false !== $gd_convert ) {
				try {

					\wp_mkdir_p( dirname( $webp_path ) );
					if ( \imagewebp( $gd_convert, $webp_path, $webp_quality ) ) {
						if ( \file_exists( $webp_path ) ) {

							if ( \filesize( $webp_path ) % 2 === 1 ) {
								// phpcs:ignore
								\file_put_contents( $webp_path, "\0", FILE_APPEND );
							}

							$stat  = stat( dirname( $webp_path ) );
							$perms = $stat['mode'] & 0000666;
							chmod( $webp_path, $perms );
							\imagedestroy( $gd_convert );
							return $webp_path;
						} else {
							Lib::debug( 'imagewebp: file not created: ' . _wp_relative_upload_path( $webp_path ) );
						}
					} else {
						Lib::debug( 'imagewebp: failed for: ' . _wp_relative_upload_path( $webp_path ) );
					}
				} catch ( Exception $e ) {
					Lib::error( 'Exception caught: ' . $e->getMessage() );
				}
				\imagedestroy( $gd_convert );
			}
			return false;
		}

		/** Return private property  */
		public function is_upload() {
			return $this->my_is_upload;
		}

		/** Return private property  */
		public function get_my_metadata_done() {
			return $this->my_metadata_done;
		}

		/** Return private property  */
		public function get_my_generate_meta() {
			return $this->my_generate_meta;
		}

		/** Safe get current user can
		 *
		 * @param string $capability to check.
		 * @param mixed  ...$args [optional].
		 */
		public function get_current_user_can( $capability, ...$args ) {
			if ( \function_exists( 'wp_get_current_user' )
			&& \function_exists( 'current_user_can' )
			&& \current_user_can( $capability, ...$args ) ) {
				return true;
			}
			return false;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Private Static Helper functions

		/** Check if metadata valid and contains required keys.
		 *
		 * @param array $metadata to check.
		 */
		private static function is_valid_metadata( $metadata ) {

			if ( ! is_array( $metadata ) ) {
				return false;
			}
			if ( ! array_key_exists( 'file', $metadata ) ) {
				return false;
			}
			if ( ! array_key_exists( 'width', $metadata ) ) {
				return false;
			}
			if ( ! array_key_exists( 'height', $metadata ) ) {
				return false;
			}
			return true;
		}

		/** Debug-Log Imagick Resources.
		 *
		 * @param string $message debug block title.
		 */
		private static function debugImagickResources( $message ) {

			if ( ! Lib::is_debug() ) {
				return;
			}

		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Attachment Thumbnails Preview Template.

		/** Add Preview Thumbnails Template - Not publicly available.
		 * Template is activated only for logged in user,
		 * with required capability to upload media.
		 */
		private function add_preview_thumbnails_template() {

			\add_filter(
				'media_row_actions',
				function( $actions, $post = 0 ) {
					$actions[ $this->get_slug() . '-thumbnails' ] = sprintf(
						'<a target=_blank href="%s" rel="bookmark">%s</a>',
						\add_query_arg( $this->get_slug(), 'all', \get_permalink( $post->ID ) ),
						__( 'Preview Thumbnails', 'warp-imagick' )
					);
					return $actions;
				},
				10,
				2
			);

			\add_action(
				'attachment_submitbox_misc_actions',
				function() {
					global $post;
					?>
<div class="misc-pub-section misc-pub-<?php echo \esc_attr( $this->get_slug() ); ?>-thumbnails">
	<a target=_blank href="<?php echo \esc_url( \add_query_arg( $this->get_slug(), 'all', \get_permalink( $post->ID ) ) ); ?>" class="button-secondary button-large" title="<?php echo esc_attr( __( 'Preview all generated thumbnails.', 'warp-imagick' ) ); ?>"><?php echo \esc_html( __( 'Preview Thumbnails', 'warp-imagick' ) ); ?></a>
</div>
					<?php
				},
				100
			);

			\add_filter(
				'attachment_fields_to_edit',
				function ( $form_fields, $post ) {

					$form_fields[ $this->get_slug() . '-thumbnails' ] = array(
						'label'         => '',
						'input'         => 'html',
						'html'          => '<a target=_blank href="' . \esc_url( \add_query_arg( $this->get_slug(), 'all', \get_permalink( $post->ID ) ) ) . '" class="button-secondary button-large" title="' . esc_attr( __( 'Preview all generated thumbnails.', 'warp-imagick' ) ) . '">' . __( 'Preview Thumbnails', 'warp-imagick' ) . '</a>',
						'show_in_modal' => true,
						'show_in_edit'  => false,
					);

					return $form_fields;
				},
				99,
				2
			);

			\add_rewrite_endpoint( $this->get_slug(), EP_ATTACHMENT );

			\add_action(
				'wp',
				function() {

					if ( $this->is_raw_image_template_request() ) {

						\remove_all_actions( 'template_redirect' );
						\add_action(
							'template_redirect',
							function() {
								\remove_all_filters( 'template_include' );
								\add_filter(
									'template_include',
									function( $template ) {

										$raw_image_template = $this->get_path() . '/templates/raw-image-template.php';
										if ( is_file( $raw_image_template ) ) {
											header( $this->get_slug() . ': template' );
											$template = $raw_image_template;
										} else {
											Lib::error( 'Template file not found: ' . $raw_image_template );
										}
										return $template;
									}
								);
								return false;
							}
						);
					}
				}
			);
		}

		/** Is current request a Template Request? */
		private function is_raw_image_template_request() {

			$my_wp_query = $GLOBALS['wp_the_query'];

			if ( ! isset( $my_wp_query->query_vars[ $this->get_slug() ] ) ) {
				return false;
			}

			if ( ! in_array( $my_wp_query->query_vars[ $this->get_slug() ], array( '', 'all', 'raw', 'full', 'webp' ), true ) ) {
				return false;
			}

			if ( ! isset( $my_wp_query->post->post_type ) || 'attachment' !== $my_wp_query->post->post_type ) {
				return false;
			}

			if ( ! isset( $my_wp_query->post->post_mime_type ) || ! Lib::starts_with( $my_wp_query->post->post_mime_type, 'image/' ) ) {
				return false;
			}

			switch ( $my_wp_query->post->post_mime_type ) {
				case 'image/jpeg':
				case 'image/png':
					break;
				default:
					return false;
			}

			return true;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Convert WebP on demand

		/** Convert to WebP on demand */
		private function convert_webp_on_demand() {

			if ( false === $this->get_option( 'webp-cwebp-on-demand' ) ) {
				return;
			}

			\add_rewrite_endpoint( 'cwebp-on-demand', EP_ROOT );
			\add_action(
				'wp',
				function() {

					if ( isset( $GLOBALS['wp_the_query']->query_vars['cwebp-on-demand'] ) ) {

						\remove_all_actions( 'template_redirect' );
						\add_action(
							'template_redirect',
							function() {
								\remove_all_filters( 'template_include' );
								\add_filter(
									'template_include',
									function( $template ) {

										$cwebp_on_demand_template = $this->get_path() . '/templates/cwebp-on-demand-template.php';
										if ( is_file( $cwebp_on_demand_template ) ) {
											$template = $cwebp_on_demand_template;
										} else {
											Lib::error( 'Template file not found: ' . $cwebp_on_demand_template );
										}
										return $template;
									}
								);
								return false;
							}
						);
					}
				}
			);
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Disable Performance Lab WebP Upload Crap.

		/** Disable Performance Lab WebP Upload Crap. */
		private function disable_perflab_upload() {

			\add_filter(
				'pre_update_option',
				function ( $value, $option ) {
					switch ( $option ) {
						case 'perflab_modules_settings':
							if ( \is_array( $value ) ) {
								if ( \array_key_exists( 'images/webp-uploads', $value ) ) {
									if ( $value['images/webp-uploads']['enabled'] ) {
										$value['images/webp-uploads']['enabled'] = 0;
									}
								}
							}
							break;

						case 'perflab_generate_webp_and_jpeg':
							if ( $value ) {
								$value = 0;
							}
							break;
					}

					return $value;
				},
				99,
				2
			);

			$perflab = \get_option( 'perflab_modules_settings', 'Not available' );
			if ( \is_array( $perflab ) ) {
				if ( \array_key_exists( 'images/webp-uploads', $perflab ) ) {
					if ( $perflab['images/webp-uploads']['enabled'] ) {
						$perflab['images/webp-uploads']['enabled'] = 0;
					}
					\update_option( 'perflab_modules_settings', $perflab );
				}
			}
			$perflab = \get_option( 'perflab_generate_webp_and_jpeg' );
			if ( $perflab ) {
				\update_option( 'perflab_generate_webp_and_jpeg', 0 );
			}

		}

		// phpcs:ignore
	# endregion

	}
}
