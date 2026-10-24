{{-- تاریخچه کیف پول — پورت back/wallets/show.blade.php --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ: موجودی + ایجاد تراکنش (پورت card-header قدیمی) --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="wallet" class="size-5" />
            </span>
            <div>
                <h1 class="text-base font-bold">تاریخچه کیف پول</h1>
                <p class="mt-0.5 text-[13px] text-ink-3 dark:text-night-ink-2">
                    موجودی: <span class="fa-digits font-bold text-ink-2 dark:text-night-ink-2">{{ number_format($wallet->balance) }}</span> تومان
                </p>
            </div>
        </div>
        <a href="{{ route('np.wallets.create', ['wallet' => $wallet->id]) }}" wire:navigate class="ap-btn !py-2 text-[13px]">
            <x-admin-panel::icon name="plus" class="size-4" />
            ایجاد تراکنش
        </a>
    </div>

    {{-- کارت مالک کیف پول (پورت breadcrumb قدیمی) --}}
    <div class="ap-card flex flex-wrap items-center justify-between gap-3 p-4">
        <div class="flex items-center gap-2 text-[13px]">
            <span class="text-xs font-bold text-ink-3 dark:text-night-ink-2">مالک کیف پول:</span>
            @if ($wallet->user)
                <a href="{{ route('np.users.show', $wallet->user) }}" wire:navigate
                   class="inline-flex items-center gap-1 font-bold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400">
                    {{ $wallet->user->fullname }}
                    <x-admin-panel::icon name="external-link" class="size-3.5" />
                </a>
            @elseif ($wallet->seller)
                <a href="{{ route('np.sellers.show', $wallet->seller) }}" wire:navigate
                   class="inline-flex items-center gap-1.5 font-bold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400">
                    {{ $wallet->sellerInfo?->fullname ?? $wallet->seller->fullname }}
                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">فروشنده</span>
                </a>
            @else
                <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">حذف شده</span>
            @endif
        </div>
        <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
            شناسه کیف پول: <span class="fa-digits">{{ number_format($wallet->id) }}</span>
        </span>
    </div>

    {{-- جدول تاریخچه‌ها (پورت جدول قدیمی) --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-14 px-3 py-3 text-start font-bold">ردیف</th>
                        <th class="px-3 py-3 text-start font-bold">مبلغ (تومان)</th>
                        <th class="px-3 py-3 text-start font-bold">نوع تراکنش</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="w-20 px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->histories as $history)
                        @php
                            $is_deposit = $history->type == 'deposit';
                        @endphp
                        <tr wire:key="wallet-history-{{ $history->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits">{{ number_format($loop->iteration + ($this->histories->firstItem() - 1)) }}</td>
                            <td class="px-3 py-3 fa-digits font-semibold {{ $is_deposit ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ number_format($history->amount) }}
                            </td>
                            <td class="px-3 py-3">
                                @if ($history->withdraw)
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">
                                        <x-admin-panel::icon name="arrow-left" class="size-3.5" />
                                        برداشت وجه
                                    </span>
                                @elseif ($is_deposit)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                                        <x-admin-panel::icon name="trending-up" class="size-3.5" />
                                        افزایش اعتبار
                                    </span>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">
                                        <x-admin-panel::icon name="trending-down" class="size-3.5" />
                                        کاهش اعتبار
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($history->created_at) }}</td>
                            <td class="px-3 py-3 text-center">
                                @if ($history->status == 'success')
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">موفق</span>
                                    @if ($history->withdraw)
                                        <span class="mt-1 block">
                                            @if ($history->status_pay == 'waiting')
                                                <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">در انتظار پرداخت</span>
                                            @elseif ($history->status_pay == 'pay')
                                                <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">پرداخت شده</span>
                                            @elseif ($history->status_pay == 'unpay')
                                                <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">پرداخت نشده</span>
                                            @elseif ($history->status_pay == 'unpay-refund')
                                                <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">پرداخت نشده و مبلغ عودت داده شده</span>
                                            @endif
                                        </span>
                                    @endif
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">ناموفق</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <a href="{{ route('np.wallets.history', ['history' => $history->id]) }}" wire:navigate
                                   class="inline-flex items-center gap-1 rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                   title="مشاهده جزئیات" aria-label="مشاهده">
                                    <x-admin-panel::icon name="eye" class="size-4" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="wallet" class="size-10 opacity-40" />
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
                    نمایش <span class="fa-digits">{{ number_format($this->histories->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->histories->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->histories->total()) }}</span> تراکنش کیف پول
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->histories->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->histories->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->histories->hasMorePages())
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
