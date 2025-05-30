<?php

class WooAdvancePay_Public {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Add action to display the partial payment form on the checkout page
        add_action( 'woocommerce_checkout_order_review', [$this, 'display_partial_payment_form'], 20 ); // Priority 20 to run after totals
    }

    /**
     * Display the partial payment form on the checkout page if applicable.
     *
     * @since 1.0.0
     */
    public function display_partial_payment_form() {
        // Ensure session and shipping are available
        if (!WC()->session || !WC()->shipping || !WC()->customer) {
            return;
        }

        // 1. Get Chosen Shipping Methods (and ensure some shipping is chosen)
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        if (empty($chosen_methods) || !is_array($chosen_methods)) {
            // No shipping method selected yet, or data is invalid.
            // This can happen when the checkout page is first loaded before a shipping option is chosen.
            // Silently return; the page will update via AJAX when a shipping method is selected.
            return;
        }

        // 2. Get Admin Defined Localities (Shipping Zone IDs)
        $advance_payment_localities_raw = get_option('wooadvancepay_advance_payment_localities');
        if (empty($advance_payment_localities_raw)) {
            return; // Admin has not specified any zones for advance payment.
        }

        $admin_defined_zone_ids_raw = array_map('trim', explode(',', $advance_payment_localities_raw));
        // Remove any empty values that might result from trailing commas or multiple commas.
        $admin_defined_zone_ids = array_filter($admin_defined_zone_ids_raw, 'strlen');

        if (empty($admin_defined_zone_ids)) {
            return; // No valid zone IDs configured by admin after filtering.
        }
        
        // Convert admin defined zone IDs to integers for reliable comparison, as zone IDs are integers.
        $admin_defined_zone_ids = array_map('intval', $admin_defined_zone_ids);

        // 3. Find Applicable Zone IDs for the Customer
        $customer_applicable_zone_ids = [];
        $packages = WC()->shipping->get_packages();

        if (empty($packages)) {
             // This might happen if called too early or in unusual checkout flows.
            error_log('WooAdvancePay: No shipping packages found for the current session.');
            return;
        }

        foreach ($packages as $package_key => $package) {
            // Ensure chosen_shipping_methods for the current package is available
            // The $chosen_methods from session is an array of chosen methods for *all* packages.
            // We need to see if a method is chosen for *this* specific package.
            if (!isset($chosen_methods[$package_key]) || empty($chosen_methods[$package_key])) {
                continue; // No shipping method chosen for this package yet.
            }

            $zone = WC_Shipping_Zones::get_zone_matching_package($package);
            if ($zone) {
                $customer_applicable_zone_ids[] = (int) $zone->get_id();
            } else {
                // Package matches "Rest of the World" (Zone ID 0)
                $customer_applicable_zone_ids[] = 0;
            }
        }
        
        // Remove duplicate zone IDs that might arise if multiple packages map to the same zone.
        $customer_applicable_zone_ids = array_unique($customer_applicable_zone_ids);

        if (empty($customer_applicable_zone_ids) && !in_array(0, $customer_applicable_zone_ids, true)) {
            // No zones found for customer, this is unusual if shipping is available.
            // Error_log this for debugging if necessary.
            // error_log('WooAdvancePay: No applicable shipping zones found for customer.');
            return;
        }

        // 4. Check for Match
        $matching_zones = array_intersect($customer_applicable_zone_ids, $admin_defined_zone_ids);

        if (!empty($matching_zones)) {
            // A match is found, proceed to display the form.
            $payment_type = get_option('wooadvancepay_payment_type', 'percentage');
            $percentage_amount = get_option('wooadvancepay_advance_payment_percentage');
            $fixed_amount = get_option('wooadvancepay_advance_payment_fixed_amount');
            
            // Get cart total directly. Using session 'order_total' might be stale or not yet set.
            // WC()->cart->get_total('edit') gets the total without formatting.
            $order_total = floatval(WC()->cart->get_total('edit'));

            $partial_payment_amount = 0;

            if ($payment_type === 'percentage' && !empty($percentage_amount)) {
                $partial_payment_amount = $order_total * (floatval($percentage_amount) / 100);
            } elseif ($payment_type === 'fixed_amount' && !empty($fixed_amount)) {
                $partial_payment_amount = floatval($fixed_amount);
            }

            // Ensure partial payment does not exceed order total
            if ($partial_payment_amount > $order_total) {
                $partial_payment_amount = $order_total;
            }
            
            // If partial payment is zero or negative (e.g. bad config), don't show form
            if ($partial_payment_amount <= 0) {
                return;
            }

            $remaining_payment_due = $order_total - $partial_payment_amount;

            // Display the partial payment form
            ?>
            <div id="wooadvancepay-partial-payment-details" class="woocommerce-info">
                <h3><?php _e('Advance Payment Required', 'wooadvancepay'); ?></h3>
                <p>
                    <?php 
                    printf(
                        __('An advance payment of %s is required for your selected shipping zone. The remaining amount of %s will be due upon delivery.', 'wooadvancepay'),
                        wc_price($partial_payment_amount),
                        wc_price($remaining_payment_due)
                    );
                    ?>
                </p>
                <?php
                // These hidden fields might be useful for JS or for passing data upon submission
                // if the plugin were to modify the order total or add fees directly.
                // For now, they are informational but could be used by future enhancements.
                ?>
                <input type="hidden" name="wooadvancepay_is_applicable" value="1">
                <input type="hidden" name="wooadvancepay_partial_amount_calculated" value="<?php echo esc_attr($partial_payment_amount); ?>">
            </div>
            <?php
        }
    }
}
