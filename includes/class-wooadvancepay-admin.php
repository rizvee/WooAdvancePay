<?php

class WooAdvancePay_Admin {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('wp_ajax_wooadvancepay_save_settings', [$this, 'ajax_save_settings']);
    }

    /**
     * Add the plugin settings menu to the WordPress admin.
     *
     * @since 1.0.0
     */
    public function admin_menu() {
        add_menu_page(
            'WooAdvancePay Settings',
            'WooAdvancePay',
            'manage_options',
            'wooadvancepay',
            [$this, 'settings_page'],
            'dashicons-money'
        );
    }

    /**
     * Render the plugin settings page.
     *
     * @since 1.0.0
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Retrieve existing plugin settings
        $advance_payment_percentage = get_option('wooadvancepay_advance_payment_percentage');
        $advance_payment_fixed_amount = get_option('wooadvancepay_advance_payment_fixed_amount');
        $advance_payment_localities = get_option('wooadvancepay_advance_payment_localities');
        $payment_type = get_option('wooadvancepay_payment_type', 'percentage'); // Default to percentage

        ?>
        <div class="wrap">
            <h2>WooAdvancePay Settings</h2>

            <form id="wooadvancepay-settings-form" method="post">
                <table class="form-table">
                    <!-- Form fields for selecting advance payment type -->
                    <tr>
                        <th scope="row">Advance Payment Type</th>
                        <td>
                            <label for="advance_payment_type_percentage">
                                <input type="radio" name="advance_payment_type" id="advance_payment_type_percentage" value="percentage" <?php checked($payment_type, 'percentage'); ?>>
                                Percentage
                            </label>
                            <br>
                            <label for="advance_payment_type_fixed_amount">
                                <input type="radio" name="advance_payment_type" id="advance_payment_type_fixed_amount" value="fixed_amount" <?php checked($payment_type, 'fixed_amount'); ?>>
                                Fixed Amount
                            </label>
                        </td>
                    </tr>
                    <!-- Form fields for entering advance payment percentage and fixed amount -->
                    <tr class="payment-type-field" id="percentage_field" style="<?php echo $payment_type === 'percentage' ? '' : 'display:none;'; ?>">
                        <th scope="row">Advance Payment Percentage</th>
                        <td><input type="number" name="advance_payment_percentage" value="<?php echo esc_attr($advance_payment_percentage); ?>" class="regular-input"></td>
                    </tr>
                    <tr class="payment-type-field" id="fixed_amount_field" style="<?php echo $payment_type === 'fixed_amount' ? '' : 'display:none;'; ?>">
                        <th scope="row">Advance Payment Fixed Amount</th>
                        <td><input type="number" name="advance_payment_fixed_amount" value="<?php echo esc_attr($advance_payment_fixed_amount); ?>" class="regular-input"></td>
                    </tr>
                    <!-- Form field for entering advance payment localities -->
                    <tr>
                        <th scope="row">Advance Payment Localities</th>
                        <td><textarea name="advance_payment_localities" rows="5" class="regular-input"><?php echo esc_textarea($advance_payment_localities); ?></textarea></td>
                    </tr>
                </table>

                <?php wp_nonce_field('wooadvancepay_save_settings', '_wpnonce'); ?>

                <!-- Submit button to save settings -->
                <p class="submit">
                    <button type="submit" class="button-primary">Save Settings</button>
                </p>

                <div class="wooadvancepay-settings-message"></div>
            </form>
        </div>
        <?php
    }

    /**
     * AJAX handler for saving settings.
     *
     * @since 1.0.0
     */
    public function ajax_save_settings() {
        // Nonce Verification
        check_ajax_referer('wooadvancepay_save_settings', '_wpnonce');

        // Permission Check
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            wp_die();
        }

        // Retrieve and Sanitize Data
        $payment_type = isset($_POST['advance_payment_type']) ? sanitize_text_field($_POST['advance_payment_type']) : '';
        $percentage = isset($_POST['advance_payment_percentage']) ? floatval($_POST['advance_payment_percentage']) : '';
        $fixed_amount = isset($_POST['advance_payment_fixed_amount']) ? floatval($_POST['advance_payment_fixed_amount']) : '';
        $localities = isset($_POST['advance_payment_localities']) ? sanitize_textarea_field($_POST['advance_payment_localities']) : '';

        // Validate Data
        if (!in_array($payment_type, ['percentage', 'fixed_amount'])) {
            wp_send_json_error(['message' => 'Invalid payment type.'], 400);
            wp_die();
        }

        if ($payment_type === 'percentage') {
            if ($percentage < 0 || $percentage > 100) {
                wp_send_json_error(['message' => 'Percentage must be between 0 and 100.'], 400);
                wp_die();
            }
        } elseif ($payment_type === 'fixed_amount') {
            if ($fixed_amount < 0) {
                wp_send_json_error(['message' => 'Fixed amount must be greater than or equal to 0.'], 400);
                wp_die();
            }
        }


        // Save Options
        update_option('wooadvancepay_payment_type', $payment_type);

        if ($payment_type === 'percentage') {
            update_option('wooadvancepay_advance_payment_percentage', $percentage);
            delete_option('wooadvancepay_advance_payment_fixed_amount'); // Clear the other type
        } elseif ($payment_type === 'fixed_amount') {
            update_option('wooadvancepay_advance_payment_fixed_amount', $fixed_amount);
            delete_option('wooadvancepay_advance_payment_percentage'); // Clear the other type
        }

        update_option('wooadvancepay_advance_payment_localities', $localities);

        // Send Response
        wp_send_json_success(['message' => 'Settings saved successfully!']);
        wp_die();
    }
}
