{{-- مانیتور سیستم — پورت back/pulse/index.blade.php + PulseService (فاز ۷-ج۴) --}}
{{-- ★ SSE قدیمی با wire:poll.10s جایگزین شد (به‌روزرسانی دیتا هر ۱۰ ثانیه — همان رفتار) --}}
<div class="mx-auto max-w-7xl space-y-5" wire:poll.10s="pollData">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                <x-admin-panel::icon name="activity" class="size-6" />
            </span>
            <div>
                <h1 class="flex items-center gap-2 text-lg font-bold">
                    مانیتور سیستم
                    <span class="relative flex size-2.5" title="زنده">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                        <span class="relative inline-flex size-2.5 rounded-full bg-emerald-500"></span>
                    </span>
                </h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">Laravel Pulse — نظارت زنده (به‌روزرسانی خودکار هر ۱۰ ثانیه)</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if ($lastUpdate)
                <span class="ltr text-[11px] text-ink-3 dark:text-night-ink-2">last update: {{ $lastUpdate }}</span>
            @endif
            <button type="button" wire:click="refreshData" wire:loading.attr="disabled" wire:target="refreshData"
                    class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="refresh-cw" class="size-4" />
                رفرش فوری
            </button>
            <a href="{{ url('/pulse') }}" target="_blank" rel="noopener" class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="external-link" class="size-4" />
                Pulse اصلی
            </a>
        </div>
    </div>

    {{-- هشدار محدودیت‌های هاست (پورت capabilities قدیمی) --}}
    @php
        $caps = $pulse['capabilities'] ?? [];
        $missing = [];
        if (!($caps['sys_getloadavg'] ?? true)) $missing[] = 'دریافت CPU';
        if (!($caps['proc_meminfo'] ?? true)) $missing[] = 'اطلاعات حافظه';
        if (!($caps['disk_total_space'] ?? true)) $missing[] = 'اطلاعات دیسک';
        if (!($caps['shell_exec'] ?? true)) $missing[] = 'اجرای دستورات سیستمی';
        if (!($caps['file_get_contents'] ?? true)) $missing[] = 'خواندن فایل‌های سیستمی';
    @endphp
    @if (count($missing))
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400">
            <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-5 shrink-0" />
            <div>
                <p class="font-bold">محدودیت‌های هاست ({{ count($missing) }} مورد)</p>
                <p class="mt-0.5 text-xs">{{ implode('، ', $missing) }} در دسترس نیست؛ برخی آمار ممکن است ناقص نمایش داده شود.</p>
            </div>
        </div>
    @endif

    {{-- کارت‌های آمار سرور (پورت KPI های قدیمی) --}}
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        @php
            $memPct = $pulse['memory_percent'] ?? 0;
            $diskPct = $pulse['disk_percent'] ?? 0;
            $kpis = [
                ['label' => 'CPU', 'value' => ($pulse['cpu'] ?? 0) . '%', 'icon' => 'chart-line', 'bad' => ($pulse['cpu'] ?? 0) > 85, 'color' => 'text-violet-600 bg-violet-50 dark:bg-violet-500/12 dark:text-violet-400'],
                ['label' => 'حافظه', 'value' => ($pulse['memory_percent'] ?? 0) . '%', 'icon' => 'layers', 'bad' => $memPct > 90, 'color' => 'text-sky-600 bg-sky-50 dark:bg-sky-500/12 dark:text-sky-400'],
                ['label' => 'دیسک', 'value' => $diskPct . '%', 'icon' => 'database', 'bad' => $diskPct > 90, 'color' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/12 dark:text-emerald-400'],
                ['label' => 'اتصالات DB', 'value' => ($pulse['db_connections_avail'] ?? false) ? number_format($pulse['db_connections'] ?? 0) : '—', 'icon' => 'database-backup', 'bad' => false, 'color' => 'text-brand-600 bg-brand-50 dark:bg-brand-500/12 dark:text-brand-400'],
            ];
        @endphp
        @foreach ($kpis as $kpi)
            <div class="ap-card flex items-center gap-3 p-4">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $kpi['color'] }}">
                    <x-admin-panel::icon name="{{ $kpi['icon'] }}" class="size-5" />
                </span>
                <div>
                    <span class="fa-digits block text-lg font-extrabold {{ $kpi['bad'] ? 'text-red-500' : '' }}">{{ $kpi['value'] }}</span>
                    <span class="text-[11px] text-ink-3 dark:text-night-ink-2">{{ $kpi['label'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- KPI درخواست‌های کند / خطاها --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="ap-card flex items-center justify-between p-4">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="clock" class="size-5" />
                </span>
                <div>
                    <span class="fa-digits block text-lg font-extrabold {{ ($pulse['slow_requests_count'] ?? 0) > 0 ? 'text-red-500' : '' }}">{{ number_format($pulse['slow_requests_count'] ?? 0) }}</span>
                    <span class="text-[11px] text-ink-3 dark:text-night-ink-2">درخواست کند (۲۴ ساعت)</span>
                </div>
            </div>
            <span class="text-[11px] {{ ($pulse['slow_requests_count'] ?? 0) > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                {{ ($pulse['slow_requests_count'] ?? 0) > 0 ? 'نیاز به بررسی' : 'بدون مشکل' }}
            </span>
        </div>
        <div class="ap-card flex items-center justify-between p-4">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">
                    <x-admin-panel::icon name="triangle-alert" class="size-5" />
                </span>
                <div>
                    <span class="fa-digits block text-lg font-extrabold {{ ($pulse['exceptions_count'] ?? 0) > 0 ? 'text-red-500' : '' }}">{{ number_format($pulse['exceptions_count'] ?? 0) }}</span>
                    <span class="text-[11px] text-ink-3 dark:text-night-ink-2">خطاها (۲۴ ساعت)</span>
                </div>
            </div>
            <span class="text-[11px] {{ ($pulse['exceptions_count'] ?? 0) > 10 ? 'text-red-500' : (($pulse['exceptions_count'] ?? 0) > 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                {{ ($pulse['exceptions_count'] ?? 0) > 10 ? 'زیاد' : (($pulse['exceptions_count'] ?? 0) > 0 ? 'دارد' : 'پاک') }}
            </span>
        </div>
    </div>

    {{-- درخواست‌های کند + کوئری‌های کند --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="ap-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-line p-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="clock" class="size-4 text-brand-600" />
                    درخواست‌های کند
                </h3>
                <div class="flex items-center gap-1.5">
                    <span class="fa-digits ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">{{ count($slowRequests) }} مورد</span>
                    @if (count($slowRequests))
                        <button type="button" wire:click="downloadSlowRequests" wire:loading.attr="disabled" wire:target="downloadSlowRequests"
                                title="دانلود گزارش درخواست‌های کند (متنی — قابل ارسال به پشتیبانی)"
                                class="flex size-7 items-center justify-center rounded-lg text-ink-3 transition hover:bg-surface-2 hover:text-brand-600 dark:text-night-ink-2 dark:hover:bg-night-3 dark:hover:text-brand-400">
                            <x-admin-panel::icon name="download" class="size-4" wire:loading.remove wire:target="downloadSlowRequests" />
                            <svg class="size-4 animate-spin" wire:loading wire:target="downloadSlowRequests" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                            <span class="sr-only">دانلود گزارش</span>
                        </button>
                    @endif
                </div>
            </div>
            <div class="max-h-80 overflow-y-auto">
                @if (count($slowRequests))
                    <table class="w-full text-[12px]">
                        <thead>
                            <tr class="bg-surface-2 text-[11px] text-ink-3 dark:bg-night-3 dark:text-night-ink-2">
                                <th class="px-3 py-2 text-start font-semibold">Method</th>
                                <th class="px-3 py-2 text-start font-semibold">مسیر (URI)</th>
                                <th class="px-3 py-2 text-start font-semibold">مدت (ms)</th>
                                <th class="px-3 py-2 text-start font-semibold">زمان</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line dark:divide-night-line">
                            @foreach ($slowRequests as $req)
                                <tr>
                                    <td class="px-3 py-2"><span class="ltr ap-badge bg-surface-2 font-mono text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ strtolower($req['method'] ?? 'GET') }}</span></td>
                                    <td class="max-w-[200px] px-3 py-2">
                                        <span class="ltr block truncate" title="{{ $req['uri'] ?? '' }}@if(!empty($req['via'])) — {{ $req['via'] }}@endif">{{ $req['uri'] ?? '' }}</span>
                                        @if (!empty($req['livewire']))
                                            <span class="ltr block truncate text-[9px] text-ink-3 dark:text-night-ink-2" title="{{ $req['via'] ?? '' }}">Livewire update</span>
                                        @endif
                                    </td>
                                    <td class="fa-digits px-3 py-2 {{ ($req['duration'] ?? 0) > 2000 ? 'font-bold text-red-500' : '' }}">{{ number_format($req['duration'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-[10px] text-ink-3 dark:text-night-ink-2">{{ $req['time_ago'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="flex flex-col items-center gap-1.5 p-8 text-center">
                        <x-admin-panel::icon name="circle-check" class="size-6 text-emerald-500" />
                        <span class="text-xs text-ink-3 dark:text-night-ink-2">هیچ درخواست کندی ثبت نشده</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="ap-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-line p-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="database" class="size-4 text-brand-600" />
                    کوئری‌های کند
                </h3>
                <div class="flex items-center gap-1.5">
                    <span class="fa-digits ap-badge bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">{{ count($slowQueries) }} مورد</span>
                    @if (count($slowQueries))
                        <button type="button" wire:click="downloadSlowQueries" wire:loading.attr="disabled" wire:target="downloadSlowQueries"
                                title="دانلود گزارش کوئری‌های کند (متنی — قابل ارسال به پشتیبانی)"
                                class="flex size-7 items-center justify-center rounded-lg text-ink-3 transition hover:bg-surface-2 hover:text-brand-600 dark:text-night-ink-2 dark:hover:bg-night-3 dark:hover:text-brand-400">
                            <x-admin-panel::icon name="download" class="size-4" wire:loading.remove wire:target="downloadSlowQueries" />
                            <svg class="size-4 animate-spin" wire:loading wire:target="downloadSlowQueries" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                            <span class="sr-only">دانلود گزارش</span>
                        </button>
                    @endif
                </div>
            </div>
            <div class="max-h-80 overflow-y-auto">
                @if (count($slowQueries))
                    <table class="w-full text-[12px]">
                        <thead>
                            <tr class="bg-surface-2 text-[11px] text-ink-3 dark:bg-night-3 dark:text-night-ink-2">
                                <th class="px-3 py-2 text-start font-semibold">SQL</th>
                                <th class="px-3 py-2 text-start font-semibold">مدت (ms)</th>
                                <th class="px-3 py-2 text-start font-semibold">زمان</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line dark:divide-night-line">
                            @foreach ($slowQueries as $q)
                                <tr>
                                    <td class="max-w-[280px] px-3 py-2">
                                        <span class="ltr block truncate font-mono text-[10px]" title="{{ $q['sql'] ?? '' }}">{{ \Illuminate\Support\Str::limit($q['sql'] ?? '', 80) }}</span>
                                        @if (!empty($q['source']))
                                            <span class="ltr block truncate text-[9px] text-ink-3 dark:text-night-ink-2" title="{{ $q['source'] }}">{{ $q['source'] }}</span>
                                        @endif
                                    </td>
                                    <td class="fa-digits px-3 py-2 {{ ($q['duration'] ?? 0) > 1000 ? 'font-bold text-red-500' : '' }}">{{ number_format($q['duration'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-[10px] text-ink-3 dark:text-night-ink-2">{{ $q['time_ago'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="flex flex-col items-center gap-1.5 p-8 text-center">
                        <x-admin-panel::icon name="circle-check" class="size-6 text-emerald-500" />
                        <span class="text-xs text-ink-3 dark:text-night-ink-2">هیچ کوئری کندی ثبت نشده</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- جاب‌های صف --}}
    <div class="ap-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-line p-4 dark:border-night-line">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="layers" class="size-4 text-brand-600" />
                جاب‌های صف
            </h3>
            <span class="fa-digits ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ count($queueJobs) }} مورد</span>
        </div>
        <div class="max-h-80 overflow-y-auto">
            @if (count($queueJobs))
                <table class="w-full text-[12px]">
                    <thead>
                        <tr class="bg-surface-2 text-[11px] text-ink-3 dark:bg-night-3 dark:text-night-ink-2">
                            <th class="px-3 py-2 text-start font-semibold">Job</th>
                            <th class="px-3 py-2 text-start font-semibold">صف</th>
                            <th class="px-3 py-2 text-start font-semibold">وضعیت</th>
                            <th class="px-3 py-2 text-start font-semibold">مدت (s)</th>
                            <th class="px-3 py-2 text-start font-semibold">زمان</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line dark:divide-night-line">
                        @foreach ($queueJobs as $job)
                            <tr>
                                <td class="ltr max-w-[240px] px-3 py-2"><span class="block truncate font-mono text-[10px]" title="{{ $job['job'] ?? '' }}">{{ $job['job'] ?? '' }}</span></td>
                                <td class="px-3 py-2"><span class="ltr ap-badge bg-surface-2 text-[10px] text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ $job['queue'] ?? 'default' }}</span></td>
                                <td class="px-3 py-2">
                                    @php $st = $job['status'] ?? ''; @endphp
                                    <span class="ap-badge text-[10px] {{ $st === 'done' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' : ($st === 'failed' ? 'bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400') }}">
                                        @switch($st)
                                            @case('done') ✓ موفق @break
                                            @case('running') ⟳ اجرا @break
                                            @case('failed') ✗ شکست @break
                                            @case('pending') ⏳ انتظار @break
                                            @case('slow') 🐢 کند @break
                                            @default {{ $st }}
                                        @endswitch
                                    </span>
                                </td>
                                <td class="fa-digits px-3 py-2">{{ $job['duration'] !== null ? number_format($job['duration'], 1) : '—' }}</td>
                                <td class="px-3 py-2 text-[10px] text-ink-3 dark:text-night-ink-2">{{ $job['time_ago'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="flex flex-col items-center gap-1.5 p-8 text-center">
                    <x-admin-panel::icon name="layers" class="size-6 text-ink-3" />
                    <span class="text-xs text-ink-3 dark:text-night-ink-2">هیچ جابی ثبت نشده</span>
                </div>
            @endif
        </div>
    </div>

    {{-- خطاها (Exceptions) --}}
    <div class="ap-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-line p-4 dark:border-night-line">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="triangle-alert" class="size-4 text-red-500" />
                خطاها (Exceptions)
            </h3>
            <span class="fa-digits ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">{{ count($exceptions) }} نوع</span>
        </div>
        <div class="space-y-3 p-4">
            @if (count($exceptions))
                @foreach ($exceptions as $exc)
                    <div class="rounded-xl border border-line p-3 dark:border-night-line">
                        <p class="ltr text-[12px] font-bold text-red-600">{{ $exc['class_short'] ?? ($exc['class'] ?? '') }}</p>
                        <p class="mt-1 text-[12px] leading-5 text-ink-2 dark:text-night-ink-2">{{ $exc['message'] ?? '' }}</p>
                        <p class="mt-1.5 flex flex-wrap items-center gap-2 text-[10px] text-ink-3 dark:text-night-ink-2">
                            <span class="fa-digits ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">{{ number_format($exc['count'] ?? 0) }} بار</span>
                            <span>آخرین بار: {{ $exc['time_ago'] ?? '' }}</span>
                            <span class="ltr">{{ $exc['last_seen'] ?? '' }}</span>
                        </p>
                    </div>
                @endforeach
            @else
                <div class="flex flex-col items-center gap-1.5 py-6 text-center">
                    <x-admin-panel::icon name="circle-check" class="size-6 text-emerald-500" />
                    <span class="text-xs text-ink-3 dark:text-night-ink-2">هیچ خطایی ثبت نشده</span>
                </div>
            @endif
        </div>
    </div>

    {{-- وضعیت دسترسی به توابع سرور (پورت capabilities قدیمی) --}}
    @if (!empty($caps))
        <div class="ap-card overflow-hidden">
            <div class="border-b border-line p-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="shield" class="size-4 text-violet-600" />
                    وضعیت دسترسی به توابع سرور
                </h3>
            </div>
            <div class="grid grid-cols-2 gap-x-6 gap-y-1 p-4 text-[12px] md:grid-cols-4">
                @foreach ($caps as $func => $enabled)
                    <div class="flex items-center justify-between gap-2 border-b border-line/60 py-1.5 dark:border-night-line/60">
                        <span class="ltr font-mono text-[11px] text-ink-3 dark:text-night-ink-2">{{ $func }}</span>
                        <span class="{{ $enabled ? 'text-emerald-600' : 'text-red-500' }}">{{ $enabled ? '✓ فعال' : '✗ غیرفعال' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
