{{-- تاریخچه کیف پول — پورت back/wallet-histories/index.blade.php + فیلترهای WalletHistory::scopeFilter --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                همه: <span class="fa-digits">{{ number_format($this->statistics['total']) }}</span>
            </span>
            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                افزایش اعتبار موفق: <span class="fa-digits">{{ number_format($this->statistics['deposit']) }}</span>
            </span>
            <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">
                برداشت موفق: <span class="fa-digits">{{ number_format($this->statistics['withdraw']) }}</span>
            </span>
        </div>
    </div>

    {{-- کارت فیلترها — پورت WalletHistory::scopeFilter (status_pay/source/ordering) --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر تاریخچه کیف پول
                @if ($statusPay || $source || $ordering === 'oldest')
                    <span class="size-2 rounded-full bg-brand-500"></span>
                @endif
            </span>
            <span class="ap-chevron" :class="open ? 'is-open' : ''">
                <x-admin-panel::icon name="chevron-left" class="size-4 text-ink-3" />
            </span>
        </button>

        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="pt-4">
            <form wire:submit.prevent="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت پرداخت</label>
                    <select wire:model.live="statusPay" class="ap-input !py-2">
                        <option value="">همه</option>
                        <option value="waiting">در انتظار پرداخت</option>
                        <option value="pay">پرداخت شده</option>
                        <option value="unpay">پرداخت نشده</option>
                        <option value="unpay-refund">پرداخت نشده و بازگشت وجه</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">منبع</label>
                    <select wire:model.live="source" class="ap-input !py-2">
                        <option value="">همه</option>
                        <option value="user">کاربران سایت</option>
                        <option value="seller">فروشندگان</option>
                        <option value="admin">مدیران</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">مرتب سازی</label>
                    <select wire:model.live="ordering" class="ap-input !py-2">
                        <option value="latest">جدیدترین</option>
                        <option value="oldest">قدیمی ترین</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="ap-btn-outline !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        بازنشانی فیلترها
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="history" class="size-4.5 text-brand-600 dark:text-brand-400" />
                تاریخچه کیف پول
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-2 py-3 text-start font-bold">ID</th>
                        <th class="px-3 py-3 text-start font-bold">کاربر مربوطه</th>
                        <th class="px-3 py-3 text-start font-bold">مبلغ (تومان)</th>
                        <th class="px-3 py-3 text-start font-bold">نوع تراکنش</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->histories as $history)
                        @php
                            $is_deposit = $history->type == 'deposit';
                        @endphp
                        <tr wire:key="wallet-history-{{ $history->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-2 py-3 fa-digits font-bold">{{ number_format($history->id) }}</td>
                            <td class="max-w-44 px-3 py-3">
                                @if ($history->wallet?->user)
                                    <a href="{{ route('np.users.show', $history->wallet->user) }}" wire:navigate
                                       class="block truncate font-semibold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400"
                                       title="نمایش کاربر">
                                        {{ $history->wallet->user->fullname }}
                                    </a>
                                @elseif ($history->wallet?->seller)
                                    <a href="{{ route('np.sellers.show', $history->wallet->seller) }}" wire:navigate
                                       class="flex items-center gap-1.5 truncate font-semibold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400"
                                       title="نمایش فروشنده">
                                        {{ $history->wallet->sellerInfo?->fullname ?? $history->wallet->seller->fullname }}
                                        <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">فروشنده</span>
                                    </a>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">حذف شده</span>
                                @endif
                            </td>
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
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">ناموفق</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <a href="{{ route('np.wallets.history', ['history' => $history->id]) }}" wire:navigate
                                   class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                   title="مشاهده جزئیات" aria-label="مشاهده">
                                    <x-admin-panel::icon name="eye" class="size-4" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="history" class="size-10 opacity-40" />
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
                    <span class="fa-digits">{{ number_format($this->histories->total()) }}</span> رکورد
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
    <div wire:loading wire:target="statusPay, source, ordering, previousPage, nextPage, gotoPage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
