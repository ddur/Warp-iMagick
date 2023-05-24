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

/**
 * Custom Template for Media Attachment Images
 *
 * @package Warp iMagick
 * @version 1.0
 */
namespace ddur\Warp_iMagick;

use \ddur\Warp_iMagick\Base\Plugin\v1\Lib;
use \ddur\Warp_iMagick\Shared;

defined( 'ABSPATH' ) || die( -1 );

/** Get array of media attachment images
 *
 * @param int $id of attachment.
 * @return array|false [$size-name => $file-path]
 */
function get_attachment_image_files( $id ) {

	$metadata = \wp_get_attachment_metadata( $id );
	if ( ! is_array( $metadata ) ) {
		return false;
	}
	if ( ! isset( $metadata ['file'] ) ) {
		return false;
	}
	if ( ! is_string( $metadata ['file'] ) ) {
		return false;
	}
	if ( ! isset( $metadata ['sizes'] ) ) {
		return false;
	}
	if ( ! is_array( $metadata ['sizes'] ) ) {
		return false;
	}

	$mime_type = \get_post_mime_type( $id );
	switch ( $mime_type ) {
		case 'image/jpeg':
		case 'image/png':
			break;
		default:
			return false;
	}

	$my_wp_query = $GLOBALS['wp_the_query'];
	if ( isset( $my_wp_query->query_vars[ Shared::slug() ] ) ) {
		$query_value = strtolower( $my_wp_query->query_vars[ Shared::slug() ] );
		$query_value = '' !== trim( $query_value ) ? $query_value : 'all';
	} else {
		$query_value = 'all';
	}

	$main_img_path = \trailingslashit( \wp_upload_dir() ['basedir'] ) . $metadata ['file'];
	$base_dir_path = \trailingslashit( dirname( $main_img_path ) );

	$distinct_files = array();

	$sizes = $metadata ['sizes'];

	uksort(
		$sizes,
		function(
			$a,
			$b
		) use ( $sizes ) {
			$width_a = (int) $sizes [ $a ] ['width'];
			$width_b = (int) $sizes [ $b ] ['width'];
			if ( $width_a === $width_b ) {
				return 0;
			} elseif ( $width_a > $width_b ) {
				return 1;
			} else {
				return -1;
			}
		}
	);

	$width  = 0;
	$height = 0;

	if ( in_array( $query_value, array( 'raw', 'all', 'webp' ), true ) ) {
		foreach ( $sizes as $size_name => $metadata_data ) {
			$distinct_file = $base_dir_path . $metadata_data ['file'];
			if ( ! file_exists( $distinct_file ) ) {
				continue;
			}
			$geometry = \getimagesize( $distinct_file );
			if ( is_array( $geometry ) ) {
				$width  = (int) $geometry [0];
				$height = (int) $geometry [1];
			}
			$bytes     = (int) \filesize( $distinct_file );
			$size_name = str_replace( array( '_', '-' ), ' ', $size_name );
			if ( in_array( $query_value, array( 'raw', 'all' ), true ) ) {
				$distinct_files [ $distinct_file ] = array( $size_name, $width, $height, $bytes, $mime_type );
			}
			if ( in_array( $query_value, array( 'all', 'webp' ), true ) ) {
				$distinct_file = Shared::get_webp_file_name( $distinct_file );
				if ( ! file_exists( $distinct_file ) ) {
					continue;
				}
				$geometry = \getimagesize( $distinct_file );

				if ( is_array( $geometry ) ) {
					$width  = (int) $geometry [0];
					$height = (int) $geometry [1];
				}
				$bytes = (int) \filesize( $distinct_file );

				$distinct_files [ $distinct_file ] = array( $size_name . ' (webp)', $width, $height, $bytes, 'image/webp' );
			}
		}
	}

	$width  = 0;
	$height = 0;

	if ( in_array( $query_value, array( 'full', 'all' ), true ) ) {
		$distinct_file = $main_img_path;
		if ( file_exists( $distinct_file ) ) {
			$geometry = \getimagesize( $distinct_file );
			if ( is_array( $geometry ) ) {
				$width  = (int) $geometry [0];
				$height = (int) $geometry [1];
			}
			$bytes = (int) \filesize( $distinct_file );

			$distinct_files[ $distinct_file ] = array( 'attached', $width, $height, $bytes, $mime_type );
			if ( in_array( $query_value, array( 'full', 'all', 'webp' ), true ) ) {
				$distinct_file = Shared::get_webp_file_name( $distinct_file );
				if ( file_exists( $distinct_file ) ) {
					$geometry = \getimagesize( $distinct_file );

					if ( is_array( $geometry ) ) {
						$width  = (int) $geometry [0];
						$height = (int) $geometry [1];
					}
					$bytes = (int) \filesize( $distinct_file );

					$distinct_files [ $distinct_file ] = array( 'attached (webp)', $width, $height, $bytes, 'image/webp' );
				}
			}
		}
	}

	$width  = 0;
	$height = 0;

	if ( \function_exists( '\\wp_get_original_image_path' ) && ! empty( $metadata ['original_image'] ) ) {
		if ( in_array( $query_value, array( 'full', 'all' ), true ) ) {
			$distinct_file = \wp_get_original_image_path( $id );
			if ( file_exists( $distinct_file ) ) {
				$geometry = \getimagesize( $distinct_file );
				if ( is_array( $geometry ) ) {
					$width  = (int) $geometry [0];
					$height = (int) $geometry [1];
				}
				$bytes = (int) \filesize( $distinct_file );

				$distinct_files[ $distinct_file ] = array( 'original', $width, $height, $bytes, $mime_type );
				if ( in_array( $query_value, array( 'full', 'all', 'webp' ), true ) ) {
					$distinct_file = Shared::get_webp_file_name( $distinct_file );
					if ( file_exists( $distinct_file ) ) {
						$geometry = \getimagesize( $distinct_file );

						if ( is_array( $geometry ) ) {
							$width  = (int) $geometry [0];
							$height = (int) $geometry [1];
						}
						$bytes = (int) \filesize( $distinct_file );

						$distinct_files [ $distinct_file ] = array( 'original (webp)', $width, $height, $bytes, 'image/webp' );
					}
				}
			}
		}
	}

	$files = array();
	foreach ( $distinct_files as $file_path => $size_data ) {

		$new_array_key = $size_data[0];
		$size_data[0]  = $file_path;

		$files [ $new_array_key ] = $size_data;
	}

	return $files;
}

/** Return <img> elements */
function get_img_html_elements() {

	if ( ! is_callable( '\\getimagesize' ) ) {
		Lib::error( 'Function is not callable: \getimagesize' );
		return '';
	}

	$my_wp_query = $GLOBALS['wp_the_query'];
	$img_files   = get_attachment_image_files( $my_wp_query->post->ID );

	if ( ! is_array( $img_files ) || count( $img_files ) === 0 ) {
		return '';
	}

	$img_files = array_reverse( $img_files );

	$html  = '';
	$break = 0;

	$root_path = wp_normalize_path( untrailingslashit( ABSPATH ) );
	foreach ( $img_files as $size_name => $size_data ) {

		$file_path = \wp_normalize_path( $size_data[0] );
		$byte_size = $size_data[3];
		$mime_type = $size_data[4];
		$file_size = \size_format( $byte_size );

		if ( Lib::starts_with( $file_path, $root_path ) ) {

			$breakname = \wp_basename( $file_path, '.webp' );
			if ( $break ) {
				if ( $break !== $breakname ) {
					$break = $breakname;
					$html .= '<br>' . PHP_EOL;
				}
			} else {
				$break = $breakname;
			}
			$file_size = esc_attr( $file_size );
			$size_name = esc_attr( $size_name );
			switch ( $mime_type ) {
				case 'image/webp':
					// phpcs:ignore
					$src = 'data:' . $mime_type . ';base64,' . base64_encode( file_get_contents( $file_path ) );

					break;
				default:
					// phpcs:ignore
					$src = 'data:' . $mime_type . ';base64,' . base64_encode( file_get_contents( $file_path ) );
					break;

			}
			$width     = esc_attr( $size_data [1] );
			$height    = esc_attr( $size_data [2] );
			$basename  = basename( $file_path );
			$byte_size = esc_attr( $byte_size );
			$title     = esc_attr( "WP Size Name: $size_name\nWidth&Height: ${width}x${height}px\nFile Basename: $basename\nFile Byte-size: $file_size ($byte_size bytes)" );
			$html     .= "<img data-file-size='$byte_size' data-size-name='$size_name' src='$src' width='$width' height='$height' title='$title'>" . PHP_EOL;
		}
	}

	if ( Lib::is_debug() ) {

		if ( Shared::get_option( 'preview_thumbnails_show_metadata', true ) ) {
			$metadata = \wp_get_attachment_metadata( $my_wp_query->post->ID );
			// phpcs:ignore
			$html .= '<p>Image Metadata:</p><p><pre>' . print_r( $metadata, true ) . '</pre></p>';
		}

		$perflab = get_option( 'perflab_modules_settings', 'Not available' );
		// phpcs:ignore
		$html .= '<p>PerfLab modules settings:</p><p><pre>' . print_r( $perflab, true ) . '</pre></p>';

		$perflab = \get_option( 'perflab_generate_webp_and_jpeg', 'Not available' );
		// phpcs:ignore
		$html .= '<p>PerfLab generate WebP and JPEG:<pre>' . print_r( $perflab, true ) . '</pre></p>';
	}

	return $html;
}
?><!doctype html><head><title>Thumbnails</title></head><html><body>
<?php Lib::echo_html( get_img_html_elements() ); ?></body></html>
