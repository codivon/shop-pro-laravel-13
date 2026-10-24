/* ============================================================
   صفحات احراز هویت — سانیار (فاز E)
   - خانه‌های OTP پنج‌رقمی (پرش خودکار، پِیست، جمع در فیلد مخفی)
   - نوار شمارش معکوس «ارسال مجدد» + لینک resend
   - فرم‌های AJAX (data-ajax) با مدیریت خطای 422/429
   - دکمه نمایش/مخفی رمز عبور
   - ارسال خودکار فرم پس از تکمیل OTP (در صورت data-otp-autosubmit)
   ============================================================ */
(function () {
    'use strict';

    var $ = window.jQuery;
    if (!$) return;

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    /* ================= جعبه خطای فرم ================= */
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

    /* ================= خانه‌های OTP ================= */
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
                // data-otp-autosubmit بدون مقدار است → جکویی data آن را رشتهٔ خالی می‌دهد؛ با attr چک می‌شود
                if ($form.length && typeof $form.attr('data-otp-autosubmit') !== 'undefined' && !$form.data('submitting')) {
                    $form.trigger('submit');
                }
            }
        }

        $inputs.on('input', function () {
            var $this = $(this);
            var val = $this.val();
            // فقط رقم فارسی/انگلیسی → عدد انگلیسی
            val = String(val).replace(/[^\d]/g, '');
            if (val.length > 1) {
                // پِیست چند رقمی: پخش در خانه‌ها
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
            // اگر خانه پر باشد و رقم جدید تایپ شود → جایگزینی (رفع بلاکِ maxlength)
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

    /* ================= شمارش معکوس ارسال مجدد ================= */
    function initCountdown($scope) {
        var $wrap = $scope.find('.countDownContainer');
        if (!$wrap.length) return;

        var $bar = $wrap.find('#countdownB');
        var resendTs = parseInt($bar.data('resend'), 10); // unix timestamp (ثانیه)
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

        var timer = setInterval(function () {
            var remain = Math.max(0, Math.round(resendTs - Date.now() / 1000));
            var ratio = Math.min(1, remain / total);
            $fill.css('width', (ratio * 100) + '%');

            if (remain <= 0) {
                clearInterval(timer);
                $bar.hide();
                $text.hide();
                if (action) {
                    $wrap.append(
                        '<a href="' + action + '" class="resend-link">' +
                        '<i class="bi bi-arrow-repeat"></i> ارسال مجدد کد تایید</a>'
                    );
                }
            } else {
                $text.html('ارسال مجدد کد تا <b>' + fmt(remain) + '</b>');
            }
        }, 1000);
    }

    /* ================= دکمه نمایش/مخفی رمز ================= */
    function initPasswordToggle($scope) {
        $scope.find('.toggle-password').on('click', function () {
            var $btn = $(this);
            var $input = $btn.closest('.comment-item').find('input');
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $btn.find('i').removeClass('bi-eye-slash').addClass('bi-eye');
            } else {
                $input.attr('type', 'password');
                $btn.find('i').removeClass('bi-eye').addClass('bi-eye-slash');
            }
        });
    }

    /* ================= فرم‌های AJAX ================= */
    function initAjaxForms($scope) {
        $scope.find('form[data-ajax]').each(function () {
            var $form = $(this);
            var submitLocked = false;

            $form.on('submit', function (e) {
                e.preventDefault();
                if (submitLocked) return;
                submitLocked = true;
                $form.data('submitting', true);

                var $btn = $form.find('button[type="submit"]');
                var btnHtml = $btn.html();
                $btn.prop('disabled', true).css('opacity', '.7');
                clearFormError($form);

                $.ajax({
                    url: $form.attr('action'),
                    type: $form.data('method') || 'POST',
                    data: $form.serialize(),
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },

                    success: function () {
                        // data-stay: بدون هدایت — نمایش پیام موفقیت روی همان صفحه (مثلاً تا ساخت مسیر مقصد در فاز بعد)
                        if (typeof $form.attr('data-stay') !== 'undefined') {
                            var $box = ensureErrorBox($form).removeClass('is-empty');
                            $box.addClass('auth-form-success');
                            $box.html('<i class="bi bi-check-circle-fill"></i><span></span>');
                            $box.find('span').text($form.attr('data-success-message') || 'با موفقیت انجام شد.');
                            $form[0].reset();
                            return;
                        }

                        var redirect = $form.attr('data-redirect');
                        if (redirect) {
                            var url = new URL(redirect, window.location.origin);
                            // data-append-mobile بدون مقدار است → با attr چک می‌شود نه data (falsy bug)
                            if (typeof $form.attr('data-append-mobile') !== 'undefined') {
                                var mobile = $form.find('input[name="mobile"]').val();
                                if (mobile) url.searchParams.set('mobile', mobile);
                            }
                            window.location.href = url.href;
                        } else if (typeof window.authRedirectUrl !== 'undefined') {
                            window.location.href = window.authRedirectUrl;
                        } else {
                            window.location.href = '/';
                        }
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
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        } else if (xhr.responseText && xhr.status !== 500) {
                            var text = xhr.responseText.trim();
                            if (text.length < 200) msg = text;
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

    /* ================= راه‌اندازی ================= */
    $(function () {
        var $body = $('body');
        initOtpBoxes($body);
        initCountdown($body);
        initPasswordToggle($body);
        initAjaxForms($body);
    });
})();
