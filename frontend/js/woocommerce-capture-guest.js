(function ($) {
  $(document).on('change', 'input#billing_email', function () {
    var data = {
      // Billing
      billing_first_name: $('#billing_first_name').val(),
      billing_last_name: $('#billing_last_name').val(),
      billing_email: $('#billing_email').val(),
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
