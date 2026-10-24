/* ============================================================
   پروفایل فروشنده — سانیار (فاز J)
   - کاسکید استان → شهر (#sfe-province → #sfe-city)
   - سوییچ مالیات بر ارزش افزوده (نمایش جعبه آپلود گواهی)
   - ارسال AJAX فرم پروفایل (FormData/multipart) با مودال تأیید
   - امضای قرارداد الکترونیک (#sfe-econtract-accept)
   بدون پلاگین جدید — jQuery + Bootstrap5 موجود قالب
   ============================================================ */
(function () {
    'use strict';

    var $ = window.jQuery;
    if (!$) return;

    /* ================= ابزارهای مشترک (همسان seller-orders.js) ================= */

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

    /* ================= استان → شهر (همسان seller-auth.js) ================= */

    function initCityCascade() {
        var $state = $('#sfe-province');
        if (!$state.length) return;
        var citiesUrl = $state.data('cities-url') || '/province/get-cities';

        $state.on('change', function () {
            var provinceId = $(this).val();
            var $city = $('#sfe-city');
            if (!provinceId) {
                $city.empty().append('<option value="">ابتدا استان را انتخاب کنید</option>');
                return;
            }
            $.ajax({
                url: citiesUrl,
                type: 'GET',
                data: { id: provinceId },
                dataType: 'json',
                success: function (cities) {
                    $city.empty().append('<option value="">انتخاب شهر...</option>');
                    $.each(cities, function (i, c) {
                        $('<option>').val(c.id).text(c.name).appendTo($city);
                    });
                }
            });
        });
    }

    /* ================= مالیات → جعبه آپلود گواهی ================= */

    function initVatToggle() {
        var $vat = $('select[name="vat_free"]');
        if (!$vat.length) return;
        $vat.on('change', function () {
            var $box = $('#sfe-vat-upload');
            if (!$box.length) return;
            if (String($(this).val()) === '1') {
                $box.removeClass('d-none');
            } else {
                $box.addClass('d-none');
            }
        });
    }

    /* ================= ارسال فرم پروفایل (AJAX + مودال تأیید) ================= */

    function initSubmit() {
        var $form = $('#sfe-profile-form');
        if (!$form.length) return;

        // ارسال فرم → فقط مودال تأیید باز می‌شود (تب‌های Bootstrap خودش کار می‌کند)
        $form.on('submit', function (e) {
            e.preventDefault();
            $('#sfe-confirm-modal').modal('show');
        });

        $('#sfe-confirm-submit').on('click', function () {
            var $btn = $(this);

            // FIX باگ WeblakShop: پرچم قفل روی سلکتور اشتباه (#post-create-form) ست می‌شد؛
            // اینجا روی خود فرم ست می‌شود تا دابل‌کلیک باعث دو درخواست نشود.
            if ($form.data('submitting')) return;
            $form.data('submitting', true);

            var restore = $btn.html();
            $btn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>در حال ثبت…');

            var formData = new FormData($form[0]);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (data) {
                if (typeof data === 'string' && $.trim(data) === 'success') {
                    $('#sfe-confirm-modal').modal('hide');
                    toast('تغییرات شما با موفقیت ثبت شد.', 'success');
                    setTimeout(function () {
                        window.location.href = $form.data('redirect');
                    }, 900);
                } else {
                    toast('پاسخ نامعتبر از سرور دریافت شد.', 'error');
                    $form.data('submitting', false);
                    $btn.prop('disabled', false).html(restore);
                }
            }).fail(function (xhr) {
                if (xhr.status === 419) {
                    toast('نشست شما منقضی شده؛ صفحه را نوسازی کنید.', 'warning');
                } else {
                    ajaxError(xhr, 'خطا در ثبت تغییرات');
                }
                $form.data('submitting', false);
                $btn.prop('disabled', false).html(restore);
            });
        });
    }

    /* ================= امضای قرارداد الکترونیک ================= */

    function initEcontract() {
        var $btn = $('#sfe-econtract-accept');
        if (!$btn.length) return;

        $btn.on('click', function () {
            var val = $('input[name="econtract"]:checked').val();

            if (val === undefined || val === null || val === '') {
                toast('یک گزینه را انتخاب کنید.', 'error');
                return;
            }
            if (String(val) === '0') {
                $('#sfe-econtract-modal').modal('hide');
                return;
            }

            // گزینه «می‌پذیرم» — ثبت امضا روی سرور
            $btn.prop('disabled', true);
            $.ajax({
                url: $btn.data('action'),
                type: 'POST',
                data: { econtract: 1 },
                headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                dataType: 'json'
            }).done(function (data) {
                if (data && data.status === 'success') {
                    toast('قرارداد با موفقیت امضا شد.', 'success');
                    setTimeout(function () { window.location.reload(); }, 900);
                } else if (data && data.status === 'documents') {
                    toast('مدارک شما هنوز تأیید نشده است.', 'error');
                    $btn.prop('disabled', false);
                } else {
                    toast('خطا در امضای قرارداد.', 'error');
                    $btn.prop('disabled', false);
                }
            }).fail(function (xhr) {
                if (xhr.status === 419) {
                    toast('نشست شما منقضی شده؛ صفحه را نوسازی کنید.', 'warning');
                } else {
                    ajaxError(xhr, 'خطا در امضای قرارداد');
                }
                $btn.prop('disabled', false);
            });
        });
    }

    $(function () {
        initCityCascade();
        initVatToggle();
        initSubmit();
        initEcontract();
    });
})();
