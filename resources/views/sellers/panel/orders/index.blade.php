<div>
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
                <x-admin-panel::icon name="shopping-cart" class="size-5 text-brand-600 dark:text-brand-400" />
                سفارش‌ها
            </h1>
            <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">اینجا می‌توانید تمام سفارش‌های خود را ببینید.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ $notCompletedUrl }}" wire:navigate
               class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                <x-admin-panel::icon name="package" class="size-4" />
                محصولات منتظر ارسال
            </a>
            <button type="button" x-data x-on:click="$dispatch('open-orders-export')"
                    class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                <x-admin-panel::icon name="file-spreadsheet" class="size-4" />
                خروجی گرفتن از لیست
            </button>
        </div>
    </div>

    {{-- ================= فیلترها (پورت partials/filters قدیمی) ================= --}}
    <section class="ap-card mb-4">
        <header class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4 text-brand-600 dark:text-brand-400" />
                فیلتر کردن
            </h2>
        </header>
        <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نام و نام خانوادگی</span>
                <input type="text" wire:model.live.debounce.400ms="fullname" value="{{ $fullname }}"
                       class="ap-input w-full" placeholder="جستجوی نام مشتری">
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نام کاربری</span>
                <input type="text" wire:model.live.debounce.400ms="username" value="{{ $username }}"
                       class="ap-input w-full" placeholder="جستجوی نام کاربری">
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شماره سفارش</span>
                <input type="text" wire:model.live.debounce.400ms="order_id" value="{{ $order_id }}"
                       class="ap-input w-full fa-digits" placeholder="مثال: ۱۲۳">
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">وضعیت</span>
                <select wire:model.live="status" class="ap-input w-full">
                    <option value="all" @selected($status === 'all')>همه</option>
                    <option value="paid" @selected($status === 'paid')>پرداخت شده</option>
                    <option value="unpaid" @selected($status === 'unpaid')>پرداخت نشده</option>
                    <option value="canceled" @selected($status === 'canceled')>لغو شده</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">وضعیت ارسال</span>
                <select wire:model.live="shipping_status" class="ap-input w-full">
                    <option value="all" @selected($shipping_status === 'all')>همه</option>
                    <option value="w-pending" @selected($shipping_status === 'w-pending')>در انتظار بررسی</option>
                    <option value="pending" @selected($shipping_status === 'pending')>در حال بررسی</option>
                    <option value="waiting" @selected($shipping_status === 'waiting')>منتظر ارسال</option>
                    <option value="sent" @selected($shipping_status === 'sent')>ارسال شد</option>
                    <option value="canceled" @selected($shipping_status === 'canceled')>ارسال لغو شد</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نام محصول</span>
                <input type="text" wire:model.live.debounce.400ms="product_name" value="{{ $product_name }}"
                       class="ap-input w-full">
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">آیدی محصول</span>
                <input type="text" wire:model.live.debounce.400ms="product_id" value="{{ $product_id }}"
                       class="ap-input w-full fa-digits">
            </label>
            <div class="grid grid-cols-2 gap-2">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">از تاریخ</span>
                    <input type="text" wire:model.live.debounce.400ms="from_date" value="{{ $from_date }}"
                           data-jdp data-jdp-only-date="true" autocomplete="off"
                           class="ap-input w-full fa-digits" placeholder="۱۴۰۴-۰۱-۰۱">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تا تاریخ</span>
                    <input type="text" wire:model.live.debounce.400ms="to_date" value="{{ $to_date }}"
                           data-jdp data-jdp-only-date="true" autocomplete="off"
                           class="ap-input w-full fa-digits" placeholder="{{ $todayJalali }}">
                </label>
            </div>
        </div>
    </section>

    {{-- ================= نوار عملیات گروهی ================= --}}
    @if(count($selected) > 0)
        <section class="ap-card mb-4 border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/10">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-amber-700 dark:text-amber-300">
                    <span class="fa-digits">{{ count($selected) }}</span> مورد انتخاب شده:
                </span>
                {{-- لینک‌های کلاسیک به کنترلرهای قدیمی — روت‌های GET با ids[] --}}
                <a href="{{ $printAllUrl }}?{{ http_build_query(['ids' => $selected]) }}" target="_blank"
                   class="ap-btn inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-bold text-white hover:bg-emerald-700">
                    <x-admin-panel::icon name="printer" class="size-4" /> پرینت فاکتور ها
                </a>
                <a href="{{ $printAllShippingFormsMinUrl }}?{{ http_build_query(['ids' => $selected]) }}" target="_blank"
                   class="ap-btn inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-bold text-white hover:bg-emerald-700">
                    <x-admin-panel::icon name="tag" class="size-4" /> پرینت لیبل ها
                </a>
                <a href="{{ $printAllShippingFormsUrl }}?{{ http_build_query(['ids' => $selected]) }}" target="_blank"
                   class="ap-btn inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-emerald-100 px-3 text-xs font-bold text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300">
                    <x-admin-panel::icon name="labels" class="size-4" /> پرینت برچسب ها
                </a>
                <button type="button" wire:click="$set('selected', [])"
                        class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                    <x-admin-panel::icon name="x" class="size-4" /> لغو انتخاب
                </button>
            </div>
        </section>
    @endif

    {{-- ================= جدول سفارش‌ها ================= --}}
    <section class="ap-card overflow-hidden">
        <header class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="list" class="size-4 text-brand-600 dark:text-brand-400" />
                لیست سفارشات
            </h2>
            <span class="text-[11px] text-ink-500 dark:text-night-ink-2">
                تعداد نتایج: <span class="fa-digits font-bold">{{ number_format($orders->total()) }}</span>
            </span>
        </header>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-right text-xs">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                        <th class="px-3 py-3 font-bold">
                            <input type="checkbox" wire:click="toggleAll" class="size-4 cursor-pointer rounded border-line"
                                   aria-label="انتخاب همهٔ ردیف‌های صفحه" @checked(count($selected) > 0 && count($selected) === $orders->count())>
                        </th>
                        <th class="px-3 py-3 font-bold">#</th>
                        <th class="px-3 py-3 font-bold">نام سفارش دهنده</th>
                        <th class="px-3 py-3 font-bold">شماره سفارش</th>
                        <th class="px-3 py-3 font-bold">تاریخ ثبت سفارش</th>
                        <th class="px-3 py-3 font-bold">قیمت کل</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت ارسال</th>
                        <th class="px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $index => $row)
                        <tr class="border-b border-line/70 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/70 dark:hover:bg-night-2">
                            <td class="px-3 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $row->id }}"
                                       class="size-4 cursor-pointer rounded border-line" aria-label="انتخاب سفارش {{ $row->id }}">
                            </td>
                            <td class="px-3 py-3 text-ink-500 dark:text-night-ink-2">
                                <span class="fa-digits">{{ ($orders->currentPage() - 1) * $orders->perPage() + $index + 1 }}</span>
                            </td>
                            <td class="px-3 py-3 font-semibold text-ink-800 dark:text-night-ink-1">{{ $row->name }}</td>
                            <td class="px-3 py-3">
                                <span class="fa-digits font-bold text-brand-700 dark:text-brand-400">{{ $row->id }}</span>
                            </td>
                            <td class="px-3 py-3 text-ink-600 dark:text-night-ink-2">
                                <span class="fa-digits">{{ $row->created_at }}</span>
                            </td>
                            <td class="px-3 py-3 font-bold">
                                <span class="fa-digits">{{ number_format($row->priceSeller) }}</span>
                                <span class="text-[10px] text-ink-500 dark:text-night-ink-2">تومان</span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($row->status === 'paid')
                                    <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">پرداخت شده</span>
                                @elseif($row->status === 'canceled')
                                    <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">لغو شده</span>
                                @else
                                    <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">پرداخت نشده</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($row->shipping_status)
                                    <span class="ap-badge bg-surface-3 text-ink-700 dark:bg-night-3 dark:text-night-ink-1">
                                        @switch($row->shipping_status)
                                            @case('w-pending') در انتظار بررسی @break
                                            @case('pending') در حال بررسی @break
                                            @case('processing') در حال پردازش @break
                                            @case('waiting') منتظر ارسال @break
                                            @case('sent') ارسال شد @break
                                            @case('post-sent') تحویل به پست @break
                                            @case('delivered') تحویل داده شد @break
                                            @case('canceled') لغو شد @break
                                            @default {{ $row->shipping_status }}
                                        @endswitch
                                    </span>
                                @else
                                    <span class="text-ink-400 dark:text-night-ink-2">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <a href="{{ route('seller.orders.show', ['order' => $row->id]) }}" wire:navigate
                                   class="ap-btn-outline inline-flex min-h-11 items-center gap-1 rounded-lg px-2.5 text-[11px] font-bold">
                                    <x-admin-panel::icon name="eye" class="size-4" />
                                    مشاهده
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-ink-500 dark:text-night-ink-2">
                                <x-admin-panel::icon name="inbox" class="mx-auto mb-2 size-8 text-ink-300 dark:text-night-ink-3" />
                                چیزی برای نمایش وجود ندارد!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="border-t border-line px-4 py-3 dark:border-night-line">
            {{ $orders->links() }}
        </footer>
    </section>

    {{-- ================= مودال خروجی (پورت مودال export قدیمی — فرم GET کلاسیک) ================= --}}
    <div x-data="{ open: false }" x-on:open-orders-export.window="open = true"
         x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="open = false" x-transition.opacity></div>
        <div class="ap-card relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto"
             x-show="open" x-transition.scale.origin.center>
            <header class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
                <h3 class="text-sm font-bold">فیلدهای مورد نظر را انتخاب کنید</h3>
                <button type="button" x-on:click="open = false" class="min-h-11 rounded p-1 text-ink-500 hover:text-red-500" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </header>
            {{-- فرم خروجی اکسل — همان اکشن و فیلدهای نام‌گذاری‌شدهٔ قدیمی (filters[row] و…) --}}
            <form method="GET" action="{{ $exportUrl }}" class="p-4">
                <div class="grid grid-cols-2 gap-2 text-xs sm:grid-cols-3">
                    @foreach([
                        'row' => 'ردیف', 'id' => 'آیدی', 'user_id' => 'آیدی کاربر',
                        'name' => 'نام و نام خانوادگی', 'mobile' => 'شماره همراه',
                        'province' => 'استان', 'city' => 'شهر', 'postal_code' => 'کد پستی',
                        'address' => 'آدرس کامل', 'carrier' => 'شیوه تحویل', 'created_at' => 'تاریخ ثبت',
                        'shipping_cost' => 'هزینه ارسال', 'total_discount' => 'تخفیف', 'price' => 'جمع قیمت',
                        'description' => 'توضیحات سفارش', 'products' => 'لیست محصولات',
                    ] as $field => $label)
                        <label class="flex min-h-11 cursor-pointer items-center gap-2 rounded-lg px-1">
                            <input type="checkbox" name="filters[{{ $field }}]" value="1" checked
                                   class="size-4 rounded border-line accent-emerald-600">
                            <span class="font-semibold text-ink-700 dark:text-night-ink-1">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="mt-3">
                    <label class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نوع خروجی</label>
                    <select name="export_type" class="ap-input w-full">
                        <option value="excel">اکسل</option>
                    </select>
                </div>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button" x-on:click="open = false"
                            class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-4 text-xs font-bold">انصراف</button>
                    <button type="submit"
                            class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-4 text-xs font-bold">
                        <x-admin-panel::icon name="download" class="size-4" /> خروجی گرفتن
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
