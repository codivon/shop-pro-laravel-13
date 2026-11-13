@extends('front::auth.layouts.master', ['title' => 'ورود به سایت'])

@php
    $redirect_url = request("redirect") ?: \Illuminate\Support\Facades\Redirect::intended()->getTargetUrl();
  $params = null;
        if (request('ref')) {
            $params = "?ref=" . request('ref');
        }
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-4">

            {{-- کارت ورود — ساختار register.html قالب سانیار --}}
            <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
                <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
                    <h2 class="text-center h4 text-muted title-font">ورود / ثبت‌نام</h2>
                    <a href="{{ route('front.index') }}">
                        <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                    </a>
                </div>

                <form action="{{ route('front.user.CheckMobileEmail').$params }}" id="login" method="post">
                    @csrf

                    {{-- فاز ۴۴ — تلهٔ زمان (زمان رندر فرم) --}}
                    <input type="hidden" name="rendered_at" value="{{ time() }}">

                    {{-- فاز ۴۴ — هانی‌پات: فیلد مخفی که فقط ربات‌ها پر می‌کنند --}}
                    @if(\App\Services\SecuritySettings::honeypotEnabled())
                        <div style="position:absolute !important;height:0 !important;width:0 !important;margin:0 !important;padding:0 !important;overflow:hidden;opacity:0;" aria-hidden="true" tabindex="-1">
                            <label for="website_url">وب‌سایت شما</label>
                            <input id="website_url" type="text" name="website_url" tabindex="-1" autocomplete="off">
                            <label for="confirm_email">تأیید ایمیل</label>
                            <input id="confirm_email" type="text" name="confirm_email" tabindex="-1" autocomplete="off">
                        </div>
                    @endif

                    {{-- ساختار register.html قالب سانیار: input سپس label شناور داخل comment-item --}}
                    <div class="comment-item mb-3">
                        <input name="username" type="text" id="email-phone" value="{{old('username')}}" class="form-control" placeholder=" " required>
                        <label for="email-phone" class="form-label label-float">شماره موبایل یا ایمیل خود را وارد کنید</label>
                    </div>
                    @error('username')
                    <span class="invalid-feedback" role="alert" style="margin: 10px 0;">
                                        <strong>{{ $message }}</strong>
                                    </span>
                    @enderror

                    {{-- فاز ۴۴ — کپچای ورود مشتری (در صورت فعال بودن از تنظیمات امنیتی) --}}
                    @if(\App\Services\SecuritySettings::captchaEnabled('customer_login'))
                        <div class="mt-3">
                            <x-captcha />
                        </div>
                        @error('captcha')
                        <span class="invalid-feedback" role="alert" style="margin: 10px 0;">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    @endif

                    <div class="form-group">
                        <button type="submit" class="main-color-one-bg py-3 btn w-100 rounded-3 border-0">
                            ورود / ثبت نام
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
    </script>

    <script src="{{ theme_asset('js/pages/login.js') }}"></script>
@endpush
