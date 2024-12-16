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

use ddur\Warp_iMagick\Shared;
use ddur\Warp_iMagick\Base\Plugin\v1\Lib;
use ddur\Warp_iMagick\Base\Base_Settings_Renderer;

$class = __NAMESPACE__ . '\\Meta_Settings_Renderer';

if ( ! class_exists( $class ) ) {
	/** Meta Settings Renderer class.
	 *
	 * Class between Settings Renderer and abstract Base_Settings_Renderer.
	 */
	class Meta_Settings_Renderer extends Base_Settings_Renderer {}
} else {
	Shared::debug( "Class already exists: $class" );
}
