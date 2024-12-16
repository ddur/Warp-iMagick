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

use ddur\Warp_iMagick\Base\Meta_Settings_Renderer;
use ddur\Warp_iMagick\Base\Plugin\v1\Lib;
use ddur\Warp_iMagick\Shared;

$class = __NAMESPACE__ . '\\Settings_Renderer';

if ( ! class_exists( $class ) ) {
	/** Admin-Setting page renderer (separated rendering code) */
	class Settings_Renderer extends Meta_Settings_Renderer {
		/** Relative path/name of copytight notice file
		 *
		 * @var string $copyright_notice contains relative path_name.
		 */
		private $copyright_notice = '/docs/copyright-notice.php';

		/** Render Sidebar (Logo). */
		public function render_settings_page_sidebar() {
			if ( ! Shared::get_option( 'disable-img-test-metabox' ) ) {
				$root_path = wp_normalize_path( untrailingslashit( ABSPATH ) );
				$directory = path_join( wp_get_upload_dir()['basedir'], $this->pageslug );
				if ( file_exists( $directory . '/test-webp.png' )
					&& file_exists( $directory . '/test-webp.jpg' )
					&& file_exists( $directory . '/test-webp.png.webp' )
					&& file_exists( $directory . '/test-webp.jpg.webp' )
				) {
					$id          = 'visual-redirect-test';
					$title       = __( 'WebP Redirect Visual Test', 'warp-imagick' );
					$tooltip     = __( 'Original png/jpg images have red background, WebP Clones have green background. If you see images with green background color, your server WebP redirection is configured and you may disable this MetaBox in Plugin Settings section. If you see images with the red background, first hard refresh your browser (Hold down Ctrl and press F5 or Reload Button) because images with red background may be still in your browser memory cache. After browser refresh, if you still see images with the red background, either your browser is not supporting WebP image format or your server WebP Redirection is not yet configured. To read instructions about configuring your server after installing Warp iMagick plugin, click on images with red background.', 'warp-imagick' );
					$click_link  = 'https://github.com/ddur/Warp-iMagick/#post-install-apache-server-webp-configuration';
					$clones_url  = site_url( substr( $directory, strlen( $root_path ) ) );
					$img_png_src = $clones_url . '/test-webp.png';
					$img_jpg_src = $clones_url . '/test-webp.jpg';
					$this->render_test_meta_box( $id, $title, $tooltip, $click_link, $img_png_src, $img_jpg_src );
				}
			}

			$pluginbox = Lib::safe_key_value( $this->settings->get_settings(), array( 'plugin', 'metabox' ), array() );
			$image_lnk = Lib::safe_key_value( $pluginbox, 'logo', $this->plugin->get_url_path() . '\/assets/warp-logo.png' );
			$click_lnk = Lib::safe_key_value( $pluginbox, 'link', 'https://warp-imagick.pagespeed.club/' );
			$box_title = Lib::safe_key_value( $pluginbox, 'name', wp_parse_url( $click_lnk, PHP_URL_HOST ) );
			$this->render_hard_meta_box( $image_lnk, $click_lnk, $box_title, 'logo' );

			// phpcs:enable
		}

		/** Render Settings Page Subtitle.
		 *
		 * Redirected here from "on_render_settings_page" event handler.
		 */
		public function render_page_subtitle() {
			echo '<div>' . esc_html( Lib::safe_key_value( $this->settings->get_settings(), array( 'page', 'subtitle' ), '' ) ) . '</div>';

			Lib::echo_html( Lib::safe_key_value( $this->settings->get_settings(), array( 'page', 'sub-html' ), '' ) );

			echo '<div>' . esc_html( __( 'To apply current settings to previously uploaded JPEG/PNG media, use ', 'warp-imagick' ) ) . '<a target=_blank rel="noopener noreferrer" href=https://wordpress.org/plugins/regenerate-thumbnails>Regenerate Thumbnails plugin</a> or <a target=_blank rel="noopener noreferrer" href="https://developer.wordpress.org/cli/commands/media/regenerate/">WP CLI media regenerate command</a>.</div>';

			if ( ! class_exists( '\\RegenerateThumbnails' ) ) {
				echo '<div><a href="' . esc_url_raw(
					add_query_arg(
						array(
							's'    => 'regenerate thumbnails',
							'tab'  => 'search',
							'type' => 'term',
						),
						admin_url( 'plugin-install.php' )
					)
				) . '">' . esc_html( __( 'Click here to find, install and/or activate "Regenerate Thumbnails" plugin.', 'warp-imagick' ) ) . '</a></div>';
			}

			$github_issuess     = __( 'GitHub Issues', 'warp-imagick' );
			$github_discussions = __( 'GitHub Discussions', 'warp-imagick' );
			echo '<p></p><div>' . esc_html( __( 'If you have any problem, question or idea, please visit ', 'warp-imagick' ) );
			printf( '<a target=_blank rel="noopener noreferrer" href="https://github.com/ddur/Warp-iMagick/issues">%s</a> or <a target=_blank rel="noopener noreferrer" href="https://github.com/ddur/Warp-iMagick/discussions?discussions_q=">%s</a>.</div>', esc_html( $github_issuess ), esc_html( $github_discussions ) );
			echo '<p></p><div>' . esc_html( __( 'For older issues, questions and answers, you may also browse a', 'warp-imagick' ) ) . ' ' . sprintf( '<a target=_blank rel="noopener noreferrer" href="https://wordpress.org/support/plugin/warp-imagick"/>%s</a>', esc_html( __( 'WordPress Support Forum', 'warp-imagick' ) ) ) . '.</div>';
		}

		/** Section Terms renderer.
		 *
		 * @access public
		 */
		public function render_section_terms() {
			$copyright_notice = $this->plugin->get_path();
			if ( property_exists( $this, 'copyright_notice' ) ) {
				$copyright_notice .= $this->copyright_notice;
			} else {
				$copyright_notice .= '/docs/copyright-notice.php';
			}
			if ( file_exists( $copyright_notice ) ) {
				require $copyright_notice;
			}
		}

		/** Section max width renderer.
		 *
		 * @access public
		 */
		public function render_section_max_width() {
			$sizes_max_width      = 0;
			$sizes_max_width_name = '';
			$sizes_max_width_from = 'WordPress';

			foreach ( get_intermediate_image_sizes() as $size_name ) {
				$current_width = intval( get_option( "{$size_name}_size_w" ) );
				if ( $current_width > $sizes_max_width ) {
					$sizes_max_width      = $current_width;
					$sizes_max_width_name = $size_name;
				}
			}

			foreach ( wp_get_additional_image_sizes() as $size_name => $sizes ) {
				$current_width = $sizes ['width'];
				if ( $current_width > $sizes_max_width ) {
					$sizes_max_width      = $current_width;
					$sizes_max_width_name = trim( $size_name );
					switch ( $sizes_max_width_name ) {
						case '1536x1536':
						case '2048x2048':
							$sizes_max_width_from = 'WordPress (media.php)';
							break;
						default:
							$sizes_max_width_from = 'Theme';
							break;
					}
				}
			}

			if ( $sizes_max_width ) {
				?>
<span style="white-space:pre-line;color:DarkRed"><b>
Widest image size found is <?php echo esc_html( $sizes_max_width ); ?> pixels, size name "<?php echo esc_html( $sizes_max_width_name ); ?>", defined by <?php echo esc_html( $sizes_max_width_from ); ?>.
Widest image size used in responsive "srcset" is 2048 pixels (as seen in WordPress 5.3.2).
</b></span>
				<?php
			}
		}

		/** Section Image Extra Sizes.
		 *
		 * @access public
		 */
		public function render_section_image_sizes() {
			$sizes_max_width      = 0;
			$sizes_max_width_name = '';
			$all_sizes            = array();

			foreach ( get_intermediate_image_sizes() as $size_name ) {
				$all_sizes [ $size_name ] = array( intval( get_option( "{$size_name}_size_w" ) ), intval( get_option( "{$size_name}_size_h" ) ), intval( get_option( "{$size_name}_crop" ) ) );
			}
			foreach ( wp_get_additional_image_sizes() as $size_name => $size_data ) {
				$all_sizes [ $size_name ] = array( intval( $size_data['width'] ), intval( $size_data['height'] ), intval( $size_data['crop'] ) );
			}

			$sort_sizes = array();
			foreach ( $all_sizes as $size_name => $size_data ) {
				$sort_sizes [ $size_data[0] ] = array( $size_name => $size_data );
			}
			ksort( $sort_sizes );
			$all_sizes = array();
			foreach ( $sort_sizes as $sort_size ) {
				foreach ( $sort_size as $size_name => $size_data ) {
					$all_sizes [ $size_name ] = $size_data;
				}
			}

			$message = '';
			foreach ( $all_sizes as $size_name => $size_data ) {
				$message .= str_pad( $size_name, 40 ) . ' - width: ' . $size_data[0] . ', height: ' . $size_data[1];
				$message .= ', ' . ( $size_data[2] ? 'cropped' : 'proportional' );
				$message .= PHP_EOL;
			}
			?>
<div style="white-space:pre;color:DarkRed;font-family:monospace,monospace"><?php echo esc_html( $message ); ?>
</div>
			<?php
		}

		/** Section PNG Thumbs options.
		 *
		 * Warn if Imagick is not capable to quantize (PNG) colors.
		 *
		 * @access public
		 */
		public function render_png_thumb_options() {
			$magic_test = false;
			if ( class_exists( '\\Imagick' ) ) {
				try {
					$magic_test = new \Imagick();
				} catch ( \Exception $e ) {
					$magic_test = false;
				}
			}

			$warning_message = '';

			if ( $magic_test ) {
				if ( ! is_callable( array( $magic_test, 'getImageColors' ) ) ) {
					$warning_message .= PHP_EOL . 'Imagick::getImageColors function is not available.';
				}
				if ( ! is_callable( array( $magic_test, 'getImageType' ) ) ) {
					$warning_message .= PHP_EOL . 'Imagick::getImageType function is not available.';
				}
				if ( ! is_callable( array( $magic_test, 'setImageType' ) ) ) {
					$warning_message .= PHP_EOL . 'Imagick::setImageType function is not available.';
				}
				if ( ! is_callable( array( $magic_test, 'quantizeImage' ) ) ) {
					$warning_message .= PHP_EOL . 'Imagick::quantizeImage function is not available.';
				}
				if ( ! is_callable( array( $magic_test, 'posterizeImage' ) ) ) {
					$warning_message .= PHP_EOL . 'Imagick::posterizeImage function is not available.';
				}
			} else {
				$warning_message = 'PHP-Imagick module is not available.';
			}

			if ( $magic_test ) {
				$magic_test->clear();
				$magic_test->destroy();
				$magic_test = null;
			}

			if ( trim( $warning_message ) ) {
				?>
<span style="white-space:pre-line;color:DarkRed"><b>
				<?php echo esc_html( $warning_message ); ?>
</b></span>
				<?php
			}
		}

		/** Section WEBP Thumbs options.
		 *
		 * Warn if GD is not present or not capable to generate webp thumbnails.
		 *
		 * @access public
		 */
		public function render_webp_thumb_options() {
			if ( ! function_exists( '\\imagewebp' ) ) {
				if ( Lib::is_gd_available() ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
Function that generates WebP images is not available in your PHP-GD extension.
</b></span>
					<?php
				} else {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD extension is not available.
</b></span>
					<?php
				}
			}
			if ( Lib::is_gd_available() ) {
				if ( ! function_exists( '\\imagecreatefromjpeg' ) ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD extension cannot read JPEG image files.
</b></span>
					<?php
				}
				if ( ! function_exists( '\\imagecreatefrompng' ) ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD extension cannot read PNG image files.
</b></span>
					<?php
				}
				if ( ! function_exists( '\\imagesavealpha' ) ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD function missing: imagesavealpha.
</b></span>
					<?php
				}
				if ( ! function_exists( '\\imagealphablending' ) ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD function missing: imagealphablending.
</b></span>
					<?php
				}
				if ( ! function_exists( '\\imageistruecolor' ) ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD function missing: imageistruecolor.
</b></span>
					<?php
				}
				if ( ! function_exists( '\\imagepalettetotruecolor' ) ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD function missing: imagepalettetotruecolor.
</b></span>
					<?php
				}
			}
		}
	}
} else {
	Shared::debug( "Class already exists: $class" );
}
