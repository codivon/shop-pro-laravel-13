{{-- فرم ویرایش برند — پنل جدید (فاز ۵) --}}
<div class="mx-auto max-w-3xl space-y-6">

    <form wire:submit="save" class="space-y-6"
          x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- هدر --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="tags" class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-extrabold">ویرایش برند: {{ $name ?: '—' }}</h2>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                        شناسه: <span class="fa-digits">{{ number_format($brand->id) }}</span>
                        — اسلاگ فعلی: <code class="text-[10px]" dir="ltr">{{ $brand->slug ?? '—' }}</code>
                    </p>
                </div>
            </div>
            <a href="{{ route('np.brands.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به لیست
            </a>
        </div>

        {{-- فرم --}}
        <div class="ap-card space-y-5 p-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="brand-name">نام <span class="text-red-500">*</span></label>
                    <input id="brand-name" type="text" wire:model="name" class="ap-input" placeholder="مثال: سامسونگ">
                    @error('name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="brand-slug">آدرس (slug) <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input id="brand-slug" type="text" wire:model="slug" dir="ltr"
                               class="ap-input font-mono" placeholder="samsung">
                        <button type="button" wire:click="generateSlug" class="ap-btn-outline shrink-0 !px-3"
                                title="تولید خودکار از نام">
                            <x-admin-panel::icon name="wand-2" class="size-4" />
                        </button>
                    </div>
                    @error('slug') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold" for="brand-description">توضیحات</label>
                <textarea id="brand-description" wire:model="description" rows="4" class="ap-input" placeholder="توضیح اختیاری برند…"></textarea>
                @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- تصویر فعلی + جایگزینی --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <p class="mb-1.5 text-xs font-semibold">تصویر فعلی</p>
                    <img src="{{ $brand->image ? asset($brand->image) : asset('/empty.svg') }}"
                         alt="{{ $brand->name }}"
                         class="h-24 w-24 rounded-2xl border border-line object-cover dark:border-night-line">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="brand-image">تصویر جدید (اختیاری)</label>
                    <input id="brand-image" type="file" accept="image/*" wire:model="image"
                           class="block w-full cursor-pointer rounded-xl border border-line bg-transparent text-xs text-ink-2
                                  file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5
                                  file:text-xs file:font-semibold file:text-white hover:file:bg-brand-700 dark:border-night-line">
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">در صورت انتخاب، تصویر قبلی حذف و این تصویر جایگزین می‌شود.</p>
                    @error('image') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    @if ($image)
                        <button type="button" wire:click="$set('image', null)" class="mt-2 flex items-center gap-1 text-xs font-semibold text-red-500 hover:underline">
                            <x-admin-panel::icon name="x" class="size-3.5" />
                            حذف فایل انتخاب‌شده
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- اکشن‌ها --}}
        <div class="flex flex-wrap items-center justify-end gap-2.5">
            <a href="{{ route('np.brands.index') }}" wire:navigate class="ap-btn-ghost">انصراف</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn">
                <x-admin-panel::icon name="check" class="size-4" />
                ذخیره تغییرات
            </button>
        </div>
    </form>

    {{-- لودینگ --}}
    <div wire:loading wire:target="save, generateSlug, image"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
