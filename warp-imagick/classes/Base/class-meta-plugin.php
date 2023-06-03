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

namespace ddur\Warp_iMagick\Base;

defined( 'ABSPATH' ) || die( -1 );

use \ddur\Warp_iMagick\Base\Plugin\v1\Lib;
use \ddur\Warp_iMagick\Base\Base_Plugin;

if ( ! class_exists( __NAMESPACE__ . '\Meta_Plugin' ) ) {

	/** Meta Plugin Class.
	 *
	 * Class between Plugin and abstract Base_Plugin class.
	 */
	abstract class Meta_Plugin extends Base_Plugin {

		/** Instantiate plugin upgrade checker client.
		 *
		 * @param string $uplink updates link (https://[host.]domain.tld/[updates/]).
		 */
		public function plugin_upgrade_checker( $uplink = '' ) {
			if ( empty( $uplink ) ) {
				$uplink = 'https://' . $this->get_slug() . '.pagespeed.club/updates/';
			}
			$uplink = \trailingslashit( $uplink );

			$client = $this->get_path() . '/classes/Base/plugin-update-checker-4.11/plugin-update-checker.php';
			if ( ! \file_exists( $client ) ) {
				Lib::error( 'Missing update checker directory/file: ' . $client );
				return;
			}
			require_once $client;

			$server = \add_query_arg(
				\urlencode_deep(
					array(
						'action' => 'get_metadata',
						'slug'   => $this->get_slug(),
					)
				),
				$uplink
			);

			$my_update_checker = \Puc_v4_Factory::buildUpdateChecker(
				$server,
				$this->get_file(),
				$this->get_slug()
			);

			\add_filter(
				$my_update_checker->getUniqueName( 'request_metadata_http_result' ),
				function( $result, $url = '', $options = '' ) use ( $uplink ) {
					if ( ! \is_wp_error( $result ) ) {
						switch ( $result['response']['code'] ) {
							case 404:
								$result = new \WP_Error(
									'404',
									'Plugin ' . $this->get_slug() . ' is not available on Updates Server (' . $uplink . ').'
								);
						}
					}
					return $result;
				},
				10,
				1
			);

			\add_filter(
				$my_update_checker->getUniqueName( 'manual_check_message' ),
				function( $message, $status = '' ) {
					switch ( $status ) {
						case 'no_update':
							break;

						case 'update_available':
							if ( empty( $this->get_option( 'plugin-app-update-password' ) ) ) {
								$message = $message . '<br>Your update password is empty. Please register at <a href=' . $uplink . '>Updates Server</a> to get your free update password.';
							}
							break;

						case 'error':
							break;
					}
					return $message;
				},
				10,
				2
			);

			$host = wp_parse_url( home_url(), PHP_URL_HOST );
			$pass = preg_replace( '/[^a-z\d]/i', '', $this->get_option( 'plugin-app-update-password', 'xxxx xxxx xxxx xxxx xxxx xxxx' ) );

			$head_auth = 'Basic ' . base64_encode( $host . ':' . $pass); // phpcs:ignore

			\add_filter(
				$my_update_checker->getUniqueName( 'request_update_result' ),
				function( $update, $http_result = null ) {

					return $update;
				},
				10,
				2
			);

			\add_filter(
				'http_request_args',
				function ( $parsed_args, $url ) use ( $uplink, $head_auth, $host ) {

					$remote_host = wp_parse_url( $url, PHP_URL_HOST );
					$uplink_host = wp_parse_url( $uplink, PHP_URL_HOST );

					Lib::debug( '(Client) $remote_host: ' . $remote_host );
					Lib::debug( '(Client) $uplink_host: ' . $uplink_host );
					Lib::debug( '(Client) $_local_host: ' . $host );

					if ( $remote_host !== $uplink_host ) {
						Lib::debug( '(Client) Remote host ignored.' );
						return $parsed_args;
					}
					Lib::debug( '(Client) $remote_host accepted( ' . $remote_host . ' ) == ( ' . $uplink_host . ' )' );

					if ( $remote_host === $host ) {
						Lib::debug( '(Client) Update server is on local host.' );
					} else {
						Lib::debug( '(Client) Update server is on remote host.' );

						if ( false === strpos( $url, '?action=' ) ) {
							Lib::debug( '(Client) missing ?action=' );
							return $parsed_args;
						}

						if ( false === strpos( $url, '&slug=' ) ) {
							Lib::debug( '(Client) missing &slug=' );
							return $parsed_args;
						}
					}

					$parsed_args ['headers'] = array_merge(
						is_array( $parsed_args ['headers'] ) ? $parsed_args ['headers'] : array(),
						array( 'Authorization' => $head_auth )
					);
					Lib::debug( '(Client) Authorization header added: ' . $head_auth );

					return $parsed_args;
				},
				10,
				2
			);
		}
	}
}
