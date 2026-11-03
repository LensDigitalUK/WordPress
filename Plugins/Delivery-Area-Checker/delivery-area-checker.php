<?php
/**
 * Plugin Name: Delivery Area Checker
 * Plugin URI: https://github.com/LensDigitalUK/WordPress/tree/master/Plugins/Delivery-Area-Checker
 * Description: Adds a UK postcode checker that verifies whether free delivery is offered in a selected WooCommerce shipping zone.
 * Version: 1.0.1
 * Author: Lens Digital
 * Author URI: https://wearelens.co.uk/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.5
 * Tested up to: 7.1.4
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Text Domain: delivery-area-checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DAC_Delivery_Area_Checker {
	const VERSION = '1.0.1';
	const OPTION  = 'dac_settings';
	const NONCE   = 'dac_check_postcode';

	public function __construct() {
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_shortcode( 'delivery_area_checker', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_ajax_dac_check_postcode', array( $this, 'ajax_check_postcode' ) );
		add_action( 'wp_ajax_nopriv_dac_check_postcode', array( $this, 'ajax_check_postcode' ) );
	}

	public function declare_compatibility() {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	private function defaults() {
		return array(
			'zone_id'         => '',
			'success_message' => __( 'Great news! Free delivery is available to your postcode.', 'delivery-area-checker' ),
			'failure_message' => __( 'Sorry, free delivery is not currently available in your area.', 'delivery-area-checker' ),
			'button_text'     => __( 'Check postcode', 'delivery-area-checker' ),
			'button_color'    => '#222222',
			'text_color'      => '#222222',
		);
	}

	private function settings() {
		return wp_parse_args( get_option( self::OPTION, array() ), $this->defaults() );
	}

	public function admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Delivery Area Checker', 'delivery-area-checker' ),
			__( 'Delivery Area Checker', 'delivery-area-checker' ),
			'manage_woocommerce',
			'delivery-area-checker',
			array( $this, 'settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'dac_settings_group', self::OPTION, array( $this, 'sanitize_settings' ) );
		add_filter(
			'option_page_capability_dac_settings_group',
			function() {
				return 'manage_woocommerce';
			}
		);
	}

	public function sanitize_settings( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = $this->defaults();
		$output   = array();
		$output['zone_id'] = isset( $input['zone_id'] ) && is_scalar( $input['zone_id'] ) ? absint( $input['zone_id'] ) : 0;
		$output['success_message'] = isset( $input['success_message'] ) && is_scalar( $input['success_message'] ) ? sanitize_text_field( $input['success_message'] ) : $defaults['success_message'];
		$output['failure_message'] = isset( $input['failure_message'] ) && is_scalar( $input['failure_message'] ) ? sanitize_text_field( $input['failure_message'] ) : $defaults['failure_message'];
		$output['button_text'] = isset( $input['button_text'] ) && is_scalar( $input['button_text'] ) ? sanitize_text_field( $input['button_text'] ) : $defaults['button_text'];
		$output['button_color'] = isset( $input['button_color'] ) && is_scalar( $input['button_color'] ) ? sanitize_hex_color( $input['button_color'] ) : $defaults['button_color'];
		$output['text_color'] = isset( $input['text_color'] ) && is_scalar( $input['text_color'] ) ? sanitize_hex_color( $input['text_color'] ) : $defaults['text_color'];
		if ( ! $output['button_color'] ) $output['button_color'] = $defaults['button_color'];
		if ( ! $output['text_color'] ) $output['text_color'] = $defaults['text_color'];
		return $output;
	}

	public function settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) return;
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Delivery Area Checker', 'delivery-area-checker' ) . '</h1><p>' . esc_html__( 'WooCommerce must be installed and active to use this plugin.', 'delivery-area-checker' ) . '</p></div>';
			return;
		}

		$settings = $this->settings();
		$zones = WC_Shipping_Zones::get_zones();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Delivery Area Checker', 'delivery-area-checker' ); ?></h1>
			<p><?php esc_html_e( 'Choose the WooCommerce shipping zone that the postcode checker should test. The checker confirms that the selected zone is the zone WooCommerce matches for the entered UK postcode, and that the zone has an enabled Free Shipping method.', 'delivery-area-checker' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'dac_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="dac-zone-id"><?php esc_html_e( 'Shipping zone to check', 'delivery-area-checker' ); ?></label></th>
						<td>
							<select id="dac-zone-id" name="<?php echo esc_attr( self::OPTION ); ?>[zone_id]">
								<option value="0" <?php selected( (string) $settings['zone_id'], '0' ); ?>><?php esc_html_e( 'Locations not covered by your other zones (Rest of the world)', 'delivery-area-checker' ); ?></option>
								<?php foreach ( $zones as $zone ) : ?>
									<option value="<?php echo esc_attr( $zone['zone_id'] ); ?>" <?php selected( (string) $settings['zone_id'], (string) $zone['zone_id'] ); ?>>
										<?php echo esc_html( $zone['zone_name'] . ' (ID: ' . $zone['zone_id'] . ')' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'WooCommerce zone order and postcode rules are respected. The selected zone must be the zone WooCommerce actually matches.', 'delivery-area-checker' ); ?></p>
						</td>
					</tr>
					<tr><th scope="row"><label for="dac-success"><?php esc_html_e( 'Success message', 'delivery-area-checker' ); ?></label></th><td><input class="regular-text" id="dac-success" name="<?php echo esc_attr( self::OPTION ); ?>[success_message]" type="text" value="<?php echo esc_attr( $settings['success_message'] ); ?>"></td></tr>
					<tr><th scope="row"><label for="dac-failure"><?php esc_html_e( 'Failure message', 'delivery-area-checker' ); ?></label></th><td><input class="regular-text" id="dac-failure" name="<?php echo esc_attr( self::OPTION ); ?>[failure_message]" type="text" value="<?php echo esc_attr( $settings['failure_message'] ); ?>"></td></tr>
					<tr><th scope="row"><label for="dac-button-text"><?php esc_html_e( 'Button text', 'delivery-area-checker' ); ?></label></th><td><input class="regular-text" id="dac-button-text" name="<?php echo esc_attr( self::OPTION ); ?>[button_text]" type="text" value="<?php echo esc_attr( $settings['button_text'] ); ?>"></td></tr>
					<tr><th scope="row"><label for="dac-button-color"><?php esc_html_e( 'Button colour', 'delivery-area-checker' ); ?></label></th><td><input id="dac-button-color" name="<?php echo esc_attr( self::OPTION ); ?>[button_color]" type="color" value="<?php echo esc_attr( $settings['button_color'] ); ?>"></td></tr>
					<tr><th scope="row"><label for="dac-text-color"><?php esc_html_e( 'Text colour', 'delivery-area-checker' ); ?></label></th><td><input id="dac-text-color" name="<?php echo esc_attr( self::OPTION ); ?>[text_color]" type="color" value="<?php echo esc_attr( $settings['text_color'] ); ?>"></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<hr>
			<h2><?php esc_html_e( 'Shortcode', 'delivery-area-checker' ); ?></h2>
			<p><code>[delivery_area_checker]</code></p>
			<p><?php esc_html_e( 'To check a different zone on one page, use its zone ID:', 'delivery-area-checker' ); ?> <code>[delivery_area_checker zone="3"]</code></p>
			<p class="description"><?php esc_html_e( 'The shortcode zone override affects that instance only. This postcode-only checker does not evaluate minimum-spend or coupon requirements; WooCommerce still enforces those at checkout.', 'delivery-area-checker' ); ?></p>
		</div>
		<?php
	}

	public function register_assets() {
		wp_register_style( 'dac-delivery-area-checker', plugins_url( 'assets/delivery-area-checker.css', __FILE__ ), array(), self::VERSION );
		wp_register_script( 'dac-delivery-area-checker', plugins_url( 'assets/delivery-area-checker.js', __FILE__ ), array(), self::VERSION, true );
	}

	public function render_shortcode( $atts ) {
		if ( ! class_exists( 'WooCommerce' ) ) return '';
		$settings = $this->settings();
		$atts = shortcode_atts( array( 'zone' => '' ), $atts, 'delivery_area_checker' );
		$zone_attribute = is_scalar( $atts['zone'] ) ? (string) $atts['zone'] : '';
		$zone_id = ( '' !== $zone_attribute ) ? absint( $zone_attribute ) : absint( $settings['zone_id'] );

		wp_enqueue_style( 'dac-delivery-area-checker' );
		wp_enqueue_script( 'dac-delivery-area-checker' );
		static $localized = false;
		if ( ! $localized ) {
			wp_localize_script( 'dac-delivery-area-checker', 'DACChecker', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( self::NONCE ),
				'genericError' => __( 'Sorry, something went wrong. Please try again.', 'delivery-area-checker' ),
				'invalidPostcode' => __( 'Please enter a valid UK postcode.', 'delivery-area-checker' ),
				'checking' => __( 'Checking…', 'delivery-area-checker' ),
			) );
			$localized = true;
		}

		$instance_id = wp_unique_id( 'dac-checker-' );
		$style = '--dac-button-color:' . esc_attr( $settings['button_color'] ) . ';--dac-text-color:' . esc_attr( $settings['text_color'] ) . ';';
		ob_start();
		?>
		<div class="dac-checker" id="<?php echo esc_attr( $instance_id ); ?>" style="<?php echo esc_attr( $style ); ?>" data-zone="<?php echo esc_attr( $zone_id ); ?>" data-success="<?php echo esc_attr( $settings['success_message'] ); ?>" data-failure="<?php echo esc_attr( $settings['failure_message'] ); ?>">
			<form class="dac-checker__form">
				<label class="dac-checker__label" for="<?php echo esc_attr( $instance_id ); ?>-postcode"><?php esc_html_e( 'Check whether we deliver to your area', 'delivery-area-checker' ); ?></label>
				<div class="dac-checker__controls">
					<input class="dac-checker__input" id="<?php echo esc_attr( $instance_id ); ?>-postcode" name="postcode" type="text" inputmode="text" autocomplete="postal-code" placeholder="<?php esc_attr_e( 'Enter your postcode', 'delivery-area-checker' ); ?>" maxlength="12" required>
					<button class="dac-checker__button" type="submit"><?php echo esc_html( $settings['button_text'] ); ?></button>
				</div>
				<div class="dac-checker__result" role="status" aria-live="polite"></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public function ajax_check_postcode() {
		check_ajax_referer( self::NONCE, 'nonce' );
		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'delivery-area-checker' ) ), 400 );
		}
		$postcode = isset( $_POST['postcode'] ) && is_string( $_POST['postcode'] ) ? wc_clean( wp_unslash( $_POST['postcode'] ) ) : '';
		if ( strlen( $postcode ) > 12 ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid UK postcode.', 'delivery-area-checker' ) ), 400 );
		}
		$zone_id = isset( $_POST['zone_id'] ) && is_scalar( $_POST['zone_id'] ) ? absint( wp_unslash( $_POST['zone_id'] ) ) : 0;
		$postcode = wc_format_postcode( $postcode, 'GB' );
		if ( ! $postcode || ! WC_Validation::is_postcode( $postcode, 'GB' ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid UK postcode.', 'delivery-area-checker' ) ), 400 );
		}

		$zone = new WC_Shipping_Zone( $zone_id );
		if ( 0 !== $zone_id && ! $zone->get_id() ) {
			wp_send_json_error( array( 'message' => __( 'The selected shipping zone could not be found. Please check the plugin settings.', 'delivery-area-checker' ) ), 400 );
		}
		$package = array( 'destination' => array(
			'country' => 'GB', 'state' => '', 'postcode' => $postcode,
			'city' => '', 'address' => '', 'address_1' => '', 'address_2' => '',
		) );
		$matched_zone = WC_Shipping_Zones::get_zone_matching_package( $package );
		$is_selected_zone = ( (int) $matched_zone->get_id() === $zone_id );
		$has_free_shipping = false;
		if ( $is_selected_zone ) {
			foreach ( $matched_zone->get_shipping_methods( true ) as $method ) {
				if ( isset( $method->id, $method->enabled ) && 'free_shipping' === $method->id && 'yes' === $method->enabled ) {
					$has_free_shipping = true;
					break;
				}
			}
		}
		$settings = $this->settings();
		if ( $is_selected_zone && $has_free_shipping ) {
			wp_send_json_success( array( 'eligible' => true, 'message' => $settings['success_message'], 'postcode' => $postcode ) );
		}
		wp_send_json_success( array( 'eligible' => false, 'message' => $settings['failure_message'], 'postcode' => $postcode ) );
	}
}

add_action( 'plugins_loaded', function() {
	if ( class_exists( 'WooCommerce' ) ) {
		new DAC_Delivery_Area_Checker();
	} else {
		add_action( 'admin_notices', function() {
			if ( current_user_can( 'activate_plugins' ) ) {
				echo '<div class="notice notice-warning"><p>' . esc_html__( 'Delivery Area Checker requires WooCommerce to be installed and active.', 'delivery-area-checker' ) . '</p></div>';
			}
		} );
	}
} );
