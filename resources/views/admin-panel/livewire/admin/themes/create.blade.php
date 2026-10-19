{{-- آپلود قالب جدید — پورت back/themes/create.blade.php (فاز ۷-ج۴) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-3xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('np.themes.index') }}" class="ap-btn-ghost !p-2" title="بازگشت به لیست قالب‌ها">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
            </a>
            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="upload" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">افزودن قالب جدید</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">فایل zip قالب را آپلود کنید</p>
            </div>
        </div>
    </div>

    {{-- فرم آپلود (پورت store قدیمی) --}}
    <form wire:submit="store" class="ap-card p-5">
        <label class="mb-1.5 block text-xs font-semibold">انتخاب فایل قالب <span class="text-red-500">*</span></label>
        <input type="file" accept=".zip" wire:model="file"
               class="block w-full cursor-pointer rounded-xl border border-line bg-transparent text-xs text-ink-2
                      file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5
                      file:text-xs file:font-semibold file:text-white hover:file:bg-brand-700 dark:border-night-line">
        @error('file') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

        <p class="mt-3 flex items-start gap-2 rounded-xl bg-sky-50 p-3 text-[11px] leading-5 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400">
            <x-admin-panel::icon name="info" class="mt-0.5 size-3.5 shrink-0" />
            پس از آپلود، قالب استخراج، نصب و به‌عنوان قالب فعال تنظیم می‌شود (پورت دقیق رفتار قدیمی: dump-autoload، publish و shop:link خودکار اجرا می‌شود).
        </p>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" wire:loading.attr="disabled" wire:target="store" class="ap-btn !py-2">
                <x-admin-panel::icon name="plus" class="size-4" />
                افزودن قالب
            </button>
            <span wire:loading wire:target="store" class="flex items-center gap-2 text-xs text-ink-3 dark:text-night-ink-2">
                <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                </svg>
                در حال پردازش قالب… (ممکن است چند ثانیه طول بکشد)
            </span>
        </div>
    </form>
</div>
