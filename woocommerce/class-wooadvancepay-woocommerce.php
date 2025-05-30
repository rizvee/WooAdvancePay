<?php

class WooAdvancePay_WooCommerce {

    /**
     * Constructor.
     *
     * @since 1.2.2
     */
    public function __construct() {
        add_action('woocommerce_order_status_processing', [$this, 'send_partial_payment_receipt']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_advance_payment_order_meta'], 10, 2);
    }

    /**
     * Save advance payment details to order meta.
     *
     * @since 1.2.3
     * @param int   $order_id The order ID.
     * @param array $data     The data posted from checkout.
     */
    public function save_advance_payment_order_meta($order_id, $data) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Retrieve plugin settings
        $payment_type = get_option('wooadvancepay_payment_type');
        $advance_payment_localities_raw = get_option('wooadvancepay_advance_payment_localities');

        // If localities or payment type are not set, or payment type is invalid, do nothing.
        if (empty($advance_payment_localities_raw) || empty($payment_type) || !in_array($payment_type, ['percentage', 'fixed_amount'])) {
            return;
        }

        // Parse admin-defined zone IDs
        $admin_defined_zone_ids_raw = array_map('trim', explode(',', $advance_payment_localities_raw));
        $admin_defined_zone_ids = array_filter(array_map('intval', $admin_defined_zone_ids_raw), function($id) { return $id >= 0; }); // Ensure IDs are 0 or positive

        if (empty($admin_defined_zone_ids)) {
            return; // No valid admin zones defined
        }

        // Determine customer's applicable shipping zone ID from the order
        $customer_zone_ids = [];
        $shipping_items = $order->get_items('shipping');
        
        if (!empty($shipping_items)) {
            // In a typical setup, there's one shipping method chosen per order,
            // which implies one set of destination details for zone matching.
            // If multiple shipping methods (e.g. per package shipping) were used and stored complexly,
            // this might need adjustment. For now, assume standard WooCommerce behavior.
            $package = [
                'destination' => [
                    'country'   => $order->get_shipping_country(),
                    'state'     => $order->get_shipping_state(),
                    'postcode'  => $order->get_shipping_postcode(),
                    'city'      => $order->get_shipping_city(), // Add city for more precise matching if zones use it
                ],
                // 'contents' might not be strictly necessary if zone matching relies on destination primarily.
                // However, some shipping methods might vary based on contents (e.g. weight, shipping class).
                // For WC_Shipping_Zones::get_zone_matching_package, a fuller package is better.
                'contents'    => $order->get_items(), 
                'applied_coupons' => $order->get_coupon_codes(),
                'user'        => ['id' => $order->get_customer_id()],
                // 'rates' are important for get_zone_matching_package as it might check available methods for the zone
                // We need to reconstruct available rates for the package, which is complex here.
                // A simpler approach for zone ID might be needed if this proves problematic.
                // For now, we'll proceed with this structure.
            ];

            // Attempt to get the zone for the primary shipping destination of the order
            $matching_zone = WC_Shipping_Zones::get_zone_matching_package($package);

            if ($matching_zone) {
                $customer_zone_ids[] = (int) $matching_zone->get_id();
            } else {
                // No specific zone matched, implies "Rest of the World" (Zone ID 0)
                $customer_zone_ids[] = 0;
            }
        } else {
            // No shipping items on the order. This could be for virtual products or an error.
            // If it's virtual, advance payment for shipping locality might not apply.
            // If localities can be non-shipping related, this logic needs adjustment.
            // For now, assume localities are shipping zone based.
            return;
        }
        
        $customer_zone_ids = array_unique($customer_zone_ids); // Ensure unique IDs

        // Check for intersection
        $matching_zones = array_intersect($customer_zone_ids, $admin_defined_zone_ids);

        if (empty($matching_zones)) {
            // This order's shipping zone is not in the admin-defined list for advance payments.
            // Clear any potentially set meta from previous checkout display attempts, just in case.
            delete_post_meta($order_id, 'wooadvancepay_partial_payment_amount');
            delete_post_meta($order_id, 'wooadvancepay_remaining_due');
            return;
        }

        // If a match is found, calculate and save the partial payment amount
        $order_total = $order->get_total();
        $partial_payment_amount = 0;

        if ($payment_type === 'percentage') {
            $percentage = get_option('wooadvancepay_advance_payment_percentage');
            if (!empty($percentage)) {
                $partial_payment_amount = $order_total * (floatval($percentage) / 100);
            }
        } elseif ($payment_type === 'fixed_amount') {
            $fixed_amount = get_option('wooadvancepay_advance_payment_fixed_amount');
            if (!empty($fixed_amount)) {
                $partial_payment_amount = floatval($fixed_amount);
            }
        }
        
        // Sanity check and validation for the calculated partial payment amount
        if ($partial_payment_amount > 0 && $partial_payment_amount < $order_total) {
            // Round to currency decimals
            $partial_payment_amount = round($partial_payment_amount, wc_get_price_decimals());

            update_post_meta($order_id, 'wooadvancepay_partial_payment_amount', $partial_payment_amount);
            
            $remaining_due = $order_total - $partial_payment_amount;
            update_post_meta($order_id, 'wooadvancepay_remaining_due', $remaining_due);
        } elseif ($partial_payment_amount >= $order_total) {
            // If partial payment is full or more, it's not a "partial" payment in this context.
            // Or it could be a misconfiguration. Clear meta if it was set.
            delete_post_meta($order_id, 'wooadvancepay_partial_payment_amount');
            delete_post_meta($order_id, 'wooadvancepay_remaining_due');
            // Optionally, log this situation.
            // error_log("WooAdvancePay: Partial payment for order $order_id was >= order total. Not applied.");
        } else { // <= 0
            delete_post_meta($order_id, 'wooadvancepay_partial_payment_amount');
            delete_post_meta($order_id, 'wooadvancepay_remaining_due');
        }
    }


    /**
     * Send a partial payment receipt email to the customer.
     *
     * @since 1.2.2
     *
     * @param int $order_id The order ID.
     */
    public function send_partial_payment_receipt($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $partial_payment_amount = $order->get_meta('wooadvancepay_partial_payment_amount', true);
        
        // Only send if partial payment was actually made/recorded
        if (empty($partial_payment_amount) || floatval($partial_payment_amount) <= 0) {
            return;
        }

        $remaining_payment_due = $order->get_total() - floatval($partial_payment_amount);

        // Ensure WooCommerce email infrastructure is available
        if (class_exists('WC_Email')) {
            $mailer = WC()->mailer(); // Get the mailer instance
            
            // Construct the email content
            $subject = __('Partial Payment Receipt for Order #' . $order->get_order_number(), 'wooadvancepay');
            $message_body = sprintf(
                __('Dear %1$s,<br><br>Thank you for your advance payment of %2$s for order #%3$s.<br>Your remaining payment due is %4$s and will be collected upon delivery.<br><br>Thank you for your business!', 'wooadvancepay'),
                $order->get_billing_first_name(),
                wc_price($partial_payment_amount),
                $order->get_order_number(),
                wc_price($remaining_payment_due)
            );
            
            // Use a standard WooCommerce email template
            $message = $mailer->wrap_message($subject, $message_body);

            // Send the email
            $mailer->send(
                $order->get_billing_email(),
                $subject,
                $message,
                "Content-Type: text/html\r\n", // Headers
                [] // Attachments
            );
        }
    }
}
