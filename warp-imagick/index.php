<?php
/**
 * Copyright © 2017-2025 Dragan Đurić. All rights reserved.
 *
 * @package warp-imagick
 * @license GNU General Public License Version 2.
 * @copyright © 2017-2025. All rights reserved.
 * @author Dragan Đurić
 * @link https://github.com/ddur/Warp-iMagick
 *
 * @wordpress-plugin
 * Plugin Name: Warp iMagick - Image Compressor
 * Plugin URI:  https://warp-imagick.pagespeed.club/
 * Description: Optimize Images. Convert & Serve WebP. Set JPEG quality (file size). Sharpen blurry WP images. Upload & resize BIG images down.
 * Author:      ddur
 * Author URI:  https://github.com/ddur
 * License:     GPLv2
 * Version:     1.12.1
 * Text Domain: warp-imagick
 * Domain Path: /languages
 *
 * This copyright notice, source files, licenses and other included
 * materials are protected by U.S. and international copyright laws.
 * You are not allowed to remove or modify this or any other
 * copyright notice contained within this software package.
 */

/** Namespace should work on PHP version since namespaces are implemented (>=5.3).
 * Using namespace here will automatically exclude PHP versions <5.3 (fatal error).
 * https://en.wikipedia.org/wiki/Namespace#:~:text=Namespaces%20were%20introduced%20into%20PHP,defined%20with%20a%20namespace%20block.
 */
namespace ddur\Warp_iMagick;

defined( 'ABSPATH' ) || die( -1 );

/** No objects used here. Code below should work on PHP versions even before OOP is implemented (<5).
 * https://www.w3schools.com/php/php_oop_what_is.asp#:~:text=PHP%20%2D%20What%20is%20OOP%3F&text=From%20PHP5%2C%20you%20can%20also,in%20an%20object%2Doriented%20style.
 */
if ( ( \version_compare( \phpversion(), '7.4', '>=' ) && \version_compare( \phpversion(), '7.999', '<=' ) )
|| ( \version_compare( \phpversion(), '8.0', '>=' ) && \version_compare( \phpversion(), '8.999', '<=' ) ) ) {
	require_once __DIR__ . '/plugin.php';
} else {
	\function_exists( '\\add_action' ) || die( -1 );
	\add_action( 'init', __NAMESPACE__ . '\\php_version_error' );
}

/** Required PHP version missing */
function php_version_error() {
	if ( \function_exists( '\\wp_get_current_user' )
	&& \function_exists( '\\current_user_can' )
	&& \current_user_can( 'activate_plugins' ) ) {
		\add_action( 'admin_init', __NAMESPACE__ . '\\php_version_error_deactivate_plugin' );
		\add_action( 'admin_notices', __NAMESPACE__ . '\\php_version_error_admin_notice_plugin_deactivated' );
	} else {
		\add_action( 'admin_notices', __NAMESPACE__ . '\\php_version_error_admin_notice_please_deactivate' );
	}
}

/** Deactivate plugin */
function php_version_error_deactivate_plugin() {
	if ( \function_exists( '\\deactivate_plugins' )
	&& \function_exists( '\\plugin_basename' ) ) {
		\deactivate_plugins( \plugin_basename( __FILE__ ) );
	}
}

/** Plugin Deactivated notice */
function php_version_error_admin_notice_plugin_deactivated() {
	echo '<div class="notice notice-error is-dismissible"><p><strong>Warp iMagick</strong> plugin has been <strong>deactivated</strong>, please use minimal PHP versions 7.4+/8.0+. Latest supported PHP version is recommended in version series 7/8. See: <a target=_blank rel="noopener noreferrer" href=https://www.php.net/supported-versions.php>Currently Supported PHP versions</a></p></div>';
	// phpcs:ignore
	unset( $_GET ['activate'] );
}

/** Please Deactivate notice */
function php_version_error_admin_notice_please_deactivate() {
	echo '<div class="notice notice-error is-dismissible"><p>Please deactivate <strong>Warp iMagick</strong> plugin and please use minimal PHP versions 7.4+/8.0+. Latest supported PHP version is recommended in version series 7/8. See: <a target=_blank rel="noopener noreferrer" href=https://www.php.net/supported-versions.php>Currently Supported PHP versions</a></p></div>';
}
