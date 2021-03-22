<?php
/**
 * Admin extra functionality.
 *
 * @package HCSNG
 */

declare(strict_types=1);

namespace HCSNG\ADMIN\HELPERS;

use function HCSNG\LOGIN\V3\TOKEN\is_token_set;

defined( 'ABSPATH' ) || exit;

/**
 * Render the settings fields.
 *
 * @return array
 */
function render_settings() : array {
	$fields = [];
	if ( ! is_token_set() ) {
		$login_fields = [
			'hcsng_login_start' => [
				'name' => esc_html__( 'HeltHjem Login information', 'hcsng' ),
				'type' => 'title',
				'desc' => esc_html__( 'This are needed resources in order to connect and receive the token for the API communication.', 'hcsng' ),
				'id'   => 'hcsng_login_start',
			],
			'hcnsg_username'    => [
				'id'       => 'hcnsg_username',
				'name'     => esc_html__( 'Username: ', 'hcsng' ),
				'type'     => 'text',
				'desc'     => esc_html__( 'The username that you use for login to the HeltHjem account.', 'hcsng' ),
				'desc_tip' => true,
			],
			'hcnsg_password'    => [
				'id'       => 'hcnsg_password',
				'name'     => esc_html__( 'Password:', 'hcsng' ),
				'type'     => 'password',
				'desc'     => esc_html__( 'The password that you use for login to the HeltHjem account.', 'hcsng' ),
				'desc_tip' => true,
			],
			'hcsng_login_end'   => [
				'type' => 'sectionend',
				'id'   => 'hcsng_login_end',
			],
		];

		$fields = array_merge( $fields, $login_fields );
	}

	$token_extra_fields = [
		'hcsng_login_extra_start' => [
			'name' => esc_html__( 'HeltHjem API connection settings.', 'hcsng' ),
			'type' => 'title',
			'desc' => esc_html__( 'This are needed resources in order to connect and receive the token for the API communication.', 'hcsng' ),
			'id'   => 'hcsng_login_start',
		],
		'hcnsg_login_url'         => [
			'id'       => 'hcnsg_login_url',
			'name'     => esc_html__( 'API LOGIN URL:', 'hcsng' ),
			'type'     => 'text',
			'desc'     => esc_html__( 'The API base url. Default: https://ws.di.no/ws/json/auth/v-3/login/', 'hcsng' ),
			'default'  => 'https://ws.di.no/ws/json/auth/v-3/login/',
			'desc_tip' => true,
		],
		'hcsng_expiration_time'   => [
			'id'       => 'hcsng_expiration_time',
			'name'     => esc_html__( 'Token expiration time:', 'hcsng' ),
			'type'     => 'text',
			'desc'     => esc_html__( 'Expiration time in minutes. Values from 1 to max of 1440. Default: 1440.', 'hcsng' ),
			'desc_tip' => true,
			'default'  => '1440',
		],
		'hcsng_login_extra_end'   => [
			'type' => 'sectionend',
			'id'   => 'hcsng_login_end',
		],
	];

	$fields = array_merge( $fields, $token_extra_fields );

	if ( is_token_set() ) {
		$token_active_fields = [
			'hcsng_details_start'      => [
				'name' => esc_html__( 'HeltHjem Additional Settings', 'hcsng' ),
				'type' => 'title',
				'desc' => esc_html__( 'Settings in reagrds with the Helthjem account.', 'hcsng' ),
				'id'   => 'hcsng_details_start',
			],
			'hcnsg_tokenInfo'          => [
				'name'              => esc_html__( 'Token: ', 'hcsng' ),
				'type'              => 'text',
				'default'           => preg_replace( '/(^.{3}|.{3}$)(*SKIP)(*F)|(.)/', '*', substr( get_option( 'hcsng_api_token' ), 0, 20 ) ) . '*******',
				'custom_attributes' => [
					'readonly' => 'readonly',
				],
			],
			'hcnsg_adress_check_url'   => [
				'id'       => 'hcnsg_adress_check_url',
				'name'     => esc_html__( 'Adress Check API URL: ', 'hcsng' ),
				'type'     => 'text',
				'desc'     => esc_html__( 'URL from HeltHjem API for checking single address. Please contact HeltHjem team.', 'hcsng' ),
				'desc_tip' => true,
			],
			'hcnsg_shopID'             => [
				'id'       => 'hcnsg_shopID',
				'name'     => esc_html__( 'Shop ID: ', 'hcsng' ),
				'type'     => 'text',
				'desc'     => esc_html__( 'This ID should be provided by HeltHjem. Please contact HeltHjem team.', 'hcsng' ),
				'desc_tip' => true,
			],
			'hcnsg_transportSolutions' => [
				'id'                => 'hcnsg_transportSolutions',
				'name'              => esc_html__( 'Transport Solutions: ', 'hcsng' ),
				'type'              => 'textarea',
				'custom_attributes' => [
					'cols' => '30',
					'rows' => '10',
				],
				'desc_tip'          => false,
				'desc'              => sprintf(
					'<strong>%1$s</strong><br/><small>%2$s</small><pre style="background-color: #efefef; color: #8e8c8c; width: 250px;padding: 10px;border-radius: 3px;border: 1px solid #dedede; cursor: not-allowed;">%3$s</pre>',
					esc_html__( 'They will appear in the WooCommerce > Settings > Shipping', 'hcnsg' ),
					esc_html__( '( Edit any Shipping zone or create one. At the Shipping methods for that zone, add a shpping method. Edit Shipping method just added and you will see the HeltHjem TransportID selector in the popup. )', 'hcnsg' ),
					wp_kses_post( 'Example:<br/>First option name : 23<br/>Second option name : 45<br/>Third option name : 67' )
				),
			],
			'hcnsg_debug'              => [
				'id'       => 'hcnsg_debug',
				'name'     => esc_html__( 'Debugging: ', 'hcsng' ),
				'type'     => 'checkbox',
				'desc'     => esc_html__( 'Extra details for developers.', 'hcsng' ),
				'desc_tip' => false,
			],
			'hcnsg_style_enhance'      => [
				'id'       => 'hcnsg_style_enhance',
				'name'     => esc_html__( 'Style enhance: ', 'hcsng' ),
				'type'     => 'checkbox',
				'desc'     => esc_html__( 'Adds some style and some scripting in the front-end.', 'hcsng' ),
				'desc_tip' => false,
			],
			'hcsng_details_end'        => [
				'type' => 'sectionend',
				'id'   => 'hcsng_details_end',
			],
		];

		$fields = array_merge( $fields, $token_active_fields );
	}

	return apply_filters( 'wc_settings_tab_hcsng_settings', $fields );
}
