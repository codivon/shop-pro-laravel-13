{{-- جزئیات مرسوله — پنل جدید (فاز ۲) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- هدر --}}
    <div class="ap-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('np.orders.show', $orderItem->order->id) }}" wire:navigate
                   class="rounded-xl p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3"
                   aria-label="بازگشت به سفارش">
                    <x-admin-panel::icon name="arrow-right" class="size-5" />
                </a>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-extrabold">جزئیات مرسوله <span class="fa-digits">#{{ number_format($orderItem->id) }}</span></h2>
                        <span class="ap-badge {{ in_array($orderItem->shipping_status, ['sent', 'post-sent', 'delivered'])
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400'
                            : ($orderItem->shipping_status === 'canceled'
                                ? 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400'
                                : 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400') }}">
                            {{ $this->shippingStatusLabel($orderItem->shipping_status) }}
                        </span>
                        @if ($orderItem->refunded)
                            <span class="ap-badge bg-rose-50 text-rose-700 dark:bg-rose-500/12 dark:text-rose-400">وجه برگشت داده شد</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-ink-3 dark:text-night-ink-2">
                        سفارش <span class="fa-digits">#{{ number_format($orderItem->order->id) }}</span> — {{ $this->sellerName }}
                    </p>
                </div>
            </div>
            <a href="{{ route('np.orders.print', ['order' => $orderItem->order->id, 'seller_id' => $orderItem->seller_id]) }}"
               target="_blank" rel="noopener" class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="printer" class="size-4" />
                چاپ
            </a>
        </div>
    </div>

    {{-- هشدارها --}}
    @if ($this->orderCanceled)
        <div class="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-[13px] text-rose-700 dark:border-rose-500/25 dark:bg-rose-950/40 dark:text-rose-300">
            <x-admin-panel::icon name="circle-alert" class="mt-0.5 size-4.5 shrink-0" />
            سفارش قبلاً لغو شده و وجه آن به کیف پول مشتری برگشت داده شده است.
        </div>
    @elseif (! $orderItem->order->isPaid())
        <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-[13px] text-amber-700 dark:border-amber-500/25 dark:bg-amber-950/40 dark:text-amber-300">
            <x-admin-panel::icon name="circle-alert" class="mt-0.5 size-4.5 shrink-0" />
            سفارش اصلی هنوز پرداخت نشده است؛ امکان تغییر وضعیت ارسال وجود ندارد.
        </div>
    @endif

    {{-- تغییر وضعیت مرسوله --}}
    <section class="ap-card p-5">
        <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
            <x-admin-panel::icon name="truck" class="size-4.5 text-brand-600 dark:text-brand-400" />
            تغییر وضعیت مرسوله
        </h3>

        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-56 flex-1">
                <label for="shipping-status-change" class="mb-1.5 block text-xs font-semibold">وضعیت ارسال</label>
                <select id="shipping-status-change" wire:model="shippingStatus" {{ $this->disableStatus ? 'disabled' : '' }} class="ap-input">
                    @foreach ([
                        'w-pending' => 'در انتظار بررسی',
                        'pending' => 'در حال پردازش',
                        'waiting' => 'منتظر ارسال',
                        'sent' => 'ارسال شد',
                        'post-sent' => 'تحویل به پست',
                        'delivered' => 'تحویل داده شد',
                        'canceled' => 'لغو شد',
                    ] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" wire:click="updateStatus" wire:loading.attr="disabled"
                    {{ $this->disableStatus ? 'disabled' : '' }}
                    class="ap-btn-primary">
                <span wire:loading.remove wire:target="updateStatus">ذخیره وضعیت</span>
                <span wire:loading wire:target="updateStatus" class="flex items-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                    در حال ذخیره…
                </span>
            </button>
        </div>

        {{-- تایم‌لاین --}}
        @if ($orderItem->shipping_status !== 'canceled')
            <div class="mt-5 flex items-center">
                @foreach ($this->shippingSteps() as $key => $label)
                    @php $stepIndex = array_search($key, array_keys($this->shippingSteps())); $currentStep = array_search($orderItem->shipping_status, array_keys($this->shippingSteps())); @endphp
                    <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                        <div class="flex flex-col items-center gap-1.5">
                            <span class="flex size-6 items-center justify-center rounded-full text-[10px] font-bold
                                {{ $stepIndex <= $currentStep ? 'bg-brand-600 text-white' : 'bg-surface-3 text-ink-3 dark:bg-night-3 dark:text-night-ink-2' }}">
                                {{ $stepIndex < $currentStep ? '✓' : '' }}
                            </span>
                            <span class="hidden whitespace-nowrap text-[9px] font-semibold sm:block {{ $stepIndex <= $currentStep ? 'text-brand-700 dark:text-brand-400' : 'text-ink-3 dark:text-night-ink-2' }}">{{ $label }}</span>
                        </div>
                        @if (! $loop->last)
                            <div class="mx-1 h-0.5 flex-1 rounded {{ $stepIndex < $currentStep ? 'bg-brand-500' : 'bg-line dark:bg-night-line' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- کارت‌های وضعیت --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        {{-- فاکتور مرسوله --}}
        <section class="ap-card p-5">
            <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="receipt" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فاکتور مرسوله
            </h3>
            <dl class="space-y-2.5 text-[13px]">
                <div class="flex items-center justify-between gap-3">
                    <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">شماره مرسوله</dt>
                    <dd class="fa-digits truncate font-bold">{{ number_format($orderItem->id) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">تاریخ ثبت</dt>
                    <dd class="fa-digits truncate font-semibold">{{ $this->jdate($orderItem->created_at, 'Y/m/d - H:i') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">وضعیت پرداخت</dt>
                    <dd>
                        <span class="ap-badge {{ $orderItem->order->status === 'paid'
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400'
                            : 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' }}">
                            {{ \App\Livewire\Admin\Orders\OrderIndex::statusLabel($orderItem->order->status) }}
                        </span>
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">نحوه پرداخت</dt>
                    <dd class="truncate font-semibold">{{ $this->paymentMethodLabel }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">مبلغ پرداخت شده</dt>
                    <dd class="fa-digits truncate font-extrabold text-brand-700 dark:text-brand-400">
                        {{ number_format($this->sellerItems->sum(fn ($i) => $i->realPrice() * $i->quantity - ($i->realPrice() * $i->quantity * $i->discount / 100)) + $orderItem->shipping_cost) }} تومان
                    </dd>
                </div>
            </dl>
        </section>

        {{-- اطلاعات فروشنده --}}
        <section class="ap-card p-5">
            <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="store" class="size-4.5 text-brand-600 dark:text-brand-400" />
                اطلاعات فروشنده
            </h3>
            <dl class="space-y-2.5 text-[13px]">
                <div class="flex items-center justify-between gap-3">
                    <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">فروشنده</dt>
                    <dd class="truncate font-bold">{{ $this->sellerName }}</dd>
                </div>
                @if ($orderItem->seller)
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">موبایل</dt>
                        <dd class="fa-digits truncate font-semibold">{{ $orderItem->seller->seller_info?->mobile ?? '—' }}</dd>
                    </div>
                @endif
                @if ($orderItem->seller_id)
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">کمیسیون</dt>
                        <dd class="fa-digits truncate font-semibold">
                            {{ \App\Livewire\Admin\Dashboard::fa($order->getAmountSellerCommission()['percent'] ?? 0) }}٪
                        </dd>
                    </div>
                @endif
            </dl>
            @if ($orderItem->seller)
                <a href="{{ route('admin.sellers.show', $orderItem->seller->id) }}" class="ap-menu-link mt-3 !bg-surface-2 dark:!bg-night-3">
                    <x-admin-panel::icon name="external-link" class="size-4 text-brand-600 dark:text-brand-400" />
                    مشاهده فروشنده
                </a>
            @endif
        </section>

        {{-- وضعیت مرسوله + کد رهگیری --}}
        <section class="ap-card p-5">
            <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="hash" class="size-4.5 text-brand-600 dark:text-brand-400" />
                کد رهگیری
            </h3>
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <input type="text" wire:model="trackingCode" id="tracking-code" {{ $this->disableStatus ? 'disabled' : '' }}
                           class="ap-input" placeholder="کد رهگیری پست…" aria-label="کد رهگیری">
                </div>
                <button type="button" wire:click="updateTracking" wire:loading.attr="disabled"
                        {{ $this->disableStatus ? 'disabled' : '' }}
                        class="ap-btn-primary shrink-0">
                    <span wire:loading.remove wire:target="updateTracking">ثبت</span>
                    <span wire:loading wire:target="updateTracking">…</span>
                </button>
            </div>
            @error('trackingCode') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            @if ($orderItem->tracking_code)
                <p class="fa-digits mt-3 rounded-xl bg-surface-2 px-3.5 py-2 text-xs text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                    کد فعلی: <span class="font-bold">{{ $orderItem->tracking_code }}</span>
                </p>
            @endif
        </section>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- محصولات مرسوله --}}
        <section class="ap-card overflow-hidden xl:col-span-2">
            <header class="border-b border-line px-5 py-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="package" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    جزئیات محصولات
                </h3>
            </header>
            <div class="overflow-x-auto">
                <table class="w-full min-w-3xl text-[13px]">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                            <th class="w-12 px-3 py-2.5 text-start font-bold">تصویر</th>
                            <th class="px-3 py-2.5 text-start font-bold">عنوان محصول</th>
                            <th class="w-16 px-2 py-2.5 text-start font-bold">تعداد</th>
                            <th class="px-3 py-2.5 text-start font-bold">قیمت واحد</th>
                            <th class="w-20 px-2 py-2.5 text-start font-bold">تخفیف</th>
                            <th class="px-3 py-2.5 text-start font-bold">قیمت نهایی</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->sellerItems as $sItem)
                            <tr wire:key="sitem-{{ $sItem->id }}" class="border-b border-line/60 last:border-0 dark:border-night-line/60">
                                <td class="px-3 py-2.5">
                                    <img src="{{ $sItem->product?->imageUrl() ?? asset('/empty.svg') }}" alt="{{ $sItem->title }}" width="36" height="36"
                                         class="size-9 rounded-lg border border-line object-cover dark:border-night-line" loading="lazy">
                                </td>
                                <td class="max-w-56 px-3 py-2.5">
                                    <p class="truncate font-semibold">{{ $sItem->title }}</p>
                                    @if ($sItem->attributes)
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @php $attrs = json_decode($sItem->attributes, true); @endphp
                                            @if (is_array($attrs))
                                                @foreach ($attrs as $attr)
                                                    <span class="inline-flex items-center gap-1 rounded-md bg-surface-2 px-1.5 py-0.5 text-[10px] font-semibold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                                        @if (!empty($attr['color']))
                                                            <span class="size-2.5 rounded-full border border-black/10" style="background: {{ $attr['color'] }}"></span>
                                                        @endif
                                                        {{ $attr['name'] ?? '' }}{{ isset($attr['value']) ? ': ' . $attr['value'] : '' }}
                                                    </span>
                                                @endforeach
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-2 py-2.5 fa-digits">{{ number_format($sItem->quantity) }}</td>
                                <td class="px-3 py-2.5 fa-digits">{{ number_format($sItem->real_price) }}</td>
                                <td class="px-2 py-2.5 fa-digits">{{ $sItem->discount ? \App\Livewire\Admin\Dashboard::fa($sItem->discount) . '٪' : '—' }}</td>
                                <td class="px-3 py-2.5 fa-digits font-bold">{{ number_format($sItem->price * $sItem->quantity) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-line bg-surface-2 dark:border-night-line dark:bg-night-3">
                            <td colspan="5" class="px-3 py-2.5 text-start font-semibold">جمع کل محصولات</td>
                            <td class="px-3 py-2.5 fa-digits font-extrabold">{{ number_format($this->sellerItems->sum(fn ($i) => $i->price * $i->quantity)) }}</td>
                        </tr>
                        <tr class="border-t border-line/60 dark:border-night-line/60">
                            <td colspan="5" class="px-3 py-2.5 text-start font-semibold">هزینه ارسال</td>
                            <td class="px-3 py-2.5 fa-digits font-bold">{{ number_format($orderItem->shipping_cost ?? 0) }}</td>
                        </tr>
                        <tr class="border-t border-line/60 dark:border-night-line/60">
                            <td colspan="5" class="px-3 py-2.5 text-start font-bold">مبلغ قابل پرداخت نهایی</td>
                            <td class="px-3 py-2.5 fa-digits text-base font-extrabold text-brand-700 dark:text-brand-400">
                                {{ number_format($this->sellerItems->sum(fn ($i) => $i->price * $i->quantity) + ($orderItem->shipping_cost ?? 0)) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        {{-- گیرنده و آدرس --}}
        <div class="space-y-5">
            <section class="ap-card p-5">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="user" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    اطلاعات گیرنده
                </h3>
                <dl class="space-y-2.5 text-[13px]">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">نام</dt>
                        <dd class="truncate font-semibold">{{ $orderItem->order->name ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">نام کاربری</dt>
                        <dd class="truncate font-semibold">{{ $orderItem->order->user?->username ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">موبایل</dt>
                        <dd class="fa-digits truncate font-semibold">{{ $orderItem->order->mobile ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">ایمیل</dt>
                        <dd class="truncate font-semibold" dir="ltr">{{ $orderItem->order->user?->email ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="ap-card p-5">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="map" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    آدرس ارسال
                </h3>
                <dl class="space-y-2.5 text-[13px]">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">استان / شهر</dt>
                        <dd class="truncate font-semibold">{{ $orderItem->order->province?->name ?? '—' }} / {{ $orderItem->order->city?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">کد پستی</dt>
                        <dd class="fa-digits truncate font-semibold">{{ $orderItem->order->postal_code ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="mb-1 text-ink-2 dark:text-night-ink-2">آدرس کامل</dt>
                        <dd class="leading-6 text-ink dark:text-night-ink">{{ $orderItem->order->address ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">شیوه تحویل</dt>
                        <dd class="truncate font-semibold">{{ $orderItem->carrier?->title ?: 'پیک' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">تاریخ تحویل</dt>
                        <dd class="fa-digits truncate font-semibold">{{ $this->jdate($orderItem->delivery_date, 'Y/m/d') }}</dd>
                    </div>
                </dl>
            </section>

            @if ($orderItem->order->description)
                <section class="ap-card p-5">
                    <h3 class="mb-2.5 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="message-square" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        توضیحات سفارش
                    </h3>
                    <p class="text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">{{ $orderItem->order->description }}</p>
                </section>
            @endif
        </div>
    </div>

    {{-- مودال لغو سفارش --}}
    <div x-data="{ open: @entangle('cancelModal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="circle-alert" class="size-6" />
            </div>
            <h3 class="text-base font-bold">تأیید لغو سفارش</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با لغو این مرسوله، مبلغ <span class="fa-digits font-bold">{{ number_format($this->refundAmount) }}</span> تومان
                به کیف پول مشتری بازگردانده می‌شود و موجودی محصولات به انبار برمی‌گردد.
            </p>

            <div class="mt-4">
                <label for="cancel_reason" class="mb-1.5 block text-xs font-semibold">دلیل لغو سفارش</label>
                <textarea id="cancel_reason" wire:model="cancelReason" rows="3" class="ap-input"
                          placeholder="دلیل لغو را بنویسید (اختیاری)…"></textarea>
                @error('cancelReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="confirmCancel" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    <span wire:loading.remove wire:target="confirmCancel">تأیید و لغو سفارش</span>
                    <span wire:loading wire:target="confirmCancel">در حال ثبت…</span>
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="updateStatus, updateTracking, confirmCancel"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال پردازش…</span>
    </div>
</div>
