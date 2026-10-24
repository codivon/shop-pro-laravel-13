@extends('front::auth.layouts.master', ['title' => 'ورود به سایت'])

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">
                @if(session('SettFirstOnePassword'))
                    رمز عبور جدید
                @else
                    رمز عبور
                @endif
            </h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>
        <form action="{{ route('front.user.CheckPassword') }}" id="form-auth" method="post">
            @csrf
            <input name="username" value="{{ session('ShowPasswordForm_Mobile_Email') }}" type="hidden">

            <div class="comment-item position-relative mb-3">
                <input type="password" class="form-control" id="passwd" name="password"
                       placeholder=" " value="{{ old('password') }}" autofocus required
                       aria-label="رمز عبور">
                <label for="passwd" class="form-label label-float">رمز عبور خود را وارد کنید</label>

                @if(!session('SettFirstOnePassword'))
                    @if (option('login_with_code', 'on') == 'on')
                        <div class="inline-btn-text">
                            <a href="{{ route('login-with-code.request') }}" class="btn border-0 main-color-one-bg text-white font-12">ورود با پیامک</a>
                        </div>
                    @endif
                @endif
            </div>
            <input name="remember" type="hidden" value="1">

            @error('password')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
            @if(session('admin_login_error'))
                <span class="invalid-feedback" role="alert"><strong>{{ session('admin_login_error') }}</strong></span>
            @endif

            @if(!session('SettFirstOnePassword'))
                @if (option('forgot_password_link', 'on') == 'on')
                    <div class="text-start mb-2">
                        <a href="{{ route('password.request') }}" class="font-12 main-color-one-color text-decoration-none">
                            رمز خود را فراموش کرده‌ام
                        </a>
                    </div>
                @endif
            @endif

            @if(session('SettFirstOnePassword') and option('user_referrals_enable', "false") == "true")
                <div class="comment-item mb-3">
                    <input type="text" class="form-control" id="referralCode" name="referralCode"
                           placeholder=" " value="{{ @request('ref') }}" aria-label="کد معرف">
                    <label for="referralCode" class="form-label label-float">کد معرف را وارد کنید (اختیاری)</label>
                </div>
                @error('referralCode')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            @endif

            <div class="form-group step-two">
                <button type="submit" class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    تایید
                </button>
            </div>
        </form>
    </div>
@endsection
