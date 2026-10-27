<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full {{ request()->cookie('seller-theme') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- فاز ۱۰۸ — لایوت مهمان (ورود/ثبت‌نام) پنل فروشندگان مدرن — از توکن‌های ap-* پنل مدیریت --}}
    <title>@yield('title', $title ?? 'پنل فروشندگان') | {{ option('info_site_title', 'او پی شاپ') }}</title>

    @vite(['resources/admin-panel/css/admin.css', 'resources/admin-panel/js/admin.js'])
    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }

        {{-- فاز ۱۱۰ — اسکرول نرم برای پرش به سکشن‌های لندینگ (منوی چسبان) --}}
        @media (prefers-reduced-motion: no-preference) {
            html { scroll-behavior: smooth; }
        }
    </style>
</head>
<body class="min-h-screen bg-background text-ink dark:bg-night-1 dark:text-night-ink">

{{-- Toast سادهٔ سشن (میدل‌ور Seller پیام toast-error می‌گذارد) --}}
<div class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4" role="status" aria-live="polite">
    @if (session('toast-error'))
        <div class="pointer-events-auto flex items-start gap-2.5 rounded-2xl border border-red-200 bg-red-50/95 p-3.5 text-red-800 shadow-lg backdrop-blur-md dark:border-red-500/30 dark:bg-red-950/90 dark:text-red-200"
             x-data="{ show: true }" x-show="show" x-cloak
             x-init="setTimeout(() => show = false, 6000)">
            <x-admin-panel::icon name="circle-alert" class="mt-0.5 size-5 shrink-0" />
            <p class="flex-1 text-[13px] font-bold leading-6">{{ session('toast-error') }}</p>
            <button type="button" class="shrink-0 rounded-lg p-1 opacity-60 hover:opacity-100" x-on:click="show = false" aria-label="بستن">
                <x-admin-panel::icon name="x" class="size-4" />
            </button>
        </div>
    @endif
    @if (session('toast-success'))
        <div class="pointer-events-auto flex items-start gap-2.5 rounded-2xl border border-emerald-200 bg-emerald-50/95 p-3.5 text-emerald-800 shadow-lg backdrop-blur-md dark:border-emerald-500/30 dark:bg-emerald-950/90 dark:text-emerald-200"
             x-data="{ show: true }" x-show="show" x-cloak
             x-init="setTimeout(() => show = false, 6000)">
            <x-admin-panel::icon name="check-circle-2" class="mt-0.5 size-5 shrink-0" />
            <p class="flex-1 text-[13px] font-bold leading-6">{{ session('toast-success') }}</p>
            <button type="button" class="shrink-0 rounded-lg p-1 opacity-60 hover:opacity-100" x-on:click="show = false" aria-label="بستن">
                <x-admin-panel::icon name="x" class="size-4" />
            </button>
        </div>
    @endif
</div>

<div class="flex min-h-screen flex-col @if (! ($sellerGuestHideAside ?? false)) lg:flex-row @endif">

    {{-- ستون برند (فقط دسکتاپ — لندینگ فاز ۱۰۹ بدون آن تمام‌عرض می‌شود) --}}
    @if (! ($sellerGuestHideAside ?? false))
    <aside class="relative hidden w-[42%] overflow-hidden bg-gradient-to-br from-brand-600 to-emerald-700 lg:flex lg:flex-col lg:justify-between" aria-hidden="true">
        <div class="pointer-events-none absolute -end-24 -top-24 size-96 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -start-24 size-96 rounded-full bg-emerald-400/20 blur-3xl"></div>

        <div class="relative flex items-center gap-3 p-10">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-white/15 text-white backdrop-blur">
                <x-admin-panel::icon name="store" class="size-6" />
            </span>
            <div class="leading-tight">
                <p class="text-lg font-extrabold text-white">{{ option('info_site_title', 'او پی شاپ') }}</p>
                <p class="text-xs font-semibold text-white/70">مرکز فروشندگان</p>
            </div>
        </div>

        <div class="relative space-y-6 px-10">
            <h2 class="text-3xl font-black leading-[1.6] text-white">
                محصولات‌تان را در معرض دید<br>
                هزاران مشتری بگذارید
            </h2>
            <p class="max-w-md text-sm font-semibold leading-8 text-white/80">
                با پنل فروشندگان، فروشگاه اختصاصی خود را بسازید؛ محصولات را مدیریت کنید،
                سفارش‌ها را ببینید و تسویهٔ شفاف داشته باشید.
            </p>
            <ul class="space-y-3 text-[13px] font-bold text-white/90">
                <li class="flex items-center gap-2.5">
                    <x-admin-panel::icon name="circle-check" class="size-4.5 text-emerald-300" />
                    فروشگاه اختصاصی با آدرس اختصاصی
                </li>
                <li class="flex items-center gap-2.5">
                    <x-admin-panel::icon name="circle-check" class="size-4.5 text-emerald-300" />
                    کیف پول و تسویهٔ شفاف با صورت‌حساب ردیف‌به‌ردیف
                </li>
                <li class="flex items-center gap-2.5">
                    <x-admin-panel::icon name="circle-check" class="size-4.5 text-emerald-300" />
                    آمار بازدید، گزارش فروش و سیگنال‌های تقاضا
                </li>
            </ul>
        </div>

        <div class="relative p-10 text-xs font-semibold text-white/60">
            © {{ date('Y') }} {{ option('info_site_title', 'او پی شاپ') }} — پنل مدرن فروشندگان (Livewire 4)
        </div>
    </aside>
    @endif

    {{-- ستون محتوا --}}
    <main class="flex flex-1 flex-col">
        <div class="flex items-center justify-between p-4 sm:px-8">
            {{-- برند موبایل --}}
            <a href="{{ route('front.index') }}" class="flex items-center gap-2.5 lg:hidden">
                <span class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="store" class="size-4.5" />
                </span>
                <span class="text-sm font-extrabold">{{ option('info_site_title', 'او پی شاپ') }}</span>
            </a>
            <span class="hidden lg:block"></span>

            {{-- تغییر پوسته (فاز ۱۰۹ — متد به‌جای عبارت var-دار که در evaluator جدید می‌شکند) --}}
            <button type="button"
                    class="flex size-10 items-center justify-center rounded-xl text-ink-2 transition-colors hover:bg-surface-2 dark:text-night-ink-2 dark:hover:bg-night-3"
                    x-data="{
                        toggleTheme() {
                            let dark = document.documentElement.classList.toggle('dark');
                            document.cookie = 'seller-theme=' + (dark ? 'dark' : 'light') + '; path=/; max-age=31536000; SameSite=Lax';
                            setTimeout(function () { window.location.reload(); }, 60);
                        }
                    }"
                    x-on:click="toggleTheme()"
                    aria-label="تغییر پوسته روشن/تاریک">
                <x-admin-panel::icon name="moon-star" class="size-5 dark:hidden" />
                <x-admin-panel::icon name="sun" class="hidden size-5 dark:block" />
            </button>
        </div>

        <div class="flex flex-1 items-start justify-center px-4 pb-16 sm:px-8">
            {{-- فاز ۱۰۹ — لندینگ عریض می‌شود (sellerGuestMaxWidth از کامپوننت share می‌شود) --}}
            <div class="w-full {{ $sellerGuestMaxWidth ?? 'max-w-xl' }}">
                {{ $slot }}
            </div>
        </div>
    </main>
</div>

{{-- فاز ۱۱۱ — Toast سراسری پیام‌های fetch ثبت‌نام/ورود (spToast در sp-fetch رویداد
     sp-auth-toast می‌فرستد؛ میزبان و شنونده از این پس در لایوت مهمان است تا پیام‌های
     خطای سرور مثل «این شماره قبلاً ثبت شده» در همهٔ صفحات auth واقعاً دیده شوند.
     قبلاً این بلوک فقط در لندینگ بود و بقیهٔ صفحات پیام را نمی‌دیدند) --}}
<div x-data x-on:sp-auth-toast.document="spToastShow($event.detail.message, $event.detail.type)" class="hidden"></div>
<script>
    function spToastShow(message, type) {
        var host = document.getElementById('sp-auth-toasts');
        if (! host) { return; }
        var el = document.createElement('div');
        el.className = 'pointer-events-auto flex items-start gap-2.5 rounded-2xl border p-3.5 shadow-lg backdrop-blur-md text-[13px] font-bold w-full max-w-md '
            + (type === 'success'
                ? 'border-emerald-200 bg-emerald-50/95 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-950/90 dark:text-emerald-200'
                : 'border-red-200 bg-red-50/95 text-red-800 dark:border-red-500/30 dark:bg-red-950/90 dark:text-red-200');
        el.textContent = message;
        host.appendChild(el);
        setTimeout(function () { el.remove(); }, 6000);
    }
</script>
<div id="sp-auth-toasts" class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4"></div>

@livewireScripts
@stack('scripts')
</body>
</html>
