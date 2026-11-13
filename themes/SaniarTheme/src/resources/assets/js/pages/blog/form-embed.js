/**
 * SaniarTheme — فرم ساندکد (forms/embed)
 * جابجایی فرم نسبت به توضیحات + تزریق CSS سفارشی فرم + ارسال AJAX
 * (جایگزین اسکریپت اینلاین بلید)
 */
(function () {
    'use strict';

    function initForm(root) {
        if (!root || root.dataset.embedReady === '1') { return; }
        root.dataset.embedReady = '1';

        var formId = root.dataset.formId;
        var formPosition = root.dataset.position || 'top';
        var formWrapper = document.getElementById('form-wrapper-' + formId);
        var formDescription = document.getElementById('form-description-' + formId);

        // ---------- تزریق CSS سفارشی فرم (داده پنل مدیریت) ----------
        var customCss = root.dataset.customCss || '';
        if (customCss) {
            var styleEl = document.createElement('style');
            styleEl.textContent = '.shortcode-form-' + formId + ' ' + customCss;
            document.head.appendChild(styleEl);
        }

        // ---------- تنظیم موقعیت فرم نسبت به توضیحات ----------
        if (formDescription && formWrapper) {
            if (formPosition === 'bottom') {
                // فرم باید زیر توضیحات قرار بگیرد
                formDescription.parentNode.insertBefore(formWrapper, formDescription.nextSibling);
            } else {
                // حالت top (پیش‌فرض) - فرم بالای توضیحات قرار می‌گیرد
                formDescription.parentNode.insertBefore(formWrapper, formDescription);
            }
        }

        // نمایش فرم بعد از جابجایی
        if (formWrapper) {
            formWrapper.style.display = 'block';
        }

        // ---------- ارسال فرم با AJAX ----------
        var form = document.getElementById('ajax-form-' + formId);

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var formData = new FormData(this);
                var submitBtn = this.querySelector('[type="submit"]');
                var originalText = submitBtn.innerHTML;
                var resultDiv = this.querySelector('.form-result');

                submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> در حال ارسال...';
                submitBtn.disabled = true;

                var csrfMeta = document.querySelector('meta[name="csrf-token"]');

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        if (!response.ok) {
                            return response.text().then(function (text) {
                                console.error('Server response:', text);
                                throw new Error('Server error: ' + response.status);
                            });
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        if (data.success) {
                            resultDiv.innerHTML = `
                            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                                <i class="bi bi-check-circle"></i> ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                            form.reset();

                            setTimeout(function () {
                                resultDiv.innerHTML = '';
                            }, 5000);
                        }
                    })
                    .catch(function (error) {
                        console.error('Error:', error);
                        resultDiv.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                            <i class="bi bi-exclamation-circle"></i> خطایی رخ داده است
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;

                        setTimeout(function () {
                            resultDiv.innerHTML = '';
                        }, 5000);
                    })
                    .finally(function () {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.shortcode-form').forEach(initForm);
    });
})();
