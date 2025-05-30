<?php
/*
Plugin Name: WooAdvancePay
Description: Enhance WooCommerce with advance payment for cash on delivery to a certain locality.
Version: 1.2.2
Author: Hasan Rizvee
GitHub: https://github.com/rizvee
Tags: WooCommerce, Payment Gateway, Delivery Charge, Partial Payment
*/

if (!defined('ABSPATH')) {
    exit;
}

define('WOOADVANCEPAY_VERSION', '1.2.2');
define('WOOADVANCEPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOOADVANCEPAY_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin activation hook.
 * Sets up default options if they don't exist.
 *
 * @since 1.2.3
 */
function wooadvancepay_activate() {
    if (false === get_option('wooadvancepay_payment_type')) {
        add_option('wooadvancepay_payment_type', 'percentage');
    }
    if (false === get_option('wooadvancepay_advance_payment_percentage')) {
        add_option('wooadvancepay_advance_payment_percentage', 10); // Default to 10%
    }
    // No default for fixed amount as percentage is the default type.
    // wooadvancepay_advance_payment_fixed_amount will remain unset until explicitly saved.
    if (false === get_option('wooadvancepay_advance_payment_localities')) {
        add_option('wooadvancepay_advance_payment_localities', ''); // Default to empty
    }
    // Ensure options that should exist are present, even if empty, to prevent issues.
    if (false === get_option('wooadvancepay_advance_payment_fixed_amount')) {
        add_option('wooadvancepay_advance_payment_fixed_amount', ''); 
    }
}
register_activation_hook(__FILE__, 'wooadvancepay_activate');

/**
 * Plugin deactivation hook.
 * Placeholder for any deactivation logic.
 *
 * @since 1.2.3
 */
function wooadvancepay_deactivate() {
    // Placeholder for any deactivation logic in the future.
    // For now, we won't remove options on deactivation to preserve settings.
}
register_deactivation_hook(__FILE__, 'wooadvancepay_deactivate');

// Load plugin text domain for i18n
add_action('plugins_loaded', 'wooadvancepay_load_textdomain');
function wooadvancepay_load_textdomain() {
    load_plugin_textdomain('wooadvancepay', false, basename(dirname(__FILE__)) . '/languages/');
}

// Include necessary files
require_once WOOADVANCEPAY_PLUGIN_DIR . 'includes/class-wooadvancepay.php';
require_once WOOADVANCEPAY_PLUGIN_DIR . 'includes/class-wooadvancepay-admin.php';
require_once WOOADVANCEPAY_PLUGIN_DIR . 'includes/class-wooadvancepay-public.php';
require_once WOOADVANCEPAY_PLUGIN_DIR . 'woocommerce/class-wooadvancepay-woocommerce.php';

// Enqueue scripts and styles
function wooadvancepay_enqueue_scripts() {
    if (is_checkout()) {
        wp_enqueue_style('wooadvancepay-frontend', WOOADVANCEPAY_PLUGIN_URL . 'assets/css/frontend.css', array(), WOOADVANCEPAY_VERSION);
        wp_enqueue_script('jquery');
        // wp_enqueue_script('jquery-bind-first', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.bind-first/0.2.3/jquery.bind-first.min.js', array('jquery'), '0.2.3', true); // Removed
        wp_enqueue_script('wooadvancepay-frontend', WOOADVANCEPAY_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), WOOADVANCEPAY_VERSION, true); // Dependency removed
    }
}

add_action('wp_enqueue_scripts', 'wooadvancepay_enqueue_scripts');

// Initialize the plugin after all plugins have loaded
function wooadvancepay_init() {
    if (class_exists('WooAdvancePay')) {
        $GLOBALS['wooadvancepay'] = new WooAdvancePay();
        // Instantiate WooAdvancePay_Admin if the class exists
        if (class_exists('WooAdvancePay_Admin')) {
            $GLOBALS['wooadvancepay_admin'] = new WooAdvancePay_Admin();
        }
        // Initialize WooCommerce-specific functionality
        if (class_exists('WooAdvancePay_WooCommerce')) {
            $GLOBALS['wooadvancepay_woocommerce'] = new WooAdvancePay_WooCommerce(); 
        }
    }
}

add_action('plugins_loaded', 'wooadvancepay_init');

// Add custom hooks and filters
function wooadvancepay_custom_hooks() {
    // Add a column to the WooCommerce order table displaying advance payment details
    add_filter('woocommerce_admin_order_table_headers', 'wooadvancepay_add_order_table_headers');
    function wooadvancepay_add_order_table_headers($headers) {
        $headers['wooadvancepay_payment_details'] = __('Advance Payment Details', 'wooadvancepay');
        return $headers;
    }

    add_action('woocommerce_admin_order_table_row', 'wooadvancepay_add_order_table_row_data', 10, 3);
    function wooadvancepay_add_order_table_row_data($order, $column, $data) {
        if ($column === 'wooadvancepay_payment_details') {
            $partial_payment_amount = $order->get_meta('wooadvancepay_partial_payment_amount', true);
            if ($partial_payment_amount) { // Check if advance payment was made
                $remaining_payment_due = $order->get_total() - $partial_payment_amount;
                $payment_details = 'Advance Payment: ' . wc_price($partial_payment_amount) . '<br>';
                $payment_details .= 'Remaining Payment Due: ' . wc_price($remaining_payment_due);
                echo $payment_details;
            } else {
                echo __('No advance payment made.', 'wooadvancepay');
            }
        }
    }
}

add_action('init', 'wooadvancepay_custom_hooks');
