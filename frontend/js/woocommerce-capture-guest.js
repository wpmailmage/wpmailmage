(function ($) {
  $(document).on('change', 'input#billing_email', function () {
    var data = {
      // Billing
      billing_first_name: $('#billing_first_name').val(),
      billing_last_name: $('#billing_last_name').val(),
      billing_company: $('#billing_company').val(),
      billing_country: $('#billing_country').val(),
      billing_address_1: $('#billing_address_1').val(),
      billing_address_2: $('#billing_address_2').val(),
      billing_city: $('#billing_city').val(),
      billing_state: $('#billing_state').val(),
      billing_postcode: $('#billing_postcode').val(),
      billing_phone: $('#billing_phone').val(),
      billing_email: $('#billing_email').val(),

      // Shipping
      shipping_first_name: $('#shipping_first_name').val(),
      shipping_last_name: $('#shipping_last_name').val(),
      shipping_company: $('#shipping_company').val(),
      shipping_country: $('#shipping_country').val(),
      shipping_address_1: $('#shipping_address_1').val(),
      shipping_address_2: $('#shipping_address_2').val(),
      shipping_city: $('#shipping_city').val(),
      shipping_state: $('#shipping_state').val(),
      shipping_postcode: $('#shipping_postcode').val(),
      shipping_phone: $('#shipping_phone').val(),

      // Misc
      order_comments: $('#order_comments').val(),
    };
    $.ajax({
      url: ewp.ajax_url,
      method: 'POST',
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', ewp.nonce);
      },
      data: data,
    });
  });
})(jQuery);
