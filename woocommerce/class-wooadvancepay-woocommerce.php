<?php

class WooAdvancePay_WooCommerce {

    /**
     * Constructor.
     *
     * @since 1.2.2
     */
    public function __construct() {
        add_action( 'woocommerce_order_status_completed', [$this, 'send_partial_payment_receipt'] );
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
        $partial_payment_amount = $order->get_meta('wooadvancepay_partial_payment_amount', true);
        $remaining_payment_due = $order->get_total() - $partial_payment_amount;

        $email = new WC_Email();
        $email->subject = __('Partial Payment Receipt for Order #' . $order->get_id(), 'wooadvancepay');
        $email->set_recipient($order->get_billing_email());
        $email->set_content(sprintf(__('Thank you for your partial payment of %s. Your remaining payment due is %s.', 'wooadvancepay'), wc_price($partial_payment_amount), wc_price($remaining_payment_due)));
        $email->send();
    }
}
