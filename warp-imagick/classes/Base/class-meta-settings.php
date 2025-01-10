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

use ddur\Warp_iMagick\Base\Base_Settings;
use ddur\Warp_iMagick\Dbg;

$class = __NAMESPACE__ . '\\Meta_Settings';

if ( ! class_exists( $class ) ) {
	/** Meta Settings Class.
	 *
	 * Class between Settings and abstract Base_Settings class.
	 */
	abstract class Meta_Settings extends Base_Settings {
		// phpcs:ignore
	# region Static Manage Plugin Functions.

		/** Plugin manage action handler
		 *
		 * @param string $action - one of activate/deactivate/uninstall.
		 */
		protected static function on_manage_plugin( $action ) {
			// phpcs:enable
		}

		/** Get Plugin ID */
		public static function ID() {
			return wp_parse_url( get_site_url(), PHP_URL_HOST ) . '/' . self::instance()->pageslug;
		}

		// phpcs:ignore
	# endregion
	}
} else {
	Dbg::debug( "Class already exists: $class" );
}
