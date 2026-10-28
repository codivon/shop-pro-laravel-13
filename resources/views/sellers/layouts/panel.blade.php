<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full {{ request()->cookie('seller-theme') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- فاز ۱۰۸ — لایوت پنل فروشندگان مدرن (Livewire 4) — هم‌تراز با پنل مدیریت.
         از همان توکن‌های ap-* (admin.css) استفاده می‌شود ولی JS اختصاصی ادمین به آن
         وابسته نیست؛ spSidebar/spToast خودکفا و داخل همین فایل تعریف شده‌اند. --}}
    <title>@yield('title', $title ?? 'پنل فروشنده') | {{ option('info_site_title', 'او پی شاپ') }}</title>

    @vite(['resources/admin-panel/css/admin.css', 'resources/admin-panel/js/admin.js'])
    @livewireStyles

    {{-- Font Awesome — همان منبع پنل ادمین/قدیمی (برای آیکون‌های متنی خروج و ...) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- ثبت درایورهای Alpine پنل فروشنده (خودکفا — بدون وابستگی به JS ادمین) --}}
    <script>
        document.addEventListener('alpine:init', function () {
            // درایور سایدبار — الگوی کشوی موبایل (هم‌الگوی apSidebar پنل مدیریت)
            Alpine.data('spSidebar', function () {
                return {
                    open: false,
                };
            });

            // درایور Toast — پیام‌های سشن (مسیر کلاسیک کنترلرهای قدیمی)
            // + رویداد 'toast' لایووایر ($this->dispatch('toast', message: ..., type: ...))
            Alpine.data('spToast', function () {
                return {
                    items: [],
                    counter: 0,
                    show: function (message, type) {
                        var id = ++this.counter;
                        var duration = type === 'error' ? 7000 : 5000;
                        this.items.push({ id: id, message: message, type: type || 'info', visible: true });
                        var self = this;
                        setTimeout(function () { self.removeById(id); }, duration);
                    },
                    removeById: function (id) {
                        this.items = this.items.filter(function (t) { return t.id !== id; });
                    },
                    init: function () {
                        var self = this;
                        @if (session('toast-success'))
                            self.show(@js(session('toast-success')), 'success');
                        @endif
                        @if (session('toast-error'))
                            self.show(@js(session('toast-error')), 'error');
                        @endif
                        @if (session('toast-warning'))
                            self.show(@js(session('toast-warning')), 'warning');
                        @endif
                        if (window.Livewire) {
                            Livewire.on('toast', function (e) {
                                var payload = Array.isArray(e) ? e[0] : (e || {});
                                self.show(payload.message || '', payload.type || 'info');
                            });
                        }
                    },
                };
            });
        });
    </script>

    {{-- فاز ۱۱۹ — سشن‌توست‌ها فقط «یک بار» باید دیده شوند (مثل کنوانسیون پنل ادمین
     module-new/master-legacy که بعد از رندر forget می‌کنند)؛ قبلاً با putِ
     کنترلرهای قدیمی هر رفرش دوباره آلرت را نشان می‌داد --}}
    @php session()->forget(['toast-success', 'toast-error', 'toast-warning']); @endphp

    {{-- ⑨-۹ — اسکرول نرم سایدبار روی منوی فعال بعد از هر ناوبری --}}
    <script>
        (function () {
            function scrollToActive() {
                var active = document.querySelector('nav [aria-current="page"]');
                if (active && typeof active.scrollIntoView === 'function') {
                    active.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            }
            document.addEventListener('DOMContentLoaded', scrollToActive);
            document.addEventListener('livewire:navigated', scrollToActive);
        })();
    </script>

    {{-- ⑨-۱۱ — کی‌فریم نوار پیشرفت --}}
    <style>
        @keyframes sp-navslide {
            0% { transform: translateX(100%); }
            50% { transform: translateX(-150%); }
            100% { transform: translateX(-350%); }
        }
    </style>

    @stack('styles')
</head>
<body class="min-h-screen bg-background">

{{-- بدنه با درایور Alpine سایدبار --}}
<div x-data="spSidebar" class="min-h-screen flex flex-col">

    {{-- ⑨-۱۰/⑨-۱۱ — نوار پیشرفت ناوبری SPA و اکشن‌های Livewire (کلیک منو تا باز شدن صفحه) --}}
    <div wire:loading class="fixed inset-x-0 top-0 z-[60] h-0.5 overflow-hidden bg-brand-500/20" aria-hidden="true">
        <div class="h-full w-1/3 animate-[sp-navslide_1s_ease-in-out_infinite] rounded-full bg-brand-500"></div>
    </div>
    <div x-data="{ p: 0, on: false }"
         x-on:livewire:navigate.window="p = 30; on = true"
         x-on:livewire:navigated.window="p = 100; setTimeout(() => { on = false; p = 0 }, 250)"
         aria-hidden="true"
         class="pointer-events-none fixed inset-x-0 top-0 z-[61] h-0.5 transition-all duration-200"
         x-bind:style="on ? 'opacity:1' : 'opacity:0'">
        <div class="h-full bg-emerald-400 transition-all duration-300" x-bind:style="'width:' + p + '%'"></div>
    </div>

    {{-- پوشش موبایل --}}
    <div x-show="open" x-cloak x-transition.opacity.duration.200ms
         class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden"
         x-on:click="open = false" aria-hidden="true"></div>

    <div class="flex flex-1">
        {{-- سایدبار پنل فروشنده (start-0 در RTL یعنی سمت راست — ستون اصلی با lg:mr-[17rem]) --}}
        @include('sellers.panel.partials.sidebar')

        {{-- ستون اصلی --}}
        <div class="flex min-w-0 flex-1 flex-col lg:mr-[17rem]">
            @include('sellers.panel.partials.topbar')

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>

            {{-- فوتر چسبیده به پایین (وقتی محتوا کوتاه است) و پشت‌زنندهٔ طبیعی (وقتی بلند است) --}}
            <footer class="ap-footer mt-auto px-4 py-4 sm:px-6 lg:px-8 pb-[max(1rem,env(safe-area-inset-bottom))]">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 text-xs text-ink-3 dark:text-night-ink-2">
                    <span>پنل فروشندگان {{ option('info_site_title', 'او پی شاپ') }} — نسخهٔ جدید (Livewire 4)</span>
                    <span class="fa-digits">© {{ date('Y') }}</span>
                </div>
            </footer>
        </div>
    </div>

    {{-- Toasts --}}
    @include('sellers.panel.partials.toasts')
</div>

@livewireScripts

@stack('scripts')
</body>
</html>
