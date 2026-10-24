@extends('front::auth.layouts.master', ['title' => 'خوش آمدید'])

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">حساب کاربری ساخته شد</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <div class="auth-welcome">
            <div class="auth-welcome-icon" aria-hidden="true">
                <i class="bi bi-check2"></i>
            </div>

            <h3>به {{ option('info_site_title', config('front.demo.name')) }} خوش آمدید!</h3>
            <p>
                حساب کاربری شما با شماره
                <span class="auth-number-chip">{{ $user?->username ?? session('UserWelcome') }}</span>
                با موفقیت ساخته شد.
                <br>
                حالا می‌توانید از همه امکانات فروشگاه از جمله خرید، کیف پول و پیگیری سفارش‌ها استفاده کنید.
            </p>

            <div class="auth-welcome-actions">
                <a href="{{ route('front.user.profile.edit') }}"
                   class="btn btn-main-auth border-0 waves-effect waves-light">
                    <i class="bi bi-person-gear"></i> تکمیل اطلاعات حساب کاربری
                </a>
                <a href="{{ route('front.index') }}" class="btn btn-outline-main">
                    <i class="bi bi-shop"></i> بازگشت به فروشگاه
                </a>
            </div>
        </div>
    </div>
@endsection
