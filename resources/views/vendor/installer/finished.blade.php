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
            <span><strong>تبریک!</strong> نصب فروشگاه‌ساز با موفقیت انجام شد. فقط یک قدم مانده: ساخت حساب مدیر فروشگاه.</span>
        </div>

        @php
            $installAdmin = null;
            try {
                $installAdmin = \Illuminate\Support\Facades\DB::table('admins')->orderBy('id')->value('username');
            } catch (\Throwable $e) {}
        @endphp

        @if($installAdmin)
            {{-- مدیر از قبل وجود دارد (نصب مجدد) → مستقیم به پنل --}}
            <ul class="ap-list" style="margin-bottom:1.4rem">
                @php $panelUrl = url('admin/'.(admin_route_prefix() ?: 'admin')); @endphp
                <li><span>ورود به پنل مدیریت</span><a href="{{ $panelUrl }}" dir="ltr" style="color:var(--brand-strong);font-weight:700;text-decoration:none">{{ $panelUrl }}</a></li>
            </ul>

            <div class="ap-alert ap-alert-warn" style="margin-bottom:1.2rem">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                <span>حساب مدیر از قبل ساخته شده است (نام کاربری: <strong dir="ltr">{{ $installAdmin }}</strong>). <u>اگر رمز اولیه را تغییر نداده‌اید، بلافاصله پس از ورود آن را عوض کنید.</u></span>
            </div>
        @else
            {{-- فاز ۲۵ — آخرین مرحله‌ی نصب: ثبت‌نام اولین مدیر --}}
            <div class="ap-alert ap-alert-warn" style="margin-bottom:1.2rem">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                <span><strong>قدم آخر:</strong> حساب مدیر کل را بسازید — نام کاربری، گذرواژه و آدرس اختصاصی پنل را در صفحه‌ی ثبت‌نام انتخاب کنید. پس از ثبت‌نام، استایل فروشگاه به‌صورت خودکار ساخته و مستقیم وارد پنل می‌شوید.</span>
            </div>
        @endif

        <p class="ap-muted">توصیه امنیتی: پس از ورود، از بخش «ویرایش پروفایل» رمز عبور را عوض کنید و سپس این صفحه/نشست را ببندید.</p>
    @endif

    <div class="ap-actions">
        @if($installAdmin)
            @php $panelUrl = url('admin/'.(admin_route_prefix() ?: 'admin')); @endphp
            <a href="{{ $panelUrl }}" class="ap-btn ap-btn-primary">
                ورود به پنل مدیریت
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
        @else
            <a href="{{ route('register') }}" class="ap-btn ap-btn-primary">
                ساخت حساب مدیر (ثبت‌نام)
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
        @endif
    </div>
@endsection
