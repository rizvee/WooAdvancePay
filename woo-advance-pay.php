<?php
/*
Plugin Name: WooAdvancePay
Description: Enhance WooCommerce with advance payment for cash on delivery to a certain locality.
Version: 1.1
Author: Hasan Rizvee
*/

// Add your custom functionality here


// Enqueue JavaScript for conditional application
function woo_advancepay_enqueue_script() {
    wp_enqueue_script('woo-advancepay-script', plugins_url('/main.js', __FILE__), array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'woo_advancepay_enqueue_script');

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
