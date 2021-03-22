<?php
/**
 * SHipping options for HeltHjem.
 *
 * @package HCSNG
 */

declare(strict_types=1);

namespace HCSNG\EMAIL;

defined( 'ABSPATH' ) || exit;

// Actions and filters.
add_action( 'woocommerce_email_after_order_table', __NAMESPACE__ . '\\add_pickup_point_to_emails', 10, 3 );
add_filter( 'woocommerce_email_styles', __NAMESPACE__ . '\\add_pickup_point_email_css' );


/**
 * Adds the pickupt point, if any, to the emails.
 *
 * @param object $order         The order instance.
 * @param bool   $sent_to_admin Whether is sent to admin or not.
 * @param bool   $plain_text    Wheteher is plain text or HTML.
 */
function add_pickup_point_to_emails( object $order, bool $sent_to_admin, bool $plain_text ) {
	$nearby_point = get_post_meta( $order->get_id(), '_hcnsg_nearby', true );

	if ( ! empty( $nearby_point ) ) {

		if ( ! $plain_text ) {

			echo wp_kses_post(
				sprintf(
					'<div class="pickup_point_address"><h2 class="woocommerce-column__title">%1$s</h2>%2$s</div>',
					esc_html__( 'Pickup Point', 'hcnsg' ),
					$nearby_point
				)
			);
		} else {
			$text = esc_html__( 'Pickup Point', 'hcnsg' ) . PHP_EOL . '---------' . PHP_EOL . wp_strip_all_tags( $nearby_point, false );
			echo esc_html( $text );
		}
	}
}

/**
 * Style the pickup point in WooCommerce Emails.
 *
 * @param string $css The CSS applied to the email.
 * @return string     The CSS plus the new pick-up point style.
 */
function add_pickup_point_email_css( string $css ) : string {
	return $css .= '
		.pickup_point_address {margin-bottom:40px;}
		.pickup_point_address p {display:block; padding: 12px; border: 1px solid #e5e5e5;}
		.pickup_point_address p strong {display:inline-block; padding-bottom:4px;}
	';

}
