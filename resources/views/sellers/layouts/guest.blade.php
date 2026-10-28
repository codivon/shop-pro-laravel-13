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

    {{-- فاز ۱۱۴ — Font Awesome برای آیکون‌های انتخاب‌شده از انتخابگر (لندینگ فروشندگان) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

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
{{-- فاز ۱۱۹ — مصرف یک‌بارهٔ سشن‌توست‌ها (نمایش مجدد با رفرش ممنوع) --}}
@php session()->forget(['toast-success', 'toast-error', 'toast-warning']); @endphp

<div class="flex min-h-screen flex-col @if (! ($sellerGuestHideAside ?? false)) lg:flex-row @endif">

    {{-- ستون برند (فقط دسکتاپ — لندینگ فاز ۱۰۹ بدون آن تمام‌عرض می‌شود) --}}
    {{-- فاز ۱۱۳ — چسبان: متن ستون برند کوتاه است؛ ستون ثابت می‌ماند و فقط ستون فرم
         اسکرول می‌خورد + تصویرسازی داشبورد برای پرشدن فضای خالی --}}
    @if (! ($sellerGuestHideAside ?? false))
    <aside class="sticky top-0 hidden h-screen w-[42%] shrink-0 overflow-hidden bg-gradient-to-br from-brand-600 to-emerald-700 lg:flex lg:flex-col lg:justify-between" aria-hidden="true">
        <div class="pointer-events-none absolute -end-24 -top-24 size-96 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -start-24 size-96 rounded-full bg-emerald-400/20 blur-3xl"></div>

        <div class="relative flex items-center gap-3 p-10 pb-4">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-white/15 text-white backdrop-blur">
                <x-admin-panel::icon name="store" class="size-6" />
            </span>
            <div class="leading-tight">
                <p class="text-lg font-extrabold text-white">{{ option('info_site_title', 'او پی شاپ') }}</p>
                <p class="text-xs font-semibold text-white/70">مرکز فروشندگان</p>
            </div>
        </div>

        <div class="relative flex flex-1 flex-col justify-center space-y-6 px-10 py-4">
            <h2 class="text-2xl font-black leading-[1.7] text-white xl:text-3xl">
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

            {{-- فاز ۱۱۳ — تصویرسازی «پیش‌نمایش داشبورد فروشنده» (شیشه‌ای) — پرکردن
                 فضای خالی ستون برند؛ فقط وقتی ارتفاع صفحه اجازه دهد تا فوتر دیده شود --}}
            <div class="relative hidden pt-2 [@media(min-height:720px)]:block">
                <div class="relative max-w-sm rounded-3xl border border-white/15 bg-white/10 p-4 shadow-2xl backdrop-blur-md">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="flex size-8 items-center justify-center rounded-xl bg-white/20 text-white">
                                <x-admin-panel::icon name="store" class="size-4" />
                            </span>
                            <span>
                                <span class="block text-[11px] font-black text-white">فروشگاه شما</span>
                                <span class="block text-[9px] font-semibold text-white/60">نمای داشبورد پنل</span>
                            </span>
                        </div>
                        <span class="flex items-center gap-1 rounded-lg bg-emerald-400/25 px-2 py-1 text-[9px] font-black text-emerald-50">
                            <x-admin-panel::icon name="bell" class="size-3" />
                            سفارش جدید
                        </span>
                    </div>

                    <div class="mt-3 grid grid-cols-3 gap-2">
                        @foreach ([['package', '۳ محصول'], ['shopping-bag', '۱۲ سفارش'], ['wallet', 'تسویه']] as $tile)
                            <div class="rounded-2xl bg-white/10 p-2.5">
                                <span class="flex size-7 items-center justify-center rounded-lg bg-white/15 text-white">
                                    <x-admin-panel::icon name="{{ $tile[0] }}" class="size-3.5" />
                                </span>
                                <span class="mt-1.5 block h-1.5 w-3/4 rounded-full bg-white/25"></span>
                                <span class="mt-1 block text-[9px] font-bold text-white/80">{{ $tile[1] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-2 flex items-end justify-between gap-2 rounded-2xl bg-white/10 p-3">
                        <div class="flex h-14 flex-1 items-end gap-1.5" >
                            <span class="w-full rounded-t-md bg-white/25" style="height: 30%;"></span>
                            <span class="w-full rounded-t-md bg-white/25" style="height: 45%;"></span>
                            <span class="w-full rounded-t-md bg-white/30" style="height: 38%;"></span>
                            <span class="w-full rounded-t-md bg-white/35" style="height: 60%;"></span>
                            <span class="w-full rounded-t-md bg-white/40" style="height: 52%;"></span>
                            <span class="w-full rounded-t-md bg-emerald-300" style="height: 88%;"></span>
                        </div>
                        <span class="flex items-center gap-1 rounded-lg bg-emerald-400/25 px-2 py-1 text-[10px] font-black text-emerald-50">
                            <x-admin-panel::icon name="trending-up" class="size-3.5" />
                            رشد فروش
                        </span>
                    </div>
                </div>

                {{-- نشان‌های شناور تزئینی --}}
                <span class="absolute -end-3 top-6 flex items-center gap-1.5 rounded-2xl border border-white/20 bg-white/15 px-3 py-2 text-[10px] font-black text-white shadow-lg backdrop-blur">
                    <x-admin-panel::icon name="circle-check-big" class="size-3.5 text-emerald-200" />
                    تأیید سریع مدارک
                </span>
                <span class="absolute -start-5 bottom-8 flex items-center gap-1.5 rounded-2xl border border-white/20 bg-white/15 px-3 py-2 text-[10px] font-black text-white shadow-lg backdrop-blur">
                    <x-admin-panel::icon name="shield-check" class="size-3.5 text-emerald-200" />
                    تسویهٔ شفاف
                </span>
            </div>
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
