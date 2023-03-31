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
 * @wordpress-plugin
 * Plugin Name: Warp iMagick - Image Compressor
 * Plugin URI:  https://warp-imagick.pagespeed.club/
 * Description: Optimize Images. Convert & Serve WebP. Set JPEG quality (file size). Sharpen blurry WP images. Upload & resize BIG images down.
 * Author:      ddur
 * Author URI:  https://github.com/ddur
 * License:     GPLv2
 * Version:     1.10.1
 * Text Domain: warp-imagick
 * Domain Path: /languages
 *
 * This copyright notice, source files, licenses and other included
 * materials are protected by U.S. and international copyright laws.
 * You are not allowed to remove or modify this or any other
 * copyright notice contained within this software package.
 */

defined( 'ABSPATH' ) || die( -1 );

if ( ( version_compare( phpversion(), '7.2', '>=' ) && version_compare( phpversion(), '7.999', '<=' ) )
|| ( version_compare( phpversion(), '8.0', '>=' ) && version_compare( phpversion(), '8.999', '<=' ) ) ) {
	require_once __DIR__ . '/plugin.php';
} else {
	function_exists( 'add_action' ) || die( -1 );
	add_action( 'init', 'warp_imagick_php_version_error' );
}

/** Required PHP version missing */
function warp_imagick_php_version_error() {
	if ( function_exists( 'wp_get_current_user' )
		&& function_exists( 'current_user_can' )
		&& current_user_can( 'activate_plugins' ) ) {
		add_action( 'admin_init', 'warp_imagick_php_version_error_deactivate_plugin' );
		add_action( 'admin_notices', 'warp_imagick_php_version_error_admin_notice_plugin_deactivated' );
	} else {
		add_action( 'admin_notices', 'warp_imagick_php_version_error_admin_notice_please_deactivate' );
	}
}

/** Deactivate plugin */
function warp_imagick_php_version_error_deactivate_plugin() {
	if ( function_exists( 'deactivate_plugins' ) && function_exists( 'plugin_basename' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}
}

/** Plugin Deactivated notice */
function warp_imagick_php_version_error_admin_notice_plugin_deactivated() {
	echo '<div class="notice notice-error is-dismissible"><p><strong>Warp iMagick</strong> plugin has been <strong>deactivated</strong>, please use PHP version 7.2+ or 8.0+.</p></div>';
	// phpcs:ignore
	unset( $_GET ['activate'] );
}

/** Please Deactivate notice */
function warp_imagick_php_version_error_admin_notice_please_deactivate() {
	echo '<div class="notice notice-error is-dismissible"><p>Please deactivate <strong>Warp iMagick</strong> plugin and use PHP version 7.2+ or 8.0+.</p></div>';
}
