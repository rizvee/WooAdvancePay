jQuery(document).ready(function ($) {
    // Handle form submission for saving plugin settings
    $('#wooadvancepay-settings-form').submit(function (event) {
        event.preventDefault();

        var data = {
            action: 'wooadvancepay_save_settings',
            _wpnonce: $('#_wpnonce').val(),
            advance_payment_percentage: $('#advance_payment_percentage').val(),
            advance_payment_fixed_amount: $('#advance_payment_fixed_amount').val(),
            advance_payment_localities: $('#advance_payment_localities').val()
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: function (response) {
                if (response.success) {
                    $('.wooadvancepay-settings-message').removeClass('error').addClass('success').html('Settings saved successfully!');
                } else {
                    $('.wooadvancepay-settings-message').removeClass('success').addClass('error').html('An error occurred while saving settings.');
                }
            }
        });
    });

    // Handle adding and removing localities
    $('#add-locality').click(function (event) {
        event.preventDefault();

        var localityName = $('#locality-name').val();
        if (!localityName) {
            return;
        }

        var localityHTML = '<div class="wooadvancepay-locality">' +
            '<input type="text" name="advance_payment_localities[]" value="' + localityName + '" class="regular-input">' +
            '<button class="button button-small button-remove">Remove</button>' +
            '</div>';

        $('#advance_payment_localities-list').append(localityHTML);
        $('#locality-name').val('');
    });

    $(document).on('click', '.button-remove', function (event) {
        event.preventDefault();

        $(this).closest('.wooadvancepay-locality').remove();
    });
});
