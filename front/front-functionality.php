<?php
/**
 * SHipping options for HeltHjem.
 *
 * @package HCSNG
 */

declare(strict_types=1);

namespace HCSNG\FRONT\SHIPPING;

use function HCSNG\LOGIN\V3\PARCEL\helthjem_nearby_check;
use function HCSNG\LOGIN\V3\PARCEL\helthjem_single_address_check;

defined( 'ABSPATH' ) || exit;

// Actions and filters.
add_action( 'woocommerce_after_shipping_rate', __NAMESPACE__ . '\\append_shipping_options', 10, 2 );
add_action( 'woocommerce_checkout_update_order_meta', __NAMESPACE__ . '\\add_shipping_pickuppoint_to_order_details', 10, 2 );
add_filter( 'woocommerce_package_rates', __NAMESPACE__ . '\\check_shipping_address', 10, 2 );
add_filter( 'woocommerce_order_get_formatted_shipping_address', __NAMESPACE__ . '\\add_pickupoint_to_customer_details', 10, 3 );

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

				if ( in_array( $options['helthjem_trasport_id'], get_option( 'hcnsg_api_points_solutions' ), true ) ) {
					$request = [
						'shopId'              => get_option( 'hcnsg_shopID' ),
						'transportSolutionId' => $options['helthjem_trasport_id'],
						'zipCode'             => $destination['postcode'],
						'countryCode'         => $destination['country'],
					];

					if ( is_checkout() ) {
						$nearbypoints_call = helthjem_nearby_check( 'https://staging-ws.di.no/ws/json/freightcoverage/v-1/servicepoints', $request );

						if ( ! is_wp_error( $nearbypoints_call ) && ! empty( $nearbypoints_call ) ) {
							$this_rate    = $rates[ $rate_key ];
							$nearbypoints = [];

							foreach ( $nearbypoints_call->freightProducts as $freights ) { //phpcs:ignore
								$rate_title = $freights->freightTitle;

								if ( ! empty( $freights->servicePoints ) ) {
									foreach ( $freights->servicePoints as $service_point ) {
										$service_name = $service_point->servicePointName;

										$opening_hours = [
											'MONDAY'    => '',
											'TUESDAY'   => '',
											'WEDNESDAY' => '',
											'THURSDAY'  => '',
											'FRIDAY'    => '',
											'SATURDAY'  => '',
											'SUNDAY'    => '',
										];

										foreach ( $service_point->openingHours as $opening_hour ) {

											if ( ! empty( $opening_hour->from1 ) && ! empty( $opening_hour->to1 ) ) {
												if ( $opening_hour->from1 === $opening_hour->to1 ) {
													$hours = esc_html__( '24/7', 'hcnsg' );
												} else {
													$hours = $opening_hour->from1 . ' - ' . $opening_hour->to1;
												}
											} else {
												$hours = esc_html__( 'Closed', 'hcnsg' );
											}

											$opening_hours[ $opening_hour->day ] = $hours;
										}

										foreach ( $opening_hours as $day => $hours ) {
											if ( empty( $hours ) ) {
												$opening_hours[ $day ] = esc_html__( 'Closed', 'hcnsg' );
											}
										}

										$opening_hours = array_change_key_case( $opening_hours, CASE_LOWER );

										$street = ( ! empty( $service_point->deliveryAddress->streetName ) ) ? $service_point->deliveryAddress->streetName : '';
										$number = ( ! empty( $service_point->deliveryAddress->streetNumber ) ) ? $service_point->deliveryAddress->streetNumber : '';
										$city   = ( ! empty( $service_point->deliveryAddress->postalName ) ) ? $service_point->deliveryAddress->postalName : '';
										$north  = ( ! empty( $service_point->servicePointCoordinates->northing ) ) ? $service_point->servicePointCoordinates->northing : '';
										$east   = ( ! empty( $service_point->servicePointCoordinates->easting ) ) ? $service_point->servicePointCoordinates->easting : '';

										$delivery_address = [
											'address' => $street . ' ' . $number . ', ' . $city,
											'hours'   => $opening_hours,
											'gps'     => $north . ', ' . $east,
										];

										$nearbypoints[] = [
											'id'           => 'servicepoint_' . $service_point->servicePointExternalId,
											'name'         => $rate_title . ' - ' . $service_name,
											'service_name' => $service_name,
											'opened'       => $opening_hours,
											'location'     => $delivery_address,
										];
									}
								}
							}

							$this_rate->add_meta_data( 'hcnsg_nearby_points', $nearbypoints );
						}
					}
				}
			}
		}
	}

	return $rates;
}

/**
 * Add the pickup points to the shipping rate.
 *
 * @param \WC_Shipping_Rate $shipping_rate The shipping rate.
 * @param int               $index The index of it in the shiiping rates array.
 */
function append_shipping_options( \WC_Shipping_Rate $shipping_rate, int $index ) {
	if ( is_checkout() ) {
		$meta_data = $shipping_rate->get_meta_data();

		if ( ! empty( $meta_data['hcnsg_nearby_points'] ) ) {
			printf(
				'<ul class="hcnsg-nearby-points hidden" data-id="%2$s"><p><strong>%1$s</strong></p>',
				( 1 < count( $meta_data['hcnsg_nearby_points'] ) ) ? esc_html__( 'Choose a pickup point:', 'hcnsg' ) : '',
				esc_attr(
					'shipping_method_' . $index . '_' . str_replace( ':', '', $shipping_rate->get_id() )
				)
			);

			foreach ( $meta_data['hcnsg_nearby_points'] as $service_point ) {
				echo '<li>';
				if ( 1 < count( $meta_data['hcnsg_nearby_points'] ) ) {
					printf(
						'<input type="radio" name="%2$s" id="%1$s" value="%1$s" class="shipping_method servicepoint"/>',
						esc_attr( $service_point['id'] ),
						esc_attr( 'nerabypoints_' . str_replace( ':', '', $shipping_rate->get_id() ) )
					);
				} else {
					printf(
						'<input type="hidden" name="%2$s" id="%1$s" value="%1$s"/>',
						esc_attr( $service_point['id'] ),
						esc_attr( 'nerabypoints_' . str_replace( ':', '', $shipping_rate->get_id() ) )
					);
				}
				printf(
					'<label for="%1$s"><span class="name">%2$s</span><br/><span class="address">%3$s</span><br/><span class="openings">%4$s</span><br/>',
					esc_attr( $service_point['id'] ),
					esc_html( $service_point['name'] ),
					esc_html( $service_point['location']['address'] ),
					esc_html__( 'Schedule: ', 'hcnsg' )
				);
				if ( ! empty( $service_point['opened'] ) ) {
					foreach ( $service_point['opened'] as $day => $hours ) {
						printf(
							'<span class="open-day"><span class="day">%1$s</span>%2$s<span class="hours">%3$s</span></span><br/>',
							esc_html( ( $day ) ),
							esc_html__( ': ', 'hcnsg' ),
							esc_html( $hours )
						);
					}
				}
				echo '</li>';
			}
			echo '</ul>';
		}
	}
}

/**
 * Add the pick-up point to the order meta data.
 *
 * @param int   $order_id Order ID.
 * @param array $data     The order data before save.
 */
function add_shipping_pickuppoint_to_order_details( int $order_id, array $data ) {
	$shipping_method = str_replace( ':', '', current( $data['shipping_method'] ) );
	$nearbypoint     = filter_input( INPUT_POST, 'nerabypoints_' . $shipping_method, FILTER_SANITIZE_STRING );

	if ( ! empty( $nearbypoint ) ) {

		foreach ( WC()->shipping->get_packages() as $key => $package ) {

			foreach ( $package['rates'] as $rate_id => $rate ) {

				if ( current( $data['shipping_method'] ) === $rate_id ) {
					$meta_data = $rate->get_meta_data();

					if ( ! empty( $meta_data['hcnsg_nearby_points'] ) ) {
						foreach ( $meta_data['hcnsg_nearby_points'] as $item ) {
							if ( $nearbypoint === $item['id'] ) {
								$html_hours = PHP_EOL;

								if ( ! empty ( $item['location']['hours'] ) ) {
									foreach ( $item['location']['hours'] as $day => $hours ) {
										$html_hours .= sprintf(
											'<span class="day-line"><span class="day">%s</span><span class="hours">%s</span></span><br/>',
											esc_html( $day . ':' ),
											esc_html( $hours )
										);
									}
								}

								$html = sprintf(
									'<p class="hcnsg-order-nearby-point"><strong>%1$s</strong><br/>%2$s<br/><span class="opening-hours">%3$s</span></p>',
									esc_html( $item['service_name'] ),
									esc_html( $item['location']['address'] ),
									wp_kses_post( $html_hours )
								);

								\update_post_meta( $order_id, '_hcnsg_nearby', $html );
							}
						}
					}
				}
			}
		}
	}
}

/**
 * Add the pickup point to the order Thank you overview.
 *
 * @param string    $address     The shipping address.
 * @param array     $raw_address The raw shipping address.
 * @param \WC_Order $order    The order instance.
 *
 * @return string
 */
function add_pickupoint_to_customer_details( string $address, array $raw_address, \WC_Order $order ) : string {
	if ( is_checkout() ) {
		$nearby_point = get_post_meta( $order->get_id(), '_hcnsg_nearby', true );

		if ( ! empty( $nearby_point ) ) {
			$address .= '</address><br/><br/>' . wp_kses_post(
				sprintf(
					'<div class="pickup_point_address"><h2 class="woocommerce-column__title">%1$s</h2>%2$s</div>',
					esc_html__( 'Pickup Point: ', 'hcnsg' ),
					$nearby_point
				)
			);
		}
	}
	return $address;
}
