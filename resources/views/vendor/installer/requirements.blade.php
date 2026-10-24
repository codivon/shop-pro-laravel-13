@extends('vendor.installer.layouts.master')

@section('template_title', 'نیازمندی‌های سرور')

@section('title', 'بررسی نیازمندی‌ها')
@section('title_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m8 6-6 6 6 6m8-12 6 6-6 6"/></svg>
@endsection
@section('title_desc', 'سازگاری سرور با فروشگاه‌ساز بررسی شد')

@section('container')
    @foreach($requirements['requirements'] as $type => $requirement)
        <ul class="ap-list">
            <li class="ap-list-title">
                <span>{{ ucfirst($type) }}
                    @if($type == 'php')
                        <small>(حداقل نسخه {{ $phpSupportInfo['minimum'] }} — نسخه فعلی {{ $phpSupportInfo['current'] }})</small>
                    @endif
                </span>
                @if($type == 'php')
                    <span class="ap-state {{ $phpSupportInfo['supported'] ? 'ok' : 'bad' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            @if($phpSupportInfo['supported'])<path d="M20 6 9 17l-5-5"/>@else<circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>@endif
                        </svg>
                    </span>
                @endif
            </li>
            @foreach($requirements['requirements'][$type] as $extention => $enabled)
                <li>
                    <span>{{ $extention }}</span>
                    <span class="ap-state {{ $enabled ? 'ok' : 'bad' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            @if($enabled)<path d="M20 6 9 17l-5-5"/>@else<circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>@endif
                        </svg>
                    </span>
                </li>
            @endforeach
        </ul>
    @endforeach

    @if (isset($requirements['errors']) || ! $phpSupportInfo['supported'])
        <div class="ap-alert ap-alert-error" style="margin-top:1.2rem">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
            <span>برای ادامه، موارد علامت‌خورده با قرمز را روی سرور فعال کنید و این صفحه را دوباره بارگذاری کنید.</span>
        </div>
    @endif

    @if ( ! isset($requirements['errors']) && $phpSupportInfo['supported'] )
        <div class="ap-actions">
            <a class="ap-btn ap-btn-primary" href="{{ route('LaravelInstaller::permissions') }}">
                مرحله بعد: مجوزها
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
        </div>
    @endif
@endsection
