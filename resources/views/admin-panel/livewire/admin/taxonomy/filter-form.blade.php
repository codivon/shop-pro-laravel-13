{{-- فرم ایجاد/ویرایش فیلتر — پنل جدید (فاز ۵) | پورت back/filters/create + edit + partials --}}
<div class="mx-auto max-w-5xl">
    <form wire:submit="save" class="space-y-6" x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- هدر --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="filter" class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-extrabold">
                        {{ $isEditing ? 'ویرایش فیلتر: ' . ($title ?: '—') : 'ایجاد فیلتر جدید' }}
                    </h2>
                    @if ($isEditing)
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                            شناسه: <span class="fa-digits">{{ number_format($filterId) }}</span>
                        </p>
                    @endif
                </div>
            </div>
            <a href="{{ route('np.filters.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به لیست
            </a>
        </div>

        {{-- اطلاعات پایه --}}
        <div class="ap-card space-y-5 p-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="filter-title">عنوان فیلتر</label>
                    <input id="filter-title" type="text" wire:model="title" class="ap-input" placeholder="مثال: فیلتر محصولات پرطرفدار">
                    @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                {{-- چک‌باکس فیلتر فروشنده — در ایجاد اگر فیلتر فروشنده موجود باشد مخفی می‌شود (مثل قدیمی)؛
                     در ویرایش همیشه نمایش داده می‌شود (حتی برای فیلتر فروشنده، قابل برداشتن است) --}}
                <div>
                    @if (! $isEditing && $this->sellerFilterExists)
                        <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-[12px] leading-5 text-amber-700 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-400">
                            فیلتر فروشنده از قبل تعریف شده است؛ امکان تعریف فیلتر فروشنده دوم وجود ندارد.
                        </p>
                    @else
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2.5 text-[13px] font-semibold transition-colors hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                            <input type="checkbox" wire:model="isSeller"
                                   class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            برای صفحه فروشندگان؟
                        </label>
                    @endif
                    @error('filter') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- سازنده فیلترپذیرها --}}
        <div class="ap-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line bg-surface-2 px-5 py-3 dark:border-night-line dark:bg-night-3/50">
                <p class="text-sm font-bold">موارد فیلترپذیر</p>
                <button type="button" wire:click="addRow" class="ap-btn-outline !py-1.5 text-[13px]">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن مورد
                </button>
            </div>
            @error('rows') <p class="px-5 pt-3 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

            <div class="space-y-3 p-4 sm:p-5">
                @forelse ($rows as $i => $row)
                    @php
                        $source = match ($row['type']) {
                            'static_filter'  => $this->staticFilters,
                            'attributeGroup' => $this->attributeGroups,
                            default          => $this->specifications,
                        };
                    @endphp
                    <div class="rounded-2xl border border-line p-3.5 dark:border-night-line" wire:key="filter-row-{{ $i }}">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-12">
                            <div class="sm:col-span-3">
                                <label class="mb-1.5 block text-xs font-semibold" for="row-type-{{ $i }}">نوع</label>
                                <select id="row-type-{{ $i }}" wire:model.live="rows.{{ $i }}.type" class="ap-input !py-2">
                                    @foreach (\App\Livewire\Admin\Taxonomy\FilterForm::ROW_TYPES as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error("rows.{$i}.type") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-4">
                                <label class="mb-1.5 block text-xs font-semibold" for="row-id-{{ $i }}">
                                    مورد
                                    @if ($row['type'] === 'static_filter') (فیلتر ثابت) @elseif ($row['type'] === 'attributeGroup') (گروه ویژگی) @else (مشخصات) @endif
                                </label>
                                <select id="row-id-{{ $i }}" wire:model="rows.{{ $i }}.id" class="ap-input !py-2">
                                    <option value="">انتخاب کنید…</option>
                                    @foreach ($source as $option)
                                        <option value="{{ $option->id }}">{{ $option->title ?? $option->name }}</option>
                                    @endforeach
                                </select>
                                @if ($row['label'] && $row['id'] && ! $source->firstWhere('id', $row['id']))
                                    <p class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                                        مورد فعلی: «{{ $row['label'] }}» — در فهرست حاضر یافت نشد.
                                    </p>
                                @endif
                                @error("rows.{$i}.id") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold">وضعیت</label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2 text-[13px] transition-colors hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                                    <input type="checkbox" wire:model="rows.{{ $i }}.active"
                                           class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                                    فعال
                                </label>
                            </div>
                            <div class="sm:col-span-3" x-show="$wire.rows[{{ $i }}].type === 'specification'" x-cloak>
                                <label class="mb-1.5 block text-xs font-semibold" for="row-sep-{{ $i }}">جداکننده (اختیاری)</label>
                                <input id="row-sep-{{ $i }}" type="text" wire:model="rows.{{ $i }}.separator" class="ap-input !py-2" placeholder="مثلا: / یا ،">
                                @error("rows.{$i}.separator") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-end gap-0.5 border-t border-line/60 pt-2.5 dark:border-night-line/60">
                            <button type="button" wire:click="moveRowUp({{ $i }})"
                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 disabled:opacity-30 dark:hover:bg-brand-500/10"
                                    @if ($i === 0) disabled @endif title="انتقال به بالا" aria-label="انتقال به بالا">
                                <x-admin-panel::icon name="chevron-up" class="size-4" />
                            </button>
                            <button type="button" wire:click="moveRowDown({{ $i }})"
                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 disabled:opacity-30 dark:hover:bg-brand-500/10"
                                    @if ($i === count($rows) - 1) disabled @endif title="انتقال به پایین" aria-label="انتقال به پایین">
                                <x-admin-panel::icon name="chevron-down" class="size-4" />
                            </button>
                            <button type="button" wire:click="removeRow({{ $i }})"
                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                    title="حذف مورد" aria-label="حذف مورد">
                                <x-admin-panel::icon name="trash-2" class="size-4" />
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center gap-2 py-8 text-ink-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="layers" class="size-8 opacity-40" />
                        <p class="text-sm font-medium">هنوز موردی اضافه نشده است</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- نوار ذخیره (چسبان) --}}
        <div class="ap-card sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 p-4 shadow-pop sm:p-5">
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
</div>
