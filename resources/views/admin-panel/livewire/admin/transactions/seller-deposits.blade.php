{{-- لیست کمیسیون فروشندگان (واریزی‌ها) — پورت back/transactions/seller_deposits.blade.php --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                همه واریزی‌ها: <span class="fa-digits">{{ number_format($this->statistics['total']) }}</span>
            </span>
            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                موفق: <span class="fa-digits">{{ number_format($this->statistics['success']) }}</span>
            </span>
            <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                جمع مبالغ موفق: <span class="fa-digits">{{ number_format($this->statistics['amount']) }}</span> تومان
            </span>
        </div>
        <a href="{{ route('np.transactions.index') }}" wire:navigate class="ap-btn-outline !py-2 text-[13px]">
            <x-admin-panel::icon name="credit-card" class="size-4" />
            لیست تراکنش ها
        </a>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="percent" class="size-4.5 text-brand-600 dark:text-brand-400" />
                لیست کمیسیون فروشندگان
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-14 px-3 py-3 text-start font-bold">ردیف</th>
                        <th class="px-3 py-3 text-start font-bold">فروشنده مربوطه</th>
                        <th class="px-3 py-3 text-start font-bold">دسته بندی مربوطه</th>
                        <th class="px-3 py-3 text-start font-bold">مبلغ (تومان)</th>
                        <th class="px-3 py-3 text-center font-bold">درصد</th>
                        <th class="px-3 py-3 text-start font-bold">نوع تراکنش</th>
                        <th class="px-3 py-3 text-center font-bold">شماره سفارش</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="px-3 py-3 text-start font-bold">توضیحات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->deposits as $deposit)
                        <tr wire:key="seller-deposit-{{ $deposit->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits">{{ number_format($loop->iteration + ($this->deposits->firstItem() - 1)) }}</td>
                            <td class="max-w-40 px-3 py-3">
                                @if ($deposit->seller)
                                    <a href="{{ route('np.sellers.show', $deposit->seller) }}" wire:navigate
                                       class="block truncate font-semibold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400"
                                       title="نمایش فروشنده">
                                        {{ $deposit->seller->fullname }}
                                    </a>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">حذف شده</span>
                                @endif
                            </td>
                            <td class="max-w-40 px-3 py-3">
                                <p class="truncate">{{ $deposit->category?->title ?? '—' }}</p>
                            </td>
                            <td class="px-3 py-3 fa-digits font-semibold text-emerald-700 dark:text-emerald-400">{{ number_format($deposit->amount) }}</td>
                            <td class="px-3 py-3 text-center fa-digits">%{{ $deposit->percent ?? 0 }}</td>
                            <td class="px-3 py-3">
                                <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                                    <x-admin-panel::icon name="trending-up" class="size-3.5" />
                                    واریز شده
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($deposit->order)
                                    <a href="{{ route('np.orders.show', $deposit->order) }}" wire:navigate
                                       class="fa-digits inline-flex items-center gap-1 text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400">
                                        سفارش {{ number_format($deposit->order_id) }}
                                    </a>
                                @else
                                    <span class="fa-digits">{{ $deposit->order_id ? number_format($deposit->order_id) : '—' }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($deposit->created_at) }}</td>
                            <td class="px-3 py-3 text-center">
                                @if ($deposit->status == 'success')
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">موفق</span>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">ناموفق</span>
                                @endif
                            </td>
                            <td class="max-w-40 px-3 py-3">
                                <p class="truncate" title="{{ $deposit->description }}">{{ $deposit->description ?: '—' }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="percent" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">چیزی برای نمایش وجود ندارد!</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->deposits->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->deposits->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->deposits->total()) }}</span> واریزی
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->deposits->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->deposits->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->deposits->hasMorePages())
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

    {{-- لودینگ --}}
    <div wire:loading wire:target="previousPage, nextPage, gotoPage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
