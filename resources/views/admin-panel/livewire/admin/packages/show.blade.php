{{-- جزئیات پکیج — پورت کامل back/packages/show.blade.php + show.js قدیمی (فاز ۱۴) --}}
<div class="mx-auto max-w-6xl space-y-5">

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

            {{-- ================= ستون اصلی ================= --}}
            <div class="space-y-5 lg:col-span-2">

                {{-- کارت سربرگ (پورت Header card قدیمی) --}}
                <div class="ap-card overflow-hidden">
                    <div class="flex items-start gap-4 p-5">
                        <span class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-surface-2 dark:bg-night-3">
                            @if (\App\Support\PackageMedia::thumbnailUrl($package))
                                <img src="{{ \App\Support\PackageMedia::thumbnailUrl($package) }}" alt="{{ $package['name'] ?? $slug }}" class="size-full object-cover"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <span class="hidden size-full items-center justify-center" style="display:none">
                                    <x-admin-panel::icon name="package-2" class="size-8 text-ink-3" />
                                </span>
                            @else
                                <x-admin-panel::icon name="package-2" class="size-8 text-ink-3" />
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <h2 class="text-base font-bold">{{ $package['name'] ?? $slug }}</h2>
                                <span class="ltr fa-digits ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                    v{{ $package['latestVersion'] ?? '—' }}
                                </span>
                                @if (!empty($package['is_free']))
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">رایگان</span>
                                @else
                                    <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">پولی</span>
                                @endif
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px]">
                                @if (!empty($package['author']))
                                    <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="user" class="size-3" /> {{ $package['author'] }}
                                    </span>
                                @endif
                                @if (!empty($package['category']))
                                    <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="tag" class="size-3" /> {{ $package['category'] }}
                                    </span>
                                @endif
                                @if (!empty($package['downloads']))
                                    <span class="fa-digits ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="download" class="size-3" /> {{ number_format($package['downloads']) }} دانلود
                                    </span>
                                @endif
                                @if (!empty($package['purchased']))
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">خریداری‌شده</span>
                                @endif
                            </div>

                            {{-- توضیح کوتاه — رندر HTML مثل قدیمی ({!! short_description !!}) --}}
                            <div class="pkg-prose mt-2.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                                {!! sanitize_rich_text($package['short_description'] ?? ($package['description'] ?? '')) !!}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- توضیحات کامل + تاریخچه نسخه‌ها (پورت کارت «توضیحات کامل» قدیمی) --}}
                @if (!empty($package['long_description']) || !empty($package['changelog']))
                    <div class="ap-card overflow-hidden">
                        <div class="border-b border-line p-4 dark:border-night-line">
                            <h3 class="flex items-center gap-2 text-sm font-bold">
                                <x-admin-panel::icon name="file-text" class="size-4 text-brand-600" />
                                توضیحات کامل
                            </h3>
                        </div>
                        <div class="space-y-4 p-5">
                            @if (!empty($package['long_description']))
                                <div class="pkg-prose text-[13px] leading-7 text-ink-2 dark:text-night-ink-2">
                                    {!! sanitize_rich_text($package['long_description']) !!}
                                </div>
                            @endif

                            @if (!empty($package['changelog']))
                                <div>
                                    <h4 class="mb-2 flex items-center gap-1.5 text-xs font-bold">
                                        <x-admin-panel::icon name="history" class="size-3.5 text-ink-3" />
                                        تاریخچه نسخه‌ها
                                    </h4>
                                    <ul class="space-y-2">
                                        @foreach ($package['changelog'] as $ver => $changes)
                                            <li class="flex items-start gap-2 rounded-xl border border-line p-2.5 text-xs leading-6 dark:border-night-line">
                                                <span class="ltr fa-digits shrink-0 rounded-lg bg-surface-2 px-2 py-0.5 font-bold dark:bg-night-3">v{{ $ver }}</span>
                                                <span class="text-ink-2 dark:text-night-ink-2">{!! sanitize_rich_text(is_array($changes) ? implode('<br>', e($changes)) : e($changes)) !!}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- پلن‌های قیمت‌گذاری --}}
                @if (!empty($package['active_pricing_plans']) || !empty($package['pricing_plans']) || !empty($package['plans']))
                    <div class="ap-card overflow-hidden">
                        <div class="border-b border-line p-4 dark:border-night-line">
                            <h3 class="text-sm font-bold">پلن‌های قیمت‌گذاری</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2">
                            @foreach (($package['active_pricing_plans'] ?? $package['pricing_plans'] ?? $package['plans'] ?? []) as $plan)
                                <div class="rounded-xl border border-line p-3.5 dark:border-night-line">
                                    <div class="flex items-center justify-between gap-2">
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
                                    <button type="button" wire:click="openInstall({{ $plan['id'] ?? 'null' }})"
                                            class="ap-btn mt-3 w-full !py-1.5 !text-xs">
                                        <x-admin-panel::icon name="download" class="size-3.5" />
                                        نصب این پلن
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- لاگ‌های اخیر نصب/آپدیت (پورت جدول لاگ قدیمی با نشان‌های وضعیت) --}}
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
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                            {{ \App\Livewire\Admin\Packages\PackageShow::actionLabel($log['action']) }}
                                        </span>
                                        @if ($log['status'] === 'success')
                                            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                                                <x-admin-panel::icon name="check" class="size-3" /> موفق
                                            </span>
                                        @elseif ($log['status'] === 'failed')
                                            <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400">
                                                <x-admin-panel::icon name="x" class="size-3" /> ناموفق
                                            </span>
                                        @else
                                            <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                                                در حال اجرا
                                            </span>
                                        @endif
                                        @if (!empty($log['from_version']) || !empty($log['to_version']))
                                            <span class="ltr fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">
                                                {{ $log['from_version'] ?: '—' }} ← {{ $log['to_version'] ?: '—' }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 text-[11px] text-ink-3 dark:text-night-ink-2">
                                        <span class="max-w-[260px] truncate" title="{{ $log['message'] }}">{{ $log['message'] ?: '—' }}</span>
                                        <span class="shrink-0">{{ $log['created_at'] }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="p-4 text-xs text-ink-3 dark:text-night-ink-2">لاگی ثبت نشده است.</p>
                    @endif
                </div>
            </div>

            {{-- ================= ستون کنار: پنل عملیات (پورت action-panel قدیمی) ================= --}}
            <div class="space-y-5">

                {{-- جعبه وضعیت (۴ حالت پورت دقیق) --}}
                <div class="ap-card overflow-hidden">
                    <div class="border-b border-line p-4 dark:border-night-line">
                        <h3 class="flex items-center gap-2 text-sm font-bold">
                            <x-admin-panel::icon name="settings" class="size-4 text-brand-600" />
                            عملیات پکیج
                        </h3>
                    </div>
                    <div class="p-4">

                        @if (empty($package['installed']))
                            <div class="flex items-center gap-2 rounded-xl border border-line bg-surface-2 p-3 text-[13px] text-ink-2 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                                <x-admin-panel::icon name="info" class="size-4 shrink-0 text-ink-3" />
                                این پکیج نصب نشده است.
                            </div>
                        @elseif (($package['installed']['status'] ?? '') === 'updating')
                            {{-- polling وضعیت — پورت startPolling قدیمی --}}
                            <div wire:poll.3s="refreshStatus" class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-500/20 dark:bg-amber-500/10">
                                <p class="flex items-center gap-2 text-[13px] font-bold text-amber-700 dark:text-amber-400">
                                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                                    </svg>
                                    در حال نصب/آپدیت…
                                </p>
                                @if (!empty($logs) && ($logs[0]['status'] ?? '') === 'running')
                                    <p class="mt-1.5 text-[11px] leading-5 text-amber-600/90 dark:text-amber-400/80">
                                        {{ $logs[0]['message'] }}
                                    </p>
                                @endif
                                <p class="mt-1 text-[11px] text-amber-600/70 dark:text-amber-400/60">صفحه را باز نگه دارید — پس از اتمام، وضعیت خودکار به‌روز می‌شود.</p>
                            </div>
                        @elseif (($package['installed']['status'] ?? '') === 'failed')
                            <div class="rounded-xl border border-red-200 bg-red-50 p-3 dark:border-red-500/20 dark:bg-red-500/10">
                                <p class="flex items-center gap-2 text-[13px] font-bold text-red-700 dark:text-red-400">
                                    <x-admin-panel::icon name="triangle-alert" class="size-4" />
                                    نصب با خطا مواجه شد.
                                </p>
                                @if (!empty($package['installed']['last_error']))
                                    <p class="mt-1 text-[11px] leading-5 text-red-600/90 dark:text-red-400/80">{{ $package['installed']['last_error'] }}</p>
                                @endif
                            </div>
                        @else
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                                <p class="flex items-center gap-2 text-[13px] font-bold text-emerald-700 dark:text-emerald-400">
                                    <x-admin-panel::icon name="check-circle-2" class="size-4" />
                                    نصب‌شده — نسخه <span class="fa-digits">{{ $package['installed']['version'] }}</span>
                                </p>
                                @if (!empty($package['installed']['license_expires_human']))
                                    <p class="mt-1 flex items-center gap-1.5 text-[11px] text-emerald-700/80 dark:text-emerald-400/80">
                                        <x-admin-panel::icon name="clock" class="size-3" />
                                        انقضای لایسنس: {{ $package['installed']['license_expires_human'] }}
                                        @if (!empty($package['installed']['is_expired']))
                                            <span class="font-bold text-red-500">(منقضی شده)</span>
                                        @endif
                                    </p>
                                @endif
                                <p class="mt-1 flex items-center gap-1.5 text-[11px] text-emerald-700/80 dark:text-emerald-400/80">
                                    <x-admin-panel::icon name="{{ ($package['installed']['is_active'] ?? false) ? 'circle-check' : 'circle-x' }}" class="size-3" />
                                    وضعیت: {{ ($package['installed']['is_active'] ?? false) ? 'فعال' : 'غیرفعال' }}
                                </p>
                            </div>
                        @endif

                        {{-- دکمه‌های عملیات (پورت d-grid قدیمی) --}}
                        <div class="mt-4 space-y-2">
                            @if (empty($package['installed']))
                                @if (!empty($package['subscription']['is_free_with_subscription']))
                                    <button type="button" wire:click="openInstall(null, true, false)" class="ap-btn w-full !py-2">
                                        <x-admin-panel::icon name="award" class="size-4" />
                                        نصب با اشتراک فعال
                                        @if (!empty($package['subscription']['days_remaining']))
                                            <span class="fa-digits text-[11px] opacity-75">({{ $package['subscription']['days_remaining'] }} روز)</span>
                                        @endif
                                    </button>
                                @endif

                                @if (!empty($package['purchased']))
                                    <button type="button" wire:click="openInstall(null, false, true)" class="ap-btn-outline w-full !py-2">
                                        <x-admin-panel::icon name="download" class="size-4" />
                                        نصب با لایسنس قبلی
                                    </button>
                                @endif

                                <button type="button" wire:click="openInstall(null)" class="ap-btn w-full !py-2">
                                    <x-admin-panel::icon name="download" class="size-4" />
                                    {{ ($package['is_free'] ?? false) ? 'نصب پکیج' : 'پرداخت و نصب' }}
                                </button>
                            @elseif (($package['installed']['status'] ?? '') === 'updating')
                                <button type="button" disabled class="ap-btn w-full !py-2 opacity-60">
                                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                                    </svg>
                                    در حال نصب…
                                </button>
                            @else
                                @if (!empty($package['has_update']))
                                    <button type="button" wire:click="updateModule"
                                            @if (!empty($package['installed']['is_expired'])) disabled @endif
                                            class="ap-btn w-full !py-2 {{ ($package['installed']['is_expired'] ?? false) ? 'pointer-events-none opacity-50' : '' }}">
                                        <x-admin-panel::icon name="arrow-up-circle" class="size-4" />
                                        آپدیت به نسخه <span class="fa-digits">{{ $package['latestVersion'] }}</span>
                                    </button>
                                    @if (!empty($package['installed']['is_expired']))
                                        <p class="text-center text-[11px] font-bold text-red-500">لایسنس منقضی شده است.</p>
                                    @endif
                                @else
                                    <div class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 p-2.5 text-xs font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400">
                                        <x-admin-panel::icon name="check-circle-2" class="size-4" />
                                        آخرین نسخه نصب شده است.
                                    </div>
                                @endif

                                <button type="button" wire:click="toggleActivation"
                                        wire:loading.attr="disabled" wire:target="toggleActivation"
                                        class="ap-btn-outline w-full !py-2">
                                    <x-admin-panel::icon name="{{ ($package['installed']['is_active'] ?? false) ? 'eye-off' : 'eye' }}" class="size-4" />
                                    {{ ($package['installed']['is_active'] ?? false) ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                                </button>

                                <button type="button" wire:click="confirmUninstall" class="ap-btn-outline w-full !border-red-200 !py-2 !text-red-600 hover:!bg-red-50 dark:!border-red-500/20 dark:hover:!bg-red-500/10">
                                    <x-admin-panel::icon name="trash-2" class="size-4" />
                                    حذف ماژول
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- پیش‌نیازها (پورت Requirements قدیمی) --}}
                @if (!empty($package['requirements']))
                    <div class="ap-card p-4">
                        <h3 class="flex items-center gap-2 text-sm font-bold">
                            <x-admin-panel::icon name="info" class="size-4 text-brand-600" />
                            پیش‌نیازها
                        </h3>
                        <ul class="mt-2.5 space-y-1.5 text-xs text-ink-2 dark:text-night-ink-2">
                            @foreach ($package['requirements'] as $req => $ver)
                                <li class="flex items-center gap-1.5">
                                    <code class="ltr rounded-lg bg-surface-2 px-1.5 py-0.5 font-mono dark:bg-night-3">{{ $req }}</code>
                                    <span class="fa-digits">>= {{ $ver }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- اشتراک مرتبط --}}
                @if (!empty($package['subscription']['subscription_expires_at']))
                    <div class="ap-card p-4">
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

    {{-- ================= مودال تأیید نصب (پورت install-confirm-modal قدیمی) ================= --}}
    @if ($confirmingInstall)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" wire:click="cancelInstall" x-transition.opacity aria-hidden="true"></div>
            <div x-data x-on:click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
                 role="dialog" aria-modal="true">
                <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="download" class="size-6" />
                </div>
                <h3 class="text-base font-bold">نصب پکیج</h3>
                <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                    آیا از نصب پکیج <b>{{ $package['name'] ?? $slug }}</b>
                    @if ($installPlanTitle)
                        (پلن: {{ $installPlanTitle }})
                    @endif
                    مطمئن هستید؟
                </p>

                @if (!$installIsFree)
                    <div class="mt-3 flex items-start gap-2.5 rounded-xl border border-sky-200 bg-sky-50 p-3 text-xs leading-6 text-sky-800 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                        <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                        <span>
                            این پکیج پولی است و مبلغ <b class="fa-digits">{{ number_format($installPrice) }}</b> تومان باید پرداخت شود. برای ادامه به درگاه پرداخت هدایت می‌شوید.
                        </span>
                    </div>
                @endif

                <div class="mt-5 flex gap-2.5">
                    <button type="button" wire:click="confirmInstall" wire:loading.attr="disabled" wire:target="confirmInstall"
                            class="ap-btn flex-1">
                        <x-admin-panel::icon name="check" class="size-4" />
                        {{ $installIsFree ? 'شروع نصب' : 'پرداخت و نصب' }}
                    </button>
                    <button type="button" wire:click="cancelInstall" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= مودال تأیید حذف ماژول (پورت uninstall-modal قدیمی) ================= --}}
    @if ($confirmingUninstall)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" wire:click="cancelUninstall" x-transition.opacity aria-hidden="true"></div>
            <div x-data x-on:click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
                 role="dialog" aria-modal="true">
                <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                    <x-admin-panel::icon name="triangle-alert" class="size-6" />
                </div>
                <h3 class="text-base font-bold">حذف ماژول</h3>
                <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                    آیا از حذف <b>{{ $package['name'] ?? $slug }}</b> مطمئن هستید؟
                </p>
                <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-6 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                    تمام فایل‌های ماژول از پوشه Modules حذف می‌شوند و در صورت وجود، جداول ماژول rollback می‌شوند. این عمل غیرقابل بازگشت است.
                </div>
                <div class="mt-5 flex gap-2.5">
                    <button type="button" wire:click="uninstall" wire:loading.attr="disabled" wire:target="uninstall"
                            class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                        <x-admin-panel::icon name="trash-2" class="size-4" />
                        بله، حذف شود
                    </button>
                    <button type="button" wire:click="cancelUninstall" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </div>
        </div>
    @endif

    {{-- لودینگ --}}
    <div wire:loading wire:target="install, confirmInstall, updateModule, toggleActivation, loadPackage, refreshStatus, uninstall"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
