<?php

class WooAdvancePay_Public {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Add action to display the partial payment form on the checkout page
        add_action( 'woocommerce_checkout_order_review', [$this, 'display_partial_payment_form'] );
    }

    /**
     * Display the partial payment form on the checkout page.
     *
     * @since 1.0.0
     */
    public function display_partial_payment_form() {
        // Check if chosen shipping methods are available
        if ( ! WC()->session->get('chosen_shipping_methods') ) {
            return;
        }

        // Get the shipping zone ID based on the chosen shipping methods
        $shipping_zone_id = WC()->shipping->get_shipping_zone_id( WC()->session->get('chosen_shipping_methods') );
        $advance_payment_localities = get_option('wooadvancepay_advance_payment_localities');

        // Check if localities are specified for advance payment
        if (empty($advance_payment_localities)) {
            return;
        }

        // Convert advance payment localities to an array
        $localities = explode(',', $advance_payment_localities);

        // Check if the current shipping zone is eligible for advance payment
        if (in_array($shipping_zone_id, $localities)) {
            // Get advance payment settings
            $advance_payment_percentage = get_option('wooadvancepay_advance_payment_percentage');
            $advance_payment_fixed_amount = get_option('wooadvancepay_advance_payment_fixed_amount');
            $order_total = WC()->session->get('order_total');

            // Calculate partial payment amount based on percentage or fixed amount
            if ($advance_payment_percentage) {
                $partial_payment_amount = $order_total * ($advance_payment_percentage / 100);
            } elseif ($advance_payment_fixed_amount) {
                $partial_payment_amount = $advance_payment_fixed_amount;
            }

            // Calculate remaining payment due
            $remaining_payment_due = $order_total - $partial_payment_amount;

            // Display the partial payment form
            ?>
            <div id="wooadvancepay-partial-payment-form">
                <h2>Advance Payment</h2>

                <p>Make an advance payment of <?php echo wc_price($partial_payment_amount); ?> before placing your order.</p>

                <p>The remaining payment of <?php echo wc_price($remaining_payment_due); ?> will be due upon delivery.</p>

                <!-- Hidden input fields to store partial payment information -->
                <input type="hidden" name="wooadvancepay_partial_payment_percentage" value="<?php echo $advance_payment_percentage; ?>">
                <input type="hidden" name="wooadvancepay_partial_payment_fixed_amount" value="<?php echo $advance_payment_fixed_amount; ?>">
            </div>
            <?php
        }
    }
}
