<div>
    {{-- ================================================================
         فاز ۱۰۸ — «محصولات من» (پورت sellers-old/panel/products/index.blade.php)
         جدول Livewire + فیلترها با همان کلیدهای قدیمی (title/category_id/stock/special)
         ================================================================ --}}

    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
                <x-admin-panel::icon name="package" class="size-5 text-brand-600 dark:text-brand-400" />
                مدیریت محصولات
            </h1>
            <p class="mt-1 max-w-xl text-xs text-ink-500 dark:text-night-ink-2">محصولی که قصد فروش آن را دارید، جستجو کنید. در غیر این‌صورت از "ایجاد کالای جدید" اقدام به درج کالای خود کنید</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            {{-- فاز ۱۰۶-۶ — خروجی اکسل؛ گیت سوییچ seller_products_export (قانون طلایی) --}}
            @if($exportUrl)
                <a href="{{ $exportUrl }}"
                   class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                    <x-admin-panel::icon name="download" class="size-4" />
                    خروجی اکسل
                </a>
            @endif
            <a href="{{ $findUrl }}" wire:navigate
               class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                <x-admin-panel::icon name="package-search" class="size-4" />
                افزودن از محصولات سایت
            </a>
            <a href="{{ $createUrl }}" wire:navigate
               class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-4 text-xs font-bold">
                <x-admin-panel::icon name="plus" class="size-4" />
                محصول جدید
            </a>
        </div>
    </div>

    {{-- ================= کارت فیلترها (همان کلیدهای فرم قدیمی) ================= --}}
    <section class="ap-card mb-4 p-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">عنوان</span>
                <input type="text" wire:model.live.debounce.400ms="title" placeholder="جستجوی عنوان محصول" class="ap-input w-full">
            </label>

            {{-- فاز ۱۱۹ — فیلتر مدرن دسته‌بندی (جایگزین select multiple) --}}
            <div>
                @include('sellers.panel.partials.category-filter')
            </div>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">وضعیت موجودی</span>
                <select wire:model.live="stock" class="ap-input w-full">
                    <option value="all">همه</option>
                    <option value="available">موجود</option>
                    <option value="unavailable">ناموجود</option>
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">محصول ویژه</span>
                <select wire:model.live="special" class="ap-input w-full">
                    <option value="all">همه</option>
                    <option value="yes">بله</option>
                    <option value="no">خیر</option>
                </select>
            </label>
        </div>
        <div class="mt-3 flex items-center justify-between gap-2">
            <span class="fa-digits text-[11px] text-ink-400 dark:text-night-ink-3">
                تعداد نتایج: <b>{{ number_format($products->total()) }}</b> مورد
            </span>
            <button type="button" wire:click="resetFilters"
                    class="inline-flex min-h-11 items-center gap-1 rounded-lg px-2 text-[11px] font-bold text-ink-500 hover:text-red-500 dark:text-night-ink-2">
                <x-admin-panel::icon name="rotate-ccw" class="size-3.5" />
                حذف فیلترها
            </button>
        </div>
    </section>

    {{-- ================= نوار حذف گروهی (وقتی ردیفی انتخاب شده) ================= --}}
    @if(count($selected) > 0)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-red-200 bg-red-50 p-3 dark:border-red-500/20 dark:bg-red-500/10">
            <span class="fa-digits text-xs font-bold text-red-700 dark:text-red-300">
                <b>{{ count($selected) }}</b> محصول انتخاب شده است
            </span>
            <button type="button" wire:click="$set('confirmingBulkDelete', true)"
                    class="ap-btn inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-red-500 px-3 text-xs font-bold text-white hover:bg-red-600">
                <x-admin-panel::icon name="trash-2" class="size-4" />
                حذف گروهی
            </button>
        </div>
    @endif

    {{-- ================= جدول لیست محصولات ================= --}}
    <section class="ap-card overflow-hidden">
        <header class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="text-sm font-bold">لیست محصولات</h2>
        </header>

        @if($rows->count())
            <div class="overflow-x-auto sp-scroll">
                <table class="w-full min-w-[900px] text-right text-xs">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                            <th class="px-3 py-3 text-center font-bold">
                                <input type="checkbox" wire:model.live="selectAll" class="size-4 accent-brand-600" aria-label="انتخاب همه">
                            </th>
                            <th class="px-3 py-3 font-bold">ID</th>
                            <th class="px-3 py-3 font-bold">تصویر شاخص</th>
                            <th class="px-3 py-3 font-bold">عنوان محصول</th>
                            <th class="px-3 py-3 font-bold">دسته بندی</th>
                            <th class="px-3 py-3 text-center font-bold">تعداد تنوع</th>
                            <th class="px-3 py-3 font-bold">قیمت</th>
                            <th class="px-3 py-3 text-center font-bold">موجودی تنوع‌های من</th>
                            <th class="px-3 py-3 text-center font-bold">وضعیت انتشار</th>
                            <th class="px-3 py-3 font-bold">تاریخ ایجاد</th>
                            <th class="px-3 py-3 text-center font-bold">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr wire:key="product-row-{{ $row->id }}"
                                class="border-b border-line/70 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/70 dark:hover:bg-night-2">
                                <td class="px-3 py-3 text-center">
                                    <input type="checkbox" wire:model.live="selected" value="{{ $row->id }}" class="size-4 accent-brand-600" aria-label="انتخاب محصول {{ $row->title }}">
                                </td>
                                <td class="fa-digits px-3 py-3 text-ink-500 dark:text-night-ink-2">{{ $row->id }}</td>
                                <td class="px-3 py-3">
                                    <img src="{{ $row->image }}" alt="{{ $row->title }}" loading="lazy"
                                         class="size-10 rounded-lg border border-line object-cover dark:border-night-line">
                                </td>
                                <td class="max-w-[220px] px-3 py-3">
                                    <span class="block truncate font-bold text-ink-900 dark:text-night-ink">{{ $row->title }}</span>
                                    @if($row->brand)
                                        <span class="mt-0.5 block text-[11px] text-ink-400 dark:text-night-ink-3">{{ $row->brand->name }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-ink-500 dark:text-night-ink-2">{{ $row->category?->title }}</td>
                                <td class="px-3 py-3 text-center">
                                    <span class="ap-badge fa-digits bg-surface-2 text-ink-600 dark:bg-night-2 dark:text-night-ink-2">{{ $row->variant_count_seller }}</span>
                                </td>
                                <td class="px-3 py-3">
                                    @if($row->addableToCart)
                                        <span class="fa-digits block font-bold text-red-500">{{ $row->price_discount }}</span>
                                        <span class="fa-digits block text-[11px] text-ink-400 dark:text-night-ink-3">{{ $row->price }} تومان</span>
                                    @else
                                        <span class="text-ink-400 dark:text-night-ink-3">ناموجود</span>
                                    @endif
                                </td>
                                <td class="fa-digits px-3 py-3 text-center font-bold {{ $row->stock_count > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">{{ $row->stock_count }}</td>
                                <td class="px-3 py-3 text-center">
                                    @if($row->published)
                                        <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">منتشر شده</span>
                                    @else
                                        <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">پیش نویس</span>
                                    @endif
                                    @if($row->status == 'Accept')
                                        <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">تایید شده</span>
                                    @elseif($row->status == 'Waiting')
                                        <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">در انتظار تایید</span>
                                    @elseif($row->status == 'Reject')
                                        <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">تایید نشده</span>
                                    @endif
                                </td>
                                <td class="fa-digits px-3 py-3 text-ink-500 dark:text-night-ink-2">{{ $row->created_at }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        @if($row->frontUrl)
                                            <a href="{{ $row->frontUrl }}" target="_blank" rel="noopener" title="نمایش"
                                               class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg p-2 text-ink-500 hover:bg-surface-2 hover:text-brand-600 dark:text-night-ink-2 dark:hover:bg-night-2">
                                                <x-admin-panel::icon name="external-link" class="size-4" />
                                            </a>
                                        @endif
                                        @if($row->editable)
                                            <a href="{{ $row->editUrl }}" wire:navigate title="ویرایش"
                                               class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg p-2 text-ink-500 hover:bg-surface-2 hover:text-brand-600 dark:text-night-ink-2 dark:hover:bg-night-2">
                                                <x-admin-panel::icon name="pencil" class="size-4" />
                                            </a>
                                        @endif
                                        <a href="{{ $row->variantUrl }}" wire:navigate title="تنوع‌ها"
                                           class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg p-2 text-ink-500 hover:bg-surface-2 hover:text-brand-600 dark:text-night-ink-2 dark:hover:bg-night-2">
                                            <x-admin-panel::icon name="layers" class="size-4" />
                                        </a>
                                        <a href="{{ $row->copyUrl }}" wire:navigate title="کپی کردن"
                                           class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg p-2 text-ink-500 hover:bg-surface-2 hover:text-brand-600 dark:text-night-ink-2 dark:hover:bg-night-2">
                                            <x-admin-panel::icon name="copy" class="size-4" />
                                        </a>
                                        <button type="button" wire:click="confirmDelete({{ $row->id }})" title="حذف"
                                                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg p-2 text-ink-500 hover:bg-red-50 hover:text-red-500 dark:text-night-ink-2 dark:hover:bg-red-500/10">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-4 py-12 text-center text-sm text-ink-500 dark:text-night-ink-2">
                <x-admin-panel::icon name="package-search" class="mx-auto mb-2 size-8 text-ink-300 dark:text-night-ink-3" />
                محصولی یافت نشد.
            </div>
        @endif

        <footer class="border-t border-line px-4 py-3 dark:border-night-line">
            {{ $products->links() }}
        </footer>
    </section>

    {{-- ================= مودال تایید حذف تکی ================= --}}
    <div x-data="{ open: false }" x-effect="open = $wire.confirmingDelete !== null"
         x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="open = false; $wire.confirmingDelete = null" x-transition.opacity></div>
        <div class="ap-card relative z-10 w-full max-w-md" x-show="open" x-transition.scale.origin.center>
            <header class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold text-red-600 dark:text-red-400">
                    <x-admin-panel::icon name="triangle-alert" class="size-4" />
                    حذف محصول
                </h3>
                <button type="button" x-on:click="open = false; $wire.confirmingDelete = null" class="min-h-11 rounded p-1 text-ink-500 hover:text-red-500" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </header>
            <p class="p-4 text-xs leading-6 text-ink-600 dark:text-night-ink-2">
                آیا از حذف این محصول مطمئن هستید؟ تمام تنوع‌ها، تصاویر و اطلاعات مرتبط با آن حذف خواهد شد. این عملیات قابل بازگشت نیست.
            </p>
            <footer class="flex items-center justify-end gap-2 border-t border-line p-4 dark:border-night-line">
                <button type="button" x-on:click="open = false; $wire.confirmingDelete = null"
                        class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-4 text-xs font-bold">
                    انصراف
                </button>
                <button type="button" wire:click="deleteOne" wire:loading.attr="disabled"
                        class="ap-btn inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-red-500 px-4 text-xs font-bold text-white hover:bg-red-600 disabled:opacity-50">
                    <x-admin-panel::icon name="trash-2" class="size-4" />
                    حذف محصول
                </button>
            </footer>
        </div>
    </div>

    {{-- ================= مودال تایید حذف گروهی ================= --}}
    <div x-data="{ open: false }" x-effect="open = $wire.confirmingBulkDelete === true"
         x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="open = false; $wire.confirmingBulkDelete = false" x-transition.opacity></div>
        <div class="ap-card relative z-10 w-full max-w-md" x-show="open" x-transition.scale.origin.center>
            <header class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold text-red-600 dark:text-red-400">
                    <x-admin-panel::icon name="triangle-alert" class="size-4" />
                    حذف گروهی محصولات
                </h3>
                <button type="button" x-on:click="open = false; $wire.confirmingBulkDelete = false" class="min-h-11 rounded p-1 text-ink-500 hover:text-red-500" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </header>
            <p class="p-4 text-xs leading-6 text-ink-600 dark:text-night-ink-2">
                آیا از حذف محصولات انتخاب‌شده مطمئن هستید؟ این عملیات قابل بازگشت نیست.
            </p>
            <footer class="flex items-center justify-end gap-2 border-t border-line p-4 dark:border-night-line">
                <button type="button" x-on:click="open = false; $wire.confirmingBulkDelete = false"
                        class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-4 text-xs font-bold">
                    انصراف
                </button>
                <button type="button" wire:click="deleteSelected" wire:loading.attr="disabled"
                        class="ap-btn inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-red-500 px-4 text-xs font-bold text-white hover:bg-red-600 disabled:opacity-50">
                    <x-admin-panel::icon name="trash-2" class="size-4" />
                    حذف محصولات
                </button>
            </footer>
        </div>
    </div>
</div>
