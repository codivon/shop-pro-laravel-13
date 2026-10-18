{{-- جزئیات تراکنش کیف پول — پورت back/wallets/history.blade.php (+ بخش‌های requestDeposit/history: نام/شماره کارت-شبا) --}}
<div class="mx-auto max-w-3xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="history" class="size-5" />
            </span>
            <div>
                <h1 class="text-base font-bold">جزئیات تراکنش کیف پول</h1>
                <p class="mt-0.5 text-[13px] text-ink-3 dark:text-night-ink-2">
                    شناسه رکورد: <span class="fa-digits font-bold text-ink-2 dark:text-night-ink-2">{{ number_format($history->id) }}</span>
                </p>
            </div>
        </div>
        <a href="{{ route('np.wallet-histories.index') }}" wire:navigate class="ap-btn-outline !py-2 text-[13px]">
            <x-admin-panel::icon name="arrow-left" class="size-4" />
            بازگشت به تاریخچه
        </a>
    </div>

    {{-- مالک کیف پول (پورت بخش «نام» از requestDeposit/history) --}}
    <div class="ap-card flex flex-wrap items-center justify-between gap-3 p-4">
        <div class="flex items-center gap-2 text-[13px]">
            <span class="text-xs font-bold text-ink-3 dark:text-night-ink-2">مالک کیف پول:</span>
            @if ($history->source != 'seller')
                @if ($history->wallet?->user)
                    <a href="{{ route('np.users.show', $history->wallet->user) }}" wire:navigate
                       class="inline-flex items-center gap-1 font-bold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400">
                        {{ $history->wallet->user->fullname }}
                        <x-admin-panel::icon name="external-link" class="size-3.5" />
                    </a>
                    <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">کاربر سایت</span>
                @else
                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">حذف شده</span>
                @endif
            @else
                @if ($history->wallet?->seller)
                    <a href="{{ route('np.sellers.show', $history->wallet->seller) }}" wire:navigate
                       class="inline-flex items-center gap-1 font-bold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400">
                        {{ $history->wallet->sellerInfo?->fullname ?? $history->wallet->seller->fullname }}
                        <x-admin-panel::icon name="external-link" class="size-3.5" />
                    </a>
                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">فروشنده</span>
                @else
                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">حذف شده</span>
                @endif
            @endif
        </div>
        <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
            شناسه کیف پول: <span class="fa-digits">{{ number_format($history->wallet_id) }}</span>
        </span>
    </div>

    {{-- جزئیات تراکنش (پورت جدول قدیمی) --}}
    <div class="ap-card p-5 sm:p-6">
        <div class="text-[13px]">
            <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">آیدی</span>
                <span class="fa-digits font-semibold">{{ number_format($history->id) }}</span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">مبلغ</span>
                <span class="fa-digits font-semibold">{{ number_format($history->amount) }} تومان</span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">نوع تراکنش</span>
                <span>
                    @if ($history->withdraw)
                        <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">
                            <x-admin-panel::icon name="arrow-left" class="size-3.5" />
                            برداشت وجه
                        </span>
                    @elseif ($history->type == 'deposit')
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
                </span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">تاریخ تراکنش</span>
                <span class="fa-digits font-semibold">{{ $this->jdate($history->created_at) }}</span>
            </div>

            {{-- وضعیت + سلکت status_pay (پورت دقیق قدیمی — وقتی unpay-refund است سلکت مخفی می‌شود) --}}
            <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">وضعیت</span>
                <div class="text-end">
                    @if ($history->status == 'success')
                        <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">موفق</span>

                        @if ($history->withdraw)
                            @if ($history->status_pay != 'unpay-refund')
                                <div class="mt-2.5 w-56">
                                    <select wire:model="statusPay" wire:change="updateStatusPay" class="ap-input !py-2">
                                        <option value="waiting" {{ $history->status_pay == 'waiting' ? 'selected' : '' }}>در انتظار پرداخت</option>
                                        <option value="pay" {{ $history->status_pay == 'pay' ? 'selected' : '' }}>پرداخت شده</option>
                                        <option value="unpay" {{ $history->status_pay == 'unpay' ? 'selected' : '' }}>پرداخت نشده</option>
                                        <option value="unpay-refund" {{ $history->status_pay == 'unpay-refund' ? 'selected' : '' }}>پرداخت نشده و بازگشت وجه</option>
                                    </select>
                                </div>
                            @else
                                <span class="mt-2.5 block">
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">پرداخت نشده و مبلغ عودت داده شده</span>
                                </span>
                            @endif
                        @endif
                    @else
                        <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">ناموفق</span>
                    @endif
                </div>
            </div>

            {{-- شماره پیگیری — فقط برای سطرهای برداشت با وضعیت pay (پورت ردیف trackingIdTr قدیمی) --}}
            @if ($history->withdraw && $history->status == 'success' && $history->status_pay == 'pay')
                <div class="py-2.5">
                    <label class="mb-1.5 block text-xs font-bold text-ink-3 dark:text-night-ink-2">شماره پیگیری (تراکنش)</label>
                    <div class="flex items-center gap-2">
                        <input type="text" wire:model="trackingId"
                               class="ap-input fa-digits flex-1 @error('trackingId') !border-red-400 @enderror"
                               placeholder="شماره پیگیری…">
                        <button type="button" wire:click="saveTrackingId" wire:loading.attr="disabled" wire:target="saveTrackingId"
                                class="ap-btn-outline shrink-0 !py-2">
                            ثبت
                        </button>
                    </div>
                    @error('trackingId')
                        <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- توضیحات — قدیمی {!! !!} بود؛ به‌دلیل امنیت escape شده (انحراف مستندشده) --}}
            <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">توضیحات</span>
                <span class="max-w-md whitespace-pre-line text-end font-semibold">{{ $history->description ?: '—' }}</span>
            </div>

            {{-- شماره کارت کاربر / شماره شبا فروشنده (پورت requestDeposit/history) --}}
            @if ($history->wallet?->user)
                <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                    <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">شماره کارت</span>
                    <span class="fa-digits font-semibold">{{ $history->wallet->user->card_number ?: '—' }}</span>
                </div>
            @elseif ($history->wallet?->seller)
                <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                    <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">شماره شبا</span>
                    <span class="fa-digits font-semibold">{{ $history->wallet->sellerInfo?->shaba_number ?? '—' }}</span>
                </div>
            @endif

            {{-- اطلاعات تراکنش بانکی مرتبط --}}
            @if ($history->transaction)
                <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                    <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">شماره تراکنش</span>
                    <span class="fa-digits font-semibold">{{ $history->transaction->transId ?: '—' }}</span>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                    <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">شماره پیگیری</span>
                    <span class="fa-digits font-semibold">{{ $history->transaction->traceNumber ?: '—' }}</span>
                </div>
            @endif

            {{-- سفارش مرتبط --}}
            @if ($history->order)
                <div class="flex items-start justify-between gap-4 py-2.5">
                    <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">شماره سفارش</span>
                    <span class="font-semibold">
                        <span class="fa-digits">{{ number_format($history->order->id) }}</span>
                        <a href="{{ route('np.orders.show', ['order' => $history->order->id]) }}" wire:navigate
                           class="ms-1 inline-flex items-center align-middle text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400"
                           title="نمایش سفارش">
                            <x-admin-panel::icon name="external-link" class="size-3.5" />
                        </a>
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="updateStatusPay, saveTrackingId"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
