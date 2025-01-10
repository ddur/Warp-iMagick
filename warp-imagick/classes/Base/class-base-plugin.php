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
use ddur\Warp_iMagick\Base\Plugin\v1\Abstract_Plugin;

$class_name = __NAMESPACE__ . '\\Base_Plugin';

if ( ! class_exists( $class_name ) ) {
	/** Plugin base class.
	 * Instantiates Plugin-Singleton object.
	 */
	abstract class Base_Plugin extends Abstract_Plugin {
		// phpcs:ignore
	# region Plugin construction.

		/** Static singleton object.
		 *
		 * @var object $me contains this class singleton.
		 */
		private static $me = null;

		/** Construct Once.
		 * Static Singleton Class Constructor.
		 *
		 * @param string $file - Magic __FILE__ constant from plugin-entry file.
		 * @return mixed this object singleton or null on error.
		 */
		public static function once( $file = null ) {
			if ( null === self::$me && null !== $file && file_exists( $file ) ) {
				self::$me = new static( $file );
			}
			if ( null === self::$me ) {
				if ( null === $file ) {
					Dbg::error( 'Missing $file argument' );
				} else {
					Dbg::error( 'Invalid $file argument' );
				}
			} else {
				self::$me->init();

			}
			return self::$me;
		}

		/** Class initialization method to override. */
		protected function init() {}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Public Singleton Static Access.

		/** Static access to Class instance. */
		public static function instance() {
			return self::$me;
		}

		// phpcs:ignore
	# endregion
	}
} else {
	Dbg::debug( "Class already exists: $css_name" );
}
