<!DOCTYPE html>
<html lang="fa" dir="rtl"
      class="h-full lw-booting {{ request()->cookie('admin-theme') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="admin-prefix" content="{{ admin_route_prefix() }}">

    <title>@yield('title', $title ?? 'داشبورد') | پنل مدیریت {{ option('info_site_title', config('app.name')) }}</title>

    {{-- کوکی «پنل فعال» — صفحات و منوهای پکیج‌ها با همین کوکی بین پنل جدید/قدیمی سوییچ می‌کنند --}}
    <script>document.cookie = 'ap_panel=new; path=/; max-age=31536000; SameSite=Lax';</script>

    @vite(['resources/admin-panel/css/admin.css', 'resources/admin-panel/js/admin.js'])
    @livewireStyles

    {{-- فاز ۳۴ — Font Awesome برای آیکون‌های منوی پکیج‌ها (همان منبعی که پنل قدیمی/پل استفاده می‌کند) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="min-h-screen">

{{-- بدنه با درایور Alpine سایدبار --}}
<div x-data="apSidebar" class="min-h-screen flex flex-col">

    {{-- پوشش موبایل --}}
    <div x-show="open" x-cloak x-transition.opacity.duration.200ms
         class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden"
         x-on:click="open = false" aria-hidden="true"></div>

    <div class="flex flex-1">
        <x-admin-panel::sidebar />

        {{-- ستون اصلی --}}
        <div class="flex min-w-0 flex-1 flex-col lg:mr-[17rem]">
            {{-- فاز ۱۹: $title (از #[Title] Livewire) صریحاً پاس داده می‌شود — کامپوننت‌های anonymous اسکوپ والد را ارث نمی‌برند --}}
            <x-admin-panel::topbar :title="($title ?? null)" />

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>

            <footer class="ap-footer px-4 py-4 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 text-xs text-ink-3 dark:text-night-ink-2">
                    <span>پنل مدیریت {{ option('info_site_title', config('app.name')) }} — نسخه جدید (Livewire 4)</span>
                    <span class="fa-digits">© {{ date('Y') }}</span>
                </div>
            </footer>
        </div>
    </div>

    {{-- Toasts --}}
    <x-admin-panel::toasts />
</div>

@if (session('np-toast'))
    @php $npToast = session('np-toast') @endphp
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.ApToast?.{{ in_array($npToast['type'] ?? '', ['success', 'error', 'info']) ? $npToast['type'] : 'info' }}(
                {{ json_encode($npToast['message'] ?? '') }});
        });
    </script>
@endif

@livewireScripts

{{-- فاز ۱۳ — گارد بوت لایووایر + نگهبان خودترمیم asset
     ۱) کلاس lw-booting تا لحظه راه‌اندازی لایووایر، spanهای wire:loading را مخفی نگه می‌دارد
        (رفع دکمه‌هایی که از ابتدا «در حال ذخیره…» نشان می‌دهند وقتی JS لایووایر لود نشده).
     ۲) اگر اسکریپت لایووایر بعد از ۶ ثانیه لود نشد (مثل 404 پس از تغییر APP_KEY که
        URL سرویسِ asset را عوض می‌کند)، یک‌بار صفحه تازه‌سازی می‌شود (بدون حلقه بی‌پایان). --}}
<script>
    (function () {
        var root = document.documentElement;
        var unboot = function () {
            root.classList.remove('lw-booting');
            document.removeEventListener('livewire:init', unboot);
            document.removeEventListener('livewire:initialized', unboot);
            document.removeEventListener('livewire:navigated', unboot);
        };
        document.addEventListener('livewire:init', unboot);
        document.addEventListener('livewire:initialized', unboot);
        document.addEventListener('livewire:navigated', unboot);
        setTimeout(function () {
            if (window.Livewire) { unboot(); return; }
            try {
                var stamp = 'ap-lw-boot-reload';
                var last = parseInt(sessionStorage.getItem(stamp) || '0', 10);
                if (Date.now() - last > 30000) {
                    sessionStorage.setItem(stamp, String(Date.now()));
                    location.reload();
                }
            } catch (e) { /* دسترسی به sessionStorage نیست — چیزی نکن */ }
        }, 6000);
    })();
</script>
@stack('scripts')
</body>
</html>
