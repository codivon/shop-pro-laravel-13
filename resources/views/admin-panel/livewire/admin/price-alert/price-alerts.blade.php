{{-- لیست هشدارهای کاهش قیمت — پورت back/price-alerts/index.blade.php — پنل جدید (فاز ۷۸) — فاز ۸۳: تب‌های فیلتر + نوع مشتری + کانال‌ها --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                کل هشدارها: <span class="fa-digits">{{ number_format($this->totalCount) }}</span>
            </span>
        </div>

        {{-- فاز ۸۳ — تب‌های فیلتر وضعیت --}}
        <div class="flex items-center gap-1 rounded-xl border border-line bg-surface-2 p-1 dark:border-night-line dark:bg-night-3/60" role="tablist" aria-label="فیلتر وضعیت">
            <button type="button" wire:click="setFilter('all')"
                    @class([
                        'rounded-lg px-3 py-1.5 text-xs font-bold transition-colors',
                        'bg-brand-600 text-white shadow-sm' => $filter === 'all',
                        'text-ink-3 hover:text-ink-2 dark:text-night-ink-2' => $filter !== 'all',
                    ]) role="tab" aria-selected="{{ $filter === 'all' ? 'true' : 'false' }}">
                همه <span class="fa-digits opacity-75">({{ number_format($this->totalCount) }})</span>
            </button>
            <button type="button" wire:click="setFilter('active')"
                    @class([
                        'rounded-lg px-3 py-1.5 text-xs font-bold transition-colors',
                        'bg-brand-600 text-white shadow-sm' => $filter === 'active',
                        'text-ink-3 hover:text-ink-2 dark:text-night-ink-2' => $filter !== 'active',
                    ]) role="tab" aria-selected="{{ $filter === 'active' ? 'true' : 'false' }}">
                در انتظار افت قیمت <span class="fa-digits opacity-75">({{ number_format($this->activeCount) }})</span>
            </button>
            <button type="button" wire:click="setFilter('notified')"
                    @class([
                        'rounded-lg px-3 py-1.5 text-xs font-bold transition-colors',
                        'bg-brand-600 text-white shadow-sm' => $filter === 'notified',
                        'text-ink-3 hover:text-ink-2 dark:text-night-ink-2' => $filter !== 'notified',
                    ]) role="tab" aria-selected="{{ $filter === 'notified' ? 'true' : 'false' }}">
                اطلاع‌داده‌شده <span class="fa-digits opacity-75">({{ number_format($this->notifiedCount) }})</span>
            </button>
        </div>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-3 py-3 text-start font-bold">ID</th>
                        <th class="px-3 py-3 text-start font-bold">محصول</th>
                        <th class="px-3 py-3 text-start font-bold">مشتری</th>
                        <th class="w-36 px-3 py-3 text-start font-bold">شماره موبایل</th>
                        <th class="w-40 px-3 py-3 text-start font-bold">راه اطلاع‌رسانی</th>
                        <th class="w-36 px-3 py-3 text-start font-bold">قیمت هدف</th>
                        <th class="w-32 px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->priceAlerts as $priceAlert)
                        <tr wire:key="price-alert-{{ $priceAlert->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($priceAlert->id) }}</td>
                            <td class="max-w-44 px-3 py-3">
                                <span class="flex items-center gap-1">
                                    <span class="truncate font-semibold">{{ $priceAlert->product->title ?? "محصول #{$priceAlert->product_id}" }}</span>
                                    @if ($frontLink = $this->productFrontLink($priceAlert))
                                        <a href="{{ $frontLink }}" target="_blank" rel="noopener"
                                           class="shrink-0 text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400" title="مشاهده در فروشگاه">
                                            <x-admin-panel::icon name="external-link" class="size-3.5" />
                                        </a>
                                    @endif
                                </span>
                            </td>
                            <td class="max-w-36 px-3 py-3">
                                {{-- فاز ۸۳ — نوع مشتری (عضو/مهمان) + نام --}}
                                @if ($priceAlert->user_id)
                                    <span class="flex items-center gap-1.5">
                                        <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400" title="کاربر عضو">
                                            <x-admin-panel::icon name="user-round" class="size-3" />
                                            عضو
                                        </span>
                                    </span>
                                    <p class="mt-0.5 truncate text-[11.5px] font-semibold text-ink-2 dark:text-night-ink">{{ $priceAlert->name ?? ($priceAlert->user->fullname ?? '—') }}</p>
                                @else
                                    <span class="ap-badge bg-slate-100 text-slate-600 dark:bg-slate-500/12 dark:text-slate-400" title="مهمان (بدون ثبت‌نام)">
                                        <x-admin-panel::icon name="user-round" class="size-3" />
                                        مهمان
                                    </span>
                                    @if ($priceAlert->name)
                                        <p class="mt-0.5 truncate text-[11.5px] font-semibold text-ink-2 dark:text-night-ink">{{ $priceAlert->name }}</p>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits" dir="ltr">{{ $priceAlert->mobile }}</td>
                            <td class="px-3 py-3">
                                {{-- فاز ۸۳ — کانال‌های انتخابی مشتری --}}
                                <span class="flex flex-wrap gap-1">
                                    @foreach ($this->channelsLabel($priceAlert) as $channelLabel)
                                        <span class="ap-badge {{ $channelLabel === 'پیامک' ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' }}">
                                            {{ $channelLabel }}
                                        </span>
                                    @endforeach
                                </span>
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">
                                {{ $priceAlert->target_price !== null ? number_format($priceAlert->target_price) : '—' }}
                            </td>
                            <td class="px-3 py-3">
                                @if ($priceAlert->active)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">در انتظار افت قیمت</span>
                                @else
                                    <span class="ap-badge bg-slate-100 text-slate-600 dark:bg-slate-500/12 dark:text-slate-400">اعلان شده</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($priceAlert->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <button type="button" wire:click="openDetail({{ $priceAlert->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="مشاهده جزئیات" aria-label="مشاهده">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $priceAlert->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="bell" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">
                                        {{ $filter === 'active' ? 'هشداری در انتظار افت قیمت نیست' : ($filter === 'notified' ? 'هشدار اطلاع‌داده‌شده‌ای نیست' : 'هنوز هشدار قیمتی ثبت نشده است') }}
                                    </p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->priceAlerts->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->priceAlerts->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->priceAlerts->total()) }}</span> هشدار
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->priceAlerts->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->priceAlerts->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->priceAlerts->hasMorePages())
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

    {{-- مودال جزئیات --}}
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
                    <x-admin-panel::icon name="bell" class="size-5 text-brand-600 dark:text-brand-400" />
                    جزئیات هشدار قیمت
                </h3>
                <button type="button" x-on:click="open=false"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            @if ($this->detailAlert)
                @php $alert = $this->detailAlert; @endphp

                <div class="text-[13px]">
                    {{-- محصول --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">محصول</span>
                        <span class="text-end font-semibold">
                            {{ $alert->product->title ?? "محصول #{$alert->product_id}" }}
                            @if ($frontLink = $this->productFrontLink($alert))
                                <a href="{{ $frontLink }}" target="_blank" rel="noopener"
                                   class="ms-1 inline-flex items-center align-middle text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400" title="مشاهده در فروشگاه">
                                    <x-admin-panel::icon name="external-link" class="size-3.5" />
                                </a>
                            @endif
                        </span>
                    </div>

                    {{-- مشتری (فاز ۸۳ — نوع + نام) --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">مشتری</span>
                        <span class="text-end font-semibold">
                            {{ $alert->name ?? '—' }}
                            <span class="ap-badge ms-1 {{ $alert->user_id ? 'bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-500/12 dark:text-slate-400' }}">
                                {{ $alert->user_id ? 'کاربر عضو' : 'مهمان' }}
                            </span>
                        </span>
                    </div>

                    {{-- شماره موبایل --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">شماره موبایل</span>
                        <span class="fa-digits text-end font-semibold" dir="ltr">{{ $alert->mobile }}</span>
                    </div>

                    {{-- راه اطلاع‌رسانی (فاز ۸۳) --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">راه اطلاع‌رسانی</span>
                        <span class="text-end">
                            @if ($alert->notify_sms)
                                <span class="ap-badge bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400">پیامک</span>
                            @endif
                            @if ($alert->notify_profile)
                                <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">اعلان پروفایل</span>
                            @endif
                            @if (! $alert->notify_sms && ! $alert->notify_profile)
                                <span class="ap-badge bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400">پیامک</span>
                                <span class="text-[11px] text-ink-3 dark:text-night-ink-2">(رکورد قبل از فاز ۸۳)</span>
                            @endif
                        </span>
                    </div>

                    {{-- قیمت هدف --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">قیمت هدف</span>
                        <span class="fa-digits text-end font-semibold">{{ $alert->target_price !== null ? number_format($alert->target_price) . ' تومان' : '—' }}</span>
                    </div>

                    {{-- وضعیت --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">وضعیت</span>
                        <span class="text-end font-semibold">{{ $alert->active ? 'در انتظار افت قیمت' : 'اعلان شده' }}</span>
                    </div>

                    {{-- تاریخ ثبت --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">تاریخ ثبت</span>
                        <span class="fa-digits text-end font-semibold">{{ $this->jdate($alert->created_at, 'Y/m/d | H:i') }}</span>
                    </div>

                    {{-- تاریخ اطلاع‌رسانی (فاز ۸۳) --}}
                    @if ($alert->notified_at)
                        <div class="flex items-start justify-between gap-4 py-2.5">
                            <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">تاریخ اطلاع‌رسانی</span>
                            <span class="fa-digits text-end font-semibold">{{ $this->jdate($alert->notified_at, 'Y/m/d | H:i') }}</span>
                        </div>
                    @endif
                </div>
            @else
                <div class="py-10 text-center text-sm text-ink-3 dark:text-night-ink-2">رکوردی یافت نشد</div>
            @endif

            <div class="mt-5">
                <button type="button" x-on:click="open=false" class="ap-btn-ghost w-full">بستن</button>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف --}}
    <div x-data="{ open: @entangle('confirmingDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="trash-2" class="size-6" />
            </div>
            <h3 class="text-base font-bold">حذف هشدار قیمت</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف هشدار قیمت دیگر قادر به بازیابی آن نخواهید بود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="previousPage, nextPage, gotoPage, delete, applyFilters"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
