@extends('vendor.installer.layouts.master')

@section('template_title', 'خوش آمدید')

@section('title', 'نصب فروشگاه‌ساز')
@section('title_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4m0 12v4M2 12h4m12 0h4"/><circle cx="12" cy="12" r="9"/></svg>
@endsection
@section('title_desc', 'چند دقیقه دیگر فروشگاه شما آماده است')

@section('container')
    <p class="ap-lead">
        به نصب‌کننده فروشگاه‌ساز خوش آمدید. این راهنما شما را قدم‌به‌قدم برای راه‌اندازی فروشگاه همراهی می‌کند:
        بررسی نیازمندی‌های سرور، تعیین اطلاعات دیتابیس و ساخت جداول.
    </p>

    <div class="ap-alert ap-alert-warn" style="margin-top:1.2rem">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4m0 4h.01"/></svg>
        <span>پیش از شروع، مطمئن شوید فایل <code dir="ltr">.env</code> پروژه قابل نوشتن است و اطلاعات اتصال دیتابیس را در اختیار دارید.</span>
    </div>

    <div class="ap-actions">
        <a href="{{ route('LaravelInstaller::requirements') }}" class="ap-btn ap-btn-primary">
            شروع نصب
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </a>
    </div>
@endsection
