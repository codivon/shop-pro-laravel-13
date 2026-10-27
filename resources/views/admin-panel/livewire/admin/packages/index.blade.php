{{-- بازار — فاز ۴۴: پکیج‌ها + قالب‌ها (فاز ۱۷: بازسازی کامل ظاهر و جریان نصب از پنل قدیمی back/packages/index.blade.php)
     کارت‌های رسانه‌دار + نشان‌ها + مُهر نصب + اورلی جزئیات + مودال تأیید نصب با انتخاب پلن/لایسنس/اشتراک --}}
<div class="mx-auto max-w-7xl space-y-5" x-data="pkgMarket">

    {{-- ============ سربرگ (پورت pkg-hero) ============ --}}
    <div class="relative overflow-hidden rounded-2xl border border-line bg-surface p-5 dark:border-night-line dark:bg-night-2 sm:p-6">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-l from-brand-500/10 via-transparent to-emerald-500/10" aria-hidden="true"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-emerald-600 text-white shadow-lg shadow-brand-500/25">
                    <x-admin-panel::icon name="package" class="size-6" />
                </span>
                <div>
                    <h1 class="text-lg font-extrabold">بازار</h1>
                    <p class="text-xs text-ink-3 dark:text-night-ink-2">پکیج‌ها و قالب‌ها را ببینید، بخرید، نصب و مدیریت کنید</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                {{-- فاز ۴۵ — تب بازار: همه / قالب‌ها / پکیج‌ها (پیش‌فرض: همه) --}}
                <div class="inline-flex rounded-xl border border-line bg-surface-2 p-1 dark:border-night-line dark:bg-night-3" role="tablist" aria-label="انتخاب بخش بازار">
                    <button type="button" wire:click="$set('type', '')"
                            class="rounded-lg px-4 py-1.5 text-[13px] font-bold transition-all {{ $type === '' ? 'bg-brand-600 text-white shadow-sm' : 'text-ink-2 hover:text-ink dark:text-night-ink-2 dark:hover:text-night-ink' }}"
                            role="tab" aria-selected="{{ $type === '' ? 'true' : 'false' }}">
                        <x-admin-panel::icon name="sparkles" class="me-1 inline size-4" />
                        همه
                    </button>
                    {{-- فاز ۹۹ — گیت تب قالب‌ها: packages_market_show_templates خاموش ⇒ تب مخفی --}}
                    @if (\App\Support\MarketplaceSettings::showTemplates())
                        <button type="button" wire:click="$set('type', 'template')"
                                class="rounded-lg px-4 py-1.5 text-[13px] font-bold transition-all {{ $type === 'template' ? 'bg-brand-600 text-white shadow-sm' : 'text-ink-2 hover:text-ink dark:text-night-ink-2 dark:hover:text-night-ink' }}"
                                role="tab" aria-selected="{{ $type === 'template' ? 'true' : 'false' }}">
                            <x-admin-panel::icon name="layout-template" class="me-1 inline size-4" />
                            قالب‌ها
                        </button>
                    @endif
                    <button type="button" wire:click="$set('type', 'package')"
                            class="rounded-lg px-4 py-1.5 text-[13px] font-bold transition-all {{ $type === 'package' ? 'bg-brand-600 text-white shadow-sm' : 'text-ink-2 hover:text-ink dark:text-night-ink-2 dark:hover:text-night-ink' }}"
                            role="tab" aria-selected="{{ $type === 'package' ? 'true' : 'false' }}">
                        <x-admin-panel::icon name="package" class="me-1 inline size-4" />
                        پکیج‌ها
                    </button>
                </div>

                @if ($type === 'package')
                    <a href="{{ route('np.packages.installed') }}" class="ap-btn-outline !py-2">
                        <x-admin-panel::icon name="layers" class="size-4" />
                        ماژول‌های نصب‌شده
                    </a>
                    <button type="button" wire:click="checkUpdates" wire:loading.attr="disabled" wire:target="checkUpdates"
                            class="ap-btn-outline !py-2">
                        <span wire:loading.remove wire:target="checkUpdates" class="flex items-center gap-2">
                            <x-admin-panel::icon name="refresh-cw" class="size-4" />
                            بررسی آپدیت‌ها
                        </span>
                        <span wire:loading wire:target="checkUpdates" class="flex items-center gap-2">
                            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                            </svg>
                            در حال بررسی…
                        </span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ============ فاز ۹۹ — بنر اختیاری بازارچه (تنظیمات ← تب «بازارچه») ============ --}}
    @if (\App\Support\MarketplaceSettings::noticeText() !== '')
        <div class="flex items-start gap-3 rounded-2xl border border-brand-200 bg-brand-50/70 p-4 dark:border-brand-500/25 dark:bg-brand-500/10">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <x-admin-panel::icon name="info" class="size-4.5" />
            </span>
            <p class="text-[13px] leading-6 text-brand-900 dark:text-brand-100">{{ \App\Support\MarketplaceSettings::noticeText() }}</p>
        </div>
    @endif

    {{-- ============ بنر اشتراک (پورت pkg-sub-banner) ============
         فاز ۵۸ — رفع باگ «اشتراکم فعال است ولی می‌گوید اشتراک فعالی ندارید»:
         شرط قبلی $type === 'package' بود؛ یعنی در تب پیش‌فرض «همه» حتی با اشتراک فعال
         همیشه بنر خاکستری نشان داده می‌شد. حالا بنر در هر تبی که پکیج‌ها را نشان می‌دهد
         (همه + پکیج‌ها) بر اساس وضعیت واقعی اشتراک رندر می‌شود و در تب قالب‌ها حذف است
         (اشتراک فقط پکیج‌ها را پوشش می‌دهد — گلاساری API).
         فاز ۱۰۷ — درخواست کاربر: در تب «همه» این بنر تنها محل نمایش وضعیت اشتراک است؛
         پیام «اشتراک ندارید / منقضی شده» دیگر داخل بخش پکیج‌ها یا قالب‌ها تکرار نمی‌شود
         و دکمه‌های خرید/تمدید به update.opashop.ir/subscriptions لینک می‌شوند. --}}
    @if ($this->showsPackages() && !empty($subscriptionSummary) && ($subscriptionSummary['has_active_subscription'] ?? false))
        <div class="relative flex flex-wrap items-center gap-4 overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-l from-emerald-50 to-teal-50 p-4 dark:border-emerald-500/25 dark:from-emerald-500/10 dark:to-teal-500/10 sm:p-5">
            <div class="pointer-events-none absolute -end-10 -top-10 size-32 rounded-full bg-emerald-400/20 blur-2xl" aria-hidden="true"></div>
            <span class="relative flex size-11 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                <x-admin-panel::icon name="award" class="size-5.5" />
            </span>
            <div class="relative min-w-0 flex-1">
                <p class="flex items-center gap-1.5 text-sm font-bold text-emerald-700 dark:text-emerald-400">
                    <x-admin-panel::icon name="check-circle" class="size-4" />
                    اشتراک فعال: {{ $subscriptionSummary['plan_name'] ?? 'اشتراک' }}
                </p>
                <p class="mt-0.5 text-xs text-ink-2 dark:text-night-ink-2">پکیج‌های مشمول اشتراک، بدون پرداخت نصب می‌شوند</p>
            </div>
            <div class="relative rounded-2xl border border-emerald-200 bg-white/70 px-4 py-2 text-center dark:border-emerald-500/25 dark:bg-emerald-950/30">
                @if (!empty($subscriptionSummary['less_than_one_day']))
                    {{-- فاز ۱۰۰ — به‌جای «۰ روز» عبارت «کمتر از ۱ روز» (اشتراک فعال ولی کمتر از ۲۴ ساعت مانده) --}}
                    <span class="block text-sm font-extrabold leading-6 text-emerald-600 dark:text-emerald-400">کمتر از ۱ روز</span>
                    <span class="text-[10px] font-semibold text-emerald-700/70 dark:text-emerald-400/70">باقی‌مانده</span>
                @else
                    <span class="fa-digits block text-xl font-extrabold leading-6 text-emerald-600 dark:text-emerald-400">{{ $subscriptionSummary['days_remaining'] ?? 0 }}</span>
                    <span class="text-[10px] font-semibold text-emerald-700/70 dark:text-emerald-400/70">روز باقی‌مانده</span>
                @endif
            </div>
        </div>
    @elseif ($this->showsPackages() && !empty($subscriptionSummary) && ($subscriptionSummary['is_expired'] ?? false))
        {{-- فاز ۱۰۰ — اشتراک قبلاً وجود داشته ولی منقضی شده (تفکیک از «اصلاً اشتراک ندارید») --}}
        <div class="relative flex flex-wrap items-center gap-4 overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-l from-amber-50 to-orange-50 p-4 dark:border-amber-500/25 dark:from-amber-500/10 dark:to-orange-500/10 sm:p-5">
            <div class="pointer-events-none absolute -end-10 -top-10 size-32 rounded-full bg-amber-400/20 blur-2xl" aria-hidden="true"></div>
            <span class="relative flex size-11 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-500/30">
                <x-admin-panel::icon name="clock" class="size-5.5" />
            </span>
            <div class="relative min-w-0 flex-1">
                <p class="flex items-center gap-1.5 text-sm font-bold text-amber-700 dark:text-amber-400">
                    <x-admin-panel::icon name="alert-triangle" class="size-4" />
                    اشتراک شما منقضی شده است
                </p>
                <p class="mt-0.5 text-xs text-ink-2 dark:text-night-ink-2">به دلیل انقضای اشتراک، پکیج‌های مشمول اشتراک نمایش داده نمی‌شوند؛ برای دسترسی مجدد اشتراک را تمدید کنید</p>
            </div>
            {{-- فاز ۱۰۷ — لینک به صفحهٔ خرید/تمدید اشتراک روی سرور مارکت (update.opashop.ir/subscriptions) --}}
            <a href="{{ config('packages.api.subscriptions_url') }}" target="_blank" rel="noopener" class="ap-btn-primary relative !py-2">
                <x-admin-panel::icon name="refresh-cw" class="size-4" />
                تمدید اشتراک
            </a>
        </div>
    @elseif ($this->showsPackages())
        <div class="relative flex flex-wrap items-center gap-4 overflow-hidden rounded-2xl border border-line bg-surface p-4 dark:border-night-line dark:bg-night-2 sm:p-5">
            <div class="pointer-events-none absolute -end-10 -top-10 size-32 rounded-full bg-brand-400/15 blur-2xl" aria-hidden="true"></div>
            <span class="relative flex size-11 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-500/30">
                <x-admin-panel::icon name="shopping-cart" class="size-5.5" />
            </span>
            <div class="relative min-w-0 flex-1">
                <p class="flex items-center gap-1.5 text-sm font-bold">
                    <x-admin-panel::icon name="award" class="size-4 text-brand-600 dark:text-brand-400" />
                    اشتراک فعالی ندارید
                </p>
                <p class="mt-0.5 text-xs text-ink-2 dark:text-night-ink-2">برای دسترسی رایگان به پکیج‌های ویژه، اشتراک را از سایت مرکزی خریداری کنید</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="rounded-lg border border-dashed border-line bg-surface-2 px-2.5 py-1 text-[11px] text-ink-3 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                        کد پروژه شما:
                        <code class="ltr ms-1 font-mono font-bold text-brand-600 dark:text-brand-400">{{ config('packages.api.project_key') }}</code>
                    </span>
                    <button type="button"
                            x-data="{ copied: false }"
                            x-on:click="navigator.clipboard && navigator.clipboard.writeText(@js(config('packages.api.project_key'))); copied = true; setTimeout(() => copied = false, 1800)"
                            class="flex items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-bold transition-colors"
                            :class="copied ? 'text-emerald-600' : 'text-ink-3 hover:text-brand-600 dark:text-night-ink-2 dark:hover:text-brand-400'">
                        <x-admin-panel::icon name="copy" class="size-3.5" x-show="! copied" />
                        <x-admin-panel::icon name="check" class="size-3.5 text-emerald-500" x-show="copied" x-cloak />
                        <span x-text="copied ? 'کپی شد' : 'کپی کد'"></span>
                    </button>
                </div>
            </div>
            {{-- فاز ۱۰۷ — لینک به صفحهٔ خرید/تمدید اشتراک روی سرور مارکت (update.opashop.ir/subscriptions) --}}
            <a href="{{ config('packages.api.subscriptions_url') }}" target="_blank" rel="noopener" class="ap-btn-primary relative !py-2">
                <x-admin-panel::icon name="external-link" class="size-4" />
                خرید اشتراک
            </a>
        </div>
    @endif

    {{-- ============ فاز ۴۵ — جستجوی واحد تب «همه» (قالب + پکیج هم‌زمان) ============ --}}
    @if ($this->showsAll())
        <div class="ap-card p-4" wire:key="market-all-search">
            <label class="mb-1.5 block text-xs font-semibold" for="market-search">جستجو در بازار (قالب‌ها و پکیج‌ها)</label>
            <div class="relative">
                <x-admin-panel::icon name="search" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                <input id="market-search" type="text" wire:model.live.debounce.400ms="search" class="ap-input !pe-9 !py-2" placeholder="نام قالب یا پکیج…">
            </div>
        </div>
    @endif

    {{-- ============ خطای API (پورت pkg-empty-state + جزئیات خطا) ============ --}}
    @if ($this->showsPackages() && $apiError)
        <div class="ap-card flex flex-col items-center gap-2 p-8 text-center">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="triangle-alert" class="size-6" />
            </span>
            <p class="text-sm font-bold">ارتباط با سرور پکیج‌ها برقرار نشد!</p>
            <p class="text-xs text-ink-3 dark:text-night-ink-2">لطفاً چند لحظه دیگر مجدد تلاش کنید یا با پشتیبانی تماس بگیرید.</p>
            <details class="mt-1 w-full max-w-xl rounded-xl border border-red-200 bg-red-50/60 p-3 text-start dark:border-red-500/20 dark:bg-red-500/5">
                <summary class="cursor-pointer text-xs font-bold text-red-600 dark:text-red-400">مشاهده جزئیات خطا</summary>
                <code class="ltr mt-2 block max-h-40 overflow-auto whitespace-pre-wrap font-mono text-[11px] text-red-700 dark:text-red-300">{{ $apiError }}</code>
            </details>
            <button type="button" wire:click="loadPackages" class="ap-btn-outline mt-1 !py-2">
                <x-admin-panel::icon name="rotate-cw" class="size-4" />
                تلاش مجدد
            </button>
        </div>
    @endif

    {{-- ============ نوار پیشرفت نصب‌های در جریان (پورت progress.js) ============ --}}
    @if ($this->showsPackages() && count($pendingSlugs))
        <div wire:poll.4s="refreshPending"
             class="flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-3.5 dark:border-amber-500/25 dark:bg-amber-500/10">
            <svg class="size-5 animate-spin text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
            </svg>
            <div class="flex-1">
                <p class="text-[13px] font-bold text-amber-800 dark:text-amber-300">
                    در حال نصب <span class="fa-digits">{{ count($pendingSlugs) }}</span> پکیج — این نوار به‌صورت خودکار وضعیت را به‌روز می‌کند…
                </p>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-amber-200 dark:bg-amber-500/20">
                    <div class="h-full w-1/3 animate-pulse rounded-full bg-amber-500"></div>
                </div>
            </div>
        </div>
    @endif

    @if ($type === 'package')
    {{-- ============ کارت‌های آماری (پورت pkg-stat — کارت‌های کل/رایگان/پولی مثل فیلتر وضعیت قیمت عمل می‌کنند) ============ --}}
    @php
        // آمار از priceCounts (بازار کامل) — در حالت فیلتر هم عددها ثابت می‌مانند
        $total = $priceCounts['total'] ?? count($packages);
        $freeCount = $priceCounts['free'] ?? collect($packages)->filter(fn ($p) => \App\Livewire\Admin\Packages\PackageIndex::isEffectivelyFree($p))->count();
        $paidCount = $priceCounts['paid'] ?? ($total - $freeCount);
        $installedCount = count($installedMap);
    @endphp
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ([
            ['label' => 'کل پکیج‌ها', 'value' => $total, 'icon' => 'package', 'accent' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400', 'filter' => ''],
            ['label' => 'رایگان', 'value' => $freeCount, 'icon' => 'gift', 'accent' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400', 'filter' => 'free'],
            ['label' => 'نصب‌شده', 'value' => $installedCount, 'icon' => 'check-circle', 'accent' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400', 'filter' => null],
            ['label' => 'پولی', 'value' => $paidCount, 'icon' => 'coins', 'accent' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400', 'filter' => 'paid'],
        ] as $stat)
            @if ($stat['filter'] === null)
                <div class="ap-card flex items-center gap-3 p-4">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $stat['accent'] }}">
                        <x-admin-panel::icon name="{{ $stat['icon'] }}" class="size-5" />
                    </span>
                    <div>
                        <span class="fa-digits block text-lg font-extrabold">{{ number_format($stat['value']) }}</span>
                        <span class="text-[11px] text-ink-3 dark:text-night-ink-2">{{ $stat['label'] }}</span>
                    </div>
                </div>
            @else
                <button type="button" wire:click="setFreeFilter('{{ $stat['filter'] }}')" wire:loading.attr="disabled" wire:target="setFreeFilter"
                        class="ap-card flex items-center gap-3 p-4 text-start transition-all hover:border-brand-400/60 hover:shadow-sm disabled:opacity-60"
                        @class(['!border-brand-500/60 ring-2 ring-brand-500/25 dark:!border-brand-500/50' => $free === $stat['filter']])>
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $stat['accent'] }}">
                        <x-admin-panel::icon name="{{ $stat['icon'] }}" class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <span class="fa-digits block text-lg font-extrabold">{{ number_format($stat['value']) }}</span>
                        <span class="block truncate text-[11px] text-ink-3 dark:text-night-ink-2">{{ $stat['label'] }}</span>
                    </div>
                    @if ($free === $stat['filter'])
                        <span class="ms-auto flex size-5 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white">
                            <x-admin-panel::icon name="check" class="size-3" />
                        </span>
                    @endif
                </button>
            @endif
        @endforeach
    </div>
    @endif

    @if ($type === 'package')
    {{-- ============ جستجو و فیلتر (پورت pkg-filter — دسته‌بندی select مثل قدیمی) ============ --}}
    <div class="ap-card p-4 sm:p-5">
        <form wire:submit="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold" for="pkg-search">جستجو در پکیج‌ها</label>
                <div class="relative">
                    <x-admin-panel::icon name="search" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    <input id="pkg-search" type="text" wire:model.live.debounce.400ms="search" class="ap-input !pe-9 !py-2" placeholder="جستجو در پکیج‌ها…">
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold" for="pkg-category">دسته‌بندی</label>
                <select id="pkg-category" wire:model.live="category" class="ap-input !py-2">
                    <option value="">همه دسته‌ها</option>
                    @foreach (\App\Livewire\Admin\Packages\PackageIndex::$categoryOptions as $catKey => $catLabel)
                        <option value="{{ $catKey }}">{{ $catLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="mb-1.5 block text-xs font-semibold" for="pkg-sort">ترتیب</label>
                    <select id="pkg-sort" wire:model.live="sort" class="ap-input !py-2">
                        <option value="">پیش‌فرض</option>
                        <option value="newest">جدیدترین</option>
                        <option value="popular">محبوب‌ترین</option>
                        <option value="cheapest">ارزان‌ترین</option>
                    </select>
                </div>
                <button type="button" wire:click="resetFilters" class="ap-btn-ghost !px-3 !py-2.5" title="حذف فیلترها">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>
        </form>

        {{-- فیلتر وضعیت قیمت (رایگان بودن) — درخواست کاربر: فیلترهای رایگان بودن --}}
        <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-line pt-4 dark:border-night-line">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-ink-2 dark:text-night-ink-2">
                <x-admin-panel::icon name="coins" class="size-4 text-ink-3 dark:text-night-ink-3" />
                وضعیت قیمت:
            </span>
            <div class="flex items-center gap-1 rounded-xl bg-surface-2 p-1 dark:bg-night-3" role="group" aria-label="فیلتر وضعیت قیمت پکیج‌ها">
                <button type="button" wire:click="setFreeFilter('')" wire:loading.attr="disabled" wire:target="setFreeFilter"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-all"
                        @class([$free === ''
                            ? 'bg-surface text-brand-700 shadow-sm dark:bg-night-2 dark:text-brand-400'
                            : 'text-ink-2 hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-2'])>
                    <x-admin-panel::icon name="list" class="size-3.5" />
                    همه
                </button>
                <button type="button" wire:click="setFreeFilter('free')" wire:loading.attr="disabled" wire:target="setFreeFilter"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-all"
                        @class([$free === 'free'
                            ? 'bg-emerald-500 text-white shadow-sm shadow-emerald-500/30'
                            : 'text-ink-2 hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-2'])>
                    <x-admin-panel::icon name="gift" class="size-3.5" />
                    رایگان
                </button>
                <button type="button" wire:click="setFreeFilter('paid')" wire:loading.attr="disabled" wire:target="setFreeFilter"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-all"
                        @class([$free === 'paid'
                            ? 'bg-amber-500 text-white shadow-sm shadow-amber-500/30'
                            : 'text-ink-2 hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-2'])>
                    <x-admin-panel::icon name="coins" class="size-3.5" />
                    پولی
                </button>
            </div>
            @if ($free !== '')
                <span class="flex items-center gap-1.5 rounded-lg border border-dashed border-line bg-surface-2 px-2.5 py-1 text-[11px] text-ink-2 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="filter" class="size-3.5 text-brand-500" />
                    فقط پکیج‌های {{ \App\Livewire\Admin\Packages\PackageIndex::$freeOptions[$free] }}
                    <button type="button" wire:click="setFreeFilter('')" class="font-bold text-red-500 transition-colors hover:text-red-600" title="حذف فیلتر قیمت">
                        <x-admin-panel::icon name="x" class="size-3.5" />
                    </button>
                </span>
            @endif
        </div>
    </div>
    @endif

    {{-- ============ فاز ۴۴ — بازار قالب‌ها (Templates) — فاز ۴۵: بخش اولِ تب «همه» ============ --}}
    @if ($this->showsTemplates())
        @php
            // فاز ۴۵ — در تب «همه» فقط ۶ قالب اول نمایش داده می‌شود (قالب‌ها اول دیده می‌شوند)
            $tplList = $this->showsAll() ? array_slice($templates, 0, 6) : $templates;
        @endphp

        {{-- فاز ۴۵ — سربرگ بخش قالب‌ها در تب «همه» --}}
        @if ($this->showsAll())
            @php
                $tplTotalForHeader = (int) ($templateMeta['total'] ?? count($templates));
            @endphp
            <div class="flex flex-wrap items-center justify-between gap-3" wire:key="market-tpl-header">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                        <x-admin-panel::icon name="layout-template" class="size-5" />
                    </span>
                    <div>
                        <h2 class="flex items-center gap-2 text-sm font-extrabold">
                            قالب‌ها
                            @if ($tplTotalForHeader)
                                <span class="fa-digits rounded-md bg-surface-2 px-1.5 py-0.5 text-[10px] font-bold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ number_format($tplTotalForHeader) }}</span>
                            @endif
                        </h2>
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">پوسته‌های آماده برای چهره‌ی فروشگاه — خرید و نصب یک‌کلیکی</p>
                    </div>
                </div>
                <button type="button" wire:click="$set('type', 'template')" class="ap-btn-outline !py-2 !text-xs">
                    مشاهده همه قالب‌ها
                    <x-admin-panel::icon name="chevron-left" class="size-3.5" />
                </button>
            </div>
        @endif

        {{-- خطای API قالب‌ها --}}
        @if ($templateError)
            <div class="ap-card flex flex-col items-center justify-center gap-2 p-10 text-center">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-500 dark:bg-red-500/12">
                    <x-admin-panel::icon name="triangle-alert" class="size-6" />
                </span>
                <p class="text-sm font-bold">خطا در ارتباط با سرور قالب‌ها</p>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">{{ $templateError }}</p>
            </div>
        @endif

        {{-- نوار پیشرفت نصب قالب‌ها --}}
        @if (count($templatePendingSlugs))
            <div wire:poll.4s="refreshPendingTemplates" wire:key="tpl-pending"
                 class="ap-card flex items-center gap-3 border-brand-200 p-4 dark:border-brand-500/25">
                <svg class="size-5 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                </svg>
                <div>
                    <p class="text-[13px] font-bold">نصب قالب در جریان است</p>
                    <p class="text-xs text-ink-3 dark:text-night-ink-2">پس از اتمام، قالب به‌صورت خودکار در لیست نمایش داده می‌شود.</p>
                </div>
            </div>
        @endif

        @if ($type === 'template')
        {{-- جستجوی قالب‌ها — فقط تب اختصاصی قالب‌ها (در تب «همه» جستجوی واحد بالا هست) --}}
        <div class="ap-card p-4">
            <form wire:submit="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold" for="tpl-search">جستجو در قالب‌ها</label>
                    <div class="relative">
                        <x-admin-panel::icon name="search" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                        <input id="tpl-search" type="text" wire:model.live.debounce.400ms="search" class="ap-input !pe-9 !py-2" placeholder="نام قالب…">
                    </div>
                </div>
            </form>
        </div>
        @endif

        {{-- شبکه قالب‌ها --}}
        @if (count($tplList))
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3" wire:key="market-tpl-grid">
                @foreach ($tplList as $tpl)
                    @php
                        $tSlug      = $tpl['slug'] ?? '';
                        // فاز ۶۹ — گاردهای اسکالر: فیلد آبجکتی نباید رندر را بترکاند
                        $tNameRaw   = $tpl['name'] ?? $tSlug;
                        $tName      = is_scalar($tNameRaw) && trim((string) $tNameRaw) !== '' ? (string) $tNameRaw : $tSlug;
                        $tVersion   = $tpl['latest_version']['version'] ?? ($tpl['version'] ?? null);
                        // فاز ۶۷ — فیلد واقعی API: thumbnail_url (با fallbackهای قبلی)
                        $tThumbRaw  = $tpl['thumbnail_url'] ?? $tpl['thumbnail'] ?? $tpl['cover'] ?? $tpl['preview_image'] ?? $tpl['image'] ?? null;
                        $tThumb     = is_string($tThumbRaw) && trim($tThumbRaw) !== '' ? $tThumbRaw : null;
                        $tPurchased = (bool) ($tpl['is_purchased'] ?? false);
                        $tSales     = $tpl['purchases_count'] ?? null;
                        $tState     = $installedThemesMap[$tSlug] ?? null;
                        $tPending   = $templatePendingSlugs[$tSlug] ?? null;
                    @endphp

                    <article wire:key="tpl-{{ $tSlug }}"
                             class="group ap-card relative cursor-pointer overflow-hidden p-0 transition-all hover:shadow-card hover:shadow-brand-500/5 hover:ring-1 hover:ring-brand-500/25"
                             wire:click="openTemplateDetail('{{ $tSlug }}')">
                        {{-- فاز ۶۹ — لودینگ روی خود کارت تا کامل شدن مودال جزئیات --}}
                        <div wire:loading.flex wire:target="openTemplateDetail('{{ $tSlug }}')"
                             class="pointer-events-none absolute inset-0 z-20 items-center justify-center bg-white/60 backdrop-blur-[2px] dark:bg-night-1/60"
                             aria-hidden="true">
                            <span class="flex flex-col items-center gap-2">
                                <span class="flex size-12 items-center justify-center rounded-2xl bg-white/95 shadow-lg dark:bg-night-2/95">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" class="size-6 animate-spin text-brand-600 dark:text-brand-400">
                                        <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"></path>
                                    </svg>
                                </span>
                                <span class="text-[11px] font-bold text-ink-2 dark:text-night-ink-2">در حال باز کردن جزئیات…</span>
                            </span>
                        </div>
                        {{-- کاور --}}
                        <div class="relative aspect-video overflow-hidden bg-gradient-to-br from-brand-100 via-teal-50 to-amber-50 dark:from-night-3 dark:via-night-2 dark:to-night-3">
                            @if ($tThumb)
                                <img src="{{ $tThumb }}" alt="پیش‌نمایش قالب {{ $tName }}" loading="lazy"
                                     class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
                            @else
                                <div class="flex size-full items-center justify-center" aria-hidden="true">
                                    <span class="flex size-16 items-center justify-center rounded-2xl bg-white/70 text-2xl font-black text-brand-600 shadow-sm backdrop-blur dark:bg-night-1/70 dark:text-brand-400">{{ mb_substr($tName, 0, 1) }}</span>
                                </div>
                            @endif

                            {{-- نشان‌ها --}}
                            <div class="absolute start-3 top-3 flex flex-wrap gap-1.5">
                                @if ($tState && $tState['is_active'])
                                    <span class="ap-badge bg-emerald-600 text-white shadow">قالب فعال</span>
                                @elseif ($tState && $tState['status'] === 'installed')
                                    <span class="ap-badge bg-surface text-emerald-700 shadow dark:bg-night-2 dark:text-emerald-400">نصب‌شده</span>
                                @endif
                                @if ($tPurchased && ! $tState)
                                    <span class="ap-badge bg-brand-600 text-white shadow">خریداری‌شده</span>
                                @endif
                            </div>

                            @if ($tPending)
                                <div class="absolute inset-0 flex items-center justify-center bg-slate-950/45 backdrop-blur-[2px]">
                                    <span class="flex items-center gap-2 rounded-xl bg-white/95 px-3.5 py-2 text-xs font-bold text-slate-800 shadow-lg">
                                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                                        </svg>
                                        در حال نصب…
                                    </span>
                                </div>
                            @endif

                            {{-- اورلی مشاهده جزئیات --}}
                            <div class="absolute inset-0 flex items-end justify-center bg-gradient-to-t from-slate-950/70 via-slate-950/10 to-transparent p-4 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                <span class="rounded-xl bg-white/95 px-4 py-2 text-xs font-bold text-slate-900 shadow-lg">مشاهده جزئیات و خرید</span>
                            </div>
                        </div>

                        {{-- بدنه --}}
                        <div class="space-y-2.5 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-sm font-extrabold leading-6">{{ $tName }}</h3>
                                @if ($tVersion)
                                    <span class="ap-badge bg-surface-2 font-mono text-[10px] text-ink-2 dark:bg-night-3 dark:text-night-ink-2" dir="ltr">v{{ $tVersion }}</span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between gap-2 text-[11px] text-ink-3 dark:text-night-ink-2">
                                @if ($tSales !== null)
                                    <span class="flex items-center gap-1">
                                        <x-admin-panel::icon name="shopping-bag" class="size-3.5" />
                                        {{ number_format((int) $tSales) }} فروش
                                    </span>
                                @endif
                                @if ($tState && $tState['version'])
                                    <span class="font-mono" dir="ltr">نصب‌شده: v{{ $tState['version'] }}</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($type === 'template')
            {{-- صفحه‌بندی قالب‌ها — فقط تب اختصاصی قالب‌ها --}}
            @php
                $tLastPage = (int) ($templateMeta['last_page'] ?? 1);
                $tCurrent  = (int) ($templateMeta['current_page'] ?? $page);
            @endphp
            @if ($tLastPage > 1)
                <div class="flex items-center justify-center gap-1.5">
                    <nav class="flex items-center gap-1" aria-label="صفحه‌بندی قالب‌ها">
                        <button type="button" wire:click="previousPage" @disabled($tCurrent <= 1)
                                class="ap-btn-outline !px-3 !py-2 disabled:opacity-40" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                        @for ($i = 1; $i <= $tLastPage; $i++)
                            @if (max(1, $tCurrent - 2) <= $i && $i <= min($tLastPage, $tCurrent + 2))
                                @if ($i === $tCurrent)
                                    <span class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-[13px] font-bold text-white">{{ $i }}</span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $i }})"
                                            class="flex size-9 items-center justify-center rounded-xl border border-line bg-surface text-[13px] font-bold text-ink-2 transition-colors hover:bg-surface-3 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">{{ $i }}</button>
                                @endif
                            @endif
                        @endfor
                        <button type="button" wire:click="nextPage" @disabled($tCurrent >= $tLastPage)
                                class="ap-btn-outline !px-3 !py-2 disabled:opacity-40" aria-label="صفحه بعد">
                            <x-admin-panel::icon name="chevron-left" class="size-4" />
                        </button>
                    </nav>
                </div>
            @endif
            @endif
        @elseif (! $templateError)
            <div class="ap-card flex flex-col items-center justify-center gap-2 p-10 text-center">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                    <x-admin-panel::icon name="layout-template" class="size-6" />
                </span>
                <p class="text-sm font-bold">قالبی برای نمایش وجود ندارد</p>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">فیلتر جستجو را تغییر دهید یا بعداً دوباره بررسی کنید.</p>
            </div>
        @endif
    @endif

    {{-- ============ شبکه پکیج‌ها (پورت pkg-grid / pkg-card) ============ --}}
    @if ($this->showsPackages())
        {{-- فاز ۴۵ — سربرگ بخش پکیج‌ها در تب «همه» --}}
        @if ($this->showsAll())
            @php
                $pkgTotalForHeader = (int) ($paginationMeta['total'] ?? count($packages));
            @endphp
            <div class="flex flex-wrap items-center justify-between gap-3" wire:key="market-pkg-header">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                        <x-admin-panel::icon name="package" class="size-5" />
                    </span>
                    <div>
                        <h2 class="flex items-center gap-2 text-sm font-extrabold">
                            پکیج‌ها
                            @if ($pkgTotalForHeader)
                                <span class="fa-digits rounded-md bg-surface-2 px-1.5 py-0.5 text-[10px] font-bold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ number_format($pkgTotalForHeader) }}</span>
                            @endif
                        </h2>
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">افزونه‌های آماده برای گسترش فروشگاه — پرداخت، اعلان، سئو و…</p>
                    </div>
                </div>
                <button type="button" wire:click="$set('type', 'package')" class="ap-btn-outline !py-2 !text-xs">
                    مشاهده همه پکیج‌ها
                    <x-admin-panel::icon name="chevron-left" class="size-3.5" />
                </button>
            </div>
        @endif

        @if (count($packages))
        @php
            // فاز ۴۵ — در تب «همه» فقط ۶ پکیج اول نمایش داده می‌شود (کاربر گيج نشود)
            $visiblePackages = $this->showsAll() ? array_slice($packages, 0, 6) : $packages;
        @endphp
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3" wire:key="market-pkg-grid">
            @foreach ($visiblePackages as $pkg)
                @php
                    $slug = $pkg['slug'] ?? '';
                    $isPurchased = in_array($slug, $purchasedSlugs);
                    $installedVersion = $installedMap[$slug] ?? null;
                    $isInstalled = $installedVersion !== null;
                    $hasUpdate = $this->hasUpdate($slug, $pkg, $installedVersion);
                    $thumbnail = \App\Support\PackageMedia::thumbnailUrl($pkg);
                    $pendingState = $pendingSlugs[$slug] ?? null;
                    $minPrice = $pkg['min_price'] ?? ($pkg['price'] ?? 0);
                    $isFree = $pkg['is_free'] ?? false;
                    $hasFreePlan = $pkg['has_free_plan'] ?? false;
                    $hasActiveSub = ($pkg['subscription']['is_free_with_subscription'] ?? false);
                @endphp
                {{-- فاز ۲۸: مثل پنل قدیمی — کلیک روی هر جای کارت (خارج از دکمه/لینک) مودال جزئیات را باز می‌کند --}}
                <div class="ap-card group relative flex cursor-pointer flex-col overflow-hidden" wire:key="pkg-{{ $slug }}" x-bind:data-slug="{{ \Illuminate\Support\Js::from($slug) }}"
                     @click="if (!$event.target.closest('button, a, [wire\\:click], input, label')) $wire.openDetail('{{ $slug }}')">
                    {{-- فاز ۶۹ — لودینگ روی خود کارت تا کامل شدن مودال جزئیات --}}
                    <div wire:loading.flex wire:target="openDetail('{{ $slug }}')"
                         class="pointer-events-none absolute inset-0 z-20 items-center justify-center bg-white/60 backdrop-blur-[2px] dark:bg-night-1/60"
                         aria-hidden="true">
                        <span class="flex flex-col items-center gap-2">
                            <span class="flex size-12 items-center justify-center rounded-2xl bg-white/95 shadow-lg dark:bg-night-2/95">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" class="size-6 animate-spin text-brand-600 dark:text-brand-400">
                                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"></path>
                                </svg>
                            </span>
                            <span class="text-[11px] font-bold text-ink-2 dark:text-night-ink-2">در حال باز کردن جزئیات…</span>
                        </span>
                    </div>
                    {{-- رسانه کارت --}}
                    <div class="relative h-40 w-full overflow-hidden bg-surface-2 dark:bg-night-3">
                        @if ($thumbnail)
                            <img src="{{ $thumbnail }}" alt="{{ $pkg['name'] ?? $slug }}" loading="lazy"
                                 class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                 onerror="this.style.display='none'">
                        @else
                            <div class="flex size-full items-center justify-center text-ink-3/50 dark:text-night-ink-2/40">
                                <x-admin-panel::icon name="package-2" class="size-12" />
                            </div>
                        @endif

                        {{-- نشان‌ها --}}
                        <div class="absolute start-3 top-3 flex flex-wrap gap-1.5">
                            @if (! $isFree && $hasActiveSub)
                                <span class="ap-badge bg-violet-500 text-white shadow">
                                    <x-admin-panel::icon name="award" class="size-3" /> رایگان با اشتراک
                                </span>
                            @elseif ($isPurchased && ! $isFree)
                                <span class="ap-badge bg-sky-500 text-white shadow">خریداری‌شده</span>
                            @elseif ($isFree)
                                <span class="ap-badge bg-emerald-500 text-white shadow">رایگان</span>
                            @else
                                <span class="ap-badge bg-amber-500 text-white shadow">پولی</span>
                            @endif
                        </div>

                        {{-- مُهر نصب‌شده / در حال نصب (فاز ۲۶: «در صف» حذف شد — تا نتیجه، وضعیت «در حال نصب» است) --}}
                        @if ($pendingState)
                            <div class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-2 bg-slate-950/60 py-1.5 text-[11px] font-bold text-white backdrop-blur-sm">
                                <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                                </svg>
                                در حال نصب...
                            </div>
                        @elseif ($isInstalled)
                            <div class="absolute end-3 bottom-3 flex items-center gap-1 rounded-full bg-emerald-500 px-2.5 py-1 text-[11px] font-bold text-white shadow">
                                <x-admin-panel::icon name="check" class="size-3" />
                                نصب‌شده
                            </div>
                        @endif

                        {{-- اورلی مشاهده جزئیات — فاز ۲۶: مودال جزئیات مثل پنل قدیمی (تک‌صفحه حذف شد) --}}
                        <button type="button" wire:click="openDetail('{{ $slug }}')"
                                class="absolute inset-0 flex items-center justify-center gap-2 bg-slate-950/55 text-[13px] font-bold text-white opacity-0 backdrop-blur-[2px] transition-opacity duration-200 group-hover:opacity-100">
                            <x-admin-panel::icon name="eye" class="size-4.5" />
                            مشاهده جزئیات
                        </button>
                    </div>

                    {{-- بدنه کارت --}}
                    <div class="flex flex-1 flex-col p-4">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="min-w-0 text-sm font-bold leading-6">{{ $pkg['name'] ?? $slug }}</h3>
                            <span class="fa-digits shrink-0 rounded-lg bg-surface-2 px-2 py-0.5 text-[10px] font-bold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                v{{ $pkg['latest_version']['version'] ?? $pkg['version'] ?? '—' }}
                            </span>
                        </div>

                        {{-- توضیحات — مثل قدیمی HTML غنی و پاک‌سازی‌شده رندر می‌شود --}}
                        <div class="ap-rich line-clamp-3 mt-1.5 min-h-12 text-xs leading-5 text-ink-3 dark:text-night-ink-2">
                            {!! sanitize_package_html($pkg['short_description'] ?? ($pkg['description'] ?? '')) ?: 'بدون توضیحات' !!}
                        </div>

                        {{-- متا --}}
                        <div class="mt-3 flex flex-wrap items-center gap-1.5 text-[11px]">
                            @if (!empty($pkg['author']))
                                <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="user" class="size-3" /> {{ $pkg['author'] }}
                                </span>
                            @endif
                            @if (!empty($pkg['category']))
                                <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                    <x-admin-panel::icon name="tag" class="size-3" /> {{ $pkg['category'] }}
                                </span>
                            @endif
                        </div>

                        {{-- فوتر: قیمت + اکشن (پورت pkg-card-footer و حالت‌های قدیمی) --}}
                        <div class="mt-4 flex items-end justify-between gap-2 border-t border-line pt-3.5 dark:border-night-line">
                            <div class="min-w-0">
                                @if ($isFree || ($hasFreePlan && ! $hasActiveSub))
                                    <span class="text-sm font-extrabold text-emerald-600">رایگان</span>
                                @elseif ($hasActiveSub)
                                    <span class="flex items-center gap-1 text-sm font-extrabold text-violet-600 dark:text-violet-400">
                                        <x-admin-panel::icon name="award" class="size-4" />
                                        رایگان با اشتراک
                                    </span>
                                    @if (!empty($pkg['subscription']['less_than_one_day']))
                                        <small class="block text-[10px] text-ink-3 dark:text-night-ink-2">کمتر از ۱ روز باقی‌مانده</small>
                                    @elseif (!empty($pkg['subscription']['days_remaining']))
                                        <small class="fa-digits block text-[10px] text-ink-3 dark:text-night-ink-2">{{ $pkg['subscription']['days_remaining'] }} روز باقی‌مانده</small>
                                    @endif
                                @else
                                    <small class="block text-[10px] text-ink-3 dark:text-night-ink-2">از</small>
                                    <span class="fa-digits text-sm font-extrabold">{{ number_format($minPrice) }}</span>
                                    <span class="text-[10px] text-ink-3 dark:text-night-ink-2">تومان</span>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-1.5">
                                @if ($pendingState)
                                    {{-- فاز ۲۶: تا مشخص شدن نتیجه‌ی صف، پکیج «در حال نصب» است --}}
                                    <button type="button" wire:click="openDetail('{{ $slug }}')" class="ap-btn !px-3 !py-1.5 !text-xs opacity-80">
                                        <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                                        </svg>
                                        در حال نصب...
                                    </button>
                                @elseif ($hasUpdate)
                                    <button type="button" wire:click="openDetail('{{ $slug }}')" class="ap-btn-primary !px-3 !py-1.5 !text-xs">
                                        <x-admin-panel::icon name="arrow-up" class="size-3.5" /> آپدیت
                                    </button>
                                @elseif ($isInstalled)
                                    <span class="flex items-center gap-1 text-[11px] font-bold text-emerald-600">
                                        <x-admin-panel::icon name="check" class="size-3.5" /> به‌روز
                                    </span>
                                @elseif ($hasActiveSub)
                                    <button type="button" x-on:click="ask({{ \Illuminate\Support\Js::from($pkg)->toHtml() }})"
                                            class="ap-btn-primary !px-3 !py-1.5 !text-xs">
                                        <x-admin-panel::icon name="award" class="size-3.5" /> نصب با اشتراک
                                    </button>
                                @elseif ($isPurchased)
                                    <button type="button" x-on:click="ask({{ \Illuminate\Support\Js::from($pkg)->toHtml() }})"
                                            class="ap-btn-primary !px-3 !py-1.5 !text-xs">
                                        <x-admin-panel::icon name="shield" class="size-3.5" /> برسی و نصب
                                    </button>
                                @else
                                    <button type="button" x-on:click="ask({{ \Illuminate\Support\Js::from($pkg)->toHtml() }})"
                                            class="ap-btn-primary !px-3 !py-1.5 !text-xs">
                                        <x-admin-panel::icon name="download" class="size-3.5" /> نصب
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- فاز ۴۵ — صفحه‌بندی فقط در تب اختصاصی پکیج‌ها --}}
        @if ($type === 'package' && ! count($packages) && ! $apiError)
            <div class="ap-card flex flex-col items-center justify-center gap-2 p-10 text-center">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                    <x-admin-panel::icon name="package" class="size-6" />
                </span>
                @if ($free === 'free')
                    <p class="text-sm font-bold">پکیج رایگانی یافت نشد</p>
                    <p class="text-xs text-ink-3 dark:text-night-ink-2">همه‌ی پکیج‌های این بخش پولی هستند یا فیلترها را تغییر دهید.</p>
                @elseif ($free === 'paid')
                    <p class="text-sm font-bold">پکیج پولی یافت نشد</p>
                    <p class="text-xs text-ink-3 dark:text-night-ink-2">پکیج‌های این بخش رایگان هستند یا فیلترها را تغییر دهید.</p>
                @else
                    @if (!empty($subscriptionSummary) && ! ($subscriptionSummary['has_active_subscription'] ?? false))
                        {{-- فاز ۱۰۰ — لیست خالی به‌خاطر نبود اشتراک فعال است، نه نبود پکیج؛ کاربر باید علت را بداند --}}
                        <p class="text-sm font-bold">نمایش پکیج‌ها به اشتراک فعال نیاز دارد</p>
                        <p class="text-xs text-ink-3 dark:text-night-ink-2">به دلیل نداشتن اشتراک فعال، پکیج‌های مشمول اشتراک نمایش داده نمی‌شوند. برای مشاهدهٔ پکیج‌ها، اشتراک خود را خریداری یا تمدید کنید.</p>
                        {{-- فاز ۱۰۷ — لینک به صفحهٔ خرید/تمدید اشتراک روی سرور مارکت --}}
                        <a href="{{ config('packages.api.subscriptions_url') }}" target="_blank" rel="noopener" class="ap-btn-primary mt-2 !py-2">
                            <x-admin-panel::icon name="shopping-cart" class="size-4" />
                            خرید یا تمدید اشتراک
                        </a>
                    @else
                        <p class="text-sm font-bold">پکیجی برای نمایش وجود ندارد</p>
                        <p class="text-xs text-ink-3 dark:text-night-ink-2">فیلترها را تغییر دهید یا اتصال به سرور پکیج‌ها را بررسی کنید.</p>
                    @endif
                @endif
                @if ($free !== '' || $search !== '' || $category !== '' || $sort !== '')
                    <button type="button" wire:click="resetFilters" class="ap-btn-outline mt-1 !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        حذف همه‌ی فیلترها
                    </button>
                @endif
            </div>
        @endif

        @if ($type === 'package')
        {{-- صفحه‌بندی سرور دست‌ساز بر اساس meta پاسخ API --}}
        @php
            $lastPage = (int) ($paginationMeta['last_page'] ?? $paginationMeta['lastPage'] ?? 1);
            $currentPage = (int) ($paginationMeta['current_page'] ?? $paginationMeta['currentPage'] ?? $this->page);
            $totalItems = (int) ($paginationMeta['total'] ?? count($packages));
        @endphp
        @if ($lastPage > 1)
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    صفحه <span class="fa-digits">{{ $currentPage }}</span> از <span class="fa-digits">{{ $lastPage }}</span>
                    — <span class="fa-digits">{{ number_format($totalItems) }}</span> پکیج
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($currentPage <= 1)
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" wire:loading.attr="disabled" wire:target="previousPage, nextPage, gotoPage"
                                class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 disabled:opacity-40 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @for ($i = 1; $i <= $lastPage; $i++)
                        @if (max(1, $currentPage - 2) <= $i && $i <= min($lastPage, $currentPage + 2))
                            @if ($i === $currentPage)
                                <span wire:key="pkgpg-{{ $i }}" class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $i }}</span>
                            @else
                                <button wire:key="pkgpg-{{ $i }}" wire:click="gotoPage({{ $i }})"
                                        class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                    {{ $i }}
                                </button>
                            @endif
                        @endif
                    @endfor

                    @if ($currentPage >= $lastPage)
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-left" class="size-4" /></span>
                    @else
                        <button wire:click="nextPage" wire:loading.attr="disabled" wire:target="previousPage, nextPage, gotoPage"
                                class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 disabled:opacity-40 dark:hover:bg-night-3" aria-label="صفحه بعد">
                            <x-admin-panel::icon name="chevron-left" class="size-4" />
                        </button>
                    @endif
                </nav>
            </div>
        @endif
    @elseif (! $apiError && ! count($packages))
        {{-- فاز ۵۲: کارت خالی پکیج‌ها فقط وقتی واقعاً پکیجی نیست — در تب «همه» با رندر شدن
             گرید پکیج‌ها، این کارت نباید زیر پکیج‌ها دیده شود (باگ گزارش‌شده توسط کاربر)
             فاز ۱۰۷ — درخواست کاربر: پیام «اشتراک ندارید / تمدید کنید» فقط یک بار و در بنر
             بالای صفحه رندر می‌شود و دیگر داخل بخش پکیج‌های تب «همه» تکرار نمی‌شود؛
             پس این کارت فقط وقتی رندر می‌شود که اشتراک فعال هست (و واقعاً نتیجه‌ای نیست)
             یا فیلتری فعال است — در هر دو حالت علت خالی‌بودن، نبود اشتراک نیست. --}}
        @php
            $hasFilters = ($free !== '' || $search !== '' || $category !== '' || $sort !== '');
            $hasActiveSub = ! empty($subscriptionSummary) && ($subscriptionSummary['has_active_subscription'] ?? false);
        @endphp
        @if ($hasActiveSub || $hasFilters)
        <div class="ap-card flex flex-col items-center justify-center gap-2 p-10 text-center">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                <x-admin-panel::icon name="package" class="size-6" />
            </span>
            @if ($free === 'free')
                <p class="text-sm font-bold">پکیج رایگانی یافت نشد</p>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">همه‌ی پکیج‌های این بخش پولی هستند یا فیلترها را تغییر دهید.</p>
            @elseif ($free === 'paid')
                <p class="text-sm font-bold">پکیج پولی یافت نشد</p>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">پکیج‌های این بخش رایگان هستند یا فیلترها را تغییر دهید.</p>
            @else
                <p class="text-sm font-bold">پکیجی برای نمایش وجود ندارد</p>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">فیلترها را تغییر دهید یا اتصال به سرور پکیج‌ها را بررسی کنید.</p>
            @endif
            @if ($hasFilters)
                <button type="button" wire:click="resetFilters" class="ap-btn-outline mt-1 !py-2">
                    <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                    حذف همه‌ی فیلترها
                </button>
            @endif
        </div>
        @endif
    @endif
    @endif
    {{-- ============ مودال تأیید نصب (پورت install-confirm-modal — انتخاب پلن/لایسنس/اشتراک) ============ --}}
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-[90] flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-label="تأیید نصب پکیج"
         x-on:keydown.escape.window="! busy && (modalOpen = false)">
        <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" x-show="modalOpen" x-on:click="! busy && (modalOpen = false)" x-transition.opacity aria-hidden="true"></div>

        <div x-show="modalOpen"
             x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2">

            {{-- هدر --}}
            <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                        <x-admin-panel::icon name="download" class="size-4" />
                    </span>
                    نصب پکیج
                </h3>
                <button type="button" x-on:click="! busy && (modalOpen = false)" class="ap-btn-ghost !p-2" title="بستن">
                    <x-admin-panel::icon name="x" class="size-4.5" />
                </button>
            </div>

            {{-- بدنه --}}
            <div class="flex-1 overflow-y-auto p-5" x-show="pkg">
                <p class="text-sm">
                    آیا از نصب پکیج <strong class="text-brand-600 dark:text-brand-400" x-text="pkg?.name"></strong> مطمئن هستید؟
                </p>

                {{-- بررسی وضعیت خرید/اشتراک --}}
                <div x-show="checking" class="mt-4 flex items-center gap-2 rounded-xl border border-line bg-surface-2 p-3.5 text-[13px] text-ink-2 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                    <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                    </svg>
                    در حال بررسی وضعیت خرید و اشتراک…
                </div>

                {{-- هشدار انقضای لایسنس/اشتراک (پورت confirm-license-warning) --}}
                <div x-show="warning" x-cloak class="mt-4 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-[13px] text-amber-800 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-300">
                    <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-4 shrink-0" />
                    <span x-text="warning"></span>
                </div>

                {{-- نصب مجدد با لایسنس فعال (پورت confirm-license-section) --}}
                <div x-show="mode === 'license'" x-cloak class="mt-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/25 dark:bg-emerald-500/10">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white">
                        <x-admin-panel::icon name="shield" class="size-4.5" />
                    </span>
                    <div class="text-[13px]">
                        <p class="flex items-center gap-1.5 font-bold text-emerald-700 dark:text-emerald-400">
                            <x-admin-panel::icon name="check-circle" class="size-4" />
                            قبلاً خریداری شده — بدون پرداخت نصب می‌شود
                        </p>
                        <p class="mt-1 text-emerald-700/80 dark:text-emerald-400/80">
                            لایسنس شما برای این پکیج فعال است
                            <span x-show="license?.expiry_label" x-text="license?.expiry_label"></span>
                        </p>
                    </div>
                </div>

                {{-- نصب با اشتراک فعال (پورت confirm-subscription-section) --}}
                <div x-show="mode === 'subscription'" x-cloak class="mt-4 flex items-start gap-3 rounded-xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-500/25 dark:bg-violet-500/10">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-violet-500 text-white">
                        <x-admin-panel::icon name="award" class="size-4.5" />
                    </span>
                    <div class="text-[13px]">
                        <p class="flex items-center gap-1.5 font-bold text-violet-700 dark:text-violet-400">
                            <x-admin-panel::icon name="check-circle" class="size-4" />
                            این پکیج با اشتراک شما رایگان است
                        </p>
                        <p class="mt-1 text-violet-700/80 dark:text-violet-400/80">
                            طرح: <strong x-text="subscription?.plan_name || 'اشتراک'"></strong>
                            <span x-show="subscription?.expiry_label" x-text="subscription?.expiry_label"></span>
                        </p>
                    </div>
                </div>

                {{-- انتخاب طرح قیمت‌گذاری (پورت confirm-plans-section) --}}
                <div x-show="mode === 'paid' && plans.length" x-cloak class="mt-4">
                    <h4 class="mb-2.5 flex items-center gap-1.5 text-[13px] font-bold">
                        <x-admin-panel::icon name="tag" class="size-4 text-brand-600 dark:text-brand-400" />
                        انتخاب طرح قیمت‌گذاری
                    </h4>
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        <template x-for="plan in plans" :key="plan.id">
                            <button type="button" x-on:click="pick(plan)"
                                    class="relative rounded-xl border-2 p-3.5 text-start transition-all"
                                    :class="planId === plan.id
                                        ? 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10'
                                        : 'border-line bg-surface hover:border-brand-300 dark:border-night-line dark:bg-night-3 dark:hover:border-brand-500/40'">
                                <span class="absolute end-3 top-3 flex size-4 items-center justify-center rounded-full border-2 transition-colors"
                                      :class="planId === plan.id ? 'border-brand-500 bg-brand-500' : 'border-line dark:border-night-line'">
                                    <span x-show="planId === plan.id" class="size-1.5 rounded-full bg-white"></span>
                                </span>
                                <p class="text-[13px] font-bold" x-text="plan.name"></p>
                                <p class="mt-0.5 flex items-center gap-1 text-[11px] text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="clock" class="size-3" />
                                    <span x-text="plan.duration"></span>
                                </p>
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400" x-show="plan.is_one_time">یک‌بار مصرف</span>
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400" x-show="plan.is_cheapest">ارزان‌ترین</span>
                                </div>
                                <div class="mt-2">
                                    <template x-if="plan.final_price === 0">
                                        <span class="text-sm font-extrabold text-emerald-600">رایگان</span>
                                    </template>
                                    <template x-if="plan.final_price > 0">
                                        <span class="text-sm">
                                            <span class="fa-digits text-ink-3 line-through dark:text-night-ink-2" x-show="plan.has_discount" x-text="plan.price_label"></span>
                                            <span class="fa-digits ms-1 font-extrabold" x-text="plan.final_label"></span>
                                            <span class="ms-0.5 text-[10px] text-ink-3 dark:text-night-ink-2">تومان</span>
                                        </span>
                                    </template>
                                </div>
                                <p class="mt-1.5 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2" x-show="plan.description" x-text="plan.description"></p>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- اطلاعات پرداخت (وقتی پلنی نیست) (پورت confirm-payment-info) --}}
                <div x-show="mode === 'paid' && ! plans.length" x-cloak class="mt-4 flex items-center gap-2 rounded-xl border border-sky-200 bg-sky-50 p-3.5 text-[13px] text-sky-800 dark:border-sky-500/25 dark:bg-sky-500/10 dark:text-sky-300">
                    <x-admin-panel::icon name="info" class="size-4 shrink-0" />
                    <span>
                        مبلغ قابل پرداخت:
                        <strong class="fa-digits" x-text="pkg?.min_price ? formatPrice(pkg.min_price) : 'رایگان'"></strong>
                        <span x-show="pkg?.min_price">تومان</span>
                    </span>
                </div>
            </div>

            {{-- فوتر --}}
            <div class="flex items-center justify-end gap-2.5 border-t border-line px-5 py-4 dark:border-night-line">
                <button type="button" x-on:click="! busy && (modalOpen = false)" class="ap-btn-ghost" x-bind:disabled="busy">انصراف</button>
                <button type="button" x-on:click="confirmInstall()" x-bind:disabled="busy || checking || (mode === 'paid' && plans.length && ! planId)"
                        class="ap-btn-primary min-w-36">
                    <span x-show="! busy" class="flex items-center gap-2">
                        <x-admin-panel::icon name="download" class="size-4" />
                        <span x-text="confirmLabel"></span>
                    </span>
                    <span x-show="busy" x-cloak class="flex items-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                        </svg>
                        در حال ارسال…
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- ============ نتیجه بررسی آپدیت‌ها (پورت مودال check-updates قدیمی) ============ --}}
    @if (count($updateResults))
        <div class="ap-card overflow-hidden">
            <div class="border-b border-line p-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="refresh-cw" class="size-4 text-brand-600" />
                    آپدیت‌های موجود (<span class="fa-digits">{{ count($updateResults) }}</span>)
                </h3>
            </div>
            <ul class="divide-y divide-line dark:divide-night-line">
                @foreach ($updateResults as $up)
                    <li class="flex flex-wrap items-center justify-between gap-2 p-4 text-sm" wire:key="up-{{ $up['slug'] }}">
                        <div>
                            <span class="font-bold">{{ $up['name'] }}</span>
                            <span class="ltr ms-2 text-xs text-ink-3 dark:text-night-ink-2">{{ $up['slug'] }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                فعلی: {{ $up['current'] }}
                            </span>
                            <span class="fa-digits ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                                جدید: {{ $up['latest'] }}
                            </span>
                            <a href="{{ route('np.packages.installed') }}" class="ap-btn-outline !px-3 !py-1 !text-[11px]">
                                آپدیت از پکیج‌های نصب‌شده
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ============ مودال نتیجه پرداخت (پورت payment-result-modal قدیمی) ============ --}}
    @if ($paymentResult)
        <div x-show="paymentModal" x-cloak x-init="paymentModal = true"
             class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-on:click="paymentModal=false" x-transition.opacity aria-hidden="true"></div>
            <div x-on:click.stop
                 class="relative w-full max-w-md overflow-hidden rounded-2xl border border-line bg-surface p-6 text-center shadow-pop dark:border-night-line dark:bg-night-2"
                 role="dialog" aria-modal="true">
                <div class="pointer-events-none absolute -top-12 start-1/2 size-40 -translate-x-1/2 rounded-full blur-3xl"
                     :class="paymentOk ? 'bg-emerald-400/20' : 'bg-red-400/20'"></div>
                @php
                    $ok = ($paymentResult['status'] ?? '') === 'success';
                @endphp
                <span class="relative mx-auto flex size-14 items-center justify-center rounded-full text-white shadow-lg {{ $ok ? 'bg-emerald-500 shadow-emerald-500/30' : 'bg-red-500 shadow-red-500/30' }}">
                    <x-admin-panel::icon name="{{ $ok ? 'check' : 'x' }}" class="size-7" />
                </span>
                <h3 class="mt-4 text-base font-bold">{{ $paymentResult['title'] ?? '' }}</h3>
                <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">{{ $paymentResult['message'] ?? '' }}</p>

                {{-- رسید پرداخت (پورت pay-receipt) --}}
                @if (!empty($paymentResult['package_name']) || !empty($paymentResult['amount']) || !empty($paymentResult['transaction_id']) || !empty($paymentResult['license_key']))
                    <div class="mt-4 space-y-1.5 rounded-xl border border-line bg-surface-2/60 p-3.5 text-[12px] dark:border-night-line dark:bg-night-3/60">
                        @if (!empty($paymentResult['package_name']))
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-ink-3 dark:text-night-ink-2">پکیج</span>
                                <span class="font-bold">{{ $paymentResult['package_name'] }}</span>
                            </div>
                        @endif
                        @if (!empty($paymentResult['amount']))
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-ink-3 dark:text-night-ink-2">مبلغ پرداخت‌شده</span>
                                <span class="fa-digits font-bold">{{ number_format($paymentResult['amount']) }} تومان</span>
                            </div>
                        @endif
                        @if (!empty($paymentResult['transaction_id']))
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-ink-3 dark:text-night-ink-2">کد پیگیری</span>
                                <span class="ltr font-mono text-[11px] font-bold">{{ $paymentResult['transaction_id'] }}</span>
                            </div>
                        @endif
                        @if (!empty($paymentResult['license_key']))
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-ink-3 dark:text-night-ink-2">کد لایسنس</span>
                                <span class="ltr flex items-center gap-1 font-mono text-[11px] font-bold">
                                    {{ $paymentResult['license_key'] }}
                                    <button type="button" x-data="{ copied: false }"
                                            x-on:click="navigator.clipboard && navigator.clipboard.writeText(@js($paymentResult['license_key'])); copied = true; setTimeout(() => copied = false, 1800)"
                                            class="text-brand-600 hover:text-brand-700 dark:text-brand-400" title="کپی">
                                        <x-admin-panel::icon name="check" class="size-3.5 text-emerald-500" x-show="copied" x-cloak />
                                        <x-admin-panel::icon name="copy" class="size-3.5" x-show="! copied" />
                                    </button>
                                </span>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="mt-5">
                    <button type="button" x-on:click="paymentModal=false" class="ap-btn-primary w-full">بستن</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ====================== --}}
    {{-- فاز ۲۶ — مودال جزئیات پکیج (پورت package-detail-modal + modal.js قدیمی) --}}
    {{-- ====================== --}}
    @if ($detailOpen)
        <div class="fixed inset-0 z-[90] flex items-center justify-center p-4"
             x-on:keydown.escape.window="$wire.closeDetail()" role="dialog" aria-modal="true" aria-label="جزئیات پکیج">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" wire:click="closeDetail" aria-hidden="true"></div>

            {{-- فاز ۲۸: مثل مودال پنل قدیمی (modal-xl) — عرض ۱۱۵۲px --}}
            <div class="relative flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2">

                {{-- هدر --}}
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4 dark:border-night-line">
                    <h3 class="flex min-w-0 items-center gap-2 text-sm font-bold">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                            <x-admin-panel::icon name="package" class="size-4" />
                        </span>
                        <span class="truncate">
                            {{ $detail['name'] ?? ($detailLoading ? 'در حال دریافت اطلاعات…' : 'جزئیات پکیج') }}
                        </span>
                    </h3>
                    <button type="button" wire:click="closeDetail" class="ap-btn-ghost !p-2" title="بستن (Esc)">
                        <x-admin-panel::icon name="x" class="size-4.5" />
                    </button>
                </div>

                {{-- بدنه --}}
                <div class="flex-1 overflow-y-auto p-5">
                    @if ($detailLoading)
                        <div class="flex flex-col items-center justify-center gap-3 py-14">
                            <svg class="size-8 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                            </svg>
                            <p class="text-[13px] font-semibold text-ink-2 dark:text-night-ink-2">در حال دریافت اطلاعات پکیج…</p>
                        </div>
                    @elseif ($detailError)
                        <div class="flex flex-col items-center gap-3 py-10 text-center">
                            <span class="flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400">
                                <x-admin-panel::icon name="triangle-alert" class="size-6" />
                            </span>
                            <p class="text-sm font-bold">خطا در دریافت اطلاعات پکیج</p>
                            <code class="ltr max-w-lg overflow-auto whitespace-pre-wrap rounded-lg bg-red-50 p-2 text-[11px] text-red-700 dark:bg-red-500/10 dark:text-red-300">{{ $detailError }}</code>
                            <button type="button" wire:click="closeDetail" class="ap-btn-outline mt-1 !py-2">بستن</button>
                        </div>
                    @elseif ($detail)
                        @php
                            $dPlans = $detail['active_pricing_plans'] ?? $detail['pricing_plans'] ?? $detail['plans'] ?? [];
                            $dInstalled = $detail['installed'] ?? null;
                            $dImages = $detail['images'] ?? [];
                            $dChangelog = $detail['changelog'] ?? null;
                            $dVersions = $detail['versions'] ?? [];
                            $dThumb = \App\Support\PackageMedia::thumbnailUrl($detail);
                        @endphp

                        {{-- هیرو --}}
                        <div class="flex flex-wrap items-start gap-4 rounded-2xl border border-line bg-gradient-to-l from-brand-500/8 to-transparent p-4 dark:border-night-line dark:from-brand-500/8">
                            @if ($dThumb)
                                <img src="{{ $dThumb }}" alt="{{ $detail['name'] ?? '' }}" class="size-20 shrink-0 rounded-xl object-cover"
                                     onerror="this.style.display='none'">
                            @else
                                <span class="flex size-20 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/12 dark:text-brand-400">
                                    <x-admin-panel::icon name="package" class="size-9" />
                                </span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-base font-extrabold">{{ $detail['name'] ?? '' }}</h4>
                                    <span class="fa-digits rounded-lg bg-surface-2 px-2 py-0.5 text-[10px] font-bold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                        v{{ $detail['latestVersion'] ?? '—' }}
                                    </span>
                                    @if (!empty($detail['is_free']))
                                        <span class="ap-badge bg-emerald-500 text-white">رایگان</span>
                                    @else
                                        <span class="ap-badge bg-amber-500 text-white">پولی</span>
                                    @endif
                                    @if (!empty($detail['subscription']['is_free_with_subscription']))
                                        <span class="ap-badge bg-violet-500 text-white">
                                            <x-admin-panel::icon name="award" class="size-3" /> رایگان با اشتراک
                                        </span>
                                    @endif
                                    @if ($dInstalled && $dInstalled['status'] === 'installed')
                                        <span class="ap-badge bg-brand-600 text-white">نصب‌شده v{{ $dInstalled['version'] }}</span>
                                    @endif
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px]">
                                    @if (!empty($detail['author']))
                                        <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                            <x-admin-panel::icon name="user" class="size-3" /> {{ $detail['author'] }}
                                        </span>
                                    @endif
                                    @if (!empty($detail['category']))
                                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                            <x-admin-panel::icon name="tag" class="size-3" /> {{ $detail['category'] }}
                                        </span>
                                    @endif
                                    @if (!empty($detail['downloads']))
                                        <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                            <x-admin-panel::icon name="download" class="size-3" />
                                            <span class="fa-digits">{{ number_format($detail['downloads']) }}</span> دانلود
                                        </span>
                                    @endif
                                </div>

                                @if (!empty($detail['short_description']))
                                    <div class="ap-rich mt-2.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                                        {!! sanitize_package_html($detail['short_description']) !!}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- هشدار لایسنس منقضی (پورت confirm-license-warning) --}}
                        @if ($detailWarning)
                            <div class="mt-4 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-[13px] text-amber-800 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-300">
                                <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-4 shrink-0" />
                                <span>{{ $detailWarning }}</span>
                            </div>
                        @endif

                        {{-- وضعیت لایسنس فعال (پورت modal-license-section) --}}
                        @if ($detailMode === 'license' && !$dInstalled)
                            <div class="mt-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/25 dark:bg-emerald-500/10">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white">
                                    <x-admin-panel::icon name="shield" class="size-4.5" />
                                </span>
                                <div class="text-[13px]">
                                    <p class="flex items-center gap-1.5 font-bold text-emerald-700 dark:text-emerald-400">
                                        <x-admin-panel::icon name="check-circle" class="size-4" />
                                        قبلاً خریداری شده — بدون پرداخت نصب می‌شود
                                    </p>
                                    <p class="mt-1 text-emerald-700/80 dark:text-emerald-400/80">لایسنس شما برای این پکیج فعال است</p>
                                </div>
                            </div>
                        @endif

                        {{-- وضعیت اشتراک فعال (پورت modal-subscription-section) --}}
                        @if ($detailMode === 'subscription' && !$dInstalled)
                            <div class="mt-4 flex items-start gap-3 rounded-xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-500/25 dark:bg-violet-500/10">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-violet-500 text-white">
                                    <x-admin-panel::icon name="award" class="size-4.5" />
                                </span>
                                <div class="text-[13px]">
                                    <p class="flex items-center gap-1.5 font-bold text-violet-700 dark:text-violet-400">
                                        <x-admin-panel::icon name="check-circle" class="size-4" />
                                        این پکیج با اشتراک شما رایگان است
                                    </p>
                                    <p class="mt-1 text-violet-700/80 dark:text-violet-400/80">
                                        طرح: <strong>{{ $detail['subscription']['plan_name'] ?? 'اشتراک' }}</strong>
                                        @if (!empty($detail['subscription']['less_than_one_day']))
                                            — <span>کمتر از ۱ روز باقی‌مانده</span>
                                        @elseif (!empty($detail['subscription']['days_remaining']))
                                            — <span class="fa-digits">{{ $detail['subscription']['days_remaining'] }}</span> روز باقی‌مانده
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif

                        {{-- گالری --}}
                        @if (count($dImages))
                            <div class="mt-5">
                                <h5 class="mb-2 flex items-center gap-1.5 text-[13px] font-bold">
                                    <x-admin-panel::icon name="images" class="size-4 text-brand-600 dark:text-brand-400" />
                                    گالری تصاویر <span class="fa-digits text-ink-3">({{ count($dImages) }})</span>
                                </h5>
                                <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                                    @foreach ($dImages as $img)
                                        @php $url = is_array($img) ? ($img['url'] ?? $img['path'] ?? '') : $img; @endphp
                                        @if ($url)
                                            <a href="{{ $url }}" target="_blank" rel="noopener" class="block aspect-video overflow-hidden rounded-xl border border-line bg-surface-2 dark:border-night-line dark:bg-night-3">
                                                <img src="{{ $url }}" alt="" loading="lazy" class="size-full object-cover transition-transform hover:scale-105">
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- توضیحات کامل --}}
                        @if (!empty($detail['long_description']) || !empty($detail['description']))
                            <div class="mt-5">
                                <h5 class="mb-2 flex items-center gap-1.5 text-[13px] font-bold">
                                    <x-admin-panel::icon name="file-text" class="size-4 text-brand-600 dark:text-brand-400" />
                                    توضیحات کامل
                                </h5>
                                {{-- فاز ۲۷: مثل پنل قدیمی — بدون محدودیت ارتفاع (pkg-long-desc قدیمی فقط جعبه‌ی حاشیه‌دار بود؛
                                     اسکرول را خودِ مودال می‌دهد) و با پاک‌سازی مخصوص پکیج که تگ <style> توضیحات API را نگه می‌دارد --}}
                                <div class="ap-rich mt-0 rounded-e-xl rounded-s-xl border border-line border-e-4 border-e-brand-500 bg-surface-2/50 p-4 text-[13px] leading-7 text-ink-2 dark:border-night-line dark:bg-night-3/40 dark:text-night-ink-2">
                                    {!! sanitize_package_html($detail['long_description'] ?? $detail['description']) !!}
                                </div>
                            </div>
                        @endif

                        {{-- تغییرات نسخه آخر (پورت what_added/changed/fixed) --}}
                        @if (!empty($dVersions[0]))
                            @php $latestV = $dVersions[0]; @endphp
                            @if (!empty($latestV['what_added']) || !empty($latestV['what_changed']) || !empty($latestV['what_fixed']))
                                <div class="mt-5">
                                    <h5 class="mb-2 flex items-center gap-1.5 text-[13px] font-bold">
                                        <x-admin-panel::icon name="info" class="size-4 text-brand-600 dark:text-brand-400" />
                                        تغییرات نسخه v{{ $latestV['version'] ?? '' }}
                                    </h5>
                                    <div class="space-y-2">
                                        @if (!empty($latestV['what_added']))
                                            <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 text-[12.5px] leading-6 dark:border-emerald-500/20 dark:bg-emerald-500/8">
                                                <strong class="text-emerald-700 dark:text-emerald-400">قابلیت‌های جدید:</strong>
                                                <span class="text-ink-2 dark:text-night-ink-2">{!! nl2br(e($latestV['what_added'])) !!}</span>
                                            </div>
                                        @endif
                                        @if (!empty($latestV['what_changed']))
                                            <div class="rounded-xl border border-sky-200 bg-sky-50/60 p-3 text-[12.5px] leading-6 dark:border-sky-500/20 dark:bg-sky-500/8">
                                                <strong class="text-sky-700 dark:text-sky-400">تغییرات:</strong>
                                                <span class="text-ink-2 dark:text-night-ink-2">{!! nl2br(e($latestV['what_changed'])) !!}</span>
                                            </div>
                                        @endif
                                        @if (!empty($latestV['what_fixed']))
                                            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3 text-[12.5px] leading-6 dark:border-amber-500/20 dark:bg-amber-500/8">
                                                <strong class="text-amber-700 dark:text-amber-400">رفع باگ:</strong>
                                                <span class="text-ink-2 dark:text-night-ink-2">{!! nl2br(e($latestV['what_fixed'])) !!}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endif

                        {{-- تاریخچه نسخه‌ها (پورت changelog) --}}
                        @if (!empty($dChangelog))
                            <div class="mt-5">
                                <h5 class="mb-2 flex items-center gap-1.5 text-[13px] font-bold">
                                    <x-admin-panel::icon name="history" class="size-4 text-brand-600 dark:text-brand-400" />
                                    تاریخچه نسخه‌ها
                                </h5>
                                <ul class="max-h-44 space-y-1.5 overflow-y-auto rounded-xl border border-line bg-surface-2/50 p-3.5 text-[12.5px] leading-6 dark:border-night-line dark:bg-night-3/40">
                                    @forelse ($dChangelog as $ver => $changes)
                                        <li class="flex gap-2">
                                            <strong class="fa-digits shrink-0 text-brand-600 dark:text-brand-400">v{{ $ver }}:</strong>
                                            <span class="text-ink-2 dark:text-night-ink-2">{!! nl2br(e(is_array($changes) ? implode(' — ', $changes) : $changes)) !!}</span>
                                        </li>
                                    @empty
                                        <li class="text-ink-3">—</li>
                                    @endforelse
                                </ul>
                            </div>
                        @endif

                        {{-- پیش‌نیازها --}}
                        @if (!empty($detail['requirements']))
                            <div class="mt-5">
                                <h5 class="mb-2 flex items-center gap-1.5 text-[13px] font-bold">
                                    <x-admin-panel::icon name="list" class="size-4 text-brand-600 dark:text-brand-400" />
                                    پیش‌نیازها
                                </h5>
                                <ul class="flex flex-wrap gap-1.5">
                                    @foreach ($detail['requirements'] as $req => $ver)
                                        <li class="ap-kbd">{{ $req }} ≥ {{ $ver }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- وضعیت نصب (پورت pkg-status-box) --}}
                        @if ($dInstalled)
                            <div class="mt-5">
                                <h5 class="mb-2 flex items-center gap-1.5 text-[13px] font-bold">
                                    <x-admin-panel::icon name="settings" class="size-4 text-brand-600 dark:text-brand-400" />
                                    وضعیت نصب
                                </h5>
                                @if ($dInstalled['status'] === 'updating')
                                    <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3.5 dark:border-amber-500/25 dark:bg-amber-500/10">
                                        <svg class="size-5 animate-spin text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                                        </svg>
                                        <span class="text-[13px] font-bold text-amber-800 dark:text-amber-300">در حال نصب/آپدیت…</span>
                                    </div>
                                @elseif ($dInstalled['status'] === 'failed')
                                    <div class="rounded-xl border border-red-200 bg-red-50 p-3.5 dark:border-red-500/25 dark:bg-red-500/10">
                                        <p class="flex items-center gap-2 text-[13px] font-bold text-red-700 dark:text-red-400">
                                            <x-admin-panel::icon name="triangle-alert" class="size-4" />
                                            نصب با خطا مواجه شد
                                        </p>
                                        @if ($dInstalled['last_error'])
                                            <p class="mt-1 text-[12px] leading-5 text-red-600/90 dark:text-red-300/90">{{ $dInstalled['last_error'] }}</p>
                                        @endif
                                    </div>
                                @else
                                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 dark:border-emerald-500/25 dark:bg-emerald-500/10">
                                        <p class="flex items-center gap-2 text-[13px] font-bold text-emerald-700 dark:text-emerald-400">
                                            <x-admin-panel::icon name="check-circle" class="size-4" />
                                            نصب‌شده — نسخه <span class="fa-digits">{{ $dInstalled['version'] }}</span>
                                            @if ($dInstalled['is_active'])
                                                <span class="ap-badge bg-emerald-500 text-white">فعال</span>
                                            @else
                                                <span class="ap-badge bg-amber-400 text-amber-950">غیرفعال</span>
                                            @endif
                                        </p>
                                        @if ($dInstalled['license_expires_at'])
                                            <p class="mt-1 text-[12px] text-emerald-800/80 dark:text-emerald-300/80">
                                                انقضای لایسنس: {{ $dInstalled['license_expires_human'] ?: $dInstalled['license_expires_at'] }}
                                                @if ($dInstalled['is_expired']) <strong class="text-red-500">(منقضی شده)</strong> @endif
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- پلن‌های قیمت‌گذاری (پورت modal-plans-section) --}}
                        @if (!$dInstalled && $detailMode === 'paid' && count($dPlans) > 0)
                            @php
                                $dMinPrice = null;
                                foreach ($dPlans as $p) { $fp = $this->planFinalPrice($p); $dMinPrice = $dMinPrice === null ? $fp : min($dMinPrice, $fp); }
                            @endphp
                            <div class="mt-5">
                                <h5 class="mb-2 flex items-center gap-1.5 text-[13px] font-bold">
                                    <x-admin-panel::icon name="tag" class="size-4 text-brand-600 dark:text-brand-400" />
                                    طرح‌های قیمت‌گذاری <span class="text-[11px] font-medium text-ink-3">یک طرح انتخاب کنید</span>
                                </h5>
                                {{-- فاز ۲۸: مثل قدیمی col-md-3 — ۳ ستون در عرض مودال xl --}}
                                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($dPlans as $plan)
                                        @php
                                            $fp = $this->planFinalPrice($plan);
                                            $isCheapest = $fp === $dMinPrice;
                                            $hasDiscount = isset($plan['discount_price']) && $plan['discount_price'] !== null && $plan['discount_price'] < ($plan['price'] ?? 0);
                                        @endphp
                                        <button type="button" wire:click="setDetailPlan({{ (int) $plan['id'] }})"
                                                class="relative rounded-xl border-2 p-3.5 text-start transition-all {{ $detailPlanId === (int) $plan['id'] ? 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10' : 'border-line bg-surface hover:border-brand-300 dark:border-night-line dark:bg-night-3 dark:hover:border-brand-500/40' }}">
                                            <span class="absolute end-3 top-3 flex size-4 items-center justify-center rounded-full border-2 {{ $detailPlanId === (int) $plan['id'] ? 'border-brand-500 bg-brand-500' : 'border-line dark:border-night-line' }}">
                                                @if ($detailPlanId === (int) $plan['id'])
                                                    <span class="size-1.5 rounded-full bg-white"></span>
                                                @endif
                                            </span>
                                            <p class="text-[13px] font-bold">{{ $plan['name'] ?? 'پلن' }}</p>
                                            <p class="mt-0.5 flex items-center gap-1 text-[11px] text-ink-3 dark:text-night-ink-2">
                                                <x-admin-panel::icon name="clock" class="size-3" />
                                                {{ $this->planDurationLabel($plan) }}
                                            </p>
                                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                @if (!empty($plan['is_one_time']))
                                                    <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">یک‌بار مصرف</span>
                                                @endif
                                                @if ($isCheapest)
                                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">ارزان‌ترین</span>
                                                @endif
                                            </div>
                                            <div class="mt-2">
                                                @if ($fp === 0)
                                                    <span class="text-sm font-extrabold text-emerald-600">رایگان</span>
                                                @else
                                                    <span class="text-sm">
                                                        <span class="fa-digits text-ink-3 line-through dark:text-night-ink-2" @if(!$hasDiscount) hidden @endif>{{ number_format($plan['price'] ?? 0) }}</span>
                                                        <span class="fa-digits ms-1 font-extrabold">{{ number_format($fp) }}</span>
                                                        <span class="ms-0.5 text-[10px] text-ink-3 dark:text-night-ink-2">تومان</span>
                                                    </span>
                                                @endif
                                            </div>
                                            @if (!empty($plan['description']))
                                                <p class="mt-1.5 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">{{ $plan['description'] }}</p>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- لاگ‌های اخیر (پورت pkg-logs-table) --}}
                        @if (count($detailLogs))
                            <div class="mt-5">
                                <h5 class="mb-2 flex items-center gap-1.5 text-[13px] font-bold">
                                    <x-admin-panel::icon name="list" class="size-4 text-brand-600 dark:text-brand-400" />
                                    لاگ‌های اخیر
                                </h5>
                                <div class="max-h-56 overflow-y-auto rounded-xl border border-line dark:border-night-line">
                                    <table class="w-full text-[12px]">
                                        <thead class="sticky top-0 bg-surface-2 text-ink-3 dark:bg-night-3">
                                            <tr>
                                                <th class="px-3 py-2 text-start font-bold">عملیات</th>
                                                <th class="px-3 py-2 text-start font-bold">از → به</th>
                                                <th class="px-3 py-2 text-start font-bold">وضعیت</th>
                                                <th class="px-3 py-2 text-start font-bold">تاریخ</th>
                                                <th class="px-3 py-2 text-start font-bold">پیام</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-line dark:divide-night-line">
                                            @foreach ($detailLogs as $log)
                                                <tr>
                                                    <td class="px-3 py-2">
                                                        @switch($log['action'])
                                                            @case('install') <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">نصب</span> @break
                                                            @case('update') <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">آپدیت</span> @break
                                                            @case('uninstall') <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400">حذف</span> @break
                                                            @default <span class="ap-badge bg-surface-2 text-ink-2">{{ $log['action'] }}</span>
                                                        @endswitch
                                                    </td>
                                                    <td class="fa-digits px-3 py-2 text-ink-2 dark:text-night-ink-2">{{ $log['from_version'] ?: '—' }} → {{ $log['to_version'] ?: '—' }}</td>
                                                    <td class="px-3 py-2">
                                                        @if ($log['status'] === 'running')
                                                            <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400"><span class="size-1.5 animate-pulse rounded-full bg-amber-500"></span> در حال اجرا</span>
                                                        @elseif ($log['status'] === 'success')
                                                            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">موفق</span>
                                                        @else
                                                            <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400">ناموفق</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-ink-3">{{ $log['created_at'] }}</td>
                                                    <td class="max-w-44 truncate px-3 py-2 text-ink-2 dark:text-night-ink-2" title="{{ $log['message'] }}">{{ $log['message'] ?: '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- فوتر: قیمت + عملیات (پورت modal footer قدیمی) --}}
                @if ($detail && !$detailLoading && !$detailError)
                    @php
                        $dInstalled = $detail['installed'] ?? null;
                        $dPlans = $detail['active_pricing_plans'] ?? $detail['pricing_plans'] ?? $detail['plans'] ?? [];
                        $selectedPlan = collect($dPlans)->firstWhere('id', $detailPlanId);
                        $selectedFree = $selectedPlan ? ($this->planFinalPrice($selectedPlan) === 0) : !empty($detail['is_free']);
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-surface-2/40 px-5 py-4 dark:border-night-line dark:bg-night-3/40">
                        {{-- قیمت --}}
                        <div class="text-sm">
                            @if ($dInstalled && $dInstalled['status'] === 'updating')
                                <span class="flex items-center gap-2 font-bold text-amber-600 dark:text-amber-400">
                                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" /></svg>
                                    در حال نصب…
                                </span>
                            @elseif (!$dInstalled && $detailMode === 'subscription')
                                <span class="flex items-center gap-1 font-extrabold text-violet-600 dark:text-violet-400">
                                    <x-admin-panel::icon name="award" class="size-4" /> رایگان با اشتراک
                                </span>
                            @elseif (!$dInstalled && $detailMode === 'license')
                                <span class="flex items-center gap-1 font-extrabold text-emerald-600 dark:text-emerald-400">
                                    <x-admin-panel::icon name="shield" class="size-4" /> لایسنس فعال — بدون پرداخت
                                </span>
                            @elseif ($selectedFree)
                                <span class="font-extrabold text-emerald-600">رایگان</span>
                            @elseif ($selectedPlan)
                                <span class="fa-digits font-extrabold">{{ number_format($this->planFinalPrice($selectedPlan)) }}</span>
                                <span class="text-[11px] text-ink-3">تومان</span>
                            @elseif (!empty($detail['is_free']))
                                <span class="font-extrabold text-emerald-600">رایگان</span>
                            @else
                                <span class="fa-digits font-extrabold">{{ number_format($detail['min_price'] ?? ($detail['price'] ?? 0)) }}</span>
                                <span class="text-[11px] text-ink-3">تومان</span>
                            @endif
                        </div>

                        {{-- عملیات --}}
                        <div class="flex flex-wrap items-center gap-2">
                            {{-- فاز ۹۹ — گیت نصب: با خاموشی allow_install پکیجِ نصب‌نشده فقط مرور می‌شود (بدون دکمهٔ نصب) --}}
                            @if (!$dInstalled && \App\Support\MarketplaceSettings::allowInstall())
                                <button type="button" wire:click="installFromDetail"
                                        class="ap-btn-primary min-w-32"
                                        @if ($detailMode === 'paid' && count($dPlans) && !$detailPlanId) disabled @endif>
                                    <x-admin-panel::icon name="{{ $detailMode === 'subscription' ? 'award' : ($detailMode === 'license' ? 'shield' : ($selectedFree ? 'download' : 'credit-card')) }}" class="size-4" />
                                    {{ $detailMode === 'subscription' ? 'نصب با اشتراک' : ($detailMode === 'license' ? 'بررسی و نصب' : ($selectedFree ? 'نصب پکیج' : 'پرداخت و نصب')) }}
                                </button>
                            @elseif ($dInstalled['status'] === 'updating')
                                <button type="button" disabled class="ap-btn min-w-32 opacity-70">
                                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" /></svg>
                                    در حال نصب…
                                </button>
                            @else
                                @if (($detail['has_update'] ?? false) && \App\Support\MarketplaceSettings::allowInstall())
                                    <button type="button" wire:click="updateFromDetail" class="ap-btn-primary min-w-32" @if ($dInstalled['is_expired']) disabled @endif>
                                        <x-admin-panel::icon name="arrow-up" class="size-4" />
                                        آپدیت به v{{ $detail['latestVersion'] }}
                                    </button>
                                    @if ($dInstalled['is_expired'])
                                        <span class="text-[11px] font-bold text-red-500">لایسنس منقضی</span>
                                    @endif
                                @else
                                    <span class="flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-[12px] font-bold text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                                        <x-admin-panel::icon name="check" class="size-3.5" /> آخرین نسخه نصب شده
                                    </span>
                                @endif

                                @if ($detailConfirmingUninstall && \App\Support\MarketplaceSettings::allowUninstall())
                                    <span class="flex items-center gap-1.5 rounded-xl border border-red-200 bg-red-50 px-2.5 py-1.5 text-[12px] font-bold text-red-700 dark:border-red-500/25 dark:bg-red-500/10 dark:text-red-400">
                                        مطمئنید؟
                                        <button type="button" wire:click="uninstallFromDetail" class="rounded-lg bg-red-600 px-2 py-1 text-white hover:bg-red-700">بله، حذف شود</button>
                                        <button type="button" wire:click="cancelDetailUninstall" class="rounded-lg px-2 py-1 hover:bg-red-100 dark:hover:bg-red-500/20">انصراف</button>
                                    </span>
                                @else
                                    <button type="button" wire:click="toggleFromDetail" class="ap-btn-outline !py-2">
                                        <x-admin-panel::icon name="{{ $dInstalled['is_active'] ? 'eye-off' : 'eye' }}" class="size-4" />
                                        {{ $dInstalled['is_active'] ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                                    </button>
                                    {{-- فاز ۹۹ — گیت حذف: با خاموشی allow_uninstall دکمهٔ حذف مخفی --}}
                                    @if (\App\Support\MarketplaceSettings::allowUninstall())
                                        <button type="button" wire:click="confirmDetailUninstall" class="ap-btn-ghost !py-2 !text-red-600 hover:!bg-red-50 dark:hover:!bg-red-500/10">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                            حذف
                                        </button>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ====================== --}}
    {{-- فاز ۶۴ — مودال پیشرفت نصب تمام‌صفحهٔ بلوکه‌شده (فاز ۲۶: پورت pkg-install-progress در progress.js قدیمی)
         درخواست کاربر: تا وقتی نصب در جریان است:
           ۱) نصب همزمان دیگری ممکن نباشد (قفل سراسری سمت سرور — hasOtherInstallationInProgress)
           ۲) این پنجره قابل بستن نباشد (بدون دکمهٔ بستن، بدون بک‌دراپ‌کلیک، بدون Esc)
           ۳) جاهای دیگر صفحه قابل کلیک نباشند (اورلی تمام‌صفحه با z بالاتر از همهٔ مودال‌ها)
         پس از پایان (موفق/ناموفق) پنجره با دکمهٔ بستن، کلیک بک‌دراپ یا Esc بسته می‌شود. --}}
    {{-- ====================== --}}
    @if ($progressSlug)
        @php
            $pPercent = $progressState['percent'] ?? 15;
            $pStep = $progressState['step'] ?? 0;
            $pStatus = $progressState['status'] ?? 'installing';
            $pMessage = $progressState['message'] ?? 'در حال نصب — لطفاً صبر کنید…';
            $pDone = $pStatus === 'installed';
            $pFailed = $pStatus === 'failed';
            $pBusy = ! $pDone && ! $pFailed;          // نصب همچنان در جریان است
            $pIsTemplate = $progressKind === 'template';
        @endphp
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4"
             wire:key="pkg-progress" wire:poll.2s="pollProgress"
             role="dialog" aria-modal="true" aria-live="polite"
             @if (! $pBusy) x-on:keydown.escape.window="$wire.dismissProgress()" @endif
             aria-label="وضعیت نصب">
            {{-- بک‌دراپ تمام‌صفحه — حین نصب هیچ تعاملی ندارد (کلیک‌های صفحه را می‌گیرد) --}}
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" aria-hidden="true"
                 @if (! $pBusy) wire:click="dismissProgress" @endif></div>

            <div class="relative w-[min(92vw,34rem)] overflow-hidden rounded-2xl border shadow-pop transition-colors {{ $pDone ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/25 dark:bg-emerald-500/10' : ($pFailed ? 'border-red-200 bg-red-50 dark:border-red-500/25 dark:bg-red-500/10' : 'border-line bg-surface dark:border-night-line dark:bg-night-2') }}">
                {{-- سربرگ --}}
                <div class="flex items-center gap-3 p-4">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl text-white shadow-lg {{ $pDone ? 'bg-emerald-500 shadow-emerald-500/30' : ($pFailed ? 'bg-red-500 shadow-red-500/30' : 'bg-brand-500 shadow-brand-500/30') }}">
                        @if ($pDone)
                            <x-admin-panel::icon name="check" class="size-5" />
                        @elseif ($pFailed)
                            <x-admin-panel::icon name="x" class="size-5" />
                        @else
                            <svg class="size-5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                            </svg>
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13.5px] font-extrabold">
                            @if ($pDone) نصب کامل شد!
                            @elseif ($pFailed) خطا در نصب
                            @else در حال نصب <span class="text-brand-600 dark:text-brand-400">«{{ $progressName }}»</span>
                            @endif
                        </p>
                        <p class="mt-0.5 truncate text-[11.5px] text-ink-3 dark:text-night-ink-2">{{ $pMessage }}</p>
                    </div>
                    {{-- دکمهٔ بستن فقط پس از پایان نصب (موفق/ناموفق) — حین نصب پنجره غیرقابل بستن است --}}
                    @if (! $pBusy)
                        <button type="button" wire:click="dismissProgress" class="ap-btn-ghost !p-1.5" title="بستن (Esc)"
                                aria-label="بستن پنجرهٔ وضعیت نصب">
                            <x-admin-panel::icon name="x" class="size-4" />
                        </button>
                    @endif
                </div>

                {{-- نوار پیشرفت --}}
                <div class="px-4">
                    <div class="flex items-center gap-2.5">
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-surface-3 dark:bg-night-3">
                            <div class="h-full rounded-full transition-all duration-500 {{ $pFailed ? 'bg-red-500' : ($pDone ? 'bg-emerald-500' : 'bg-gradient-to-l from-brand-500 to-emerald-500') }}"
                                 style="width: {{ $pPercent }}%"></div>
                        </div>
                        <span class="fa-digits w-9 text-end text-[11px] font-extrabold text-ink-2 dark:text-night-ink-2">{{ $pPercent }}٪</span>
                    </div>

                    {{-- مراحل — فقط نصب پکیج (نصب قالب لاگ مرحله‌ای ندارد) --}}
                    @if (! $pIsTemplate)
                        <div class="mt-3 grid grid-cols-6 gap-1">
                            @foreach (self::$progressSteps as $i => $step)
                                @php
                                    $stepDone = $pDone || $i < $pStep;
                                    $stepActive = !$pDone && !$pFailed && $i === $pStep;
                                @endphp
                                <div class="flex flex-col items-center gap-1">
                                    <span class="flex size-7 items-center justify-center rounded-lg border transition-colors
                                        {{ $stepDone ? 'border-emerald-300 bg-emerald-100 text-emerald-600 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-400'
                                            : ($stepActive ? 'border-brand-300 bg-brand-50 text-brand-600 dark:border-brand-500/40 dark:bg-brand-500/15 dark:text-brand-400'
                                            : 'border-line bg-surface-2 text-ink-3 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2') }}">
                                        @if ($stepDone)
                                            <x-admin-panel::icon name="check" class="size-3.5" />
                                        @elseif ($stepActive)
                                            <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" /></svg>
                                        @else
                                            <x-admin-panel::icon name="{{ $step['icon'] }}" class="size-3.5" />
                                        @endif
                                    </span>
                                    <span class="text-[9.5px] font-bold {{ $stepDone || $stepActive ? 'text-ink-2 dark:text-night-ink-2' : 'text-ink-3 dark:text-night-ink-2' }}">{{ $step['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- پانویس — راهنمای قفل حین نصب / دکمهٔ پایان --}}
                <div class="mt-3.5 flex items-center justify-between gap-3 border-t px-4 py-3
                    {{ $pDone ? 'border-emerald-200/70 dark:border-emerald-500/20' : ($pFailed ? 'border-red-200/70 dark:border-red-500/20' : 'border-line dark:border-night-line') }}">
                    @if ($pBusy)
                        <p class="flex items-center gap-1.5 text-[11px] font-bold text-ink-2 dark:text-night-ink-2">
                            <x-admin-panel::icon name="lock" class="size-3.5 shrink-0 text-amber-500" />
                            تا پایان نصب، این پنجره قابل بستن نیست و صفحه قفل است.
                        </p>
                    @else
                        <p class="truncate text-[11px] font-bold {{ $pDone ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400' }}">
                            @if ($pDone) ✅ نصب با موفقیت به پایان رسید
                            @else نصب ناموفق بود — می‌توانید دوباره تلاش کنید
                            @endif
                        </p>
                        <button type="button" wire:click="dismissProgress" class="ap-btn-primary !py-1.5 !text-xs">
                            بستن
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ====================== --}}
    {{-- فاز ۴۴ — مودال جزئیات قالب (خرید normal/exclusive + وضعیت نصب) --}}
    {{-- ====================== --}}
    {{-- فاز ۶۷ — مودال جزئیات قالب (بازطراحی مدرن: هیروی تصویری + فوتر چسبان + گالری +
         خطای خودتشخیص‌دهنده با تلاش دوباره — بدنهٔ مودال دیگر هیچ‌وقت خالی نمی‌ماند) --}}
    @if ($tDetailOpen)
    <div class="fixed inset-0 z-[90] flex items-center justify-center p-3 sm:p-4"
         x-on:keydown.escape.window="$wire.closeTemplateDetail()" role="dialog" aria-modal="true" aria-label="جزئیات قالب">
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" wire:click="closeTemplateDetail" aria-hidden="true"></div>

        <div class="relative flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2">

            {{-- دکمهٔ بستن شناور --}}
            <button type="button" wire:click="closeTemplateDetail"
                    class="absolute end-3.5 top-3.5 z-20 flex size-9 items-center justify-center rounded-full bg-slate-950/35 text-white shadow-lg backdrop-blur transition-all hover:scale-105 hover:bg-slate-950/55"
                    aria-label="بستن" title="بستن (Esc)">
                <x-admin-panel::icon name="x" class="size-4.5" />
            </button>

            {{-- ============ حالت بارگذاری ============ --}}
            @if ($tDetailLoading)
                <div class="flex min-h-[320px] flex-col items-center justify-center gap-4">
                    <span class="relative flex size-16 items-center justify-center">
                        <span class="absolute inset-0 animate-ping rounded-full bg-brand-500/20" aria-hidden="true"></span>
                        <span class="flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                            <x-admin-panel::icon name="layout-template" class="size-7" />
                        </span>
                    </span>
                    <p class="text-[13px] font-bold text-ink-2 dark:text-night-ink-2">در حال دریافت اطلاعات قالب…</p>
                </div>

            {{-- ============ حالت خطا + تلاش دوباره (فاز ۶۷) ============ --}}
            @elseif ($tDetailError)
                <div class="flex min-h-[320px] flex-col items-center justify-center gap-3 px-6 py-10 text-center">
                    <span class="flex size-14 items-center justify-center rounded-2xl bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400">
                        <x-admin-panel::icon name="triangle-alert" class="size-7" />
                    </span>
                    <p class="text-sm font-bold">دریافت جزئیات قالب ناموفق بود</p>
                    <p class="max-w-md text-xs leading-6 text-ink-3 dark:text-night-ink-2">{{ $tDetailError }}</p>
                    <div class="mt-1 flex items-center gap-2">
                        <button type="button" wire:click="openTemplateDetail('{{ $tDetailSlug }}')" wire:loading.attr="disabled" wire:target="openTemplateDetail"
                                class="ap-btn-primary !py-2.5">
                            <x-admin-panel::icon name="refresh-cw" class="size-4" />
                            تلاش دوباره
                        </button>
                        <button type="button" wire:click="closeTemplateDetail" class="ap-btn-outline !py-2.5">بستن</button>
                    </div>
                </div>

            {{-- ============ محتوای قالب ============ --}}
            @elseif ($tDetail)
                @php
                    $tPrice    = (int) ($tDetail['discount_price'] ?? $tDetail['price'] ?? $tDetail['min_price'] ?? 0);
                    $tOldPrice = ($tDetail['discount_price'] ?? null) ? (int) ($tDetail['price'] ?? 0) : 0;
                    $tHasDiscount = $tOldPrice > 0 && $tPrice > 0 && $tPrice < $tOldPrice;
                    $tExPrice  = (int) ($tDetail['exclusive_price'] ?? 0);
                    $tSold     = (bool) ($tDetail['is_sold_exclusively'] ?? false);
                    // فاز ۶۶/۶۷ — تشخیص رایگان + جزئیات HTML + تصویر شاخص thumbnail_url
                    $tIsFree   = ! empty($tDetail['is_free']) || $tPrice === 0;
                    $tLongDesc = $tDetail['long_description'] ?? $tDetail['description'] ?? $tDetail['content'] ?? null;
                    // فاز ۶۹ — گاردهای اسکالر (لایهٔ دوم دفاع؛ نرمال‌سازی اصلی در openTemplateDetail است):
                    // API واقعی برخی فیلدها را آبجکت می‌دهد → htmlspecialchars crash
                    $tLongDesc = is_string($tLongDesc) && trim($tLongDesc) !== '' ? $tLongDesc : null;
                    $tShortRaw = $tDetail['short_description'] ?? null;
                    $tShortDesc = is_string($tShortRaw) && trim($tShortRaw) !== '' ? $tShortRaw : null;
                    $tCoverRaw = $tDetail['thumbnail_url'] ?? $tDetail['thumbnail'] ?? $tDetail['cover'] ?? $tDetail['preview_image'] ?? $tDetail['image'] ?? null;
                    $tCover    = is_scalar($tCoverRaw) && trim((string) $tCoverRaw) !== '' ? (string) $tCoverRaw : null;
                    $tName     = is_scalar($tDetail['name'] ?? null) && trim((string) $tDetail['name']) !== '' ? (string) $tDetail['name'] : '';
                    $tAuthor   = is_scalar($tDetail['author'] ?? null) ? (string) $tDetail['author'] : '';
                    $tCategory = is_scalar($tDetail['category'] ?? null) ? (string) $tDetail['category'] : '';
                    $tVersions = $tDetail['active_versions'] ?? [];
                    $tInstalled= $tDetail['installed'] ?? null;
                    // فاز ۶۷ — گالری: نرمال‌شده در trait (لیست URL) + fallback نگاشت داخل ویو
                    $tGallery  = [];
                    foreach (['gallery', 'images', 'screenshots', 'preview_images'] as $tGalleryKey) {
                        if (! empty($tDetail[$tGalleryKey]) && is_array($tDetail[$tGalleryKey])) {
                            $tGallery = $tDetail[$tGalleryKey];
                            break;
                        }
                    }
                    $tGallery  = array_values(array_filter(array_map(fn ($g) => is_string($g) ? $g : (is_array($g) ? ($g['url'] ?? $g['path'] ?? $g['thumbnail_url'] ?? $g['image_url'] ?? null) : null), $tGallery)));
                @endphp

                {{-- ---- هیروی تصویری ---- --}}
                <div class="relative h-44 shrink-0 overflow-hidden sm:h-52" wire:key="tpl-hero">
                    @if ($tCover)
                        <img src="{{ $tCover }}" alt="پیش‌نمایش {{ $tName }}" class="size-full object-cover">
                    @else
                        <div class="flex size-full items-center justify-center bg-gradient-to-br from-brand-100 via-teal-50 to-amber-50 dark:from-night-3 dark:via-night-2 dark:to-night-3" aria-hidden="true">
                            <span class="text-5xl font-black text-brand-600/70 dark:text-brand-400/70">{{ mb_substr($tName !== '' ? $tName : 'ق', 0, 1) }}</span>
                        </div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/25 to-slate-950/5" aria-hidden="true"></div>

                    {{-- نشان‌ها --}}
                    <div class="absolute start-4 top-4 flex flex-wrap gap-1.5">
                        @if ($tSold)
                            <span class="ap-badge bg-amber-500 text-white shadow">اختصاصی فروخته شد</span>
                        @elseif ($tIsFree)
                            <span class="ap-badge bg-emerald-500 text-white shadow">رایگان</span>
                        @else
                            <span class="ap-badge bg-amber-500 text-white shadow">پولی</span>
                        @endif
                        @if ($tInstalled && $tInstalled['is_active'])
                            <span class="ap-badge bg-emerald-600 text-white shadow">قالب فعال فروشگاه</span>
                        @elseif ($tInstalled && ($tInstalled['status'] ?? '') === 'installed')
                            <span class="ap-badge bg-white/90 text-emerald-700 shadow">نصب‌شده</span>
                        @endif
                        @if ($tDetail['has_update'] ?? false)
                            <span class="ap-badge bg-sky-500 text-white shadow">به‌روزرسانی موجود است</span>
                        @endif
                    </div>

                    {{-- نام + مشخصات --}}
                    <div class="absolute inset-x-0 bottom-0 p-4 sm:p-5">
                        <h3 class="truncate text-lg font-black text-white drop-shadow sm:text-xl">{{ $tName }}</h3>
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px] text-white/90">
                            @if ($tDetail['latestVersion'] ?? null)
                                <span class="rounded-md bg-white/15 px-1.5 py-0.5 font-mono font-bold backdrop-blur" dir="ltr">v{{ $tDetail['latestVersion'] }}</span>
                            @endif
                            @if ($tAuthor !== '')
                                <span class="flex items-center gap-1"><x-admin-panel::icon name="user" class="size-3" /> {{ $tAuthor }}</span>
                            @endif
                            @if ($tCategory !== '')
                                <span class="flex items-center gap-1"><x-admin-panel::icon name="tag" class="size-3" /> {{ $tCategory }}</span>
                            @endif
                            @if (!empty($tDetail['downloads']) && is_numeric($tDetail['downloads']))
                                <span class="flex items-center gap-1"><x-admin-panel::icon name="download" class="size-3" /> <span class="fa-digits">{{ number_format((int) $tDetail['downloads']) }}</span> دانلود</span>
                            @endif
                            @if (!empty($tDetail['purchases_count']) && is_numeric($tDetail['purchases_count']))
                                <span class="flex items-center gap-1"><x-admin-panel::icon name="shopping-bag" class="size-3" /> <span class="fa-digits">{{ number_format((int) $tDetail['purchases_count']) }}</span> فروش</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ---- بدنه ---- --}}
                <div class="flex-1 space-y-5 overflow-y-auto p-5">
                    {{-- معرفی کوتاه (HTML پاک‌سازی‌شده) --}}
                    @if ($tShortDesc !== null)
                        <div class="ap-rich text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                            {!! sanitize_package_html($tShortDesc) !!}
                        </div>
                    @endif

                    {{-- وضعیت نصب محلی --}}
                    @if ($tInstalled && ($tInstalled['status'] ?? '') === 'updating')
                        <div class="flex items-center gap-3 rounded-xl border border-brand-200 bg-brand-50 p-4 text-xs font-bold text-brand-700 dark:border-brand-500/25 dark:bg-brand-500/10 dark:text-brand-400">
                            <svg class="size-5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" /></svg>
                            نصب این قالب در جریان است — پس از اتمام، وضعیت خودکار به‌روز می‌شود.
                        </div>
                    @elseif ($tInstalled && ($tInstalled['status'] ?? '') === 'installed')
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface-2 p-4 text-xs dark:border-night-line dark:bg-night-3/60">
                            <span class="text-ink-2 dark:text-night-ink-2">
                                وضعیت: <strong>{{ $tInstalled['is_active'] ? 'نصب‌شده و فعال' : 'نصب‌شده' }}</strong>
                                @if ($tInstalled['version']) — نسخه <span class="font-mono" dir="ltr">v{{ $tInstalled['version'] }}</span>@endif
                            </span>
                            <div class="flex flex-wrap items-center gap-2">
                                @if (($tDetail['has_update'] ?? false) && \App\Support\MarketplaceSettings::allowInstall())
                                    <button type="button" wire:click="reinstallTemplate('{{ $tDetail['slug'] ?? '' }}')" wire:loading.attr="disabled" wire:target="reinstallTemplate"
                                            class="rounded-lg bg-amber-500 px-3 py-1.5 text-[11px] font-bold text-white transition-colors hover:bg-amber-600">
                                        <x-admin-panel::icon name="refresh-cw" class="me-1 inline size-3.5" />
                                        به‌روزرسانی قالب
                                    </button>
                                @endif
                                @if (! $tInstalled['is_active'])
                                    <button type="button" wire:click="activateTemplate('{{ $tDetail['slug'] ?? '' }}')"
                                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-[11px] font-bold text-white transition-colors hover:bg-emerald-700">
                                        <x-admin-panel::icon name="check" class="me-1 inline size-3.5" />
                                        فعال‌سازی
                                    </button>
                                    {{-- فاز ۹۹ — گیت حذف قالب --}}
                                    @if (\App\Support\MarketplaceSettings::allowUninstall())
                                        <button type="button" wire:click="uninstallTemplate('{{ $tDetail['slug'] ?? '' }}')" wire:confirm="این قالب حذف شود؟ این عمل قابل بازگشت نیست."
                                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-[11px] font-bold text-red-600 transition-colors hover:bg-red-100 dark:border-red-500/25 dark:bg-red-500/10 dark:text-red-400">
                                            <x-admin-panel::icon name="trash-2" class="me-1 inline size-3.5" />
                                            حذف
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- انتخاب نوع خرید (قالب پولیِ نصب‌نشده یا نصب ناموفق — فاز ۶۷) --}}
                    @if ($tSold)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-6 text-amber-800 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-300">
                            این قالب به‌صورت اختصاصی فروخته شده و از فروشگاه حذف شده است.
                        </div>
                    @elseif ($tIsFree)
                        {{-- فاز ۶۶ — قالب رایگان: بدون انتخاب نوع خرید؛ پیام «رایگان» (نصب از فوتر) --}}
                        <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/25 dark:bg-emerald-500/10">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white">
                                <x-admin-panel::icon name="gift" class="size-4.5" />
                            </span>
                            <div class="text-[13px]">
                                <p class="flex items-center gap-1.5 font-bold text-emerald-700 dark:text-emerald-400">
                                    <x-admin-panel::icon name="check-circle" class="size-4" />
                                    این قالب رایگان است
                                </p>
                                <p class="mt-1 text-emerald-700/80 dark:text-emerald-400/80">نیازی به خرید نیست — با دکمهٔ «نصب قالب» همین حالا نصب می‌شود؛ به‌روزرسانی‌های بعدی هم رایگان دریافت می‌شود.</p>
                            </div>
                        </div>
                    @elseif (! $tIsFree)
                        {{-- انتخاب نوع خرید — قالب پولی (نصب‌نشده یا نصب ناموفق که تلاش مجدد آزاد است) --}}
                        <div class="rounded-2xl border border-line p-4 dark:border-night-line">
                            <p class="mb-3 flex items-center gap-1.5 text-xs font-bold">
                                <x-admin-panel::icon name="shopping-cart" class="size-4 text-brand-600 dark:text-brand-400" />
                                نوع خرید را انتخاب کنید
                            </p>
                            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                                <button type="button" wire:click="setTemplatePurchaseType('normal')"
                                        class="rounded-xl border p-3.5 text-start transition-all {{ $tDetailPurchaseType === 'normal' ? 'border-brand-500 bg-brand-50/60 ring-2 ring-brand-500/25 dark:bg-brand-500/10' : 'border-line bg-surface-2 hover:border-brand-400/50 dark:border-night-line dark:bg-night-3' }}">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="text-[13px] font-bold">خرید عادی</span>
                                        <span class="text-sm font-extrabold text-brand-700 dark:text-brand-400">
                                            {{ $tPrice === 0 ? 'رایگان' : number_format($tPrice).' تومان' }}
                                        </span>
                                    </span>
                                    <span class="mt-1 block text-[11px] text-ink-3 dark:text-night-ink-2">نصب روی این فروشگاه + دریافت آپدیت‌ها</span>
                                    @if ($tHasDiscount)
                                        <span class="mt-1 block text-[11px] text-ink-3 line-through dark:text-night-ink-2">{{ number_format($tOldPrice) }} تومان</span>
                                    @endif
                                </button>

                                <button type="button" wire:click="setTemplatePurchaseType('exclusive')"
                                        class="rounded-xl border p-3.5 text-start transition-all {{ $tDetailPurchaseType === 'exclusive' ? 'border-brand-500 bg-brand-50/60 ring-2 ring-brand-500/25 dark:bg-brand-500/10' : 'border-line bg-surface-2 hover:border-brand-400/50 dark:border-night-line dark:bg-night-3' }}">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="text-[13px] font-bold">خرید اختصاصی</span>
                                        <span class="text-sm font-extrabold text-brand-700 dark:text-brand-400">
                                            {{ $tExPrice === 0 ? '—' : number_format($tExPrice).' تومان' }}
                                        </span>
                                    </span>
                                    <span class="mt-1 block text-[11px] text-ink-3 dark:text-night-ink-2">مالکیت کامل — قالب از بازار حذف می‌شود</span>
                                </button>
                            </div>

                            @if ($tDetailPurchaseType === 'exclusive')
                                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-[11px] leading-6 text-amber-800 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-300">
                                    <strong>توجه:</strong> با خرید اختصاصی، این قالب برای همه‌ی کاربران دیگر از بازار حذف می‌شود و فقط شما به آن دسترسی خواهید داشت. این عمل قابل بازگشت نیست.
                                </div>
                            @endif

                            @if ($tDetailPurchaseType === 'exclusive' && $tExclusiveConfirmed)
                                <div class="mt-3 rounded-lg border border-amber-300 bg-amber-100 p-3 text-[11px] font-bold text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/15 dark:text-amber-300">
                                    برای تأیید نهایی، دوباره روی «خرید اختصاصی و ادامه» در پایین کلیک کنید.
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- جزئیات کامل قالب (HTML خام بازار) --}}
                    @if (!empty($tLongDesc))
                        <div>
                            <p class="mb-2 flex items-center gap-1.5 text-[13px] font-bold">
                                <x-admin-panel::icon name="file-text" class="size-4 text-brand-600 dark:text-brand-400" />
                                جزئیات قالب
                            </p>
                            <div class="ap-rich mt-0 rounded-e-xl rounded-s-xl border border-line border-e-4 border-e-brand-500 bg-surface-2/50 p-4 text-[13px] leading-7 text-ink-2 dark:border-night-line dark:bg-night-3/40 dark:text-night-ink-2">
                                {!! sanitize_package_html($tLongDesc) !!}
                            </div>
                        </div>
                    @endif

                    {{-- گالری تصاویر (فاز ۶۷ — اگر API بدهد) --}}
                    @if (count($tGallery))
                        <div>
                            <p class="mb-2 flex items-center gap-1.5 text-[13px] font-bold">
                                <x-admin-panel::icon name="images" class="size-4 text-brand-600 dark:text-brand-400" />
                                گالری پیش‌نمایش <span class="fa-digits text-[11px] font-medium text-ink-3">({{ count($tGallery) }})</span>
                            </p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ($tGallery as $tGalleryUrl)
                                    <a href="{{ $tGalleryUrl }}" target="_blank" rel="noopener"
                                       class="block aspect-video overflow-hidden rounded-xl border border-line bg-surface-2 transition-shadow hover:shadow-card dark:border-night-line dark:bg-night-3">
                                        <img src="{{ $tGalleryUrl }}" alt="پیش‌نمایش گالری قالب" loading="lazy" class="size-full object-cover transition-transform duration-300 hover:scale-105">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- نسخه‌ها --}}
                    @if (count($tVersions))
                        <div>
                            <p class="mb-2 flex items-center gap-1.5 text-[13px] font-bold">
                                <x-admin-panel::icon name="history" class="size-4 text-brand-600 dark:text-brand-400" />
                                نسخه‌های موجود
                            </p>
                            <div class="space-y-1.5">
                                @foreach (array_slice($tVersions, 0, 5) as $tVer)
                                    <div class="flex items-center justify-between gap-2 rounded-lg border border-line bg-surface-2 px-3 py-2 text-[11px] dark:border-night-line dark:bg-night-3/60">
                                        <span class="font-mono font-bold" dir="ltr">v{{ is_scalar($tVer['version'] ?? null) ? $tVer['version'] : '?' }}</span>
                                        @if (!empty($tVer['file_size']) && is_numeric($tVer['file_size']))
                                            <span class="text-ink-3 dark:text-night-ink-2">{{ number_format((int) $tVer['file_size'] / 1048576, 1) }} مگابایت</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ---- فوتر چسبان: قیمت + عمل اصلی (فاز ۶۷) ---- --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-surface-2/60 px-5 py-3.5 dark:border-night-line dark:bg-night-3/50" wire:key="tpl-footer">
                    {{-- قیمت/وضعیت --}}
                    <div class="text-sm">
                        @if ($tSold)
                            <span class="text-xs font-bold text-amber-600 dark:text-amber-400">قالب اختصاصی فروخته شده</span>
                        @elseif ($tInstalled && ($tInstalled['status'] ?? '') === 'updating')
                            <span class="flex items-center gap-2 text-xs font-bold text-brand-600 dark:text-brand-400">
                                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" /></svg>
                                در حال نصب…
                            </span>
                        @elseif ($tInstalled)
                            <span class="flex items-center gap-1.5 font-extrabold text-emerald-600 dark:text-emerald-400">
                                <x-admin-panel::icon name="check-circle" class="size-4.5" />
                                نصب‌شده@if ($tInstalled['version']) — <span class="fa-digits">v{{ $tInstalled['version'] }}</span>@endif
                            </span>
                        @elseif ($tIsFree)
                            <span class="font-extrabold text-emerald-600 dark:text-emerald-400">رایگان</span>
                        @elseif ($tDetailPurchaseType === 'exclusive' && $tExPrice > 0)
                            <span class="fa-digits font-extrabold">{{ number_format($tExPrice) }}</span>
                            <span class="text-[11px] text-ink-3">تومان — خرید اختصاصی</span>
                        @else
                            @if ($tHasDiscount)
                                <span class="fa-digits text-ink-3 line-through dark:text-night-ink-2">{{ number_format($tOldPrice) }}</span>
                            @endif
                            <span class="fa-digits font-extrabold">{{ number_format($tPrice) }}</span>
                            <span class="text-[11px] text-ink-3">تومان</span>
                        @endif
                    </div>

                    {{-- عمل اصلی — نصب ناموفق هم دوباره می‌تواند بخرد/نصب کند (فاز ۶۷) --}}
                    <div class="flex items-center gap-2">
                        @if ($tSold || ($tInstalled && ($tInstalled['status'] ?? '') === 'installed'))
                            <button type="button" wire:click="closeTemplateDetail" class="ap-btn-outline !py-2.5">بستن</button>
                        @elseif (! ($tInstalled && ($tInstalled['status'] ?? '') === 'updating') && \App\Support\MarketplaceSettings::allowInstall())
                            <button type="button" wire:click="startTemplatePurchase" wire:loading.attr="disabled" wire:target="startTemplatePurchase"
                                    class="ap-btn-primary min-w-40 !py-2.5">
                                <span wire:loading.remove wire:target="startTemplatePurchase" class="flex items-center justify-center gap-2">
                                    <x-admin-panel::icon name="{{ $tIsFree ? 'download' : 'credit-card' }}" class="size-4.5" />
                                    @if ($tIsFree)
                                        نصب قالب
                                    @elseif ($tDetailPurchaseType === 'exclusive')
                                        خرید اختصاصی و ادامه
                                    @else
                                        خرید و نصب
                                    @endif
                                </span>
                                <span wire:loading wire:target="startTemplatePurchase" class="flex items-center justify-center gap-2">
                                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                                    </svg>
                                    {{ $tIsFree ? 'در حال شروع نصب…' : 'در حال انتقال به پرداخت…' }}
                                </span>
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>
