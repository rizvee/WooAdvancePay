<?php
/**
 * Plugin Name:       Advance Deposits for WooCommerce
 * Description:       Collect a configurable deposit online and leave the balance due on delivery for selected WooCommerce shipping zones.
 * Version:           2.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Hasan Rizvee
 * Author URI:        https://github.com/rizvee
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wooadvancepay
 * Domain Path:       /languages
 * WC requires at least: 8.0
 * WC tested up to:   10.1
 *
 * @package WooAdvancePay
 */

defined( 'ABSPATH' ) || exit;

define( 'WOOADVANCEPAY_VERSION', '2.0.0' );
define( 'WOOADVANCEPAY_FILE', __FILE__ );
define( 'WOOADVANCEPAY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOOADVANCEPAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/** Set safe defaults. */
function wooadvancepay_activate() {
	add_option( 'wooadvancepay_payment_type', 'percentage' );
	add_option( 'wooadvancepay_advance_payment_percentage', '10' );
	add_option( 'wooadvancepay_advance_payment_fixed_amount', '' );
	add_option( 'wooadvancepay_advance_payment_localities', array() );
}
register_activation_hook( __FILE__, 'wooadvancepay_activate' );

/** Declare WooCommerce feature compatibility before WooCommerce initializes. */
function wooadvancepay_declare_compatibility() {
	if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		// Checkout Blocks cannot safely alter the payable total with the classic hooks used here.
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, false );
	}
}
add_action( 'before_woocommerce_init', 'wooadvancepay_declare_compatibility' );

/** Load the plugin only when WooCommerce is available. */
function wooadvancepay_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wooadvancepay_missing_woocommerce_notice' );
		return;
	}

	require_once WOOADVANCEPAY_PLUGIN_DIR . 'includes/class-wooadvancepay.php';
	require_once WOOADVANCEPAY_PLUGIN_DIR . 'includes/class-wooadvancepay-admin.php';
	require_once WOOADVANCEPAY_PLUGIN_DIR . 'includes/class-wooadvancepay-public.php';

	$GLOBALS['wooadvancepay'] = new WooAdvancePay();
	if ( is_admin() ) {
		$GLOBALS['wooadvancepay_admin'] = new WooAdvancePay_Admin();
	}
	$GLOBALS['wooadvancepay_public'] = new WooAdvancePay_Public();
}
add_action( 'plugins_loaded', 'wooadvancepay_init' );

/** Explain a missing dependency. */
function wooadvancepay_missing_woocommerce_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>' . esc_html__( 'WooAdvancePay requires WooCommerce to be installed and active.', 'wooadvancepay' ) . '</p></div>';
}
