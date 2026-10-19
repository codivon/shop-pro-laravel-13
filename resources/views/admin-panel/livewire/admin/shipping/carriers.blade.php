{{-- روش‌های ارسال — پورت back/carriers (فاز ۷-ب۲) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="truck" class="size-3.5" />
                روش‌های ارسال عمومی: <span class="fa-digits">{{ number_format(\App\Models\Carrier::detectLang()->currentGeneral()->count()) }}</span>
            </span>
        </div>
        <button type="button" wire:click="$set('showCreateModal', true)" class="ap-btn !py-2">
            <x-admin-panel::icon name="plus" class="size-4" />
            ایجاد روش ارسال
        </button>
    </div>

    {{-- جدول (پورت back/carriers/index) --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-12 px-3 py-3 text-start font-bold">ردیف</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">شهر فروشگاه</th>
                        <th class="px-3 py-3 text-start font-bold">شهرهای تحت پوشش</th>
                        <th class="px-3 py-3 text-start font-bold">پس کرایه</th>
                        <th class="px-3 py-3 text-start font-bold">تعرفه‌ها</th>
                        <th class="px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->rows as $carrier)
                        <tr wire:key="carrier-{{ $carrier->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits text-ink-3 dark:text-night-ink-2">{{ $loop->iteration }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2.5">
                                    @if ($carrier->image)
                                        <img src="{{ $carrier->image }}" alt="{{ $carrier->title }}"
                                             class="size-9 rounded-lg border border-line object-cover dark:border-night-line">
                                    @else
                                        <span class="flex size-9 items-center justify-center rounded-lg bg-surface-3 text-ink-3 dark:bg-night-3">
                                            <x-admin-panel::icon name="truck" class="size-4" />
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold">{{ $carrier->title }}</p>
                                        @if ($carrier->waiting_time)
                                            <p class="truncate text-[11px] text-ink-3 dark:text-night-ink-2">{{ $carrier->waiting_time }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                {{ optional($carrier->province)->name }} - {{ optional($carrier->city)->name }}
                            </td>
                            <td class="px-3 py-3">
                                @if ($carrier->covered_cities === 'all')
                                    <span class="text-ink-2 dark:text-night-ink-2">همه</span>
                                @else
                                    <span class="fa-digits inline-flex items-center gap-1 rounded-lg bg-surface-3 px-2 py-1 text-xs font-bold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="map-pin" class="size-3.5" />
                                        {{ number_format($carrier->cities_count ?? $carrier->cities->count()) }} شهر
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if ($carrier->carrige_forward)
                                    <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">بله</span>
                                @else
                                    <span class="text-ink-3 dark:text-night-ink-2">خیر</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if ($carrier->carrige_forward)
                                    <span class="text-ink-3 dark:text-night-ink-2">—</span>
                                @else
                                    <a @if (Route::has('np.tariffs.index')) href="{{ route('np.tariffs.index', ['carrier' => $carrier->id]) }}" wire:navigate @endif
                                       class="fa-digits inline-flex items-center gap-1 rounded-lg bg-surface-3 px-2 py-1 text-xs font-bold text-ink-2 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                                        <x-admin-panel::icon name="tags" class="size-3.5" />
                                        {{ number_format($carrier->tariffs_count) }}
                                    </a>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if ($carrier->is_active)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">فعال</span>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400">غیر فعال</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits text-ink-3 dark:text-night-ink-2">{{ self::jdate($carrier->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <button type="button" wire:click="editCarrier({{ $carrier->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="ویرایش روش ارسال" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $carrier->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف روش ارسال" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="truck" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">چیزی برای نمایش وجود ندارد!</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->rows->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->rows->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->rows->total()) }}</span> روش ارسال
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->rows->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->rows->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->rows->hasMorePages())
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

    {{-- مودال ایجاد روش ارسال (پورت back/carriers/create — ۳ تب) --}}
    <div x-data="{ tab: 'base' }" x-show="$wire.showCreateModal" x-cloak
         class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto p-4 sm:items-center">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="$wire.showCreateModal" x-on:click="$wire.set('showCreateModal', false)" x-transition.opacity aria-hidden="true"></div>
        <div x-show="$wire.showCreateModal" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-3xl rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-base font-bold">
                    <x-admin-panel::icon name="truck" class="size-5 text-brand-600 dark:text-brand-400" />
                    روش ارسال جدید
                </h3>
                <button type="button" x-on:click="$wire.set('showCreateModal', false)" class="rounded-lg p-1.5 text-ink-3 hover:bg-surface-3 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            {{-- تب‌ها --}}
            <div class="mt-4 flex gap-1 rounded-xl bg-surface-2 p-1 dark:bg-night-3">
                <button type="button" x-on:click="tab = 'base'" :class="tab === 'base' ? 'bg-surface shadow-sm dark:bg-night-2 text-brand-600 dark:text-brand-400' : 'text-ink-2 dark:text-night-ink-2'" class="flex-1 rounded-lg px-3 py-2 text-xs font-bold transition-colors">اطلاعات کلی</button>
                <button type="button" x-on:click="tab = 'zones'" :class="tab === 'zones' ? 'bg-surface shadow-sm dark:bg-night-2 text-brand-600 dark:text-brand-400' : 'text-ink-2 dark:text-night-ink-2'" class="flex-1 rounded-lg px-3 py-2 text-xs font-bold transition-colors">مناطق و هزینه‌ها</button>
                <button type="button" x-on:click="tab = 'ranges'" :class="tab === 'ranges' ? 'bg-surface shadow-sm dark:bg-night-2 text-brand-600 dark:text-brand-400' : 'text-ink-2 dark:text-night-ink-2'" class="flex-1 rounded-lg px-3 py-2 text-xs font-bold transition-colors">بازه زمانی ارسال</button>
            </div>

            <form wire:submit="createCarrier" class="mt-4">
                {{-- تب ۱: اطلاعات کلی --}}
                <div x-show="tab === 'base'" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">عنوان <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="createTitle" class="ap-input !py-2">
                        @error('createTitle') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">تصویر</label>
                        <input type="file" accept="image/*" wire:model="createImage" class="ap-input !py-1.5 text-xs file:me-2 file:rounded-md file:border-0 file:bg-surface-3 file:px-2.5 file:py-1 file:text-xs">
                        @error('createImage') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">وضعیت</label>
                        <select wire:model="createIsActive" class="ap-input !py-2">
                            <option value="1">فعال</option>
                            <option value="0">غیر فعال</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">زمان انتظار</label>
                        <input type="text" wire:model="createWaitingTime" class="ap-input !py-2">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">حداکثر وزن بسته (گرم)</label>
                        <input type="number" wire:model="createMaxPackageWeight" class="ap-input !py-2 fa-digits">
                        @error('createMaxPackageWeight') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">حداقل وزن بسته (گرم)</label>
                        <input type="number" wire:model="createMinPackageWeight" class="ap-input !py-2 fa-digits">
                        @error('createMinPackageWeight') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="mb-1.5 block text-xs font-semibold">توضیحات</label>
                        <textarea wire:model="createDescription" rows="3" class="ap-input !py-2"></textarea>
                    </div>
                </div>

                {{-- تب ۲: مناطق و هزینه‌ها --}}
                <div x-show="tab === 'zones'" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">استان فروشگاه <span class="text-red-500">*</span></label>
                        <select wire:model.live="createProvinceId" class="ap-input !py-2">
                            <option value="">انتخاب کنید</option>
                            @foreach ($this->provincesTree as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                        @error('createProvinceId') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">شهر فروشگاه <span class="text-red-500">*</span></label>
                        <select wire:model="createCityId" class="ap-input !py-2">
                            <option value="">انتخاب کنید</option>
                            @foreach ($this->createShopCities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                        @error('createCityId') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex cursor-pointer items-center gap-2 self-end text-[13px] font-semibold">
                        <input type="checkbox" wire:model="createCarrigeForward" class="size-4 accent-brand-600">
                        پس کرایه (کرایه پس از ارسال مشخص شود)
                    </label>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">ارسال رایگان برای وزن‌های بزرگتر از (گرم)</label>
                        <input type="number" wire:model="createFreeShippingWeight" class="ap-input !py-2 fa-digits">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">ارسال رایگان برای مبالغ بزرگتر از</label>
                        <input type="number" wire:model="createFreeShippingPrice" class="ap-input !py-2 fa-digits">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">هزینه اضافی برای بارهای سنگین‌تر (هر کیلوگرم)</label>
                        <input type="number" wire:model="createExtraCost" class="ap-input !py-2 fa-digits">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">نوع شهرهای تحت پوشش</label>
                        <select wire:model.live="createCoveredCities" class="ap-input !py-2">
                            <option value="all">همه</option>
                            <option value="select_city">انتخاب شهر</option>
                        </select>
                    </div>
                    @if ($createCoveredCities === 'select_city')
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="mb-1.5 block text-xs font-semibold">شهرهای تحت پوشش <span class="text-red-500">*</span></label>
                            <div class="max-h-56 space-y-3 overflow-y-auto rounded-xl border border-line p-3 dark:border-night-line">
                                @forelse ($this->provincesTree as $province)
                                    <div>
                                        <p class="mb-1.5 text-xs font-bold text-ink-2 dark:text-night-ink-2">{{ $province->name }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            @forelse ($province->cities as $city)
                                                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-surface-2 px-2.5 py-1.5 text-xs font-semibold dark:bg-night-3">
                                                    <input type="checkbox" value="{{ $city->id }}" wire:model="createIncludedCities" class="size-3.5 accent-brand-600">
                                                    {{ $city->name }}
                                                </label>
                                            @empty
                                                <span class="text-[11px] text-ink-3">شهر ثبت نشده</span>
                                            @endforelse
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-ink-3">استانی ثبت نشده است</p>
                                @endforelse
                            </div>
                            @error('createIncludedCities') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>

                {{-- تب ۳: بازه زمانی ارسال --}}
                <div x-show="tab === 'ranges'" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">نوع بازه زمانی ارسال</label>
                        <select wire:model.live="createDeliveryTimeType" class="ap-input !py-2">
                            <option value="default">متن پیشفرض</option>
                            <option value="user_select">انتخاب توسط کاربر</option>
                        </select>
                        <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">انتخاب کنید که بازه زمانی ثابت باشد یا کاربر انتخاب کند</p>
                        @error('createDeliveryTimeType') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">شروع بازه زمانی از (روز بعد از سفارش)</label>
                        <select wire:model="createStartDaysAfterOrder" class="ap-input !py-2">
                            @for ($days = 1; $days <= 11; $days++)
                                <option value="{{ $days }}">از {{ $days }} روز بعد</option>
                            @endfor
                        </select>
                        @error('createStartDaysAfterOrder') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    @if ($createDeliveryTimeType === 'default')
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold">بازه زمانی ارسال (پیشفرض)</label>
                            <input type="text" wire:model="createDefaultDeliveryRange" class="ap-input !py-2" placeholder="مثال: ارسال در 3 الی 6 روز کاری">
                            <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">متنی که به کاربر نمایش داده می‌شود</p>
                            @error('createDefaultDeliveryRange') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold">انتخاب بازه زمانی ارسال</label>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach ([7 => '۷ روزه', 10 => '۱۰ روزه', 15 => '۱۵ روزه', 20 => '۲۰ روزه'] as $range => $label)
                                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-xs font-bold transition-colors"
                                           :class="$wire.createUserSelectRanges == {{ $range }} ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400' : 'border-line text-ink-2 dark:border-night-line dark:text-night-ink-2'">
                                        <input type="radio" value="{{ $range }}" wire:model="createUserSelectRanges" class="size-3.5 accent-brand-600">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('createUserSelectRanges') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            <div class="mt-3 flex flex-wrap gap-6">
                                <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                                    <input type="checkbox" wire:model="createDisableFridays" class="size-4 accent-brand-600">
                                    غیرفعال کردن جمعه‌ها
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                                    <input type="checkbox" wire:model="createDisableHolidays" class="size-4 accent-brand-600">
                                    غیرفعال کردن تعطیلات رسمی
                                </label>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-5 flex items-center justify-end gap-2.5 border-t border-line pt-4 dark:border-night-line">
                    <button type="button" x-on:click="$wire.set('showCreateModal', false)" class="ap-btn-ghost">انصراف</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="createCarrier" class="ap-btn">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        ایجاد روش ارسال
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال ویرایش روش ارسال (پورت back/carriers/edit) --}}
    <div x-data="{ tab: 'base' }" x-show="$wire.editingId" x-cloak
         class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto p-4 sm:items-center">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="$wire.editingId" x-on:click="$wire.set('editingId', null)" x-transition.opacity aria-hidden="true"></div>
        <div x-show="$wire.editingId" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-3xl rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-base font-bold">
                    <x-admin-panel::icon name="pencil" class="size-5 text-brand-600 dark:text-brand-400" />
                    ویرایش روش ارسال
                </h3>
                <button type="button" x-on:click="$wire.set('editingId', null)" class="rounded-lg p-1.5 text-ink-3 hover:bg-surface-3 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            <div class="mt-4 flex gap-1 rounded-xl bg-surface-2 p-1 dark:bg-night-3">
                <button type="button" x-on:click="tab = 'base'" :class="tab === 'base' ? 'bg-surface shadow-sm dark:bg-night-2 text-brand-600 dark:text-brand-400' : 'text-ink-2 dark:text-night-ink-2'" class="flex-1 rounded-lg px-3 py-2 text-xs font-bold transition-colors">اطلاعات کلی</button>
                <button type="button" x-on:click="tab = 'zones'" :class="tab === 'zones' ? 'bg-surface shadow-sm dark:bg-night-2 text-brand-600 dark:text-brand-400' : 'text-ink-2 dark:text-night-ink-2'" class="flex-1 rounded-lg px-3 py-2 text-xs font-bold transition-colors">مناطق و هزینه‌ها</button>
                <button type="button" x-on:click="tab = 'ranges'" :class="tab === 'ranges' ? 'bg-surface shadow-sm dark:bg-night-2 text-brand-600 dark:text-brand-400' : 'text-ink-2 dark:text-night-ink-2'" class="flex-1 rounded-lg px-3 py-2 text-xs font-bold transition-colors">بازه زمانی ارسال</button>
            </div>

            <form wire:submit="updateCarrier" class="mt-4">
                <div x-show="tab === 'base'" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">عنوان <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="editTitle" class="ap-input !py-2">
                        @error('editTitle') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">تصویر <span class="text-[11px] font-normal text-ink-3">(خالی = نگه‌داشتن قبلی)</span></label>
                        <input type="file" accept="image/*" wire:model="editImage" class="ap-input !py-1.5 text-xs file:me-2 file:rounded-md file:border-0 file:bg-surface-3 file:px-2.5 file:py-1 file:text-xs">
                        @error('editImage') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">وضعیت</label>
                        <select wire:model="editIsActive" class="ap-input !py-2">
                            <option value="1">فعال</option>
                            <option value="0">غیر فعال</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">زمان انتظار</label>
                        <input type="text" wire:model="editWaitingTime" class="ap-input !py-2">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">حداکثر وزن بسته (گرم)</label>
                        <input type="number" wire:model="editMaxPackageWeight" class="ap-input !py-2 fa-digits">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">حداقل وزن بسته (گرم)</label>
                        <input type="number" wire:model="editMinPackageWeight" class="ap-input !py-2 fa-digits">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="mb-1.5 block text-xs font-semibold">توضیحات</label>
                        <textarea wire:model="editDescription" rows="3" class="ap-input !py-2"></textarea>
                    </div>
                </div>

                <div x-show="tab === 'zones'" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">استان فروشگاه <span class="text-red-500">*</span></label>
                        <select wire:model.live="editProvinceId" class="ap-input !py-2">
                            <option value="">انتخاب کنید</option>
                            @foreach ($this->provincesTree as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                        @error('editProvinceId') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">شهر فروشگاه <span class="text-red-500">*</span></label>
                        <select wire:model="editCityId" class="ap-input !py-2">
                            <option value="">انتخاب کنید</option>
                            @foreach ($this->editShopCities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                        @error('editCityId') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex cursor-pointer items-center gap-2 self-end text-[13px] font-semibold">
                        <input type="checkbox" wire:model="editCarrigeForward" class="size-4 accent-brand-600">
                        پس کرایه (کرایه پس از ارسال مشخص شود)
                    </label>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">ارسال رایگان برای وزن‌های بزرگتر از (گرم)</label>
                        <input type="number" wire:model="editFreeShippingWeight" class="ap-input !py-2 fa-digits">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">ارسال رایگان برای مبالغ بزرگتر از</label>
                        <input type="number" wire:model="editFreeShippingPrice" class="ap-input !py-2 fa-digits">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">هزینه اضافی برای بارهای سنگین‌تر (هر کیلوگرم)</label>
                        <input type="number" wire:model="editExtraCost" class="ap-input !py-2 fa-digits">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">نوع شهرهای تحت پوشش</label>
                        <select wire:model.live="editCoveredCities" class="ap-input !py-2">
                            <option value="all">همه</option>
                            <option value="select_city">انتخاب شهر</option>
                        </select>
                    </div>
                    @if ($editCoveredCities === 'select_city')
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="mb-1.5 block text-xs font-semibold">شهرهای تحت پوشش <span class="text-red-500">*</span></label>
                            <div class="max-h-56 space-y-3 overflow-y-auto rounded-xl border border-line p-3 dark:border-night-line">
                                @forelse ($this->provincesTree as $province)
                                    <div>
                                        <p class="mb-1.5 text-xs font-bold text-ink-2 dark:text-night-ink-2">{{ $province->name }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            @forelse ($province->cities as $city)
                                                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-surface-2 px-2.5 py-1.5 text-xs font-semibold dark:bg-night-3">
                                                    <input type="checkbox" value="{{ $city->id }}" wire:model="editIncludedCities" class="size-3.5 accent-brand-600">
                                                    {{ $city->name }}
                                                </label>
                                            @empty
                                                <span class="text-[11px] text-ink-3">شهر ثبت نشده</span>
                                            @endforelse
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-ink-3">استانی ثبت نشده است</p>
                                @endforelse
                            </div>
                            @error('editIncludedCities') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>

                <div x-show="tab === 'ranges'" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">نوع بازه زمانی ارسال</label>
                        <select wire:model.live="editDeliveryTimeType" class="ap-input !py-2">
                            <option value="default">متن پیشفرض</option>
                            <option value="user_select">انتخاب توسط کاربر</option>
                        </select>
                        @error('editDeliveryTimeType') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">شروع بازه زمانی از (روز بعد از سفارش)</label>
                        <select wire:model="editStartDaysAfterOrder" class="ap-input !py-2">
                            @for ($days = 1; $days <= 11; $days++)
                                <option value="{{ $days }}">از {{ $days }} روز بعد</option>
                            @endfor
                        </select>
                        @error('editStartDaysAfterOrder') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    @if ($editDeliveryTimeType === 'default')
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold">بازه زمانی ارسال (پیشفرض)</label>
                            <input type="text" wire:model="editDefaultDeliveryRange" class="ap-input !py-2">
                            @error('editDefaultDeliveryRange') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold">انتخاب بازه زمانی ارسال</label>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach ([7 => '۷ روزه', 10 => '۱۰ روزه', 15 => '۱۵ روزه', 20 => '۲۰ روزه'] as $range => $label)
                                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-xs font-bold transition-colors"
                                           :class="$wire.editUserSelectRanges == {{ $range }} ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400' : 'border-line text-ink-2 dark:border-night-line dark:text-night-ink-2'">
                                        <input type="radio" value="{{ $range }}" wire:model="editUserSelectRanges" class="size-3.5 accent-brand-600">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('editUserSelectRanges') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            <div class="mt-3 flex flex-wrap gap-6">
                                <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                                    <input type="checkbox" wire:model="editDisableFridays" class="size-4 accent-brand-600">
                                    غیرفعال کردن جمعه‌ها
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                                    <input type="checkbox" wire:model="editDisableHolidays" class="size-4 accent-brand-600">
                                    غیرفعال کردن تعطیلات رسمی
                                </label>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-5 flex items-center justify-end gap-2.5 border-t border-line pt-4 dark:border-night-line">
                    <button type="button" x-on:click="$wire.set('editingId', null)" class="ap-btn-ghost">انصراف</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="updateCarrier" class="ap-btn">
                        <x-admin-panel::icon name="check" class="size-4" />
                        ویرایش روش ارسال
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال تایید حذف (متن پورت back/carriers/index) --}}
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
            <h3 class="text-base font-bold">آیا مطمئن هستید؟</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف روش ارسال دیگر قادر به بازیابی آن نخواهید بود
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">خیر</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="createCarrier, updateCarrier, delete"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
