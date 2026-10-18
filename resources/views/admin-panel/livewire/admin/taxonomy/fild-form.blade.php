{{-- ویرایش فیلد اختصاصی — پنل جدید (فاز ۵) | پورت back/filds/edit --}}
<div class="mx-auto max-w-4xl">
    <form wire:submit="save" class="space-y-6" x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- هدر --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="list" class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-extrabold">ویرایش فیلد: {{ $title ?: '—' }}</h2>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                        شناسه: <span class="fa-digits">{{ number_format($fild->id) }}</span>
                    </p>
                </div>
            </div>
            <a href="{{ route('np.filds.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به لیست
            </a>
        </div>

        {{-- فرم --}}
        <div class="ap-card space-y-5 p-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="fildf-belongs">فیلد متعلق است به</label>
                    <select id="fildf-belongs" wire:model="belongsTo" class="ap-input !py-2">
                        @foreach (\App\Livewire\Admin\Taxonomy\FildForm::BELONGS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('belongsTo') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="fildf-type">نوع فیلد</label>
                    <select id="fildf-type" wire:model.live="type" class="ap-input !py-2">
                        @foreach (\App\Livewire\Admin\Taxonomy\FildForm::TYPES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="fildf-title">عنوان</label>
                    <input id="fildf-title" type="text" wire:model="title" class="ap-input" placeholder="مثال: رنگ دلخواه">
                    @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="fildf-value">مقدار پیش‌فرض (اختیاری)</label>
                    <input id="fildf-value" type="text" wire:model="value" class="ap-input" placeholder="مقدار پیش‌فرض…">
                    @error('value') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- مقادیر select — فقط برای نوع «انتخابی» --}}
            <div x-show="$wire.type === 'select'" x-cloak>
                <label class="mb-1.5 block text-xs font-semibold" for="fildf-select-options">مقادیر گزینه‌ها (با کاما جدا شود)</label>
                <textarea id="fildf-select-options" rows="2" wire:model="selectOptions" class="ap-input" placeholder="مثلا: قرمز, آبی, سبز"></textarea>
                @error('selectOptions') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2.5 text-[13px] transition-colors hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                    <input type="checkbox" wire:model="published"
                           class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                    انتشار
                </label>
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2.5 text-[13px] transition-colors hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                    <input type="checkbox" wire:model="required"
                           class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                    اجباری
                </label>
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2.5 text-[13px] transition-colors hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                    <input type="checkbox" wire:model="userShow"
                           class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                    نمایش به کاربر
                </label>
            </div>
        </div>

        {{-- نوار ذخیره (چسبان) --}}
        <div class="ap-card sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 p-4 shadow-pop sm:p-5">
            <p class="text-[12px] text-ink-3 dark:text-night-ink-2">تغییر «بخش متعلق» پس از ثبت مقادیر ممکن است مقادیر قبلی را بدون مرجع بگذارد.</p>
            <div class="flex items-center gap-2">
                <a href="{{ route('np.filds.index') }}" wire:navigate class="ap-btn-ghost !py-2">انصراف</a>
                <button type="submit" class="ap-btn-primary !py-2.5" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">ذخیره تغییرات</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                        در حال ذخیره…
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
