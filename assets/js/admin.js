jQuery(document).ready(function ($) {

    // Function to show/hide payment input fields based on selected payment type
    function togglePaymentInputFields() {
        var paymentType = $('input[name="advance_payment_type"]:checked').val();
        if (paymentType === 'percentage') {
            $('#percentage_field').show();
            $('#fixed_amount_field').hide();
        } else if (paymentType === 'fixed_amount') {
            $('#fixed_amount_field').show();
            $('#percentage_field').hide();
        } else { // Hide both if no type or an unexpected type is selected
            $('#percentage_field').hide();
            $('#fixed_amount_field').hide();
        }
    }

    // Call on page load
    togglePaymentInputFields();

    // Call when radio buttons change
    $('input[name="advance_payment_type"]').change(togglePaymentInputFields);

    // Handle form submission for saving plugin settings
    $('#wooadvancepay-settings-form').submit(function (event) {
        event.preventDefault();

        var $form = $(this);
        var $messageDiv = $('.wooadvancepay-settings-message');
        $messageDiv.removeClass('error success').empty(); // Clear previous messages

        var data = {
            action: 'wooadvancepay_save_settings',
            _wpnonce: $('#_wpnonce').val(), // Nonce field should have id="_wpnonce"
            advance_payment_type: $('input[name="advance_payment_type"]:checked').val(),
            advance_payment_percentage: $('input[name="advance_payment_percentage"]').val(), // Name used in PHP
            advance_payment_fixed_amount: $('input[name="advance_payment_fixed_amount"]').val(), // Name used in PHP
            advance_payment_localities: $('textarea[name="advance_payment_localities"]').val() // Name used in PHP
        };

        // Add a loading indicator
        $form.find('.button-primary').prop('disabled', true).after('<span class="spinner is-active" style="vertical-align: middle; margin-left: 5px;"></span>');


        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: function (response) {
                if (response.success) {
                    $messageDiv.addClass('success').html('<p>' + response.data.message + '</p>');
                } else {
                    var errorMessage = 'An error occurred while saving settings.';
                    if (response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                    $messageDiv.addClass('error').html('<p>'_ + errorMessage + '</p>');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                var errorMessage = 'An AJAX error occurred: ' + textStatus;
                if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                    errorMessage = jqXHR.responseJSON.data.message;
                } else if (jqXHR.responseText) {
                    try {
                        var response = JSON.parse(jqXHR.responseText);
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                    } catch (e) {
                        // Not JSON, or no specific message
                    }
                }
                 $messageDiv.addClass('error').html('<p>' + errorMessage + '</p>');
            },
            complete: function() {
                // Remove spinner and re-enable button
                $form.find('.spinner').remove();
                $form.find('.button-primary').prop('disabled', false);
            }
        });
    });

    // Unused localities JS has been removed.
});
