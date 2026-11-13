/**
 * SaniarTheme — صفحه نمایش فرم ساز (forms/show)
 * جابجایی فرم نسبت به توضیحات + اعتبارسنجی سمت کلاینت + تزریق CSS سفارشی فرم
 * (جایگزین اسکریپت اینلاین بلید)
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var wrapper = document.querySelector('.form-wrapper[id^="form-wrapper-"]');
        if (!wrapper) { return; }

        var formId = wrapper.dataset.formId;
        var formPosition = wrapper.dataset.position || 'top';
        var formWrapper = document.getElementById('form-wrapper-' + formId);
        var formDescription = document.getElementById('form-description-' + formId);

        // ---------- تزریق CSS سفارشی فرم (داده پنل مدیریت) ----------
        var customCss = wrapper.dataset.customCss || '';
        if (customCss) {
            var styleEl = document.createElement('style');
            styleEl.textContent = customCss;
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

        // ---------- اعتبارسنجی سمت کلاینت ----------
        var $ = window.jQuery;
        if (!$ || !$('#dynamic-form').length || !$.validator) { return; }

        var rules = {};
        var messages = {};

        var fieldsData = [];
        try {
            fieldsData = JSON.parse(wrapper.dataset.fields || '[]');
        } catch (e) {
            fieldsData = [];
        }

        fieldsData.forEach(function (field) {
            if (field.required) {
                rules[field.name] = { required: true };
                messages[field.name] = { required: 'وارد کردن ' + field.label + ' الزامی است' };
            }

            if (field.type === 'email') {
                if (rules[field.name]) {
                    rules[field.name].email = true;
                } else {
                    rules[field.name] = { email: true };
                }
                messages[field.name] = Object.assign({}, messages[field.name], {
                    email: 'لطفا یک ایمیل معتبر وارد کنید'
                });
            }

            if (field.type === 'tel') {
                if (rules[field.name]) {
                    rules[field.name].pattern = /^09[0-9]{9}$/;
                } else {
                    rules[field.name] = { pattern: /^09[0-9]{9}$/ };
                }
                messages[field.name] = Object.assign({}, messages[field.name], {
                    pattern: 'شماره موبایل باید با 09 شروع شود و 11 رقم باشد'
                });
            }

            if (field.rules_validation) {
                var validationRules = field.rules_validation.split('|');
                validationRules.forEach(function (rule) {
                    if (rule.indexOf('min:') === 0) {
                        var minVal = rule.split(':')[1];
                        if (rules[field.name]) {
                            rules[field.name].minlength = parseInt(minVal);
                        } else {
                            rules[field.name] = { minlength: parseInt(minVal) };
                        }
                        messages[field.name] = Object.assign({}, messages[field.name], {
                            minlength: 'حداقل باید ' + minVal + ' کاراکتر وارد شود'
                        });
                    }

                    if (rule.indexOf('max:') === 0) {
                        var maxVal = rule.split(':')[1];
                        if (rules[field.name]) {
                            rules[field.name].maxlength = parseInt(maxVal);
                        } else {
                            rules[field.name] = { maxlength: parseInt(maxVal) };
                        }
                        messages[field.name] = Object.assign({}, messages[field.name], {
                            maxlength: 'حداکثر ' + maxVal + ' کاراکتر مجاز است'
                        });
                    }

                    if (rule.indexOf('numeric') !== -1) {
                        if (rules[field.name]) {
                            rules[field.name].number = true;
                        } else {
                            rules[field.name] = { number: true };
                        }
                        messages[field.name] = Object.assign({}, messages[field.name], {
                            number: 'لطفا یک مقدار عددی وارد کنید'
                        });
                    }
                });
            }
        });

        $('#dynamic-form').validate({
            rules: rules,
            messages: messages,
            errorClass: 'error-message',
            errorElement: 'div',
            errorPlacement: function (error, element) {
                error.addClass('error-message');
                error.insertAfter(element);
                element.addClass('error');
            },
            success: function (label, element) {
                $(element).removeClass('error');
            },
            highlight: function (element) {
                $(element).addClass('error');
            },
            unhighlight: function (element) {
                $(element).removeClass('error');
            }
        });
    });
})();
