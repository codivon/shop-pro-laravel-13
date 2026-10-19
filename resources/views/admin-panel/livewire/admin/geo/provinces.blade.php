{{-- لیست استان‌ها + مودال ایجاد/ویرایش + ترتیب + حذف گروهی — پنل جدید (فاز ۷-ب۱) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                کل استان‌ها: <span class="fa-digits">{{ number_format(\App\Models\Province::detectLang()->count()) }}</span>
            </span>
        </div>
        <button type="button" wire:click="$set('showCreateModal', true)" class="ap-btn !py-2">
            <x-admin-panel::icon name="plus" class="size-4" />
            استان جدید
        </button>
    </div>

    {{-- کارت فیلترها (پورت back.provinces.partials.filters) --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر استان‌ها
                @if ($filterName || $filterCityName || $filterIsActive !== '')
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
                    <label class="mb-1.5 block text-xs font-semibold">نام استان</label>
                    <input type="text" wire:model.live.debounce.400ms="filterName" class="ap-input !py-2" placeholder="جستجوی نام…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نام شهر</label>
                    <input type="text" wire:model.live.debounce.400ms="filterCityName" class="ap-input !py-2" placeholder="استان‌های دارای شهر…">
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
                    <button type="submit" class="ap-btn-outline !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        بازنشانی فیلترها
                    </button>
                </div>
            </form>
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
                            <input type="checkbox" class="size-4 accent-brand-600" wire:click="toggleSelectAll"
                                   aria-label="انتخاب همه">
                        </th>
                        <th class="w-14 px-3 py-3 text-start font-bold">ID</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">ترتیب</th>
                        <th class="px-3 py-3 text-start font-bold">نام</th>
                        <th class="px-3 py-3 text-start font-bold">تعداد شهرها</th>
                        <th class="px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->provinces as $province)
                        <tr wire:key="province-{{ $province->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" class="size-4 accent-brand-600" value="{{ $province->id }}"
                                       wire:model="selected" aria-label="انتخاب استان {{ $province->name }}">
                            </td>
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($province->id) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <span class="fa-digits me-1 min-w-6 text-center font-bold text-ink-2 dark:text-night-ink-2">{{ $province->ordering ?? '—' }}</span>
                                    <button type="button" wire:click="moveUp({{ $province->id }})"
                                            class="rounded-md p-1 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="انتقال به بالا" aria-label="انتقال به بالا">
                                        <x-admin-panel::icon name="chevron-up" class="size-3.5" />
                                    </button>
                                    <button type="button" wire:click="moveDown({{ $province->id }})"
                                            class="rounded-md p-1 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="انتقال به پایین" aria-label="انتقال به پایین">
                                        <x-admin-panel::icon name="chevron-down" class="size-3.5" />
                                    </button>
                                </div>
                            </td>
                            <td class="max-w-44 px-3 py-3">
                                <p class="truncate font-semibold">{{ $province->name }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <a @if (Route::has('np.cities.index')) href="{{ route('np.cities.index', ['province' => $province->id]) }}" wire:navigate @endif
                                   class="fa-digits inline-flex items-center gap-1 rounded-lg bg-surface-3 px-2 py-1 text-xs font-bold text-ink-2 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                                    <x-admin-panel::icon name="map-pin" class="size-3.5" />
                                    {{ number_format($province->cities_count) }}
                                </a>
                            </td>
                            <td class="px-3 py-3">
                                @if ($province->is_active)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">فعال</span>
                                @else
                                    <span class="ap-badge bg-surface-3 text-ink-3 dark:bg-night-3 dark:text-night-ink-2">غیرفعال</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <a @if (Route::has('np.cities.index')) href="{{ route('np.cities.index', ['province' => $province->id]) }}" wire:navigate @endif
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="نمایش شهرها" aria-label="نمایش شهرها">
                                        <x-admin-panel::icon name="map" class="size-4" />
                                    </a>
                                    <button type="button" wire:click="editProvince({{ $province->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="ویرایش استان" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $province->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف استان" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="map" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">استانی برای نمایش وجود ندارد</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->provinces->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->provinces->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->provinces->total()) }}</span> استان
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->provinces->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->provinces->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->provinces->hasMorePages())
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

    {{-- مودال ایجاد استان (پورت create/store قدیمی) --}}
    <div x-data="{ open: @entangle('showCreateModal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">ایجاد استان جدید</h3>

            <form wire:submit="createProvince" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="province-create-name">نام <span class="text-red-500">*</span></label>
                    <input id="province-create-name" type="text" wire:model="createName" class="ap-input !py-2" placeholder="مثال: اصفهان">
                    @error('createName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="province-create-lat">عرض جغرافیایی (اختیاری)</label>
                        <input id="province-create-lat" type="text" wire:model="createLatitude" dir="ltr" class="ap-input !py-2 text-start font-mono" placeholder="32.6546">
                        @error('createLatitude') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="province-create-lng">طول جغرافیایی (اختیاری)</label>
                        <input id="province-create-lng" type="text" wire:model="createLongitude" dir="ltr" class="ap-input !py-2 text-start font-mono" placeholder="51.6680">
                        @error('createLongitude') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                    <input type="checkbox" wire:model="createIsActive" class="size-4 accent-brand-600">
                    وضعیت فعال
                </label>
                <div class="flex gap-2.5">
                    <button type="submit" wire:loading.attr="disabled" wire:target="createProvince" class="ap-btn flex-1">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        ایجاد استان
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال ویرایش استان (پورت edit/update قدیمی) --}}
    <div x-data="{ open: @entangle('editingId').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">ویرایش استان</h3>

            <form wire:submit="updateProvince" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="province-edit-name">نام <span class="text-red-500">*</span></label>
                    <input id="province-edit-name" type="text" wire:model="editName" class="ap-input !py-2">
                    @error('editName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="province-edit-lat">عرض جغرافیایی (اختیاری)</label>
                        <input id="province-edit-lat" type="text" wire:model="editLatitude" dir="ltr" class="ap-input !py-2 text-start font-mono">
                        @error('editLatitude') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="province-edit-lng">طول جغرافیایی (اختیاری)</label>
                        <input id="province-edit-lng" type="text" wire:model="editLongitude" dir="ltr" class="ap-input !py-2 text-start font-mono">
                        @error('editLongitude') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="province-edit-ordering">ترتیب</label>
                        <input id="province-edit-ordering" type="number" wire:model="editOrdering" class="ap-input !py-2 fa-digits text-center">
                        @error('editOrdering') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                    <input type="checkbox" wire:model="editIsActive" class="size-4 accent-brand-600">
                    وضعیت فعال
                </label>
                <div class="flex gap-2.5">
                    <button type="submit" wire:loading.attr="disabled" wire:target="updateProvince" class="ap-btn flex-1">
                        <x-admin-panel::icon name="check" class="size-4" />
                        ویرایش استان
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
            <h3 class="text-base font-bold">حذف استان</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف استان‌ها دیگر قادر به بازیابی آنها نخواهید بود.
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
            <h3 class="text-base font-bold">حذف استان‌های انتخابی</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف استان‌ها دیگر قادر به بازیابی آنها نخواهید بود.
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
    <div wire:loading wire:target="createProvince, updateProvince, delete, multipleDestroy, moveUp, moveDown, previousPage, nextPage, gotoPage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
