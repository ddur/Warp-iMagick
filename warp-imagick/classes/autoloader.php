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

namespace ddur\Warp_iMagick;

defined( 'ABSPATH' ) || die( -1 );

if ( ! function_exists( __NAMESPACE__ . '\\_autoload_' ) ) {
	/** Local namespace/directory autoloader.
	 *
	 * @param string $class is class name.
	 */
	function _autoload_( $class ) {
		$position = strlen( __NAMESPACE__ ) + 1;
		if ( substr( $class, 0, $position ) === __NAMESPACE__ . '\\' ) {
			$class_path_array = explode( '\\', substr( $class, $position ) );
			$class_file_name  = array_pop( $class_path_array ) . '.php';
			$class_file_name  = 'class-' . str_replace( '_', '-', strtolower( $class_file_name ) );
			$class_file_path  = count( $class_path_array ) ? implode( '/', $class_path_array ) . '/' : '';
			$class_full_path  = __DIR__ . '/' . $class_file_path . $class_file_name;

			if ( file_exists( $class_full_path ) ) {
				require_once $class_full_path;
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				error_log( __NAMESPACE__ . '\\' . __FUNCTION__ . " file not found: $class_full_path" );
				// phpcs:enable WordPress.PHP.DevelopmentFunctions
			}
		}
	}
	\spl_autoload_register( __NAMESPACE__ . '\\_autoload_' );
}
