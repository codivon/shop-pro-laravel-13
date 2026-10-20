@extends('vendor.installer.layouts.master')

@section('template_title', 'اتمام نصب')

@section('title', 'نصب کامل شد!')
@section('title_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
@endsection
@section('title_desc', 'فروشگاه شما آماده استفاده است')

@section('container')
    @if (isset(session('message')['status']) && session('message')['status'] == 'error')
        <div class="ap-alert ap-alert-error">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
            <span>در مرحله دیتابیس خطایی رخ داده است — به بخش زیر توجه کنید.</span>
        </div>

        @if(session('message')['dbOutputLog'])
            <div class="ap-log">{{ session('message')['dbOutputLog'] }}</div>
        @endif

        <div class="ap-log" style="margin-top:1rem">{{ $finalMessages ?? '' }}</div>
    @else
        <div class="ap-alert ap-alert-ok">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            <span><strong>تبریک!</strong> نصب فروشگاه‌ساز با موفقیت انجام شد. حالا می‌توانید وارد پنل مدیریت شوید و فروشگاه خود را تنظیم کنید.</span>
        </div>

        <ul class="ap-list" style="margin-bottom:1.4rem">
            <li><span>ورود به پنل مدیریت</span><a href="{{ url('/admin') }}" dir="ltr" style="color:var(--brand-strong);font-weight:700;text-decoration:none">{{ url('/admin') }}</a></li>
        </ul>

        <p class="ap-muted">توصیه امنیتی: برای بار اول با نام کاربری/ایمیل مدیر و رمزی که در مرحله راه‌اندازی ثبت کرده‌اید وارد شوید و پس از ورود، رمز عبور را تغییر دهید.</p>
    @endif

    <div class="ap-actions">
        <a href="{{ url('/admin') }}" class="ap-btn ap-btn-primary">
            ورود به پنل مدیریت
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </a>
    </div>
@endsection
