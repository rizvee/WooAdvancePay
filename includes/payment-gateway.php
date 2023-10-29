<?php
// custom payment gateway for WooAdvancePay.
class WooAdvancePay_Payment_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'woo_advance_pay_gateway';
        $this->method_title = 'WooAdvancePay Payment Gateway';
        $this->title = 'Advance Delivery Charge';
        $this->has_fields = false;
        $this->init_form_fields();
        $this->init_settings();

        // Define gateway settings.
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    // Initialize form fields.
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable Advance Delivery Charge',
                'default' => 'no',
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This is the label that customers will see when choosing the advance delivery charge.',
                'default' => 'Advance Delivery Charge',
                'desc_tip' => true,
            ),
        );
    }

    // Process the payment.
    public function process_payment($order_id) {
        // Implement payment processing logic here.
        // You can use this function to charge the advance delivery fee.
        // Ensure that the fee is added to the order and recorded correctly.

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url(WC()->cart->get_checkout_url()),
        );
    }
}

// Register the payment gateway.
function woo_advance_pay_add_payment_gateway($gateways) {
    $gateways[] = 'WooAdvancePay_Payment_Gateway';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'woo_advance_pay_add_payment_gateway');
