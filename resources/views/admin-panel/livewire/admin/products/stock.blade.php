{{-- فاز ۱۱۹ — «موجودی و تنوع‌ها» پنل مدیریت
     فاز ۱۲۰ — بازطراحی کامل (درخواست کاربر):
     • کارت‌های آماری زندهٔ وابسته به فیلترها (کلیک = اعمال فیلتر)
     • ۴ نمودار ECharts: مالکیت تنوع‌ها، وضعیت موجودی، پر موجودی‌ترین محصولات،
       فروشندگان با بیشترین تنوع
     • فیلترهای بیشتر: مالکیت تنوع (سایت/فروشنده) + فروشندهٔ خاص + وضعیت موجودی +
       وضعیت نمایش + انبار + «فیلتر تنوع مشتری/فروشنده»
     • فیلتر مدرن دسته‌بندی (پیکر درختی هم‌تجربه با پنل فروشنده) --}}
<div class="mx-auto max-w-7xl space-y-5">

    @php
        $activeFilters = ($this->title !== '' ? 1 : 0)
            + (count($this->categoryId) ? 1 : 0)
            + ($this->owner !== 'all' ? 1 : 0)
            + ($this->sellerId > 0 ? 1 : 0)
            + ($this->stockStatus !== 'all' ? 1 : 0)
            + ($this->published !== 'all' ? 1 : 0)
            + ($this->warehouseId > 0 ? 1 : 0);
    @endphp

    {{-- ================= سربرگ ================= --}}
    <div class="ap-card p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="boxes" class="size-5" />
                </span>
                <div>
                    <h1 class="text-sm font-extrabold">موجودی و تنوع‌ها</h1>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                        موجودی کل هر محصول به‌همراه ریز تک‌تک تنوع‌ها، تنوع‌های فروشندگان و نمای تحلیلی
                    </p>
                </div>
            </div>

            <span class="ap-badge gap-1.5 bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="sliders-horizontal" class="size-3.5" />
                @if ($activeFilters)
                    <span class="fa-digits">{{ \App\Livewire\Admin\Dashboard::fa($activeFilters) }}</span> فیلتر فعال
                @else
                    بدون فیلتر
                @endif
            </span>
        </div>
    </div>

    {{-- ================= کارت‌های آماری (کلیک = فیلتر) ================= --}}
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        {{-- موجودی کل انبار --}}
        <div class="rounded-2xl border border-line bg-surface p-4 dark:border-night-line dark:bg-night-2">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">موجودی کل انبار</p>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="archive" class="size-4" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-xl font-extrabold text-ink dark:text-night-ink" title="جمع موجودی تنوع‌های منطبق با فیلترهای فعلی">{{ number_format($this->stats['total_stock']) }}</p>
            <p class="mt-1 text-[10px] text-ink-3 dark:text-night-ink-3">عدد کالا</p>
        </div>

        {{-- تعداد کل تنوع‌ها --}}
        <div class="rounded-2xl border border-line bg-surface p-4 dark:border-night-line dark:bg-night-2">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">تنوع‌های منطبق</p>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="layers" class="size-4" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-xl font-extrabold text-ink dark:text-night-ink">{{ number_format($this->stats['total_variants']) }}</p>
            <p class="mt-1 text-[10px] text-ink-3 dark:text-night-ink-3">قیمت/تنوع ثبت‌شده</p>
        </div>

        {{-- تنوع‌های فروش خودِ سایت (کلیک = فیلتر) --}}
        <button type="button" wire:click="toggleOwner('site')" aria-pressed="{{ $this->owner === 'site' ? 'true' : 'false' }}"
                class="rounded-2xl border p-4 text-start transition-all {{ $this->owner === 'site' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-500/25 dark:border-brand-500/50 dark:bg-brand-500/10' : 'border-line bg-surface hover:border-brand-300 dark:border-night-line dark:bg-night-2 dark:hover:border-brand-500/40' }}">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">تنوع‌های خود سایت</p>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="package" class="size-4" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-xl font-extrabold text-ink dark:text-night-ink">{{ number_format($this->stats['site_variants']) }}</p>
            <p class="mt-1 text-[10px] {{ $this->owner === 'site' ? 'font-bold text-brand-600 dark:text-brand-400' : 'text-ink-3 dark:text-night-ink-3' }}">{{ $this->owner === 'site' ? 'در حال نمایش — برای حذف کلیک کنید' : 'برای فیلتر کلیک کنید' }}</p>
        </button>

        {{-- تنوع‌های فروشندگان / مشتریان بازار (کلیک = فیلتر) --}}
        <button type="button" wire:click="toggleOwner('seller')" aria-pressed="{{ $this->owner === 'seller' ? 'true' : 'false' }}"
                class="rounded-2xl border p-4 text-start transition-all {{ $this->owner === 'seller' ? 'border-sky-400 bg-sky-50/60 ring-2 ring-sky-500/25 dark:border-sky-500/50 dark:bg-sky-500/10' : 'border-line bg-surface hover:border-sky-300 dark:border-night-line dark:bg-night-2 dark:hover:border-sky-500/40' }}">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">تنوع‌های فروشندگان</p>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                    <x-admin-panel::icon name="store" class="size-4" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-xl font-extrabold text-sky-600 dark:text-sky-400">{{ number_format($this->stats['seller_variants']) }}</p>
            <p class="mt-1 text-[10px] {{ $this->owner === 'seller' ? 'font-bold text-sky-600 dark:text-sky-400' : 'text-ink-3 dark:text-night-ink-3' }}">{{ $this->owner === 'seller' ? 'در حال نمایش — برای حذف کلیک کنید' : 'تنوع مشتریان بازار — برای فیلتر کلیک کنید' }}</p>
        </button>

        {{-- موجود --}}
        <button type="button" wire:click="toggleStock('in')" aria-pressed="{{ $this->stockStatus === 'in' ? 'true' : 'false' }}"
                class="rounded-2xl border p-4 text-start transition-all {{ $this->stockStatus === 'in' ? 'border-emerald-400 bg-emerald-50/60 ring-2 ring-emerald-500/25 dark:border-emerald-500/50 dark:bg-emerald-500/10' : 'border-line bg-surface hover:border-emerald-300 dark:border-night-line dark:bg-night-2 dark:hover:border-emerald-500/40' }}">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">موجود</p>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                    <x-admin-panel::icon name="check" class="size-4" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($this->stats['in_stock']) }}</p>
            <p class="mt-1 text-[10px] text-ink-3 dark:text-night-ink-3">بیش از <span class="fa-digits">{{ \App\Livewire\Admin\Dashboard::fa(5) }}</span> عدد</p>
        </button>

        {{-- کم‌موجود --}}
        <button type="button" wire:click="toggleStock('low')" aria-pressed="{{ $this->stockStatus === 'low' ? 'true' : 'false' }}"
                class="rounded-2xl border p-4 text-start transition-all {{ $this->stockStatus === 'low' ? 'border-amber-400 bg-amber-50/60 ring-2 ring-amber-500/25 dark:border-amber-500/50 dark:bg-amber-500/10' : 'border-line bg-surface hover:border-amber-300 dark:border-night-line dark:bg-night-2 dark:hover:border-amber-500/40' }}">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">کم‌موجود</p>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="alert-triangle" class="size-4" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-xl font-extrabold text-amber-600 dark:text-amber-400">{{ number_format($this->stats['low_stock']) }}</p>
            <p class="mt-1 text-[10px] text-ink-3 dark:text-night-ink-3">۱ تا <span class="fa-digits">{{ \App\Livewire\Admin\Dashboard::fa(5) }}</span> عدد</p>
        </button>

        {{-- ناموجود --}}
        <button type="button" wire:click="toggleStock('out')" aria-pressed="{{ $this->stockStatus === 'out' ? 'true' : 'false' }}"
                class="rounded-2xl border p-4 text-start transition-all {{ $this->stockStatus === 'out' ? 'border-red-400 bg-red-50/60 ring-2 ring-red-500/25 dark:border-red-500/50 dark:bg-red-500/10' : 'border-line bg-surface hover:border-red-300 dark:border-night-line dark:bg-night-2 dark:hover:border-red-500/40' }}">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">ناموجود</p>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-500 dark:bg-red-500/12 dark:text-red-400">
                    <x-admin-panel::icon name="x" class="size-4" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-xl font-extrabold text-red-500 dark:text-red-400">{{ number_format($this->stats['out_of_stock']) }}</p>
            <p class="mt-1 text-[10px] text-ink-3 dark:text-night-ink-3">موجودی صفر</p>
        </button>

        {{-- محصولات --}}
        <div class="rounded-2xl border border-line bg-surface p-4 dark:border-night-line dark:bg-night-2">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">محصولات</p>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="package-open" class="size-4" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-xl font-extrabold text-ink dark:text-night-ink">{{ number_format($this->stats['products_count']) }}</p>
            <p class="mt-1 text-[10px] text-ink-3 dark:text-night-ink-3">در نتیجهٔ فعلی</p>
        </div>
    </div>

    {{-- ================= نمای تحلیلی (نمودارها) ================= --}}
    <div class="ap-card p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="bar-chart-3" class="size-4.5 text-brand-600 dark:text-brand-400" />
                نمای تحلیلی موجودی
            </h2>
            <span class="text-[11px] text-ink-3 dark:text-night-ink-3">همهٔ نمودارها بر اساس فیلترهای فعلی به‌روز می‌شوند</span>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- نمودار مالکیت تنوع‌ها --}}
            <div class="rounded-2xl border border-line p-3.5 dark:border-night-line">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="flex items-center gap-1.5 text-xs font-bold">
                        <x-admin-panel::icon name="chart-pie" class="size-4 text-brand-600 dark:text-brand-400" />
                        مالکیت تنوع‌ها
                    </h3>
                    <span class="ap-badge bg-brand-50 text-[10px] text-brand-700 dark:bg-brand-500/12 dark:text-brand-300">سایت vs فروشندگان</span>
                </div>
                <div data-chart="stock-owner-chart" data-options="{{ json_encode($this->chartOption('stock-owner-chart')) }}" class="h-56 w-full"></div>
            </div>

            {{-- نمودار وضعیت موجودی --}}
            <div class="rounded-2xl border border-line p-3.5 dark:border-night-line">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="flex items-center gap-1.5 text-xs font-bold">
                        <x-admin-panel::icon name="chart-pie" class="size-4 text-amber-600 dark:text-amber-400" />
                        وضعیت موجودی تنوع‌ها
                    </h3>
                    <span class="ap-badge bg-amber-50 text-[10px] text-amber-700 dark:bg-amber-500/12 dark:text-amber-300">موجود / کم / ناموجود</span>
                </div>
                <div data-chart="stock-status-chart" data-options="{{ json_encode($this->chartOption('stock-status-chart')) }}" class="h-56 w-full"></div>
            </div>

            {{-- پر موجودی‌ترین محصولات --}}
            <div class="rounded-2xl border border-line p-3.5 dark:border-night-line">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="flex items-center gap-1.5 text-xs font-bold">
                        <x-admin-panel::icon name="boxes" class="size-4 text-brand-600 dark:text-brand-400" />
                        پر موجودی‌ترین محصولات
                    </h3>
                    <span class="text-[10px] text-ink-3 dark:text-night-ink-3">۸ مورد برتر</span>
                </div>
                @if (count($this->chartData['topProducts']))
                    <div data-chart="stock-top-products-chart" data-options="{{ json_encode($this->chartOption('stock-top-products-chart')) }}" class="h-64 w-full"></div>
                @else
                    <div class="flex h-64 flex-col items-center justify-center gap-2 text-ink-3 dark:text-night-ink-3">
                        <x-admin-panel::icon name="package-search" class="size-8 opacity-40" />
                        <p class="text-xs font-medium">تنوع منطبقی با فیلترهای فعلی یافت نشد</p>
                    </div>
                @endif
            </div>

            {{-- فروشندگان با بیشترین تنوع --}}
            <div class="rounded-2xl border border-line p-3.5 dark:border-night-line">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="flex items-center gap-1.5 text-xs font-bold">
                        <x-admin-panel::icon name="store" class="size-4 text-sky-600 dark:text-sky-400" />
                        فروشندگان با بیشترین تنوع
                    </h3>
                    <span class="text-[10px] text-ink-3 dark:text-night-ink-3">۸ مورد برتر</span>
                </div>
                @if ($this->owner !== 'site' && count($this->chartData['topSellers']))
                    <div data-chart="stock-top-sellers-chart" data-options="{{ json_encode($this->chartOption('stock-top-sellers-chart')) }}" class="h-64 w-full"></div>
                @elseif ($this->owner === 'site')
                    <div class="flex h-64 flex-col items-center justify-center gap-2 text-ink-3 dark:text-night-ink-3">
                        <x-admin-panel::icon name="store" class="size-8 opacity-40" />
                        <p class="text-xs font-medium">فیلتر «فروش خودِ سایت» فعال است</p>
                        <button type="button" wire:click="toggleOwner('site')" class="ap-btn-outline !py-1.5 text-[11px]">
                            نمایش همهٔ تنوع‌ها
                        </button>
                    </div>
                @else
                    <div class="flex h-64 flex-col items-center justify-center gap-2 text-ink-3 dark:text-night-ink-3">
                        <x-admin-panel::icon name="store" class="size-8 opacity-40" />
                        <p class="text-xs font-medium">در نتایج فعلی تنوع فروشنده‌ای وجود ندارد</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ================= فیلترها ================= --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: {{ $activeFilters ? 'true' : 'false' }} }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <button type="button" class="flex items-center gap-2 text-start" x-on:click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                <span class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    فیلترها
                    @if ($activeFilters)
                        <span class="fa-digits flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1.5 text-[10px] font-bold text-white">{{ \App\Livewire\Admin\Dashboard::fa($activeFilters) }}</span>
                    @endif
                </span>
                <span class="ap-chevron" :class="open ? 'is-open' : ''">
                    <x-admin-panel::icon name="chevron-left" class="size-4 text-ink-3" />
                </span>
            </button>

            <button type="button" wire:click="resetFilters"
                    class="ap-btn-ghost !py-2 text-[11px] {{ $activeFilters ? '' : 'pointer-events-none opacity-40' }}">
                <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                بازنشانی همهٔ فیلترها
            </button>
        </div>

        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="pt-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">

                {{-- عنوان --}}
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-ink-2 dark:text-night-ink-2">عنوان محصول</span>
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.400ms="title" placeholder="جستجوی عنوان…" autocomplete="off"
                               class="ap-input pe-9" aria-label="جستجوی عنوان محصول">
                        <span class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-ink-3">
                            <x-admin-panel::icon name="search" class="size-4" />
                        </span>
                    </div>
                </label>

                {{-- دسته‌بندی‌ها (پیکر مدرن) --}}
                <div class="relative">
                    @include('admin-panel::partials.category-filter', [
                        'wireProp' => 'categoryId',
                        'categoriesTree' => $this->categoryFilter['tree'],
                        'categoriesFlatJs' => $this->categoryFilter['flatJs'],
                        'categoryTitles' => $this->categoryFilter['titles'],
                    ])
                </div>

                {{-- فروشنده --}}
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-ink-2 dark:text-night-ink-2">فروشنده</span>
                    <select wire:model.live="sellerId" class="ap-input" aria-label="فیلتر فروشنده">
                        <option value="0">همهٔ فروشندگان</option>
                        @foreach ($this->sellers as $seller)
                            <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                        @endforeach
                    </select>
                </label>

                {{-- مالکیت تنوع (فیلتر تنوع مشتری/فروشنده) --}}
                <div>
                    <span class="mb-1.5 block text-xs font-semibold text-ink-2 dark:text-night-ink-2">مالکیت تنوع</span>
                    <div class="flex rounded-xl bg-surface-2 p-1 dark:bg-night-3" role="group" aria-label="مالکیت تنوع">
                        <button type="button" wire:click="$set('owner', 'all')"
                                class="flex-1 rounded-lg px-2 py-2 text-[11px] font-bold transition-all {{ $this->owner === 'all' ? 'bg-surface text-brand-700 shadow-sm dark:bg-night-2 dark:text-brand-400' : 'text-ink-3 transition-colors hover:text-ink-2 dark:text-night-ink-3 dark:hover:text-night-ink-2' }}">
                            همه
                        </button>
                        <button type="button" wire:click="$set('owner', 'site')"
                                class="flex-1 rounded-lg px-2 py-2 text-[11px] font-bold transition-all {{ $this->owner === 'site' ? 'bg-surface text-brand-700 shadow-sm dark:bg-night-2 dark:text-brand-400' : 'text-ink-3 transition-colors hover:text-ink-2 dark:text-night-ink-3 dark:hover:text-night-ink-2' }}">
                            فروش خودِ سایت
                        </button>
                        <button type="button" wire:click="$set('owner', 'seller')"
                                class="flex-1 rounded-lg px-2 py-2 text-[11px] font-bold transition-all {{ $this->owner === 'seller' ? 'bg-surface text-sky-700 shadow-sm dark:bg-night-2 dark:text-sky-400' : 'text-ink-3 transition-colors hover:text-ink-2 dark:text-night-ink-3 dark:hover:text-night-ink-2' }}">
                            تنوع فروشندگان
                        </button>
                    </div>
                </div>

                {{-- وضعیت موجودی --}}
                <div>
                    <span class="mb-1.5 block text-xs font-semibold text-ink-2 dark:text-night-ink-2">وضعیت موجودی</span>
                    <div class="flex rounded-xl bg-surface-2 p-1 dark:bg-night-3" role="group" aria-label="وضعیت موجودی">
                        <button type="button" wire:click="$set('stockStatus', 'all')"
                                class="flex-1 rounded-lg px-2 py-2 text-[11px] font-bold transition-all {{ $this->stockStatus === 'all' ? 'bg-surface text-brand-700 shadow-sm dark:bg-night-2 dark:text-brand-400' : 'text-ink-3 transition-colors hover:text-ink-2 dark:text-night-ink-3 dark:hover:text-night-ink-2' }}">
                            همه
                        </button>
                        <button type="button" wire:click="$set('stockStatus', 'in')"
                                class="flex-1 rounded-lg px-2 py-2 text-[11px] font-bold transition-all {{ $this->stockStatus === 'in' ? 'bg-surface text-emerald-700 shadow-sm dark:bg-night-2 dark:text-emerald-400' : 'text-ink-3 transition-colors hover:text-ink-2 dark:text-night-ink-3 dark:hover:text-night-ink-2' }}">
                            موجود
                        </button>
                        <button type="button" wire:click="$set('stockStatus', 'low')"
                                class="flex-1 rounded-lg px-2 py-2 text-[11px] font-bold transition-all {{ $this->stockStatus === 'low' ? 'bg-surface text-amber-700 shadow-sm dark:bg-night-2 dark:text-amber-400' : 'text-ink-3 transition-colors hover:text-ink-2 dark:text-night-ink-3 dark:hover:text-night-ink-2' }}">
                            کم‌موجود
                        </button>
                        <button type="button" wire:click="$set('stockStatus', 'out')"
                                class="flex-1 rounded-lg px-2 py-2 text-[11px] font-bold transition-all {{ $this->stockStatus === 'out' ? 'bg-surface text-red-700 shadow-sm dark:bg-night-2 dark:text-red-400' : 'text-ink-3 transition-colors hover:text-ink-2 dark:text-night-ink-3 dark:hover:text-night-ink-2' }}">
                            ناموجود
                        </button>
                    </div>
                </div>

                {{-- وضعیت نمایش + انبار + تعداد در صفحه --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 xl:grid-cols-1">
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold text-ink-2 dark:text-night-ink-2">وضعیت نمایش</span>
                        <select wire:model.live="published" class="ap-input" aria-label="وضعیت نمایش تنوع">
                            <option value="all">همه</option>
                            <option value="yes">در حال نمایش</option>
                            <option value="no">عدم نمایش</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold text-ink-2 dark:text-night-ink-2">انبار</span>
                        <select wire:model.live="warehouseId" class="ap-input" aria-label="فیلتر انبار">
                            <option value="0">همهٔ انبارها</option>
                            @foreach ($this->warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold text-ink-2 dark:text-night-ink-2">تعداد در صفحه</span>
                        <select wire:model.live="perPage" class="ap-input" aria-label="تعداد در صفحه">
                            <option value="10">۱۰</option>
                            <option value="20">۲۰</option>
                            <option value="50">۵۰</option>
                        </select>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= لیست محصولات ================= --}}
    @if ($this->products->count())
        <div class="space-y-4">
            @foreach ($this->products as $product)
                @php
                    $matching = $product->prices->filter(fn ($price) => $this->variantMatches($price));
                    $matchingStock = (int) $matching->sum('stock');
                    $matchingSeller = $matching->whereNotNull('seller_id')->count();
                    $hasHidden = $matching->count() !== $product->prices->count();
                @endphp
                <div wire:key="stock-product-{{ $product->id }}" class="ap-card overflow-hidden"
                     x-data="{ open: false }">
                    <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->title }}"
                             class="size-12 shrink-0 rounded-xl border border-line object-cover dark:border-night-line">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-bold">{{ $product->title }}</p>
                            <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                                شناسه: {{ number_format($product->id) }}
                                @if ($product->category)
                                    — {{ $product->category->title }}
                                @endif
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="ap-badge {{ $matchingStock > 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300' }}"
                                  title="موجودی تنوع‌های نمایش‌داده‌شده">
                                موجودی: <b class="fa-digits">{{ number_format($matchingStock) }}</b>
                            </span>
                            <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2"
                                  title="{{ $hasHidden ? 'تعداد تنوع‌های منطبق از کل تنوع‌های محصول' : 'تعداد کل تنوع‌های این محصول' }}">
                                تنوع‌ها: <b class="fa-digits">{{ number_format($matching->count()) }}</b>
                                @if ($hasHidden)
                                    <span class="fa-digits text-[10px] opacity-70">از {{ number_format($product->prices->count()) }}</span>
                                @endif
                            </span>
                            @if ($matchingSeller)
                                <span class="ap-badge bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300"
                                      title="تعداد تنوع‌های متعلق به فروشندگان">
                                    فروشندگان: <b class="fa-digits">{{ number_format($matchingSeller) }}</b>
                                </span>
                            @endif
                            <button type="button" x-on:click="open = !open"
                                    class="ap-btn-outline !py-1.5 text-[11px]">
                                <span x-text="open ? 'بستن تنوع‌ها' : 'نمایش تنوع‌ها'"></span>
                                <x-admin-panel::icon name="chevron-down" class="size-3.5 transition-transform"
                                                     x-bind:class="open ? 'rotate-180' : ''" />
                            </button>
                        </div>
                    </div>

                    {{-- ریز تنوع‌ها (فقط منطبق با فیلتر) --}}
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                         class="border-t border-line dark:border-night-line">
                        @if ($matching->count())
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-3xl text-start text-[13px]">
                                    <thead>
                                        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                                            <th class="px-4 py-2.5 text-start font-bold">تنوع</th>
                                            <th class="w-44 px-4 py-2.5 text-start font-bold">مالک تنوع</th>
                                            <th class="w-40 px-4 py-2.5 text-start font-bold">قیمت نهایی</th>
                                            <th class="w-28 px-4 py-2.5 text-start font-bold">موجودی</th>
                                            <th class="w-36 px-4 py-2.5 text-start font-bold">انبار</th>
                                            <th class="w-24 px-4 py-2.5 text-start font-bold">وضعیت</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($matching as $price)
                                            <tr wire:key="stock-variant-{{ $price->id }}"
                                                class="border-b border-line/60 last:border-0 dark:border-night-line/60">
                                                <td class="px-4 py-2.5">
                                                    <span class="fa-digits me-1.5 inline-flex size-5 items-center justify-center rounded-md bg-surface-2 text-[10px] font-bold text-ink-3 dark:bg-night-3 dark:text-night-ink-3">{{ \App\Livewire\Admin\Dashboard::fa($loop->iteration) }}</span>
                                                    {{ $price->getAttributesName() ?: 'عادی' }}
                                                </td>
                                                <td class="px-4 py-2.5">
                                                    @if ($price->seller_id)
                                                        <span class="ap-badge bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300"
                                                              title="این تنوع متعلق به فروشنده است">
                                                            <x-admin-panel::icon name="store" class="size-3" />
                                                            {{ $price->seller?->seller_info?->business_name
                                                                ?: trim(($price->seller?->seller_info?->first_name ?? '') . ' ' . ($price->seller?->seller_info?->last_name ?? ''))
                                                                ?: ('فروشنده #' . $price->seller_id) }}
                                                        </span>
                                                    @else
                                                        <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                                            فروش خود سایت
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="fa-digits px-4 py-2.5 font-bold">
                                                    @if ($price->discount_price && $price->hasDiscount())
                                                        <span class="text-red-500">{{ number_format($price->discount_price) }}</span>
                                                        <span class="text-[10px] text-ink-3 line-through">{{ number_format($price->regular_price) }}</span>
                                                    @else
                                                        {{ number_format($price->regular_price ?: $price->price) }}
                                                    @endif
                                                    <span class="text-[10px] font-normal text-ink-3">تومان</span>
                                                </td>
                                                <td class="fa-digits px-4 py-2.5">
                                                    @if ($price->stock > \App\Livewire\Admin\Products\StockIndex::LOW_STOCK)
                                                        <span class="inline-flex items-center gap-1 font-bold text-emerald-600 dark:text-emerald-400" title="موجودی مطلوب">
                                                            <x-admin-panel::icon name="check" class="size-3.5" />
                                                            {{ number_format($price->stock) }}
                                                        </span>
                                                    @elseif ($price->stock > 0)
                                                        <span class="inline-flex items-center gap-1 font-bold text-amber-600 dark:text-amber-400" title="کم‌موجود (۱ تا ۵ عدد)">
                                                            <x-admin-panel::icon name="alert-triangle" class="size-3.5" />
                                                            {{ number_format($price->stock) }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 font-bold text-red-500" title="ناموجود">
                                                            <x-admin-panel::icon name="x" class="size-3.5" />
                                                            {{ number_format($price->stock) }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2.5 text-ink-2 dark:text-night-ink-2">
                                                    {{ $price->warehouse?->name ?: '—' }}
                                                </td>
                                                <td class="px-4 py-2.5">
                                                    @if ($price->published)
                                                        <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">نمایش</span>
                                                    @else
                                                        <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">عدم نمایش</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="px-4 py-6 text-center text-xs text-ink-3 dark:text-night-ink-2">
                                هیچ تنوعی از این محصول با فیلترهای فعلی منطبق نیست.
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- صفحه‌بندی --}}
        <div class="ap-card px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    نمایش <span class="fa-digits">{{ number_format($this->products->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->products->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->products->total()) }}</span> محصول
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
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
    @else
        <div class="ap-card flex flex-col items-center gap-2 py-16 text-ink-3 dark:text-night-ink-2">
            <x-admin-panel::icon name="package-search" class="size-10 opacity-40" />
            <p class="text-sm font-medium">هیچ محصولی با فیلترهای فعلی یافت نشد!</p>
            @if ($activeFilters)
                <button type="button" wire:click="resetFilters" class="ap-btn-outline !py-1.5 text-[11px]">
                    <x-admin-panel::icon name="rotate-ccw" class="size-3.5" />
                    بازنشانی فیلترها
                </button>
            @endif
        </div>
    @endif
</div>

@script
<script>
    /* فاز ۱۲۰ — به‌روزرسانی نمودارها پس از تغییر فیلترها + ثبت نمودارهای تازه‌رندرشده */
    $wire.on('chart-update', (payload) => {
        const charts = (payload && payload.charts) ? payload.charts : {};

        Object.keys(charts).forEach((key) => {
            const el = document.querySelector('[data-chart="' + key + '"]');
            if (!el || !window.ApChart || !window.ApECharts) return;

            const options = window.ApChart.resolveFormatters(charts[key]);
            let chart = window.ApChart.instances[key];

            if (!chart || chart.getDom() !== el) {
                chart = window.ApChart.instances[key] = window.ApECharts.init(el);
            }

            chart.setOption(options, true);
            chart.resize();
        });

        // نمودارهای تازه‌افزوده‌شده (مثلاً پس از پر شدن دوبارهٔ دادهٔ فروشندگان)
        if (window.ApChart && window.ApChart.renderAll) {
            window.ApChart.renderAll();
        }
    });
</script>
@endscript
