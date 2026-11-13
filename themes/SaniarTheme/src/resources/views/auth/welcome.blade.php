@extends('front::auth.layouts.master', ['title' => 'ورود به سایت'])

@php
    $redirect_url = request("redirect") ?: Redirect::intended()->getTargetUrl();
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
            <span class="account-head-line">به {{ option('info_site_title', 'او پی شاپ') }} خوش آمدید</span>
            <div class="content-account">
                <div class="account-box-message">
                    <div class="user-account-welcome">
                        <span class="mdi mdi-account-outline user-welcome"></span>
                    </div>
                    <div class="made-account">
                        <h2>حساب کاربری شما در {{ option('info_site_title', 'او پی شاپ') }} ساخته شد</h2>
                        <p>اکنون می‌توانید به صفحه‌ای که در آن بودید بازگردید و یا با تکمیل اطلاعات حساب کاربری
                            خود به کلیه امکانات و
                            سرویس‌های {{ option('info_site_title', 'او پی شاپ') }} و سرویس‌های وابسته به آن دسترسی داشته باشید</p>
                    </div>
                </div>
                <ul>
                    <li>
                        <a href="{{route('front.user.profile.edit')}}" class="parent-btn">
                            <button class="dk-btn dk-btn-info">
                                تکمیل حساب کاربری
                                <i class="fa fa-user sign-in"></i>
                            </button>
                        </a>
                    </li>
                    <li>
                        <a href="/" class="back-page-before">بازگشت به صفحه‌ای اصلی</a>
                    </li>
                </ul>
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
