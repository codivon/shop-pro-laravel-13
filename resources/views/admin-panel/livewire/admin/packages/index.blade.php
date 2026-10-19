{{-- بازار پکیج‌ها — پورت back/packages/index.blade.php (فاز ۷-ج۴) --}}
<div class="mx-auto max-w-7xl space-y-5" x-data="{ paymentModal: false }">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="package" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">بازار پکیج‌ها</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">پکیج‌های آماده را نصب و مدیریت کنید</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('np.packages.installed') }}" class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="package-check" class="size-4" />
                ماژول‌های نصب‌شده
            </a>
            <button type="button" wire:click="checkUpdates" wire:loading.attr="disabled" wire:target="checkUpdates"
                    class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="refresh-cw" class="size-4" />
                بررسی آپدیت‌ها
            </button>
        </div>
    </div>

    {{-- ★ بنر اشتراک فعال (پورت دقیق) --}}
    @if (!empty($subscriptionSummary) && ($subscriptionSummary['has_active_subscription'] ?? false))
        <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-emerald-200 bg-gradient-to-l from-emerald-50 to-teal-50 p-4 dark:border-emerald-500/20 dark:from-emerald-500/10 dark:to-teal-500/10">
            <span class="flex size-10 items-center justify-center rounded-full bg-emerald-500 text-white">
                <x-admin-panel::icon name="award" class="size-5" />
            </span>
            <div class="flex-1">
                <p class="text-sm font-bold text-emerald-700 dark:text-emerald-400">
                    اشتراک فعال: {{ $subscriptionSummary['plan_name'] ?? 'اشتراک' }}
                </p>
                <p class="text-xs text-ink-2 dark:text-night-ink-2">پکیج‌های مشمول اشتراک، بدون پرداخت نصب می‌شوند</p>
            </div>
            <div class="text-center">
                <span class="fa-digits block text-xl font-extrabold text-emerald-600">{{ $subscriptionSummary['days_remaining'] ?? 0 }}</span>
                <span class="text-[11px] text-ink-3 dark:text-night-ink-2">روز باقی‌مانده</span>
            </div>
        </div>
    @else
        <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-line bg-surface p-4 dark:border-night-line dark:bg-night-2">
            <span class="flex size-10 items-center justify-center rounded-full bg-brand-600 text-white">
                <x-admin-panel::icon name="shopping-cart" class="size-5" />
            </span>
            <div class="flex-1">
                <p class="text-sm font-bold">اشتراک فعالی ندارید</p>
                <p class="text-xs text-ink-2 dark:text-night-ink-2">برای دسترسی رایگان به پکیج‌های ویژه، اشتراک را از سایت مرکزی خریداری کنید</p>
                <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs">
                    <span class="text-ink-3 dark:text-night-ink-2">کد پروژه شما:</span>
                    <code class="ltr rounded-lg bg-surface-2 px-2 py-0.5 font-mono text-[11px] dark:bg-night-3">{{ config('packages.api.project_key') }}</code>
                    <button type="button"
                            x-data
                            x-on:click="navigator.clipboard.writeText(@js(config('packages.api.project_key'))); $el.querySelector('span')?.classList.add('text-emerald-500')"
                            class="flex items-center gap-1 text-ink-3 transition-colors hover:text-brand-600 dark:text-night-ink-2"
                            title="کپی کد">
                        <x-admin-panel::icon name="copy" class="size-3.5" />
                    </button>
                </div>
            </div>
            <a href="{{ config('packages.api.base_url') }}" target="_blank" rel="noopener" class="ap-btn !py-2">
                <x-admin-panel::icon name="external-link" class="size-4" />
                خرید اشتراک
            </a>
        </div>
    @endif

    {{-- خطای API (بدون شبکه) — پورت errors/index قدیمی --}}
    @if ($apiError)
        <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
            <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-5 shrink-0" />
            <div>
                <p class="font-bold">ارتباط با سرور پکیج‌ها برقرار نشد</p>
                <p class="mt-0.5 text-xs">{{ $apiError }}</p>
                <button type="button" wire:click="loadPackages" class="mt-2 ap-btn-outline !py-1.5 !text-xs">
                    <x-admin-panel::icon name="rotate-cw" class="size-3.5" />
                    تلاش مجدد
                </button>
            </div>
        </div>
    @endif

    {{-- کارت‌های آماری (پورت pkg-stat قدیمی) --}}
    @php
        $total = count($packages);
        $freeCount = collect($packages)->where('is_free', true)->count();
        $paidCount = $total - $freeCount;
        $installedCount = count($installedMap);
    @endphp
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ([
            ['label' => 'کل پکیج‌ها', 'value' => $total, 'icon' => 'package', 'color' => 'text-brand-600 bg-brand-50 dark:bg-brand-500/12 dark:text-brand-400'],
            ['label' => 'رایگان', 'value' => $freeCount, 'icon' => 'sparkles', 'color' => 'text-sky-600 bg-sky-50 dark:bg-sky-500/12 dark:text-sky-400'],
            ['label' => 'پولی', 'value' => $paidCount, 'icon' => 'coins', 'color' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/12 dark:text-emerald-400'],
            ['label' => 'نصب‌شده', 'value' => $installedCount, 'icon' => 'package-check', 'color' => 'text-violet-600 bg-violet-50 dark:bg-violet-500/12 dark:text-violet-400'],
        ] as $stat)
            <div class="ap-card flex items-center gap-3 p-4">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $stat['color'] }}">
                    <x-admin-panel::icon name="{{ $stat['icon'] }}" class="size-5" />
                </span>
                <div>
                    <span class="fa-digits block text-lg font-extrabold">{{ number_format($stat['value']) }}</span>
                    <span class="text-[11px] text-ink-3 dark:text-night-ink-2">{{ $stat['label'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- فیلترها --}}
    <div class="ap-card p-4 sm:p-5">
        <form wire:submit.prevent="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1.5 block text-xs font-semibold">جستجو</label>
                <input type="text" wire:model.live.debounce.400ms="search" class="ap-input !py-2" placeholder="نام پکیج…">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">دسته‌بندی</label>
                <input type="text" wire:model="category" class="ap-input !py-2" placeholder="مثال: seo">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">ترتیب</label>
                <select wire:model="sort" class="ap-input !py-2">
                    <option value="">پیش‌فرض</option>
                    <option value="newest">جدیدترین</option>
                    <option value="popular">محبوب‌ترین</option>
                    <option value="cheapest">ارزان‌ترین</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="ap-btn w-full !py-2">
                    <x-admin-panel::icon name="search" class="size-4" />
                    اعمال فیلتر
                </button>
            </div>
        </form>
    </div>

    {{-- لیست پکیج‌ها --}}
    @if (count($packages))
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($packages as $pkg)
                @php
                    $installedVersion = $installedMap[$pkg['slug']] ?? null;
                    $pending = $pendingSlugs[$pkg['slug']] ?? null;
                @endphp
                <div class="ap-card flex flex-col overflow-hidden">
                    <div class="flex items-start gap-3 p-4 pb-0">
                        <span class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-surface-2 dark:bg-night-3">
                            @if (\App\Support\PackageMedia::thumbnailUrl($pkg))
                                <img src="{{ \App\Support\PackageMedia::thumbnailUrl($pkg) }}" alt="{{ $pkg['name'] ?? $pkg['slug'] }}" loading="lazy" class="size-full object-cover"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <span class="hidden size-full items-center justify-center" style="display:none">
                                    <x-admin-panel::icon name="package-2" class="size-5 text-ink-3" />
                                </span>
                            @else
                                <x-admin-panel::icon name="package-2" class="size-5 text-ink-3" />
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-sm font-bold">{{ $pkg['name'] ?? $pkg['slug'] }}</h3>
                            <p class="mt-0.5 line-clamp-2 text-xs leading-5 text-ink-3 dark:text-night-ink-2">
                                {{ \App\Support\PackageMedia::descriptionText($pkg['short_description'] ?? $pkg['description'] ?? null, 90) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 px-4 pt-3 text-[11px]">
                        <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                            <x-admin-panel::icon name="hash" class="size-3" /> v{{ $pkg['latest_version']['version'] ?? $pkg['version'] ?? '—' }}
                        </span>
                        @if (!empty($pkg['author']))
                            <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ $pkg['author'] }}</span>
                        @endif
                        @if (!empty($pkg['category']))
                            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">{{ $pkg['category'] }}</span>
                        @endif
                    </div>

                    <div class="mt-auto flex items-center justify-between gap-2 p-4 pt-3">
                        <span class="text-sm font-extrabold {{ ($pkg['is_free'] ?? false) || ($pkg['has_free_plan'] ?? false) ? 'text-emerald-600' : '' }}">
                            {{ \App\Livewire\Admin\Packages\PackageIndex::priceLabel($pkg) }}
                        </span>

                        @if ($pending)
                            <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                                {{ $pending === 'running' ? 'در حال نصب…' : 'در صف نصب' }}
                            </span>
                        @elseif ($installedVersion)
                            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                                نصب‌شده (v{{ $installedVersion }})
                            </span>
                            <a href="{{ route('np.packages.show', ['slug' => $pkg['slug']]) }}" class="ap-btn-outline !px-3 !py-1.5 !text-xs">
                                <x-admin-panel::icon name="eye" class="size-3.5" />
                                جزئیات
                            </a>
                        @elseif (in_array($pkg['slug'], $purchasedSlugs))
                            <button type="button" wire:click="startInstall({{ \Illuminate\Support\Js::from($pkg['slug'])->toHtml() }}, null, false, true)"
                                    wire:loading.attr="disabled" wire:target="startInstall"
                                    class="ap-btn !px-3 !py-1.5 !text-xs">
                                <x-admin-panel::icon name="download" class="size-3.5" />
                                نصب با لایسنس قبلی
                            </button>
                        @else
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('np.packages.show', ['slug' => $pkg['slug']]) }}" class="ap-btn-outline !px-3 !py-1.5 !text-xs">
                                    جزئیات
                                </a>
                                <button type="button" wire:click="startInstall({{ \Illuminate\Support\Js::from($pkg['slug'])->toHtml() }})"
                                        wire:loading.attr="disabled" wire:target="startInstall"
                                        class="ap-btn !px-3 !py-1.5 !text-xs">
                                    <x-admin-panel::icon name="download" class="size-3.5" />
                                    {{ ($pkg['is_free'] ?? false) || ($pkg['has_free_plan'] ?? false) ? 'نصب' : 'خرید و نصب' }}
                                </button>
                            </div>
                        @endif
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
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @for ($i = 1; $i <= $lastPage; $i++)
                        @if (max(1, $currentPage - 2) <= $i && $i <= min($lastPage, $currentPage + 2))
                            @if ($i === $currentPage)
                                <span class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $i }}</span>
                            @else
                                <button wire:click="gotoPage({{ $i }})"
                                        class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                    {{ $i }}
                                </button>
                            @endif
                        @endif
                    @endfor

                    @if ($currentPage >= $lastPage)
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-left" class="size-4" /></span>
                    @else
                        <button wire:click="nextPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه بعد">
                            <x-admin-panel::icon name="chevron-left" class="size-4" />
                        </button>
                    @endif
                </nav>
            </div>
        @endif
    @elseif (!$apiError)
        <div class="ap-card flex flex-col items-center justify-center gap-2 p-10 text-center">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                <x-admin-panel::icon name="package" class="size-6" />
            </span>
            <p class="text-sm font-bold">پکیجی برای نمایش وجود ندارد</p>
            <p class="text-xs text-ink-3 dark:text-night-ink-2">فیلترها را تغییر دهید یا اتصال به سرور پکیج‌ها را بررسی کنید.</p>
        </div>
    @endif

    {{-- نتیجه بررسی آپدیت‌ها (پورت مودال check-updates قدیمی) --}}
    @if (count($updateResults))
        <div class="ap-card overflow-hidden">
            <div class="border-b border-line p-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="refresh-cw" class="size-4 text-brand-600" />
                    آپدیت‌های موجود ({{ count($updateResults) }})
                </h3>
            </div>
            <ul class="divide-y divide-line dark:divide-night-line">
                @foreach ($updateResults as $up)
                    <li class="flex flex-wrap items-center justify-between gap-2 p-4 text-sm">
                        <div>
                            <span class="font-bold">{{ $up['name'] }}</span>
                            <span class="ltr ml-2 text-xs text-ink-3 dark:text-night-ink-2">{{ $up['slug'] }}</span>
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

    {{-- مودال نتیجه پرداخت (پورت session payment_result قدیمی) --}}
    @if ($paymentResult)
        <div x-show="paymentModal" x-cloak x-init="paymentModal = true"
             class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-on:click="paymentModal=false" x-transition.opacity aria-hidden="true"></div>
            <div x-on:click.stop
                 class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
                 role="dialog" aria-modal="true">
                @php
                    $ok = ($paymentResult['status'] ?? '') === 'success';
                @endphp
                <div class="mb-3 flex size-12 items-center justify-center rounded-2xl {{ $ok ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' }}">
                    <x-admin-panel::icon name="{{ $ok ? 'check-circle' : 'circle-alert' }}" class="size-6" />
                </div>
                <h3 class="text-base font-bold">{{ $paymentResult['title'] ?? '' }}</h3>
                <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">{{ $paymentResult['message'] ?? '' }}</p>
                @if (!empty($paymentResult['package_name']))
                    <p class="mt-2 text-xs text-ink-3 dark:text-night-ink-2">
                        پکیج: <b>{{ $paymentResult['package_name'] }}</b>
                        @if (!empty($paymentResult['amount']))
                            — مبلغ: <span class="fa-digits">{{ number_format($paymentResult['amount']) }}</span> تومان
                        @endif
                    </p>
                @endif
                <div class="mt-5">
                    <button type="button" x-on:click="paymentModal=false" class="ap-btn w-full">بستن</button>
                </div>
            </div>
        </div>
    @endif

    {{-- لودینگ --}}
    <div wire:loading wire:target="loadPackages, checkUpdates, startInstall, previousPage, nextPage, gotoPage, applyFilters"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
