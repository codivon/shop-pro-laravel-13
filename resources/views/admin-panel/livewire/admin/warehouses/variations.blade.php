{{-- تنوع‌های محصول در انبار — پنل جدید (فاز ۷-ب۳) — پورت productVariations + فرم‌های افزودن/ویرایش/حذف تنوع --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('np.warehouses.index') }}" wire:navigate class="font-semibold text-ink-3 transition-colors hover:text-brand-600 dark:text-night-ink-2">انبارها</a>
            <x-admin-panel::icon name="chevron-left" class="size-3.5 text-ink-3" />
            <a href="{{ route('np.warehouses.show', ['warehouse' => $warehouse->id]) }}" wire:navigate class="font-semibold text-ink-3 transition-colors hover:text-brand-600 dark:text-night-ink-2">{{ $warehouse->name }}</a>
            <x-admin-panel::icon name="chevron-left" class="size-3.5 text-ink-3" />
            <span class="font-black">تنوع‌ها</span>
        </div>
        <a href="{{ route('np.warehouses.show', ['warehouse' => $warehouse->id]) }}" wire:navigate class="ap-btn-ghost !py-2">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به انبار
        </a>
    </div>

    @if (! $this->product)
        {{-- انتخابگر محصول (وقتی ?product= داده نشده) --}}
        <div class="ap-card p-5">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="package-search" class="size-4.5 text-brand-600 dark:text-brand-400" />
                انتخاب محصول برای مشاهده تنوع‌ها در «{{ $warehouse->name }}»
            </h3>
            <div class="mt-4 max-w-sm">
                <input type="text" wire:model.live.debounce.400ms="searchProduct" class="ap-input !py-2" placeholder="جستجوی محصول…">
            </div>
            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($this->warehouseProducts as $wp)
                    <a href="{{ route('np.warehouses.variations', ['warehouse' => $warehouse->id, 'product' => $wp->id]) }}" wire:navigate
                       class="flex items-center gap-3 rounded-xl border border-line p-3 transition-colors hover:border-brand-300 hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                        <img src="{{ $wp->image ? asset($wp->image) : asset('/empty.svg') }}" alt="{{ $wp->title }}" class="size-11 rounded-xl border border-line object-cover dark:border-night-line">
                        <span class="min-w-0">
                            <span class="block truncate text-[13px] font-bold">{{ $wp->title }}</span>
                            <span class="fa-digits mt-0.5 block text-[11px] text-ink-3 dark:text-night-ink-2">{{ number_format($wp->warehouse_prices_count) }} تنوع در این انبار</span>
                        </span>
                        <x-admin-panel::icon name="chevron-left" class="ms-auto size-4 shrink-0 text-ink-3" />
                    </a>
                @empty
                    <p class="py-6 text-sm text-ink-3 dark:text-night-ink-2">هیچ محصولی در این انبار وجود ندارد.</p>
                @endforelse
            </div>
        </div>
    @else
        {{-- هدر محصول --}}
        <div class="ap-card flex flex-wrap items-center gap-4 p-4">
            <img src="{{ $this->product->image ? asset($this->product->image) : asset('/empty.svg') }}" alt="{{ $this->product->title }}"
                 class="size-20 rounded-2xl border border-line object-cover dark:border-night-line">
            <div class="min-w-0">
                <h1 class="truncate text-lg font-black">{{ $this->product->title }}</h1>
                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                    <span class="fa-digits ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">کد محصول: {{ number_format($this->product->id) }}</span>
                    @if ($this->product->category)
                        <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">دسته: {{ $this->product->category->title }}</span>
                    @endif
                    @if ($this->product->brand)
                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">برند: {{ $this->product->brand->name }}</span>
                    @endif
                </div>
            </div>
            <div class="ms-auto flex flex-wrap items-center gap-2">
                <a href="{{ route('np.products.edit', ['product' => $this->product]) }}" wire:navigate class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="pencil" class="size-4" />
                    ویرایش محصول
                </a>
                <button type="button" wire:click="openCreate" class="ap-btn !py-2">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن تنوع جدید
                </button>
            </div>
        </div>

        {{-- کارت‌های آماری (پورت product-variation-stats) --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="ap-card p-4">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">تنوع‌های این انبار</p>
                <p class="fa-digits mt-1 text-2xl font-black">{{ number_format($this->stats['current_count']) }}</p>
                <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">موجودی: {{ number_format($this->stats['total_stock_current']) }}</p>
            </div>
            <div class="ap-card p-4">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">سایر انبارهای شما</p>
                <p class="fa-digits mt-1 text-2xl font-black">{{ number_format($this->stats['other_current_count']) }}</p>
                <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">موجودی: {{ number_format($this->stats['total_stock_other_seller']) }}</p>
            </div>
            <div class="ap-card p-4">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">فروشگاه اصلی</p>
                <p class="fa-digits mt-1 text-2xl font-black">{{ number_format($this->stats['main_count']) }}</p>
                <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">موجودی: {{ number_format($this->stats['total_stock_main']) }}</p>
            </div>
            <div class="ap-card p-4">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">فروشندگان دیگر</p>
                <p class="fa-digits mt-1 text-2xl font-black">{{ number_format($this->stats['other_sellers_count']) }}</p>
                <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">موجودی: {{ number_format($this->stats['total_stock_other_sellers']) }}</p>
            </div>
        </div>

        {{-- جمع‌بندی (پورت alert قدیمی) --}}
        <div class="flex items-start gap-2 rounded-xl border border-sky-200 bg-sky-50 p-3.5 text-[13px] leading-6 text-sky-900 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
            <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
            <span>
                <strong>جمع‌بندی:</strong>
                این محصول در مجموع دارای <strong class="fa-digits">{{ number_format($this->stats['current_count'] + $this->stats['other_current_count'] + $this->stats['main_count'] + $this->stats['other_sellers_count']) }}</strong> تنوع
                در <strong class="fa-digits">{{ number_format(1 + $this->stats['other_current_count'] + $this->stats['main_count'] + $this->stats['other_sellers_count']) }}</strong> انبار مختلف است.
            </span>
        </div>

        {{-- تنوع‌های انبار فعلی --}}
        <div class="ap-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="layers" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    تنوع‌های موجود در انبار {{ $warehouse->name }}
                    <span class="fa-digits ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">{{ number_format($this->currentVariations->count()) }} تنوع</span>
                </h3>
                <button type="button" wire:click="openCreate" class="ap-btn !py-2">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن تنوع جدید
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-4xl text-start text-[13px]">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                            <th class="w-12 px-3 py-3 text-start font-bold">#</th>
                            <th class="px-3 py-3 text-start font-bold">ویژگی‌ها</th>
                            <th class="px-3 py-3 text-center font-bold">قیمت</th>
                            <th class="px-3 py-3 text-center font-bold">قیمت با تخفیف</th>
                            <th class="px-3 py-3 text-center font-bold">موجودی</th>
                            <th class="px-3 py-3 text-center font-bold">تعداد فروش</th>
                            <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                            <th class="px-3 py-3 text-center font-bold">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->currentVariations as $index => $price)
                            <tr wire:key="variation-{{ $price->id }}"
                                class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                <td class="fa-digits px-3 py-3">{{ number_format($index + 1) }}</td>
                                <td class="max-w-40 px-3 py-3">
                                    @if ($price->attributes && $price->attributes->count())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($price->attributes as $attr)
                                                <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                                    @if ($attr->group && $attr->group->type == 'color')
                                                        <span class="inline-block size-2.5 rounded-full" style="background-color: {{ $attr->value ?? '#6c757d' }};"></span>
                                                    @endif
                                                    {{ $attr->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-ink-3 dark:text-night-ink-2">بدون ویژگی</span>
                                    @endif
                                    @if ($index === 0 && $price->sold_count > 0)
                                        <span class="ap-badge mt-1 bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                                            <x-admin-panel::icon name="star" class="size-3" />
                                            پرفروش‌ترین
                                        </span>
                                    @endif
                                </td>
                                <td class="fa-digits px-3 py-3 text-center">{{ number_format($price->price) }} تومان</td>
                                <td class="fa-digits px-3 py-3 text-center">
                                    @if ($price->discount_price && $price->discount_price < $price->price)
                                        <span class="text-emerald-600 dark:text-emerald-400">{{ number_format($price->discount_price) }} تومان</span>
                                        <small class="block text-red-500">({{ $price->discount }}%)</small>
                                    @else
                                        <span class="text-ink-3 dark:text-night-ink-2">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($price->stock > 0)
                                        <span class="fa-digits ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">{{ number_format($price->stock) }}</span>
                                    @else
                                        <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400">ناموجود</span>
                                    @endif
                                </td>
                                <td class="fa-digits px-3 py-3 text-center">
                                    <span class="ap-badge {{ $price->sold_count > 100 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' : ($price->sold_count > 10 ? 'bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400' : 'bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2') }}">
                                        {{ number_format($price->sold_count ?? 0) }}
                                    </span>
                                    @if ($price->sold_count > 0 && $this->stats['total_sold_current'] > 0)
                                        <div class="mx-auto mt-1 h-1 w-16 overflow-hidden rounded bg-surface-3 dark:bg-night-3">
                                            <div class="h-full rounded bg-emerald-500" style="width: {{ min(100, round(($price->sold_count / $this->stats['total_sold_current']) * 100)) }}%"></div>
                                        </div>
                                        <small class="fa-digits text-ink-3 dark:text-night-ink-2">{{ round(($price->sold_count / $this->stats['total_sold_current']) * 100, 1) }}% از کل</small>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($price->published)
                                        <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">فعال</span>
                                    @else
                                        <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400">غیرفعال</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-center gap-0.5">
                                        <button type="button" wire:click="loadVariationData({{ $price->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-500/10"
                                                title="ویرایش">
                                            <x-admin-panel::icon name="pencil" class="size-4" />
                                        </button>
                                        <button type="button" wire:click="confirmDeleteVariation({{ $price->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="layers" class="size-8 opacity-40" />
                                        <p class="text-sm font-medium">هیچ تنوعی در این انبار وجود ندارد</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($this->currentVariations->count())
                        <tfoot>
                            <tr class="border-t border-line bg-surface-2 font-bold dark:border-night-line dark:bg-night-3">
                                <td colspan="4" class="px-3 py-3 text-end">جمع کل:</td>
                                <td class="fa-digits px-3 py-3 text-center">{{ number_format($this->currentVariations->sum('stock')) }}</td>
                                <td class="fa-digits px-3 py-3 text-center">{{ number_format($this->currentVariations->sum('sold_count')) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- پرفروش‌ترین تنوع‌ها (پورت بخش کارت‌های پرفروش) --}}
        @php $topSellers = $this->currentVariations->where('sold_count', '>', 0)->take(5); @endphp
        @if ($topSellers->count() > 0)
            <div class="ap-card p-4">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="trending-up" class="size-4.5 text-amber-600 dark:text-amber-400" />
                    پرفروش‌ترین تنوع‌های این انبار
                </h3>
                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                    @foreach ($topSellers as $price)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-line p-3 dark:border-night-line">
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-bold">{{ $price->attributes->pluck('name')->implode(' ') ?: 'بدون ویژگی' }}</p>
                                <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">فروش: {{ number_format($price->sold_count) }} عدد</p>
                            </div>
                            <div class="text-end">
                                <span class="fa-digits ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">{{ number_format($price->price) }} تومان</span>
                                <div class="mt-1 h-1 w-36 overflow-hidden rounded bg-surface-3 dark:bg-night-3">
                                    <div class="h-full rounded bg-emerald-500" style="width: {{ min(100, round(($price->sold_count / max(1, $topSellers->first()->sold_count)) * 100)) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- سایر جداول (سایر انبارهای شما / فروشگاه اصلی / فروشندگان دیگر) --}}
        @if ($this->otherSellerVariations->count() > 0)
            <div class="ap-card overflow-hidden">
                <div class="border-b border-line px-4 py-3 dark:border-night-line">
                    <h3 class="fa-digits flex items-center gap-2 text-sm font-bold text-sky-700 dark:text-sky-400">
                        <x-admin-panel::icon name="layers" class="size-4.5" />
                        همچنین دارای {{ number_format($this->otherSellerVariations->count()) }} تنوع در سایر انبارهای شما
                    </h3>
                </div>
                <div class="overflow-x-auto">@include('admin-panel::livewire.admin.warehouses.partials.variations-table', ['variations' => $this->otherSellerVariations])</div>
            </div>
        @endif

        @if ($this->mainWarehouseVariations->count() > 0)
            <div class="ap-card overflow-hidden">
                <div class="border-b border-line px-4 py-3 dark:border-night-line">
                    <h3 class="fa-digits flex items-center gap-2 text-sm font-bold text-ink-2 dark:text-night-ink-2">
                        <x-admin-panel::icon name="warehouse" class="size-4.5" />
                        همچنین دارای {{ number_format($this->mainWarehouseVariations->count()) }} تنوع در فروشگاه اصلی
                    </h3>
                </div>
                <div class="overflow-x-auto">@include('admin-panel::livewire.admin.warehouses.partials.variations-table', ['variations' => $this->mainWarehouseVariations])</div>
            </div>
        @endif

        @if ($this->otherSellersVariations->count() > 0)
            <div class="ap-card overflow-hidden">
                <div class="border-b border-line px-4 py-3 dark:border-night-line">
                    <h3 class="fa-digits flex items-center gap-2 text-sm font-bold text-amber-700 dark:text-amber-400">
                        <x-admin-panel::icon name="users" class="size-4.5" />
                        همچنین دارای {{ number_format($this->otherSellersVariations->count()) }} تنوع در انبار سایر فروشندگان
                    </h3>
                </div>
                <div class="overflow-x-auto">@include('admin-panel::livewire.admin.warehouses.partials.variations-table', ['variations' => $this->otherSellersVariations])</div>
            </div>
        @endif
    @endif

    {{-- مودال افزودن تنوع (پورت storeVariation) --}}
    <div x-data="{ open: @entangle('creating').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="flex items-center gap-2 text-base font-bold">
                <x-admin-panel::icon name="plus" class="size-5 text-emerald-600 dark:text-emerald-400" />
                افزودن تنوع جدید برای «{{ $this->product?->title }}»
            </h3>

            <form wire:submit="storeVariation" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="mb-1.5 block text-xs font-semibold">انبار <span class="text-red-500">*</span></label>
                    <select wire:model="addWarehouse" class="ap-input !py-2">
                        <option value="">انتخاب انبار</option>
                        @foreach ($this->warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }}) @if ($wh->type == 'main') - انبار اصلی @elseif ($wh->type == 'seller') - انبار فروشنده @else - انبار موقت @endif</option>
                        @endforeach
                    </select>
                    @error('addWarehouse') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                @foreach ($this->attributeGroups as $group)
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">{{ $group->name }}</label>
                        <select wire:model="addAttributes.{{ $group->id }}" class="ap-input !py-2">
                            <option value="">انتخاب کنید</option>
                            @foreach ($group->get_attributes as $attribute)
                                <option value="{{ $attribute->id }}">{{ $attribute->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">قیمت <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="addPrice" min="0" class="ap-input !py-2" placeholder="تومان">
                    @error('addPrice') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">تخفیف (درصد)</label>
                    <input type="number" wire:model="addDiscount" min="0" max="100" class="ap-input !py-2" placeholder="%">
                    @error('addDiscount') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">زمان انقضای تخفیف</label>
                    <input type="text" wire:model="addDiscountExpireAt" class="ap-input !py-2" placeholder="۱۴۰۴-۰۱-۰۱ ۰۰:۰۰:۰۰">
                    @error('addDiscountExpireAt') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">بیشترین تعداد مجاز در هر سفارش</label>
                    <input type="number" wire:model="addCartMax" min="1" class="ap-input !py-2">
                    @error('addCartMax') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">کمترین تعداد مجاز در هر سفارش</label>
                    <input type="number" wire:model="addCartMin" min="1" class="ap-input !py-2">
                    @error('addCartMin') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">موجودی انبار <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="addStock" min="0" class="ap-input !py-2">
                    @error('addStock') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت <span class="text-red-500">*</span></label>
                    <select wire:model="addPublished" class="ap-input !py-2">
                        <option value="1">نمایش</option>
                        <option value="0">عدم نمایش</option>
                    </select>
                    @error('addPublished') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                @if ($this->isMultiVendor())
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">فروشنده</label>
                        <select wire:model="addSellerId" class="ap-input !py-2">
                            <option value="">انتخاب کنید</option>
                            @foreach ($this->sellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->label }}</option>
                            @endforeach
                        </select>
                        @error('addSellerId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex gap-2.5 sm:col-span-2 lg:col-span-3">
                    <button type="submit" wire:loading.attr="disabled" wire:target="storeVariation" class="ap-btn flex-1 !py-2.5">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        افزودن تنوع
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1 !py-2.5">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال ویرایش تنوع (پورت getVariationData + updateVariation) --}}
    <div x-data="{ open: @entangle('editingPriceId').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="flex items-center gap-2 text-base font-bold">
                <x-admin-panel::icon name="pencil" class="size-5 text-amber-600 dark:text-amber-400" />
                ویرایش تنوع
            </h3>

            <form wire:submit="updateVariation" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="mb-1.5 block text-xs font-semibold">انبار <span class="text-red-500">*</span></label>
                    <select wire:model="editWarehouse" class="ap-input !py-2">
                        <option value="">انتخاب انبار</option>
                        @foreach ($this->warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }}) @if ($wh->type == 'main') - انبار اصلی @elseif ($wh->type == 'seller') - انبار فروشنده @else - انبار موقت @endif</option>
                        @endforeach
                    </select>
                    @error('editWarehouse') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                @foreach ($this->attributeGroups as $group)
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">{{ $group->name }}</label>
                        <select wire:model="editAttributes.{{ $group->id }}" class="ap-input !py-2">
                            <option value="">انتخاب کنید</option>
                            @foreach ($group->get_attributes as $attribute)
                                <option value="{{ $attribute->id }}">{{ $attribute->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">قیمت <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="editPrice" min="0" class="ap-input !py-2">
                    @error('editPrice') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">تخفیف (درصد)</label>
                    <input type="number" wire:model="editDiscount" min="0" max="100" class="ap-input !py-2">
                    @error('editDiscount') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">زمان انقضای تخفیف</label>
                    <input type="text" wire:model="editDiscountExpireAt" class="ap-input !py-2" placeholder="۱۴۰۴-۰۱-۰۱ ۰۰:۰۰:۰۰">
                    @error('editDiscountExpireAt') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">بیشترین تعداد مجاز در هر سفارش</label>
                    <input type="number" wire:model="editCartMax" min="1" class="ap-input !py-2">
                    @error('editCartMax') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">کمترین تعداد مجاز در هر سفارش</label>
                    <input type="number" wire:model="editCartMin" min="1" class="ap-input !py-2">
                    @error('editCartMin') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">موجودی انبار <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="editStock" min="0" class="ap-input !py-2">
                    @error('editStock') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت <span class="text-red-500">*</span></label>
                    <select wire:model="editPublished" class="ap-input !py-2">
                        <option value="1">نمایش</option>
                        <option value="0">عدم نمایش</option>
                    </select>
                    @error('editPublished') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                @if ($this->isMultiVendor())
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">فروشنده</label>
                        <select wire:model="editSellerId" class="ap-input !py-2">
                            <option value="">انتخاب کنید</option>
                            @foreach ($this->sellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->label }}</option>
                            @endforeach
                        </select>
                        @error('editSellerId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex gap-2.5 sm:col-span-2 lg:col-span-3">
                    <button type="submit" wire:loading.attr="disabled" wire:target="updateVariation" class="ap-btn flex-1 !py-2.5">
                        <x-admin-panel::icon name="check" class="size-4" />
                        ذخیره تغییرات
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1 !py-2.5">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال تایید حذف تنوع --}}
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
            <h3 class="text-base font-bold">حذف تنوع</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                کل موجودی این تنوع از انبار خارج و ردیف تنوع حذف می‌شود. اگر تنوع سفارش فعال داشته باشد حذف انجام نمی‌شود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="destroyVariation" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="storeVariation, updateVariation, destroyVariation, loadVariationData, searchProduct"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
