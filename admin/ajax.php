<?php
/**
 * DATA processed through AJAX calls.
 *
 * @package HCSNG
 */

declare(strict_types=1);

namespace HCSNG\AJAX;

use function HCSNG\LOGIN\V3\PARCEL\helthjem_nearby_check;

defined( 'ABSPATH' ) || exit;

// Actions and filters.
add_action( 'wp_ajax_hcnsg_clear_login', __NAMESPACE__ . '\\clear_ogin_data' );
add_action( 'wp_ajax_hcnsg_clear_cache', __NAMESPACE__ . '\\clear_cache_address_data' );
add_action( 'wp_ajax_hcnsg_get_nearby_points', __NAMESPACE__ . '\\check_nearby_sevice_api_points' );

/**
 * Clearing the data for the old HeltHjem account.
 */
function clear_ogin_data() {
	$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );

	if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'hcnsg_ajax' ) ) {
		delete_option( 'hcnsg_username' );
		delete_option( 'hcnsg_password' );
		delete_option( 'hcsng_api_token' );
		delete_transient( 'hcsng_expiration_time' );
		wp_send_json( esc_html__( 'Data deleted.', 'hcnsg' ) );
	} else {
		wp_send_json( esc_html__( 'Could not verify nonce. Are you logged in?', 'hcnsg' ) );
	}
}

/**
 * Clearing the HeltHjem cache for the already checked adresses.
 */
function clear_cache_address_data() {
	$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
	if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'hcnsg_ajax' ) ) {
		$cleared = delete_option( 'hcnsg_already_checked_zips' );
		if ( $cleared ) {
			wp_send_json( esc_html__( 'Addresses cache cleared.', 'hcnsg' ) );
		} else {
			wp_send_json( esc_html__( 'Cannot clear cache. It may be already deleted.', 'hcnsg' ) );
		}
	} else {
		wp_send_json( esc_html__( 'Could not verify nonce. Are you logged in?', 'hcnsg' ) );
	}
}

/**
 * Get the nearby service API points.
 */
function check_nearby_sevice_api_points() {
	$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );

	if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'hcnsg_ajax' ) ) {
		$zip_code     = filter_input( INPUT_POST, 'zip', FILTER_SANITIZE_STRING );
		$country      = filter_input( INPUT_POST, 'country', FILTER_SANITIZE_STRING );
		$transport_id = filter_input( INPUT_POST, 'transport', FILTER_SANITIZE_STRING );
		$shop_id      = get_option( 'hcnsg_shopID' );

		if ( empty( $zip_code ) || empty( $country ) || empty( $transport_id ) || empty( $shop_id ) ) {
			wp_send_json( esc_html__( 'Not all requested values have been added.', 'hcnsg' ) );
		} else {
			$url = 'https://staging-ws.di.no/ws/json/freightcoverage/v-1/servicepoints';

			$request = [
				'shopId'              => $shop_id,
				'transportSolutionId' => $transport_id,
				'zipCode'             => $zip_code,
				'countryCode'         => $country,
			];

			$response = helthjem_nearby_check( $url, $request );

			wp_send_json( $response );

		}
	} else {
		wp_send_json( esc_html__( 'Could not verify nonce. Are you logged in?', 'hcnsg' ) );
	}
}
