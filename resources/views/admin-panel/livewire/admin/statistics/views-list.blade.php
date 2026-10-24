{{-- لیست بازدیدها — پورت views/viewsList قدیمی (فاز ۷-ج۲) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- کارت‌های آمار --}}
    <section class="stagger grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="eye" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">کل بازدیدها</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa($stats['views']) }}</p>
                </div>
            </div>
        </div>
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-indigo-50 text-indigo-600 dark:bg-indigo-500/12 dark:text-indigo-400">
                    <x-admin-panel::icon name="users" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">بازدیدکنندگان یکتا</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa($stats['unique']) }}</p>
                </div>
            </div>
        </div>
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                    <x-admin-panel::icon name="user-check" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">کاربران عضو</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa($stats['users']) }}</p>
                </div>
            </div>
        </div>
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="user" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">مهمان‌ها (IP یکتا)</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa($stats['guests']) }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- فیلتر بازه --}}
    <div class="ap-card p-4 sm:p-5">
        <form wire:submit="applyFilters" class="flex flex-wrap items-end gap-4">
            <div class="flex flex-wrap gap-1.5">
                @foreach (['daily' => 'روزانه', 'weekly' => 'هفتگی', 'monthly' => 'ماهانه', 'yearly' => 'سالانه'] as $key => $label)
                    <button type="button" wire:click="applyPeriod('{{ $key }}')"
                            class="rounded-xl px-3.5 py-2 text-[13px] font-bold transition-colors
                                {{ $period === $key
                                    ? 'bg-brand-600 text-white shadow-sm'
                                    : 'bg-surface-2 text-ink-2 hover:bg-surface-3 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-night-2' }}">
                        {{ $label }}
                    </button>
                @endforeach
                <button type="button" wire:click="applyPeriod('custom')"
                        class="flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-[13px] font-bold transition-colors
                            {{ $period === 'custom'
                                ? 'bg-brand-600 text-white shadow-sm'
                                : 'bg-surface-2 text-ink-2 hover:bg-surface-3 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-night-2' }}">
                    <x-admin-panel::icon name="calendar" class="size-4" />
                    بازه دلخواه
                </button>
            </div>

            <div class="ms-auto flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="viewslist-from-date">از تاریخ</label>
                    <input id="viewslist-from-date" type="text" wire:model="from_date" data-jdp data-jdp-only-date="true"
                           autocomplete="off" dir="ltr"
                           class="ap-input !py-2 !pe-9 font-mono" placeholder="1400-01-01">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="viewslist-to-date">تا تاریخ</label>
                    <input id="viewslist-to-date" type="text" wire:model="to_date" data-jdp data-jdp-only-date="true"
                           autocomplete="off" dir="ltr"
                           class="ap-input !py-2 !pe-9 font-mono" placeholder="1400-12-29">
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="applyFilters"
                        class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="filter" class="size-4" />
                    اعمال بازه
                </button>
            </div>
        </form>
    </div>

    {{-- نمودار روند --}}
    <section class="ap-card overflow-hidden">
        <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
            <div class="flex items-center gap-2.5">
                <x-admin-panel::icon name="chart-line" class="size-4.5 text-brand-600 dark:text-brand-400" />
                <h3 class="text-sm font-bold">روند بازدید</h3>
            </div>
            <span class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">
                {{ $rangeFrom }} تا {{ $rangeTo }}
            </span>
        </header>
        <div data-chart="stats-views-list" data-options="{{ json_encode($this->viewsChartOptions()) }}" class="h-80 w-full"></div>
    </section>

    {{-- جدول بازدیدها --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="px-3 py-3 text-start font-bold">کاربر</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="px-3 py-3 text-start font-bold">IP</th>
                        <th class="px-3 py-3 text-start font-bold">پلتفرم</th>
                        <th class="px-3 py-3 text-start font-bold">آدرس صفحه</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->views as $view)
                        @php($platform = get_option_property($view->options, 'platform'))
                        <tr wire:key="view-{{ $view->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                @if ($view->user)
                                    <a href="{{ route('np.users.show', ['user' => $view->user]) }}" wire:navigate
                                       class="flex items-center gap-2 font-semibold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400">
                                        <span class="flex size-7 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">
                                            {{ mb_substr($view->user->fullname, 0, 1) }}
                                        </span>
                                        {{ $view->user->fullname }}
                                    </a>
                                @else
                                    <span class="flex items-center gap-1.5 text-ink-3 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="user" class="size-4" />
                                        مهمان
                                    </span>
                                @endif
                            </td>
                            <td class="fa-digits whitespace-nowrap px-3 py-3 text-ink-2 dark:text-night-ink-2">
                                {{ substr((string) jdate($view->created_at, 'Y/m/d H:i'), 0, 16) }}
                            </td>
                            <td class="px-3 py-3"><code class="text-[11px]" dir="ltr">{{ $view->ip }}</code></td>
                            <td class="px-3 py-3 text-ink-2 dark:text-night-ink-2">{{ $platform ?: 'نامشخص' }}</td>
                            <td class="max-w-64 px-3 py-3">
                                <a href="{{ url(urldecode($view->path)) }}" target="_blank" rel="noopener"
                                   class="block truncate text-brand-700 hover:underline dark:text-brand-400" title="{{ urldecode($view->path) }}">
                                    {{ urldecode($view->path) }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="eye-off" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">در این بازه بازدیدی ثبت نشده!</p>
                                    <p class="text-xs">بازه یا فیلتر زمانی دیگری را انتخاب کنید.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی --}}
        <div class="border-t border-line px-4 py-3 dark:border-night-line">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    نمایش <span class="fa-digits">{{ number_format($this->views->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->views->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->views->total()) }}</span> بازدید
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->views->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->views->onEachSide(1)->toArray()['links'] as $link)
                        @if ($link['label'] === '...')
                            <span class="px-2 text-xs text-ink-3">…</span>
                        @elseif (! is_numeric(trim($link['label'])))
                            {{-- دکمه‌های قبلی/بعدی بالاتر مدیریت شده‌اند --}}
                        @elseif ($link['active'])
                            <span class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $link['label'] }}</span>
                        @else
                            <button wire:click="gotoPage({{ trim($link['label']) }})"
                                    class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                {{ $link['label'] }}
                            </button>
                        @endif
                    @endforeach

                    @if ($this->views->hasMorePages())
                        <button wire:click="nextPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه بعد">
                            <x-admin-panel::icon name="chevron-left" class="size-4" />
                        </button>
                    @else
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-left" class="size-4" /></span>
                    @endif
                </nav>
            </div>
        </div>
    </div>
</div>

@script
<script>
    /* به‌روزرسانی نمودار پس از تغییر فیلترها — داده از کامپوننت dispatch می‌شود */
    $wire.on('chart-update', (payload) => {
        const charts = (payload && payload.charts) ? payload.charts : {};

        Object.keys(charts).forEach((key) => {
            const el = document.querySelector('[data-chart="' + key + '"]');
            if (!el || !window.ApChart || !window.ApECharts) return;

            const options = window.ApChart.resolveFormatters(charts[key]);
            let chart = window.ApChart.instances[key];

            if (!chart || chart.getDom() !== el) {
                chart = window.ApChart.instances[key] = window.ApECharts.init(el);
            }

            chart.setOption(options, true);
            chart.resize();
        });
    });
</script>
@endscript
