/**
 * سانیار فاز K — فرم تماس با ما: ارسال AJAX + رفرش کپچا + توست
 * endpoint از data-store-url روی [data-saniar-contact-form]
 */
(function () {
    'use strict';

    var box = document.querySelector('[data-saniar-contact-form]');
    if (!box || box.dataset.saniarInit === '1') return;
    box.dataset.saniarInit = '1';

    var form = box.querySelector('form');
    if (!form) return;

    var storeUrl = box.dataset.storeUrl;
    var CSRF = document.querySelector('meta[name="csrf-token"]');
    var submitBtn = form.querySelector('.contact-submit-btn');

    function toast(message, type) {
        var el = document.createElement('div');
        el.className = 'saniar-toast alert alert-' + (type === 'error' ? 'danger' : 'success') +
            ' position-fixed shadow-box rounded-3 px-3 py-2 font-14';
        el.style.cssText = 'top:84px;left:18px;z-index:2000;max-width:340px;';
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 3600);
    }

    function refreshCaptcha() {
        var img = form.querySelector('img[src*="captcha"]');
        if (img) {
            img.src = img.src.split('?')[0] + '?_=' + Date.now() + Math.random().toString(36).slice(2, 6);
        }
        var captchaInput = form.querySelector('input[name="captcha"]');
        if (captchaInput) {
            captchaInput.value = '';
            captchaInput.focus();
        }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var data = {};
        new FormData(form).forEach(function (value, key) { data[key] = value; });

        // اعتبارسنجی سمت کلاینت
        var required = ['name', 'email', 'subject', 'message', 'captcha'];
        for (var i = 0; i < required.length; i++) {
            if (!(data[required[i]] || '').toString().trim()) {
                toast('لطفاً همهٔ فیلدهای ستاره‌دار را تکمیل کنید', 'error');
                var field = form.querySelector('[name="' + required[i] + '"]');
                if (field) field.focus();
                return;
            }
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm ms-1"></span> در حال ارسال ...';
        }

        $.ajax({
            url: storeUrl,
            type: 'POST',
            data: data,
            headers: function (xhr) {
                if (CSRF) xhr.setRequestHeader('X-CSRF-TOKEN', CSRF.content);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function () {
                toast('پیام شما با موفقیت ثبت شد؛ کارشناسان ما در اسرع وقت پاسخ می‌دهند', 'success');
                form.reset();
                refreshCaptcha();
            },
            error: function (xhr) {
                if (xhr.statusText === 'abort') return;
                var msg = 'ارسال پیام ناموفق بود';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors)[0][0];
                    else if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                }
                toast(msg, 'error');
                refreshCaptcha();
            },
            complete: function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-send me-1"></i>ثبت و ارسال پیام';
                }
            }
        });
    });
})();
