@extends('vendor.installer.layouts.master')

@section('template_title', 'مجوزهای پوشه‌ها')

@section('title', 'بررسی مجوزها')
@section('title_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
@endsection
@section('title_desc', 'پوشه‌های زیر باید قابل نوشتن باشند')

@section('container')
    <ul class="ap-list">
        @foreach($permissions['permissions'] as $permission)
            <li class="list__item--permissions">
                <span class="bar {{ $permission['isSet'] ? 'bar-success' : 'bar-danger' }}"></span>
                <code dir="ltr" style="font-size:.78rem">{{ $permission['folder'] }}</code>
                <span class="ap-state {{ $permission['isSet'] ? 'ok' : 'bad' }}">
                    {{ $permission['permission'] }}
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        @if($permission['isSet'])<path d="M20 6 9 17l-5-5"/>@else<circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>@endif
                    </svg>
                </span>
            </li>
        @endforeach
    </ul>

    @if (isset($permissions['errors']))
        <div class="ap-alert ap-alert-error" style="margin-top:1.2rem">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
            <span>مجوز نوشتن (۷۵۵ یا ۷۷۵) را برای پوشه‌های قرمز از طریق مدیریت فایل هاست تنظیم کنید و صفحه را دوباره بارگذاری کنید.</span>
        </div>
    @endif

    @if ( ! isset($permissions['errors']))
        <div class="ap-actions">
            <a href="{{ route('LaravelInstaller::environmentWizard') }}" class="ap-btn ap-btn-primary">
                مرحله بعد: تنظیمات محیط
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
        </div>
    @endif
@endsection
