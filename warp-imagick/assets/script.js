/**!
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

(function ($) {
	if (typeof $ === 'function') {
		$(
			function () {
				var page_slug = document.getElementById( 'settings-page' ).dataset.page;

				var $png_reduce_colors_enable    = $( '#' + page_slug + '-png-reduce-colors-enable' );
				var $png_reduce_colors_dither    = $( 'tr.' + page_slug + '-png-reduce-colors-dither' );
				var $png_reduce_max_colors_count = $( 'tr.' + page_slug + '-png-reduce-max-colors-count' );
				$png_reduce_colors_enable.on(
					'change',
					function () {
						if ($png_reduce_colors_enable.is( ':checked' )) {
							$png_reduce_colors_dither.show( 'slow' );
							$png_reduce_max_colors_count.show( 'slow' );
						} else {
							$png_reduce_colors_dither.hide( 'slow' );
							$png_reduce_max_colors_count.hide( 'slow' );
						}
					}
				);
				$png_reduce_colors_enable.trigger( 'change' );

				var $webp_image_clones_enabled           = $( '#' + page_slug + '-webp-images-create' );
				var $webp_image_compression_quality      = $( 'tr.' + page_slug + '-webp-compression-quality' );
				var $webp_jpeg_image_compression_quality = $( 'tr.' + page_slug + '-webp-jpeg-compression-quality' );
				$webp_image_clones_enabled.on(
					'change',
					function () {
						if ($webp_image_clones_enabled.is( ':checked' )) {
							$webp_image_compression_quality.show( 'slow' );
							$webp_jpeg_image_compression_quality.show( 'slow' );
						} else {
							$webp_image_compression_quality.hide( 'slow' );
							$webp_jpeg_image_compression_quality.hide( 'slow' );
						}
					}
				);
				$webp_image_clones_enabled.trigger( 'change' );

				var $image_max_width_enabled = $( '#' + page_slug + '-image-max-width-enabled' );
				var $image_max_width_pixels  = $( 'tr.' + page_slug + '-image-max-width-pixels' );
				$image_max_width_enabled.on(
					'change',
					function () {
						if ($image_max_width_enabled.is( ':checked' )) {
							$image_max_width_pixels.show( 'slow' );
						} else {
							$image_max_width_pixels.hide( 'slow' );
						}
					}
				);
				$image_max_width_enabled.trigger( 'change' );

				var $big_image_size_threshold_disabled = $( '#' + page_slug + '-wp-big-image-size-threshold-disabled' );
				var $big_image_size_threshold_value    = $( 'tr.' + page_slug + '-wp-big-image-size-threshold-value' );
				$big_image_size_threshold_disabled.on(
					'change',
					function () {
						if ($big_image_size_threshold_disabled.is( ':checked' )) {
							$big_image_size_threshold_value.hide( 'slow' );
						} else {
							$big_image_size_threshold_value.show( 'slow' );
						}
					}
				);
				$big_image_size_threshold_disabled.trigger( 'change' );
			}
		);
	} else {
		console.log( 'jQuery function not available (' + typeof $ + ')' );
	}
}(jQuery));
