{{-- مقایسهٔ فروشندگان — فاز ۱۰۶-۷ (گیت multi_vendor_leaderboard) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- کارت‌های آمار --}}
    <section class="stagger grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="store" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">فروشندگان دارای فروش</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa(number_format($this->stats['sellers'])) }}</p>
                </div>
            </div>
        </div>
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                    <x-admin-panel::icon name="banknote" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">جمع درآمد دوره</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa(number_format($this->stats['revenue'])) }}</p>
                </div>
            </div>
        </div>
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                    <x-admin-panel::icon name="receipt" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">سفارش‌های موفق</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa(number_format($this->stats['orders'])) }}</p>
                </div>
            </div>
        </div>
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="chart-column" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">میانگین درآمد هر فروشنده</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa(number_format($this->stats['avg_revenue'])) }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- فیلتر دوره --}}
    <div class="ap-card p-4 sm:p-5">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-[13px] font-bold text-ink-2 dark:text-night-ink-2">دوره:</span>
            <div class="flex flex-wrap gap-1.5">
                @foreach (['30' => '۳۰ روز اخیر', '90' => '۹۰ روز اخیر', 'all' => 'همهٔ زمان‌ها'] as $key => $label)
                    <button type="button" wire:click="applyPeriod('{{ $key }}')"
                            class="rounded-xl px-3.5 py-2 text-[13px] font-bold transition-colors
                                {{ $period === $key
                                    ? 'bg-brand-600 text-white shadow-sm'
                                    : 'bg-surface-2 text-ink-2 hover:bg-surface-3 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-night-2' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- نمودار درآمد ۱۰ فروشندهٔ برتر --}}
    <section class="ap-card overflow-hidden">
        <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
            <div class="flex items-center gap-2.5">
                <x-admin-panel::icon name="trophy" class="size-4.5 text-amber-600 dark:text-amber-400" />
                <h3 class="text-sm font-bold">درآمد ۱۰ فروشندهٔ برتر</h3>
            </div>
            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">مبنای آیتم‌های سفارش‌های پرداخت‌شده</span>
        </header>
        <div data-chart="stats-seller-leaderboard" data-options="{{ json_encode($this->chartOptions()) }}" class="h-96 w-full"></div>
    </section>

    {{-- جدول رتبه‌بندی --}}
    <div class="ap-card overflow-hidden">
        <header class="flex items-center gap-2.5 border-b border-line px-5 py-4 dark:border-night-line">
            <x-admin-panel::icon name="list" class="size-4.5 text-brand-600 dark:text-brand-400" />
            <h3 class="text-sm font-bold">رتبه‌بندی کامل</h3>
        </header>
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="px-3 py-3 text-start font-bold">رتبه</th>
                        <th class="px-3 py-3 text-start font-bold">فروشنده</th>
                        <th class="px-3 py-3 text-start font-bold">درآمد (تومان)</th>
                        <th class="px-3 py-3 text-start font-bold">سفارش‌های موفق</th>
                        <th class="px-3 py-3 text-start font-bold">اقلام فروخته‌شده</th>
                        <th class="px-3 py-3 text-start font-bold">میانگین هر سفارش</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->rows as $row)
                        <tr wire:key="leader-{{ $row->seller_id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                @if ($loop->iteration <= 3)
                                    <span class="flex size-7 items-center justify-center rounded-full bg-amber-100 text-[12px] font-extrabold text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                                        {{ $this->fa($loop->iteration) }}
                                    </span>
                                @else
                                    <span class="fa-digits px-2 text-[13px] font-bold text-ink-3 dark:text-night-ink-2">{{ $this->fa($loop->iteration) }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 font-semibold">
                                <a href="{{ route('np.sellers.show', ['seller' => $row->slug]) }}" wire:navigate
                                   class="text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400">
                                    {{ $row->name }}
                                </a>
                            </td>
                            <td class="fa-digits px-3 py-3 font-bold text-emerald-700 dark:text-emerald-400">{{ number_format($row->revenue) }}</td>
                            <td class="fa-digits px-3 py-3 text-ink-2 dark:text-night-ink-2">{{ number_format($row->orders_count) }}</td>
                            <td class="fa-digits px-3 py-3 text-ink-2 dark:text-night-ink-2">{{ number_format($row->items_count) }}</td>
                            <td class="fa-digits px-3 py-3 text-ink-2 dark:text-night-ink-2">{{ number_format($row->avg_order) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="trophy" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">در این دوره فروشِ فروشنده‌داری ثبت نشده!</p>
                                    <p class="text-xs">پس از اولین سفارشِ پرداخت‌شدهٔ فروشنده‌دار، رتبه‌بندی اینجا ساخته می‌شود.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
