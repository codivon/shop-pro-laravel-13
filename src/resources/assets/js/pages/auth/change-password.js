/**
 * صفحه تغییر رمز عبور با کد تایید (auth/change-password)
 * انتقال‌یافته از بلید به فایل js (بدون <script> داخل ویو)
 */

// نمایش/مخفی کردن رمز عبور
function togglePasswordVisibility(fieldId) {
    var field = document.getElementById(fieldId);
    var icon = document.getElementById(fieldId + '-icon');

    if (!field || !icon) {
        return;
    }

    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye';
    } else {
        field.type = 'password';
        icon.className = 'bi bi-eye-slash';
    }
}

(function () {
    'use strict';

    var passwordInput = document.getElementById('password');
    var confirmInput = document.getElementById('password_confirmation');
    var verifyCodeInput = document.getElementById('verify_code');

    // بررسی قدرت رمز عبور
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            var password = this.value;
            var strengthBar = document.getElementById('strength-bar');
            var strengthText = document.getElementById('strength-text');
            var strengthDiv = document.getElementById('password-strength');

            if (password.length === 0) {
                strengthDiv.style.display = 'none';
                return;
            }

            strengthDiv.style.display = 'block';

            var strength = 0;
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;

            var percentage = (strength / 5) * 100;
            strengthBar.style.width = percentage + '%';

            var color, text;
            if (strength <= 2) {
                color = '#dc3545';
                text = 'ضعیف';
            } else if (strength <= 3) {
                color = '#ffc107';
                text = 'متوسط';
            } else if (strength <= 4) {
                color = '#17a2b8';
                text = 'خوب';
            } else {
                color = '#28a745';
                text = 'قوی';
            }

            strengthBar.style.backgroundColor = color;
            strengthText.textContent = 'قدرت رمز: ' + text;
            strengthText.style.color = color;
        });
    }

    // بررسی تطابق رمز عبور
    if (confirmInput) {
        confirmInput.addEventListener('input', function () {
            var password = document.getElementById('password').value;
            var confirm = this.value;
            var matchDiv = document.getElementById('password-match');

            if (confirm.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }

            if (password === confirm) {
                matchDiv.innerHTML = '<span style="color: #28a745;"><i class="bi bi-check-circle"></i> رمز عبور مطابقت دارد</span>';
            } else {
                matchDiv.innerHTML = '<span style="color: #dc3545;"><i class="bi bi-x-circle"></i> رمز عبور مطابقت ندارد</span>';
            }
        });
    }

    // اعتبارسنجی کامل قبل از ارسال فرم
    var form = document.getElementById('one-time-login-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            // جلوگیری از ارسال خودکار
            e.preventDefault();
            e.stopPropagation();

            var verifyCode = document.getElementById('verify_code').value.trim();
            var password = document.getElementById('password').value;
            var confirm = document.getElementById('password_confirmation').value;

            var errors = [];

            // 1. بررسی کد تایید
            if (verifyCode.length === 0) {
                errors.push('لطفاً کد تایید را وارد کنید');
            } else if (!/^\d{5}$/.test(verifyCode)) {
                errors.push('کد تایید باید 5 رقم باشد');
            }

            // 2. بررسی رمز عبور
            if (password.length === 0) {
                errors.push('لطفاً رمز عبور جدید را وارد کنید');
            } else if (password.length < 8) {
                errors.push('رمز عبور باید حداقل ۸ کاراکتر باشد');
            } else if (!/(?=.*[a-zA-Z])(?=.*\d)/.test(password)) {
                errors.push('رمز عبور باید شامل حروف و اعداد باشد');
            }

            // 3. بررسی قدرت رمز عبور (حداقل متوسط)
            var strength = 0;
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;

            if (password.length > 0 && strength < 3) {
                errors.push('رمز عبور باید حداقل در سطح "متوسط" باشد (شامل حروف بزرگ، کوچک و اعداد)');
            }

            // 4. بررسی تطابق رمز عبور
            if (confirm.length === 0) {
                errors.push('لطفاً تکرار رمز عبور را وارد کنید');
            } else if (password !== confirm) {
                errors.push('رمز عبور و تکرار آن مطابقت ندارند');
            }

            // حذف خطاهای قبلی
            var existingError = document.querySelector('.form-errors');
            if (existingError) {
                existingError.remove();
            }

            // اگر خطایی وجود داشت، نمایش بده و فرم را ارسال نکن
            if (errors.length > 0) {
                // ایجاد المان نمایش خطا
                var errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger form-errors';
                errorDiv.style.cssText = 'margin-bottom: 15px; padding: 10px; border-radius: 5px;';

                var errorHtml = '<ul style="margin: 0; padding-right: 20px; list-style: none;">';
                errors.forEach(function (error) {
                    errorHtml += '<li><i class="bi bi-exclamation-circle"></i> ' + error + '</li>';
                });
                errorHtml += '</ul>';
                errorDiv.innerHTML = errorHtml;

                // قرار دادن خطاها قبل از فرم
                var messageLight = document.querySelector('.message-light');
                if (messageLight) {
                    messageLight.insertBefore(errorDiv, form);
                } else {
                    form.parentNode.insertBefore(errorDiv, form);
                }

                // اسکرول به بالای صفحه
                window.scrollTo({ top: 0, behavior: 'smooth' });

                return false;
            }

            // اگر همه چیز معتبر بود، فرم را ارسال کن
            this.submit();
        }, true); // استفاده از true برای اطمینان از اجرا
    }

    // حذف خطاها هنگام تایپ در فیلدها
    document.querySelectorAll('#verify_code, #password, #password_confirmation').forEach(function (input) {
        input.addEventListener('input', function () {
            var errorDiv = document.querySelector('.form-errors');
            if (errorDiv) {
                errorDiv.remove();
            }
        });
    });

    // محدود کردن ورودی کد تایید به اعداد
    if (verifyCodeInput) {
        verifyCodeInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // همچنین برای اطمینان از عدم ارسال فرم با کلید Enter
    document.querySelectorAll('#verify_code, #password, #password_confirmation').forEach(function (input) {
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var theForm = document.getElementById('one-time-login-form');
                if (theForm) {
                    theForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                }
            }
        });
    });

    // جلوگیری از ارسال فرم با دکمه submit در صورت وجود خطا
    var submitBtn = document.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.addEventListener('click', function (e) {
            var theForm = document.getElementById('one-time-login-form');
            if (theForm) {
                theForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                e.preventDefault();
            }
        });
    }
})();
