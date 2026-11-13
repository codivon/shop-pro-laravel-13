function reloadCaptcha(selector) {
    var field  = selector ? document.querySelector(selector) : document.querySelector('[data-captcha]');
    if (!field) return;

    var img    = field.querySelector('.cap-image__img');
    var input  = field.querySelector('.cap-input__field');
    var loading = field.querySelector('.cap-image__loading');

    if (!img) return;

    if (loading) loading.classList.add('is-visible');

    // cache-bust: پارامتر تصادفی ⇒ مرورگر مجبور به درخواست جدید می‌شود
    img.src = img.src.split('?')[0] + '?_=' + Date.now() + Math.random().toString(36).slice(2, 6);

    img.onload = function () {
        if (loading) loading.classList.remove('is-visible');
        input.value = '';   // پاک‌کردن کد اشتباه قبلی
        input.focus();
    };
}

function block(el) {
    var block_ele = $(el);

    // Block Element
    block_ele.block({
        message: '<div class="mdi mdi-refresh icon-spin text-primary"></div>',
        overlayCSS: {
            backgroundColor: '#fff',
            cursor: 'wait'
        },
        css: {
            border: 0,
            padding: 0,
            backgroundColor: 'none'
        }
    });
}

function unblock(el) {
    $(el).unblock();
}

$.ajaxSetup({
    error: function (data) {

        reloadCaptcha();

        if (data.status == 403) {
            showCustomToast('اجازه ی دسترسی ندارید','error');
            return;
        } else if (data.status == 429) {
            showCustomToast('تعداد درخواست ها بیش از حد مجاز است لطفا پس از دقایقی مجدد تلاش کنید','error');
            return;
        } else if (data.status == 401) {
            showCustomToast('لطفا وارد حساب کاربری خود شوید','error');
            return;
        } else if (data.status == 500) {
            showCustomToast('خطایی در سرور رخ داده است','error');
            return;
        } else if (!data.responseJSON.errors) {
            showCustomToast('خطایی رخ داده است','error');
            return;
        }

        for (var key in data.responseJSON.errors) {
            // skip loop if the property is from prototype
            if (!data.responseJSON.errors.hasOwnProperty(key)) continue;

            var obj = data.responseJSON.errors[key];
            for (var prop in obj) {
                // skip loop if the property is from prototype
                if (!obj.hasOwnProperty(prop)) continue;

                showCustomToast(obj[prop],'warning')

            }
        }
    }
});

$(document).on('click', '#checkout-link', function () {
    $('#checkout-form').trigger('submit');
});

$('#province').change(function () {
    $('#city').empty();
    $('#city').append('<option value="">انتخاب کنید</option>');
    $('#city').trigger('change');
    $('.custom-select-ui select').niceSelect('update');

    if (!$(this).val()) {
        return;
    }

    var id = $(this).find(':selected').val();

    $.ajax({
        type: 'get',
        url: '/province/get-cities',
        data: {id: id},
        success: function (data) {
            $(data).each(function () {
                $('#city').append(
                    '<option value="' +
                        $(this)[0].id +
                        '">' +
                        $(this)[0].name +
                        '</option>'
                );
            });

            $('.custom-select-ui select').niceSelect('update');
        },
        beforeSend: function () {
            //
        }
    });
});

$('#search-header-btn-search').click(function () {
    event.preventDefault();
    var q = $('.search-header .header-search-input').val();
    if (q.length>=3){
        $('#search-form').submit();
    }
})

// **************  search
// تعریف تابع Debouncing
function debounce(func, delay) {
    let timeoutId;
    return function(...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
}

// جستجوی اصلی
const performSearch = debounce(function() {
    let q = $.trim($(this).val());

    if (q.length < 3) {
        $('.search-header #search-result').empty().addClass('d-none');
        $('.search-result-fixed').removeClass('d-none');
        return;
    }

    $('.search-header #search-result').removeClass('d-none').html('<div class="text-center p-3"><i class="fa fa-spinner fa-spin"></i> جستجو...</div>');
    $('.search-result-fixed').addClass('d-none');

    $.ajax({
        url: '/search',
        type: 'POST',
        data: {
            q: q,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            let html = response.html || response;
            $('.search-header #search-result').html(html);

            if ($('.search-header #search-result').find('.item, .search-result-brands, .search-result-category-title').length === 0) {
                $('.search-header #search-result').html('<div class="text-center p-3 text-muted">نتیجه‌ای یافت نشد</div>');
            }
        },
        error: function() {
            $('.search-header #search-result').html('<div class="text-center p-3 text-danger">خطا در جستجو</div>');
        }
    });
}, 350);

$('.search-header input.header-search-input').on('keyup', performSearch);

// بستن نتایج با کلیک خارج از باکس
$(document).on('click', function(e) {
    if (!$(e.target).closest('.search-header').length) {
        $('.search-header #search-result').empty().addClass('d-none');
        $('.search-result-fixed').removeClass('d-none');
    }
});

$('header.main-header .search-area form.search .close-search-result').on(
    'click',
    function () {
        $(this).removeClass('show');
        $(
            'header.main-header .search-area form.search .search-result'
        ).removeClass('open');
    }
);

function delay(callback, ms) {
    var timer = 0;
    return function () {
        var context = this,
            args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
            callback.apply(context, args);
        }, ms || 0);
    };
}

$('img.captcha').on('click', reloadCaptcha);

if (typeof $.lazyLoadXT != 'undefined') {
    $.lazyLoadXT.onload.addClass = 'animated fadeIn lazyLoadXT-completed';
    $.lazyLoadXT.selector = 'img[data-src]:not(.lazyLoadXT-completed)';

    setInterval(() => {
        $(window).lazyLoadXT();
    }, 1500);

    $(document).on('lazyerror', function (e, el) {
        $(el).attr('data-src', '');
    });
}

function inputFilter(e) {
    var key = e.keyCode || e.which;

    if (
        (!e.shiftKey && !e.altKey && !e.ctrlKey && key >= 48 && key <= 57) ||
        (key >= 96 && key <= 105) ||
        key == 8 ||
        key == 9 ||
        key == 13 ||
        key == 37 ||
        key == 39
    ) {
    } else {
        return false;
    }

    if ($(e.target).val().length > 0) {
        $(e.target).val('');
    }
}

jQuery.fn.activationCodeInput = function (options) {
    var defaults = {
        number: 4,
        length: 1
    };
    var settings = $.extend({}, defaults, options);

    return this.each(function () {
        var self = $(this);
        var activationCode = $('<div />').addClass('activation-code');
        var placeHolder = self.attr('placeholder');
        activationCode.append($('<span />').text(placeHolder));
        self.replaceWith(activationCode);
        activationCode.append(self);

        var activationCodeInputs = $('<div />').addClass(
            'activation-code-inputs'
        );

        for (var i = 1; i <= settings.number; i++) {
            activationCodeInputs.append(
                $('<input />').attr({
                    maxLength: settings.length,
                    onkeydown: 'return inputFilter(event)',
                    oncopy: 'return false',
                    onpaste: 'return false',
                    oncut: 'return false',
                    ondrag: 'return false',
                    ondrop: 'return false',
                    type: 'number'
                })
            );
        }

        activationCode.prepend(activationCodeInputs);

        activationCode.on('click touchstart', function (event) {
            if (!activationCode.hasClass('active')) {
                activationCode.addClass('active');
                setTimeout(function () {
                    activationCode
                        .find('.activation-code-inputs input:first-child')
                        .focus();
                }, 200);
            }
        });

        activationCode
            .find('.activation-code-inputs')
            .on('keyup input', 'input', function (event) {
                if (
                    $(this).val().toString().length == settings.length ||
                    event.keyCode == 39
                ) {
                    $(this).next().focus();
                    if ($(this).val().toString().length) {
                        $(this).css('border-color', '#46b2f0');
                    }
                }
                if (event.keyCode == 8 || event.keyCode == 37) {
                    $(this).prev().focus();
                    if (!$(this).val().toString().length) {
                        $(this).css('border-color', '#ccc');
                    }
                }
                var value = '';
                activationCode
                    .find('.activation-code-inputs input')
                    .each(function () {
                        value += $(this).val().toString();
                    });
                self.attr({
                    value: value
                });
            });

        $(document).on('click touchstart', function (e) {
            if (
                !$(e.target).parent().is(activationCode) &&
                !$(e.target).is(activationCode) &&
                !$(e.target).parent().parent().is(activationCode)
            ) {
                var hide = true;

                activationCode
                    .find('.activation-code-inputs input')
                    .each(function () {
                        if ($(this).val().toString().length) {
                            hide = false;
                        }
                    });
                if (hide) {
                    activationCode.removeClass('active');
                } else {
                    activationCode.addClass('active');
                }
            }
        });
    });
};

function number_format(nStr) {
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}
let intervals = {};

$('.product-special-end-date').each(function (index, el) {
    // Set the date we're counting down to
    var countDownDate = new Date($(el).data('date')).getTime();

    // Update the count down every 1 second
    let x = setInterval(function () {
        // Get today's date and time
        var now = new Date().getTime();

        // Find the distance between now and the count down date
        var distance = countDownDate - now;

        // Time calculations for days, hours, minutes and seconds
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor(
            (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
        );
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        $(el).find('[data-days]').text(days);
        $(el).find('[data-hours]').text(hours);
        $(el).find('[data-minutes]').text(minutes);
        $(el).find('[data-seconds]').text(seconds);

        // If the count down is over, write some text
        if (distance < 0) {
            clearInterval(intervals[index]);
            $(el);
        }
    }, 1000);

    intervals[index] = x;
});
