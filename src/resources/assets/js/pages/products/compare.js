/**
 * سانیار فاز C — جاوااسکریپت صفحه مقایسه
 * جستجوی محصول مشابه در مودال → POST similar-compare → جایگزینی ردیف نتایج
 */
(function () {
    'use strict';

    $(document).on('submit', '#compare-products-form', function (e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);

        $.ajax({
            url: form.action,
            type: 'POST',
            data: formData,
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function (data) {
                var wrapper = document.createElement('div');
                wrapper.innerHTML = data;
                var content = wrapper.querySelector('.products-row');

                if (content) {
                    document.querySelector('.compare-modal .products-row').innerHTML = content.innerHTML;
                }
            },
            error: function (xhr) {
                console.error('Saniar compare: search error', xhr);
            },
            cache: false,
            contentType: false,
            processData: false
        });
    });
})();
