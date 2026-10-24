/* ============================================================
   صفحه تکمیل خرید (چک‌اوت) — سانیار
   - محاسبه قیمت زنده (getPrices): شهر + حامل هر مرسوله + تاریخ ارسال
   - همگام‌سازی کارت‌های انتخاب (آدرس/حامل/درگاه/تاریخ) با radioهای فرم
   - کیف پول (بررسی موجودی)، کد تخفیف (مودال AJAX)
   - مودال افزودن/ویرایش آدرس (بدون نقشه — فرم ساده + لود شهرها)
   - ولیدیشن سمت کلاینت و ثبت سفارش
   ============================================================ */
(function () {
    'use strict';

    var $ = window.jQuery;
    if (!$) return;

    /* ---------------- ابزارها ---------------- */
    function toast(message, type) {
        var $t = $('#saniar-toast');
        if (!$t.length) {
            $t = $('<div id="saniar-toast" class="saniar-toast"></div>').appendTo('body');
        }
        $t.text(message)
            .removeClass('saniar-toast-error saniar-toast-success')
            .addClass(type === 'error' ? 'saniar-toast-error' : 'saniar-toast-success')
            .addClass('saniar-toast-show');
        clearTimeout($t.data('timer'));
        $t.data('timer', setTimeout(function () { $t.removeClass('saniar-toast-show'); }, 3200));
    }

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function showJsErrors(messages) {
        var $box = $('#checkout-js-errors');
        if (!$box.length) {
            toast(typeof messages === 'string' ? messages : messages[0], 'error');
            return;
        }
        if (typeof messages === 'string') messages = [messages];
        var $ul = $box.find('ul').empty();
        $.each(messages, function (i, msg) { $('<li>').text(msg).appendTo($ul); });
        $box.removeClass('d-none');
        $box[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function hideJsErrors() {
        $('#checkout-js-errors').addClass('d-none');
    }

    /* ============================================================
       ۱) محاسبه قیمت زنده — GET front.checkout.prices
       ============================================================ */
    var priceActionUrl = $('#checkout-form').data('price-action') ||
        $('#address-section').data('action');

    function selectedCityId() {
        var $checked = $('input[name="address"]:checked');
        if ($checked.length) {
            return $checked.closest('.addresses-item').data('city') || null;
        }
        return null;
    }

    function collectCarriers() {
        var carriers = {};
        var count = 0;
        $('input.carrier-select-btn:checked').each(function () {
            carriers['carrier_id_' + $(this).data('group-id')] = this.value;
            count++;
        });
        if (count === 0) {
            // سرور carriers را الزامی می‌خواند؛ کلید ساختگی برای رفع foreach(null)
            carriers['__none'] = '0';
        }
        return carriers;
    }

    function applyPricesResponse(res, idGroupSelect) {
        if (res.checkout_sidebar) {
            var oldSidebar = document.getElementById('checkout-sidebar');
            if (oldSidebar) {
                oldSidebar.outerHTML = res.checkout_sidebar;
            } else {
                $('#checkout-sidebar-wrapper').html(res.checkout_sidebar);
            }
        }
        if (res.carriers_container) {
            $('#checkout-carrier-container').html(res.carriers_container);
        }
        if (res.deliveryDateForOne && idGroupSelect) {
            var $period = $('#send-period-container-' + idGroupSelect);
            if ($period.length) {
                $period.html(res.deliveryDateForOne);
            }
        }
    }

    function getPrices(idGroupSelect, opts) {
        opts = opts || {};
        var dfd = $.Deferred();

        var data = {
            city_id: selectedCityId() || '',
            is_first_load: opts.isFirstLoad ? 1 : 0,
        };
        var carriers = collectCarriers();
        $.each(carriers, function (key, val) {
            data['carriers[' + key + ']'] = val;
        });
        if (idGroupSelect) {
            data.idGroupSelect = idGroupSelect;
        }

        var $form = $('#checkout-form');
        if (!$form.hasClass('saniar-loading')) $form.addClass('saniar-loading');

        $.ajax({
            url: priceActionUrl,
            type: 'GET',
            data: data,
            dataType: 'json'
        })
            .done(function (res) {
                applyPricesResponse(res, idGroupSelect);
                dfd.resolve(res);
            })
            .fail(function (xhr) {
                if (xhr.status === 419) {
                    toast('نشست شما منقضی شده؛ صفحه بروزرسانی می‌شود...', 'error');
                    setTimeout(function () { window.location.reload(); }, 1200);
                } else {
                    toast('خطا در دریافت قیمت‌ها؛ لطفاً صفحه را بروزرسانی کنید.', 'error');
                }
                dfd.reject();
            })
            .always(function () {
                $('#checkout-form').removeClass('saniar-loading');
            });

        return dfd.promise();
    }

    /* ---------- بارگذاری اولیه: انتخاب خودکار حامل‌ها + تاریخ‌ها ---------- */
    function loadDatesForPendingGroups() {
        var pending = [];
        $('input.carrier-select-btn:checked').each(function () {
            var $radio = $(this);
            var groupId = $radio.data('group-id');
            if ($radio.data('delivery-type') === 'user_select' &&
                !$('#send-period-container-' + groupId).children().length) {
                pending.push(groupId);
            }
        });
        // زنجیره‌ای تا درخواست‌ها هم‌زمان نشوند
        (function next() {
            if (!pending.length) return;
            var gid = pending.shift();
            getPrices(gid).done(next);
        })();
    }

    $(function () {
        var isFirstRun = true;
        getPrices(null, { isFirstRun: true }).done(function () {
            loadDatesForPendingGroups();
            isFirstRun = false;
        });
    });

    /* ============================================================
       ۲) کارت‌های انتخاب — همگام‌سازی radio + کلاس active
       ============================================================ */

    // آدرس: کلیک روی کارت → انتخاب radio + دریافت قیمت با شهر جدید
    $(document).on('click', '.addresses-item', function (e) {
        if ($(e.target).closest('a, button, input, label').length) return; // کلیک روی دکمه‌های کارت
        var $card = $(this);
        var $radio = $card.find('input[name="address"]');
        if (!$radio.length || $radio.prop('checked')) return;
        $radio.prop('checked', true).trigger('change');
    });

    $(document).on('change', 'input[name="address"]', function () {
        $('.addresses-item').removeClass('active-address active');
        var $card = $(this).closest('.addresses-item').addClass('active-address active');
        // جابه‌جایی نشان «آدرس پیشفرض» صرفاً بصری است؛ رندر سمت سرور ملاک نهایی است
        if (selectedCityId()) {
            getPrices(null);
        }
    });

    // حامل: کلیک روی آیتم → چک radio (change خودش قیمت‌ها را می‌گیرد)
    $(document).on('click', '.shipping-item', function (e) {
        if ($(e.target).closest('input').length) return;
        var $radio = $(this).find('input[type="radio"]');
        if ($radio.length && !$radio.prop('checked')) {
            $radio.prop('checked', true).trigger('change');
        }
    });

    $(document).on('change', 'input.carrier-select-btn', function () {
        var gid = $(this).data('group-id');
        // همگام‌سازی کلاس active در همه آیتم‌های این گروه
        var $box = $(this).closest('.shipping-methods');
        $box.find('.shipping-item').removeClass('active');
        $(this).closest('.shipping-item').addClass('active');
        if (selectedCityId()) {
            getPrices(gid);
        }
    });

    // درگاه پرداخت
    $(document).on('click', '.bank-item', function (e) {
        if ($(e.target).closest('input').length) return;
        var $radio = $(this).find('input[name="gateway"]');
        if ($radio.length && !$radio.prop('checked')) {
            $radio.prop('checked', true).trigger('change');
        }
    });

    $(document).on('change', 'input[name="gateway"]', function () {
        var $input = $(this);
        $('.bank-item').removeClass('active');
        $input.closest('.bank-item').addClass('active');

        if (this.value === 'wallet') {
            var balance = parseFloat($('#wallet-balance').data('value')) || 0;
            var payable = parseFloat($('#final-price').data('value')) || 0;
            if (balance < payable) {
                // موجودی کافی نیست → بازگشت به درگاه اینترنتی
                toast('موجودی کیف پول شما کافی نیست.', 'error');
                var $fallback = $('input[name="gateway"]').not('#wallet').first();
                if ($fallback.length) {
                    $fallback.prop('checked', true).trigger('change');
                }
            }
        }
    });

    // تاریخ ارسال
    $(document).on('click', '.send-item', function (e) {
        if ($(e.target).closest('input').length) return;
        if ($(this).hasClass('disabled')) return;
        var $radio = $(this).find('input[type="radio"]');
        if ($radio.length && !$radio.prop('checked')) {
            $radio.prop('checked', true);
        }
        $(this).addClass('active').siblings().removeClass('active');
    });

    /* ---------- دکمه «آدرس پیشفرض» ---------- */
    $(document).on('click', '.sett-address', function (e) {
        e.preventDefault();
        var $btn = $(this);
        $.ajax({
            url: $btn.data('action'),
            type: 'GET',
            dataType: 'json'
        })
            .done(function (res) {
                if (res && res.active === 'success') {
                    var $card = $btn.closest('.addresses-item');
                    $card.find('input[name="address"]').prop('checked', true).trigger('change');
                    toast('آدرس پیشفرض بروزرسانی شد.', 'success');
                }
            })
            .fail(function () {
                toast('خطا در تنظیم آدرس پیشفرض.', 'error');
            });
    });

    /* ============================================================
       ۳) کد تخفیف (مودال)
       ============================================================ */
    $(document).on('submit', '#discount-create-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $err = $('#discount-error').addClass('d-none').empty();
        var $btn = $form.find('button[type="submit"]');
        var $spinner = $('#discount-spinner').removeClass('d-none');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': csrfToken() }
        })
            .done(function (res) {
                if (res === 'success') {
                    toast('کد تخفیف اعمال شد؛ صفحه بروزرسانی می‌شود...', 'success');
                    setTimeout(function () { window.location.reload(); }, 900);
                } else {
                    $err.text('پاسخ نامشخص از سرور.').removeClass('d-none');
                }
            })
            .fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var first = xhr.responseJSON.errors.code ? xhr.responseJSON.errors.code[0]
                        : Object.values(xhr.responseJSON.errors)[0][0];
                    $err.text(first).removeClass('d-none');
                } else {
                    $err.text('خطا در ثبت کد تخفیف؛ دوباره تلاش کنید.').removeClass('d-none');
                }
            })
            .always(function () {
                $spinner.addClass('d-none');
                $btn.blur();
            });
    });

    /* ============================================================
       ۴) مودال آدرس — افزودن/ویرایش (بدون نقشه)
       ============================================================ */
    var addressModalEl = document.getElementById('add-edit-address-modal');

    function loadCities(provinceId, selectedCityId) {
        var $city = $('#city');
        if (!provinceId) return $.Deferred().resolve().promise();
        return $.ajax({
            url: '/province/get-cities',
            type: 'GET',
            data: { id: provinceId },
            dataType: 'json'
        })
            .done(function (cities) {
                $city.empty().append('<option value="">انتخاب شهر...</option>');
                $.each(cities, function (i, c) {
                    $('<option>').val(c.id).text(c.name).appendTo($city);
                });
                if (selectedCityId) {
                    $city.val(String(selectedCityId));
                }
            })
            .fail(function () {
                toast('خطا در دریافت شهرها.', 'error');
            });
    }

    $(document).on('change', '#province', function () {
        loadCities(this.value, null);
    });

    if (addressModalEl) {
        addressModalEl.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            var $trigger = $(trigger);
            var $form = $('#add-update-address-form');
            var isEdit = !!$trigger.data('url');

            // بازنشانی
            $form[0].reset();
            $('#address-form-error').addClass('d-none').empty();
            $('#city').html('<option value="">ابتدا استان را انتخاب کنید...</option>');
            $form.attr('action', $trigger.data('UpdateUrl') || $form.attr('action'));
            $('#add-edit-address-modal-label').text(isEdit ? 'ویرایش آدرس' : 'ثبت آدرس جدید');
            $('#address-submit-btn').text(isEdit ? 'ذخیره تغییرات' : 'ثبت آدرس');

            if (isEdit) {
                $.ajax({
                    url: $trigger.data('url'),
                    type: 'GET',
                    dataType: 'json'
                })
                    .done(function (res) {
                        var a = res.address;
                        if (!a) return;
                        $('#address-fullname').val(a.fullname);
                        $('#address-mobile').val(a.mobile);
                        $('#address-postal').val(a.postal_code);
                        $('#address-building').val(a.buildingNumber);
                        $('#address-unit').val(a.unit || '');
                        $('#address').val(a.address);
                        $('input[name="lat"]').val(a.lat || '');
                        $('input[name="lng"]').val(a.lng || '');
                        if (a.province_id) {
                            $('#province').val(String(a.province_id));
                            loadCities(a.province_id, a.city_id);
                        }
                    })
                    .fail(function () {
                        toast('خطا در دریافت اطلاعات آدرس.', 'error');
                    });
            }
        });
    }

    $(document).on('submit', '#add-update-address-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $err = $('#address-form-error').addClass('d-none').empty();

        var required = ['fullname', 'mobile', 'postal_code', 'buildingNumber', 'province_id', 'city_id', 'address'];
        var missing = [];
        $.each(required, function (i, name) {
            var $f = $form.find('[name="' + name + '"]');
            if (!$f.val() || !$.trim(String($f.val()))) {
                missing.push(name);
                $f.addClass('is-invalid');
            } else {
                $f.removeClass('is-invalid');
            }
        });
        if (missing.length) {
            $err.text('لطفاً همه فیلدهای ستاره‌دار را تکمیل کنید.').removeClass('d-none');
            return;
        }

        var $btn = $('#address-submit-btn').addClass('loading');
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': csrfToken() }
        })
            .done(function (res) {
                if (res && (res.action === 'store' || res.action === 'update')) {
                    toast('آدرس با موفقیت ذخیره شد.', 'success');
                    setTimeout(function () { window.location.reload(); }, 900);
                }
            })
            .fail(function (xhr) {
                $btn.removeClass('loading');
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var msgs = [];
                    $.each(xhr.responseJSON.errors, function (k, v) { msgs.push(v[0]); });
                    $err.text(msgs[0]).removeClass('d-none');
                } else {
                    $err.text('خطا در ذخیره آدرس؛ دوباره تلاش کنید.').removeClass('d-none');
                }
            });
    });

    /* ============================================================
       ۵) ثبت سفارش — ولیدیشن کلاینت + سابمیت
       ============================================================ */
    $(document).on('click', '#checkout-link', function () {
        if (!$('#checkout-form').length) return; // صفحه سبد: cart.js عهده‌دار است

        hideJsErrors();
        var errors = [];
        var hasAddressCards = $('.addresses-item').length > 0;

        if (hasAddressCards && $('input[name="address"]:checked').length === 0) {
            errors.push('انتخاب آدرس تحویل سفارش الزامی است.');
        }

        var missingCarrier = false;
        $('#checkout-carrier-container .consignments').each(function () {
            if (!$(this).find('input.carrier-select-btn:checked').length) {
                missingCarrier = true;
            }
        });
        if (missingCarrier) {
            errors.push('برای هر مرسوله یک روش ارسال انتخاب کنید.');
        }

        var missingDate = false;
        $('.send-period-container').each(function () {
            if ($(this).find('input[type="radio"]').length &&
                !$(this).find('input[type="radio"]:checked').length) {
                missingDate = true;
            }
        });
        if (missingDate) {
            errors.push('زمان ارسال مرسوله را انتخاب کنید.');
        }

        if ($('input[name="gateway"]:checked').length === 0) {
            errors.push('روش پرداخت را انتخاب کنید.');
        }

        if (errors.length) {
            showJsErrors(errors);
            return;
        }

        var $btn = $(this).addClass('loading');
        // سابمیت عادی: پاسخ، رندر درگاه پرداخت (ریدایرکت بانک) است
        $('#checkout-form')[0].submit();
        setTimeout(function () { $btn.removeClass('loading'); }, 4000);
    });
})();
