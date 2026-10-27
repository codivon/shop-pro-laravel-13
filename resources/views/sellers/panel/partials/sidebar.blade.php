{{-- سایدبار پنل فروشندگان مدرن (فاز ۱۰۸) — پورت ۱:۱ منوی پنل قدیمی
     (resources/views/sellers-old/panel/partials/menu-sidebar.blade.php)
     با همان گروه‌بندی/برچسب‌ها/گیت‌ها + آیتم «فروشگاه من» --}}
@php
    $seller = seller();
    $sellerInfo = seller_info();
    $siteTitle = option('info_site_title', 'او پی شاپ');

    // لینک فروشگاه فروشنده — مسیر فروشگاه با اسلاگ فروشنده (مبنای آمار Viewer: /store/{slug})
    $storeUrl = $seller && $seller->slug ? url('/store/'.$seller->slug) : url('/');

    // گیت‌های فاز ۱۰۳/۱۰۶ — همان شرط‌های منوی قدیمی (قانون طلایی)
    $showDemand = \App\Support\MultiVendorSettings::demandSignalsEnabled();
    $showReviews = \App\Support\MultiVendorSettings::reviewsAccessEnabled();
    $showInventory = \App\Support\MultiVendorSettings::inventoryCenterEnabled();
    $showSettlements = \App\Support\MultiVendorSettings::settlementStatementEnabled();
@endphp

<aside
    class="fixed inset-y-0 start-0 z-50 flex w-[17rem] flex-col border-l border-slate-800 bg-slate-900 text-slate-100 transition-transform duration-300
           dark:border-night-line dark:bg-night-2"
    {{-- فاز ۱۰۹ — فیکس: کشوی موبایل فقط زیر lg جابه‌جا می‌شود؛ در دسکتاپ سایدبار همیشه پیداست
         (قبلاً translate-x-full بدون lg:translate-x-0 بود و سایدبار دسکتاپ بیرون صفحه می‌ماند) --}}
    x-bind:class="open ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
    aria-label="منوی اصلی پنل فروشنده">

    {{-- برند --}}
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-800 px-5 dark:border-night-line">
        <span class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-white shadow-[0_6px_16px_-6px_rgb(5_150_105/.6)]">
            <x-admin-panel::icon name="store" class="size-5" />
        </span>
        <div class="min-w-0 leading-tight">
            <p class="truncate text-sm font-extrabold text-white">{{ $siteTitle }}</p>
            <p class="text-[11px] text-slate-400">مرکز فروشندگان</p>
        </div>
        {{-- بستن موبایل --}}
        <button type="button" class="ms-auto rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 lg:hidden"
                x-on:click="open = false" aria-label="بستن منو">
            <x-admin-panel::icon name="x" class="size-5" />
        </button>
    </div>

    {{-- آیتم‌های منو --}}
    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 pb-6 pt-3 sp-scroll">
        {{-- داشبورد --}}
        <ul class="space-y-0.5">
            <li>
                <a href="{{ route('seller.dashboard') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.dashboard')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.dashboard')) aria-current="page" @endif>
                    <x-admin-panel::icon name="layout-dashboard" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">داشبورد</span>
                </a>
            </li>
        </ul>

        {{-- محصولات --}}
        <p class="px-3 pb-1.5 pt-5 text-[11px] font-bold tracking-wide text-slate-500">محصولات</p>
        <ul class="space-y-0.5">
            <li>
                <a href="{{ route('seller.products.find') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.products.find')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.products.find')) aria-current="page" @endif>
                    <x-admin-panel::icon name="search" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">جستجو یا درج محصول</span>
                </a>
            </li>
            <li>
                <a href="{{ route('seller.products.index') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.products.index')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.products.index')) aria-current="page" @endif>
                    <x-admin-panel::icon name="package" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">مدیریت محصولات</span>
                </a>
            </li>

            {{-- فاز ۱۰۶-۳ — سیگنال‌های تقاضا؛ گیت سوییچ seller_demand_signals (قانون طلایی) --}}
            @if ($showDemand)
                <li>
                    <a href="{{ route('seller.demand.signals') }}" wire:navigate
                       class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('seller.demand.signals')
                                  ? 'bg-brand-500/15 font-bold text-emerald-300'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                       @if(request()->routeIs('seller.demand.signals')) aria-current="page" @endif>
                        <x-admin-panel::icon name="activity" class="size-[18px] shrink-0 opacity-80" />
                        <span class="flex-1">سیگنال‌های تقاضا</span>
                    </a>
                </li>
            @endif

            {{-- فاز ۱۰۶-۴ — نظرات محصولات؛ گیت سوییچ seller_reviews_access (قانون طلایی) --}}
            @if ($showReviews)
                <li>
                    <a href="{{ route('seller.reviews.index') }}" wire:navigate
                       class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('seller.reviews.index')
                                  ? 'bg-brand-500/15 font-bold text-emerald-300'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                       @if(request()->routeIs('seller.reviews.index')) aria-current="page" @endif>
                        <x-admin-panel::icon name="star" class="size-[18px] shrink-0 opacity-80" />
                        <span class="flex-1">نظرات محصولات</span>
                    </a>
                </li>
            @endif

            {{-- فاز ۱۰۶-۵ — مرکز موجودی؛ گیت سوییچ seller_inventory_center (قانون طلایی) --}}
            @if ($showInventory)
                <li>
                    <a href="{{ route('seller.inventory.index') }}" wire:navigate
                       class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('seller.inventory.index')
                                  ? 'bg-brand-500/15 font-bold text-emerald-300'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                       @if(request()->routeIs('seller.inventory.index')) aria-current="page" @endif>
                        <x-admin-panel::icon name="box" class="size-[18px] shrink-0 opacity-80" />
                        <span class="flex-1">مرکز موجودی</span>
                    </a>
                </li>
            @endif
        </ul>

        {{-- سفارش‌ها --}}
        <p class="px-3 pb-1.5 pt-5 text-[11px] font-bold tracking-wide text-slate-500">سفارش‌ها</p>
        <ul class="space-y-0.5">
            <li>
                <a href="{{ route('seller.orders.index') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.orders.index')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.orders.index')) aria-current="page" @endif>
                    <x-admin-panel::icon name="shopping-cart" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">تاریخچه سفارش‌ها</span>
                </a>
            </li>
            <li>
                <a href="{{ route('seller.orders.notCompleted') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.orders.notCompleted')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.orders.notCompleted')) aria-current="page" @endif>
                    <x-admin-panel::icon name="package-check" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">محصولات منتظر ارسال</span>
                </a>
            </li>
        </ul>

        {{-- مالی --}}
        <p class="px-3 pb-1.5 pt-5 text-[11px] font-bold tracking-wide text-slate-500">مالی</p>
        <ul class="space-y-0.5">
            <li>
                <a href="{{ route('seller.wallet.index') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.wallet.index')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.wallet.index')) aria-current="page" @endif>
                    <x-admin-panel::icon name="wallet" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">کیف پول، واریزی ها</span>
                </a>
            </li>

            {{-- فاز ۱۰۶-۱ — صورت‌حساب تسویه؛ گیت سوییچ multi_vendor_settlement_statement --}}
            @if ($showSettlements)
                <li>
                    <a href="{{ route('seller.wallet.settlements') }}" wire:navigate
                       class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('seller.wallet.settlements')
                                  ? 'bg-brand-500/15 font-bold text-emerald-300'
                                  : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                       @if(request()->routeIs('seller.wallet.settlements')) aria-current="page" @endif>
                        <x-admin-panel::icon name="file-text" class="size-[18px] shrink-0 opacity-80" />
                        <span class="flex-1">صورت‌حساب تسویه</span>
                    </a>
                </li>
            @endif

            <li>
                <a href="{{ route('seller.commission') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.commission')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.commission')) aria-current="page" @endif>
                    <x-admin-panel::icon name="percent" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">کمیسیون و هزینه‌ها</span>
                </a>
            </li>
            <li>
                <a href="{{ route('seller.commission.guide') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.commission.guide')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.commission.guide')) aria-current="page" @endif>
                    <x-admin-panel::icon name="badge-percent" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">نرخ کمیسیون</span>
                </a>
            </li>
        </ul>

        {{-- حمل و نقل --}}
        <p class="px-3 pb-1.5 pt-5 text-[11px] font-bold tracking-wide text-slate-500">حمل و نقل</p>
        <ul class="space-y-0.5">
            <li>
                <a href="{{ route('seller.carriers.index') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.carriers.*')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.carriers.*')) aria-current="page" @endif>
                    <x-admin-panel::icon name="truck" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">روش‌های ارسال</span>
                </a>
            </li>
            <li>
                <a href="{{ route('seller.tariffs.index') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.tariffs.*')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.tariffs.*')) aria-current="page" @endif>
                    <x-admin-panel::icon name="coins" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">تعرفه‌های ارسال</span>
                </a>
            </li>
        </ul>

        {{-- ابزارها و حساب --}}
        <p class="px-3 pb-1.5 pt-5 text-[11px] font-bold tracking-wide text-slate-500">سایر</p>
        <ul class="space-y-0.5">
            <li>
                <a href="{{ route('seller.statistics.index') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.statistics.*')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.statistics.*')) aria-current="page" @endif>
                    <x-admin-panel::icon name="bar-chart-3" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">آمار و گزارش فروش</span>
                </a>
            </li>
            <li>
                <a href="{{ route('seller.tickets.index') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.tickets.*')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.tickets.*')) aria-current="page" @endif>
                    <x-admin-panel::icon name="life-buoy" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">درخواست پشتیبانی</span>
                </a>
            </li>
            <li>
                <a href="{{ route('seller.questions.index') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.questions.*')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.questions.*')) aria-current="page" @endif>
                    <x-admin-panel::icon name="message-circle" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">پرسش و پاسخ</span>
                </a>
            </li>
            <li>
                <a href="{{ route('seller.notifications.index') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.notifications.*')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.notifications.*')) aria-current="page" @endif>
                    <x-admin-panel::icon name="mail" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">پیام ها</span>
                </a>
            </li>
            <li>
                <a href="{{ route('seller.profile.index') }}" wire:navigate
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('seller.profile.*')
                              ? 'bg-brand-500/15 font-bold text-emerald-300'
                              : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                   @if(request()->routeIs('seller.profile.*')) aria-current="page" @endif>
                    <x-admin-panel::icon name="user" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">پروفایل شما</span>
                </a>
            </li>

            {{-- فروشگاه من — لینک بیرونی (در تب جدید) --}}
            <li>
                <a href="{{ $storeUrl }}" target="_blank" rel="noopener"
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-300 transition-all duration-200 hover:bg-slate-800 hover:text-white">
                    <x-admin-panel::icon name="store" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">فروشگاه من</span>
                    <x-admin-panel::icon name="external-link" class="size-3.5 opacity-50" />
                </a>
            </li>

            {{-- خروج — GET سادهٔ پنل قدیمی؛ بدون wire:navigate (خروج کامل با رفرش) --}}
            <li>
                <a href="{{ route('seller.logout') }}"
                   class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-300 transition-all duration-200 hover:bg-red-500/10 hover:text-red-400">
                    <x-admin-panel::icon name="log-out" class="size-[18px] shrink-0 opacity-80" />
                    <span class="flex-1">خروج</span>
                </a>
            </li>
        </ul>
    </nav>

    {{-- پایین سایدبار: پروفایل فروشنده --}}
    <div class="border-t border-slate-800 p-3 dark:border-night-line">
        <div class="flex items-center gap-3 rounded-xl bg-slate-800/70 p-2.5 dark:bg-night-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-sm font-extrabold text-white ring-2 ring-brand-500/25">
                {{ mb_substr($sellerInfo?->business_name ?: ($seller?->email ?: 'ف'), 0, 1, 'UTF-8') }}
            </span>
            <div class="min-w-0 flex-1 leading-tight">
                <p class="truncate text-[13px] font-bold text-white">{{ $sellerInfo?->business_name ?: 'فروشنده' }}</p>
                <p class="text-[11px] text-slate-400">{{ $seller?->email }}</p>
            </div>
            <a href="{{ route('seller.logout') }}"
               class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-red-500/10 hover:text-red-400"
               aria-label="خروج از حساب" title="خروج از حساب">
                <x-admin-panel::icon name="log-out" class="size-[18px]" />
            </a>
        </div>
    </div>
</aside>
