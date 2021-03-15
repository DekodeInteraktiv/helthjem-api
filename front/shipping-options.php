<?php
/**
 * SHipping options for HeltHjem.
 *
 * @package HCSNG
 */

declare(strict_types=1);

namespace HCSNG\FRONT\SHIPPING;

use function HCSNG\LOGIN\V3\PARCEL\helthjem_single_address_check;

defined( 'ABSPATH' ) || exit;

/**
 * Filters out the shipping options that has a HeltHjem transport Solution ID added,
 * but we don't find any carrier for the required zip code.
 *
 * @param array $rates   The rates that are applying for the package. We get the shipping options from it.
 * @param array $package The package itself. We get the shipping details from it.
 *
 * @return array Returns the filtered rates.
 */
function check_shipping_address( array $rates, array $package ) : array {
	if ( ! empty( $rates ) && isset( $package['destination'] ) ) {
		$destination    = $package['destination'];
		$cache_adresses = get_option( 'hcnsg_already_checked_zips' );

		if ( ! empty( $destination ) && ! empty( get_option( 'hcnsg_shopID' ) ) ) {
			$checking_args = [
				'shopId'      => get_option( 'hcnsg_shopID' ),
				'countryCode' => $destination['country'],
				'postalName'  => $destination['city'],
				'zipCode'     => $destination['postcode'],
				'address'     => $destination['address'],
			];

			foreach ( $rates as $rate_key => $rate ) {
				$method_id   = $rate->__get( 'method_id' );
				$instance_id = $rate->__get( 'instance_id' );
				$options     = get_option( 'woocommerce_' . $method_id . '_' . $instance_id . '_settings' );

				if ( ! isset( $cache_adresses[ $destination['postcode'] ][ $rate_key ] ) ) {
					if ( ! empty( $options['helthjem_trasport_id'] ) ) {
						$url                                  = get_option( 'hcnsg_adress_check_url' );
						$checking_args['transportSolutionId'] = $options['helthjem_trasport_id'];
						$address_check                        = helthjem_single_address_check( $url, $checking_args );

						if ( is_wp_error( $address_check ) ) {
							$cache_adresses[ $destination['postcode'] ][ $rate_key ] = 'no';
							unset( $rates[ $rate_key ] );
						} else {
							$cache_adresses[ $destination['postcode'] ][ $rate_key ] = 'yes';
						}

						update_option( 'hcnsg_already_checked_zips', $cache_adresses );
					}
				} else {
					if ( 'no' === $cache_adresses[ $destination['postcode'] ][ $rate_key ] ) {
						unset( $rates[ $rate_key ] );
					}
				}
			}
		}
	}

	return $rates;
}

add_filter( 'woocommerce_package_rates', __NAMESPACE__ . '\\check_shipping_address', 10, 2 );
