{{-- لیست مرجوعی‌ها — پنل جدید (فاز ۴) پورت back/returns/index.blade.php --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="flex items-center gap-2.5 text-base font-extrabold">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-500/25">
                    <x-admin-panel::icon name="undo-2" class="size-5.5" />
                </span>
                مدیریت مرجوعی‌ها</h1>
            <p class="mt-1 text-xs text-ink-3 dark:text-night-ink-2">بررسی، تایید و تسویه درخواست‌های بازگشت کالا</p>
        </div>
        <a href="{{ route('np.returns.reasons') }}" wire:navigate
           class="ap-btn-outline !py-2">
            <x-admin-panel::icon name="list" class="size-4" />
            دلایل مرجوعی
        </a>
    </div>

    {{-- آمار وضعیت (پورت کارت‌های آمار قدیمی) --}}
    <div class="stagger grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <div class="ap-card p-4 text-center">
            <p class="fa-digits text-xl font-extrabold">{{ number_format($this->statistics['total']) }}</p>
            <p class="mt-0.5 text-[11px] font-medium text-ink-3 dark:text-night-ink-2">کل</p>
        </div>
        <div class="ap-card border-amber-200/70 bg-amber-50/60 p-4 text-center dark:border-amber-500/20 dark:bg-amber-500/5">
            <p class="fa-digits text-xl font-extrabold text-amber-600 dark:text-amber-400">{{ number_format($this->statistics['pending']) }}</p>
            <p class="mt-0.5 text-[11px] font-medium text-ink-3 dark:text-night-ink-2">در حال بررسی</p>
        </div>
        <div class="ap-card border-blue-200/70 bg-blue-50/60 p-4 text-center dark:border-blue-500/20 dark:bg-blue-500/5">
            <p class="fa-digits text-xl font-extrabold text-blue-600 dark:text-blue-400">{{ number_format($this->statistics['approved']) }}</p>
            <p class="mt-0.5 text-[11px] font-medium text-ink-3 dark:text-night-ink-2">تایید اولیه</p>
        </div>
        <div class="ap-card border-cyan-200/70 bg-cyan-50/60 p-4 text-center dark:border-cyan-500/20 dark:bg-cyan-500/5">
            <p class="fa-digits text-xl font-extrabold text-cyan-600 dark:text-cyan-400">{{ number_format($this->statistics['received']) }}</p>
            <p class="mt-0.5 text-[11px] font-medium text-ink-3 dark:text-night-ink-2">دریافت شد</p>
        </div>
        <div class="ap-card border-emerald-200/70 bg-emerald-50/60 p-4 text-center dark:border-emerald-500/20 dark:bg-emerald-500/5">
            <p class="fa-digits text-xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($this->statistics['completed']) }}</p>
            <p class="mt-0.5 text-[11px] font-medium text-ink-3 dark:text-night-ink-2">تکمیل شده</p>
        </div>
        <div class="ap-card border-red-200/70 bg-red-50/60 p-4 text-center dark:border-red-500/20 dark:bg-red-500/5">
            <p class="fa-digits text-xl font-extrabold text-red-600 dark:text-red-400">{{ number_format($this->statistics['rejected']) }}</p>
            <p class="mt-0.5 text-[11px] font-medium text-ink-3 dark:text-night-ink-2">رد شده</p>
        </div>
    </div>

    {{-- آمار نوع پرداخت --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="ap-card flex items-center justify-between p-4">
            <span class="flex items-center gap-2 text-[13px] font-semibold text-ink-2 dark:text-night-ink-2">
                <x-admin-panel::icon name="banknote" class="size-4.5 text-blue-600 dark:text-blue-400" />
                مرجوعی سفارشات نقدی
            </span>
            <span class="fa-digits text-lg font-extrabold text-blue-600 dark:text-blue-400">{{ number_format($this->statistics['cash_count']) }}</span>
        </div>
        <div class="ap-card flex items-center justify-between p-4">
            <span class="flex items-center gap-2 text-[13px] font-semibold text-ink-2 dark:text-night-ink-2">
                <x-admin-panel::icon name="credit-card" class="size-4.5 text-violet-600 dark:text-violet-400" />
                مرجوعی سفارشات اعتباری
            </span>
            <span class="fa-digits text-lg font-extrabold text-violet-600 dark:text-violet-400">{{ number_format($this->statistics['credit_count']) }}</span>
        </div>
        <div class="ap-card flex items-center justify-between p-4">
            <span class="flex items-center gap-2 text-[13px] font-semibold text-ink-2 dark:text-night-ink-2">
                <x-admin-panel::icon name="hourglass" class="size-4.5 text-amber-600 dark:text-amber-400" />
                مرجوعی سفارشات اقساطی
            </span>
            <span class="fa-digits text-lg font-extrabold text-amber-600 dark:text-amber-400">{{ number_format($this->statistics['installment_count']) }}</span>
        </div>
    </div>

    {{-- کارت فیلترها --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر مرجوعی‌ها
                @if ($status || $paymentType || $search)
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
                    <label class="mb-1.5 block text-xs font-semibold">جستجو</label>
                    <input type="text" wire:model.live.debounce.400ms="search" class="ap-input !py-2"
                           placeholder="شماره سفارش، نام یا موبایل…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت</label>
                    <select wire:model.live="status" class="ap-input !py-2">
                        <option value="">همه وضعیت‌ها</option>
                        @foreach (\App\Models\ReturnRequest::statusLabels() as $statusKey => $statusInfo)
                            <option value="{{ $statusKey }}">{{ $statusInfo['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع پرداخت</label>
                    <select wire:model.live="paymentType" class="ap-input !py-2">
                        <option value="">همه نوع پرداخت</option>
                        @foreach (\App\Models\ReturnRequest::paymentTypeLabels() as $typeKey => $typeInfo)
                            <option value="{{ $typeKey }}">{{ $typeInfo['label'] }}</option>
                        @endforeach
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
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-14 px-3 py-3 text-start font-bold">#</th>
                        <th class="px-3 py-3 text-start font-bold">سفارش</th>
                        <th class="px-3 py-3 text-start font-bold">کاربر</th>
                        <th class="px-3 py-3 text-start font-bold">محصول</th>
                        <th class="px-3 py-3 text-start font-bold">نوع پرداخت</th>
                        <th class="px-3 py-3 text-start font-bold">دلیل</th>
                        <th class="px-3 py-3 text-start font-bold">کیف پول</th>
                        <th class="px-3 py-3 text-start font-bold">اعتبار</th>
                        <th class="px-3 py-3 text-start font-bold">مبلغ کل</th>
                        <th class="px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->returns as $return)
                        <tr wire:key="return-{{ $return->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($return->id) }}</td>
                            <td class="px-3 py-3">
                                <a href="{{ route('np.orders.show', $return->order_id) }}" wire:navigate
                                   class="fa-digits font-bold text-brand-600 transition-colors hover:text-brand-700 dark:text-brand-400">
                                    #{{ number_format($return->order_id) }}
                                </a>
                            </td>
                            <td class="px-3 py-3">
                                <p class="font-semibold">{{ trim(($return->user?->first_name ?? '') . ' ' . ($return->user?->last_name ?? '')) ?: '—' }}</p>
                                <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $return->user?->mobile ?? '' }}</p>
                            </td>
                            <td class="max-w-40 px-3 py-3">
                                <p class="truncate" title="{{ $return->orderItem?->title }}">{{ $this->itemTitle($return) }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <span class="ap-badge {{ \App\Livewire\Admin\Returns\ReturnIndex::paymentBadgeClass($return->payment_type) }}">
                                    <x-admin-panel::icon name="{{ \App\Livewire\Admin\Returns\ReturnIndex::paymentIcon($return->payment_type) }}" class="size-3.5" />
                                    {{ $return->paymentTypeLabel() }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-ink-2 dark:text-night-ink-2">{{ $return->reason?->title ?? '—' }}</td>
                            <td class="fa-digits px-3 py-3 font-semibold text-emerald-600 dark:text-emerald-400">
                                {{ $return->wallet_refund_amount > 0 ? number_format($return->wallet_refund_amount) : '—' }}
                            </td>
                            <td class="fa-digits px-3 py-3 font-semibold text-blue-600 dark:text-blue-400">
                                {{ $return->credit_restore_amount > 0 ? number_format($return->credit_restore_amount) : '—' }}
                            </td>
                            <td class="fa-digits px-3 py-3 font-extrabold">{{ number_format($return->refund_amount) }} ت</td>
                            <td class="px-3 py-3">
                                <span class="ap-badge {{ \App\Livewire\Admin\Returns\ReturnIndex::statusBadgeClass($return->status) }}">
                                    <x-admin-panel::icon name="{{ \App\Livewire\Admin\Returns\ReturnIndex::statusIcon($return->status) }}" class="size-3.5" />
                                    {{ \App\Models\ReturnRequest::statusLabels()[$return->status]['label'] ?? $return->status }}
                                </span>
                            </td>
                            <td class="fa-digits whitespace-nowrap px-3 py-3">{{ \App\Livewire\Admin\Returns\ReturnIndex::jdate($return->created_at) }}</td>
                            <td class="px-3 py-3">
                                <a href="{{ route('np.returns.show', $return) }}" wire:navigate
                                   class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                   title="نمایش مرجوعی" aria-label="نمایش">
                                    <x-admin-panel::icon name="eye" class="size-4" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="undo-2" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هیچ درخواست مرجوعی یافت نشد</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->returns->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->returns->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->returns->total()) }}</span> مرجوعی
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->returns->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->returns->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->returns->hasMorePages())
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
    <div wire:loading wire:target="status, paymentType, search, previousPage, nextPage, gotoPage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
