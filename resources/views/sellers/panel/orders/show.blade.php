<div>
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
                <x-admin-panel::icon name="shopping-cart" class="size-5 text-brand-600 dark:text-brand-400" />
                جزئیات سفارش‌
            </h1>
            <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">اینجا می‌توانید جزئیات سفارش‌های خود را ببینید.</p>
        </div>
        <a href="{{ $indexUrl }}" wire:navigate
           class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به سفارش
        </a>
    </div>

    {{-- ================= کارت تغییر وضعیت مرسوله ================= --}}
    <section class="ap-card mb-4">
        <header class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="truck" class="size-4 text-brand-600 dark:text-brand-400" />
                ⚙️ تغییر وضعیت مرسوله
            </h2>
            {{-- لینک کلاسیک پرینت — کنترلر قدیمی --}}
            <a href="{{ $printUrl }}" target="_blank"
               class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                <x-admin-panel::icon name="printer" class="size-4" /> چاپ
            </a>
        </header>

        <div class="p-4">
            {{-- فرم کلاسیک POST به روت قدیمی seller.orders.shipping-status —
                 فیلدهای عین اسکریپت قدیم: shipping_status / cancel_reason / canceled_refund_amount --}}
            <form action="{{ $shippingStatusUrl }}" method="POST" x-data="orderStatusForm()" @submit.prevent="submit($event)">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">وضعیت ارسال</span>
                        <select name="shipping_status" id="shipping-status-change" x-model="status"
                                class="ap-input w-full" {{ $disableStatus ? 'disabled' : '' }}>
                            <option value="w-pending" @selected($orderItem->shipping_status == 'w-pending')>در انتظار بررسی</option>
                            <option value="pending" @selected($orderItem->shipping_status == 'pending')>در حال بررسی</option>
                            <option value="processing" @selected($orderItem->shipping_status == 'processing')>در حال پردازش</option>
                            <option value="waiting" @selected($orderItem->shipping_status == 'waiting')>منتظر ارسال</option>
                            <option value="sent" @selected($orderItem->shipping_status == 'sent')>ارسال شد</option>
                            <option value="post-sent" @selected($orderItem->shipping_status == 'post-sent')>تحویل به پست</option>
                            <option value="delivered" @selected($orderItem->shipping_status == 'delivered')>تحویل داده شد</option>
                            <option value="canceled" @selected($orderItem->shipping_status == 'canceled')>لغو شد</option>
                        </select>
                    </label>

                    <div class="sm:col-span-2">
                        <span class="mb-1 block text-xs font-semibold text-transparent">.</span>
                        <button type="submit" {{ $disableStatus ? 'disabled' : '' }}
                                class="ap-btn-primary inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-lg px-4 text-xs font-bold sm:w-auto disabled:cursor-not-allowed disabled:opacity-50"
                                :class="busy ? 'opacity-60' : ''" :disabled="busy || {{ $disableStatus ? 'true' : 'false' }}">
                            <x-admin-panel::icon name="save" class="size-4" />
                            <span x-text="busy ? 'در حال ذخیره…' : 'ذخیره تغییرات'"></span>
                        </button>
                    </div>
                </div>

                {{-- دلیل لغو + بازگشت وجه — فقط هنگام انتخاب «لغو شد» (همان مودال cancelOrderModal قدیم) --}}
                <div x-show="status === 'canceled'" x-cloak x-transition class="mt-4 space-y-3">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">دلیل لغو سفارش</span>
                        <textarea name="cancel_reason" rows="3" class="ap-input w-full"
                                  placeholder="دلیل لغو سفارش را وارد کنید..."></textarea>
                    </label>
                    <label class="flex min-h-11 cursor-pointer items-center gap-2 rounded-lg bg-surface-2 px-3 dark:bg-night-2">
                        <input type="checkbox" name="canceled_refund_amount" value="1" checked
                               class="size-4 rounded border-line accent-emerald-600">
                        <span class="text-xs font-bold text-ink-700 dark:text-night-ink-1">بازگشت مبلغ به کیف پول کاربر</span>
                    </label>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-6 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                        <strong>گردش مالی:</strong>
                        <ul class="mt-1 list-inside list-disc space-y-0.5">
                            <li>➖ کسر از کیف پول فروشنده:
                                <strong class="fa-digits">{{ number_format($order->priceSeller() - $order->getAmountSellerCommission()['priceForSite']) }}</strong>
                                تومان
                            </li>
                            <li>➕ افزودن به کیف پول کاربر:
                                <strong class="fa-digits">{{ number_format($finalPayable) }}</strong>
                                تومان
                            </li>
                        </ul>
                        <p class="mt-1">لغو سفارش فقط در صورتی انجام می‌شود که موجودی کیف پول فروشنده کافی باشد.</p>
                    </div>
                </div>
            </form>

            @if($orderItem->order && $orderItem->order->status != 'paid')
                <div class="mt-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                    <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-4 shrink-0" />
                    سفارش اصلی هنوز پرداخت نشده است. تغییر وضعیت ارسال فقط پس از پرداخت امکان‌پذیر است.
                </div>
            @endif

            @if($orderItem->refunded || $orderItem->shipping_status == 'canceled')
                <div class="mt-3 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    سفارش قبلاً لغو شده و وجه آن برگشت داده شده است. امکان تغییر وضعیت وجود ندارد.
                </div>
            @endif
        </div>
    </section>

    {{-- ================= کارت‌های اطلاعاتی ================= --}}
    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- کارت فاکتور --}}
        <section class="ap-card border-r-4 border-r-emerald-500 p-4">
            <header class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-emerald-700 dark:text-emerald-400">📄 فاکتور مرسوله</h3>
                <x-admin-panel::icon name="file-text" class="size-5 text-emerald-600" />
            </header>
            <dl class="grid grid-cols-2 gap-3 text-xs">
                <div>
                    <dt class="text-ink-500 dark:text-night-ink-2">شماره مرسوله</dt>
                    <dd class="fa-digits mt-0.5 font-bold text-ink-900 dark:text-night-ink-1">{{ $orderItem->id }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500 dark:text-night-ink-2">تاریخ ثبت</dt>
                    <dd class="fa-digits mt-0.5 font-bold text-ink-900 dark:text-night-ink-1">{{ jdate($orderItem->created_at)->format('Y/m/d - H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500 dark:text-night-ink-2">وضعیت پرداخت</dt>
                    <dd class="mt-0.5">
                        @if($orderItem->order->status == 'paid')
                            <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">پرداخت شده</span>
                        @else
                            <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">پرداخت نشده</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-ink-500 dark:text-night-ink-2">مبلغ پرداخت شده</dt>
                    <dd class="mt-0.5 font-extrabold text-emerald-600">
                        <span class="fa-digits">{{ number_format($totalAmount) }}</span> تومان
                    </dd>
                </div>
            </dl>
        </section>

        {{-- کارت فروشنده --}}
        <section class="ap-card p-4">
            <header class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold">🏪 اطلاعات فروشنده</h3>
                <x-admin-panel::icon name="store" class="size-5 text-brand-600" />
            </header>
            <dl class="space-y-3 text-xs">
                <div>
                    <dt class="text-ink-500 dark:text-night-ink-2">نام فروشنده</dt>
                    <dd class="mt-0.5 font-bold text-ink-900 dark:text-night-ink-1">
                        {{ seller_info()->full_name }}
                        @if($orderItem->seller && $orderItem->seller->mobile)
                            <span class="fa-digits mr-1 block text-[11px] text-ink-500 dark:text-night-ink-2">{{ $orderItem->seller->mobile }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-ink-500 dark:text-night-ink-2">کمیسیون فروشنده</dt>
                    <dd class="mt-0.5 font-bold text-ink-900 dark:text-night-ink-1">
                        @if($orderItem->order->getAmountSellerCommission() and $orderItem->seller_id)
                            <span class="fa-digits">{{ number_format($orderItem->order->getAmountSellerCommission()['priceForSite']).' تومان' }}</span>
                            (<span class="fa-digits">{{ $orderItem->order->getAmountSellerCommission()['commission'] }}</span>%)
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-ink-500 dark:text-night-ink-2">
                        قیمت نهایی با کمیسیون
                        (<span class="fa-digits">{{ $orderItem->order->getAmountSellerCommission()['commission'] }}</span>%)
                    </dt>
                    <dd class="mt-0.5 font-bold text-ink-900 dark:text-night-ink-1">
                        @if($orderItem->order->getAmountSellerCommission() and $orderItem->seller_id)
                            <span class="fa-digits">{{ number_format($orderItem->order->priceSeller() - $orderItem->order->getAmountSellerCommission()['priceForSite']) }}</span>
                            تومان
                        @else
                            -
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        {{-- کارت وضعیت مرسوله --}}
        <section class="ap-card border-r-4 {{ $orderItem->shipping_status == 'sent' ? 'border-r-emerald-500' : ($orderItem->shipping_status == 'canceled' ? 'border-r-red-500' : 'border-r-amber-500') }} p-4">
            <header class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-amber-700 dark:text-amber-400">⚡ وضعیت مرسوله</h3>
                <x-admin-panel::icon name="truck" class="size-5 text-amber-500" />
            </header>
            <dl class="space-y-3 text-xs">
                <div>
                    <dt class="text-ink-500 dark:text-night-ink-2">وضعیت ارسال</dt>
                    <dd class="mt-1">
                        <span class="ap-badge @if($orderItem->shipping_status == 'sent') bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300
                            @elseif($orderItem->shipping_status == 'delivered') bg-surface-3 text-ink-700 dark:bg-night-3 dark:text-night-ink-1
                            @elseif($orderItem->shipping_status == 'canceled') bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300
                            @else bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 @endif">
                            @switch($orderItem->shipping_status)
                                @case('w-pending') در انتظار بررسی @break
                                @case('pending') در حال بررسی @break
                                @case('processing') در حال پردازش @break
                                @case('waiting') منتظر ارسال @break
                                @case('sent') ارسال شد @break
                                @case('post-sent') تحویل به پست @break
                                @case('delivered') تحویل داده شد @break
                                @case('canceled') لغو شد @break
                                @default {{ $orderItem->shipping_status }}
                            @endswitch
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-ink-500 dark:text-night-ink-2">کد رهگیری</dt>
                    <dd class="fa-digits mt-0.5 font-bold text-ink-900 dark:text-night-ink-1" dir="ltr">
                        {{ $orderItem->tracking_code ?: '-' }}
                    </dd>
                </div>
            </dl>
        </section>
    </div>

    {{-- ================= اطلاعات گیرنده و آدرس ارسال ================= --}}
    <section class="ap-card mb-4">
        <header class="border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="text-sm font-bold">👤 اطلاعات گیرنده و آدرس ارسال</h2>
        </header>
        <div class="p-4">
            @if($orderItem->order && $orderItem->order->hasPhysicalProduct())
                <dl class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-3">
                    <div>
                        <dt class="text-ink-500 dark:text-night-ink-2">نام و نام خانوادگی</dt>
                        <dd class="mt-0.5 font-bold text-ink-900 dark:text-night-ink-1">{{ $orderItem->order->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500 dark:text-night-ink-2">شماره موبایل</dt>
                        <dd class="fa-digits mt-0.5 font-bold text-ink-900 dark:text-night-ink-1">{{ $orderItem->order->mobile ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500 dark:text-night-ink-2">استان و شهر</dt>
                        <dd class="mt-0.5 font-bold text-ink-900 dark:text-night-ink-1">
                            {{ $orderItem->order->province->name ?? '' }} - {{ $orderItem->order->city->name ?? '' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500 dark:text-night-ink-2">کد پستی</dt>
                        <dd class="fa-digits mt-0.5 font-bold text-ink-900 dark:text-night-ink-1">{{ $orderItem->order->postal_code ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500 dark:text-night-ink-2">شیوه تحویل</dt>
                        <dd class="mt-0.5 font-bold text-ink-900 dark:text-night-ink-1">{{ $orderItem->carrier ? $orderItem->carrier->title : 'پیک' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500 dark:text-night-ink-2">تاریخ تحویل</dt>
                        <dd class="fa-digits mt-0.5 font-bold text-ink-900 dark:text-night-ink-1">{{ $deliveryDate }}</dd>
                    </div>
                    <div class="sm:col-span-3">
                        <dt class="text-ink-500 dark:text-night-ink-2">آدرس کامل</dt>
                        <dd class="mt-0.5 font-semibold leading-6 text-ink-900 dark:text-night-ink-1">{{ $orderItem->order->address ?? '-' }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-xs text-ink-500 dark:text-night-ink-2">این سفارش محصول فیزیکی ندارد</p>
            @endif
        </div>
    </section>

    {{-- ================= جدول محصولات ================= --}}
    <section class="ap-card mb-4">
        <header class="border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="text-sm font-bold">📦 جزئیات محصولات</h2>
        </header>
        <div class="overflow-x-auto p-4">
            <table class="w-full min-w-[720px] text-right text-xs">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                        <th class="px-3 py-3 text-center font-bold" width="50">#</th>
                        <th class="px-3 py-3 text-center font-bold" width="80">تصویر</th>
                        <th class="px-3 py-3 font-bold">عنوان محصول</th>
                        <th class="px-3 py-3 text-center font-bold" width="100">کد محصول</th>
                        <th class="px-3 py-3 text-center font-bold" width="80">تعداد</th>
                        <th class="px-3 py-3 text-center font-bold" width="120">قیمت واحد</th>
                        <th class="px-3 py-3 text-center font-bold" width="120">تخفیف (تومان)</th>
                        <th class="px-3 py-3 text-center font-bold" width="150">قیمت نهایی</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $tableTotalAmount = 0;
                        $tableTotalDiscount = 0;
                        $products = $orderItem->products();
                    @endphp
                    @foreach($products as $item)
                        @php
                            $itemPrice = $item->real_price * $item->quantity;
                            $itemDiscountPercent = ($item->discount ?? 0);
                            $itemDiscountAmount = ($itemPrice * $itemDiscountPercent / 100);
                            $itemFinal = $itemPrice - $itemDiscountAmount;
                            $tableTotalDiscount += $itemDiscountAmount;
                            $tableTotalAmount += $itemFinal;
                        @endphp
                        <tr class="border-b border-line/70 last:border-0 dark:border-night-line/70">
                            <td class="px-3 py-3 text-center text-ink-500 dark:text-night-ink-2">{{ $loop->iteration }}</td>
                            <td class="px-3 py-3 text-center">
                                @if($item->product && $item->product->image)
                                    <img src="{{ asset($item->product->image) }}" alt="{{ $item->title }}"
                                         class="mx-auto size-12 rounded-lg border border-line object-cover dark:border-night-line">
                                @else
                                    <img src="{{ asset('empty.svg') }}" alt="بدون تصویر" class="mx-auto size-12 rounded-lg">
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <strong class="text-ink-900 dark:text-night-ink-1">
                                    @if($item->product)
                                        <a href="{{ route('front.products.show', $item->product) }}" target="_blank" class="hover:underline">{{ $item->title }}</a>
                                    @else
                                        {{ $item->title }}
                                    @endif
                                    @if ($item->get_price)
                                        @foreach ($item->get_price->get_attributes as $attribute)
                                            <span class="mt-0.5 flex items-center gap-1 text-[11px] font-normal text-ink-500 dark:text-night-ink-2">
                                                @if ($attribute->group->type == 'color')
                                                    <span class="size-3 rounded border border-line" style="background-color: {{ $attribute->value }};"></span>
                                                @endif
                                                {{ $attribute->group->name }}: {{ $attribute->name }}
                                            </span>
                                        @endforeach
                                    @endif
                                </strong>
                                @if($item->product && $item->product->title_fa)
                                    <br><small class="text-ink-500 dark:text-night-ink-2">{{ $item->product->title_fa }}</small>
                                @endif
                            </td>
                            <td class="fa-digits px-3 py-3 text-center text-ink-600 dark:text-night-ink-2">{{ $item->product_id ?? '-' }}</td>
                            <td class="fa-digits px-3 py-3 text-center">{{ number_format($item->quantity) }}</td>
                            <td class="fa-digits px-3 py-3 text-center">{{ number_format($item->real_price) }} تومان</td>
                            <td class="fa-digits px-3 py-3 text-center text-red-500">{{ $item->discount.'%' ?? '-' }}</td>
                            <td class="fa-digits px-3 py-3 text-center font-bold text-emerald-600">{{ number_format($itemFinal) }} تومان</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-surface-2 dark:bg-night-2">
                    <tr>
                        <td colspan="6" class="px-3 py-2 text-left font-bold">جمع تخفیف محصولات:</td>
                        <td class="fa-digits px-3 py-2 text-center font-bold text-red-500">{{ number_format($tableTotalDiscount) }} تومان</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="6" class="px-3 py-2 text-left font-bold">جمع کل محصولات:</td>
                        <td colspan="2" class="fa-digits px-3 py-2 text-center font-extrabold text-ink-900 dark:text-night-ink-1">{{ number_format($tableTotalAmount) }} تومان</td>
                    </tr>
                    @if(($orderItem->shipping_cost ?? 0) > 0)
                        <tr>
                            <td colspan="6" class="px-3 py-2 text-left">هزینه ارسال:</td>
                            <td colspan="2" class="fa-digits px-3 py-2 text-center">{{ number_format($orderItem->shipping_cost ?? 0) }} تومان</td>
                        </tr>
                    @endif
                    <tr class="bg-emerald-50 dark:bg-emerald-500/10">
                        <td colspan="6" class="px-3 py-2 text-left font-extrabold">مبلغ قابل پرداخت نهایی:</td>
                        <td colspan="2" class="fa-digits px-3 py-2 text-center text-base font-extrabold text-emerald-600">{{ number_format($finalPayable) }} تومان</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    {{-- ================= توضیحات سفارش ================= --}}
    @if($orderItem->order->description)
        <section class="ap-card mb-4">
            <header class="border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="text-sm font-bold">📝 توضیحات سفارش</h2>
            </header>
            <div class="p-4">
                <p class="rounded-xl bg-surface-2 p-3 text-xs leading-6 text-ink-700 dark:bg-night-2 dark:text-night-ink-1">
                    {{ $orderItem->order->description }}
                </p>
            </div>
        </section>
    @endif

    @script
    <script>
        /* ارسال فرم وضعیت مرسوله به روت قدیمی seller.orders.shipping-status
           (پاسخ JSON قدیم: {success, message}) — همان فیلدهای اسکریپت قدیم */
        Alpine.data('orderStatusForm', () => ({
            status: '{{ $orderItem->shipping_status }}',
            busy: false,
            async submit(event) {
                this.busy = true;
                try {
                    const form = event.target;
                    const payload = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: payload,
                    });
                    const data = await response.json();
                    if (data.success) {
                        $wire.dispatch('toast', { message: data.message || 'وضعیت با موفقیت تغییر کرد', type: 'success' });
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        $wire.dispatch('toast', { message: data.message || 'خطا در ثبت وضعیت', type: 'error' });
                    }
                } catch (error) {
                    $wire.dispatch('toast', { message: 'خطا در ارتباط با سرور', type: 'error' });
                } finally {
                    this.busy = false;
                }
            },
        }));
    </script>
    @endscript
</div>
