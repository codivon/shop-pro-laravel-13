{{-- لیست انبارها + مودال ایجاد/ویرایش — پنل جدید (فاز ۷-ب۳) — پورت index/create/store/edit/update/destroy/toggleStatus --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- کارت‌های آماری (پورت index قدیمی) --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">کل انبارها</p>
                <p class="fa-digits mt-1 text-2xl font-black">{{ number_format($this->stats['total']) }}</p>
                <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">اصلی: {{ number_format($this->stats['main']) }} — فروشنده: {{ number_format($this->stats['seller']) }}</p>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="warehouse" class="size-5" />
            </span>
        </div>
        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">انبار فعال</p>
                <p class="fa-digits mt-1 text-2xl font-black">{{ number_format($this->stats['active']) }}</p>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                <x-admin-panel::icon name="circle-check" class="size-5" />
            </span>
        </div>
        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">محصول منحصر به فرد</p>
                <p class="fa-digits mt-1 text-2xl font-black">{{ number_format($this->stats['total_products']) }}</p>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                <x-admin-panel::icon name="package" class="size-5" />
            </span>
        </div>
        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">کل موجودی</p>
                <p class="fa-digits mt-1 text-2xl font-black">{{ number_format($this->stats['total_stock']) }}</p>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                <x-admin-panel::icon name="layers" class="size-5" />
            </span>
        </div>
    </div>

    {{-- فیلترها --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر انبارها
                @if ($search !== '' || $type !== 'all' || $status !== 'all')
                    <span class="size-2 rounded-full bg-brand-500"></span>
                @endif
            </span>
            <span class="ap-chevron" :class="open ? 'is-open' : ''">
                <x-admin-panel::icon name="chevron-left" class="size-4 text-ink-3" />
            </span>
        </button>

        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="pt-4">
            <form wire:submit="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">جستجو</label>
                    <input type="text" wire:model.live.debounce.400ms="search" class="ap-input !py-2" placeholder="نام یا کد انبار…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع انبار</label>
                    <select wire:model.live="type" class="ap-input !py-2">
                        <option value="all">همه</option>
                        <option value="main">اصلی</option>
                        <option value="seller">فروشندگان</option>
                        <option value="temp">موقت</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت</label>
                    <select wire:model.live="status" class="ap-input !py-2">
                        <option value="all">همه</option>
                        <option value="active">فعال</option>
                        <option value="inactive">غیرفعال</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="button" wire:click="resetFilters" class="ap-btn-outline !py-2 w-full">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        بازنشانی فیلترها
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- سربرگ لیست + دکمه ایجاد (پورت create) --}}
    <div class="ap-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 dark:border-night-line">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="list" class="size-4.5 text-brand-600 dark:text-brand-400" />
                لیست انبارها
            </h3>
            <button type="button" wire:click="openCreate" class="ap-btn !py-2">
                <x-admin-panel::icon name="plus" class="size-4" />
                ایجاد انبار جدید
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="px-3 py-3 text-start font-bold">کد انبار</th>
                        <th class="px-3 py-3 text-start font-bold">نام انبار</th>
                        <th class="px-3 py-3 text-start font-bold">نوع</th>
                        <th class="px-3 py-3 text-start font-bold">تعداد محصول</th>
                        <th class="px-3 py-3 text-start font-bold">آدرس</th>
                        <th class="px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->warehouses as $warehouse)
                        <tr wire:key="warehouse-{{ $warehouse->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3"><code class="rounded-md bg-surface-2 px-1.5 py-0.5 text-[11px] text-ink-2 dark:bg-night-3 dark:text-night-ink-2" dir="ltr">{{ $warehouse->code }}</code></td>
                            <td class="max-w-48 px-3 py-3">
                                <a href="{{ route('np.warehouses.show', ['warehouse' => $warehouse->id]) }}" wire:navigate
                                   class="font-bold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400">
                                    {{ $warehouse->name }}
                                </a>
                            </td>
                            <td class="px-3 py-3">
                                @if ($warehouse->type == 'main')
                                    <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">اصلی</span>
                                @elseif ($warehouse->type == 'seller')
                                    <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">فروشنده</span>
                                @else
                                    <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">موقت</span>
                                @endif
                            </td>
                            <td class="fa-digits px-3 py-3">{{ number_format($warehouse->products_count) }}</td>
                            <td class="max-w-56 px-3 py-3">
                                <p class="truncate text-ink-2 dark:text-night-ink-2">{{ \Illuminate\Support\Str::limit((string) $warehouse->address, 40) ?: '—' }}</p>
                            </td>
                            <td class="px-3 py-3">
                                @if ($warehouse->is_active)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">فعال</span>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400">غیرفعال</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <a href="{{ route('np.warehouses.show', ['warehouse' => $warehouse->id]) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="مشاهده" aria-label="مشاهده">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </a>
                                    <button type="button" wire:click="editWarehouse({{ $warehouse->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-500/10"
                                            title="ویرایش" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </button>
                                    <a href="{{ route('np.warehouses.movements', ['warehouse' => $warehouse->id]) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-sky-50 hover:text-sky-600 dark:hover:bg-sky-500/10"
                                       title="تاریخچه" aria-label="تاریخچه">
                                        <x-admin-panel::icon name="clock" class="size-4" />
                                    </a>
                                    <button type="button" wire:click="toggleStatus({{ $warehouse->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-violet-50 hover:text-violet-600 dark:hover:bg-violet-500/10"
                                            title="{{ $warehouse->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}" aria-label="تغییر وضعیت">
                                        <x-admin-panel::icon name="{{ $warehouse->is_active ? 'shield-off' : 'check-circle' }}" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $warehouse->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="warehouse" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هیچ انباری یافت نشد</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی (نویگیشن دست‌ساز — بدون wire:navigate) --}}
        <div class="border-t border-line px-4 py-3 dark:border-night-line">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    نمایش <span class="fa-digits">{{ number_format($this->warehouses->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->warehouses->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->warehouses->total()) }}</span> انبار
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->warehouses->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->warehouses->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->warehouses->hasMorePages())
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

    {{-- مودال ایجاد انبار (پورت create/store) --}}
    <div x-data="{ open: @entangle('creating').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="flex items-center gap-2 text-base font-bold">
                <x-admin-panel::icon name="warehouse" class="size-5 text-brand-600 dark:text-brand-400" />
                ایجاد انبار جدید
            </h3>

            <form wire:submit="createWarehouse" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold">نام انبار <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="createName" class="ap-input !py-2" placeholder="مثال: انبار مرکزی">
                    @error('createName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع انبار <span class="text-red-500">*</span></label>
                    <select wire:model="createType" class="ap-input !py-2">
                        <option value="main">انبار اصلی (فروشگاه)</option>
                        <option value="seller">انبار فروشنده</option>
                        <option value="temp">انبار موقت (برگشتی، معیوب و ...)</option>
                    </select>
                    @error('createType') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div x-show="$wire.createType === 'seller'" x-cloak>
                    <label class="mb-1.5 block text-xs font-semibold">انتخاب فروشنده <span class="text-red-500">*</span></label>
                    <select wire:model="createSellerId" class="ap-input !py-2">
                        <option value="">انتخاب فروشنده</option>
                        @foreach ($this->sellers() as $seller)
                            <option value="{{ $seller->id }}">{{ $seller->label }}</option>
                        @endforeach
                    </select>
                    @error('createSellerId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2" x-show="$wire.createType === 'temp'" x-cloak>
                    <div class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-6 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                        <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                        <span><strong>انبار موقت:</strong> برای کالاهای برگشتی، کالاهای معیوب، کالاهای تعویضی، حراجی‌های فصلی و ...</span>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">مدیر انبار</label>
                    <input type="text" wire:model="createManagerName" class="ap-input !py-2" placeholder="نام مدیر انبار">
                    @error('createManagerName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">تلفن انبار</label>
                    <input type="text" wire:model="createPhone" class="ap-input !py-2" placeholder="۰۲۱-XXXXXX">
                    @error('createPhone') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">استان</label>
                    <select wire:model="createProvinceId" class="ap-input !py-2">
                        <option value="">انتخاب استان</option>
                        @foreach ($this->provinces() as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                    @error('createProvinceId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">شهر</label>
                    <select wire:model="createCityId" class="ap-input !py-2" @if(!count($this->cities($this->createProvinceId))) disabled @endif>
                        <option value="">ابتدا استان را انتخاب کنید</option>
                        @foreach ($this->cities($this->createProvinceId) as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                    @error('createCityId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold">آدرس انبار</label>
                    <textarea wire:model="createAddress" rows="3" class="ap-input !py-2" placeholder="آدرس کامل انبار"></textarea>
                    @error('createAddress') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت انبار</label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" wire:model="createIsActive" class="size-4 rounded border-line text-brand-600 focus:ring-brand-500 dark:border-night-line">
                        <span class="text-[13px]">انبار فعال باشد</span>
                    </label>
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">در صورت غیرفعال بودن، این انبار در سیستم قابل استفاده نیست</p>
                </div>

                <div class="sm:col-span-2">
                    <div class="flex items-start gap-2 rounded-xl border border-line bg-surface-2 p-3 text-xs leading-6 text-ink-2 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                        <span>کد انبار به صورت خودکار ساخته می‌شود (مثال: WH-0001)</span>
                    </div>
                </div>

                <div class="flex gap-2.5 sm:col-span-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="createWarehouse" class="ap-btn flex-1 !py-2.5">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        ایجاد انبار
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1 !py-2.5">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال ویرایش انبار (پورت edit/update) --}}
    <div x-data="{ open: @entangle('editingId').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="flex items-center gap-2 text-base font-bold">
                <x-admin-panel::icon name="pencil" class="size-5 text-amber-600 dark:text-amber-400" />
                ویرایش انبار
            </h3>

            <form wire:submit="updateWarehouse" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold">نام انبار <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="editName" class="ap-input !py-2">
                    @error('editName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع انبار <span class="text-red-500">*</span></label>
                    <select wire:model="editType" class="ap-input !py-2">
                        <option value="main">انبار اصلی (فروشگاه)</option>
                        <option value="seller">انبار فروشنده</option>
                        <option value="temp">انبار موقت (برگشتی، معیوب و ...)</option>
                    </select>
                    @error('editType') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div x-show="$wire.editType === 'seller'" x-cloak>
                    <label class="mb-1.5 block text-xs font-semibold">انتخاب فروشنده <span class="text-red-500">*</span></label>
                    <select wire:model="editSellerId" class="ap-input !py-2">
                        <option value="">انتخاب فروشنده</option>
                        @foreach ($this->sellers() as $seller)
                            <option value="{{ $seller->id }}">{{ $seller->label }}</option>
                        @endforeach
                    </select>
                    @error('editSellerId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">مدیر انبار</label>
                    <input type="text" wire:model="editManagerName" class="ap-input !py-2">
                    @error('editManagerName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">تلفن انبار</label>
                    <input type="text" wire:model="editPhone" class="ap-input !py-2">
                    @error('editPhone') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">استان</label>
                    <select wire:model="editProvinceId" class="ap-input !py-2">
                        <option value="">انتخاب استان</option>
                        @foreach ($this->provinces() as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                    @error('editProvinceId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">شهر</label>
                    <select wire:model="editCityId" class="ap-input !py-2" @if(!count($this->editCities())) disabled @endif>
                        <option value="">ابتدا استان را انتخاب کنید</option>
                        @foreach ($this->editCities() as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                    @error('editCityId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold">آدرس انبار</label>
                    <textarea wire:model="editAddress" rows="3" class="ap-input !py-2"></textarea>
                    @error('editAddress') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت انبار</label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" wire:model="editIsActive" class="size-4 rounded border-line text-brand-600 focus:ring-brand-500 dark:border-night-line">
                        <span class="text-[13px]">انبار فعال باشد</span>
                    </label>
                </div>

                <div class="flex gap-2.5 sm:col-span-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="updateWarehouse" class="ap-btn flex-1 !py-2.5">
                        <x-admin-panel::icon name="check" class="size-4" />
                        ذخیره تغییرات
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1 !py-2.5">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال تایید حذف --}}
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
            <h3 class="text-base font-bold">حذف انبار</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                اگر این انبار محصول داشته باشد حذف انجام نمی‌شود؛ ابتدا محصولات را جابجا یا حذف کنید.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="search, type, status, previousPage, nextPage, gotoPage, createWarehouse, updateWarehouse, delete, toggleStatus"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
