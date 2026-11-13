/**
 * SaniarTheme — فاز ۸۳/۸۴: منطق مودال هشدار کاهش قیمت
 * منتقل‌شده از بلوک <script> داخل show.blade.php
 */
(function () {
    'use strict';

    var overlay = document.getElementById('dt_pa_overlay');
    if (!overlay) return;

    var modal     = overlay.querySelector('.dt-pa-modal');
    var body      = document.getElementById('dt_pa_body');
    var success   = document.getElementById('dt_pa_success');
    var errorBox  = document.getElementById('dt_pa_error');
    var submitBtn = document.getElementById('dt_pa_submit');
    var busy      = false;

    function openModal() {
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        // ریست به حالت اصلی برای باز شدن بعدی
        window.setTimeout(function () {
            body.hidden = false;
            success.hidden = true;
            errorBox.hidden = true;
        }, 250);
    }

    // دلیگیت روی document — بلوک اطلاعات محصول با AJAX جایگزین می‌شود و
    // بایندهای مستقیم می‌میرند (درس فاز ۸۱)؛ فاز ۸۴: همهٔ نمونه‌های دکمه
    // (اصلی + شناور + تزریق‌شده با سلکتور) با کلاس مشترک dt-pa-btn
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.dt-pa-btn');
        if (btn) {
            e.preventDefault();
            openModal();
        }
    });

    /* ===== فاز ۸۴ — تزریق دکمه در تگ دلخواه (سلکتور کلاس/آیدی) ===== */
    function dtPaInject() {
        var cfg = document.getElementById('dt_pa_inject_config');
        var src = document.getElementById('dt_price_alert_btn');
        if (!cfg || !src) return;

        // ایدمپوتنت: کلون‌های قبلی پاک می‌شوند (بعد از جایگزینی AJAX بلوک)
        Array.prototype.forEach.call(document.querySelectorAll('.dt-pa-btn--inject'), function (n) {
            if (n.parentNode) n.parentNode.removeChild(n);
        });

        var sels = [];
        try { sels = JSON.parse(cfg.getAttribute('data-selectors') || '[]') || []; } catch (err) { sels = []; }

        var injected = 0;
        for (var i = 0; i < sels.length; i++) {
            var target = null;
            try { target = document.querySelector(sels[i]); } catch (err) { target = null; }
            if (!target) continue;

            var clone = src.cloneNode(true);
            clone.removeAttribute('id');
            clone.removeAttribute('hidden');
            clone.classList.add('dt-pa-btn--inject');
            target.insertAdjacentElement('afterend', clone);
            injected++;
        }

        if (injected > 0) {
            if (src.parentNode) src.parentNode.removeChild(src);
        } else {
            // هیچ سلکتوری منطبق نشد → برگشت امن: زیر دکمهٔ خرید
            src.removeAttribute('hidden');
            src.classList.remove('dt-pa-btn--inject');
            var cart = document.querySelector('.product-info-block .btn-add-to-cart') || document.querySelector('.btn-add-to-cart');
            if (cart && cart.parentNode) {
                cart.insertAdjacentElement('afterend', src);
            }
        }
    }

    window.dtPriceAlertInject = dtPaInject;
    dtPaInject();

    // بعد از جایگزینی AJAX بلوک product-info (تغییر تنوع)، منبع دکمه تازه
    // از سرور می‌آید؛ تزریق دوباره انجام شود
    if (window.jQuery) {
        window.jQuery(document).ajaxSuccess(function () {
            if (document.getElementById('dt_pa_inject_config')) {
                window.setTimeout(dtPaInject, 60);
            }
        });
    }

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay || e.target.closest('.dt-pa-close')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
            closeModal();
        }
    });

    // انتخاب/لغو کانال‌ها
    modal.addEventListener('click', function (e) {
        var card = e.target.closest('.dt-pa-channel:not(.is-locked)');
        if (!card) return;
        e.preventDefault();
        card.classList.toggle('is-on');
        card.setAttribute('aria-pressed', card.classList.contains('is-on') ? 'true' : 'false');
    });

    function selectedChannels() {
        return Array.prototype.slice.call(modal.querySelectorAll('.dt-pa-channel.is-on'))
            .map(function (card) { return card.getAttribute('data-channel'); });
    }

    function showError(message) {
        errorBox.textContent = message;
        errorBox.hidden = false;
    }

    submitBtn.addEventListener('click', function () {
        if (busy) return;

        var channels = selectedChannels();

        if (!channels.length) {
            showError('حداقل یکی از راه‌های اطلاع‌رسانی (پیامک یا اعلان پروفایل) را انتخاب کنید.');
            return;
        }

        var isUser   = modal.getAttribute('data-user') === '1';
        var nameEl   = document.getElementById('dt_pa_name');
        var mobileEl = document.getElementById('dt_pa_mobile');
        var name     = nameEl ? nameEl.value.trim() : '';
        var mobile   = mobileEl ? mobileEl.value.trim() : '';

        if (!isUser && !/^09\d{9}$/.test(mobile)) {
            showError('شماره موبایل معتبر وارد کنید (مثل 09123456789).');
            return;
        }

        var fd = new FormData();
        fd.append('product_id', modal.getAttribute('data-product'));
        channels.forEach(function (c) { fd.append('channels[]', c); });
        if (mobile) fd.append('mobile', mobile);
        if (name) fd.append('name', name);

        busy = true;
        submitBtn.classList.add('is-busy');
        errorBox.hidden = true;

        var csrf = document.querySelector('meta[name="csrf-token"]');

        fetch(modal.getAttribute('data-action'), {
            method: 'POST',
            body: fd,
            headers: {
                'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(function (res) {
            return res.json().catch(function () {
                return { status: res.ok ? 'ok' : 'error', message: 'پاسخ نامعتبر از سرور؛ دوباره تلاش کنید.' };
            });
        }).then(function (res) {
            if (res && res.status === 'ok') {
                body.hidden = true;
                success.hidden = false;
                if (window.jQuery && window.showCustomToast) {
                    showCustomToast('هشدار کاهش قیمت برای این محصول فعال شد.', 'success');
                }
                window.setTimeout(closeModal, 2400);
            } else {
                showError((res && res.message) || 'ثبت هشدار انجام نشد؛ دوباره تلاش کنید.');
            }
        }).catch(function () {
            showError('ارتباط با سرور برقرار نشد؛ اتصال اینترنت را بررسی کنید.');
        }).then(function () {
            busy = false;
            submitBtn.classList.remove('is-busy');
        });
    });
})();
