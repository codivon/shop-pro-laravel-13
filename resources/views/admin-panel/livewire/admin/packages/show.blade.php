{{-- جزئیات پکیج — پورت back/packages/show.blade.php (فاز ۷-ج۴) --}}
<div class="mx-auto max-w-5xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('np.packages.index') }}" class="ap-btn-ghost !p-2" title="بازگشت به بازار پکیج‌ها">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
            </a>
            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="package-2" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">{{ $package['name'] ?? $slug }}</h1>
                <p class="ltr text-xs text-ink-3 dark:text-night-ink-2">{{ $slug }}</p>
            </div>
        </div>
        <a href="{{ route('np.packages.installed') }}" class="ap-btn-outline !py-2">
            <x-admin-panel::icon name="package-check" class="size-4" />
            ماژول‌های نصب‌شده
        </a>
    </div>

    @if ($apiError)
        <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
            <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-5 shrink-0" />
            <div>
                <p class="font-bold">ارتباط با سرور پکیج‌ها برقرار نشد</p>
                <p class="mt-0.5 text-xs">{{ $apiError }}</p>
                <button type="button" wire:click="loadPackage" class="mt-2 ap-btn-outline !py-1.5 !text-xs">
                    <x-admin-panel::icon name="rotate-cw" class="size-3.5" />
                    تلاش مجدد
                </button>
            </div>
        </div>
    @elseif ($package)
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

            {{-- ستون اصلی --}}
            <div class="space-y-5 lg:col-span-2">
                <div class="ap-card overflow-hidden">
                    <div class="flex items-start gap-4 p-5">
                        <span class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-surface-2 dark:bg-night-3">
                            @if (!empty($package['thumbnail']))
                                <img src="{{ $package['thumbnail'] }}" alt="{{ $package['name'] ?? $slug }}" class="size-full object-cover">
                            @else
                                <x-admin-panel::icon name="package-2" class="size-7 text-ink-3" />
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-base font-bold">{{ $package['name'] ?? $slug }}</h2>
                            <p class="mt-1 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">{{ $package['description'] ?? '' }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px]">
                                <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="hash" class="size-3" />
                                    آخرین نسخه: <span class="fa-digits">{{ $package['latestVersion'] ?? '—' }}</span>
                                </span>
                                @if (!empty($package['author']))
                                    <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ $package['author'] }}</span>
                                @endif
                                @if (!empty($package['category']))
                                    <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">{{ $package['category'] }}</span>
                                @endif
                                @if (!empty($package['purchased']))
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">خریداری‌شده</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- پلن‌های قیمت‌گذاری --}}
                @if (!empty($package['active_pricing_plans']) || !empty($package['pricing_plans']) || !empty($package['plans']))
                    <div class="ap-card overflow-hidden">
                        <div class="border-b border-line p-4 dark:border-night-line">
                            <h3 class="text-sm font-bold">پلن‌های قیمت‌گذاری</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2">
                            @foreach (($package['active_pricing_plans'] ?? $package['pricing_plans'] ?? $package['plans'] ?? []) as $plan)
                                <div class="rounded-xl border border-line p-3.5 dark:border-night-line">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-bold">{{ $plan['title'] ?? $plan['name'] ?? ('پلن #' . ($plan['id'] ?? '—')) }}</p>
                                        <p class="text-sm font-extrabold {{ ($plan['discount_price'] ?? $plan['price'] ?? 1) == 0 ? 'text-emerald-600' : '' }}">
                                            @if (($plan['discount_price'] ?? $plan['price'] ?? 0) == 0)
                                                رایگان
                                            @else
                                                <span class="fa-digits">{{ number_format($plan['discount_price'] ?? $plan['price']) }}</span> تومان
                                            @endif
                                        </p>
                                    </div>
                                    @if (!empty($plan['duration_months']))
                                        <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">
                                            مدت: <span class="fa-digits">{{ $plan['duration_months'] }}</span> ماه
                                        </p>
                                    @endif
                                    <button type="button" wire:click="install({{ $plan['id'] ?? 'null' }})"
                                            wire:loading.attr="disabled" wire:target="install"
                                            class="ap-btn mt-3 w-full !py-1.5 !text-xs">
                                        <x-admin-panel::icon name="download" class="size-3.5" />
                                        نصب این پلن
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- لاگ‌های اخیر نصب/آپدیت (پورت دقیق) --}}
                <div class="ap-card overflow-hidden">
                    <div class="border-b border-line p-4 dark:border-night-line">
                        <h3 class="flex items-center gap-2 text-sm font-bold">
                            <x-admin-panel::icon name="history" class="size-4 text-brand-600" />
                            لاگ‌های اخیر
                        </h3>
                    </div>
                    @if (count($logs))
                        <ul class="divide-y divide-line dark:divide-night-line">
                            @foreach ($logs as $log)
                                <li class="flex flex-wrap items-center justify-between gap-2 p-3.5 text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="ap-badge {{ $log['status'] === 'success' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' : ($log['status'] === 'failed' ? 'bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400') }}">
                                            {{ $log['action'] }} — {{ $log['status'] }}
                                        </span>
                                        @if (!empty($log['from_version']) || !empty($log['to_version']))
                                            <span class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">{{ $log['from_version'] }} ← {{ $log['to_version'] }}</span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-ink-3 dark:text-night-ink-2">
                                        {{ $log['message'] }} — {{ $log['created_at'] }}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="p-4 text-xs text-ink-3 dark:text-night-ink-2">لاگی ثبت نشده است.</p>
                    @endif
                </div>
            </div>

            {{-- ستون کنار: نصب و وضعیت --}}
            <div class="space-y-5">
                <div class="ap-card p-5">
                    <h3 class="text-sm font-bold">نصب / خرید</h3>
                    @if (!empty($package['installed']))
                        <div class="mt-3 space-y-2 text-[13px]">
                            <p class="flex items-center justify-between">
                                <span class="text-ink-3 dark:text-night-ink-2">نسخه نصب‌شده</span>
                                <span class="fa-digits font-bold">{{ $package['installed']['version'] }}</span>
                            </p>
                            <p class="flex items-center justify-between">
                                <span class="text-ink-3 dark:text-night-ink-2">وضعیت</span>
                                <span class="font-bold">{{ $package['installed']['status'] }}</span>
                            </p>
                            <p class="flex items-center justify-between">
                                <span class="text-ink-3 dark:text-night-ink-2">فعال</span>
                                <span class="font-bold">{{ $package['installed']['is_active'] ? 'بله' : 'خیر' }}</span>
                            </p>
                            <p class="flex items-center justify-between">
                                <span class="text-ink-3 dark:text-night-ink-2">اعتبار لایسنس</span>
                                <span class="font-bold {{ $package['installed']['is_expired'] ? 'text-red-500' : '' }}">
                                    {{ $package['installed']['is_expired'] ? 'منقضی شده' : ($package['installed']['license_expires_human'] ?? 'نامحدود') }}
                                </span>
                            </p>
                        </div>
                        @if (!empty($package['installed']['last_error']))
                            <p class="mt-3 rounded-xl bg-red-50 p-2.5 text-[11px] leading-5 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                                آخرین خطا: {{ $package['installed']['last_error'] }}
                            </p>
                        @endif
                    @endif

                    <div class="mt-4 space-y-2">
                        @if (!empty($package['subscription']['is_free_with_subscription']))
                            <button type="button" wire:click="install(null, true, false)"
                                    wire:loading.attr="disabled" wire:target="install"
                                    class="ap-btn w-full !py-2">
                                <x-admin-panel::icon name="award" class="size-4" />
                                نصب با اشتراک فعال
                                @if (!empty($package['subscription']['days_remaining']))
                                    <span class="fa-digits text-[11px] opacity-75">({{ $package['subscription']['days_remaining'] }} روز)</span>
                                @endif
                            </button>
                        @endif

                        @if (!empty($package['purchased']))
                            <button type="button" wire:click="install(null, false, true)"
                                    wire:loading.attr="disabled" wire:target="install"
                                    class="ap-btn-outline w-full !py-2">
                                <x-admin-panel::icon name="download" class="size-4" />
                                نصب با لایسنس قبلی
                            </button>
                        @endif

                        <button type="button" wire:click="install(null)"
                                wire:loading.attr="disabled" wire:target="install"
                                class="ap-btn w-full !py-2">
                            <x-admin-panel::icon name="shopping-cart" class="size-4" />
                            {{ ($package['is_free'] ?? false) ? 'نصب رایگان' : 'خرید و نصب' }}
                        </button>
                    </div>
                </div>

                {{-- اشتراک مرتبط --}}
                @if (!empty($package['subscription']['subscription_expires_at']))
                    <div class="ap-card p-5">
                        <h3 class="flex items-center gap-2 text-sm font-bold">
                            <x-admin-panel::icon name="award" class="size-4 text-brand-600" />
                            اشتراک
                        </h3>
                        <p class="mt-2 text-[13px] text-ink-2 dark:text-night-ink-2">
                            @if (!empty($package['subscription']['plan_name']))
                                پلن: <b>{{ $package['subscription']['plan_name'] }}</b>
                            @endif
                            — <span class="fa-digits">{{ $package['subscription']['days_remaining'] ?? 0 }}</span> روز باقی‌مانده
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- لودینگ --}}
    <div wire:loading wire:target="install, loadPackage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
