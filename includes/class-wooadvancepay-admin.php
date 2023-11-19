<?php

class WooAdvancePay_Admin {

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

        ?>
        <div class="wrap">
            <h2>WooAdvancePay Settings</h2>

            <form id="wooadvancepay-settings-form" method="post">
                <table class="form-table">
                    <!-- Form fields for selecting advance payment type -->
                    <tr>
                        <th scope="row">Advance Payment Type</th>
                        <td>
                            <label for="advance_payment_percentage">
                                <input type="radio" name="advance_payment_type" id="advance_payment_percentage" value="percentage" <?php checked($advance_payment_percentage, true); ?>>
                                Percentage
                            </label>
                            <br>
                            <label for="advance_payment_fixed_amount">
                                <input type="radio" name="advance_payment_type" id="advance_payment_fixed_amount" value="fixed_amount" <?php checked($advance_payment_fixed_amount, true); ?>>
                                Fixed Amount
                            </label>
                        </td>
                    </tr>
                    <!-- Form fields for entering advance payment percentage and fixed amount -->
                    <tr>
                        <th scope="row">Advance Payment Percentage</th>
                        <td><input type="number" id="advance_payment_percentage" name="advance_payment_percentage" value="<?php echo $advance_payment_percentage; ?>" class="regular-input"></td>
                    </tr>
                    <tr>
                        <th scope="row">Advance Payment Fixed Amount</th>
                        <td><input type="number" id="advance_payment_fixed_amount" name="advance_payment_fixed_amount" value="<?php echo $advance_payment_fixed_amount; ?>" class="regular-input"></td>
                    </tr>
                    <!-- Form field for entering advance payment localities -->
                    <tr>
                        <th scope="row">Advance Payment Localities</th>
                        <td><textarea id="advance_payment_localities" name="advance_payment_localities" rows="5" class="regular-input"><?php echo $advance_payment_localities; ?></textarea></td>
                    </tr>
                </table>

                <?php wp_nonce_field('wooadvancepay_save_settings'); ?>

                <!-- Submit button to save settings -->
                <p class="submit">
                    <button type="submit" class="button-primary">Save Settings</button>
                </p>

                <div class="wooadvancepay-settings-message"></div>
            </form>
        </div>
        <?php
    }
}
