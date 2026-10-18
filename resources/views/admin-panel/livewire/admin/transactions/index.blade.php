{{-- لیست تراکنش ها — پنل جدید (فاز ۴ — ماژول مالی) — پورت back/transactions/index.blade.php --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                همه تراکنش‌ها: <span class="fa-digits">{{ number_format($this->statistics['total']) }}</span>
            </span>
            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                موفق: <span class="fa-digits">{{ number_format($this->statistics['success']) }}</span>
            </span>
            <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">
                ناموفق: <span class="fa-digits">{{ number_format($this->statistics['failed']) }}</span>
            </span>
        </div>
        <a href="{{ route('np.transactions.seller-deposits') }}" wire:navigate
           class="ap-btn-outline !py-2 text-[13px]">
            <x-admin-panel::icon name="percent" class="size-4" />
            کمیسیون فروشندگان
        </a>
    </div>

    {{-- کارت فیلترها — پورت فیلترهای قدیمی (fullname/transId/username/order_id/id/status) --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر تراکنش‌ها
                @if ($fullname || $username || $transId || $cardNumber || $transactionId || $status || $orderId)
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
                    <label class="mb-1.5 block text-xs font-semibold">نام و نام خانوادگی</label>
                    <input type="text" wire:model.live.debounce.400ms="fullname" class="ap-input !py-2" placeholder="نام پرداخت‌کننده…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">شماره تراکنش</label>
                    <input type="text" wire:model.live.debounce.400ms="transId" class="ap-input !py-2 fa-digits" placeholder="transId…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نام کاربری</label>
                    <input type="text" wire:model.live.debounce.400ms="username" class="ap-input !py-2" placeholder="username…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">شماره سفارش</label>
                    <input type="text" wire:model.live.debounce.400ms="orderId" class="ap-input !py-2 fa-digits" placeholder="شناسه سفارش…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">آیدی تراکنش</label>
                    <input type="text" wire:model.live.debounce.400ms="transactionId" class="ap-input !py-2 fa-digits" placeholder="شناسه تراکنش…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">شماره کارت</label>
                    <input type="text" wire:model.live.debounce.400ms="cardNumber" class="ap-input !py-2 fa-digits" placeholder="۵۸۹۴…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت</label>
                    <select wire:model.live="status" class="ap-input !py-2">
                        <option value="">همه</option>
                        <option value="1">موفق</option>
                        <option value="0">ناموفق</option>
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

    {{-- نوار عملیات گروهی --}}
    <div x-show="$wire.selected.length > 0" x-cloak
         class="sticky top-20 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-200 bg-brand-50/95 px-4 py-2.5 shadow-card backdrop-blur dark:border-brand-500/25 dark:bg-brand-950/80">
        <span class="text-[13px] font-bold text-brand-800 dark:text-brand-300">
            <span class="fa-digits" x-text="ApFormat.faDigits($wire.selected.length)"></span> تراکنش انتخاب شده
        </span>
        @can('payments.transactions.delete')
            <button type="button" wire:click="$set('confirmingBulkDelete', true)"
                    class="ap-btn !px-3 !py-1.5 text-[13px] bg-red-600 text-white hover:bg-red-700">
                <x-admin-panel::icon name="trash-2" class="size-4" />
                حذف همه
            </button>
        @endcan
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-10 px-3 py-3">
                            <input type="checkbox" wire:model.live="selectAll"
                                   class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                   aria-label="انتخاب همه">
                        </th>
                        <th class="w-16 px-2 py-3 text-start font-bold">ID</th>
                        <th class="px-3 py-3 text-start font-bold">نام پرداخت‌کننده</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="px-3 py-3 text-start font-bold">مبلغ (تومان)</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="px-3 py-3 text-start font-bold">نوع</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->transactions as $transaction)
                        <tr wire:key="transaction-{{ $transaction->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $transaction->id }}"
                                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                       aria-label="انتخاب تراکنش {{ $transaction->id }}">
                            </td>
                            <td class="px-2 py-3 fa-digits font-bold">{{ number_format($transaction->id) }}</td>
                            <td class="max-w-40 px-3 py-3">
                                @if ($transaction->user)
                                    <a href="{{ route('np.users.show', $transaction->user) }}" wire:navigate
                                       class="block truncate font-semibold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400">
                                        {{ $transaction->user->fullname ?? trim($transaction->user->first_name . ' ' . $transaction->user->last_name) ?: '—' }}
                                    </a>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">حذف شده</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($transaction->created_at) }}</td>
                            <td class="px-3 py-3 fa-digits font-semibold">{{ number_format($transaction->amount ?? 0) }}</td>
                            <td class="px-3 py-3 text-center">
                                @if ($transaction->status)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">موفق</span>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">ناموفق</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">{{ $this->typeLabel($transaction) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <button type="button" wire:click="openDetail({{ $transaction->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="مشاهده جزئیات" aria-label="مشاهده">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </button>
                                    @can('payments.transactions.delete')
                                        <button type="button" wire:click="confirmDelete({{ $transaction->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف تراکنش" aria-label="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="credit-card" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">تراکنشی با این فیلترها پیدا نشد</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->transactions->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->transactions->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->transactions->total()) }}</span> تراکنش
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->transactions->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->transactions->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->transactions->hasMorePages())
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

    {{-- مودال جزئیات تراکنش — پورت back/transactions/show.blade.php --}}
    <div x-data="{ open: @entangle('showDetail').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-base font-bold">
                    <x-admin-panel::icon name="credit-card" class="size-5 text-brand-600 dark:text-brand-400" />
                    جزئیات تراکنش
                </h3>
                <button type="button" x-on:click="open=false"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            @if ($this->detailTransaction)
                @php $detail = $this->detailTransaction; @endphp
                <div class="text-[13px]">
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">آیدی</span>
                        <span class="fa-digits font-semibold">{{ number_format($detail->id) }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">نوع تراکنش</span>
                        <span class="text-end font-semibold">{{ $this->typeLabel($detail) }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">کاربر پرداخت کننده</span>
                        <span class="text-end font-semibold">
                            @if ($detail->user)
                                {{ $detail->user->fullname }}
                                <a href="{{ route('np.users.show', $detail->user) }}" wire:navigate
                                   class="ms-1 inline-flex items-center align-middle text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400" title="نمایش کاربر">
                                    <x-admin-panel::icon name="external-link" class="size-3.5" />
                                </a>
                            @else
                                <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">حذف شده</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">تاریخ تراکنش</span>
                        <span class="fa-digits font-semibold">{{ $this->jdate($detail->created_at) }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">مبلغ</span>
                        <span class="fa-digits font-semibold">{{ number_format($detail->amount ?? 0) }} تومان</span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">وضعیت</span>
                        <span>
                            @if ($detail->status)
                                <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">موفق</span>
                            @else
                                <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">ناموفق</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">شماره تراکنش</span>
                        <span class="fa-digits font-semibold">{{ $detail->transId ?? '—' }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">شماره پیگیری</span>
                        <span class="fa-digits font-semibold">{{ $detail->traceNumber ?: '—' }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">سفارش مرتبط</span>
                        <span class="text-end font-semibold">
                            @if ($detail->transactionable_type === \App\Models\Order::class && $detail->transactionable)
                                <a href="{{ route('np.orders.show', $detail->transactionable) }}" wire:navigate
                                   class="inline-flex items-center gap-1 text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400">
                                    سفارش <span class="fa-digits">{{ number_format($detail->transactionable->id) }}</span>
                                    <x-admin-panel::icon name="external-link" class="size-3.5" />
                                </a>
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-2.5">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">توضیحات</span>
                        <span class="text-end font-semibold">{{ $detail->message ?: '—' }}</span>
                    </div>
                </div>
            @else
                <div class="py-10 text-center text-sm text-ink-3 dark:text-night-ink-2">تراکنشی یافت نشد</div>
            @endif

            <div class="mt-5">
                <button type="button" x-on:click="open=false" class="ap-btn-ghost w-full">بستن</button>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف تکی --}}
    <div x-data="{ open: @entangle('confirmingDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="trash-2" class="size-6" />
            </div>
            <h3 class="text-base font-bold">حذف تراکنش</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف تراکنش دیگر قادر به بازیابی آن نخواهید بود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف گروهی --}}
    <div x-data="{ open: @entangle('confirmingBulkDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="trash-2" class="size-6" />
            </div>
            <h3 class="text-base font-bold">حذف گروهی تراکنش‌ها</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                <span class="fa-digits font-bold" x-text="ApFormat.faDigits($wire.selected.length)"></span> تراکنش انتخاب شده است. با حذف تراکنش‌ها دیگر قادر به بازیابی آنها نخواهید بود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="bulkDelete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="fullname, username, transId, cardNumber, transactionId, status, orderId, previousPage, nextPage, gotoPage, delete, bulkDelete"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
