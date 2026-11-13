@extends('front::auth.layouts.master', ['title' => 'تغییر رمز عبور'])

@php
    $redirect_url = Redirect::intended()->getTargetUrl();
    $type = request()->type;
    $back_url = $type == 'login' ? route('login-with-code.request') : route('password.request');
    $action = $type == 'login' ? route('login-with-code.confirm') : route('password.change-password');
@endphp
@push('styles')
    <style>
        .form-content{
            text-align: right;
        }
        .form-content input::placeholder{
            text-align: right;
            font-size: 13px;
        }
        .dk-btn-info{
            text-align: center;
        }
        .form-control{
            border-radius: 5px !important;
        }
        .alert-danger{
            display: flex;
        }
    </style>
@endpush
@section('content')
    <div class="col-lg-4 col-md-6 col-xs-12 mx-auto">
        <div class="account-box">
            <a href="/" class="logo-account">
                <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}">
            </a>
            <div class="message-light">
                <div class="massege-light">
                    <h5 class="text-center mb-3" style="font-weight: 700; color: #2c3e50;">
                        <i class="mdi mdi-lock-reset"></i> تغییر رمز عبور
                    </h5>
                    <div class="alert alert-info text-center py-2" style="background-color: #e8f4fd; border-color: #b8d4e8; font-size: 0.95rem;">
                        <i class="mdi mdi-cellphone-check"></i>
                        کد تایید به شماره همراه
                        <strong style="direction: ltr; display: inline-block;">{{ $user->username }}</strong>
                        ارسال گردید
                        <br>
                        <span style="font-size: 0.9rem;">برای تغییر رمز عبور، کد تایید و رمز جدید را وارد کنید</span>
                    </div>
                    <div class="text-center mb-3">
                        <a href="{{ $back_url }}" class="form-edit-number" style="font-size: 0.9rem;">
                            <i class="mdi mdi-pencil"></i> ویرایش شماره
                        </a>
                    </div>
                </div>

                <!-- نمایش خطاهای اعتبارسنجی -->
                @if ($errors->any())
                    <div class="alert alert-danger form-errors" style="margin-bottom: 15px; padding: 10px; border-radius: 5px;">
                        <ul style="margin: 0; padding-right: 20px; list-style: none;">
                            @foreach ($errors->all() as $error)
                                <li><i class="mdi mdi-alert-circle"></i> {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif


                <form id="one-time-login-form" action="{{ route('password.change-password') }}" method="POST">
                    @csrf
                    <input name="mobile" type="hidden" value="{{ $user->username }}">

                    <!-- فیلد کد تایید -->
                    <div class="form-row">
                        <div class="numbers-verify form-content form-content1 w-100">
                            <label for="verify_code" class="form-label" style="font-weight: 600; color: #34495e;">
                                <i class="mdi mdi-shield-key"></i> کد تایید
                            </label>
                            <input
                                name="verify_code"
                                class="activation-code-input form-control"
                                placeholder="کد تایید ۶ رقمی را وارد کنید"
                                id="verify_code"
                                maxlength="6"
                                style="text-align: center; font-size: 1.2rem; letter-spacing: 8px; direction: ltr;"
                            >
                        </div>
                    </div>

                    <!-- تایمر و دکمه دریافت مجدد -->
                    <div class="form-row mt-4">
                        <span class="form-account-row">دریافت مجدد کد تایید</span> (<p data-action="{{ $back_url }}" id="countdown-verify-end"></p>)
                    </div>

                    <!-- فیلد رمز عبور جدید -->
                    <div class="form-row mt-3">
                        <div class="form-content w-100">
                            <label for="password" class="form-label" style="font-weight: 600; color: #34495e;">
                                <i class="mdi mdi-lock"></i> رمز عبور جدید
                            </label>
                            <div class="input-group" style="position: relative;">
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control password-field"
                                    placeholder="حداقل ۸ کاراکتر (حروف و اعداد)"
                                    id="password"
                                    style="border-left: 0; padding-left: 45px;"
                                    minlength="8"
                                    required
                                >
                                <span
                                    class="input-group-text toggle-password"
                                    style="cursor: pointer; background: transparent; border-left: 0; position: absolute; left: 0; top: 0; height: 100%; z-index: 10; border: 1px solid #ced4da; border-right: 0; border-radius: 0 5px 5px 0;"
                                    onclick="togglePasswordVisibility('password')"
                                >
                                    <i class="mdi mdi-eye-off" id="password-icon"></i>
                                </span>
                            </div>
                            <div class="password-strength mt-2" id="password-strength" style="display: none;">
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar" id="strength-bar" role="progressbar" style="width: 0%;"></div>
                                </div>
                                <small class="text-muted" id="strength-text"></small>
                            </div>
                            <small class="text-muted"   >
                                <i class="mdi mdi-information-outline"></i>
                                رمز عبور باید حداقل ۸ کاراکتر شامل حروف و اعداد باشد
                            </small>
                        </div>
                    </div>


                    <!-- فیلد تکرار رمز عبور -->
                    <div class="form-row mt-3">
                        <div class="form-content w-100">
                            <label for="password_confirmation" class="form-label" style="font-weight: 600; color: #34495e;">
                                <i class="mdi mdi-lock-check"></i> تکرار رمز عبور
                            </label>
                            <div class="input-group" style="position: relative;">
                                <input
                                    type="password"
                                    name="password_confirmation"
                                    class="form-control password-field"
                                    placeholder="رمز عبور را مجدداً وارد کنید"
                                    id="password_confirmation"
                                    style="border-left: 0; padding-left: 45px;"
                                    required
                                >
                                <span
                                    class="input-group-text toggle-password"
                                    style="cursor: pointer; background: transparent; border-left: 0; position: absolute; left: 0; top: 0; height: 100%; z-index: 10; border: 1px solid #ced4da; border-right: 0; border-radius: 0 5px 5px 0;"
                                    onclick="togglePasswordVisibility('password_confirmation')"
                                >
                                    <i class="mdi mdi-eye-off" id="password_confirmation-icon"></i>
                                </span>
                            </div>
                            <div id="password-match" style="font-size: 0.85rem; margin-top: 5px;"></div>
                        </div>
                    </div>

                    <div class="parent-btn mt-1">
                        <button class="dk-btn dk-btn-info" type="submit" style="width: 100%; padding: 12px; font-size: 1.05rem;">
                            <i class="mdi mdi-lock-reset"></i>
                            تغییر رمز عبور
                            <i class="mdi mdi-arrow-left"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        var redirect_url = '{{ $redirect_url }}';
        var resend_time = {{ $resend_time }};

        // نمایش/مخفی کردن رمز عبور
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');

            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'mdi mdi-eye';
            } else {
                field.type = 'password';
                icon.className = 'mdi mdi-eye-off';
            }
        }

        // بررسی قدرت رمز عبور
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            const strengthDiv = document.getElementById('password-strength');

            if (password.length === 0) {
                strengthDiv.style.display = 'none';
                return;
            }

            strengthDiv.style.display = 'block';

            let strength = 0;
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;

            const percentage = (strength / 5) * 100;
            strengthBar.style.width = percentage + '%';

            let color, text;
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

        // بررسی تطابق رمز عبور
        document.getElementById('password_confirmation').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            const matchDiv = document.getElementById('password-match');

            if (confirm.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }

            if (password === confirm) {
                matchDiv.innerHTML = '<span style="color: #28a745;"><i class="mdi mdi-check-circle"></i> رمز عبور مطابقت دارد</span>';
            } else {
                matchDiv.innerHTML = '<span style="color: #dc3545;"><i class="mdi mdi-close-circle"></i> رمز عبور مطابقت ندارد</span>';
            }
        });

        // اعتبارسنجی کامل قبل از ارسال فرم
        document.getElementById('one-time-login-form').addEventListener('submit', function(e) {
            // جلوگیری از ارسال خودکار
            e.preventDefault();
            e.stopPropagation();

            const verifyCode = document.getElementById('verify_code').value.trim();
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirmation').value;

            let errors = [];

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
            let strength = 0;
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
            const existingError = document.querySelector('.form-errors');
            if (existingError) {
                existingError.remove();
            }

            // اگر خطایی وجود داشت، نمایش بده و فرم را ارسال نکن
            if (errors.length > 0) {
                // ایجاد المان نمایش خطا
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger form-errors';
                errorDiv.style.cssText = 'margin-bottom: 15px; padding: 10px; border-radius: 5px;';

                let errorHtml = '<ul style="margin: 0; padding-right: 20px; list-style: none;">';
                errors.forEach(error => {
                    errorHtml += `<li><i class="mdi mdi-alert-circle"></i> ${error}</li>`;
                });
                errorHtml += '</ul>';
                errorDiv.innerHTML = errorHtml;

                // قرار دادن خطاها قبل از فرم
                const form = document.getElementById('one-time-login-form');
                const messageLight = document.querySelector('.message-light');
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

        // حذف خطاها هنگام تایپ در فیلدها
        document.querySelectorAll('#verify_code, #password, #password_confirmation').forEach(input => {
            input.addEventListener('input', function() {
                const errorDiv = document.querySelector('.form-errors');
                if (errorDiv) {
                    errorDiv.remove();
                }
            });
        });

        // محدود کردن ورودی کد تایید به اعداد
        const verifyCodeInput = document.getElementById('verify_code');
        if (verifyCodeInput) {
            verifyCodeInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }

        // همچنین برای اطمینان از عدم ارسال فرم با کلید Enter
        document.querySelectorAll('#verify_code, #password, #password_confirmation').forEach(input => {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const form = document.getElementById('one-time-login-form');
                    if (form) {
                        form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                    }
                }
            });
        });

        // جلوگیری از ارسال فرم با دکمه submit در صورت وجود خطا
        document.querySelector('button[type="submit"]').addEventListener('click', function(e) {
            const form = document.getElementById('one-time-login-form');
            if (form) {
                form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                e.preventDefault();
            }
        });
    </script>

    <script src="{{ theme_asset('js/vendor/countdown.min.js') }}"></script>
    <script src="{{ theme_asset('js/pages/one-time-login.js?v=3') }}"></script>
@endpush
