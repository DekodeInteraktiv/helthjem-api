<?php
/**
 * Adds plugin's options to the WooCommerce settings.
 *
 * @package HCSNG
 */

declare(strict_types=1);

namespace HCSNG\ADMIN\SETTINGS;

use function HCSNG\ADMIN\HELPERS\render_settings;
use function HCSNG\LOGIN\V3\TOKEN\is_token_set;
use function HCSNG\LOGIN\V3\TOKEN\maybe_set_token;
use function HCSNG\LOGIN\V3\TOKEN\is_token_active;

defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_init', __NAMESPACE__ . '\\add_shipping_option_transport_id' );
add_action( 'woocommerce_settings_tabs_hcsng_settings', __NAMESPACE__ . '\\append_settings_to_tab' );
add_action( 'woocommerce_update_options_hcsng_settings', __NAMESPACE__ . '\\update_settings' );
add_filter( 'woocommerce_settings_tabs_array', __NAMESPACE__ . '\\settings_tab', PHP_INT_MAX );

/**
 * Add the plugin settings tab.
 *
 * @param array $tabs The previous tabs array.
 *
 * @return array.
 */
function settings_tab( array $tabs ) : array {
	$tabs['hcsng_settings'] = esc_html__( 'HelHjem Settings', 'hcsng' );
	return $tabs;
}

/**
 * Add the settings fields.
 */
function append_settings_to_tab() {
	woocommerce_admin_fields( render_settings() );

	if ( is_token_set() ) {
		// Extra, non-WooCommerce, settings, such as buttons for clearing data.
		start_custom_settings();
		clear_login();
		clear_address_cache();
		check_nearby_pickups();
		end_custom_settings();
	}
}

/**
 * Saving the data from the fields.
 */
function update_settings() {
	woocommerce_update_options( render_settings() );
}

/**
 * Render the HeltHjem transportID field for each Shipping method.
 *
 * @param array $fields Shipping method instance fields.
 *
 * @return array        Shipping method instance fields and extra fields.
 */
function render_transport_id_field( array $fields ) : array {
	if ( ! empty( get_option( 'hcnsg_transportSolutions' ) ) ) {
		$options = [
			'' => esc_html__( 'Choose a TransportSolution', 'hcnsg' ),
		];

		foreach ( explode( PHP_EOL, get_option( 'hcnsg_transportSolutions' ) ) as $solution ) {

			$solution_array = explode( ':', $solution );

			if ( is_array( $solution_array ) && 2 === count( $solution_array ) ) {
				$options[ trim( $solution_array[1] ) ] = trim( $solution_array[0] );
			}
		}

		$fields['helthjem_trasport_id'] = [
			'id'          => 'helthjem_trasport_id',
			'title'       => esc_html__( 'HeltHjem TransportID: ', 'hcsng' ),
			'type'        => 'select',
			'default'     => '',
			'placeholder' => '',
			'description' => esc_html__( 'Add the provided ID for this field. If you don\'t have one, please get in touch with the HeltHjem team.', 'hcsng' ),
			'options'     => $options,
			'desc_tip'    => true,
		];
	}
	return apply_filters( 'wc_settings_tab_hcsng_settings', $fields );
}

/**
 * Add the HeltHjem transportID field to each Shipping method.
 */
function add_shipping_option_transport_id() {
	$shipping_methods = WC()->shipping()->get_shipping_methods();
	if ( ! empty( $shipping_methods ) ) {
		foreach ( $shipping_methods as $method ) {
			add_filter( 'woocommerce_shipping_instance_form_fields_' . $method->id, __NAMESPACE__ . '\\render_transport_id_field' );
		}
	}
}

/**
 * Using the filter to set the token from the settings page.
 *
 * @param string $value The value of the input.
 * @return string
 */
function wc_hcsng_maybe_time_new( string $value ) : string {
	if ( ! empty( $value ) ) {
		$username = filter_input( INPUT_POST, 'hcnsg_username', FILTER_SANITIZE_STRING );
		$password = filter_input( INPUT_POST, 'hcnsg_password', FILTER_SANITIZE_STRING );
		$url      = filter_input( INPUT_POST, 'hcnsg_login_url', FILTER_SANITIZE_STRING );

		if ( ! empty( $username ) && ! empty( $password ) && ! empty( $url ) && ! is_token_active() ) {
			$set_token = maybe_set_token( $username, $password, $url, absint( $value ) );

			if ( \is_wp_error( $set_token ) ) {
				$code    = $set_token->get_error_code();
				$message = $code . ' : ' . $set_token->get_error_message();

				set_transient( 'hcsng_token_error', $message );
			} else {
				if ( get_transient( 'hcsng_token_error' ) ) {
					delete_transient( 'hcsng_token_error' );
				}
			}
		}
	}

	return $value;
}
add_filter( 'pre_update_option_hcsng_expiration_time', __NAMESPACE__ . '\\wc_hcsng_maybe_time_new', 10 );

/**
 * Custom settings start.
 */
function start_custom_settings() {
	echo wp_kses_post( '<hr><table class="form-table"><tbody><h2>' . esc_html__( 'Plugin helper tools and extra settings', 'hcnsg' ) . '</h2>' );
}

/**
 * Custom settings end.
 */
function end_custom_settings() {
	echo wp_kses_post( '</tbody></table>' );
}

/**
 * Render the reset login data helper.
 */
function clear_login() {
	?>
	<tr>
		<th scope="row" class="titledesc">
			<p><?php echo esc_html__( 'Clear login data: ', 'hcnsg' ); ?></p>
		</th>
		<td class="forminp forminp-custom-button">
			<p><?php echo esc_html__( 'Use this if you want to connect with another account.', 'hcnsg' ); ?><br/><br/></p>
			<span id="hcnsg_clear_login" class="button button-secondary"><?php echo esc_html__( 'Clear data' ); ?></span>
		</td>
	</tr>
	<?php
}

/**
 * Render the clearing cache helper.
 */
function clear_address_cache() {
	?>
	<tr>
		<th scope="row" class="titledesc">
			<p><?php echo esc_html__( 'Clear used addresses cache: ', 'hcnsg' ); ?></p>
		</th>
		<td class="forminp forminp-custom-button">
			<p><?php echo esc_html__( 'Use this if you want to connect with another account.', 'hcnsg' ); ?><br/><br/></p>
			<span id="hcnsg_clear_clear_cache" class="button button-secondary"><?php echo esc_html__( 'Clear cache' ); ?></span>
		</td>
	</tr>
	<?php
}

/**
 * Render the nearby pick-up points helper.
 */
function check_nearby_pickups() {
	?>
	<tr>
		<th scope="row" class="titledesc">
			<label for="hcnsg_clear_clear_cache"><?php echo esc_html__( 'Nearby Services: ', 'hcnsg' ); ?></label>
		</th>
		<td class="forminp forminp-pickup-points">
			<p><?php echo esc_html__( 'Check nearby available pick-up points, by Country and ZipCode.', 'hcnsg' ); ?><br/><br/></p>
			<div class="data">
				<div>
					<input id="hcnsg_zipcode" placeholder="<?php echo esc_html__( 'ZIP code', 'hcnsg' ); ?>" type="text" />
					<select id="hcnsg_transportID">
						<option value=""><?php echo esc_html__( 'Transport solution', 'hcnsg' ); ?></option>
						<?php
						foreach ( explode( PHP_EOL, get_option( 'hcnsg_transportSolutions' ) ) as $solution ) {

							$solution_array = explode( ':', $solution );

							if ( is_array( $solution_array ) && 2 === count( $solution_array ) ) {
								?>

									<option value="<?php echo esc_attr( trim( $solution_array[1] ) ); ?>" ><?php echo esc_html( trim( $solution_array[0] ) ); ?></option>

								<?php
							}
						}
						?>
					</select>
					<select id="hcnsg_country">
						<option value=""><?php echo esc_html__( 'Country code', 'hcnsg' ); ?></option>
						<?php WC()->countries->country_dropdown_options(); ?>
					</select>
					<br/>
					<span id="checkpickup_submit" class="button button-secondary"><?php echo esc_html__( 'Get pick-up points' ); ?></span>
				</div>
				<div class="pickup-points" id="hcnsg_pick-up-points"></div>
			</div>
		</td>
	</tr>
	<?php
}
