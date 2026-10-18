{{-- نمای کلی سفارش — پنل جدید (فاز ۲) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- هدر --}}
    <div class="ap-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('np.orders.index') }}" wire:navigate
                   class="rounded-xl p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3"
                   aria-label="بازگشت به لیست سفارشات">
                    <x-admin-panel::icon name="arrow-right" class="size-5" />
                </a>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-extrabold">سفارش <span class="fa-digits">#{{ number_format($order->id) }}</span></h2>
                        <span class="ap-badge {{ $order->status === 'paid'
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400'
                            : ($order->status === 'unpaid'
                                ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400'
                                : 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400') }}">
                            {{ \App\Livewire\Admin\Orders\OrderIndex::statusLabel($order->status) }}
                        </span>
                        @if ($installmentPlan)
                            <span class="ap-badge bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400">💰 اقساطی</span>
                        @endif
                    </div>
                    <p class="fa-digits mt-1 text-xs text-ink-3 dark:text-night-ink-2">
                        {{ $this->jdate($order->created_at, 'Y/m/d - H:i') }} — {{ $order->name ?? ($order->user->first_name . ' ' . $order->user->last_name) }}
                        — <span class="fa-digits">{{ number_format($orderItems->count()) }}</span> مرسوله
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('np.orders.print', ['order' => $order->id, 'seller_id' => $orderItems->first()?->seller_id]) }}"
                   target="_blank" rel="noopener" class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="printer" class="size-4" />
                    چاپ فاکتور
                </a>
                @if ($order->hasPhysicalProduct())
                    <a href="{{ route('np.orders.shipping-form', ['order' => $order->id]) }}" target="_blank" rel="noopener"
                       class="ap-btn-outline !py-2">
                        <x-admin-panel::icon name="file-text" class="size-4" />
                        برچسب ارسال
                    </a>
                @endif
                @can('orders.delete')
                    <button type="button" wire:click="$set('confirmingDelete', true)"
                            class="ap-btn !py-2 bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20">
                        <x-admin-panel::icon name="trash-2" class="size-4" />
                        حذف
                    </button>
                @endcan
            </div>
        </div>
    </div>

    {{-- کارت‌های آمار --}}
    <div class="stagger grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="ap-card p-5">
            <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">مبلغ قابل پرداخت کل</p>
            <p class="fa-digits mt-1.5 text-2xl font-extrabold">{{ number_format($order->price) }} <span class="text-xs font-medium">تومان</span></p>
        </div>
        <div class="ap-card p-5">
            <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">تعداد کل اقلام</p>
            <p class="fa-digits mt-1.5 text-2xl font-extrabold">{{ number_format($orderItems->sum('quantity')) }}</p>
        </div>
        <div class="ap-card p-5">
            <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">جمع تخفیف‌ها</p>
            <p class="fa-digits mt-1.5 text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($order->totalDiscount()) }} <span class="text-xs font-medium">تومان</span></p>
        </div>
        <div class="ap-card p-5">
            <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">هزینه ارسال</p>
            <p class="fa-digits mt-1.5 text-2xl font-extrabold">{{ number_format($order->shipping_cost) }} <span class="text-xs font-medium">تومان</span></p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- مرسوله‌ها --}}
        <div class="space-y-5 xl:col-span-2">
            @foreach ($orderItems as $item)
                @php
                    $steps = \App\Livewire\Admin\Orders\OrderShow::shippingSteps();
                    $currentStep = array_search($item->shipping_status, array_keys($steps));
                    $isCanceled = $item->shipping_status === 'canceled';
                @endphp
                <section class="ap-card overflow-hidden">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4 dark:border-night-line">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="flex size-9 items-center justify-center rounded-lg bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                <x-admin-panel::icon name="package" class="size-4.5" />
                            </span>
                            <div>
                                <p class="text-sm font-bold">مرسوله <span class="fa-digits">#{{ number_format($item->id) }}</span></p>
                                <p class="mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                                    {{ $item->seller?->seller_info?->business_name ?? 'فروشگاه اصلی' }}
                                </p>
                            </div>
                            <span class="ap-badge {{ $isCanceled
                                ? 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400'
                                : 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' }}">
                                {{ \App\Livewire\Admin\Orders\OrderIndex::shippingStatusLabel($item->shipping_status) }}
                            </span>
                            @if ($returnStatus = $this->returnStatusLabel($item->return_status))
                                <span class="ap-badge bg-rose-50 text-rose-700 dark:bg-rose-500/12 dark:text-rose-400">{{ $returnStatus }}</span>
                            @endif
                        </div>
                        <a href="{{ route('np.orders.show-item', $item->id) }}" wire:navigate
                           class="ap-btn-outline !py-1.5 text-xs">
                            مشاهده مرسوله
                            <x-admin-panel::icon name="chevron-left" class="size-3.5" />
                        </a>
                    </header>

                    <div class="p-5">
                        {{-- تایم‌لاین وضعیت --}}
                        @if (! $isCanceled)
                            <div class="mb-5 flex items-center">
                                @foreach ($steps as $key => $label)
                                    @php $stepIndex = array_search($key, array_keys($steps)); @endphp
                                    <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                                        <div class="flex flex-col items-center gap-1.5">
                                            <span class="flex size-6 items-center justify-center rounded-full text-[10px] font-bold transition-all
                                                {{ $stepIndex !== false && $currentStep !== false && $stepIndex <= $currentStep
                                                    ? 'bg-brand-600 text-white'
                                                    : 'bg-surface-3 text-ink-3 dark:bg-night-3 dark:text-night-ink-2' }}">
                                                {{ $stepIndex !== false && $currentStep !== false && $stepIndex < $currentStep ? '✓' : '' }}
                                            </span>
                                            <span class="hidden whitespace-nowrap text-[9px] font-semibold sm:block {{ $stepIndex !== false && $currentStep !== false && $stepIndex <= $currentStep ? 'text-brand-700 dark:text-brand-400' : 'text-ink-3 dark:text-night-ink-2' }}">{{ $label }}</span>
                                        </div>
                                        @if (! $loop->last)
                                            <div class="mx-1 h-0.5 flex-1 rounded {{ $stepIndex !== false && $currentStep !== false && $stepIndex < $currentStep ? 'bg-brand-500' : 'bg-line dark:bg-night-line' }}"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mb-5 flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3 text-[13px] text-red-700 dark:border-red-500/25 dark:bg-red-950/40 dark:text-red-300">
                                <x-admin-panel::icon name="circle-alert" class="size-4.5 shrink-0" />
                                این مرسوله لغو شده است.
                            </div>
                        @endif

                        {{-- محصولات مرسوله --}}
                        <div class="space-y-2.5">
                            @php
                                $sameSellerItems = $orderItems->where('seller_id', $item->seller_id);
                            @endphp
                            @foreach ($sameSellerItems->take(3) as $sItem)
                                <div class="flex items-center gap-3 rounded-xl bg-surface-2 p-2.5 dark:bg-night-3/60">
                                    <img src="{{ $sItem->product?->imageUrl() ?? asset('/empty.svg') }}" alt="{{ $sItem->title }}" width="40" height="40"
                                         class="size-10 rounded-lg border border-line object-cover dark:border-night-line" loading="lazy">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[13px] font-semibold">{{ $sItem->title }}</p>
                                        <p class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">
                                            <span class="fa-digits">{{ number_format($sItem->quantity) }}</span> عدد
                                            @if ($sItem->discount)
                                                — تخفیف <span class="fa-digits">{{ \App\Livewire\Admin\Dashboard::fa($sItem->discount) }}٪</span>
                                            @endif
                                        </p>
                                    </div>
                                    <span class="fa-digits text-[13px] font-bold">{{ number_format($sItem->price * $sItem->quantity) }}</span>
                                </div>
                            @endforeach
                            @if ($sameSellerItems->count() > 3)
                                <p class="fa-digits text-center text-[11px] text-ink-3 dark:text-night-ink-2">و {{ \App\Livewire\Admin\Dashboard::fa($sameSellerItems->count() - 3) }} محصول دیگر…</p>
                            @endif
                        </div>
                    </div>
                </section>
            @endforeach

            {{-- توضیحات سفارش --}}
            @if ($order->description)
                <section class="ap-card p-5">
                    <h3 class="mb-2.5 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="message-square" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        توضیحات سفارش
                    </h3>
                    <p class="text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">{{ $order->description }}</p>
                </section>
            @endif
        </div>

        {{-- ستون کناری --}}
        <div class="space-y-5">
            {{-- صورت‌حساب --}}
            <section class="ap-card p-5">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="receipt" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    صورت‌حساب
                </h3>
                <dl class="space-y-2.5 text-[13px]">
                    <div class="flex items-center justify-between">
                        <dt class="text-ink-2 dark:text-night-ink-2">جمع محصولات</dt>
                        <dd class="fa-digits font-bold">{{ number_format($order->priceWithoutDiscount()) }} تومان</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-ink-2 dark:text-night-ink-2">تخفیف</dt>
                        <dd class="fa-digits font-bold text-emerald-600 dark:text-emerald-400">−{{ number_format($order->totalDiscount()) }} تومان</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-ink-2 dark:text-night-ink-2">هزینه ارسال</dt>
                        <dd class="fa-digits font-bold">{{ number_format($order->shipping_cost) }} تومان</dd>
                    </div>
                    <div class="border-t border-line pt-2.5 dark:border-night-line">
                        <div class="flex items-center justify-between">
                            <dt class="font-bold">قابل پرداخت</dt>
                            <dd class="fa-digits text-base font-extrabold text-brand-700 dark:text-brand-400">{{ number_format($order->price) }} تومان</dd>
                        </div>
                    </div>
                </dl>
            </section>

            {{-- خریدار --}}
            <section class="ap-card p-5">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="user" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    خریدار
                </h3>
                <dl class="space-y-2.5 text-[13px]">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">نام و نام خانوادگی</dt>
                        <dd class="truncate font-semibold">{{ $order->name ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">نام کاربری</dt>
                        <dd class="truncate font-semibold">{{ $order->user?->username ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">موبایل</dt>
                        <dd class="fa-digits truncate font-semibold">{{ $order->mobile ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">ایمیل</dt>
                        <dd class="truncate font-semibold" dir="ltr">{{ $order->user?->email ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">نحوه پرداخت</dt>
                        <dd class="truncate font-semibold">{{ $this->paymentMethodLabel() }}</dd>
                    </div>
                </dl>
                @if ($order->user)
                    <a href="{{ route('admin.users.show', $order->user->id) }}"
                       class="ap-menu-link mt-3 !bg-surface-2 dark:!bg-night-3">
                        <x-admin-panel::icon name="user" class="size-4 text-brand-600 dark:text-brand-400" />
                        پروفایل کاربر
                    </a>
                @endif
            </section>

            {{-- آدرس ارسال --}}
            @if ($order->hasPhysicalProduct())
                <section class="ap-card p-5">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="map" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        آدرس ارسال
                    </h3>
                    <dl class="space-y-2.5 text-[13px]">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">استان / شهر</dt>
                            <dd class="truncate font-semibold">{{ $order->province?->name ?? '—' }} / {{ $order->city?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">کد پستی</dt>
                            <dd class="fa-digits truncate font-semibold">{{ $order->postal_code ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="mb-1 text-ink-2 dark:text-night-ink-2">آدرس</dt>
                            <dd class="leading-6 text-ink dark:text-night-ink">{{ $order->address ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">شیوه تحویل</dt>
                            <dd class="truncate font-semibold">{{ $orderItems->first()?->carrier?->title ?? 'پیک' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="shrink-0 text-ink-2 dark:text-night-ink-2">تاریخ تحویل</dt>
                            <dd class="fa-digits truncate font-semibold">{{ $this->jdate($orderItems->first()?->delivery_date, 'Y/m/d') }}</dd>
                        </div>
                    </dl>
                    @if ($order->location)
                        <div x-data="{ mapOpen: false }">
                            <button type="button"
                                    x-on:click="mapOpen = true"
                                    class="ap-btn-outline mt-3 w-full !py-2">
                                <x-admin-panel::icon name="map" class="size-4 text-brand-600 dark:text-brand-400" />
                                نمایش روی نقشه
                            </button>

                            {{-- مودال نقشه (OpenStreetMap Embed) --}}
                            <div x-show="mapOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="mapOpen" x-on:click="mapOpen=false" x-transition.opacity aria-hidden="true"></div>
                            <div x-show="mapOpen" x-on:click.stop
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2"
                                 role="dialog" aria-modal="true">
                                <div class="flex items-center justify-between border-b border-line px-5 py-3.5 dark:border-night-line">
                                    <h3 class="text-sm font-bold">موقعیت آدرس سفارش</h3>
                                    <button type="button" x-on:click="mapOpen=false" class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="بستن نقشه">
                                        <x-admin-panel::icon name="x" class="size-4.5" />
                                    </button>
                                </div>
                                <div class="bg-surface-2 dark:bg-night-3">
                                    <iframe title="نقشه موقعیت سفارش"
                                            src="https://www.openstreetmap.org/export/embed.html?bbox={{ explode(',', $order->location)[1] - 0.004 }}%2C{{ explode(',', $order->location)[0] - 0.002 }}%2C{{ explode(',', $order->location)[1] + 0.004 }}%2C{{ explode(',', $order->location)[0] + 0.002 }}&layer=mapnik&marker={{ explode(',', $order->location)[0] }}%2C{{ explode(',', $order->location)[1] }}"
                                            class="h-96 w-full border-0" loading="lazy"></iframe>
                                </div>
                                <div class="flex items-center justify-between gap-3 border-t border-line px-5 py-3 dark:border-night-line">
                                    <p class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2" dir="ltr">{{ $order->location }}</p>
                                    <a href="https://www.google.com/maps?q={{ $order->location }}" target="_blank" rel="noopener"
                                       class="ap-btn-outline !py-1.5 text-xs">
                                        <x-admin-panel::icon name="external-link" class="size-3.5" />
                                        گوگل مپ
                                    </a>
                                </div>
                            </div>
                        </div>
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>

    {{-- مودال تایید حذف سفارش --}}
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
            <h3 class="text-base font-bold">حذف سفارش <span class="fa-digits">#{{ number_format($order->id) }}</span></h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                اقلام و تراکنش‌های این سفارش نیز حذف می‌شوند و این عملیات غیرقابل بازگشت است.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله، حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>
</div>
