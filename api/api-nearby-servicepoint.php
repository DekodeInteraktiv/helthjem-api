<?php
/**
 * HeltHjem token processing functions.
 *
 * @package HCSNG
 */

declare(strict_types=1);

namespace HCSNG\LOGIN\V3\PARCEL;

use function HCSNG\LOGIN\V3\TOKEN\is_token_active;
use function HCSNG\LOGIN\V3\TOKEN\is_token_set;
use function HCSNG\LOGIN\V3\TOKEN\update_token;

defined( 'ABSPATH' ) || exit;

/**
 * Checks the single address and returns the result from the API.
 *
 * @param string $parcel_url   The API URL for checking single address.
 * @param array  $body_request The data array. SHould contain country code, zip code, shopID, transportSolutionID and
 * recommended, but optional, the address (street, number).
 *
 * @return mixed|string|\WP_Error Empty, if no token active. Data if the address was found and there is an active carrier. Error, for anything else.
 */
function helthjem_nearby_check( string $parcel_url, array $body_request ) { //phpcs:ignore
	$return = '';

	if ( ! is_token_set() || ! is_token_active() ) {
		$updated = update_token();
		if ( $updated ) {
			sleep( 3 );
			$token = get_option( 'hcsng_api_token' );
		}
	} else {
		$token = get_option( 'hcsng_api_token' );
	}

	if ( ! empty( $token ) ) {

		$request_args = [
			'method'  => 'POST',
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $body_request ),
		];

		$request = wp_remote_get( $parcel_url, $request_args );

		if ( is_wp_error( $request ) ) {
			$return = new \WP_Error( $request['response']['code'], $request['body'] );
		} else {
			if ( 200 !== $request['response']['code'] ) {
				$return = new \WP_Error( $request['response']['code'], $request['body'] );
			} else {
				$return = json_decode( $request['body'] );

			}
		}
	}

	return $return;
}
