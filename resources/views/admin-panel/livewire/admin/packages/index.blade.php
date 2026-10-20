{{-- بازار پکیج‌ها — فاز ۱۷: بازسازی کامل ظاهر و جریان نصب از پنل قدیمی (back/packages/index.blade.php)
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
                    <h1 class="text-lg font-extrabold">بازار پکیج‌ها</h1>
                    <p class="text-xs text-ink-3 dark:text-night-ink-2">پکیج‌های آماده را نصب و مدیریت کنید</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('np.packages.installed') }}" class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="layout-grid" class="size-4" />
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
            </div>
        </div>
    </div>

    {{-- ============ بنر اشتراک (پورت pkg-sub-banner) ============ --}}
    @if (!empty($subscriptionSummary) && ($subscriptionSummary['has_active_subscription'] ?? false))
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
                <span class="fa-digits block text-xl font-extrabold leading-6 text-emerald-600 dark:text-emerald-400">{{ $subscriptionSummary['days_remaining'] ?? 0 }}</span>
                <span class="text-[10px] font-semibold text-emerald-700/70 dark:text-emerald-400/70">روز باقی‌مانده</span>
            </div>
        </div>
    @else
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
            <a href="{{ config('packages.api.base_url') }}" target="_blank" rel="noopener" class="ap-btn-primary relative !py-2">
                <x-admin-panel::icon name="external-link" class="size-4" />
                خرید اشتراک
            </a>
        </div>
    @endif

    {{-- ============ خطای API (پورت pkg-empty-state + جزئیات خطا) ============ --}}
    @if ($apiError)
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
    @if (count($pendingSlugs))
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

    {{-- ============ کارت‌های آماری (پورت pkg-stat) ============ --}}
    @php
        $total = count($packages);
        $freeCount = collect($packages)->where('is_free', true)->count();
        $paidCount = $total - $freeCount;
        $installedCount = count($installedMap);
    @endphp
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ([
            ['label' => 'کل پکیج‌ها', 'value' => $total, 'icon' => 'package', 'accent' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400'],
            ['label' => 'رایگان', 'value' => $freeCount, 'icon' => 'gift', 'accent' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400'],
            ['label' => 'نصب‌شده', 'value' => $installedCount, 'icon' => 'check-circle', 'accent' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400'],
            ['label' => 'پولی', 'value' => $paidCount, 'icon' => 'coins', 'accent' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400'],
        ] as $stat)
            <div class="ap-card flex items-center gap-3 p-4">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $stat['accent'] }}">
                    <x-admin-panel::icon name="{{ $stat['icon'] }}" class="size-5" />
                </span>
                <div>
                    <span class="fa-digits block text-lg font-extrabold">{{ number_format($stat['value']) }}</span>
                    <span class="text-[11px] text-ink-3 dark:text-night-ink-2">{{ $stat['label'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

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
    </div>

    {{-- ============ شبکه پکیج‌ها (پورت pkg-grid / pkg-card) ============ --}}
    @if (count($packages))
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($packages as $pkg)
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
                <div class="ap-card group flex flex-col overflow-hidden" wire:key="pkg-{{ $slug }}" x-bind:data-slug="{{ \Illuminate\Support\Js::from($slug) }}">
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

                        {{-- مُهر نصب‌شده / در حال نصب --}}
                        @if ($pendingState)
                            <div class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-2 bg-slate-950/60 py-1.5 text-[11px] font-bold text-white backdrop-blur-sm">
                                @if ($pendingState === 'running')
                                    <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                                    </svg>
                                    در حال نصب...
                                @else
                                    <x-admin-panel::icon name="clock" class="size-3.5" />
                                    در صف نصب
                                @endif
                            </div>
                        @elseif ($isInstalled)
                            <div class="absolute end-3 bottom-3 flex items-center gap-1 rounded-full bg-emerald-500 px-2.5 py-1 text-[11px] font-bold text-white shadow">
                                <x-admin-panel::icon name="check" class="size-3" />
                                نصب‌شده
                            </div>
                        @endif

                        {{-- اورلی مشاهده جزئیات --}}
                        <a href="{{ route('np.packages.show', ['slug' => $slug]) }}" wire:navigate
                           class="absolute inset-0 flex items-center justify-center gap-2 bg-slate-950/55 text-[13px] font-bold text-white opacity-0 backdrop-blur-[2px] transition-opacity duration-200 group-hover:opacity-100">
                            <x-admin-panel::icon name="eye" class="size-4.5" />
                            مشاهده جزئیات
                        </a>
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
                            {!! sanitize_rich_text($pkg['short_description'] ?? ($pkg['description'] ?? '')) ?: 'بدون توضیحات' !!}
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
                                    @if (!empty($pkg['subscription']['days_remaining']))
                                        <small class="fa-digits block text-[10px] text-ink-3 dark:text-night-ink-2">{{ $pkg['subscription']['days_remaining'] }} روز باقی‌مانده</small>
                                    @endif
                                @else
                                    <small class="block text-[10px] text-ink-3 dark:text-night-ink-2">از</small>
                                    <span class="fa-digits text-sm font-extrabold">{{ number_format($minPrice) }}</span>
                                    <span class="text-[10px] text-ink-3 dark:text-night-ink-2">تومان</span>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-1.5">
                                @if ($pendingState === 'queued')
                                    <button type="button" disabled class="ap-btn !px-3 !py-1.5 !text-xs opacity-70">
                                        <x-admin-panel::icon name="clock" class="size-3.5" /> در صف نصب
                                    </button>
                                @elseif ($pendingState === 'running')
                                    <button type="button" disabled class="ap-btn !px-3 !py-1.5 !text-xs opacity-70">
                                        <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                                        </svg>
                                        در حال نصب...
                                    </button>
                                @elseif ($hasUpdate)
                                    <a href="{{ route('np.packages.show', ['slug' => $slug]) }}" wire:navigate class="ap-btn-primary !px-3 !py-1.5 !text-xs">
                                        <x-admin-panel::icon name="arrow-up" class="size-3.5" /> آپدیت
                                    </a>
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
    @elseif (! $apiError)
        <div class="ap-card flex flex-col items-center justify-center gap-2 p-10 text-center">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                <x-admin-panel::icon name="package" class="size-6" />
            </span>
            <p class="text-sm font-bold">پکیجی برای نمایش وجود ندارد</p>
            <p class="text-xs text-ink-3 dark:text-night-ink-2">فیلترها را تغییر دهید یا اتصال به سرور پکیج‌ها را بررسی کنید.</p>
        </div>
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
</div>
