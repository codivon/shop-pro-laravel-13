{{-- شهرهای استان (سلکت استان در URL) + مودال ایجاد/ویرایش + ترتیب + حذف گروهی — پنل جدید (فاز ۷-ب۱) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                استان: <span class="font-bold">{{ $this->currentProvince?->name ?? '—' }}</span>
            </span>
            <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                شهرها: <span class="fa-digits">{{ number_format($this->cities->total()) }}</span>
            </span>
        </div>
        <button type="button" wire:click="$set('showCreateModal', true)" class="ap-btn !py-2">
            <x-admin-panel::icon name="plus" class="size-4" />
            شهر جدید
        </button>
    </div>

    {{-- انتخاب استان + فیلترها (پورت back.cities.partials.filters + انتخاب province) --}}
    <div class="ap-card p-4 sm:p-5">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1.5 block text-xs font-semibold" for="city-province-select">استان</label>
                <select id="city-province-select" wire:model.live="provinceId" class="ap-input !py-2">
                    @foreach ($this->provinces as $province)
                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">نام شهر</label>
                <input type="text" wire:model.live.debounce.400ms="filterName" class="ap-input !py-2" placeholder="جستجوی نام…">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">وضعیت</label>
                <select wire:model.live="filterIsActive" class="ap-input !py-2">
                    <option value="">همه</option>
                    <option value="1">فعال</option>
                    <option value="0">غیر فعال</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="button" wire:click="applyFilters" class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                    بازنشانی فیلترها
                </button>
            </div>
        </div>
    </div>

    {{-- نوار انتخاب گروهی --}}
    @if (count($selected))
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface p-3.5 dark:border-night-line dark:bg-night-2">
            <p class="text-[13px] font-semibold">
                <span class="fa-digits text-brand-600 dark:text-brand-400">{{ number_format(count($selected)) }}</span> مورد انتخاب شده
            </p>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="confirmBulkDelete" class="ap-btn !py-1.5 bg-red-600 text-white hover:bg-red-700">
                    <x-admin-panel::icon name="trash-2" class="size-4" />
                    حذف همه
                </button>
                <button type="button" wire:click="$set('selected', [])" class="ap-btn-ghost !py-1.5">لغو انتخاب‌ها</button>
            </div>
        </div>
    @endif

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-12 px-3 py-3">
                            <input type="checkbox" class="size-4 accent-brand-600" wire:click="toggleSelectAll" aria-label="انتخاب همه">
                        </th>
                        <th class="w-14 px-3 py-3 text-start font-bold">ID</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">ترتیب</th>
                        <th class="px-3 py-3 text-start font-bold">نام</th>
                        <th class="px-3 py-3 text-start font-bold">استان</th>
                        <th class="px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->cities as $city)
                        <tr wire:key="city-{{ $city->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" class="size-4 accent-brand-600" value="{{ $city->id }}"
                                       wire:model="selected" aria-label="انتخاب شهر {{ $city->name }}">
                            </td>
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($city->id) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <span class="fa-digits me-1 min-w-6 text-center font-bold text-ink-2 dark:text-night-ink-2">{{ $city->ordering ?? '—' }}</span>
                                    <button type="button" wire:click="moveUp({{ $city->id }})"
                                            class="rounded-md p-1 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="انتقال به بالا" aria-label="انتقال به بالا">
                                        <x-admin-panel::icon name="chevron-up" class="size-3.5" />
                                    </button>
                                    <button type="button" wire:click="moveDown({{ $city->id }})"
                                            class="rounded-md p-1 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="انتقال به پایین" aria-label="انتقال به پایین">
                                        <x-admin-panel::icon name="chevron-down" class="size-3.5" />
                                    </button>
                                </div>
                            </td>
                            <td class="max-w-44 px-3 py-3">
                                <p class="truncate font-semibold">{{ $city->name }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center gap-1 rounded-lg bg-surface-3 px-2 py-1 text-xs font-semibold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="map-pin" class="size-3.5" />
                                    {{ $city->province?->name ?? '—' }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                @if ($city->is_active)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">فعال</span>
                                @else
                                    <span class="ap-badge bg-surface-3 text-ink-3 dark:bg-night-3 dark:text-night-ink-2">غیرفعال</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <button type="button" wire:click="editCity({{ $city->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="ویرایش شهر" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $city->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف شهر" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="map-pin" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">
                                        @if ($this->provinces->isEmpty())
                                            ابتدا یک استان ایجاد کنید
                                        @else
                                            شهری برای این استان ثبت نشده است
                                        @endif
                                    </p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->cities->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->cities->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->cities->total()) }}</span> شهر
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->cities->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->cities->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->cities->hasMorePages())
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

    {{-- مودال ایجاد شهر (پورت create/store قدیمی) --}}
    <div x-data="{ open: @entangle('showCreateModal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">ایجاد شهر جدید</h3>

            <form wire:submit="createCity" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="city-create-name">نام <span class="text-red-500">*</span></label>
                    <input id="city-create-name" type="text" wire:model="createName" class="ap-input !py-2" placeholder="مثال: کاشان">
                    @error('createName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="city-create-province">استان <span class="text-red-500">*</span></label>
                    <select id="city-create-province" wire:model="createProvinceId" class="ap-input !py-2">
                        <option value="">انتخاب کنید…</option>
                        @foreach ($this->provinces as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                    @error('createProvinceId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="city-create-lat">عرض جغرافیایی (اختیاری)</label>
                        <input id="city-create-lat" type="text" wire:model="createLatitude" dir="ltr" class="ap-input !py-2 text-start font-mono" placeholder="33.9850">
                        @error('createLatitude') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="city-create-lng">طول جغرافیایی (اختیاری)</label>
                        <input id="city-create-lng" type="text" wire:model="createLongitude" dir="ltr" class="ap-input !py-2 text-start font-mono" placeholder="51.4100">
                        @error('createLongitude') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                    <input type="checkbox" wire:model="createIsActive" class="size-4 accent-brand-600">
                    وضعیت فعال
                </label>
                <div class="flex gap-2.5">
                    <button type="submit" wire:loading.attr="disabled" wire:target="createCity" class="ap-btn flex-1">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        ایجاد شهر
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال ویرایش شهر (پورت edit/update قدیمی) --}}
    <div x-data="{ open: @entangle('editingId').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">ویرایش شهر</h3>

            <form wire:submit="updateCity" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="city-edit-name">نام <span class="text-red-500">*</span></label>
                    <input id="city-edit-name" type="text" wire:model="editName" class="ap-input !py-2">
                    @error('editName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="city-edit-province">استان <span class="text-red-500">*</span></label>
                    <select id="city-edit-province" wire:model="editProvinceId" class="ap-input !py-2">
                        <option value="">انتخاب کنید…</option>
                        @foreach ($this->provinces as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                    @error('editProvinceId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="city-edit-lat">عرض جغرافیایی (اختیاری)</label>
                        <input id="city-edit-lat" type="text" wire:model="editLatitude" dir="ltr" class="ap-input !py-2 text-start font-mono">
                        @error('editLatitude') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="city-edit-lng">طول جغرافیایی (اختیاری)</label>
                        <input id="city-edit-lng" type="text" wire:model="editLongitude" dir="ltr" class="ap-input !py-2 text-start font-mono">
                        @error('editLongitude') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="city-edit-ordering">ترتیب</label>
                        <input id="city-edit-ordering" type="number" wire:model="editOrdering" class="ap-input !py-2 fa-digits text-center">
                        @error('editOrdering') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                    <input type="checkbox" wire:model="editIsActive" class="size-4 accent-brand-600">
                    وضعیت فعال
                </label>
                <div class="flex gap-2.5">
                    <button type="submit" wire:loading.attr="disabled" wire:target="updateCity" class="ap-btn flex-1">
                        <x-admin-panel::icon name="check" class="size-4" />
                        ویرایش شهر
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال تایید حذف تکی --}}
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
            <h3 class="text-base font-bold">حذف شهر</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف شهرها دیگر قادر به بازیابی آنها نخواهید بود.
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

    {{-- مودال تایید حذف گروهی --}}
    <div x-data="{ open: @entangle('confirmingBulkDelete').live }"
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
            <h3 class="text-base font-bold">حذف شهرهای انتخابی</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف شهرها دیگر قادر به بازیابی آنها نخواهید بود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="multipleDestroy" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="createCity, updateCity, delete, multipleDestroy, moveUp, moveDown, previousPage, nextPage, gotoPage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
