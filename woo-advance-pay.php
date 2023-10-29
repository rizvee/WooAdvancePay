<?php
/*
Plugin Name: WooAdvancePay
Description: Partial payment for WooCommerce with locality-based delivery charge.
Version: 1.0
Author: Hasan Rizvee
*/

// Define a constant for the plugin directory path.
define('WOO_ADVANCE_PAY_DIR', plugin_dir_path(__FILE__));

// Include necessary files.
require_once(WOO_ADVANCE_PAY_DIR . 'admin/admin.php'); // Include admin settings.
require_once(WOO_ADVANCE_PAY_DIR . 'includes/payment-gateway.php'); // Include payment gateway logic.

// Register styles and scripts using WordPress actions.
function woo_advance_pay_enqueue_styles() {
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css');
    wp_enqueue_style('woo-advance-pay-styles', plugins_url('assets/css/styles.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'woo_advance_pay_enqueue_styles');

// Activation and deactivation hooks (optional).
register_activation_hook(__FILE__, 'woo_advance_pay_activate');
register_deactivation_hook(__FILE__, 'woo_advance_pay_deactivate');
