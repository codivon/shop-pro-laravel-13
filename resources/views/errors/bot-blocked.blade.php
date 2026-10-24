{{-- ======================================================================
   فاز ۷۱ — صفحهٔ زیبای مسدودسازی تلهٔ ربات (جای متن خام Forbidden)
   گزارش کاربر: «اگه Forbidden به خاطر bot_traps هست پس باید صفحه یک
   ظاهر زیبا داشته باشد و به کاربر بگوید که بلاک شده است»
   — مبتنی بر errors.layouts.modern (خودکفا، بدون asset خارجی)
   متغیرها: $mode (blocked|trap) / $ip / $siteName / $ttlHours
====================================================================== --}}
@php($isBlocked = $mode === 'blocked')

@extends('errors.layouts.modern', [
    'code'     => '403',
    'accent'   => '#ef4444',
    'title'    => $isBlocked ? 'دسترسی شما مسدود شده است' : 'دسترسی به این مسیر مجاز نیست',
    'subtitle' => $isBlocked
        ? 'سیستم امنیتی سایت فعالیت خودکار و مشکوکی از سمت شبکهٔ شما تشخیص داده و دسترسی این آی‌پی به‌صورت موقت مسدود شده است. این محدودیت به‌طور خودکار پس از حداکثر '.$ttlHours.' ساعت برداشته می‌شود؛ اگر فکر می‌کنید اشتباهی رخ داده، با پشتیبانی تماس بگیرید یا از یک شبکهٔ دیگر (مثلاً اینترنت موبایل) تلاش کنید.'
        : 'این آدرس بخش داخلی سامانه است و برای بازدیدکنندگان در دسترس نیست. اگر فکر می‌کنید اشتباهی رخ داده، به صفحهٔ اصلی برگردید یا با پشتیبانی تماس بگیرید.',
    'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m2 2 20 20" /><path d="M5 5a1 1 0 0 0-1 1v7c0 5 3.5 7.5 7.67 8.94a1 1 0 0 0 .67.01c2.35-.82 4.48-1.97 5.9-3.71" /><path d="M9.309 3.652A12.252 12.252 0 0 0 11.24 2.28a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1v7a9.784 9.784 0 0 1-.08 1.264" /></svg>',
])

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        بازگشت به صفحهٔ اصلی
    </a>
@endsection

@section('meta')
    @if ($isBlocked && filled($ip))
        <span class="code-chip" dir="ltr" title="آی‌پی مسدودشده">{{ $ip }}</span>
    @endif
@endsection
