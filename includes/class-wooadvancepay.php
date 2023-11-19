<?php

class WooAdvancePay {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_filter( 'woocommerce_available_payment_gateways', [$this, 'filter_available_payment_gateways'] );
        add_action( 'woocommerce_checkout_process', [$this, 'save_partial_payment_details'] );
        add_action( 'woocommerce_order_status_completed', [$this, 'update_order_status_for_advance_payments'] );
    }

    /**
     * Filter the available payment gateways to only show cash on delivery for specific localities.
     *
     * @since 1.0.0
     *
     * @param array $available_gateways An array of available payment gateways.
     *
     * @return array The filtered array of available payment gateways.
     */
    public function filter_available_payment_gateways($available_gateways) {
        $shipping_method = WC()->session->get('chosen_shipping_methods');
        $shipping_zone_id = WC()->shipping->get_shipping_zone_id( $shipping_method );

        $advance_payment_localities = get_option('wooadvancepay_advance_payment_localities');
        if (empty($advance_payment_localities)) {
            return $available_gateways;
        }

        $localities = explode(',', $advance_payment_localities);
        if (!in_array($shipping_zone_id, $localities)) {
            unset($available_gateways['cod']);
        }

        return $available_gateways;
    }

    /**
     * Save the partial payment details to the order.
     *
     * @since 1.0.0
     *
     * @param int $order_id The order ID.
     */
    public function save_partial_payment_details($order_id) {
        $partial_payment_percentage = WC()->session->get('wooadvancepay_partial_payment_percentage');
        $partial_payment_fixed_amount = WC()->session->get('wooadvancepay_partial_payment_fixed_amount');

        if (empty($partial_payment_percentage) && empty($partial_payment_fixed_amount)) {
            return;
        }

        $order = wc_get_order($order_id);
        $order_total = $order->get_total();

        if ($partial_payment_percentage) {
            $partial_payment_amount = $order_total * ($partial_payment_percentage / 100);
        } elseif ($partial_payment_fixed_amount) {
            $partial_payment_amount = $partial_payment_fixed_amount;
        }

        $order->update_meta_data('wooadvancepay_partial_payment_amount', $partial_payment_amount);
        $order->update_meta_data('wooadvancepay_total_payment_due', $order_total - $partial_payment_amount);
    }

    /**
     * Update the order status to completed if the full payment has been received, including the advance payment and the remaining payment on delivery.
     *
     * @since 1.0.0
     *
     * @param int $order_id The order ID.
     */
    public function update_order_status_for_advance_payments($order_id) {
        $order = wc_get_order($order_id);
        $partial_payment_amount = $order->get_meta('wooadvancepay_partial_payment_amount', true);
        $remaining_payment_due = $order->get_meta('wooadvancepay_total_payment_due', true);

        $order_status = $order->get_status();
        if ($order_status === 'processing' && ($partial_payment_amount + WC()->session->get('cash_on_delivery_amount')) >= $order->get_total()) {
            $order->set_status('completed');
            $order->save();
        }
    }
}
