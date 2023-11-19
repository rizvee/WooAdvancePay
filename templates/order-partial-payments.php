<?php
/**
 * Template for displaying the list of partial payments for an order.
 *
 * @since 1.0.0
 * @version 1.2.1
 *
 * @param int $order_id The order ID.
 */

// Get the order object based on the provided order ID
$order = wc_get_order($order_id);

// Retrieve the partial payments meta data for the order
$partial_payments = $order->get_meta('wooadvancepay_partial_payments', true);

// If there are no partial payments, exit the template
if (empty($partial_payments)) {
    return;
}
?>

<div class="order-partial-payments">
    <h3>Advance Payments</h3>

    <ul>
        <?php foreach ($partial_payments as $partial_payment) : ?>
            <li>
                <?php echo wc_price($partial_payment['amount']); ?> (<?php echo date('Y-m-d H:i:s', $partial_payment['timestamp']); ?>)
            </li>
        <?php endforeach; ?>
    </ul>
</div>
