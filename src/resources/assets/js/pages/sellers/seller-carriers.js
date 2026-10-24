/* ============================================================
   حامل‌های فروشنده — سانیار (فاز J)
   حالت‌ها از data-saniar-carriers روی تگ اسکریپت:
   - index  : مودال حذف (AJAX + reload) + مودال شهرهای تحت پوشش (فرگمنت AJAX)
   - create : فرم روش ارسال (کاسکاد استان→شهر، درخت چک‌باکس شهرها،
              بازه‌های ارسال + پیش‌نمایش روزها از سرویس تعطیلات)
   - edit   : همان فرم با مقادیر بایندشده
   بدون پلاگین جدید — jQuery + Bootstrap5 موجود قالب
   ============================================================ */
(function () {
    'use strict';

    var $ = window.jQuery;
    if (typeof $ === 'undefined') return;

    /* ================= ابزارهای مشترک (الگوی seller-orders.js) ================= */

    function esc(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function fmtNumber(num) {
        num = Number(num || 0);
        return num.toLocaleString('en-US');
    }

    function toast(message, type) {
        var $t = $('#saniar-toast');
        if (!$t.length) {
            $t = $('<div id="saniar-toast" class="saniar-toast"></div>').appendTo('body');
        }
        $t.text(message)
            .removeClass('saniar-toast-error saniar-toast-success saniar-toast-warning')
            .addClass(type === 'error' ? 'saniar-toast-error' : (type === 'warning' ? 'saniar-toast-warning' : 'saniar-toast-success'))
            .addClass('saniar-toast-show');
        if ($t.data('timer')) clearTimeout($t.data('timer'));
        $t.data('timer', setTimeout(function () { $t.removeClass('saniar-toast-show'); }, 3600));
    }

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function ajaxError(xhr, fallback) {
        var msg = fallback || 'خطای ناشناخته';
        if (xhr && xhr.responseJSON) {
            if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
            else if (xhr.responseJSON.errors) {
                var first = Object.keys(xhr.responseJSON.errors)[0];
                if (first) msg = xhr.responseJSON.errors[first][0];
            }
        }
        toast(msg, 'error');
    }

    var scriptTag = document.currentScript ||
        (function () { var s = document.getElementsByTagName('script'); return s[s.length - 1]; })();
    var MODE = scriptTag.getAttribute('data-saniar-carriers') || '';
    var DATES_URL = scriptTag.getAttribute('data-dates-url') || '';

    /* ================================================================
       بخش ۱) حالت index — مودال حذف + مودال شهرها
       ================================================================ */
    function initIndex() {

        /* --- حذف روش ارسال --- */
        $(document).on('click', '.sfa-btn-delete', function () {
            $('#sfa-carrier-delete-form').attr('action', $(this).data('action') || '#');
        });

        $('#sfa-carrier-delete-form').on('submit', function (e) {
            e.preventDefault();
            var form = this;
            var $btn = $(form).find('button[type="submit"]').prop('disabled', true);

            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: new FormData(form), /* شامل _token و _method=delete */
                processData: false,
                contentType: false,
                cache: false,
                headers: { 'X-CSRF-TOKEN': csrf() }
            }).done(function (res) {
                if (String(res).trim() === 'success') {
                    $('#delete-modal').modal('hide');
                    toast('روش ارسال با موفقیت حذف شد.', 'success');
                    setTimeout(function () { window.location.reload(); }, 700);
                } else {
                    toast('پاسخ نامعتبر از سرور دریافت شد.', 'error');
                    $btn.prop('disabled', false);
                }
            }).fail(function (xhr) {
                if (xhr.status === 419) {
                    toast('نشست شما منقضی شده؛ صفحه را نوسازی کنید.', 'warning');
                } else {
                    ajaxError(xhr, 'خطا در حذف روش ارسال');
                }
                $btn.prop('disabled', false);
            });
        });

        /* --- شهرهای تحت پوشش (فرگمنت AJAX) --- */
        $(document).on('click', '.sfa-cities-show', function (e) {
            e.preventDefault();
            var url = $(this).attr('href') || $(this).data('action');
            var $list = $('#sfa-cities-list');
            if (!url || !$list.length) return;

            $list.html('<div class="text-center py-4"><span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span><span class="font-13 text-muted">در حال بارگذاری…</span></div>');

            $.ajax({
                url: url,
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (html) {
                $list.html(html);
                $('#sfa-cities-modal').modal('show');
            }).fail(function (xhr) {
                if (xhr.status === 419) {
                    toast('نشست شما منقضی شده؛ صفحه را نوسازی کنید.', 'warning');
                } else {
                    ajaxError(xhr, 'خطا در دریافت شهرهای تحت پوشش');
                }
                $list.html('<div class="text-center text-danger font-13 py-3">خطا در دریافت شهرهای تحت پوشش.</div>');
            });
        });
    }

    /* ================================================================
       بخش ۲) حالت create + edit — فرم روش ارسال
       ================================================================ */
    function initForm() {
        var $form = $('#sfa-carrier-form');
        if (!$form.length) return;

        var submitting = false;

        /* --- کاسکاد استان → شهر (الگوی initCityCascade در seller-auth.js) --- */
        function initCityCascade() {
            var $province = $('#sfa-province');
            if (!$province.length) return;
            var citiesUrl = $province.data('cities-url');

            $province.on('change', function () {
                var provinceId = $(this).val();
                var $city = $('#sfa-city');

                if (!provinceId) {
                    $city.empty().append('<option value="">ابتدا استان را انتخاب کنید</option>');
                    return;
                }

                $.ajax({
                    url: citiesUrl,
                    type: 'GET',
                    data: { id: provinceId },
                    dataType: 'json'
                }).done(function (cities) {
                    $city.empty().append('<option value="">انتخاب شهر…</option>');
                    $.each(cities, function (i, c) {
                        $('<option>').val(c.id).text(c.name).appendTo($city);
                    });
                }).fail(function () {
                    toast('خطا در دریافت شهرهای استان.', 'error');
                });
            });
        }

        /* --- نوع شهرهای تحت پوشش --- */
        function initCoveredCitiesToggle() {
            $('#sfa-covered-cities').on('change', function () {
                if ($(this).val() === 'select_city') {
                    $('#sfa-included-cities-container').removeClass('d-none');
                } else {
                    $('#sfa-included-cities-container').addClass('d-none');
                }
            });
        }

        /* --- درخت چک‌باکس شهرها: تیک استان = همه شهرهای آن --- */
        function syncProvinceToggles() {
            $('.sfa-province-toggle').each(function () {
                var $group = $('#' + $(this).data('target'));
                var $checks = $group.find('.sfa-city-check');
                var total = $checks.length;
                var checked = $checks.filter(':checked').length;
                this.checked = total > 0 && checked === total;
                this.indeterminate = checked > 0 && checked < total;
            });
        }

        function initCityTree() {
            $(document).on('change', '.sfa-province-toggle', function () {
                $('#' + $(this).data('target')).find('.sfa-city-check').prop('checked', this.checked);
            });
            $(document).on('change', '.sfa-city-check', syncProvinceToggles);
        }

        /* --- بازه زمانی ارسال: نمایش/مخفی‌سازی بخش‌ها --- */
        function toggleDeliveryFields() {
            if ($('#sfa-delivery-type').val() === 'user_select') {
                $('#sfa-default-range-container').addClass('d-none');
                $('#sfa-user-ranges-container').removeClass('d-none');
                calculatePreview();
            } else {
                $('#sfa-default-range-container').removeClass('d-none');
                $('#sfa-user-ranges-container').addClass('d-none');
                $('#sfa-dates-preview-container').addClass('d-none');
                $('#sfa-dates-preview').empty();
            }
        }

        /* --- پیش‌نمایش روزهای ارسال از سرویس تعطیلات --- */
        function calculatePreview() {
            if ($('#sfa-delivery-type').val() !== 'user_select') return; /* فقط در حالت انتخاب خریدار */
            if (!DATES_URL) return;

            var rangeDays = parseInt($('input[name="user_select_ranges"]:checked').val(), 10);
            var startDays = parseInt($('#sfa-start-days').val(), 10) || 1;

            if (!rangeDays || isNaN(rangeDays)) {
                $('#sfa-dates-preview-container').addClass('d-none');
                return;
            }

            var disableHolidays = $('input[name="disable_holidays"]').is(':checked');
            var disableFridays = $('input[name="disable_fridays"]').is(':checked');

            $('#sfa-dates-preview').html(
                '<div class="col-12 text-center py-3"><span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span><span class="font-13 text-muted">در حال محاسبه روزها…</span></div>'
            );
            $('#sfa-dates-preview-container').removeClass('d-none');

            $.ajax({
                url: DATES_URL,
                type: 'POST',
                data: { start_days: startDays - 1, range_days: rangeDays },
                headers: { 'X-CSRF-TOKEN': csrf() },
                dataType: 'json'
            }).done(function (res) {
                if (res && res.success) {
                    renderPreview(res.dates || [], disableHolidays, disableFridays);
                } else {
                    $('#sfa-dates-preview-container').addClass('d-none');
                    toast('خطا در محاسبه پیش‌نمایش روزها.', 'error');
                }
            }).fail(function (xhr) {
                $('#sfa-dates-preview-container').addClass('d-none');
                if (xhr.status === 419) {
                    toast('نشست شما منقضی شده؛ صفحه را نوسازی کنید.', 'warning');
                } else {
                    toast('خطا در ارتباط با سرویس تعطیلات.', 'error');
                }
            });
        }

        function renderPreview(dates, disableHolidays, disableFridays) {
            var $box = $('#sfa-dates-preview').empty();

            if (!dates.length) {
                $('#sfa-dates-preview-container').addClass('d-none');
                return;
            }

            for (var i = 0; i < dates.length; i++) {
                var d = dates[i];
                var badge = '';
                var dimmed = false;

                if (d.is_friday) {
                    badge = '<span class="badge rounded-pill danger-label font-11 mt-1">جمعه</span>';
                    dimmed = disableFridays;
                } else if (d.is_holiday) {
                    badge = '<span class="badge rounded-pill danger-label font-11 mt-1">تعطیل: ' + esc(d.holiday_description || 'تعطیل رسمی') + '</span>';
                    dimmed = disableHolidays;
                }

                $box.append(
                    '<div class="col-6 col-md-3">'
                    + '<div class="sfa-date-card h-100 ' + (d.is_holiday ? 'sfa-date-card-holiday' : '') + (dimmed ? ' sfa-date-card-disabled' : '') + '">'
                    + '<i class="bi bi-calendar-day main-color-one-color d-block font-16" aria-hidden="true"></i>'
                    + '<span class="font-13 fw-bold d-block mt-1">' + esc(d.jalali_display) + '</span>'
                    + '<span class="font-12 text-muted d-block">' + esc(d.day_name) + '</span>'
                    + badge
                    + '</div>'
                    + '</div>'
                );
            }

            $('#sfa-dates-preview-container').removeClass('d-none');
        }

        /* --- رویدادها --- */
        $('#sfa-delivery-type').on('change', toggleDeliveryFields);
        $('input[name="user_select_ranges"]').on('change', calculatePreview);
        $('#sfa-start-days').on('change', calculatePreview);
        $('input[name="disable_holidays"], input[name="disable_fridays"]').on('change', calculatePreview);

        /* --- ارسال فرم (AJAX + فایل) --- */
        $form.on('submit', function (e) {
            e.preventDefault();
            if (submitting) return;

            if ($('#sfa-title').val().trim() === '') {
                toast('عنوان روش ارسال را وارد کنید.', 'error');
                return;
            }

            if ($('#sfa-delivery-type').val() === 'user_select' && !$('input[name="user_select_ranges"]:checked').length) {
                toast('یک بازه زمانی ارسال را انتخاب کنید.', 'error');
                return;
            }

            if ($('#sfa-covered-cities').val() === 'select_city' && !$('input[name="included_cities[]"]:checked').length) {
                toast('حداقل یک شهر تحت پوشش انتخاب کنید.', 'error');
                return;
            }

            submitting = true;
            var $btn = $('#sfa-submit-btn').prop('disabled', true);
            var $spinner = $btn.find('.saniar-btn-spinner').removeClass('d-none');
            var done = false;

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: new FormData($form[0]), /* شامل فایل تصویر — بدون سریالایز دستی */
                processData: false,
                contentType: false,
                cache: false,
                headers: { 'X-CSRF-TOKEN': csrf() }
            }).done(function (res) {
                if (String(res).trim() === 'success') {
                    done = true; /* گارد دوبل‌سابمیت تا هدایت کامل می‌ماند */
                    window.location.href = $form.data('redirect'); /* توست سشن بعد از هدایت نمایش داده می‌شود */
                } else {
                    toast('پاسخ نامعتبر از سرور دریافت شد.', 'error');
                }
            }).fail(function (xhr) {
                if (xhr.status === 419) {
                    toast('نشست شما منقضی شده؛ صفحه را نوسازی کنید.', 'warning');
                } else {
                    ajaxError(xhr, 'خطا در ذخیره روش ارسال');
                }
            }).always(function () {
                submitting = false;
                if (!done) {
                    $btn.prop('disabled', false);
                    $spinner.addClass('d-none');
                }
            });
        });

        /* --- اجرای اولیه --- */
        initCityCascade();
        initCoveredCitiesToggle();
        initCityTree();
        syncProvinceToggles();   /* وضعیت تیک استان‌ها بر اساس شهرهای انتخاب‌شده (حالت edit) */
        toggleDeliveryFields();  /* وضعیت اولیه بخش‌ها + محاسبه پیش‌نمایش در حالت user_select */
    }

    $(function () {
        if (MODE === 'index') initIndex();
        if (MODE === 'create' || MODE === 'edit') initForm();
    });
})();
