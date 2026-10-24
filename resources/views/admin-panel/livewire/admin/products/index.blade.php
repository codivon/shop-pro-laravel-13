{{-- لیست محصولات — پنل جدید (فاز ۱) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                تعداد کل: <span class="fa-digits">{{ number_format($this->statistics['total']) }}</span>
            </span>
            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                منتشر شده: <span class="fa-digits">{{ number_format($this->statistics['published']) }}</span>
            </span>
            <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                پیش‌نویس: <span class="fa-digits">{{ number_format($this->statistics['unpublished']) }}</span>
            </span>
        </div>
        <a href="{{ route('np.products.create') }}" wire:navigate class="ap-btn-primary">
            <x-admin-panel::icon name="plus" class="size-4.5" />
            محصول جدید
        </a>
    </div>

    {{-- کارت فیلترها — بازطراحی مدرن (فاز ۲۱) --}}
    {{-- فاز ۲۶ — overflow-hidden حذف شد: پنل بازشوی انتخابگر دسته‌بندی (absolute) را
         کلیپ می‌کرد و منوی دسته‌بندی‌ها هرگز دیده نمی‌شد --}}
    <div class="ap-card" x-data="{ open: false }">

        {{-- نوار اصلی (همیشه دیده می‌شود) --}}
        <div class="flex flex-wrap items-center gap-2.5 p-4 sm:p-5">
            <div class="relative min-w-52 flex-1">
                <span class="pointer-events-none absolute inset-y-0 start-3.5 flex items-center text-ink-3">
                    <x-admin-panel::icon name="search" class="size-4" />
                </span>
                <input type="text" wire:model.live.debounce.400ms="title" placeholder="جستجو در عنوان محصول…"
                       class="ap-input !py-2.5 ps-10 text-[13px]">
            </div>

            <label class="flex items-center gap-2 text-xs font-semibold text-ink-3 dark:text-night-ink-2">
                نمایش
                <select wire:model.live="perPage" class="ap-input !w-auto !py-2.5 text-[13px]" aria-label="تعداد در صفحه">
                    <option value="15">۱۵</option>
                    <option value="30">۳۰</option>
                    <option value="50">۵۰</option>
                    <option value="100">۱۰۰</option>
                </select>
            </label>

            <button type="button" wire:click="applyFilters" class="ap-btn-ghost !py-2.5">
                <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                بازنشانی
            </button>

            <button type="button" x-on:click="open = !open"
                    class="relative flex items-center gap-2 rounded-xl px-3.5 py-2.5 text-[13px] font-bold transition-colors"
                    :class="open ? 'bg-brand-600 text-white' : 'bg-brand-50 text-brand-700 hover:bg-brand-100 dark:bg-brand-500/12 dark:text-brand-400 dark:hover:bg-brand-500/20'"
                    :aria-expanded="open.toString()">
                <x-admin-panel::icon name="filter" class="size-4" />
                فیلترها
                <span class="fa-digits inline-flex size-5 items-center justify-center rounded-full text-[10px] font-extrabold"
                      :class="open ? 'bg-white/25 text-white' : 'bg-brand-600 text-white dark:bg-brand-500'"
                      x-text="ApFormat.faDigits(($wire.title ? 1 : 0) + ($wire.stock ? 1 : 0) + ($wire.published ? 1 : 0) + ($wire.special ? 1 : 0) + ($wire.status ? 1 : 0) + ($wire.categoryIds.length ? 1 : 0))"></span>
            </button>
        </div>

        {{-- پنل پیشرفته --}}
        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-y-2"
             class="space-y-5 border-t border-line bg-surface-2/60 p-4 sm:p-5 dark:border-night-line dark:bg-night-3/40">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {{-- وضعیت انتشار --}}
                <div>
                    <p class="mb-2 text-xs font-bold text-ink-2 dark:text-night-ink-2">وضعیت انتشار</p>
                    <div class="inline-flex rounded-xl border border-line bg-surface p-1 dark:border-night-line dark:bg-night-2">
                        <button type="button" wire:click="$set('published','')" class="ap-pill" :class="$wire.published === '' ? 'is-active' : ''">همه</button>
                        <button type="button" wire:click="$set('published','yes')" class="ap-pill" :class="$wire.published === 'yes' ? 'is-active' : ''">منتشر شده</button>
                        <button type="button" wire:click="$set('published','no')" class="ap-pill" :class="$wire.published === 'no' ? 'is-active' : ''">پیش‌نویس</button>
                    </div>
                </div>

                {{-- موجودی --}}
                <div>
                    <p class="mb-2 text-xs font-bold text-ink-2 dark:text-night-ink-2">موجودی</p>
                    <div class="inline-flex rounded-xl border border-line bg-surface p-1 dark:border-night-line dark:bg-night-2">
                        <button type="button" wire:click="$set('stock','')" class="ap-pill" :class="$wire.stock === '' ? 'is-active' : ''">همه</button>
                        <button type="button" wire:click="$set('stock','available')" class="ap-pill" :class="$wire.stock === 'available' ? 'is-active' : ''">موجود</button>
                        <button type="button" wire:click="$set('stock','unavailable')" class="ap-pill" :class="$wire.stock === 'unavailable' ? 'is-active' : ''">ناموجود</button>
                    </div>
                </div>

                {{-- محصول ویژه --}}
                <div>
                    <p class="mb-2 text-xs font-bold text-ink-2 dark:text-night-ink-2">محصول ویژه</p>
                    <div class="inline-flex rounded-xl border border-line bg-surface p-1 dark:border-night-line dark:bg-night-2">
                        <button type="button" wire:click="$set('special','')" class="ap-pill" :class="$wire.special === '' ? 'is-active' : ''">همه</button>
                        <button type="button" wire:click="$set('special','yes')" class="ap-pill" :class="$wire.special === 'yes' ? 'is-active' : ''">ویژه</button>
                        <button type="button" wire:click="$set('special','no')" class="ap-pill" :class="$wire.special === 'no' ? 'is-active' : ''">غیر ویژه</button>
                    </div>
                </div>

                {{-- وضعیت تایید --}}
                <div>
                    <p class="mb-2 text-xs font-bold text-ink-2 dark:text-night-ink-2">وضعیت تایید</p>
                    <div class="inline-flex rounded-xl border border-line bg-surface p-1 dark:border-night-line dark:bg-night-2">
                        <button type="button" wire:click="$set('status','')" class="ap-pill" :class="$wire.status === '' ? 'is-active' : ''">همه</button>
                        <button type="button" wire:click="$set('status','Accept')" class="ap-pill" :class="$wire.status === 'Accept' ? 'is-active' : ''">تایید شده</button>
                        <button type="button" wire:click="$set('status','Waiting')" class="ap-pill" :class="$wire.status === 'Waiting' ? 'is-active' : ''">در انتظار</button>
                        <button type="button" wire:click="$set('status','Reject')" class="ap-pill" :class="$wire.status === 'Reject' ? 'is-active' : ''">رد شده</button>
                    </div>
                </div>
            </div>

            {{-- دسته‌بندی‌ها — انتخابگر درختی (فاز ۲۱) --}}
            <div>
                <p class="mb-2 text-xs font-bold text-ink-2 dark:text-night-ink-2">دسته‌بندی‌ها</p>
                <x-admin-panel::category-picker wireProp="categoryIds" :tree="$this->categoryOptions()" :multiple="true" placeholder="انتخاب دسته‌بندی‌ها…" />
            </div>
        </div>

        {{-- چیپ‌های فیلتر فعال --}}
        <div class="flex flex-wrap items-center gap-2 border-t border-line px-4 py-3 dark:border-night-line"
             x-show="($wire.title || $wire.stock || $wire.published || $wire.special || $wire.status) && open === false" x-cloak>
            <span class="text-xs font-semibold text-ink-3 dark:text-night-ink-2">فیلترهای فعال:</span>
            <template x-if="$wire.title">
                <button type="button" wire:click="$set('title','')" class="ap-filter-chip">
                    <span>عنوان: <b x-text="$wire.title"></b></span>
                    <x-admin-panel::icon name="x" class="size-3" />
                </button>
            </template>
            <template x-if="$wire.published">
                <button type="button" wire:click="$set('published','')" class="ap-filter-chip">
                    <span>انتشار: <b x-text="$wire.published === 'yes' ? 'منتشر شده' : 'پیش‌نویس'"></b></span>
                    <x-admin-panel::icon name="x" class="size-3" />
                </button>
            </template>
            <template x-if="$wire.stock">
                <button type="button" wire:click="$set('stock','')" class="ap-filter-chip">
                    <span>موجودی: <b x-text="$wire.stock === 'available' ? 'موجود' : 'ناموجود'"></b></span>
                    <x-admin-panel::icon name="x" class="size-3" />
                </button>
            </template>
            <template x-if="$wire.special">
                <button type="button" wire:click="$set('special','')" class="ap-filter-chip">
                    <span>ویژه: <b x-text="$wire.special === 'yes' ? 'بله' : 'خیر'"></b></span>
                    <x-admin-panel::icon name="x" class="size-3" />
                </button>
            </template>
            <template x-if="$wire.status">
                <button type="button" wire:click="$set('status','')" class="ap-filter-chip">
                    <span>تایید: <b x-text="$wire.status === 'Accept' ? 'تایید شده' : ($wire.status === 'Waiting' ? 'در انتظار' : 'رد شده')"></b></span>
                    <x-admin-panel::icon name="x" class="size-3" />
                </button>
            </template>
        </div>
    </div>

    {{-- نوار انتخاب گروهی --}}
    <div x-show="$wire.selected.length > 0" x-cloak
         class="sticky top-20 z-20 flex items-center justify-between gap-3 rounded-2xl border border-brand-200 bg-brand-50/95 px-4 py-2.5 shadow-card backdrop-blur dark:border-brand-500/25 dark:bg-brand-950/80">
        <span class="text-[13px] font-bold text-brand-800 dark:text-brand-300">
            <span class="fa-digits" x-text="ApFormat.faDigits($wire.selected.length)"></span> محصول انتخاب شده
        </span>
        <button type="button" wire:click="$set('confirmingBulkDelete', true)"
                class="ap-btn !px-3 !py-1.5 text-[13px] bg-red-600 text-white hover:bg-red-700">
            <x-admin-panel::icon name="trash-2" class="size-4" />
            حذف همه
        </button>
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
                        <th class="w-14 px-2 py-3 text-start font-bold">#</th>
                        <th class="w-16 px-2 py-3 text-start font-bold">تصویر</th>
                        <th class="px-3 py-3 text-start font-bold">
                            <button wire:click="sortBy('title')" class="inline-flex items-center gap-1 font-bold hover:text-brand-600">
                                عنوان محصول
                                @if ($sortField === 'title')
                                    <x-admin-panel::icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3.5" />
                                @endif
                            </button>
                        </th>
                        <th class="px-3 py-3 text-start font-bold">
                            <button wire:click="sortBy('created_at')" class="inline-flex items-center gap-1 font-bold hover:text-brand-600">
                                تاریخ ایجاد
                                @if ($sortField === '' || $sortField === 'created_at')
                                    <x-admin-panel::icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3.5" />
                                @endif
                            </button>
                        </th>
                        <th class="px-3 py-3 text-start font-bold">
                            <button wire:click="sortBy('stock')" class="inline-flex items-center gap-1 font-bold hover:text-brand-600">
                                موجودی
                                @if ($sortField === 'stock')
                                    <x-admin-panel::icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3.5" />
                                @endif
                            </button>
                        </th>
                        <th class="px-3 py-3 text-start font-bold">قیمت از</th>
                        <th class="px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->products as $product)
                        <tr wire:key="product-{{ $product->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $product->id }}"
                                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                       aria-label="انتخاب محصول {{ $product->title }}">
                            </td>
                            <td class="px-2 py-3 fa-digits text-ink-3">{{ number_format($product->id) }}</td>
                            <td class="px-2 py-3">
                                <img src="{{ $product->imageUrl() }}" alt="{{ $product->title }}" width="40" height="40"
                                     class="size-10 rounded-lg border border-line object-cover dark:border-night-line"
                                     loading="lazy">
                            </td>
                            <td class="max-w-64 px-3 py-3">
                                <p class="truncate font-semibold">{{ $product->title }}</p>
                                <div class="mt-1 flex items-center gap-2 text-[11px] text-ink-3 dark:text-night-ink-2">
                                    @if ($product->category)
                                        <span class="truncate">{{ $product->category->title }}</span>
                                    @endif
                                    @if ($product->seller_id)
                                        <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">فروشنده</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <p class="fa-digits whitespace-nowrap">{{ $this->jdate($product->created_at, 'Y/m/d') }}</p>
                                <p class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">
                                    {{ $this->jdate($product->created_at, 'H:i') }}
                                </p>
                            </td>
                            <td class="px-3 py-3">
                                @if ($product->type === 'download')
                                    <span class="ap-badge bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400">دانلودی</span>
                                @elseif (($product->prices_sum_stock ?? 0) > 0)
                                    <span class="fa-digits font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($product->prices_sum_stock) }}</span>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">ناموجود</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap font-semibold">
                                @if ($product->lowestPrice)
                                    {{ number_format($product->lowestPrice->hasDiscount() ? $product->lowestPrice->discount_price : $product->lowestPrice->regular_price) }}
                                    <span class="text-[10px] font-normal text-ink-3">تومان</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-col items-start gap-1">
                                    <span class="ap-badge {{ $product->isPublished()
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400'
                                        : 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' }}">
                                        {{ $product->isPublished() ? 'منتشر شده' : 'پیش‌نویس' }}
                                    </span>
                                    @if ($product->status !== 'Accept')
                                        <span class="ap-badge {{ $product->status === 'Waiting'
                                            ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400'
                                            : 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400' }}">
                                            {{ $product->status === 'Waiting' ? 'در انتظار تایید' : 'تایید نشده' }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <a href="{{ route('np.products.edit', $product->slug) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="ویرایش" aria-label="ویرایش محصول">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </a>
                                    <a href="{{ route('np.products.create', ['copy' => $product->slug]) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-sky-50 hover:text-sky-600 dark:hover:bg-sky-500/10"
                                       title="کپی کردن" aria-label="کپی محصول">
                                        <x-admin-panel::icon name="copy" class="size-4" />
                                    </a>
                                    <a href="{{ $product->link() }}" target="_blank" rel="noopener"
                                       class="hidden rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 hover:text-ink sm:block dark:hover:bg-night-3"
                                       title="مشاهده در سایت" aria-label="مشاهده در سایت">
                                        <x-admin-panel::icon name="external-link" class="size-4" />
                                    </a>
                                    <button wire:click="confirmDelete({{ $product->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف" aria-label="حذف محصول">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="package-search" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">محصولی با این فیلترها پیدا نشد</p>
                                    <a href="{{ route('np.products.create') }}" wire:navigate class="ap-btn-outline mt-1 !py-2 text-[13px]">
                                        <x-admin-panel::icon name="plus" class="size-4" />
                                        افزودن محصول جدید
                                    </a>
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
                    نمایش <span class="fa-digits">{{ number_format($this->products->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->products->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->products->total()) }}</span> محصول
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->products->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </span>
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
                        <span class="rounded-lg p-2 text-ink-3 opacity-40">
                            <x-admin-panel::icon name="chevron-left" class="size-4" />
                        </span>
                    @endif
                </nav>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف تکی --}}
    <div x-data="{ open: @entangle('confirmingDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="trash-2" class="size-6" />
            </div>
            <h3 class="text-base font-bold">حذف محصول</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                آیا از حذف این محصول مطمئن هستید؟ تصاویر، گالری و موجودی انبار آن نیز حذف می‌شود و غیرقابل بازگشت است.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله، حذف کن
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
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="trash-2" class="size-6" />
            </div>
            <h3 class="text-base font-bold">حذف گروهی محصولات</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                <span class="fa-digits font-bold" x-text="ApFormat.faDigits($wire.selected.length)"></span> محصول انتخاب شده است. حذف غیرقابل بازگشت است.
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

    {{-- لودینگ کوئری‌ها --}}
    <div wire:loading wire:target="title, stock, published, special, status, categoryIds, perPage, previousPage, nextPage, gotoPage, sortBy"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
