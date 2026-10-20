{{-- فرم ایجاد/ویرایش فیلتر — فاز ۱۷: بازطراحی کامل مدرن
     مودال «افزودن مشخصات» (پورت add-filterable-modal قدیمی) با جستجوی زنده +
     افزودن مستقیم هر مورد با + و لیست ردیف‌های مدرن --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-5xl" x-data="apFilterForm">
    <form wire:submit="save" class="space-y-6" x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- هدر --}}
        <div class="relative overflow-hidden rounded-2xl border border-line bg-surface p-5 dark:border-night-line dark:bg-night-2 sm:p-6">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-l from-brand-500/10 via-transparent to-sky-500/10" aria-hidden="true"></div>
            <div class="relative flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-sky-600 text-white shadow-lg shadow-brand-500/25">
                        <x-admin-panel::icon name="filter" class="size-6" />
                    </span>
                    <div>
                        <h2 class="text-lg font-extrabold">
                            {{ $isEditing ? 'ویرایش فیلتر: ' . ($title ?: '—') : 'ایجاد فیلتر جدید' }}
                        </h2>
                        @if ($isEditing)
                            <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                                شناسه: <span class="fa-digits">{{ number_format($filterId) }}</span>
                            </p>
                        @else
                            <p class="text-[11px] text-ink-3 dark:text-night-ink-2">گروه‌بندی فیلترهای نمایش محصولات یا فروشندگان</p>
                        @endif
                    </div>
                </div>
                <a href="{{ route('np.filters.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                    <x-admin-panel::icon name="arrow-right" class="size-4" />
                    بازگشت به لیست
                </a>
            </div>
        </div>

        {{-- اطلاعات پایه --}}
        <div class="ap-card space-y-5 p-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="filter-title">عنوان فیلتر <span class="text-red-500">*</span></label>
                    <input id="filter-title" type="text" wire:model="title" class="ap-input" placeholder="مثال: فیلتر محصولات پرطرفدار">
                    @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                {{-- چک‌باکس فیلتر فروشنده — در ایجاد اگر فیلتر فروشنده موجود باشد مخفی می‌شود (مثل قدیمی)؛
                     در ویرایش همیشه نمایش داده می‌شود (حتی برای فیلتر فروشنده، قابل برداشتن است) --}}
                <div>
                    @if (! $isEditing && $this->sellerFilterExists)
                        <p class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-[12px] leading-5 text-amber-700 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-400">
                            <x-admin-panel::icon name="info" class="size-4 shrink-0" />
                            فیلتر فروشنده از قبل تعریف شده است؛ امکان تعریف فیلتر فروشنده دوم وجود ندارد.
                        </p>
                    @else
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-line px-3.5 py-2.5 transition-all hover:bg-surface-2 has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50/40 dark:border-night-line dark:hover:bg-night-3 dark:has-[:checked]:bg-brand-500/5">
                            <span class="relative inline-flex shrink-0 items-center">
                                <input type="checkbox" wire:model="isSeller" class="peer sr-only">
                                <span class="block h-6 w-11 rounded-full bg-surface-3 transition-colors duration-200 peer-checked:bg-brand-600 dark:bg-night-line dark:peer-checked:bg-brand-600"></span>
                                <span class="absolute start-0.5 top-0.5 size-5 rounded-full bg-white shadow transition-all duration-200 peer-checked:start-[1.375rem]"></span>
                            </span>
                            <span>
                                <span class="block text-[13px] font-bold">برای صفحه فروشندگان</span>
                                <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">فقط یک فیلتر فروشنده مجاز است</span>
                            </span>
                        </label>
                    @endif
                    @error('filter') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- سازنده فیلترپذیرها --}}
        <div class="ap-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line bg-surface-2 px-5 py-3.5 dark:border-night-line dark:bg-night-3/50">
                <p class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="layers" class="size-4 text-brand-600 dark:text-brand-400" />
                    موارد فیلترپذیر
                    <span class="fa-digits rounded-lg bg-surface px-2 py-0.5 text-[10px] font-bold text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                        {{ count($rows) }} مورد
                    </span>
                </p>
                <button type="button" x-on:click="openModal()"
                        class="ap-btn-primary !py-1.5 text-[13px]">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن مشخصات
                </button>
            </div>
            @error('rows') <p class="px-5 pt-3 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

            <div class="space-y-2.5 p-4 sm:p-5">
                @forelse ($rows as $i => $row)
                    <div class="group flex flex-wrap items-center gap-3 rounded-xl border border-line bg-surface-2/40 p-3 transition-colors hover:border-brand-200 dark:border-night-line dark:bg-night-3/40 dark:hover:border-brand-500/30"
                         wire:key="filter-row-{{ $i }}">
                        {{-- شماره ترتیب --}}
                        <span class="fa-digits flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-[12px] font-extrabold text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                            {{ $i + 1 }}
                        </span>

                        {{-- نام + نوع --}}
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-bold">
                                {{ $row['label'] ?: '— انتخاب نشده —' }}
                            </span>
                            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">
                                {{ \App\Livewire\Admin\Taxonomy\FilterForm::ROW_TYPES[$row['type']] ?? $row['type'] }}
                            </span>
                        </span>

                        {{-- جداکننده — فقط مشخصات --}}
                        @if ($row['type'] === 'specification')
                            <div class="w-28">
                                <input type="text" wire:model="rows.{{ $i }}.separator" class="ap-input !py-1.5 !text-xs" placeholder="جداکننده: / ،" aria-label="جداکننده">
                            </div>
                        @endif

                        {{-- سوییچ فعال --}}
                        <label class="flex shrink-0 cursor-pointer items-center gap-2 text-[12px] font-semibold">
                            <span class="relative inline-flex items-center">
                                <input type="checkbox" wire:model="rows.{{ $i }}.active" class="peer sr-only">
                                <span class="block h-5 w-9 rounded-full bg-surface-3 transition-colors duration-200 peer-checked:bg-emerald-500 dark:bg-night-line dark:peer-checked:bg-emerald-500"></span>
                                <span class="absolute start-0.5 top-0.5 size-4 rounded-full bg-white shadow transition-all duration-200 peer-checked:start-[1.125rem]"></span>
                            </span>
                            فعال
                        </label>

                        {{-- ابزار ترتیب/حذف --}}
                        <span class="flex shrink-0 items-center gap-0.5 border-s border-line ps-2 dark:border-night-line">
                            <button type="button" wire:click="moveRowUp({{ $i }})"
                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-brand-500/10"
                                    @if ($i === 0) disabled @endif title="انتقال به بالا" aria-label="انتقال به بالا">
                                <x-admin-panel::icon name="chevron-up" class="size-4" />
                            </button>
                            <button type="button" wire:click="moveRowDown({{ $i }})"
                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-brand-500/10"
                                    @if ($i === count($rows) - 1) disabled @endif title="انتقال به پایین" aria-label="انتقال به پایین">
                                <x-admin-panel::icon name="chevron-down" class="size-4" />
                            </button>
                            <button type="button" wire:click="removeRow({{ $i }})"
                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                    title="حذف مورد" aria-label="حذف مورد">
                                <x-admin-panel::icon name="trash-2" class="size-4" />
                            </button>
                        </span>
                    </div>
                @empty
                    <button type="button" x-on:click="openModal()"
                            class="flex w-full flex-col items-center gap-2 rounded-2xl border-2 border-dashed border-line py-10 text-ink-3 transition-colors hover:border-brand-400 hover:bg-brand-50/30 hover:text-brand-600 dark:border-night-line dark:hover:border-brand-500/50 dark:hover:bg-brand-500/5 dark:hover:text-brand-400">
                        <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-2 dark:bg-night-3">
                            <x-admin-panel::icon name="plus" class="size-6" />
                        </span>
                        <span class="text-[13px] font-bold">افزودن مشخصات</span>
                        <span class="text-[11px]">فیلترهای ثابت، گروه ویژگی‌ها و مشخصات را از همین‌جا انتخاب کنید</span>
                    </button>
                @endforelse
            </div>
        </div>

        {{-- نوار ذخیره (چسبان) --}}
        <div class="sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-lg backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <p class="text-[12px] text-ink-3 dark:text-night-ink-2">موارد حذف‌شده از فهرست، پس از ذخیره از فیلتر پاک می‌شوند.</p>
            <div class="flex items-center gap-2">
                <a href="{{ route('np.filters.index') }}" wire:navigate class="ap-btn-ghost !py-2">انصراف</a>
                <button type="submit" class="ap-btn-primary !py-2.5" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">{{ $isEditing ? 'ذخیره تغییرات' : 'ایجاد فیلتر' }}</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                        در حال ذخیره…
                    </span>
                </button>
            </div>
        </div>
    </form>

    {{-- ============================================================ --}}
    {{-- مودال «افزودن مشخصات» — پورت add-filterable-modal قدیمی با جستجو --}}
    {{-- ============================================================ --}}
    <div x-show="modalOpen" x-cloak
         x-data="{ mq: '' }"
         class="fixed inset-0 z-[90] flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-label="افزودن مشخصات"
         x-on:keydown.escape.window="modalOpen = false">
        <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" x-show="modalOpen" x-on:click="modalOpen = false" x-transition.opacity aria-hidden="true"></div>

        <div x-show="modalOpen"
             x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative flex max-h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2">

            {{-- هدر --}}
            <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                        <x-admin-panel::icon name="plus" class="size-4" />
                    </span>
                    افزودن مشخصات
                </h3>
                <button type="button" x-on:click="modalOpen = false" class="ap-btn-ghost !p-2" title="بستن">
                    <x-admin-panel::icon name="x" class="size-4.5" />
                </button>
            </div>

            {{-- جستجو --}}
            <div class="border-b border-line px-5 py-3.5 dark:border-night-line">
                <div class="relative">
                    <x-admin-panel::icon name="search" class="pointer-events-none absolute end-3.5 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    <input id="filterable-search" type="text" x-model="mq" class="ap-input !pe-10" placeholder="جستجوی مشخصات…" aria-label="جستجوی مشخصات">
                </div>
            </div>

            {{-- لیست — سه گروه مثل قدیمی --}}
            <div class="flex-1 space-y-5 overflow-y-auto p-5">
                @foreach ([
                    'static_filter'  => ['title' => 'فیلترهای ثابت', 'icon' => 'filter', 'items' => $this->staticFilters, 'nameKey' => 'title'],
                    'attributeGroup' => ['title' => 'گروه‌های ویژگی', 'icon' => 'layers', 'items' => $this->attributeGroups, 'nameKey' => 'name'],
                    'specification'  => ['title' => 'مشخصات', 'icon' => 'list', 'items' => $this->specifications, 'nameKey' => 'name'],
                ] as $type => $group)
                    @if ($group['items']->isNotEmpty())
                        <div wire:key="fm-group-{{ $type }}">
                            <h4 class="mb-2 flex items-center gap-1.5 text-[12px] font-bold text-ink-3 dark:text-night-ink-2">
                                <x-admin-panel::icon name="{{ $group['icon'] }}" class="size-3.5" />
                                {{ $group['title'] }}
                                <span class="fa-digits">({{ $group['items']->count() }})</span>
                            </h4>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach ($group['items'] as $item)
                                    @php($label = (string) ($item->{$group['nameKey']} ?? ''))
                                    <button type="button"
                                            wire:key="fm-item-{{ $type }}-{{ $item->id }}"
                                            x-show="match(@js($label), mq)"
                                            x-on:click="add({{ \Illuminate\Support\Js::from(['type' => $type, 'id' => $item->id, 'label' => $label])->toHtml() }})"
                                            class="flex items-center justify-between gap-2 rounded-xl border p-3 text-start transition-all hover:border-brand-400 hover:bg-brand-50/40 dark:hover:border-brand-500/40 dark:hover:bg-brand-500/5"
                                            x-bind:class="added({{ \Illuminate\Support\Js::from(['type' => $type, 'id' => $item->id])->toHtml() }})
                                                ? 'border-emerald-300 bg-emerald-50/60 dark:border-emerald-500/30 dark:bg-emerald-500/10'
                                                : 'border-line bg-surface dark:border-night-line dark:bg-night-3/50'">
                                        <span class="min-w-0">
                                            <span class="block truncate text-[13px] font-bold" x-bind:class="added({{ \Illuminate\Support\Js::from(['type' => $type, 'id' => $item->id])->toHtml() }}) ? 'text-emerald-700 dark:text-emerald-400' : ''">
                                                {{ $label }}
                                            </span>
                                            <span class="text-[10px] text-ink-3 dark:text-night-ink-2">{{ $group['title'] }}</span>
                                        </span>
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-lg transition-colors"
                                              x-bind:class="added({{ \Illuminate\Support\Js::from(['type' => $type, 'id' => $item->id])->toHtml() }})
                                                  ? 'bg-emerald-500 text-white'
                                                  : 'bg-brand-50 text-brand-600 group-hover:bg-brand-500 group-hover:text-white dark:bg-brand-500/12 dark:text-brand-400'">
                                            <x-admin-panel::icon name="check" class="size-4" x-show="added({{ \Illuminate\Support\Js::from(['type' => $type, 'id' => $item->id])->toHtml() }})" />
                                            <x-admin-panel::icon name="plus" class="size-4" x-show="! added({{ \Illuminate\Support\Js::from(['type' => $type, 'id' => $item->id])->toHtml() }})" />
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- فوتر --}}
            <div class="flex items-center justify-between gap-3 border-t border-line px-5 py-3.5 dark:border-night-line">
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">موردها به‌ترتیب افزودن، به انتهای فیلتر اضافه می‌شوند.</p>
                <button type="button" x-on:click="modalOpen = false" class="ap-btn-primary !py-2">اتمام و بستن</button>
            </div>
        </div>
    </div>
</div>
