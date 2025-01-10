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

/**
 * Custom Template for Media Attachment Images
 *
 * @package Warp iMagick
 * @version 1.0
 */
namespace ddur\Warp_iMagick;

defined( 'ABSPATH' ) || die( -1 );

use ddur\Warp_iMagick\Shared;

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
		function (
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

		$size_data[0] = $file_path;

		$files [ $new_array_key ] = $size_data;
	}

	return $files;
}

/** Return <img> elements */
function get_img_html_elements() {
	WP_Filesystem();

	global $wp_filesystem;

	if ( ! is_callable( '\\getimagesize' ) ) {
		Dbg::error( 'Function is not callable: \getimagesize' );
		return '';
	}

	$my_wp_query = $GLOBALS['wp_the_query'];
	$img_files   = get_attachment_image_files( $my_wp_query->post->ID );

	if ( ! is_array( $img_files ) || count( $img_files ) === 0 ) {
		return '';
	}

	$img_files = array_reverse( $img_files );

	$break = 0;

	$root_path = wp_normalize_path( untrailingslashit( ABSPATH ) );
	foreach ( $img_files as $size_name => $size_data ) {
		$file_path = \wp_normalize_path( $size_data[0] );
		$byte_size = $size_data[3];
		$mime_type = $size_data[4];
		$file_size = \size_format( $byte_size );

		if ( Hlp::starts_with( $file_path, $root_path ) ) {
			$breakname = \wp_basename( $file_path, '.webp' );
			if ( $break ) {
				if ( $break !== $breakname ) {
					$break = $breakname;
					echo '<br>' . PHP_EOL;
				}
			} else {
				$break = $breakname;
			}

			$href     = site_url( substr( $file_path, strlen( $root_path ) ) );
			$basename = basename( $file_path );

			$src = 'data:' . $mime_type . ';base64,' . base64_encode( $wp_filesystem->file_get_contents( $file_path ) ); // phpcs:ignore

			echo '<a target="_blank" href="'
			. esc_url_raw( $href ) . '"><img data-file-size="'
			. esc_attr( $byte_size ) . '" data-size-name="'
			. esc_attr( $size_name ) . '" src="'

			. esc_attr( $src ) . '" width="'
			. esc_attr( $size_data [1] ) . '" height="'
			. esc_attr( $size_data [2] ) . '" title="WP Size Name: '
			. esc_attr( $size_name ) . "\nWidth&Height: "
			. esc_attr( $size_data [1] ) . 'x'
			. esc_attr( $size_data [2] ) . "px\nFile Basename: "
			. esc_attr( $basename ) . "\nFile Byte-size: "
			. esc_attr( $file_size ) . '('
			. esc_attr( $byte_size ) . ' bytes)'
			. '"></a>' . PHP_EOL;
		}
	}

	if ( Dbg::is_debug() ) {
		if ( Shared::get_option( 'preview_thumbnails_show_metadata', true ) ) {
			$metadata = \wp_get_attachment_metadata( $my_wp_query->post->ID );
			// phpcs:ignore
			echo '<p>Image Metadata:</p><p><pre>' . print_r( $metadata, true ) . '</pre></p>';
		}
	}
}
?><!doctype html><head><title>Thumbnails</title></head><html><body>
<?php get_img_html_elements(); ?></body></html>
