@extends('front::auth.layouts.master', ['title' => 'ورود به سایت'])

@php
    $redirect_url = request("redirect") ?: \Illuminate\Support\Facades\Redirect::intended()->getTargetUrl();
  $params = null;
        if (request('ref')) {
            $params = "?ref=" . request('ref');
        }
@endphp

@section('style')
    <style>
        .dk-btn:before{
            right: -97px;
        }
        #main .account-box .content-account {
            padding: 10px 30px 30px;
        }
    </style>
@endsection
@section('content')
    <div class="col-lg-4 col-md-6 col-xs-12 mx-auto">
        <div class="account-box">
            <a href="/" class="logo-account">
                <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}">

            </a>
            <span class="account-head-line">ورود | ثبت‌نام</span>
            <div class="content-account">
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

                    <label for="email-phone">سلام!<br>

                        لطفا شماره موبایل یا ایمیل خود را وارد کنید</label>
                    <input name="username" type="text" id="email-phone" value="{{old('username')}}" class="input-email-account" placeholder="" autofocus required>
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

                    <div class="parent-btn">
                        <button class="dk-btn dk-btn-info text-center mt-5">
                            ورود
                        </button>
                    </div>
                    <label class="mt-3">ورود شما به معنای پذیرش شرایط {{ option('info_site_title', 'او پی شاپ') }} وقوانین حریم‌خصوصیاست

                    </label>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        var redirect_url = '{{ $redirect_url }}';
    </script>

    <script src="{{ theme_asset('js/pages/login.js') }}"></script>
@endpush
