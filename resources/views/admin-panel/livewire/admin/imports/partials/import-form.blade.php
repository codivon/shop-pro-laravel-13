{{-- فرم درون‌ریزی: فهرست فیلدها + گزینه‌ها + آپلود — فاز ۱۹ --}}
{{-- متغیرها: $fields، $filters، $label، $showWarehouse (فقط محصولات) --}}
<form wire:submit="store" class="space-y-5">

    {{-- کارت فیلدها --}}
    <div class="ap-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3.5 dark:border-night-line">
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="list-checks" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلدهای فایل اکسل
            </h2>
            <div class="flex items-center gap-1.5">
                <span class="fa-digits ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                    {{ number_format($this->activeFieldsCount) }} از {{ number_format(count($fields)) }}
                </span>
                <button type="button" wire:click="checkAllFilters" wire:loading.attr="disabled" wire:target="checkAllFilters, uncheckAllFilters"
                        class="rounded-lg bg-surface-2 px-2.5 py-1.5 text-[11px] font-bold text-ink-2 transition-colors hover:bg-surface-3 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-night-3/70">
                    انتخاب همه
                </button>
                <button type="button" wire:click="uncheckAllFilters" wire:loading.attr="disabled" wire:target="checkAllFilters, uncheckAllFilters"
                        class="rounded-lg bg-surface-2 px-2.5 py-1.5 text-[11px] font-bold text-ink-2 transition-colors hover:bg-surface-3 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-night-3/70">
                    هیچکدام
                </button>
            </div>
        </div>

        <ul class="max-h-96 overflow-y-auto p-3 pe-2">
            @foreach ($fields as $key => $field)
                <li wire:key="field-{{ $key }}" class="mb-1.5 last:mb-0">
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line px-3 py-2 text-[12.5px] transition-all hover:border-brand-300 hover:bg-brand-50/40 has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50 dark:border-night-line dark:hover:border-brand-500/40 dark:hover:bg-brand-500/5 dark:has-[:checked]:border-brand-500/50 dark:has-[:checked]:bg-brand-500/10 {{ ($field['required'] ?? false) ? 'opacity-90' : '' }}">
                        <input type="checkbox" value="1" wire:model.live="filters.{{ $key }}"
                               @if ($field['required'] ?? false) disabled checked @endif
                               class="size-4 shrink-0 rounded border-line text-brand-600 focus:ring-brand-500 dark:border-night-line">
                        <span class="flex-1 font-semibold">{{ $field['label'] }}</span>
                        @if ($field['required'] ?? false)
                            <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400 !text-[9.5px]">الزامی</span>
                        @endif
                    </label>
                </li>
            @endforeach
        </ul>

        <div class="flex items-start gap-2 border-t border-line bg-amber-50/60 px-4 py-3 text-[11px] leading-5 text-amber-700 dark:border-night-line dark:bg-amber-500/10 dark:text-amber-400">
            <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-3.5 shrink-0" />
            <span>ترتیب ستون‌های فایل اکسل شما باید دقیقاً با ترتیب فیلدهای بالا یکسان باشد و ردیف اول عناوین ستون‌ها باشد.</span>
        </div>
    </div>

    {{-- گزینه‌ها --}}
    <div class="ap-card space-y-3.5 p-4">
        @if (! empty($showWarehouse))
            <div>
                <label class="mb-1.5 flex items-center gap-1.5 text-xs font-bold">
                    <x-admin-panel::icon name="warehouse" class="size-3.5 text-brand-600" />
                    انبار مقصد <span class="text-red-500">*</span>
                </label>
                <select wire:model="warehouseId" class="ap-input !py-2">
                    <option value="">انتخاب انبار…</option>
                    @foreach ($this->warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
                @error('warehouseId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <label class="flex cursor-pointer items-start gap-2.5 rounded-xl bg-surface-2 px-3 py-2.5 text-[12.5px] dark:bg-night-3">
            <input type="checkbox" value="1" wire:model="updateDuplicate"
                   class="mt-0.5 size-4 shrink-0 rounded border-line text-brand-600 focus:ring-brand-500 dark:border-night-line">
            <span>
                <span class="block font-bold">بروزرسانی موارد تکراری</span>
                <span class="mt-0.5 block text-[11px] font-normal text-ink-3 dark:text-night-ink-2">در صورت تکرار ردیف، اطلاعات موجود به‌روزرسانی شود</span>
            </span>
        </label>
    </div>

    {{-- آپلود فایل --}}
    <div class="ap-card p-4">
        <label class="mb-1.5 flex items-center gap-1.5 text-xs font-bold">
            <x-admin-panel::icon name="file-up" class="size-3.5 text-brand-600" />
            فایل Excel <span class="text-red-500">*</span>
        </label>

        <div x-data="{
                    dragging: false,
                    drop(e) {
                        this.dragging = false;
                        const files = e.dataTransfer?.files;
                        if (! files?.length) { return; }
                        const input = this.$refs.fileInput;
                        input.files = files;
                        input.dispatchEvent(new Event('change'));
                    }
                }"
             x-on:dragover.prevent="dragging = true"
             x-on:dragleave.prevent="if (! $event.relatedTarget || ! $el.contains($event.relatedTarget)) { dragging = false; }"
             x-on:drop.prevent="drop($event)"
             x-on:click="$refs.fileInput.click()"
             role="button" tabindex="0"
             x-on:keydown.enter.prevent="$refs.fileInput.click()"
             :aria-label="'انتخاب فایل اکسل'"
             class="relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed p-6 text-center transition-all duration-200"
             :class="dragging
                        ? 'border-brand-500 bg-brand-50/70 scale-[1.01] dark:bg-brand-500/10'
                        : 'border-line bg-surface-2/50 hover:border-brand-500/60 hover:bg-brand-50/30 dark:border-night-line dark:bg-night-3/40 dark:hover:bg-brand-500/5'">

            <input type="file" accept=".xlsx,.csv" wire:model="file" x-ref="fileInput" class="sr-only" aria-label="فایل اکسل">

            <span class="flex size-12 items-center justify-center rounded-2xl bg-linear-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-500/25 transition-transform duration-200"
                  :class="dragging ? 'scale-110' : ''">
                <x-admin-panel::icon name="upload" class="size-6" />
            </span>

            <p class="text-[13px] font-bold">
                فایل اکسل را اینجا رها کنید یا <span class="text-brand-600 underline decoration-brand-300 underline-offset-4 dark:text-brand-400">انتخاب کنید</span>
            </p>
            <p class="text-[10.5px] text-ink-3 dark:text-night-ink-2">
                فرمت‌های مجاز: <code class="ltr rounded bg-surface-3 px-1.5 py-0.5 font-bold dark:bg-night-3">.xlsx</code> و <code class="ltr rounded bg-surface-3 px-1.5 py-0.5 font-bold dark:bg-night-3">.csv</code>
            </p>

            @if ($file)
                <p class="flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/70 px-3 py-1.5 text-[11px] font-bold text-brand-700 dark:border-brand-500/25 dark:bg-brand-500/10 dark:text-brand-400">
                    <x-admin-panel::icon name="file-spreadsheet" class="size-3.5 shrink-0" />
                    <span class="ltr truncate">{{ $file->getClientOriginalName() }}</span>
                </p>
            @endif
        </div>

        @error('file') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        @error('filters.title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        @error('filters.email') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        @error('filters.mobile') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        @error('filters.password') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

        <button type="submit" wire:loading.attr="disabled" wire:target="store" class="ap-btn mt-4 w-full !py-2.5">
            <span wire:loading.remove wire:target="store" class="flex items-center justify-center gap-2">
                <x-admin-panel::icon name="cloud-upload" class="size-4" />
                افزودن فایل و شروع درون‌ریزی {{ $label }}
            </span>
            <span wire:loading wire:target="store" class="flex items-center justify-center gap-2">
                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                </svg>
                در حال آپلود و صف‌گذاری…
            </span>
        </button>

        <p class="mt-2.5 flex items-center justify-center gap-1.5 text-[10.5px] text-ink-3 dark:text-night-ink-2">
            <x-admin-panel::icon name="info" class="size-3" />
            فایل در صف پردازش قرار می‌گیرد و نتیجه به‌صورت اعلان اطلاع داده می‌شود
        </p>
    </div>
</form>
