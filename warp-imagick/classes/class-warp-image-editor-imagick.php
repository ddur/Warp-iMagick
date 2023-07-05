<?php
/**
 * Copyright © 2017-2023 Dragan Đurić. All rights reserved.
 *
 * @package warp-imagick
 * @license GNU General Public License Version 2.
 * @copyright © 2017-2023. All rights reserved.
 * @author Dragan Đurić
 * @link https://warp-imagick.pagespeed.club/
 *
 * This copyright notice, source files, licenses and other included
 * materials are protected by U.S. and international copyright laws.
 * You are not allowed to remove or modify this or any other
 * copyright notice contained within this software package.
 */

defined( 'ABSPATH' ) || die( -1 );

use \ddur\Warp_iMagick\Base\Plugin\v1\Lib;
use \ddur\Warp_iMagick\Shared;
use \ddur\Warp_iMagick\Plugin;

if ( ! class_exists( 'Warp_Image_Editor_Imagick' ) ) {
	if ( ! class_exists( '\WP_Image_Editor_Imagick' ) ) {
		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';
	}

	/** Derived Image Optimizing Editor Class
	 *
	 * Optimization, Optimization control & Plugin integration.
	 */
	class Warp_Image_Editor_Imagick extends WP_Image_Editor_Imagick {
		/**
		 * Support & accept only JPEG/PNG media types.
		 *
		 * @param string $mime_type to check support for.
		 * @return bool
		 */
		public static function supports_mime_type( $mime_type ) {
			switch ( $mime_type ) {
				case 'image/jpeg':
				case 'image/png':
					return parent::supports_mime_type( $mime_type );
				default:
					return false;
			}
		}

		/**
		 * No need to derive multi_resize method because it uses make_subsize method.
		 */

		/**
		 * Override parent to prevent overwriting image subsize file
		 * created within <intermediate_image_sizes_advanced> filter
		 *
		 * @param array $size_data to create.
		 * @return array|WP_error
		 */
		public function make_subsize( $size_data ) {
			if ( Shared::regenerating_metadata()
				&& ! empty( $size_data['file'] )
				&& is_string( $size_data['file'] )
				&& strlen( $size_data['file'] ) > 0

			) {
				return $size_data;
			}

			$this->reset_execution_time();
			return parent::make_subsize( $size_data );
		}

		/**
		 * Compress & Optimize Image.
		 *
		 * Public interface to thumbnail_image().
		 *
		 * @param int $dst_w The destination width.
		 * @param int $dst_h The destination height.
		 * @return bool|WP_Error
		 */
		public function compress_image( $dst_w, $dst_h ) {
			return $this->thumbnail_image( $dst_w, $dst_h );
		}

		/**
		 * Thumbnail Image - Optimize Thumbnail Image.
		 *
		 * @param int    $dst_w       The destination width.
		 * @param int    $dst_h       The destination height.
		 * @param string $filter_name Optional. The Imagick filter to use when resizing. Default 'FILTER_TRIANGLE'.
		 * @param bool   $strip_meta  Optional. Strip all profiles, excluding color profiles, from the image. Default true.
		 * @return bool|WP_Error
		 */
		protected function thumbnail_image( $dst_w, $dst_h, $filter_name = 'FILTER_TRIANGLE', $strip_meta = true ) {
			$this->reset_execution_time();

			if ( is_object( Shared::plugin() ) ) {
				try {
					if ( 'image/png' === $this->mime_type ) {
						switch ( Shared::get_option( 'png-strip-meta', Shared::png_strip_meta_default() ) ) {
							case 0:
								break;
							case 1:
								$strip_meta = false;
								\add_filter( 'image_strip_meta', '__return_false', 99 );
								break;
							case 2:
								$strip_meta = true;
								\add_filter( 'image_strip_meta', '__return_true', 99 );
								break;
							case 3:
								if ( is_callable( array( $this->image, 'stripImage' ) ) ) {
									$this->image->stripImage();
									$strip_meta = false;
									\add_filter( 'image_strip_meta', '__return_false', 99 );
								} else {
									$strip_meta = true;
									\add_filter( 'image_strip_meta', '__return_true', 99 );
								}
								break;
							default:
								break;
						}
					} elseif ( 'image/jpeg' === $this->mime_type ) {
						if ( is_callable( array( $this->image, 'transformImageColorspace' ) )
						&& is_callable( array( $this->image, 'getImageColorspace' ) )
						&& is_callable( array( $this->image, 'profileImage' ) ) ) {
							$can_transform_colorspace = false;
							if ( defined( '\\Imagick::COLORSPACE_UNDEFINED' ) && \Imagick::COLORSPACE_UNDEFINED === $this->image->getImageColorspace() ) {
								$can_transform_colorspace = Shared::get_option( 'jpeg-colorspace-force', false );
							}

							if ( $can_transform_colorspace ) {
								$jpeg_colorspace = Shared::get_option( 'jpeg-colorspace', Shared::jpeg_colorspace_default() );
								if ( 0 !== $jpeg_colorspace ) {
									if ( $this->image->getImageColorspace() !== $jpeg_colorspace ) {
										if ( ! $this->image->transformImageColorspace( $jpeg_colorspace ) ) {
											;
										} else {
											$this->image->profileImage( 'icc', null );
										}
									}
								}
							}
						}

						switch ( Shared::get_option( 'jpeg-strip-meta', 0 ) ) {
							case 0:
								break;
							case 1:
								$strip_meta = false;
								\add_filter( 'image_strip_meta', '__return_false', 99 );
								break;
							case 2:
								$strip_meta = true;
								\add_filter( 'image_strip_meta', '__return_true', 99 );
								break;
							case 3:
								$can_strip_all = false;
								if ( \is_callable( array( $this->image, 'getImageColorspace' ) ) ) {
									$can_strip_all = \Imagick::COLORSPACE_SRGB === $this->image->getImageColorspace();
								}
								if ( $can_strip_all && \is_callable( array( $this->image, 'stripImage' ) ) ) {
									$this->image->stripImage();
									$strip_meta = false;
									\add_filter( 'image_strip_meta', '__return_false', 99 );
								} else {
									$strip_meta = true;
									\add_filter( 'image_strip_meta', '__return_true', 99 );
								}
								break;
							default:
								break;
						}
					}
				} catch ( Exception $e ) {
					Lib::error( 'Exception: ' . $e->getMessage() );
					return new \WP_Error( 'warp_optimize_error', $e->getMessage() );

				}
			}

			$this->reset_execution_time();

			$orig_size = $this->size;

			$thumb_result = parent::thumbnail_image( $dst_w, $dst_h, $filter_name, $strip_meta );
			if ( is_wp_error( $thumb_result ) ) {
				return $thumb_result;
			}

			$this->reset_execution_time();

			if ( is_object( Shared::plugin() ) ) {
				if ( 'image/png' === $this->mime_type ) {
					try {
						if ( true === Shared::get_option( 'png-reduce-colors-enable', Shared::png_reduce_colors_enabled_default() ) ) {
							$colors_max = Shared::get_option( 'png-reduce-max-colors-count', Shared::png_max_colors_value_default() );

							$colors_max = Shared::png_max_colors_value_min() <= $colors_max && Shared::png_max_colors_value_max() >= $colors_max ? $colors_max : Shared::png_max_colors_value_default();

							$dither_png = Shared::get_option( 'png-reduce-colors-dither', Shared::png_reduce_colors_dither_default() );

							$do_reduce_colors = true;
							$colors_reduced   = false;

							$current_colors = $this->image->getImageColors();
							if ( 0 >= $current_colors || $colors_max >= $current_colors ) {
								$do_reduce_colors = false;
								$colors_reduced   = true;
							}

							$current_imgtype = $this->image->getImageType();
							if ( ! array_key_exists( $current_imgtype, Shared::get_imagick_img_types() ) ) {
								$current_imgtype = false;
							} else {
								switch ( $current_imgtype ) {
									case 2:
									case 3:
									case 4:
									case 5:
									case 6:
									case 7:
									case 8:
									case 9:
										break;

									default:
										$current_imgtype  = false;
										$do_reduce_colors = false;
										$colors_reduced   = true;
								}
							}

							if ( $current_imgtype && true === $do_reduce_colors && false === $colors_reduced ) {
								$alphachannel_on   = Shared::is_transparent( $this->image );
								$saved_restore_img = false;
								if ( $alphachannel_on && $dither_png ) {
									if ( $colors_max <= Shared::png_max_colors_palette() ) {
										$dither_png = false;
									} else {
										$saved_restore_img = $this->image->getImage();
										switch ( $current_imgtype ) {
											case 4:
											case 5:
												break;
											default:
												if ( 4 < $this->image->getImageChannelDepth( \Imagick::CHANNEL_ALPHA ) ) {
													$this->image->setImageChannelDepth( \Imagick::CHANNEL_ALPHA, 4 );
												}
												break;
										}
									}
								}

								if ( is_callable( array( $this->image, 'quantizeImage' ) ) ) {
									$colors_reduced = $this->image->quantizeImage( $colors_max, $this->image->getImageColorspace(), 0, $dither_png, false );
								}

								if ( $saved_restore_img instanceof \Imagick ) {
									if ( \Imagick::IMGTYPE_PALETTEMATTE === $this->image->getImageType() ) {
										$this->image = $saved_restore_img->getImage();

										$dither_png = false;

										if ( is_callable( array( $this->image, 'quantizeImage' ) ) ) {
											$colors_reduced = $this->image->quantizeImage( $colors_max, $this->image->getImageColorspace(), 0, $dither_png, false );
										}
									}

									$saved_restore_img->clear();
									$saved_restore_img->destroy();
									$saved_restore_img = false;
								}
							}
						}

						$png_sizes = array();

						$this->reset_execution_time();
						$png_sizes [ strlen( $this->image->getImageBlob() ) ] = array( 1, 5, 'WordPress Default' );
						$this->image->setOption( 'png:compression-filter', '0' );
						$this->image->setOption( 'png:compression-strategy', '0' );

						$this->reset_execution_time();
						$png_sizes [ strlen( $this->image->getImageBlob() ) ] = array( 0, 0, 'Imagick Default' );

						ksort( $png_sizes );
						$this->image->setOption( 'png:compression-strategy', '' . reset( $png_sizes ) [0] );
						$this->image->setOption( 'png:compression-filter', '' . reset( $png_sizes ) [1] );

					} catch ( Exception $e ) {
						Lib::error( 'Exception: ' . $e->getMessage() );
						return new \WP_Error( 'warp_optimize_error', $e->getMessage() );

					}
				}

				if ( 'image/jpeg' === $this->mime_type ) {
					try {
						if ( is_callable( array( $this->image, 'sharpenImage' ) ) ) {
							if ( $this->image->getImageWidth() === $orig_size['width']
							&& $this->image->getImageHeight() === $orig_size['height'] ) {
								$jpeg_sharpen_sigma = Shared::get_option( 'jpeg-sharpen-image', Shared::jpeg_sharpen_image_value_default() );
								0 === $jpeg_sharpen_sigma || $this->image->sharpenImage( 0, $jpeg_sharpen_sigma / 10.0 );
							} else {
								$jpeg_sharpen_sigma = Shared::get_option( 'jpeg-sharpen-thumbnails', Shared::jpeg_sharpen_thumbnails_value_default() );
								0 === $jpeg_sharpen_sigma || $this->image->sharpenImage( 0, $jpeg_sharpen_sigma / 10.0 );
							}
						}

						$jpeg_compression_quality = Shared::get_option( 'jpeg-compression-quality', Shared::jpeg_quality_default() );
						0 === $jpeg_compression_quality || $this->image->setImageCompressionQuality( $jpeg_compression_quality );

						if ( is_callable( array( $this->image, 'setImageProperty' ) ) ) {
							$jpeg_sampling_factor = Shared::get_option( 'jpeg-sampling-factor', Shared::jpeg_sampling_factor_default() );
							'' === $jpeg_sampling_factor || $this->image->setImageProperty( 'jpeg:sampling-factor', $jpeg_sampling_factor );

						}

						if ( is_callable( array( $this->image, 'setInterlaceScheme' ) ) ) {
							$jpeg_interlace_scheme = Shared::get_option( 'jpeg-interlace-scheme', Shared::jpeg_interlace_scheme_default() );
							switch ( $jpeg_interlace_scheme ) {
								case 0:
									break;

								case -1:
									if ( defined( '\\Imagick::INTERLACE_NO' ) && defined( '\\Imagick::INTERLACE_PLANE' ) ) {
										$this->image->setInterlaceScheme( \Imagick::INTERLACE_NO );

										$this->reset_execution_time();
										$size_nointerlace = strlen( $this->image->getImageBlob() );

										$this->image->setInterlaceScheme( \Imagick::INTERLACE_PLANE );

										$this->reset_execution_time();
										$size_progressive = strlen( $this->image->getImageBlob() );

										if ( $size_nointerlace < $size_progressive ) {
											$this->image->setInterlaceScheme( \Imagick::INTERLACE_NO );
										}
									}
									break;
								default:
									$this->image->setInterlaceScheme( $jpeg_interlace_scheme );
									break;
							}
						}
					} catch ( Exception $e ) {
						Lib::error( 'Exception: ' . $e->getMessage() );
						return new \WP_Error( 'warp_optimize_error', $e->getMessage() );

					}
				}
			}
		}

		/** Parent method - modified.
		 *
		 * Generate webp for each successfully saved file.
		 *
		 * @param Imagick $image image.
		 * @param string  $filename to save as.
		 * @param string  $mime_type to save as.
		 * @return array|WP_Error
		 */
		protected function _save( $image, $filename = null, $mime_type = null ) { // phpcs:ignore

			$saved = parent::_save( $image, $filename, $mime_type );

			if ( is_wp_error( $saved ) ) {
				return $saved;
			}

			$saved_mime_type = isset( $saved ['mime_type'] ) && is_string( $saved ['mime_type'] ) && trim( $saved ['mime_type'] ) ? $saved ['mime_type'] : $this->mime_type;
			switch ( $saved_mime_type ) {
				case 'image/jpeg':
				case 'image/png':
					Shared::webp_clone_image( $saved ['path'], $saved_mime_type );
					break;

				default:
					break;
			}

			return $saved;

		}

		/** Reset execution time. Private.
		 */
		private function reset_execution_time() {
			ini_get( 'max_execution_time' ) && set_time_limit( intval( ini_get( 'max_execution_time' ) ) );
		}

	}
}
