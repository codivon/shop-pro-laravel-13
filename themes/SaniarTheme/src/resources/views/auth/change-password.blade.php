@extends('front::auth.layouts.master', ['title' => 'تغییر رمز عبور'])

@php
    $redirect_url = Redirect::intended()->getTargetUrl();
    $type = request()->type;
    $back_url = $type == 'login' ? route('login-with-code.request') : route('password.request');
    $action = $type == 'login' ? route('login-with-code.confirm') : route('password.change-password');
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-4">

            {{-- کارت تغییر رمز عبور با کد تایید — ساختار sending-code.html قالب سانیار --}}
            <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
                <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
                    <h2 class="text-center h4 text-muted title-font"><i class="bi bi-shield-lock me-1"></i> تغییر رمز عبور</h2>
                    <a href="{{ route('front.index') }}">
                        <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="120" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                    </a>
                </div>

                <div class="alert text-center alert-success font-13">
                    <i class="bi bi-phone"></i>
                    کد تایید به شماره همراه
                    <strong style="direction: ltr; display: inline-block;">{{ $user->username }}</strong>
                    ارسال گردید
                    <br>
                    <span class="font-12">برای تغییر رمز عبور، کد تایید و رمز جدید را وارد کنید</span>
                    <br>
                    <a href="{{ $back_url }}" class="form-edit-number">
                        <i class="bi bi-pencil"></i> ویرایش شماره
                    </a>
                </div>

                <!-- نمایش خطاهای اعتبارسنجی -->
                @if ($errors->any())
                    <div class="alert alert-danger form-errors">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li><i class="bi bi-exclamation-circle"></i> {{ $error }}</li>
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
                            <label for="verify_code" class="form-label fw-bold">
                                <i class="bi bi-key"></i> کد تایید
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
                    <div class="form-row mt-4 text-center">
                        <span class="font-13">دریافت مجدد کد تایید</span> (<p data-action="{{ $back_url }}" id="countdown-verify-end" class="d-inline"></p>)
                    </div>

                    <!-- فیلد رمز عبور جدید -->
                    <div class="form-row mt-3">
                        <div class="form-content w-100">
                            <label for="password" class="form-label fw-bold">
                                <i class="bi bi-lock"></i> رمز عبور جدید
                            </label>
                            <div class="position-relative">
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="حداقل ۸ کاراکتر (حروف و اعداد)"
                                    id="password"
                                    style="padding-left: 45px;"
                                    minlength="8"
                                    required
                                >
                                <span
                                    class="toggle-password"
                                    style="cursor: pointer; position: absolute; left: 10px; top: 8px; z-index: 10;"
                                    onclick="togglePasswordVisibility('password')"
                                >
                                    <i class="bi bi-eye-slash" id="password-icon"></i>
                                </span>
                            </div>
                            <div class="password-strength mt-2" id="password-strength" style="display: none;">
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar" id="strength-bar" role="progressbar" style="width: 0%;"></div>
                                </div>
                                <small class="text-muted" id="strength-text"></small>
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                رمز عبور باید حداقل ۸ کاراکتر شامل حروف و اعداد باشد
                            </small>
                        </div>
                    </div>


                    <!-- فیلد تکرار رمز عبور -->
                    <div class="form-row mt-3">
                        <div class="form-content w-100">
                            <label for="password_confirmation" class="form-label fw-bold">
                                <i class="bi bi-lock-fill"></i> تکرار رمز عبور
                            </label>
                            <div class="position-relative">
                                <input
                                    type="password"
                                    name="password_confirmation"
                                    class="form-control"
                                    placeholder="رمز عبور را مجدداً وارد کنید"
                                    id="password_confirmation"
                                    style="padding-left: 45px;"
                                    required
                                >
                                <span
                                    class="toggle-password"
                                    style="cursor: pointer; position: absolute; left: 10px; top: 8px; z-index: 10;"
                                    onclick="togglePasswordVisibility('password_confirmation')"
                                >
                                    <i class="bi bi-eye-slash" id="password_confirmation-icon"></i>
                                </span>
                            </div>
                            <div id="password-match" style="font-size: 0.85rem; margin-top: 5px;"></div>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <button class="btn-login w-100 btn main-color-one-bg text-white rounded-3 border-0 py-3" type="submit">
                            <i class="bi bi-shield-lock"></i>
                            تغییر رمز عبور
                            <i class="bi bi-arrow-left"></i>
                        </button>
                    </div>
                </form>

            </div>

            <p class="loginTermsDesc mt-3">با ورود و یا ثبت نام در {{ option('info_site_title', 'او پی شاپ') }} شما <a class="underlined main-color-one-color fw-bold" href="/rules/">شرایط و
                قوانین</a> استفاده از سرویس‌های سایت و <a class="underlined main-color-one-color fw-bold" href="/privacy-polices/">قوانین حریم
                خصوصی</a> آن را می‌پذیرید.</p>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        var redirect_url = '{{ $redirect_url }}';
        var resend_time = {{ $resend_time }};
    </script>

    <script src="{{ theme_asset('js/pages/auth/change-password.js') }}"></script>
    <script src="{{ theme_asset('js/vendor/countdown.min.js') }}"></script>
    <script src="{{ theme_asset('js/pages/one-time-login.js?v=3') }}"></script>
@endpush
