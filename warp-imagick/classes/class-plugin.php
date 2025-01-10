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

use ddur\Warp_iMagick\Base\Meta_Plugin;
use ddur\Warp_iMagick\Settings;
use ddur\Warp_iMagick\Shared;
use ddur\Warp_iMagick\Hlp;

$class = __NAMESPACE__ . '\\Plugin';

if ( ! class_exists( $class ) ) {
	/** Plugin class */
	class Plugin extends Meta_Plugin {
		// phpcs:ignore
	# region Main Properties

		/** Plugin Disabled
		 *
		 * Is plugin disabled due to failed requirements?
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
	# region Plugin Class initialization.

		/** Plugin init. Called immediately after plugin class is constructed. */
		protected function init() {
			parent::init();

			\add_action(
				'init',
				array( $this, 'handle_wordpress_init' ),
				0,
				0
			);

			if ( is_admin() ) {
				$that = Settings::once( $this );
				if ( is_callable( array( $that, 'check_conflicting_plugins' ) ) ) {
					$that->check_conflicting_plugins();
				} else {
					sleep( 0 );

				}
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
								sleep( 0 );

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
				Dbg::error( 'No $attachment_id arg provided.' );
				return $metadata;
			}

			if ( ! self::is_valid_metadata( $metadata ) ) {
				Dbg::error( 'Invalid $metadata - $attachment_id: ' . $attachment_id );
				return $metadata;
			}

			if ( ! is_string( $metadata['file'] )
			|| empty( $metadata['file'] ) ) {
				Dbg::error( 'Invalid $metadata[file] - $attachment_id: ' . $attachment_id );
				return $metadata;
			}

			// phpcs:enable

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
				sleep( 0 );

			} else {
				sleep( 0 );

			}

			sleep( 0 );

			// phpcs:enable

			if ( true !== $scope ) {
				return $metadata;
			}

			if ( $this->my_gen_attach_id !== $attachment_id ) {
				sleep( 0 );

				$this->my_gen_attach_id = $attachment_id;
				$this->my_gen_file_path = $this->get_absolute_upload_file_path( $metadata['file'] );

				$this->my_save_metadata = array();

				$old_metadata = get_post_meta( $attachment_id, '_wp_attachment_metadata', $single = true );

				if ( isset( $old_metadata['sizes'] ) && count( $old_metadata['sizes'] ) ) {
					sleep( 0 );

					$this->store_attachment_metadata( $attachment_id );
				} else {
					sleep( 0 );

				}
			} else {
				sleep( 0 );

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
	# region WordPress Init Handler.

		/** WordPress Init */
		public function handle_wordpress_init() {
			parent::handle_wordpress_init();
			$this->cwebp_endpoint_do_init();
			$this->perflab_upload_disable();

			/** Check if plugin activation requirements failed?
			 *
			 * Property $this->my_is_disabled will be
			 * set to false or array of error strings
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

			// phpcs:enable

			if ( class_exists( '\\RegenerateThumbnails' ) ) {
				\add_action( 'regenerate_thumbnails_options_onlymissingthumbnails', '__return_false' );

				\add_action(
					'admin_head',
					function () {
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
			|| Hlp::is_wp_cli() ) {
				$this->add_preview_thumbnails_template();
				$this->set_my_wp_upload_basedir();
				Hlp::auto_hook( $this );
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
						++$counter;
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
			// phpcs:enable

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
				Dbg::error( 'WordPress versions below 5.3 are not supported' );
				return $sizes;
			}

			if ( ! is_array( $sizes ) ) {
				/**
				 * Not an error because this may be a valid filter call.
				 * See: wp-admin/includes/ajax-actions.php:3901!
				 */

				Dbg::error( '$sizes is not an array.' );
				return $sizes;
			}

			if ( ! is_array( $metadata ) ) {
				/**
				 * Not an error because this may be a valid filter call.
				 * See: wp-admin/includes/ajax-actions.php:3901!
				 */
				Dbg::error( '$metadata is not an array.' );
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
				Dbg::error( 'Argument $attachment_id === false' );
				return $sizes;
			}

			if ( ! is_int( $attachment_id ) || 0 >= $attachment_id ) {
				/** Required parameter $attachment_id is not an integer with value greater than 0 (zero). */
				Dbg::error( 'Argument $attachment_id has invalid type or value' );
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
				if ( Hlp::is_wp_cli() ) {
					sleep( 0 );

				} else {
					Dbg::error( 'Calling "intermediate_image_sizes_advanced" outside of function scope: wp_create_image_subsizes( _, $attachment_id ) is ' . $attachment_id . ', expected: ' . $this->my_gen_attach_id . ' !' );
				}
				return $sizes;

			}

			if ( ! array_key_exists( 'file', $metadata ) ) {
				Dbg::error( 'Image [file] is missing from $metadata.' );
				return $sizes;
			}

			if ( ! array_key_exists( 'width', $metadata ) ) {
				Dbg::error( 'Image [width] is missing from $metadata.' );
				return $sizes;
			}

			if ( ! array_key_exists( 'height', $metadata ) ) {
				Dbg::error( 'Image [height] is missing from $metadata.' );
				return $sizes;
			}

			if ( ! array_key_exists( 'sizes', $metadata ) || ! is_array( $metadata['sizes'] ) ) {
				Dbg::error( 'Image [sizes] is missing from $metadata.' );
				return $sizes;
			}

			$new_file_path = $this->get_absolute_upload_file_path( $metadata['file'] );
			if ( ! file_exists( $new_file_path ) ) {
				Dbg::error( 'File ' . $metadata['file'] . ' not found in upload directory.' );
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
					sleep( 0 );

					break;
				default:
					Dbg::error( 'Regenerate wp_create_image_subsizes( $file, _ ) doesn\'t match [file] or [original_image]' );
					return $sizes;
			}

			$attachment = get_post( $attachment_id );
			if ( ! is_a( $attachment, '\\WP_Post' ) ) {
				Dbg::error( 'Invalid attachment ID (can\'t get attachment WP_Post object).' );
				return $sizes;
			}

			if ( 'attachment' !== $attachment->post_type ) {
				Dbg::error( 'Invalid attached post-type (not an \'attachment\' type but \'' . $attachment->post_type . '\').' );
				return $sizes;
			}

			$post_mime_type = $attachment->post_mime_type;
			if ( empty( $post_mime_type ) || 0 !== strpos( $post_mime_type, 'image/' ) ) {
				Dbg::error( 'Attachment mime-type is not an image mime-type: ' . $post_mime_type . '.' );
				return $sizes;
			}

			$image_mime_type = wp_check_filetype( $metadata['file'] );
			$image_mime_type = $image_mime_type['type'];

			if ( $post_mime_type !== $image_mime_type ) {
				Dbg::error( 'Post mime-type doesn\'t match main/attached image mime-type :' . $post_mime_type . '.' );
				return $sizes;
			}

			switch ( $image_mime_type ) {
				case 'image/jpeg':
				case 'image/png':
					$this->my_mime_type = $image_mime_type;
					break;
				default:
					Dbg::error( 'Post mime-type is not equal to JPEG or PNG image type:' . $post_mime_type . '.' );
					return $sizes;
			}

			$this->my_metadata_done = false;
			$this->my_generate_meta = false;

			sleep( 0 );

			$this->my_image_state = '';

			$old_metadata = Hlp::safe_key_value( $this->my_save_metadata, '_wp_attachment_metadata', array(), false );
			$old_attached = Hlp::safe_key_value( $this->my_save_metadata, '_wp_attached_file', '', false );
			$backup_sizes = Hlp::safe_key_value( $this->my_save_metadata, '_wp_attachment_backup_sizes', array(), false );

			$is_regenerate = false !== $old_metadata;
			if ( Dbg::is_debug() && $is_regenerate === $this->my_is_upload ) {
				sleep( 0 );

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
					sleep( 0 );

					Dbg::error( 'Old metadata is not available (not saved before regenerate)!' );
					return $sizes;
				}

				$old_file_name = Hlp::safe_key_value( $old_metadata, 'file', '', false );
				$old_file_path = $old_file_name ? $this->get_absolute_upload_file_path( $old_file_name ) : false;

				if ( $old_file_name && ! \file_exists( $old_file_path ) ) {
					Dbg::error( "Old metadata[file] does not exists on regenerate ( $old_file_name )." );
					return $sizes;
				}

				$old_orig_name = Hlp::safe_key_value( $old_metadata, 'original_image', '', false );
				$old_orig_path = $old_orig_name ? \path_join( dirname( $old_file_path ), $old_orig_name ) : false;

				if ( $old_orig_name && ! \file_exists( $old_orig_path ) ) {
					Dbg::error( "Old metadata[original_image] does not exists on regenerate ( $old_orig_name )." );
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
					sleep( 0 );

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
									sleep( 0 );

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
									sleep( 0 );

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
									sleep( 0 );

								}
							}
						}

						// phpcs:enable

						if ( empty( $warp_original ) ) {
							$warp_original = wp_basename( $full_original );
							if ( ! \file_exists( \path_join( dirname( $new_file_path ), $full_original ) ) ) {
								sleep( 0 );

							}
						}
					} else {
						Dbg::error( 'Image is user-edited but $backup_sizes[full-orig][file] is empty.' );
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
						sleep( 0 );

						$warp_original = $shortest_basename;
					}

					foreach ( $tracked_files as $basename => $flag ) {
						if ( 0 !== strpos( pathinfo( $basename, PATHINFO_FILENAME ), pathinfo( $warp_original, PATHINFO_FILENAME ) ) ) {
							sleep( 0 );

						}
					}

					if ( Shared::is_edited( $warp_original ) && 'edited' !== $this->my_image_state ) {
						sleep( 0 );

					}

					update_post_meta( $attachment_id, '_warp_imagick_source', $warp_original );

					sleep( 0 );

				} else {
					sleep( 0 );

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
				sleep( 0 );

			}

			if ( '' === $this->my_image_state ) {
				if ( ! empty( $metadata['original_image'] ) ) {
					$orig_name = pathinfo( $metadata['original_image'], PATHINFO_FILENAME );
					$file_name = pathinfo( $metadata['file'], PATHINFO_FILENAME );

					if ( Hlp::starts_with( $file_name, $orig_name ) ) {
						if ( $orig_name . '-scaled' === $file_name ) {
							$this->my_image_state = 'scaled';
						} elseif ( $orig_name . '-rotated' === $file_name ) {
							$this->my_image_state = 'rotated';
						} else {
							/** No other suffixes beyond scaled/rotated,
							 * created by wp_create_image_subsizes before
							 * this intermediate_image_sizes_advanced filter
							 * should exists at this point.
							 *
							 * Other suffixes may be created later in code.
							 */
							sleep( 0 );

							Dbg::error( 'Unexpected: [file]-suffix is not recognized: ' . $file_name . '.' );
							return $sizes;
						}
					} else {
						sleep( 0 );

						Dbg::error( 'Unexpected: $file_name (' . $file_name . ' does not start with $orig_name (' . $orig_name . ').' );
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
							Dbg::error( 'Unexpected mime-type: ' . $this->my_mime_type );
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
						// phpcs:enable

						$metadata['file']   = $old_metadata['file'];
						$metadata['width']  = $old_metadata['width'];
						$metadata['height'] = $old_metadata['height'];
					}

					$edited_source = $old_file_path;

					sleep( 0 );

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
					sleep( 0 );

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
						sleep( 0 );

						$this->webp_clone_image( $source, $this->my_mime_type );
						break;
					}

					try {
						sleep( 0 );

						$editor = Shared::get_warp_editor( $source );
						if ( \is_wp_error( $editor ) ) {
							Dbg::error( 'Function get_warp_editor() returned an error: ' . $editor->get_error_message() );
							return $sizes;
						} else {
							$pressed = $editor->compress_image( $metadata['width'], $metadata['height'] );
							if ( \is_wp_error( $pressed ) ) {
								Dbg::error( '$editor::compress_image() failed with error: ' . $pressed->get_error_message() );
								return $sizes;
							} else {
								$saved = $editor->save( $source );

								if ( \is_wp_error( $saved ) ) {
									Dbg::error( '$editor::save() failed with error: ' . $saved->get_error_message() );
									return $sizes;
								}
								$target = $saved['path'];
							}
						}
					} catch ( \Exception $e ) {
						Dbg::error( 'Exception caught: ' . $e->getMessage() );
						return $sizes;
					}

					sleep( 0 );

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
					sleep( 0 );

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
						sleep( 0 );

						$this->webp_clone_image( $source, $this->my_mime_type );
						break;

					}

					/**
					 * For larger images create WebP clone and skip compression.
					 * Because compressing larger images may exhaust Imagick
					 * resources and hang response until timeout.
					 */
					if ( 2500 < $metadata['width'] || 2500 < $metadata['height'] ) {
						sleep( 0 );

						$this->webp_clone_image( $source, $this->my_mime_type );
						break;

					}

					try {
						/** JPEG only: Compress attached original.
						 * Copy metadata[file] to metadata[original].
						 * Change metadata[file] to compressed file name.
						 */
						sleep( 0 );

						$editor = Shared::get_warp_editor( $source );
						if ( \is_wp_error( $editor ) ) {
							Dbg::error( 'Function get_warp_editor() returned an error: ' . $editor->get_error_message() );
							return $sizes;
						}

						$pressed = $editor->compress_image( $metadata['width'], $metadata['height'] );
						if ( \is_wp_error( $pressed ) ) {
							Dbg::error( '$editor::compress_image() failed with error: ' . $pressed->get_error_message() );
							return $sizes;
						}

						$saved = $editor->save();
						if ( \is_wp_error( $saved ) ) {
							Dbg::error( '$editor::save() failed with error: ' . $saved->get_error_message() );
							return $sizes;
						}

						$target = $saved['path'];

						$metadata = _wp_image_meta_replace_original( $saved, $source, $metadata, $attachment_id );

						if ( filesize( $target ) > filesize( $source ) ) {
							sleep( 0 );

							Shared::copy_file( $source, $target, $overwrite = true );
							sleep( 0 );

						}
						sleep( 0 );

						$this->webp_clone_image( $target, $this->my_mime_type );

					} catch ( \Exception $e ) {
						Dbg::error( 'Exception caught: ' . $e->getMessage() );
						return $sizes;
					}

					if ( isset( $editor ) ) {
						is_callable( array( $editor, '__destruct' ) ) && $editor->__destruct();
						unset( $editor );
					}

					break;

				default:
					Dbg::error( 'Invalid image/state (value) detected: ' . $this->my_image_state );
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
				Dbg::error( 'File ' . $metadata['file'] . ' is not found in upload directory.' );
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
					Dbg::error( 'Invalid image state value: ' . $this->my_image_state );
					return $sizes;
			}

			if ( empty( $thumbs_source ) ) {
				Dbg::error( 'Thumbnails source file-path is empty.' );
				return $sizes;
			}

			if ( ! \file_exists( $thumbs_source ) ) {
				Dbg::error( 'Thumbnails source file not found: ' . $thumbs_source . '.' );
				return $sizes;
			}

			if ( $new_orig_path ) {
				sleep( 0 );

				$this->webp_clone_image( $new_orig_path, $this->my_mime_type );

				// phpcs:enable
			}

			if ( $is_regenerate && ! empty( $warp_exifmeta ) ) {
				$metadata['image_meta'] = $warp_exifmeta;
			}

			if ( wp_basename( $metadata['file'] ) !== $warp_original ) {
				if ( empty( $metadata['original_image'] )
				|| $metadata['original_image'] !== $warp_original ) {
					sleep( 0 );

					$metadata['original_image'] = $warp_original;
				}
			}

			$curr_attached_path = \get_attached_file( $attachment_id );
			$todo_attached_path = $this->get_absolute_upload_file_path( $metadata['file'] );

			if ( $curr_attached_path !== $todo_attached_path ) {
				if ( ! \update_attached_file( $attachment_id, $todo_attached_path ) ) {
					sleep( 0 );

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
					Dbg::error( 'Function get_warp_editor() returned an error: ' . $editor->get_error_message() );
					return $sizes;
				} else {
					if ( 'image/jpeg' === $this->my_mime_type && 'edited' !== $this->my_image_state ) {
						$rotated = $editor->maybe_exif_rotate();

						if ( \is_wp_error( $rotated ) ) {
							sleep( 0 );

						}
					}

					if ( \method_exists( $editor, 'make_subsize' ) ) {
						sleep( 0 );

						foreach ( $sizes as $new_size_name => $new_size_data ) {
							$new_size_meta = $editor->make_subsize( $new_size_data );

							if ( is_wp_error( $new_size_meta ) ) {
								sleep( 0 );

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
						sleep( 0 );

					} elseif ( \method_exists( $editor, 'multi_resize' ) ) {
						/** Clean, one-time update of metadata. Anyways, in case of fatal error or timeout,
						 * function wp_update_image_subsizes() won't be able to use right source ("edited").
						 * For each size, execution time is extended to prevent timeout. See class.
						 */

						sleep( 0 );

						$metadata['sizes'] = $editor->multi_resize( $sizes );
						$sizes_generated   = $metadata['sizes'];
						sleep( 0 );

						$this->my_updating_meta = true;
						\wp_update_attachment_metadata( $attachment_id, $metadata );
						$this->my_updating_meta = false;

						$this->my_metadata_done = $metadata;

					} else {
						Dbg::error( 'Methods multi_resize/make_subsize not found in $editor (' . get_class( $editor ) . ')' );
						return $sizes;
					}
				}

				foreach ( $metadata['sizes'] as $size_name => $size_data ) {
					if ( ! empty( $size_data['file'] ) ) {
						$tracked_files[ wp_basename( $size_data['file'] ) ] = null;
					}
				}

				update_post_meta( $attachment_id, '_warp_imagick_files', $tracked_files );

			}

			// phpcs:enable

			$clone_action = $this->do_generate_webp_clones();
			switch ( $clone_action ) {
				case 1:
					$clone_action = 2;

					break;
			}
			foreach ( $tracked_files as $collected_name => $ignore ) {
				$item_path = \path_join( dirname( $metadata['file'] ), $collected_name );
				$item_path = $this->get_absolute_upload_file_path( $item_path );
				$this->webp_clone_image( $item_path, $this->my_mime_type, $clone_action );
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
		 * Late priority (+99) will allow other hook overwrite RT plugin returned sizes.
		 *
		 * @param array  $metadata - attachment meta data.
		 * @param int    $attachment_id - number.
		 * @param string $context - caller context.
		 */
		public function on_wp_generate_attachment_metadata_99_filter( $metadata, $attachment_id = false, $context = false ) {
			sleep( 0 );

			// phpcs:ignore
			$trace_stack = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );
			$calls_stack = array();

			foreach ( array_reverse( $trace_stack ) as $item ) {
				if ( isset( $item ['function'] ) ) {
					$calls_stack[] = $item ['function'];
				}
			}

			$trace_stack = array();

			sleep( 0 );

			if ( 'create' !== $context ) {
				sleep( 0 );

				return $metadata;
			}

			$this->my_generate_meta = false;

			if ( is_array( $this->my_metadata_done ) ) {
				/** Support for Dominant Color Class Methods
				 * Support for Performance Lab Dominant Color Module.
				 * This plugin/code is expected to be compatible with Dominant Color,
				 * even after Dominant Color is merged into Core Class WP_Image_Editor.
				 * Because Warp_Image_Editor_Imagick will inherit methods from parent class.
				 */
				if ( ! array_key_exists( 'dominant_color', $this->my_metadata_done ) ) {
					if ( array_key_exists( 'dominant_color', $metadata ) ) {
						$this->my_metadata_done ['dominant_color'] = $metadata ['dominant_color'];
					}
				}

				if ( ! array_key_exists( 'has_transparency', $this->my_metadata_done ) ) {
					if ( array_key_exists( 'has_transparency', $metadata ) ) {
						$this->my_metadata_done ['has_transparency'] = $metadata ['has_transparency'];
					}
				}

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
						\wp_delete_file( $delete_webp );
					}
					/** Fall through */
				case 'image/png':
					$delete_webp = Shared::get_webp_file_name( $path );
					if ( \file_exists( $delete_webp ) ) {
						\wp_delete_file( $delete_webp );
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
				// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
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
						sleep( 0 );

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
				$return = $this->get_option( 'webp-images-create', Shared::webp_images_create_default() );

				if ( false === $return ) {
					$return = 0;
				}
				if ( true === $return ) {
					$return = 1;
				}
				switch ( $return ) {
					case 0:
					case 1:
					case 2:
					case 3:
						break;
					default:
						sleep( 0 );

						$return = 3;

				}
				return $return;
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
						sleep( 0 );

						break;
					}
				}
			}
			return $this->my_can_do_webp;
		}

		/** Create WebP Clone.
		 *
		 * @param string   $orig_path to clone.
		 * @param string   $mime_type of $orig_path.
		 * @param bool|int $do_generate_webp_clones status/choice.
		 */
		public function webp_clone_image( $orig_path, $mime_type = '', $do_generate_webp_clones = null ) {
			if ( ! is_string( $orig_path ) || empty( trim( $orig_path ) ) ) {
				return false;
			}

			$webp_path = Shared::get_webp_file_name( $orig_path );

			if ( null === $do_generate_webp_clones ) {
				$do_generate_webp_clones = $this->do_generate_webp_clones();
			}

			if ( false === $do_generate_webp_clones ) {
				$do_generate_webp_clones = 0;
			}
			if ( true === $do_generate_webp_clones ) {
				$do_generate_webp_clones = 1;
			}

			switch ( $do_generate_webp_clones ) {
				case 0:
					if ( \file_exists( $webp_path ) ) {
						\wp_delete_file( $webp_path );
					}
					return false;

				case 1:
					break;

				case 2:
					if ( \file_exists( $webp_path ) ) {
						return $webp_path;

					}
					break;

				case 3:
					return false;

				default:
					sleep( 0 );

					return false;

			}

			if ( false === $this->can_generate_webp_clones() ) {
				return false;
			}

			if ( ! is_readable( $orig_path ) ) {
				sleep( 0 );

				return false;
			}
			$orig_size = \filesize( $orig_path );

			if ( ! is_string( $mime_type ) || empty( trim( $mime_type ) ) ) {
				$mime_type = wp_get_image_mime( $orig_path );
			}

			switch ( $mime_type ) {
				case 'image/jpeg':
				case 'image/png':
					break;

				default:
					sleep( 0 );

					return false;
			}

			$webp_quality = $this->get_option( 'webp-compression-quality', Shared::webp_quality_default() );
			if ( $webp_quality > Shared::webp_quality_value_max() ) {
				$webp_quality = Shared::webp_quality_value_max();
			}
			if ( $webp_quality < Shared::webp_quality_value_min() ) {
				$webp_quality = Shared::webp_quality_value_min();
			}

			$gd_convert = false;

			$gd_jpeg      = false;
			$gd_png       = false;
			$gd_truecolor = false;

			switch ( $mime_type ) {
				case 'image/jpeg':
					$gd_jpeg = \imagecreatefromjpeg( $orig_path );
					if ( false === $gd_jpeg ) {
						sleep( 0 );

						break;

					}

					$use_quality = $this->get_option( 'webp-jpeg-compression-quality', Shared::webp_jpeg_quality_default() );
					$jpg_quality = $this->get_option( 'jpeg-compression-quality', Shared::jpeg_quality_default() );
					switch ( $use_quality ) {
						case -3:
							$webp_quality = $jpg_quality - 15;
							break;
						case -2:
							$webp_quality = $jpg_quality - 10;
							break;
						case -1:
							$webp_quality = $jpg_quality - 5;
							break;
						case 0:
							$webp_quality = $jpg_quality;
							break;
						default:
					}
					sleep( 0 );

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

						$gd_jpeg = false;

					}
					break;

				case 'image/png':
					$gd_png = \imagecreatefrompng( $orig_path );
					if ( false === $gd_png ) {
						sleep( 0 );

						break;

					}
					if ( \imageistruecolor( $gd_png ) ) {
						$gd_convert = $gd_png;

						$gd_png = false;

						break;

					}
					if ( function_exists( 'imagepalettetotruecolor' )
					&& \imagepalettetotruecolor( $gd_png ) ) {
						$gd_convert = $gd_png;

						$gd_png = false;

						break;

					}

					$gd_truecolor = \imagecreatetruecolor( \imagesx( $gd_png ), \imagesy( $gd_png ) );
					if ( false === $gd_truecolor ) {
						sleep( 0 );

						if ( false !== $gd_png ) {
							\imagedestroy( $gd_png );
							$gd_png = false;

						}
						break;

					}

					if ( false === \imagealphablending( $gd_truecolor, false ) ) {
						sleep( 0 );

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
						sleep( 0 );

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
						sleep( 0 );

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
					if ( false === \imagecopy( $gd_truecolor, $gd_png, 0, 0, 0, 0, \imagesx( $gd_png ), \imagesy( $gd_png ) ) ) {
						sleep( 0 );

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
						sleep( 0 );

						$gd_png = false;
					}
					if ( false !== $gd_truecolor ) {
						$gd_convert = $gd_truecolor;

						$gd_truecolor = false;

					}
					break;
			}

			if ( $gd_jpeg ) {
				sleep( 0 );

				$gd_jpeg = false;
			}

			if ( $gd_png ) {
				sleep( 0 );

				$gd_png = false;
			}

			if ( $gd_truecolor ) {
				sleep( 0 );

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
							// phpcs:ignore
							chmod( $webp_path, $perms );

							\imagedestroy( $gd_convert );
							$webp_size = \filesize( $webp_path );
							if ( $orig_size && $orig_size < $webp_size ) {
								sleep( 0 );

							}
							return $webp_path;
						} else {
							sleep( 0 );

						}
					} else {
						sleep( 0 );

					}
				} catch ( \Exception $e ) {
					Dbg::error( 'Exception caught: ' . $e->getMessage() );
					return false;
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
			if ( ! Dbg::is_debug() ) {
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
				function ( $actions, $post = null ) {
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
				function () {
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
				function () {
					if ( $this->is_raw_image_template_request() ) {
						\remove_all_actions( 'template_redirect' );
						\add_action(
							'template_redirect',
							function () {
								\remove_all_filters( 'template_include' );
								\add_filter(
									'template_include',
									function ( $template ) {
										$raw_image_template = $this->get_path() . '/templates/raw-image-template.php';
										if ( is_file( $raw_image_template ) ) {
											header( $this->get_slug() . ': template' );
											$template = $raw_image_template;
										} else {
											Dbg::error( 'Template file not found: ' . $raw_image_template );
											return false;
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

			if ( ! isset( $my_wp_query->post->post_mime_type ) || ! Hlp::starts_with( $my_wp_query->post->post_mime_type, 'image/' ) ) {
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

		/** Add endpoint Convert to WebP on demand if enabled */
		private function cwebp_endpoint_do_init() {
			if ( false === $this->get_option( 'webp-cwebp-on-demand' ) ) {
				return;
			}

			\add_rewrite_endpoint( 'cwebp-on-demand', EP_ROOT );
			\add_action(
				'wp',
				function () {
					if ( isset( $GLOBALS['wp_the_query']->query_vars['cwebp-on-demand'] ) ) {
						\remove_all_actions( 'template_redirect' );
						\add_action(
							'template_redirect',
							function () {
								\remove_all_filters( 'template_include' );
								\add_filter(
									'template_include',
									function ( $template ) {
										$cwebp_on_demand_template = $this->get_path() . '/templates/cwebp-on-demand-template.php';
										if ( is_file( $cwebp_on_demand_template ) ) {
											$template = $cwebp_on_demand_template;
										} else {
											Dbg::error( 'Template file not found: ' . $cwebp_on_demand_template );
											return false;
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
		private function perflab_upload_disable() {
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

		// phpcs:ignore
	# region static Admin notices

		/** Run-time error-admin-notice handler.
		 * Force error notice style.
		 *
		 * @param string $message to report.
		 */
		public static function echo_error_notice( $message = '' ) {
			if ( is_string( $message ) && trim( $message ) ) {
				self::echo_admin_notice( $message, 'notice notice-error is-dismissible' );
			}
		}

		/** Run-time admin-notice handler.
		 * Default style is info notice style.
		 *
		 * @param string $message to report.
		 * @param string $css_class css class.
		 */
		public static function echo_admin_notice( $message = '', $css_class = 'notice notice-info is-dismissible' ) {
			if ( $message && $css_class ) {
				echo '<div class="' . esc_attr( $css_class ) . '"><p style="white-space:pre"><strong>' . esc_html( $message ) . '</strong></p></div>';
			}
		}

		/** Transient (Short Persistent) Admin notice implementation.
		 * Persistent until once displayed or max 1 hour.
		 * Used by debug/debug_var/error notices.
		 *
		 * @todo? multi-record for concurrent admin users?
		 * @param string $message to display.
		 * @param string $class_name to implement (CSS).
		 */
		private static function t_notice( $message, $class_name ) {
			$transient_id = __NAMESPACE__ . '-notices';
			$transient    = get_transient( $transient_id );
			$notices      = is_string( $transient ) ? $transient : '';
			$notices     .= '<div class="' . esc_attr( $class_name ) . '"><p style="white-space:pre"><strong>' . esc_html( $message ) . '</strong></p></div>';
			set_transient( $transient_id, $notices, HOUR_IN_SECONDS );
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

		/** Show Transient admin notices.
		 * Every plugin has different namespace.
		 * This will show only transient messages
		 * for current plugin implementation namespace.
		 * Keep function here to match plugin namespace.
		 */
		public static function echo_transient_notices() {
			$transient_id = __NAMESPACE__ . '_notices';
			$transient    = get_transient( $transient_id );
			if ( false !== $transient ) {
				delete_transient( $transient_id );
				echo esc_html( $transient );

			}
		}

		// phpcs:ignore
	# endregion
	}
} else {
	Dbg::debug( "Class already exists: $class" );
}
