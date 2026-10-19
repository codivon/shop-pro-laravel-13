{{-- تنظیمات فروشندگان — قرارداد الکترونیک (فاز ۳) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-5xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="file-signature" class="size-5" />
            </span>
            <div>
                <h1 class="text-base font-black">متن قرارداد فروشنده</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">قرارداد الکترونیکی که فروشنده هنگام ثبت‌نام می‌پذیرد</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-4">
        <div class="ap-card p-5 sm:p-6">
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">سرصفحه قرارداد</label>
                    <textarea wire:model="header" rows="3" class="ap-input"></textarea>
                    @error('header') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">متن اصلی قرارداد <span class="text-red-500">*</span></label>
                    <textarea wire:model="content" rows="14" class="ap-input min-h-72"></textarea>
                    @error('content') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn-primary">
                <x-admin-panel::icon name="check" class="size-4" />
                ذخیره قرارداد
            </button>
        </div>
    </form>
</div>
