{{-- پرونده انبار — پنل جدید (فاز ۷-ب۳) — پورت show + products + export + stockHistory + آخرین حرکات --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="warehouse" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-black">{{ $warehouse->name }}</h1>
                <p class="fa-digits mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">
                    کد انبار: {{ $warehouse->code }}
                    @if ($warehouse->type == 'main')
                        — <span class="font-semibold text-brand-600 dark:text-brand-400">انبار اصلی</span>
                    @elseif ($warehouse->type == 'seller')
                        — <span class="font-semibold text-sky-600 dark:text-sky-400">انبار فروشنده</span>
                    @else
                        — <span class="font-semibold text-ink-2 dark:text-night-ink-2">انبار موقت</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($warehouse->is_active)
                <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">فعال</span>
            @else
                <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400">غیرفعال</span>
            @endif
            <a href="{{ route('np.warehouses.movements', ['warehouse' => $warehouse->id]) }}" wire:navigate class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="history" class="size-4" />
                تاریخچه حرکات
            </a>
            <a href="{{ route('np.warehouses.stock-take', ['warehouse' => $warehouse->id]) }}" wire:navigate class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="clipboard-list" class="size-4" />
                سرشماری انبار
            </a>
            <a href="{{ route('np.warehouses.bulk-stock', ['warehouse' => $warehouse->id]) }}" wire:navigate class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="sliders-horizontal" class="size-4" />
                بروزرسانی گروهی موجودی
            </a>
            <a href="{{ route('np.warehouses.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به لیست
            </a>
        </div>
    </div>

    {{-- کارت‌های آماری (پورت show قدیمی) --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="ap-card p-4">
            <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">محصولات انبار</p>
            <p class="fa-digits mt-1 text-2xl font-black">{{ number_format($this->stats['total_products']) }}</p>
            <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">تنوع‌ها: {{ number_format($this->stats['current_count']) }}</p>
        </div>
        <div class="ap-card p-4">
            <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">کل موجودی</p>
            <p class="fa-digits mt-1 text-2xl font-black">{{ number_format($this->stats['total_stock_current']) }}</p>
            <p class="mt-0.5 flex flex-wrap gap-1 text-[11px]">
                <span class="fa-digits text-emerald-600 dark:text-emerald-400">کم: {{ number_format($this->stats['low_stock']) }}</span>
                <span class="fa-digits text-red-600 dark:text-red-400">ناموجود: {{ number_format($this->stats['out_of_stock']) }}</span>
            </p>
        </div>
        <div class="ap-card p-4">
            <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">کل فروش</p>
            <p class="fa-digits mt-1 text-2xl font-black">{{ number_format($this->stats['total_sold']) }}</p>
            <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">میانگین هر تنوع: {{ number_format($this->stats['avg_sold_per_variation']) }}</p>
        </div>
        <div class="ap-card p-4">
            <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">ارزش موجودی انبار</p>
            <p class="fa-digits mt-1 text-2xl font-black">{{ number_format($this->stats['total_value']) }}</p>
            <p class="mt-0.5 truncate text-[11px] text-ink-3 dark:text-night-ink-2">پرفروش‌ترین: {{ $this->stats['best_seller_attributes'] }}</p>
        </div>
    </div>

    {{-- نمودارها (پورت getChartData — ECharts) --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <section class="ap-card overflow-hidden">
            <header class="flex items-center gap-2 border-b border-line px-5 py-4 dark:border-night-line">
                <x-admin-panel::icon name="trending-up" class="size-4.5 text-emerald-600 dark:text-emerald-400" />
                <h3 class="text-sm font-bold">۱۰ محصول پرفروش</h3>
            </header>
            <div data-chart="whTopProducts" data-options="{{ json_encode($this->topProductsChartOptions()) }}" class="h-72 w-full"></div>
        </section>
        <section class="ap-card overflow-hidden">
            <header class="flex items-center gap-2 border-b border-line px-5 py-4 dark:border-night-line">
                <x-admin-panel::icon name="chart-line" class="size-4.5 text-sky-600 dark:text-sky-400" />
                <h3 class="text-sm font-bold">فروش ماهانه (۶ ماه اخیر)</h3>
            </header>
            <div data-chart="whMonthlySales" data-options="{{ json_encode($this->monthlySalesChartOptions()) }}" class="h-72 w-full"></div>
        </section>
    </div>

    {{-- محصولات انبار (پورت products + فیلترها) --}}
    <div class="ap-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 dark:border-night-line">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="list" class="size-4.5 text-brand-600 dark:text-brand-400" />
                محصولات انبار
            </h3>
            <div class="flex items-center gap-2">
                <a href="{{ route('np.products.create') }}" wire:navigate class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن محصول جدید
                </a>
                <button type="button" x-data x-on:click="$dispatch('open-modal-export')" class="ap-btn !py-2">
                    <x-admin-panel::icon name="download" class="size-4" />
                    اکسل
                </button>
            </div>
        </div>

        {{-- فیلترهای محصولات --}}
        <div class="grid grid-cols-1 gap-3 border-b border-line p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-night-line">
            <div>
                <label class="mb-1.5 block text-xs font-semibold">جستجوی محصول</label>
                <input type="text" wire:model.live.debounce.400ms="searchProduct" class="ap-input !py-2" placeholder="نام محصول…">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">وضعیت موجودی</label>
                <select wire:model.live="stockStatus" class="ap-input !py-2">
                    <option value="all">همه محصولات</option>
                    <option value="in_stock">موجود</option>
                    <option value="low_stock">موجودی کم (&lt;5)</option>
                    <option value="critical_stock">بحرانی (&lt;2)</option>
                    <option value="out_of_stock">ناموجود</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">مرتب‌سازی</label>
                <select wire:model.live="sortBy" class="ap-input !py-2">
                    <option value="">جدیدترین</option>
                    <option value="title_asc">عنوان (الفبا)</option>
                    <option value="title_desc">عنوان (معکوس)</option>
                    <option value="stock_desc">بیشترین موجودی</option>
                    <option value="stock_asc">کمترین موجودی</option>
                    <option value="sold_desc">پرفروش‌ترین</option>
                    <option value="price_desc">گران‌ترین</option>
                    <option value="price_asc">ارزان‌ترین</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-3 py-3 text-start font-bold">تصویر</th>
                        <th class="px-3 py-3 text-start font-bold">نام محصول</th>
                        <th class="px-3 py-3 text-start font-bold">دسته‌بندی</th>
                        <th class="px-3 py-3 text-start font-bold">برند</th>
                        <th class="px-3 py-3 text-center font-bold">تنوع‌ها</th>
                        <th class="px-3 py-3 text-center font-bold">کل موجودی</th>
                        <th class="px-3 py-3 text-center font-bold">رزرو شده</th>
                        <th class="px-3 py-3 text-center font-bold">فروش</th>
                        <th class="px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->products as $product)
                        @php
                            $productPrices = $product->prices->where('warehouse_id', $warehouse->id);
                            $totalStock = $productPrices->sum('stock');
                            $totalReserved = $productPrices->sum('reserved_stock');
                            $totalSold = $productPrices->sum('sold_count');
                            $variationsCount = $productPrices->count();
                        @endphp
                        <tr wire:key="wh-product-{{ $product->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <img src="{{ $product->image ? asset($product->image) : asset('/empty.svg') }}" alt="{{ $product->title }}"
                                     class="size-10 rounded-xl border border-line object-cover dark:border-night-line">
                            </td>
                            <td class="max-w-48 px-3 py-3">
                                <a href="{{ route('np.products.edit', ['product' => $product]) }}" wire:navigate
                                   class="font-bold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400">{{ $product->title }}</a>
                                <p class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">کد: {{ number_format($product->id) }}</p>
                            </td>
                            <td class="max-w-32 px-3 py-3"><p class="truncate">{{ $product->category->title ?? '-' }}</p></td>
                            <td class="max-w-32 px-3 py-3"><p class="truncate">{{ $product->brand->name ?? '-' }}</p></td>
                            <td class="fa-digits px-3 py-3 text-center">
                                <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">{{ number_format($variationsCount) }}</span>
                            </td>
                            <td class="fa-digits px-3 py-3 text-center {{ $totalStock < 5 ? 'font-bold text-red-600 dark:text-red-400' : '' }}">{{ number_format($totalStock) }}</td>
                            <td class="fa-digits px-3 py-3 text-center">{{ number_format($totalReserved) }}</td>
                            <td class="fa-digits px-3 py-3 text-center">{{ number_format($totalSold) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <a href="{{ route('np.products.edit', ['product' => $product]) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="ویرایش محصول">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </a>
                                    <a href="{{ route('np.warehouses.variations', ['warehouse' => $warehouse->id, 'product' => $product->id]) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-500/10"
                                       title="مشاهده تنوع‌ها">
                                        <x-admin-panel::icon name="layers" class="size-4" />
                                    </a>
                                    <button type="button" wire:click="openStockHistory({{ $product->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-sky-50 hover:text-sky-600 dark:hover:bg-sky-500/10"
                                            title="تاریخچه موجودی">
                                        <x-admin-panel::icon name="clock" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="package" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هیچ محصولی در این انبار وجود ندارد</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی محصولات --}}
        <div class="border-t border-line px-4 py-3 dark:border-night-line">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    نمایش <span class="fa-digits">{{ number_format($this->products->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->products->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->products->total()) }}</span> محصول
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی محصولات">
                    @if ($this->products->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->products->onEachSide(1)->toArray()['links'] as $link)
                        @if ($link['label'] === '...')
                            <span class="px-2 text-xs text-ink-3">…</span>
                        @elseif (! is_numeric(trim($link['label'])))
                            {{-- قبلی/بعدی بالاتر مدیریت شده‌اند --}}
                        @elseif ($link['active'])
                            <span class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $link['label'] }}</span>
                        @else
                            <button wire:click="gotoPage({{ trim($link['label']) }})"
                                    class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                {{ $link['label'] }}
                            </button>
                        @endif
                    @endforeach

                    @if ($this->products->hasMorePages())
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

    {{-- آخرین حرکات انبار (پورت show) --}}
    <div class="ap-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 dark:border-night-line">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="clock" class="size-4.5 text-brand-600 dark:text-brand-400" />
                آخرین حرکات انبار
            </h3>
            <a href="{{ route('np.warehouses.movements', ['warehouse' => $warehouse->id]) }}" wire:navigate
               class="flex items-center gap-1 text-xs font-bold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400">
                مشاهده همه
                <x-admin-panel::icon name="chevron-left" class="size-3.5" />
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="px-3 py-3 text-start font-bold">تاریخ و زمان</th>
                        <th class="px-3 py-3 text-start font-bold">نوع</th>
                        <th class="px-3 py-3 text-start font-bold">محصول</th>
                        <th class="px-3 py-3 text-start font-bold">تنوع</th>
                        <th class="px-3 py-3 text-center font-bold">تعداد</th>
                        <th class="px-3 py-3 text-center font-bold">موجودی قبل/بعد</th>
                        <th class="px-3 py-3 text-start font-bold">توضیحات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->recentMovements as $movement)
                        <tr wire:key="wh-movement-{{ $movement->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="fa-digits whitespace-nowrap px-3 py-3">{{ \App\Livewire\Admin\Warehouses\WarehouseShow::jdate($movement->created_at, 'd F Y H:i') }}</td>
                            <td class="px-3 py-3">
                                <span class="ap-badge {{ $movement->type_badge_class }}">{{ $movement->type_label }}</span>
                            </td>
                            <td class="max-w-40 px-3 py-3">
                                @if ($movement->price?->product)
                                    <a href="{{ route('np.products.edit', ['product' => $movement->price->product]) }}" wire:navigate
                                       class="font-semibold text-brand-700 hover:underline dark:text-brand-400">{{ $movement->price->product->title }}</a>
                                @else
                                    <span class="text-ink-3 dark:text-night-ink-2">محصول حذف شده</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if ($movement->price)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($movement->price->attributes as $attr)
                                            <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ $attr->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-red-500">تنوع حذف شده</span>
                                @endif
                            </td>
                            <td class="fa-digits px-3 py-3 text-center font-bold {{ $movement->type == 'in' ? 'text-emerald-600 dark:text-emerald-400' : ($movement->type == 'out' ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400') }}">
                                {{ $movement->type == 'in' ? '+' : '-' }}{{ number_format($movement->quantity) }}
                            </td>
                            <td class="fa-digits px-3 py-3 text-center">{{ number_format($movement->before_stock) }} ← {{ number_format($movement->after_stock) }}</td>
                            <td class="max-w-56 px-3 py-3"><p class="truncate text-ink-2 dark:text-night-ink-2">{{ \Illuminate\Support\Str::limit($movement->description ?? '-', 50) }}</p></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="clock" class="size-8 opacity-40" />
                                    <p class="text-sm font-medium">هیچ حرکتی ثبت نشده است</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- مودال خروجی اکسل (پورت modal-export-excel — ارسال به روت قدیمی admin.warehouses.export) --}}
    <div x-data="{ open: false }" x-on:open-modal-export.window="open = true"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="flex items-center gap-2 text-base font-bold">
                <x-admin-panel::icon name="file-spreadsheet" class="size-5 text-emerald-600 dark:text-emerald-400" />
                خروجی اکسل محصولات
            </h3>
            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت موجودی</label>
                    <select wire:model="exportStockStatus" class="ap-input !py-2">
                        <option value="all">تمام محصولات</option>
                        <option value="in_stock">محصولات موجود</option>
                        <option value="out_of_stock">محصولات ناموجود</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">دسته‌بندی</label>
                    <select wire:model="exportCategoryId" class="ap-input !py-2">
                        <option value="">همه دسته‌بندی‌ها</option>
                        @foreach ($this->exportCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">برند</label>
                    <select wire:model="exportBrandId" class="ap-input !py-2">
                        <option value="">همه برندها</option>
                        @foreach ($this->exportBrands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="exportWarehouse"
                        class="ap-btn flex-1 bg-emerald-600 text-white hover:bg-emerald-700">
                    <x-admin-panel::icon name="download" class="size-4" />
                    دریافت خروجی
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- مودال تاریخچه موجودی محصول (پورت stockHistory + partials) --}}
    <div x-data="{ open: @entangle('historyProductId').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-base font-bold">
                    <x-admin-panel::icon name="clock" class="size-5 text-sky-600 dark:text-sky-400" />
                    تاریخچه موجودی
                    <span class="text-xs font-semibold text-ink-3 dark:text-night-ink-2">{{ $historyProductName }}</span>
                </h3>
                <button type="button" wire:click="closeStockHistory" class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-5" />
                </button>
            </div>

            <div class="overflow-y-auto p-5">
                {{-- آمار (پورت stockHistory) --}}
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div class="rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                        <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">کل موجودی</p>
                        <p class="fa-digits mt-1 text-lg font-black">{{ number_format($this->historyStats['total_stock']) }}</p>
                    </div>
                    <div class="rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                        <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">رزرو شده</p>
                        <p class="fa-digits mt-1 text-lg font-black">{{ number_format($this->historyStats['total_reserved']) }}</p>
                    </div>
                    <div class="rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                        <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">فروش</p>
                        <p class="fa-digits mt-1 text-lg font-black">{{ number_format($this->historyStats['total_sold']) }}</p>
                    </div>
                    <div class="rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                        <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">تعداد تنوع</p>
                        <p class="fa-digits mt-1 text-lg font-black">{{ number_format($this->historyStats['variations_count']) }}</p>
                    </div>
                </div>

                {{-- فیلترها --}}
                <form wire:submit="applyHistoryFilters" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">نوع حرکت</label>
                        <select wire:model.live="historyType" class="ap-input !py-2">
                            <option value="all">همه</option>
                            <option value="in">ورود</option>
                            <option value="out">خروج</option>
                            <option value="reserve">رزرو</option>
                            <option value="unreserve">لغو رزرو</option>
                            <option value="adjustment">تعدیل دستی</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">تنوع</label>
                        <select wire:model.live="historyVariation" class="ap-input !py-2">
                            <option value="">همه تنوع‌ها</option>
                            @foreach ($this->historyPrices as $price)
                                <option value="{{ $price->id }}">{{ $price->attributes->pluck('name')->implode(' - ') ?: ('تنوع #'.$price->id) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">از تاریخ</label>
                        <input type="text" wire:model="historyFrom" class="ap-input !py-2" placeholder="۱۴۰۴-۰۱-۰۱" x-data x-on:input.debounce.400ms="$wire.applyHistoryFilters()">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">تا تاریخ</label>
                        <input type="text" wire:model="historyTo" class="ap-input !py-2" placeholder="۱۴۰۴-۱۲-۲۹" x-data x-on:input.debounce.400ms="$wire.applyHistoryFilters()">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="ap-btn-outline !py-2 w-full">
                            <x-admin-panel::icon name="filter" class="size-4" />
                            فیلتر
                        </button>
                        <button type="button" wire:click="exportHistory" class="ap-btn !py-2 shrink-0" title="خروجی اکسل">
                            <x-admin-panel::icon name="download" class="size-4" />
                        </button>
                    </div>
                </form>

                {{-- جدول تاریخچه --}}
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-4xl text-start text-[13px]">
                        <thead>
                            <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                                <th class="px-3 py-3 text-start font-bold">تاریخ</th>
                                <th class="px-3 py-3 text-start font-bold">نوع</th>
                                <th class="px-3 py-3 text-start font-bold">تنوع</th>
                                <th class="px-3 py-3 text-center font-bold">تعداد</th>
                                <th class="px-3 py-3 text-center font-bold">قبل/بعد</th>
                                <th class="px-3 py-3 text-start font-bold">توضیحات</th>
                                <th class="px-3 py-3 text-start font-bold">اپراتور</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->historyItems ?? collect() as $movement)
                                <tr wire:key="wh-history-{{ $movement->id }}"
                                    class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                    <td class="fa-digits whitespace-nowrap px-3 py-3">{{ \App\Livewire\Admin\Warehouses\WarehouseShow::jdate($movement->created_at, 'Y/m/d H:i') }}</td>
                                    <td class="px-3 py-3">
                                        <span class="ap-badge {{ $this->movementTypeBadgeClass($movement->type) }}">{{ $this->movementTypeLabel($movement->type) }}</span>
                                    </td>
                                    <td class="px-3 py-3">
                                        @if ($movement->price)
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($movement->price->attributes as $attr)
                                                    <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ $attr->name }}</span>
                                                @endforeach
                                                @if ($movement->price->attributes->isEmpty())
                                                    <span class="text-xs text-ink-3 dark:text-night-ink-2">بدون ویژگی</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-red-500">تنوع حذف شده</span>
                                        @endif
                                    </td>
                                    <td class="fa-digits px-3 py-3 text-center font-bold {{ $movement->type == 'in' ? 'text-emerald-600 dark:text-emerald-400' : ($movement->type == 'out' ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400') }}">
                                        {{ number_format($movement->quantity) }}
                                    </td>
                                    <td class="fa-digits px-3 py-3 text-center">{{ number_format($movement->before_stock) }} ← {{ number_format($movement->after_stock) }}</td>
                                    <td class="max-w-56 px-3 py-3"><p class="truncate text-ink-2 dark:text-night-ink-2">{{ \Illuminate\Support\Str::limit($movement->description ?? '-', 60) }}</p></td>
                                    <td class="px-3 py-3"><span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ $movement->operator_name }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                                            <x-admin-panel::icon name="clock" class="size-8 opacity-40" />
                                            <p class="text-sm font-medium">هیچ حرکتی برای این محصول ثبت نشده است</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- صفحه‌بندی تاریخچه --}}
                @if ($this->historyItems && $this->historyItems->lastPage() > 1)
                    <div class="mt-3 flex items-center justify-end gap-1">
                        @if ($this->historyItems->onFirstPage())
                            <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                        @else
                            <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                                <x-admin-panel::icon name="chevron-right" class="size-4" />
                            </button>
                        @endif
                        <span class="fa-digits px-2 text-xs text-ink-3 dark:text-night-ink-2">صفحه {{ number_format($this->historyItems->currentPage()) }} از {{ number_format($this->historyItems->lastPage()) }}</span>
                        @if ($this->historyItems->hasMorePages())
                            <button wire:click="nextPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه بعد">
                                <x-admin-panel::icon name="chevron-left" class="size-4" />
                            </button>
                        @else
                            <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-left" class="size-4" /></span>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end gap-2.5 border-t border-line px-5 py-4 dark:border-night-line">
                <button type="button" wire:click="closeStockHistory" class="ap-btn-ghost !py-2">بستن</button>
            </div>
        </div>
    </div>

    {{-- دانلود با رویداد open-url (الگوی فاز ۲/۴) --}}
    <div x-on:open-url.window="window.open($event.detail.url, '_blank')"></div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="searchProduct, stockStatus, sortBy, previousPage, nextPage, gotoPage, exportWarehouse, openStockHistory, applyHistoryFilters"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
