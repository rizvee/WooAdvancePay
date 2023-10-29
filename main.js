jQuery(document).ready(function($) {
    // Listen for changes in the payment method and shipping location
    $(document).on('change', 'input[name="payment_method"]', function() {
        // Check if "Cash on Delivery" is selected
        var selectedPaymentMethod = $(this).val();
        
        // Replace "Dhaka" with your specific location condition
        var shippingLocation = $('#shipping_location').val();
        var excludedLocation = 'Dhaka';

        if (selectedPaymentMethod === 'cod' && shippingLocation !== excludedLocation) {
            // Enable WooAdvancePay functionality
            // Add your code here to apply WooAdvancePay
            console.log('WooAdvancePay enabled');
        } else {
            // Disable WooAdvancePay functionality
            // Add your code here to disable WooAdvancePay
            console.log('WooAdvancePay disabled');
        }
    });
});
