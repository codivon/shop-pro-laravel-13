{{-- فرم ویرایش ویژگی — پنل جدید (فاز ۵) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-3xl space-y-6">

    <form wire:submit="save" class="space-y-6"
          x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- هدر --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="layers" class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-extrabold">ویرایش ویژگی: {{ $name ?: '—' }}</h2>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                        شناسه: <span class="fa-digits">{{ number_format($attribute->id) }}</span>
                        — گروه فعلی: {{ $attribute->group?->name ?? '—' }}
                    </p>
                </div>
            </div>
            <a href="{{ route('np.attribute-groups.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به گروه‌ها
            </a>
        </div>

        {{-- فرم --}}
        <div class="ap-card space-y-5 p-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="attribute-name">نام <span class="text-red-500">*</span></label>
                    <input id="attribute-name" type="text" wire:model="name" class="ap-input" placeholder="مثال: قرمز">
                    @error('name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="attribute-group">گروه ویژگی‌ها <span class="text-red-500">*</span></label>
                    <select id="attribute-group" wire:model.live="attributeGroupId" class="ap-input">
                        <option value="">انتخاب کنید</option>
                        @foreach ($this->groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->type === 'color' ? 'رنگ' : 'دکمه' }})</option>
                        @endforeach
                    </select>
                    @error('attributeGroupId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- فیلد رنگ — فقط وقتی گروه انتخاب‌شده از نوع color است (مثل legacy attributes/all.js) --}}
            @if ($this->selectedGroupType() === 'color')
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="attribute-value">مقدار (کد رنگ)</label>
                    <input id="attribute-value" type="color" wire:model="value"
                           class="h-11 w-full cursor-pointer rounded-xl border border-line bg-transparent p-1 dark:border-night-line">
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                        این گروه از نوع رنگ است؛ مقدار انتخابی به‌عنوان کد رنگ ویژگی ذخیره می‌شود.
                    </p>
                    @error('value') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>

        {{-- اکشن‌ها --}}
        <div class="flex flex-wrap items-center justify-end gap-2.5">
            <a href="{{ route('np.attribute-groups.index') }}" wire:navigate class="ap-btn-ghost">انصراف</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn">
                <x-admin-panel::icon name="check" class="size-4" />
                ذخیره تغییرات
            </button>
        </div>
    </form>

    {{-- لودینگ --}}
    <div wire:loading wire:target="save, attributeGroupId"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
