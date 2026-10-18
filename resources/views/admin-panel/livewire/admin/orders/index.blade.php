{{-- لیست سفارشات — پنل جدید (فاز ۲) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                همه: <span class="fa-digits">{{ number_format(\App\Models\Order::count()) }}</span>
            </span>
            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                پرداخت شده: <span class="fa-digits">{{ number_format(\App\Models\Order::where('status', 'paid')->count()) }}</span>
            </span>
            <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                پرداخت نشده: <span class="fa-digits">{{ number_format(\App\Models\Order::where('status', 'unpaid')->count()) }}</span>
            </span>
        </div>
        <div class="flex items-center gap-2">
            @can('orders.create')
                <a href="{{ route('np.orders.create') }}" wire:navigate class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن سفارش
                </a>
            @endcan
            <a href="{{ route('np.orders.not-completed') }}" wire:navigate class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="package-search" class="size-4" />
                در انتظار ارسال
            </a>
            @can('orders.export')
                <button type="button" wire:click="$set('exportModal', true)" class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="download" class="size-4" />
                    خروجی اکسل
                </button>
            @endcan
        </div>
    </div>

    {{-- تب‌های وضعیت --}}
    <div class="flex gap-1.5 overflow-x-auto">
        @foreach ([
            '' => 'همه',
            'paid' => 'پرداخت شده',
            'unpaid' => 'پرداخت نشده',
            'canceled' => 'لغو شده',
        ] as $value => $label)
            <button wire:click="$set('status', '{{ $value }}')"
                    class="whitespace-nowrap rounded-xl px-3.5 py-2 text-[13px] font-semibold transition-all
                           {{ $status === $value
                                ? 'bg-brand-600 text-white shadow-[0_6px_16px_-6px_rgb(5_150_105/.45)]'
                                : 'bg-surface text-ink-2 border border-line hover:border-brand-300 dark:bg-night-2 dark:text-night-ink-2 dark:border-night-line' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- کارت فیلترها --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر پیشرفته
                @if ($fullname || $username || $orderId || $shippingStatus || $paymentType || $productName || $productId || $fromDate || $toDate)
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
                    <label class="mb-1.5 block text-xs font-semibold">شماره سفارش</label>
                    <input type="text" wire:model.live.debounce.400ms="orderId" class="ap-input !py-2 fa-digits" placeholder="مثال: ۱۰۲۴">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نام و نام خانوادگی</label>
                    <input type="text" wire:model.live.debounce.400ms="fullname" class="ap-input !py-2" placeholder="جستجو در نام مشتری…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نام کاربری</label>
                    <input type="text" wire:model.live.debounce.400ms="username" class="ap-input !py-2">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت ارسال</label>
                    <select wire:model.live="shippingStatus" class="ap-input !py-2">
                        <option value="">همه</option>
                        @foreach (['w-pending' => 'در انتظار بررسی', 'pending' => 'در حال بررسی', 'waiting' => 'منتظر ارسال', 'sent' => 'ارسال شد', 'canceled' => 'ارسال لغو شد'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع پرداخت</label>
                    <select wire:model.live="paymentType" class="ap-input !py-2">
                        <option value="">همه</option>
                        <option value="normal">عادی</option>
                        <option value="installment">اقساطی</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نام محصول</label>
                    <input type="text" wire:model.live.debounce.400ms="productName" class="ap-input !py-2">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">آیدی محصول</label>
                    <input type="text" wire:model.live.debounce.400ms="productId" class="ap-input !py-2 fa-digits">
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">از تاریخ</label>
                        <input type="text" wire:model.live="fromDate" data-jdp data-jdp-only-date="true" class="ap-input !py-2 fa-digits text-center" placeholder="۱۴۰۴/۰۱/۰۱">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">تا تاریخ</label>
                        <input type="text" wire:model.live="toDate" data-jdp data-jdp-only-date="true" class="ap-input !py-2 fa-digits text-center" placeholder="۱۴۰۴/۰۱/۳۱">
                    </div>
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
            <span class="fa-digits" x-text="ApFormat.faDigits($wire.selected.length)"></span> سفارش انتخاب شده
        </span>
        <div class="flex flex-wrap items-center gap-2">
            @can('orders.update')
                <button type="button" wire:click="openBulkStatus"
                        class="ap-btn !px-3 !py-1.5 text-[13px] bg-sky-600 text-white hover:bg-sky-700">
                    <x-admin-panel::icon name="truck" class="size-4" />
                    تغییر وضعیت ارسال
                </button>
            @endcan
            <a href="{{ route('np.orders.printAll', ['ids' => $selected]) }}" target="_blank" rel="noopener"
               class="ap-btn !px-3 !py-1.5 text-[13px] bg-surface border border-line text-ink-2 hover:text-ink dark:bg-night-3 dark:text-night-ink-2 dark:border-night-line">
                <x-admin-panel::icon name="printer" class="size-4" />
                پرینت فاکتورها
            </a>
            <a href="{{ route('np.orders.printAllShippingForms', ['ids' => $selected]) }}" target="_blank" rel="noopener"
               class="ap-btn !px-3 !py-1.5 text-[13px] bg-surface border border-line text-ink-2 hover:text-ink dark:bg-night-3 dark:text-night-ink-2 dark:border-night-line">
                <x-admin-panel::icon name="file-text" class="size-4" />
                پرینت برچسب‌ها
            </a>
            @can('orders.delete')
                <button type="button" wire:click="$set('confirmingBulkDelete', true)"
                        class="ap-btn !px-3 !py-1.5 text-[13px] bg-red-600 text-white hover:bg-red-700">
                    <x-admin-panel::icon name="trash-2" class="size-4" />
                    حذف همه
                </button>
            @endcan
        </div>
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
                        <th class="w-16 px-2 py-3 text-start font-bold">شماره</th>
                        <th class="px-3 py-3 text-start font-bold">مشتری</th>
                        <th class="px-3 py-3 text-start font-bold">
                            <button wire:click="sortBy('created_at')" class="inline-flex items-center gap-1 font-bold hover:text-brand-600">
                                تاریخ ثبت
                                @if ($sortField === '' || $sortField === 'created_at')
                                    <x-admin-panel::icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3.5" />
                                @endif
                            </button>
                        </th>
                        <th class="px-3 py-3 text-start font-bold">مرسوله‌ها</th>
                        <th class="px-3 py-3 text-start font-bold">مبلغ پرداخت</th>
                        <th class="px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->orders as $order)
                        <tr wire:key="order-{{ $order->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $order->id }}"
                                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                       aria-label="انتخاب سفارش {{ $order->id }}">
                            </td>
                            <td class="px-2 py-3 fa-digits font-bold">{{ number_format($order->id) }}</td>
                            <td class="max-w-40 px-3 py-3">
                                <p class="truncate font-semibold">{{ $order->name ?? ($order->user->first_name . ' ' . $order->user->last_name) }}</p>
                                <p class="fa-digits mt-0.5 truncate text-[11px] text-ink-3 dark:text-night-ink-2">{{ $order->mobile ?? '' }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <p class="fa-digits whitespace-nowrap">{{ $this->jdate($order->created_at, 'Y/m/d') }}</p>
                                <p class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">{{ $this->jdate($order->created_at, 'H:i') }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex max-w-72 flex-wrap items-center gap-1.5">
                                    @foreach ($order->items as $item)
                                        <a href="{{ route('np.orders.show-item', $item->id) }}" wire:navigate
                                           class="inline-flex items-center gap-1.5 rounded-lg border px-2 py-1 text-[11px] font-semibold transition-colors hover:border-brand-400
                                                  {{ in_array($item->shipping_status, ['sent', 'post-sent', 'delivered'])
                                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/25 dark:bg-emerald-500/10 dark:text-emerald-400'
                                                        : ($item->shipping_status === 'canceled'
                                                            ? 'border-red-200 bg-red-50 text-red-600 dark:border-red-500/25 dark:bg-red-500/10 dark:text-red-400'
                                                            : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-400') }}"
                                           title="{{ $this->shippingStatusLabel($item->shipping_status) }}">
                                            <span class="fa-digits">#{{ $item->id }}</span>
                                            <span class="max-w-24 truncate">{{ $item->seller?->seller_info?->business_name ?? 'فروشگاه اصلی' }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <span class="fa-digits whitespace-nowrap font-extrabold">{{ number_format($order->price) }}</span>
                                <span class="text-[10px] text-ink-3">تومان</span>
                            </td>
                            <td class="px-3 py-3">
                                <span class="ap-badge {{ $order->status === 'paid'
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400'
                                    : ($order->status === 'unpaid'
                                        ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400'
                                        : 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400') }}">
                                    {{ $this->statusLabel($order->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <a href="{{ route('np.orders.show', $order->id) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="مشاهده سفارش" aria-label="مشاهده سفارش">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </a>
                                    <a href="{{ route('np.orders.print', ['order' => $order->id]) }}" target="_blank" rel="noopener"
                                       class="hidden rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 hover:text-ink sm:block dark:hover:bg-night-3"
                                       title="چاپ فاکتور" aria-label="چاپ">
                                        <x-admin-panel::icon name="printer" class="size-4" />
                                    </a>
                                    <a href="{{ route('np.orders.shipping-form', ['order' => $order->id]) }}" target="_blank" rel="noopener"
                                       class="hidden rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 hover:text-ink sm:block dark:hover:bg-night-3"
                                       title="برچسب ارسال" aria-label="برچسب ارسال">
                                        <x-admin-panel::icon name="file-text" class="size-4" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="receipt" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">سفارشی با این فیلترها پیدا نشد</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->orders->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->orders->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->orders->total()) }}</span> سفارش
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->orders->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->orders->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->orders->hasMorePages())
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

    {{-- مودال تغییر وضعیت ارسال گروهی --}}
    <div x-data="{ open: @entangle('bulkStatusModal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">تغییر وضعیت ارسال گروهی</h3>
            <p class="mt-1 text-[13px] text-ink-2 dark:text-night-ink-2">
                وضعیت ارسال <span class="fa-digits font-bold" x-text="ApFormat.faDigits($wire.selected.length)"></span> سفارش انتخاب‌شده تغییر می‌کند.
            </p>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت ارسال جدید</label>
                    <select wire:model="bulkShippingStatus" class="ap-input">
                        <option value="">انتخاب وضعیت…</option>
                        <option value="w-pending">در انتظار بررسی</option>
                        <option value="pending">در حال بررسی</option>
                        <option value="waiting">منتظر ارسال</option>
                        <option value="sent">ارسال شد</option>
                        <option value="canceled">ارسال لغو شد</option>
                    </select>
                    @error('bulkShippingStatus') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex cursor-pointer items-center gap-2.5 text-[13px]" x-show="$wire.bulkShippingStatus === 'canceled'">
                    <input type="checkbox" wire:model="bulkBackMoney" class="size-4 rounded-md border-line text-brand-600 dark:border-night-line">
                    بازگشت مبلغ کالا به کیف پول مشتری
                </label>
            </div>

            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="applyBulkStatus" wire:loading.attr="disabled" class="ap-btn-primary flex-1">
                    ثبت تغییر وضعیت
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
            <h3 class="text-base font-bold">حذف گروهی سفارشات</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                <span class="fa-digits font-bold" x-text="ApFormat.faDigits($wire.selected.length)"></span> سفارش انتخاب شده است. اقلام و تراکنش‌های آن‌ها نیز حذف می‌شود و غیرقابل بازگشت است.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="bulkDelete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله، همه حذف شوند
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- مودال خروجی اکسل — پورت export قدیمی --}}
    <div x-data="{ open: @entangle('exportModal').live }"
         x-on:open-url.window="window.open($event.detail.url, '_blank')"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-lg rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">خروجی اکسل سفارشات</h3>
            <p class="mt-1 text-[13px] text-ink-2 dark:text-night-ink-2">
                خروجی با اعمال <span class="font-bold">فیلترهای فعال فعلی</span> دریافت می‌شود. فیلدهای مورد نظر را انتخاب کنید:
            </p>

            <div class="mt-4 grid max-h-64 grid-cols-2 gap-2.5 overflow-y-auto rounded-xl border border-line p-3.5 sm:grid-cols-3 dark:border-night-line">
                @foreach ([
                    'row' => 'ردیف', 'id' => 'آیدی', 'user_id' => 'آیدی کاربر', 'name' => 'نام و نام خانوادگی',
                    'mobile' => 'شماره همراه', 'province' => 'استان', 'city' => 'شهر', 'postal_code' => 'کد پستی',
                    'address' => 'آدرس کامل', 'carrier' => 'شیوه تحویل', 'created_at' => 'تاریخ ثبت',
                    'shipping_cost' => 'هزینه ارسال', 'total_discount' => 'تخفیف', 'price' => 'جمع قیمت',
                    'description' => 'توضیحات سفارش', 'products' => 'لیست محصولات (شیت جداگانه)',
                ] as $column => $label)
                    <label class="flex cursor-pointer items-center gap-2 text-[12px] font-medium">
                        <input type="checkbox"
                               wire:click="toggleExportColumn('{{ $column }}')"
                               :checked="{{ in_array($column, $exportColumns) ? 'true' : 'false' }}"
                               class="size-4 rounded-md border-line text-brand-600 dark:border-night-line">
                        {{ $label }}
                    </label>
                @endforeach
            </div>

            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                        class="ap-btn-primary flex-1">
                    <x-admin-panel::icon name="download" class="size-4" />
                    دریافت فایل اکسل
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="fullname, username, orderId, status, shippingStatus, paymentType, productName, productId, fromDate, toDate, previousPage, nextPage, gotoPage, sortBy, applyBulkStatus, bulkDelete"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
