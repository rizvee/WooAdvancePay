<?php
/**
 * Partial Payment Form Template
 *
 * This template is used to display the partial payment form on the checkout page.
 *
 * @since 1.0.0
 */

// Get the current order
$order = wc_get_order();
$order_total = $order->get_total();

// Get advance payment settings
$advance_payment_percentage = get_option('wooadvancepay_advance_payment_percentage');
$advance_payment_fixed_amount = get_option('wooadvancepay_advance_payment_fixed_amount');

// Calculate partial payment amount based on percentage or fixed amount
if ($advance_payment_percentage) {
    $partial_payment_amount = $order_total * ($advance_payment_percentage / 100);
} elseif ($advance_payment_fixed_amount) {
    $partial_payment_amount = $advance_payment_fixed_amount;
}

// Calculate remaining payment due
$remaining_payment_due = $order_total - $partial_payment_amount;
?>

<div id="wooadvancepay-partial-payment-form">
    <h2>Advance Payment</h2>

    <p>Make an advance payment of <?php echo wc_price($partial_payment_amount); ?> before placing your order.</p>

    <p>The remaining payment of <?php echo wc_price($remaining_payment_due); ?> will be due upon delivery.</p>

    <!-- Hidden input fields to store partial payment information -->
    <input type="hidden" name="wooadvancepay_partial_payment_percentage" value="<?php echo $advance_payment_percentage; ?>">
    <input type="hidden" name="wooadvancepay_partial_payment_fixed_amount" value="<?php echo $advance_payment_fixed_amount; ?>">
</div>
