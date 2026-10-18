{{-- فرم ایجاد/ویرایش صفحه — پنل جدید (فاز ۶) — پورت ۱:۱ PageController@store/update --}}
@php
    $isEdit = $editing;
@endphp

<div class="mx-auto max-w-5xl">

    <form wire:submit="{{ $isEdit ? 'update' : 'store' }}" class="space-y-6"
          x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- هدر --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="file-text" class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-extrabold">{{ $isEdit ? 'ویرایش صفحه: ' . $title : 'ایجاد صفحه جدید' }}</h2>
                    @if ($isEdit)
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                            اسلاگ فعلی: <code class="text-[10px]" dir="ltr">{{ $page->slug }}</code>
                        </p>
                    @endif
                </div>
            </div>
            <a href="{{ route('np.pages.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به صفحات
            </a>
        </div>

        {{-- فیلدها --}}
        <div class="ap-card space-y-5 p-5 sm:p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="page-title">عنوان <span class="text-red-500">*</span></label>
                    <input id="page-title" type="text" wire:model="title" class="ap-input" placeholder="عنوان صفحه…">
                    @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="page-slug">آدرس (اسلاگ)</label>
                    <input id="page-slug" type="text" wire:model="slug" dir="ltr"
                           class="ap-input font-mono !text-xs" placeholder="خالی = از عنوان ساخته می‌شود">
                    @error('slug') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    @if ($isEdit)
                        <p class="mt-1.5 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                            با تغییر اسلاگ، لینک «/pages/…» در منوها و لینک‌های فوتر به‌صورت خودکار به‌روزرسانی می‌شود.
                        </p>
                    @endif
                </div>

                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="page-meta-title">عنوان سئو</label>
                    <input id="page-meta-title" type="text" wire:model="meta_title" class="ap-input"
                           placeholder="عنوان سئو…" @disabled($isEdit)>
                    @if ($isEdit)
                        <p class="mt-1.5 text-[11px] text-amber-600 dark:text-amber-400">
                            مثل پنل قدیمی، متا فقط هنگام ایجاد ذخیره می‌شود و در ویرایش به‌روزرسانی نمی‌شود.
                        </p>
                    @endif
                    @error('meta_title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="page-meta-description">توضیحات سئو</label>
                    <textarea id="page-meta-description" wire:model="meta_description" rows="3" class="ap-input"
                              placeholder="توضیحات سئو…" @disabled($isEdit)></textarea>
                    @error('meta_description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- محتوا (ویرایشگر CKEditor — پل ApEditor پنل) --}}
            <div>
                <label class="mb-1.5 block text-[13px] font-semibold">محتوا <span class="text-red-500">*</span></label>
                <div class="overflow-hidden rounded-xl border border-line dark:border-night-line" wire:ignore>
                    <div id="ap-ckeditor" data-editor-value="{{ $description }}"></div>
                </div>
                <input type="hidden" wire:model="description" id="ap-ckeditor-input">
                @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- نوار ذخیره --}}
        <div class="ap-card sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 p-4 shadow-pop sm:p-5">
            <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                <input type="checkbox" wire:model="published"
                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                انتشار صفحه؟
            </label>
            <div class="flex items-center gap-2">
                <a href="{{ route('np.pages.index') }}" wire:navigate class="ap-btn-ghost !py-2">انصراف</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="{{ $isEdit ? 'update' : 'store' }}"
                        class="ap-btn-primary !py-2.5">
                    <x-admin-panel::icon name="check" class="size-4" />
                    {{ $isEdit ? 'ذخیره تغییرات' : 'ایجاد صفحه' }}
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // رندر مجدد راه‌اندازی CKEditor بعد از هر بار لود صفحه (wire:navigate سازگار)
    document.addEventListener('DOMContentLoaded', () => window.ApEditor?.init());
    document.addEventListener('livewire:navigated', () => window.ApEditor?.init());
    window.ApEditor?.init();
</script>
@endpush
