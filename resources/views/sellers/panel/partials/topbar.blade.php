{{-- تاپ‌بار پنل فروشندگان مدرن (فاز ۱۰۸) — هم‌تراز با پنل مدیریت
     داخل اسکوپ x-data="spSidebar" لایوت است؛ دکمهٔ همبرگر همان open را true می‌کند. --}}
@php
    $seller = seller();
    $sellerName = trim((string) ($seller->store_name ?: trim(($seller->name ?? '').' '.($seller->family ?? '')) ?: 'فروشنده'));
@endphp

<header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-surface/90 px-4 backdrop-blur-md dark:border-night-line dark:bg-night-1/90 sm:px-6"
        aria-label="نوار بالای پنل فروشنده">

    {{-- همبرگر موبایل --}}
    <button type="button"
            class="flex size-10 items-center justify-center rounded-xl text-ink-2 transition-colors hover:bg-surface-2 lg:hidden dark:text-night-ink-2 dark:hover:bg-night-3"
            x-on:click="open = true" aria-label="باز کردن منو">
        <x-admin-panel::icon name="menu" class="size-5.5" />
    </button>

    {{-- عنوان صفحه (از #[Title] لایووایر) --}}
    <h1 class="min-w-0 flex-1 truncate text-[15px] font-extrabold text-ink dark:text-night-ink">
        {{ $title ?? 'پنل فروشنده' }}
    </h1>

    {{-- تغییر پوستهٔ روشن/تاریک (کوکی seller-theme — فاز ۱۰۹: متد به‌جای عبارت var-دار) --}}
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
            :aria-label="document.documentElement.classList.contains('dark') ? 'پوستهٔ روشن' : 'پوستهٔ تاریک'">
        <x-admin-panel::icon name="moon-star" class="size-5 dark:hidden" />
        <x-admin-panel::icon name="sun" class="hidden size-5 dark:block" />
    </button>

    {{-- فروشگاه من --}}
    <a href="{{ $seller && $seller->slug ? url('/store/'.$seller->slug) : url('/') }}" target="_blank" rel="noopener"
       class="hidden items-center gap-1.5 rounded-xl px-3 py-2 text-[13px] font-bold text-ink-2 transition-colors hover:bg-surface-2 sm:flex dark:text-night-ink-2 dark:hover:bg-night-3">
        <x-admin-panel::icon name="external-link" class="size-4" />
        فروشگاه من
    </a>

    {{-- هویت فروشنده --}}
    <span class="flex items-center gap-2.5 border-s border-line ps-3 dark:border-night-line">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-emerald-600 text-sm font-extrabold text-white"
              aria-hidden="true">{{ mb_substr($sellerName, 0, 1) }}</span>
        <span class="hidden min-w-0 leading-tight md:block">
            <span class="block max-w-40 truncate text-[13px] font-bold text-ink dark:text-night-ink">{{ $sellerName }}</span>
            <span class="block text-[10px] font-semibold text-ink-3 dark:text-night-ink-2">فروشنده</span>
        </span>
    </span>

    {{-- خروج (خروج مسیر کلاسیک است — بدون wire:navigate) --}}
    <a href="{{ route('seller.logout') }}"
       class="flex size-10 items-center justify-center rounded-xl text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-500/10"
       aria-label="خروج از پنل" title="خروج">
        <x-admin-panel::icon name="log-out" class="size-5" />
    </a>
</header>
