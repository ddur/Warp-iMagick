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

use ddur\Warp_iMagick\Dbg;

$call_name = __NAMESPACE__ . '\\Base_Settings_Renderer';

if ( ! class_exists( $call_name ) ) {
	/** Render settings base class. */
	abstract class Base_Settings_Renderer {
		/** Plugin instance.
		 *
		 * @var object $plugin object instance.
		 */
		protected $plugin;

		/** Plugin prefix.
		 *
		 * @var string $prefix of plugin.
		 */
		protected $prefix;

		/** Plugin settings instance.
		 *
		 * @var object $settings object instance.
		 */
		protected $settings;

		/** Plugin Settings Page Slug.
		 *
		 * @var string $pageslug of plugin settings.
		 */
		protected $pageslug;

		/** Class constructor method.
		 *
		 * @access public
		 * @param object $settings instance.
		 */
		public function __construct( $settings ) {
			$this->settings = $settings;
			$this->plugin   = $settings->get_plugin();
			$this->prefix   = $this->plugin->get_prefix();
			$this->pageslug = $this->plugin->get_slug();
		}

		/** Render hard (fixed not-re/movable) Meta-Box.
		 *
		 * @param string $image_lnk pointing to url of plugin logo-image.
		 * @param string $click_lnk pointing to url of plugin site.
		 * @param string $box_title of "meta box".
		 * @param string $id of "meta box" container.
		 * @param string $css_class of "meta box" image element.
		 * @param string $delay of "meta box" jQuery slide down.
		 */
		protected function render_hard_meta_box( $image_lnk, $click_lnk, $box_title, $id = '', $css_class = 'rotate', $delay = 10000 ) {
			$id = trim( $id ) ? $id : $this->pageslug;
			echo '<div id=' . esc_attr( $id ) . '-fixed-meta-box class=postbox style=display:none;overflow:hidden>';
			echo '<h2 style="border-bottom: 1px solid #eee;text-align:center"><span>' . esc_html( $box_title ) . '</span></h2>';
			echo '<a target="_blank" rel="noopener noreferrer" href="' . esc_url_raw( $click_lnk ) . '">';
			echo '<img src="' . esc_url_raw( $image_lnk ) . '" class="' . esc_attr( $css_class ) . '" style="max-width:100%;max-height:100%;min-width:100%;min-height:100%">';
			echo '</a></div>';
			echo '<script>(function ($){$("#' . esc_attr( $id ) . '-fixed-meta-box").delay(' . intval( $delay ) . ').slideDown(1000);}(jQuery));</script>';
		}

		/** Render hard (fixed not-re/movable) Meta-Box.
		 *
		 * @param string $id meta-box  element html-id.
		 * @param string $title meta-box title.
		 * @param string $tooltip of meta-box.
		 * @param string $click_link to remote URL.
		 * @param string $img_png_src source/url_path to PNG test image.
		 * @param string $img_jpg_src source/url_path to JPG test image.
		 */
		protected function render_test_meta_box( $id, $title, $tooltip, $click_link, $img_png_src, $img_jpg_src ) {
			echo '<div id=' . esc_attr( $id ) . '-fixed-meta-box class=postbox style="overflow:hidden;cursor:help" title="' . esc_attr( $tooltip ) . '">';
			echo '<h2 style="border-bottom: 1px solid #eee;text-align:center"><span>' . esc_html( $title ) . '</span></h2>';
			echo '<a target=_blank rel="noopener noreferrer" href="' . esc_attr( $click_link ) . '">';
			echo '<img src="' . esc_attr( $img_png_src ) . '" style="box-sizing:border-box;border-width:2px 1px 2px 2px;border-style:solid;border-color:black;max-width:50%;max-height:100%;min-width:50%;min-height:100%">';
			echo '<img src="' . esc_attr( $img_jpg_src ) . '" style="box-sizing:border-box;border-width:2px 2px 2px 1px;border-style:solid;border-color:black;max-width:50%;max-height:100%;min-width:50%;min-height:100%">';
			echo '</a></div>';
		}
	}
} else {
	Dbg::debug( "Class already exists: $call_name" );
}
