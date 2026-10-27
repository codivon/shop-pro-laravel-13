<div>
    {{-- ================================================================
         فاز ۱۰۸ — «جستجو یا درج محصول» (پورت sellers-old/panel/products/find.blade.php)
         جستجوی محصولات منتشرشدهٔ سایت + «شما هم بفروشید» (لینک تنوع‌ها مثل قدیم)
         ================================================================ --}}

    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
                <x-admin-panel::icon name="package-search" class="size-5 text-brand-600 dark:text-brand-400" />
                جستجو یا درج محصول
            </h1>
            <p class="mt-1 max-w-xl text-xs text-ink-500 dark:text-night-ink-2">محصولی که قصد فروش آن را دارید، جستجو کنید. در غیر این‌صورت از "ایجاد کالای جدید" اقدام به درج کالای خود کنید</p>
        </div>
        <a href="{{ $createUrl }}" wire:navigate
           class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-4 text-xs font-bold">
            <x-admin-panel::icon name="plus" class="size-4" />
            ایجاد کالای جدید
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
        {{-- ================= ستون فیلترها (همان کلیدهای فرم قدیمی) ================= --}}
        <aside class="ap-card h-fit p-4 lg:col-span-1">
            <h2 class="mb-3 flex items-center gap-1.5 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4 text-ink-400 dark:text-night-ink-3" />
                فیلترها
            </h2>
            <div class="space-y-4">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">عنوان</span>
                    <input type="text" wire:model.live.debounce.400ms="title" placeholder="جستجوی عنوان محصول" class="ap-input w-full">
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">دسته بندی ها</span>
                    <select multiple wire:model="category_id" size="6" class="ap-input h-auto w-full min-h-11">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->title }}</option>
                        @endforeach
                    </select>
                </label>

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

                <button type="button" wire:click="resetFilters"
                        class="inline-flex min-h-11 items-center gap-1 text-[11px] font-bold text-ink-500 hover:text-red-500 dark:text-night-ink-2">
                    <x-admin-panel::icon name="rotate-ccw" class="size-3.5" />
                    حذف فیلترها
                </button>
            </div>
        </aside>

        {{-- ================= جدول نتایج ================= --}}
        <section class="ap-card overflow-hidden lg:col-span-3">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="text-sm font-bold">جستجو یا درج محصول</h2>
                <span class="fa-digits text-[11px] text-ink-400 dark:text-night-ink-3">
                    تعداد نتایج: <b>{{ number_format($products->total()) }}</b> مورد
                </span>
            </header>

            @if($rows->count())
                <div class="overflow-x-auto sp-scroll">
                    <table class="w-full min-w-[720px] text-right text-xs">
                        <thead>
                            <tr class="border-b border-line bg-surface-2 text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                                <th class="px-3 py-3 font-bold">تصویر</th>
                                <th class="px-3 py-3 font-bold">عنوان محصول</th>
                                <th class="px-3 py-3 font-bold">گروه</th>
                                <th class="px-3 py-3 text-center font-bold">تعداد تنوع</th>
                                <th class="px-3 py-3 font-bold">کمترین قیمت</th>
                                <th class="px-3 py-3 text-center font-bold">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr wire:key="find-row-{{ $row->id }}"
                                    class="border-b border-line/70 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/70 dark:hover:bg-night-2">
                                    <td class="px-3 py-3">
                                        @if($row->frontUrl)
                                            <a href="{{ $row->frontUrl }}" target="_blank" rel="noopener">
                                                <img src="{{ $row->image }}" alt="{{ $row->title }}" loading="lazy"
                                                     class="size-12 rounded-lg border border-line object-cover dark:border-night-line">
                                            </a>
                                        @else
                                            <img src="{{ $row->image }}" alt="{{ $row->title }}" loading="lazy"
                                                 class="size-12 rounded-lg border border-line object-cover dark:border-night-line">
                                        @endif
                                    </td>
                                    <td class="max-w-[260px] px-3 py-3">
                                        @if($row->frontUrl)
                                            <a href="{{ $row->frontUrl }}" target="_blank" rel="noopener"
                                               class="block truncate font-bold text-brand-600 hover:underline dark:text-brand-400">{{ $row->title }}</a>
                                        @else
                                            <span class="block truncate font-bold text-ink-900 dark:text-night-ink">{{ $row->title }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-ink-500 dark:text-night-ink-2">{{ $row->category?->title }}</td>
                                    <td class="fa-digits px-3 py-3 text-center font-bold text-ink-600 dark:text-night-ink-2">{{ $row->variant_count }}</td>
                                    <td class="px-3 py-3">
                                        @if($row->addableToCart)
                                            <span class="fa-digits block font-bold text-red-500">{{ $row->price_discount }}</span>
                                            <span class="block text-[11px] text-ink-400 dark:text-night-ink-3">تومان</span>
                                        @else
                                            <span class="text-ink-400 dark:text-night-ink-3">ناموجود</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        @if($row->isSeller)
                                            <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">فروشنده هستید</span>
                                        @else
                                            <a href="{{ $row->variantUrl }}" wire:navigate
                                               class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-[11px] font-bold">
                                                <x-admin-panel::icon name="plus" class="size-3.5" />
                                                شما هم بفروشید
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-4 py-12 text-center text-sm text-ink-500 dark:text-night-ink-2">
                    <x-admin-panel::icon name="package-search" class="mx-auto mb-2 size-8 text-ink-300 dark:text-night-ink-3" />
                    محصولی یافت نشد — می‌توانید کالای جدیدی درج کنید.
                </div>
            @endif

            <footer class="border-t border-line px-4 py-3 dark:border-night-line">
                {{ $products->links() }}
            </footer>
        </section>
    </div>
</div>
