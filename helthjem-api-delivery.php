<?php
/**
 * HeltHjem - Consignor Helper for WooCommerce
 *
 * @package     HCSNG
 * @author      Alexandru Negoita
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: HeltHjem - Consignor Helper for WooCommerce
 * Description: This plugin will take the zip code provided by the user and check it against the HeltHjem available services.
 *              If you have an available service on HeltHjem, which is also compatible with you shipping options, it will show it to the checkout page.
 * Version:     1.0.0
 * Author:      Alexandru Negoita
 * Text Domain: hcsng
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

declare(strict_types=1);

namespace HCSNG;

if ( is_admin() ) {
	include 'api/api.php';
	include 'admin/ajax.php';
	include 'admin/hcnsg-fields.php';
	include 'admin/settings.php';
}

if ( ! is_admin() ) {
	include 'api/api.php';
	include 'front/shipping-options.php';
}



/**
 * Enqueue admin scripts and styles.
 */
function enque_admin_scripts() {
	// Get plugin headers data.
	$plugin_data = \get_plugin_data( __FILE__ );
	$screen      = \get_current_screen();

	if ( 'woocommerce_page_wc-settings' === $screen->id ) {
		wp_enqueue_script( 'hcnsg-admin-js', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'src/hcnsg-admin.js', [ 'jquery' ], '', true );
		wp_enqueue_style( 'hcnsg-admin-css', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'src/hcnsg-admin.css', [], $plugin_data['Version'] );

		wp_localize_script(
			'hcnsg-admin-js',
			'hcnsg',
			[
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'hcnsg_ajax' ),
			]
		);
	}
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enque_admin_scripts' );

