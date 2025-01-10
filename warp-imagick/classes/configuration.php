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

use ddur\Warp_iMagick\Shared;
use ddur\Warp_iMagick\Dbg;

return array(

	'plugin'          => array(

		'requires' => array(

			'wp'         => '5.3',

			'php'        => '7.4',

			'extensions' => array(
				'imagick' => 'PHP Imagick',
			),

			'classes'    => array(
				'\\Imagick' => 'PHP Imagick extension',
			),

			'functions'  => array(
				'\\getimagesize'               => 'Required PHP function',
				'\\wp_get_original_image_path' => 'WordPress 5.3+ required',
			),

			'constants'  => array(),

			'files'      => array(),
		),

		// phpcs:enable

		'metabox'  => array(
			'name' => __( 'Plugin Home Page', 'warp-imagick' ),

		),

		// phpcs:enable

	),
	'menu'            => array(
		'title'         => 'Warp iMagick',
		'menu-icon'     => 'dashicons-hammer',
		'parent-slug'   => 'upload.php',

		'position'      => 99,

		'settings-name' => __( 'Settings', 'warp-imagick' ),
		'settings-icon' => '⚙',

	),
	'page'            => array(
		'title'     => trim( 'Warp iMagick - Image Compressor ' . Shared::get_plugin_version() ),
		'subtitle'  => __( 'Current optimization settings will apply only to new media uploads.', 'warp-imagick' ),
		'help-tabs' => array(
			array(
				'id'      => 'overview',
				'title'   => __( 'Overview', 'warp-imagick' ),
				'content' => '
<h2>Compress JPEG PNG and generate WebP images.</h2>
<p>Reduce file size of WP generated JPEG and PNG thumbnails and sizes. Generate optimized WebP version of JPEG and PNG images.</p>
<p>When optimization or other setting are changed, use <a target=_blank rel="noopener noreferrer" href=https://wordpress.org/plugins/regenerate-thumbnails>Regenerate Thumbnails plugin</a> or <a target=_blank rel="noopener noreferrer" href="https://developer.wordpress.org/cli/commands/media/regenerate/">WP CLI media regenerate command]</a> to regenerate or batch-regenerate images with new settings.</p>
',
			),
			array(
				'id'      => 'webp-images',
				'title'   => __( 'WebP Converted Images', 'warp-imagick' ),
				'content' => '
<h2>Serving converted <a target=_blank rel="noopener noreferrer" href=https://developers.google.com/speed/webp>WebP</a> Images.</h2>
<p>Enable to generate optimized <a target=_blank rel="noopener noreferrer" href=https://en.wikipedia.org/wiki/Webp>WebP</a> versions for all media-attached JPEG/PNG images.</p>
<p>This settings may be disabled if your server\'s PHP software is not capable to generate WebP images.
<p>When enabled, each JPEG/PNG image subsize or thumbnail (including original upload) will have converted and optimized WebP version clone. Named with ".webp" extension appended at the end of original image file name. In example: "image-768x300.jpg.webp" will be found along with "image-768x300.jpg".</p>
<p>To serve WebP images, configure server to transparently serve WebP images to browsers supporting "image/webp" mime-type.</p>
<p>Configuring server to serve WebP images is not done by this plugin because change could potentially break your site. You will have to DIY (Do It Yourself).
<p>Below is Apache .htaccess configuration snippet that should work on most Apache servers. Snippet is tested on Apache 2.4 install on Linux Debian.</p>
<p><b>Modify your Apache .htaccess file safely using <a target=_blank rel="noopener noreferrer" href=https://wordpress.org/plugins/wp-htaccess-editor/ >Htaccess File Editor plugin</a>.</b><br>
If you use FTP or other ways, <b>always backup/save your original .htaccess file before applying changes!</b></p>
<p>Open .htaccess file (found in the site root) with editor of your choice.<br>
Copy code snippet (below) and paste it into your editor, at the top of /.htaccess file content.</p>
<button id="copy-to-clipboard">Copy to Clipboard</button>
<p style="background:#eaeaea;padding:5px">
<code id="htaccess-snippet" style="white-space:pre;color:DarkRed;background:none;padding:0"># BEGIN Warp-iMagick - First line of .htaccess file.
# Transparently serve WebP images instead of JPEG/PNG to WebP enabled browsers.

&lt;IfModule mod_mime.c&gt;
	AddType image/webp .webp
&lt;/IfModule&gt;
&lt;ifModule mod_rewrite.c&gt;
	RewriteEngine On
	RewriteBase /

	RewriteCond %{HTTP_ACCEPT} image/webp
	RewriteCond %{REQUEST_URI} /wp-content/
	RewriteCond %{REQUEST_URI} (.*)\.(?i)(jpe?g|png)$
	RewriteCond %{REQUEST_FILENAME} -f
	RewriteCond %{REQUEST_FILENAME}.webp -f
	RewriteRule .* %1\.%2.webp [T=image/webp,E=webp:1,L]

	&lt;IfModule mod_headers.c&gt
		Header append Vary Accept env=REDIRECT_webp
	&lt;/IfModule&gt

&lt;/IfModule&gt;
# END Warp-iMagick

</code>
</p>
<script>
document.getElementById("copy-to-clipboard")
  .onclick = function () {
	let text = document.getElementById("htaccess-snippet").textContent;
	navigator.clipboard.writeText(text)
	  .then(() => {
		alert(\'Code is in your clipboard.\');
	  })
	  .catch(err => {
		alert(\'Copy to clipboard failed: \', err);
	  });
  }
</script>
<p>Line "<code>AddType image/webp .webp</code>" will enable Apache to respond with Content-Type: "image/webp" for requested WebP images, regardless of rewrite rules below. Only if mod_mime is enabled and image/webp not already recognized by.</p>
<p>Line "<code>RewriteCond %{HTTP_ACCEPT} image/webp</code>" detects whether browser can accept WebP images or not.</p>
<p>Line "<code>RewriteCond %{REQUEST_URI} /wp-content/</code>" matches when WebP enabled browser request is inside /wp-content/ directory.</p>
<p>Line "<code>RewriteCond %{REQUEST_URI} (?i)(.*)\.(jpe?g|png)$</code>" matches when WebP enabled browser has requested for JPEG/PNG image.</p>
<p>Line "<code>RewriteCond %{DOCUMENT_ROOT}%1\.%2.webp -f</code>" tests if WebP version of JPEG/PNG image exists on the server file system.</p>
<p>Line "<code>RewriteRule (?i)(.*)\.(jpe?g|png)$ %1\.%2\.webp [T=image/webp,E=webp:1,L]</code>" will make Apache to respond with WebP image content, set environment variable and stop looking for other rewrite rules. Mime type "image/webp" is set regardless if mod_mime is enabled or not.</p>
<p>Line "<code>Header append Vary Accept env=REDIRECT_webp</code>" will inform your browser and/or CDN that content of JPEG/PNG image response may "vary" based on browser Accept: "content-type" header. Only if mod_headers is enabled. Your CDN may or may not cache response with Vary: "Accept" header.</p>
<p></p>
<p>To check if WebP rewrite rules are working after .htaccess modifications, refresh Settings Page and look at WebP Redirect Visual Test at Right column.</p>
<p></p>
<p>If you use <a target=_blank rel="noopener noreferrer" href=https://wordops.net/ >WordOps: Free WordPres sites Nginx stack and control CLI for VPS or Dedicated servers</a>, your <a target=_blank rel="noopener noreferrer" href=https://nginx.org/en/ >Nginx Server</a> is already configured to serve WebP converted clones instead original JPEG/PNG uploads & subsizes, but restricted to "wp-content/uploads/" directory only.</p>
<p>Instructions on "How to configure server to serve WebP images" for other http-servers, is not difficult to find on internet.</p>
<p>Good <b>Nginx</b> instructions: <a target=_blank rel="noopener noreferrer" href=https://github.com/uhop/grunt-tight-sprite/wiki/Recipe:-serve-WebP-with-nginx-conditionally>Recipe: serve WebP with nginx conditionally</a>.</p>',
			),
			array(
				'id'      => 'jpeg-reduction',
				'title'   => __( 'JPEG Reduction', 'warp-imagick' ),
				'content' => '
<h2>Reduce file size of JPEG images.</h2>
<p>Plugin default values are marked with \'*\'.</p>
<p>WordPress default JPEG compression quality is 82%.</p>
',
			),
			array(
				'id'      => 'png-reduction',
				'title'   => __( 'PNG Reduction', 'warp-imagick' ),
				'content' => '
<h2>Reduce file size of PNG images.</h2>
<p>Lossy compress by reducing number of colors.</p>
<p>Enable <a target=_blank rel="noopener noreferrer" href=https://en.wikipedia.org/wiki/Dither >Dither</a> to improve transition between colors. Disabled for transparent+palette images.</p>
<p>Configure maximum number of colors in the image. Images with number of colors less than maximum will not be quantized again.</p>
<p>Lossless compression is set to maximum (9). Every PNG image is tested with two default filter/strategy settings (WordPress/Imagick) and smaller file size is saved.</p>
',
			),
			array(
				'id'      => 'max-width',
				'title'   => __( 'Maximum Width', 'warp-imagick' ),
				'content' =>
				'
<h2>Limit maximum image width/height on upload.</h2>
<p>When enabled, JPEG/PNG images larger than maximum width (or height of 2500 px) will be reduced/downsized on upload.</p>
<p>Default maximum width/height value is 2500x2500 pixels. You may set it to lower if required.</p>
<p>Reducing is proportional, reduced image will have same aspect ratio as original.</p>
<p><b>Reducing is irreversible, still, you can disable limit and upload full size image again.</b></p>
',
			),
			array(
				'id'      => 'plugin',
				'title'   => __( 'Plugin Options', 'warp-imagick' ),
				'content' =>
				'
<h2>Remove settings on uninstall</h2>
<p>Set checkbox "on" to <b>remove</b> plugin settings when plugin is deleted.</p>
<p>Set checkbox "off" to <b>keep</b> plugin settings after plugin is deleted.</p>
<p><b>Defaults to "on" when plugin activated and no previous settings are stored.</b></p>
<h2>Select parent menu</h2>
<p>Select admin-menu position or admin-parent menu</p>

',
			),
		),
	),

	'capability'      => 'manage_options',

	'fields-extended' => array(
		'plugin-link'       => '',
		'plugin-link-title' => 'Warp WordSpeed Club',
	),

	'sections'        => array(

		'jpeg-thumb-options'          => array(
			'title'  => __( 'JPEG Sizes/Thumbs', 'warp-imagick' ),
			'fields' => array(

				'jpeg-compression-quality' => array(
					'label'   => __( 'Compression Quality', 'warp-imagick' ) . ' (' . Shared::jpeg_quality_default() . '%*)',
					'type'    => 'range',
					'style'   => 'width:200px',
					'title'   => __( 'Compression Quality in percentage. WordPress Default is 82%. For large or high details (difficult to compress) images or low quality and sharper JPEG settings, compression may result in smaller JPEG file size thumbnails than cloned WebP thumbnails.', 'warp-imagick' ),
					'default' => Shared::jpeg_quality_default(),
					'options' => array(
						'min'   => Shared::jpeg_quality_value_min(),
						'max'   => Shared::jpeg_quality_value_max(),
						'units' => __( '%', 'warp-imagick' ),
					),
				),

				'jpeg-sharpen-image'       => array(
					'label'   => __( 'Sharpen Full Size Thumbnail', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'default' => Shared::jpeg_sharpen_image_value_default(),
					// Translators: %s is default value.
					'title'   => sprintf( __( 'Sharpen Image after WP Default \'bluring\'. Select WP Default (unsharpMaskImage) or WP Default + SharpenImage( 0, $sigma ). Sharper image will be larger than WP default file size and and WebP clone on smallest thumbnails may be larger than JPEG image. WP default or small sigma recommended for full size. Default value is %s*.', 'warp-imagick' ), ( 0 === Shared::jpeg_sharpen_image_value_default() ? 'WP Default' : Shared::jpeg_sharpen_image_value_default() / 10 ) ),
					'options' => array(
						'source'   => 'callback',
						'callback' => 'get_form_sharpen_image',
					),
				),

				'jpeg-sharpen-thumbnails'  => array(
					'label'   => __( 'Sharpen Other Thumbnails', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'default' => Shared::jpeg_sharpen_thumbnails_value_default(),
					// Translators: %s is default value.
					'title'   => sprintf( __( 'Sharpen Thumbnails after WP Default \'bluring\'. Select WP Default (unsharpMaskImage) or WP Default + SharpenImage( 0, $sigma ). Sharper image will be larger than WP default file size and WebP clone on smallest thumbnails may be larger than JPEG image. Test to find your optimal $sigma or disable. Default value is %s*.', 'warp-imagick' ), ( 0 === Shared::jpeg_sharpen_image_value_default() ? 'WP Default' : Shared::jpeg_sharpen_image_value_default() / 10 ) ),
					'options' => array(
						'source'   => 'callback',
						'callback' => 'get_form_sharpen_thumbnails',
					),
				),

				// phpcs:enable

				'jpeg-colorspace'          => array(
					'label'   => __( 'Convert Colors to "sRGB" Colorspace', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'title'   => __( 'Convert image colors from other colorspaces to sRGB Colorspace or leave original image colorspace. Google recommends converting to sRGB colorspace. Colorspace sRGB allows removing all metadata, including important metadata like ICC profile.', 'warp-imagick' ),
					'default' => Shared::jpeg_colorspace_default(),
					'options' => array(
						'disabled' => ( defined( '\\Imagick::COLORSPACE_SRGB' ) ? false : true ),
						'source'   => 'callback',
						'callback' => 'get_form_jpeg_colorspaces',
					),
				),

				'jpeg-colorspace-force'    => array(
					'label'   => __( 'Test Option: Convert from Undefined Colorspace', 'warp-imagick' ),
					'type'    => 'checkbox',
					'style'   => 'width:200px',
					'title'   => __( 'When colorspace conversion enabled, allow convert from undefined colorspace. Default is OFF (not allowed).', 'warp-imagick' ),
					'default' => false,
					'options' => array(
						'disabled' => ( defined( '\\Imagick::COLORSPACE_UNDEFINED' ) ? false : true ),
					),
				),

				'jpeg-sampling-factor'     => array(
					'label'   => __( 'Color Sampling factor', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'title'   => __( 'Reduce image file size by selecting color sampling factor (lossy color compression). Choose from smallest file size to best color resolution. Default value is "4:2:0" as recommended by Google.', 'warp-imagick' ),
					'default' => Shared::jpeg_sampling_factor_default(),
					'options' => array(
						'source'   => 'callback',
						'callback' => 'get_form_jpeg_sampling_factors',
					),
				),

				'jpeg-strip-meta'          => array(
					'label'   => __( 'Strip meta data', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'default' => Shared::jpeg_strip_meta_default(),
					'title'   => __( 'By default, WordPress strips most of metadata except protected profiles (Preserve Important). "Strip All Metadata" applies only if image colorspace is sRGB, else "Preserve Important" is used. Default value is "Strip All Metadata".', 'warp-imagick' ),
					'options' => array(
						'source'   => 'callback',
						'callback' => 'get_form_strip_metadata',
					),
				),

				'jpeg-interlace-scheme'    => array(
					'label'   => __( 'Interlace scheme', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'default' => Shared::jpeg_interlace_scheme_default(),

					'title'   => __( 'Interlace scheme WP/ON/OFF or AUTO to try both and select smaller file size. Default value is "AUTO".', 'warp-imagick' ),
					'options' => array(
						'source'   => 'callback',
						'callback' => 'get_form_jpeg_interlace_types',
					),
				),
			),
		),

		'png-image-options'           => array(
			'title'  => __( 'PNG Sizes/Thumbs', 'warp-imagick' ),
			'render' => 'render_png_thumb_options',
			'fields' => array(

				// phpcs:enable

				'png-reduce-colors-enable'    => array(
					'label'   => __( 'Reduce Colors', 'warp-imagick' ),
					'type'    => 'checkbox',
					'default' => Shared::png_reduce_colors_enabled_default(),
					'title'   => __( 'Lossy Compression. When enabled, colors will be reduced to maximum number of colors (see below). Default value is on (true)', 'warp-imagick' ),
					'options' => array(
						'disabled' => ( defined( '\\Imagick::IMGTYPE_PALETTE' ) ? false : true ),
					),
				),

				'png-reduce-colors-dither'    => array(
					'label'   => __( 'Dither Colors', 'warp-imagick' ),
					'type'    => 'checkbox',
					'default' => Shared::png_reduce_colors_dither_default(),
					'title'   => __( 'Color Compensation. When enabled, dither to improve color transients (see https://en.wikipedia.org/wiki/Dither). File size will increase when enabled. Disabled on transparent images when max colors is 256 or less. Default value is off (false).', 'warp-imagick' ),
				),

				'png-reduce-max-colors-count' => array(
					'label'   => __( 'Maximum Colors', 'warp-imagick' ) . ' (' . Shared::png_max_colors_value_default() . '*)',
					'type'    => 'range',
					'style'   => 'width:200px',
					'default' => Shared::png_max_colors_value_default(),
					'title'   => __( 'Lossy Compression. If image has more colors than Maximum Colors, number of colors will be reduced down. If number of colors is less than or equal to 256, image colors will be converted to palette. File size and color quality will increase with more colors', 'warp-imagick' ),
					'options' => array(
						'min'   => Shared::png_max_colors_value_min(),
						'max'   => Shared::png_max_colors_value_max(),
						'step'  => Shared::png_max_colors_value_min(),
						'units' => __( 'colors', 'warp-imagick' ),
					),
				),

				'png-strip-meta'              => array(
					'label'   => __( 'Strip meta data', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'default' => Shared::png_strip_meta_default(),
					'title'   => __( 'WordPress by default strips most of metadata except protected profiles. Default value is "Strip All".', 'warp-imagick' ),
					'options' => array(
						'source'   => 'callback',
						'callback' => 'get_form_strip_metadata',
					),
				),
			),
		),

		'webp-image-options'          => array(
			'title'  => __( 'WebP JPEG/PNG clones', 'warp-imagick' ),
			'render' => 'render_webp_thumb_options',
			'fields' => array(
				'webp-images-create'            => array(
					'label'   => __( 'Generate WebP Images', 'warp-imagick' ),
					'type'    => 'checkbox',
					'default' => Shared::webp_images_create_default(),
					'title'   => __( 'If enabled, for every JPEG/PNG media image/thumbnail, a WebP clone (converted copy) will be added. See Help Tab at the right-top of the page, section "WebP Images".', 'warp-imagick' ),
					'options' => array(
						'disabled' => ! Shared::can_generate_webp_clones(),
					),
				),
				'webp-compression-quality'      => array(
					'label'   => __( 'WebP Compression Quality', 'warp-imagick' ) . ' (' . Shared::webp_quality_default() . '*)',
					'type'    => 'range',
					'style'   => 'width:200px',
					'default' => Shared::webp_quality_default(),
					// Translators: %s is default compression quality value.
					'title'   => sprintf( __( 'Applies to WebP image clones when converted from PNG & JPEG images. This value can be overridden when converting from JPEG (see JPEG to WebP compression quality below). This is a Lossy Compression. Default value is %s. Default cwebp command value is 75. Max value to pass Google Page Speed Test is 75+10%% (about 82%%). For large or high details (difficult to compress) images or low quality and sharper JPEG settings, compression may result in smaller JPEG file size thumbnails than cloned WebP thumbnails. If you want WebP thumbnail file-sizes always smaller than JPEG thumbnails, set WebP compression quality lower than JPEG compression quality.', 'warp-imagick' ), Shared::webp_quality_default() ),
					'options' => array(
						'min'   => Shared::webp_quality_value_min(),
						'max'   => Shared::webp_quality_value_max(),
						'step'  => 1,
						'units' => __( '%', 'warp-imagick' ),
					),
				),
				'webp-jpeg-compression-quality' => array(
					'label'   => __( 'WebP Compression Quality for JPEG images.', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					// Translators: %s is maximal WebP compression quality value.
					'title'   => sprintf( __( 'Applies to WebP compression quality when converting from JPEG to WebP image clone. Use JPEG settings compression quality or default WebP compression quality (see above settings). Using JPEG settings may sometimes result in larger WebP image file sizes than matching JPEG source image file size. This is a Lossy Compression. Default value is to use JPEG compression quality (up to %s%%). For large or high details (difficult to compress) images or low quality and sharper JPEG settings, compression may result in smaller JPEG file size thumbnails than cloned WebP thumbnails. If you want WebP thumbnail file-sizes always smaller than JPEG thumbnails, set WebP compression quality lower than JPEG compression quality.', 'warp-imagick' ), Shared::webp_quality_value_max() ),
					'default' => Shared::webp_jpeg_quality_default(),
					'options' => array(
						'source'   => 'callback',
						'callback' => 'get_form_jpeg_to_webp_compression_quality',
					),
				),
				'webp-cwebp-on-demand'          => array(
					'label'   => __( 'Convert to WebP On Demand', 'warp-imagick' ),
					'type'    => Shared::webp_cwebp_on_demand_type(),
					'default' => Shared::webp_cwebp_on_demand_default(),
					'title'   => __( 'When this is enabled, missing WebP Clones will be created "on-the-fly" from JPEG/PNG images when requested by WebP enabled browser (On Demand), except if image is in /wp-admin/ or /wp-includes/. See Help Tab at the right-top of the page, section "WebP Images" for "how to serve instructions".', 'warp-imagick' ),
					'options' => array(
						'disabled' => ! Shared::can_generate_webp_clones(),
					),
				),
			),
		),

		'upload-actions-section'      => array(
			'title'  => __( 'Upload Actions', 'warp-imagick' ),

			'fields' => array(

				'image-max-width-enabled' => array(
					'label'   => __( 'Resize too large images (recommended value on*)', 'warp-imagick' ),
					'type'    => 'checkbox',
					'default' => Shared::max_width_enabled_default(),
					// Translators: %s are configurable max size limit range min & max value.
					'title'   => sprintf( __( 'When enabled (= default and recommended value), images wider than maximum width limit (%1$s-%2$s) and/or taller than 2500 pixels will be proportionally downsized to best fit to maximum width and height. Downsizing is executed on image upload. Downsized image will replace "original" and save your disk space. When image upload post processing fails, WordPress suggests to upload image sizes up to 2500 pixels. Default value is ON.', 'warp-imagick' ), Shared::max_width_value_min(), Shared::max_width_value_max() ),
				),

				'image-max-width-pixels'  => array(
					'label'   => __( 'Maximum image width', 'warp-imagick' ),
					'type'    => 'range',
					'style'   => 'width:200px',
					'default' => Shared::max_width_value_default(),
					'title'   => __( 'Maximum image width limit in pixels. Images wider than maximum width/height limit will be proportionally downsized to best fit to (contain) maximum width given here and to maximum height of 2500 pixels. Downsized image will become new "original" and save your disk space. When image upload post processing fails, WordPress suggests to upload image sizes up to 2500 pixels anyways. Default value is 2500 pixels.', 'warp-imagick' ),
					'options' => array(
						'min'   => Shared::max_width_value_min(),
						'max'   => Shared::max_width_value_max(),
						'step'  => Shared::max_width_value_step(),
						'units' => __( 'pixels', 'warp-imagick' ),
					),
				),
			),
		),

		'generate-thumbnails-section' => array(
			'title'  => __( 'Generate Sizes/Thumbnails', 'warp-imagick' ),
			'fields' => array(
				'wp-big-image-size-threshold-disabled' => array(
					'label'   => __( 'Disable "BIG Image Size Threshold" filter (recommended value on*)', 'warp-imagick' ),
					'type'    => 'checkbox',
					'default' => Shared::big_image_size_threshold_disabled_default(),
					'title'   => __( 'When checked (= disable, default and recommended value), prevents BIG JPEG image to be downsized and reduced to thumbnail quality. WordPress (version 5.3+) "Big Image Size Threshold" filter defaults to 2560x2560 pixels.', 'warp-imagick' ),
					'options' => array(),
				),
				'wp-big-image-size-threshold-value'    => array(
					'label'   => __( 'Set "BIG Image Size Threshold" value.', 'warp-imagick' ),
					'type'    => 'range',
					'style'   => 'width:200px',
					'default' => Shared::big_image_size_threshold_value_default(),
					// Translators: %s is threshold default value.
					'title'   => sprintf( __( 'BIG Image Size Threshold in Pixels. Original images wider or higher than maximal threshold will be proportionally resized to maximal width and height given here. Resized image will be renamed to "-scaled" and will become attached (default) image. Generated sizes/thumbnails will still be created from large "original" image. Threshold value larger than 2560 pixels may crash PHP-imagick due to imposed resource limits. Default threshold value is %s pixels for both width and height. This option will not save your disk space because original big image is preserved.', 'warp-imagick' ), Shared::big_image_size_threshold_value_default() ),
					'options' => array(
						'min'   => Shared::big_image_size_threshold_value_min(),
						'max'   => Shared::big_image_size_threshold_value_max(),
						'step'  => 8,
						'units' => __( 'pixels', 'warp-imagick' ),
					),
				),
				'wp-maybe-exif-rotate-disabled'        => array(
					'label'   => __( 'Disable JPEG image (maybe) rotate introduced in WordPress 5.3.', 'warp-imagick' ),
					'type'    => 'checkbox',
					'default' => Shared::maybe_exif_rotate_disabled_default(),
					'title'   => __( 'Enabled/Allowed by default (off). When checked (on), disables WordPress (version 5.3+) JPEG image (maybe) rotate.', 'warp-imagick' ),
					'options' => array(),
				),
				'compress-jpeg-original-disabled'      => array(
					'label'   => __( 'Disable compressing original JPEG image to same size/thumbnail.', 'warp-imagick' ),
					'type'    => 'checkbox',
					'default' => Shared::compress_jpeg_original_disabled_default(),
					'title'   => __( 'Enabled/Allowed by default (off). When checked (on), disables automatic compressing JPEG original image to same size/thumbnail, introduced in version 1.3.', 'warp-imagick' ),
					'options' => array(),
				),
			),
		),

		// phpcs:enable

		'plugin-options'              => array(
			'title'  => __( 'Plugin Settings', 'warp-imagick' ),
			'fields' => array(

				'plugin-app-update-hostname' => array(
					'label'   => __( 'Plugin Update Hostname', 'warp-imagick' ),
					'type'    => 'text',
					'class'   => 'code',
					'style'   => 'width:19em;color:darkred',
					'default' => wp_parse_url( home_url(), PHP_URL_HOST ),
					'title'   => __( 'Select & copy this Hostname, go to Update Server (https://warp-imagick.pagespeed.club/user/) (register and) login. Paste your Hostname into "Application Name" field. Press "Add New Application Password" button and you will be presented with your new password. Then select & copy new password and paste it back here into "Plugin Update Password" field below.', 'warp-imagick' ),
					'options' => array(
						'readonly' => true,
					),
				),

				'plugin-app-update-password' => array(
					'label'       => __( 'Plugin Update Password', 'warp-imagick' ),
					'type'        => 'text',
					'class'       => 'code',
					'style'       => 'width:19em;color:darkblue',
					'default'     => '',
					'title'       => __( 'Paste update password from your User Profile at Update Server (https://warp-imagick.pagespeed.club/user/) as described in Hostname field above.', 'warp-imagick' ),
					'placeholder' => 'xxxx xxxx xxxx xxxx xxxx xxxx',
				),

				'menu-parent-slug'           => array(
					'label'   => __( 'Select parent menu', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'default' => Shared::menu_parent_slug_default(),
					'title'   => __( 'Select admin-menu position or admin-parent menu', 'warp-imagick' ),
					'options' => array(
						'source' => 'values',
						'values' => array(
							''                    => 'Default',
							1                     => 'Top',
							'index.php'           => 'Dashboard',
							'upload.php'          => 'Media',
							'tools.php'           => 'Tools',
							'options-general.php' => 'Settings',
							99                    => 'Bottom',
						),
					),
				),

				'remove-settings'            => array(
					'label'   => __( 'Remove settings on uninstall', 'warp-imagick' ),
					'type'    => 'checkbox',
					'style'   => 'width:200px',
					'default' => Shared::remove_plugin_settings_default(),
					'title'   => __( 'Remove plugin settings when plugin is uninstalled (and deleted)', 'warp-imagick' ),
				),

				'disable-img-test-metabox'   => array(
					'label'   => __( 'Disable WebP Redirect Visual Test.', 'warp-imagick' ),
					'type'    => 'checkbox',
					'style'   => 'width:200px',
					'default' => false,
					'title'   => __( 'Disable WebP Redirect Visual Test (MetaBox on right Sidebar).', 'warp-imagick' ),
				),

				'verbose-debug-enabled'      => array(
					'label'   => __( 'Verbose Debug', 'warp-imagick' ),
					'type'    => Dbg::is_debug() ? 'checkbox' : 'hidden',
					'style'   => 'width:200px',
					'default' => Shared::verbose_debug_enabled_value_default(),
					'title'   => __( 'Allow all debug messages', 'warp-imagick' ),
				),
			),
		),

		'sizes-conf'                  => array(
			'title'  => __( 'Subsizes', 'warp-imagick' ),
			'render' => 'render_section_image_sizes',
			'submit' => false,
			'fields' => array(),
		),

		'terms-of-use'                => array(
			'title'  => __( 'Copyright, License, Privacy and Disclaimer', 'warp-imagick' ),
			'render' => 'render_section_terms',
			'submit' => false,
			'fields' => array(),
		),
	),

	'tabs'            => array(
		'main-options' => array(
			'title'    => 'Compress Settings',
			'sections' => 3,
		),
		'conf-options' => array(
			'title'    => 'General Settings',
			'sections' => 3,
		),
		'site-conf'    => array(
			'title'    => 'Site Configuration',
			'sections' => 1,
			'submit'   => false,
		),
		'terms-of-use' => array(
			'title'    => 'Terms of Use',
			'sections' => 1,
			'submit'   => false,
		),
	),
);
