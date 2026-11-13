@extends('front::auth.layouts.master', ['title' => 'ورود به سایت'])

@php
    $redirect_url = request("redirect") ?: \Illuminate\Support\Facades\Redirect::intended()->getTargetUrl();
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
        <div class="account-box content-account">
            <a href="/" class="logo-account">
                <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}">

            </a>

            @if(session('SettFirstOnePassword'))
                <span class="account-head-line">رمز عبور جدید را وارد کنید</span>
            @else
                <span class="account-head-line">رمز عبور را وارد کنید</span>
            @endif
            <div class="content-account">
                <form action="{{ route('front.user.CheckPassword') }}" id="login" method="post">
                    @csrf
                  <input name="username" value="{{session('ShowPasswordForm_Mobile_Email')}}" type="hidden">
                    <input autofocus required name="password" type="password" id="email-phone" value="{{old('password')}}" class="input-email-account" placeholder="">
                    <input name="remember" type="hidden"  value="1" class="input-email-account" placeholder="">
                    @error('password')
                    <span class="invalid-feedback" role="alert" style="margin: 10px 0;">
                                        <strong>{{ $message }}</strong>
                                    </span>
                    @enderror
                    @if(session('admin_login_error'))
                        <span class="invalid-feedback" role="alert" style="margin: 10px 0;">
                                        <strong>{{ session('admin_login_error')  }}</strong>
                                    </span>
                        @endif
                    @if(!session('SettFirstOnePassword'))
                        @if (option('forgot_password_link', 'on') == 'on')
                        <a href="{{route('password.request')}}" class="account-link-password">رمز خود را فراموش کرده ام</a>
                        @endif
                            @if (option('login_with_code', 'on') == 'on')
                        <a href="{{route('login-with-code.request')}}" class="account-link-password">ورود با رمز یکبار مصرف</a>
                            @endif
                    @endif

                    @if(session('SettFirstOnePassword') and option('user_referrals_enable', "false") == "true")
                        <span class="account-head-line pt-4 pr-0">کد معرف را وارد کنید (اختیاری)</span>
                        <input name="referralCode" type="text"  value="{{@request('ref')}}" class="input-email-account" placeholder="">
                        @error('referralCode')
                        <span class="invalid-feedback" role="alert" style="margin: 10px 0;">
                                        <strong>{{ $message }}</strong>
                                    </span>
                        @enderror
                    @endif


                    <div class="parent-btn">
                        <button class="dk-btn dk-btn-info text-center mt-5">
                            تایید
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
    </script>

@endpush

@php session()->forget('ShowPasswordForm_Mobile_Email'); @endphp
@php session()->forget('admin_login_error'); @endphp
