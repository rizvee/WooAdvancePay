<?php
// Create an admin settings page.
function woo_advance_pay_admin_page() {
    ?>
    <div class="wrap">
        <h2>WooAdvancePay Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('woo_advance_pay_settings_group'); ?>
            <?php do_settings_sections('woo_advance_pay_settings'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register the admin settings page.
function woo_advance_pay_admin_menu() {
    add_menu_page('WooAdvancePay Settings', 'WooAdvancePay', 'manage_options', 'woo_advance_pay_settings', 'woo_advance_pay_admin_page');
}
add_action('admin_menu', 'woo_advance_pay_admin_menu');

// Define and register plugin settings.
function woo_advance_pay_register_settings() {
    register_setting('woo_advance_pay_settings_group', 'locality_based_charge', 'intval');
    add_settings_section('woo_advance_pay_settings_section', 'Local Delivery Charge Settings', 'woo_advance_pay_settings_section_callback', 'woo_advance_pay_settings');
    add_settings_field('locality_based_charge', 'Enable Locality-Based Delivery Charge', 'woo_advance_pay_locality_based_charge_callback', 'woo_advance_pay_settings', 'woo_advance_pay_settings_section');
}
add_action('admin_init', 'woo_advance_pay_register_settings');

// Callback functions for settings.
function woo_advance_pay_settings_section_callback() {
    echo 'Configure options for locality-based delivery charge.';
}

function woo_advance_pay_locality_based_charge_callback() {
    $locality_based_charge = get_option('locality_based_charge');
    ?>
    <label>
        <input type="checkbox" name="locality_based_charge" value="1" <?php checked(1, $locality_based_charge); ?> />
        Enable locality-based delivery charge
    </label>
    <?php
}
