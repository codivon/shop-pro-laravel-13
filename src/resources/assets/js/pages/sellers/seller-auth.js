/* ============================================================
   صفحات احراز هویت فروشندگان — سانیار (فاز G)
   - خانه‌های OTP پنج‌رقمی (همان موتور فاز E)
   - شمارش معکوس + ارسال مجدد کد با POST (get-new-code-for-seller)
   - فرم‌های AJAX (data-ajax) با پاسخ JSON میزبان {status, message, redirect}
     و مدیریت خطای 422/429/419
   - سوییچ شخص حقیقی/حقوقی و بلی/خیر مالیات + آپلود فوری مدارک + استان→شهر
   ============================================================ */
(function () {
    'use strict';

    var $ = window.jQuery;
    if (!$) return;

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    /* ================= جعبه خطای فرم (همان قرارداد فاز E) ================= */
    function ensureErrorBox($form) {
        var $box = $form.find('.auth-form-errors');
        if (!$box.length) {
            $box = $('<div class="auth-form-errors is-empty" role="alert"></div>');
            $form.prepend($box);
        }
        return $box;
    }

    function showFormError($form, message) {
        var $box = ensureErrorBox($form);
        $box.removeClass('auth-form-success');
        $box.html('<i class="bi bi-exclamation-circle-fill"></i><span></span>');
        $box.find('span').text(message);
        $box.removeClass('is-empty');
    }

    function clearFormError($form) {
        $form.find('.auth-form-errors').addClass('is-empty').removeClass('auth-form-success');
    }

    /* ================= خانه‌های OTP (کپی اثبات‌شدهٔ فاز E) ================= */
    function initOtpBoxes($scope) {
        var $boxesWrap = $scope.find('#otp-input');
        if (!$boxesWrap.length) return;

        var $inputs = $boxesWrap.find('input').not('[type="hidden"]');
        var $target = $boxesWrap.find('input#otp-value');

        function syncTarget(autoCheck) {
            var code = '';
            var complete = true;
            $inputs.each(function () {
                var v = $(this).val() || '';
                code += v;
                $(this).toggleClass('is-filled', v !== '');
                if (v === '') complete = false;
            });
            if ($target.length) $target.val(code);

            if (complete && autoCheck) {
                var $form = $boxesWrap.closest('form');
                if ($form.length && typeof $form.attr('data-otp-autosubmit') !== 'undefined' && !$form.data('submitting')) {
                    $form.trigger('submit');
                }
            }
        }

        $inputs.on('input', function () {
            var $this = $(this);
            var val = $this.val();
            val = String(val).replace(/[^\d]/g, '');
            if (val.length > 1) {
                var idx = $inputs.index($this);
                $inputs.each(function (i) {
                    if (i >= idx && val.length > (i - idx)) {
                        $(this).val(val.charAt(i - idx));
                    }
                });
            } else {
                $this.val(val);
                if (val !== '' && $this.next('input').not('[type="hidden"]').length) {
                    $this.next('input').not('[type="hidden"]').trigger('focus');
                }
            }
            syncTarget(true);
        });

        $inputs.on('keydown', function (e) {
            if (e.key && e.key.length === 1 && /\d/.test(e.key) && $(this).val() !== '') {
                $(this).val('');
            }
            if (e.key === 'Backspace' && $(this).val() === '') {
                var $prev = $(this).prev('input').not('[type="hidden"]');
                if ($prev.length) {
                    $prev.trigger('focus');
                    e.preventDefault();
                }
            }
            if (e.key === 'ArrowLeft') {
                var $n = $(this).next('input').not('[type="hidden"]');
                if ($n.length) $n.trigger('focus');
            }
            if (e.key === 'ArrowRight') {
                var $p = $(this).prev('input').not('[type="hidden"]');
                if ($p.length) $p.trigger('focus');
            }
        });

        $inputs.on('paste', function (e) {
            e.preventDefault();
            var text = (e.originalEvent.clipboardData || window.clipboardData).getData('text') || '';
            var digits = String(text).replace(/[^\d]/g, '').slice(0, $inputs.length);
            $inputs.each(function (i) {
                $(this).val(digits.charAt(i) || '');
            });
            var focusIdx = Math.min(digits.length, $inputs.length - 1);
            $inputs.eq(focusIdx).trigger('focus');
            syncTarget(true);
        });

        syncTarget(false);
    }

    /* ================= شمارش معکوس + ارسال مجدد (POST) ================= */
    function initCountdown($scope) {
        var $wrap = $scope.find('.countDownContainer');
        if (!$wrap.length) return;

        var $bar = $wrap.find('#countdownB');
        var resendTs = parseInt($bar.data('resend'), 10);
        var total = parseInt($bar.data('total'), 10) || 120;
        var action = $bar.data('action') || '';
        if (!resendTs) return;

        var $text = $wrap.find('.countdown-text');
        if (!$text.length) {
            $text = $('<p class="countdown-text mb-0"></p>').appendTo($wrap);
        }
        var $fill = $bar.children('div').first();

        function fmt(sec) {
            var m = Math.floor(sec / 60);
            var s = sec % 60;
            return (m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s);
        }

        function bindResend() {
            $wrap.find('.resend-link').on('click', function (e) {
                e.preventDefault();
                var $link = $(this);
                if ($link.data('busy')) return;
                $link.data('busy', true);
                var mobile = $bar.closest('form').find('input[name="mobile"]').val() || '';
                $.ajax({
                    url: action,
                    type: 'POST',
                    data: { mobile: mobile, _token: csrfToken() },
                    success: function (data) {
                        if (data && data.status === 'success') {
                            // دو ثانیه مهلت سرور برای ارسال مجدد (رفتار میزبان)
                            resendTs = Math.ceil(Date.now() / 1000) + 2;
                            total = 120;
                            $bar.show();
                            $text.show();
                            $link.remove();
                            startTimer();
                        } else if (data && data.status === 'errorTime') {
                            $link.text('لطفا کمی بعد دوباره تلاش کنید.');
                            setTimeout(function () { $link.data('busy', false); }, 4000);
                        } else if (data && data.redirect) {
                            window.location.href = data.redirect;
                        }
                    },
                    error: function () {
                        $link.text('خطا در ارسال مجدد؛ دوباره تلاش کنید.');
                        $link.data('busy', false);
                    }
                });
            });
        }

        var timer = null;
        function startTimer() {
            if (timer) clearInterval(timer);
            timer = setInterval(function () {
                var remain = Math.max(0, Math.round(resendTs - Date.now() / 1000));
                var ratio = Math.min(1, remain / total);
                $fill.css('width', (ratio * 100) + '%');

                if (remain <= 0) {
                    clearInterval(timer);
                    $bar.hide();
                    $text.hide();
                    if (action) {
                        $wrap.append(
                            '<a href="#" class="resend-link"><i class="bi bi-arrow-repeat"></i> ارسال مجدد کد تایید</a>'
                        );
                        bindResend();
                    }
                } else {
                    $text.html('ارسال مجدد کد تا <b>' + fmt(remain) + '</b>');
                }
            }, 1000);
        }

        startTimer();
    }

    /* ================= اعتبارسنجی سادهٔ فیلدهای الزامی ================= */
    function validateRequired($form) {
        var firstEmpty = null;
        $form.find('input[required], select[required], textarea[required]').each(function () {
            var $f = $(this);
            // فیلدهای داخل بخش مخفی (حقوقی وقتی حقیقی انتخاب شده) بررسی نمی‌شوند
            if ($f.closest('.company-fields-off').length) return;
            if (!$f.val() || !String($f.val()).trim()) {
                $f.addClass('is-invalid');
                if (!firstEmpty) firstEmpty = $f;
            } else {
                $f.removeClass('is-invalid');
            }
        });
        if (firstEmpty) {
            showFormError($form, 'لطفاً فیلدهای الزامی را تکمیل کنید.');
            firstEmpty.trigger('focus');
            return false;
        }
        return true;
    }

    /* ================= فرم‌های AJAX — پاسخ JSON میزبان {status, redirect} ================= */
    function initAjaxForms($scope) {
        $scope.find('form[data-ajax]').each(function () {
            var $form = $(this);
            var submitLocked = false;

            $form.on('submit', function (e) {
                e.preventDefault();
                if (submitLocked) return;
                if (!validateRequired($form)) return;
                submitLocked = true;
                $form.data('submitting', true);

                var $btn = $form.find('button[type="submit"]');
                var btnHtml = $btn.html();
                $btn.prop('disabled', true).css('opacity', '.7');
                clearFormError($form);

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },

                    success: function (data) {
                        data = data || {};
                        if (data.status === 'error') {
                            showFormError($form, data.message || 'خطایی رخ داده است.');
                            if (data.redirect) {
                                setTimeout(function () { window.location.href = data.redirect; }, 1500);
                            }
                            $form.data('submitting', false);
                            return;
                        }
                        var redirect = data.redirect || $form.attr('data-redirect') || routeFallback();
                        window.location.href = redirect;
                    },

                    error: function (xhr) {
                        var msg = 'خطایی رخ داده است. لطفاً دوباره تلاش کنید.';
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            for (var field in errors) {
                                if (Object.prototype.hasOwnProperty.call(errors, field)) {
                                    msg = errors[field][0];
                                    break;
                                }
                            }
                        } else if (xhr.status === 429) {
                            msg = 'تعداد تلاش‌های شما زیاد بوده است. لطفاً کمی بعد دوباره تلاش کنید.';
                        } else if (xhr.status === 419) {
                            msg = 'نشست شما منقضی شده است. صفحه را دوباره بارگذاری کنید.';
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        showFormError($form, msg);
                        $form.data('submitting', false);
                    },

                    complete: function () {
                        submitLocked = false;
                        $btn.prop('disabled', false).css('opacity', '').html(btnHtml);
                    }
                });
            });
        });
    }

    function routeFallback() {
        var $fallback = $('#seller-auth-fallback-url');
        return $fallback.length ? $fallback.val() : '/';
    }

    /* ================= سوییچ حقیقی/حقوقی ================= */
    function initPrivateSwitch() {
        var $form = $('#seller-register-business-details');
        if (!$form.length) return;

        $form.find('input[name="private_business"]').on('change', function () {
            var val = $form.find('input[name="private_business"]:checked').val();
            $form.find('.seller-radio-card[data-private-target]').removeClass('is-selected');
            $form.find('.seller-radio-card[data-private-target="' + val + '"]').addClass('is-selected');
            if (val === 'private') {
                $('#private-fields').removeClass('d-none');
                $('#business-fields').addClass('company-fields-off');
            } else {
                $('#private-fields').addClass('d-none');
                $('#business-fields').removeClass('company-fields-off');
            }
        });
    }

    /* ================= سوییچ مالیات بر ارزش افزوده ================= */
    function initVatSwitch() {
        var $form = $('#seller-register-documents');
        if (!$form.length) return;

        $form.find('input[name="vat_free"]').on('change', function () {
            var val = $form.find('input[name="vat_free"]:checked').val();
            $form.find('.seller-radio-card[data-vat]').removeClass('is-selected');
            $form.find('.seller-radio-card[data-vat="' + (val === '1' ? 'yes' : 'no') + '"]').addClass('is-selected');
            if (val === '1') {
                $('#vat_free_yes').removeClass('d-none');
            } else {
                $('#vat_free_yes').addClass('d-none');
            }
        });
    }

    /* ================= استان → شهر ================= */
    function initCityCascade() {
        var $state = $('#state_id');
        if (!$state.length) return;
        var citiesUrl = $state.data('cities-url') || '/province/get-cities';

        $state.on('change', function () {
            var provinceId = $(this).val();
            var $city = $('#city_id');
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

    /* ================= آپلود فوری مدارک + ثبت نهایی ================= */
    function initDocuments() {
        var $form = $('#seller-register-documents');
        if (!$form.length) return;

        var uploadUrl = $form.attr('action');
        var deleteUrl = $form.data('delete-url');
        var checkUrl = $form.data('check-url');
        var uploaded = {}; // imageFor → filename

        // انتخاب فایل → آپلود فوری
        $form.find('.seller-upload-box').each(function () {
            var $box = $(this);
            var imageFor = $box.data('image-for');
            var $input = $box.find('input[type="file"]');

            $input.on('change', function () {
                var file = this.files && this.files[0];
                if (!file) return;

                var fd = new FormData();
                fd.append('imageFor', imageFor);
                fd.append(imageFor, file);
                fd.append('_token', csrfToken());

                $box.removeClass('has-file');
                $box.find('.upload-hint').text('در حال بارگذاری...');

                $.ajax({
                    url: uploadUrl + '?imageFor=' + encodeURIComponent(imageFor),
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function () {
                        uploaded[imageFor] = file.name;
                        $box.addClass('has-file');
                        $box.find('.upload-hint').text('بارگذاری شد — برای تغییر دوباره کلیک کنید');
                        $box.find('.upload-filename').removeClass('d-none').text(file.name);
                        $box.find('.seller-upload-remove').removeClass('d-none');
                        if (file.type && file.type.indexOf('image') === 0) {
                            var reader = new FileReader();
                            reader.onload = function (e) {
                                $box.find('.upload-preview').removeClass('d-none').attr('src', e.target.result);
                            };
                            reader.readAsDataURL(file);
                        }
                    },
                    error: function (xhr) {
                        $box.find('.upload-hint').text('خطا در بارگذاری؛ دوباره تلاش کنید');
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            var msg = '';
                            for (var f in xhr.responseJSON.errors) { msg = xhr.responseJSON.errors[f][0]; break; }
                            showFormError($form, msg || 'فایل معتبر نیست.');
                        }
                    }
                });
            });

            // حذف تصویر
            $box.find('.seller-upload-remove').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $.ajax({
                    url: deleteUrl + '?imageFor=' + encodeURIComponent(imageFor),
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
                    data: { imageFor: imageFor },
                    success: function () {
                        delete uploaded[imageFor];
                        $box.removeClass('has-file');
                        $box.find('.upload-hint').text('برای بارگذاری کلیک کنید');
                        $box.find('.upload-filename').addClass('d-none').text('');
                        $box.find('.upload-preview').addClass('d-none').attr('src', '');
                        $box.find('.seller-upload-remove').addClass('d-none');
                        $input.val('');
                    }
                });
            });
        });

        // ثبت نهایی → documents-check
        $form.on('submit', function (e) {
            e.preventDefault();
            clearFormError($form);

            var vatFree = $form.find('input[name="vat_free"]:checked').val();

            // چک سمت کلاینت (میزبان هم چک می‌کند ولی پیام بهتر)
            if (vatFree === '1' && !uploaded['vat_image']) {
                showFormError($form, 'بارگذاری تصویر گواهی ارزش افزوده الزامی است.');
                return;
            }
            if (!uploaded['card_image'] || !uploaded['card_image_back']) {
                showFormError($form, 'بارگذاری تصویر رو و پشت کارت ملی الزامی است.');
                return;
            }

            var $btn = $form.find('button[type="submit"]');
            var btnHtml = $btn.html();
            $btn.prop('disabled', true).css('opacity', '.7');

            $.ajax({
                url: checkUrl,
                type: 'POST',
                data: { vat_free: vatFree, _token: csrfToken() },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (data) {
                    data = data || {};
                    if (data.status === 'success' && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        showFormError($form, data.message || 'خطایی رخ داده است.');
                        $btn.prop('disabled', false).css('opacity', '').html(btnHtml);
                    }
                },
                error: function (xhr) {
                    var msg = 'خطایی رخ داده است. لطفاً دوباره تلاش کنید.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    showFormError($form, msg);
                    $btn.prop('disabled', false).css('opacity', '').html(btnHtml);
                }
            });
        });
    }

    /* ================= راه‌اندازی ================= */
    $(function () {
        var $body = $('body');
        initOtpBoxes($body);
        initCountdown($body);
        initAjaxForms($body);
        initPrivateSwitch();
        initVatSwitch();
        initCityCascade();
        initDocuments();
    });
})();
