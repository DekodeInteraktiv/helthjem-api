<?php
/**
 * HeltHjem token processing functions.
 *
 * @package HCSNG
 */

declare(strict_types=1);

namespace HCSNG\LOGIN\V3\TOKEN;

// Actions and filters.
add_action( 'admin_notices', __NAMESPACE__ . '\\show_error_notice' );

/**
 * Retrieve token from API.
 *
 * @param string $username   The username provided by Helthjem.
 * @param string $password   The password for the account.
 * @param string $url        The Login API url from HeltHjem.
 * @param int    $expiration The expiration time. For V3, the maximum is 1440. Necessary only in V3 login connection.
 *
 * @return bool | \WP_Error
 */
function maybe_set_token( string $username = '', string $password = '', string $url = '', int $expiration = 1440 )  { //phpcs:ignore

	if ( ! is_token_active() ) {
		$request_args = [
			'method'  => 'POST',
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => wp_json_encode(
				[
					'username' => $username,
					'password' => $password,
				]
			),
		];

		$request = wp_remote_get( trailingslashit( $url ) . $expiration, $request_args );

		if ( is_wp_error( $request ) ) {
			$token_set = false;
		} else {
			if ( 200 !== $request['response']['code'] ) {
				return new \WP_Error( $request['response']['code'], $request['body'] );
			} else {
				$body = json_decode( $request['body'] );

				set_transient( 'hcsng_token_active', strtotime( gmdate( 'now' ) ) );

				$token_set = update_option( 'hcsng_api_token', $body->token );
			}
		}
	}

	return $token_set;
}

/**
 * Check if the token is active.
 *
 * @return bool
 */
function is_token_active() : bool {
	return ( 0 !== absint( get_transient( 'hcsng_token_active' ) ) ) ? true : false;
}

/**
 * Deletes the token.
 */
function delete_token() {
	delete_option( 'hcsng_api_token' );
	delete_transient( 'hcsng_token_active' );
}

/**
 * Determines if the token needs deletion.
 *
 * @return bool If the token needs deletion or not, considering the expiration time.
 */
function maybe_delete_token() : bool {
	$expiration      = ( ! empty( get_option( 'hcsng_expiration_time' ) ) ) ? get_option( 'hcsng_expiration_time' ) : 1440;
	$expiration_time = ( defined( 'MINUTE_IN_SECONDS' ) ) ? $expiration * MINUTE_IN_SECONDS : absint( get_option( 'hcsng_expiration_time' ) ) * 60;
	$now_time        = strtotime( gmdate( 'now' ) );
	$token           = get_transient( 'hcsng_token_active' );
	$token_time      = ( 0 < absint( $now_time - $token ) ) ? $now_time - $token : -1;

	if ( -1 !== $token_time && $expiration_time < $token_time ) {
		delete_option( 'hcsng_api_token' );
		delete_transient( 'hcsng_token_active' );

		return true;
	} else {
		return false;
	}
}

/**
 * Update token.
 *
 * @return bool True is token updated. False otherwise.
 */
function update_token() : bool {
	$expiration = get_option( 'hcsng_expiration_time' );
	$username   = get_option( 'hcnsg_username' );
	$password   = get_option( 'hcnsg_password' );
	$url        = get_option( 'hcnsg_login_url' );

	if ( ! empty( $username ) && ! empty( $password ) && ! empty( $url ) && ! empty( $expiration ) ) {
		$set_token = maybe_set_token( $username, $password, $url, absint( $expiration ) );

		if ( \is_wp_error( $set_token ) ) {
			$code    = $set_token->get_error_code();
			$message = $code . ' : ' . $set_token->get_error_message();

			set_transient( 'hcsng_token_error', $message );
		} else {
			if ( get_transient( 'hcsng_token_error' ) ) {
				delete_transient( 'hcsng_token_error' );
			}
		}
		return true;
	} else {
		return false;
	}
}

/**
 * Determines wheter the token needs updating, considering the expiration time. See maybe_delete_token().
 */
function maybe_update_token() {
	if ( maybe_delete_token() ) {
		update_token();
	}
}

/**
 * Shows error if there is an issue with retreiving the token from HeltHjem.
 */
function show_error_notice() {
	if ( get_transient( 'hcsng_token_error' ) ) {
		echo sprintf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( get_transient( 'hcsng_token_error' ) ),
		);
	}
}

/**
 * Check if token has been created.
 *
 * @return bool
 */
function is_token_set() : bool {
	return ( ! empty( get_option( 'hcsng_api_token' ) ) ) ? true : false;
}
