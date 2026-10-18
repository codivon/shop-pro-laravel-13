{{-- ایجاد تراکنش کیف پول — پورت back/wallets/create.blade.php --}}
<div class="mx-auto max-w-3xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="plus" class="size-5" />
            </span>
            <div>
                <h1 class="text-base font-bold">ایجاد تراکنش کیف پول</h1>
                <p class="mt-0.5 text-[13px] text-ink-3 dark:text-night-ink-2">
                    موجودی فعلی: <span class="fa-digits font-bold text-ink-2 dark:text-night-ink-2">{{ number_format($wallet->balance) }}</span> تومان
                </p>
            </div>
        </div>
        <a href="{{ route('np.wallets.show', ['wallet' => $wallet->id]) }}" wire:navigate class="ap-btn-outline !py-2 text-[13px]">
            <x-admin-panel::icon name="arrow-left" class="size-4" />
            بازگشت به کیف پول
        </a>
    </div>

    {{-- فرم (پورت فرم قدیمی: amount/type/description) --}}
    <form wire:submit="save" class="ap-card p-5 sm:p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-semibold">مبلغ <span class="text-red-500">*</span></label>
                <input type="number" wire:model="amount" min="0"
                       class="ap-input fa-digits @error('amount') !border-red-400 @enderror"
                       placeholder="مثلاً ۵۰۰۰۰۰">
                @error('amount')
                    <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">نوع عملیات <span class="text-red-500">*</span></label>
                <select wire:model="type" class="ap-input @error('type') !border-red-400 @enderror">
                    <option value="deposit">افزایش اعتبار</option>
                    <option value="withdraw">کاهش اعتبار</option>
                </select>
                @error('type')
                    <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold">توضیحات</label>
                <textarea wire:model="description" rows="3"
                          class="ap-input @error('description') !border-red-400 @enderror"
                          placeholder="توضیحات تراکنش…"></textarea>
                @error('description')
                    <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-5 flex items-center justify-end gap-2.5 border-t border-line pt-4 dark:border-night-line">
            <a href="{{ route('np.wallets.show', ['wallet' => $wallet->id]) }}" wire:navigate class="ap-btn-ghost">
                انصراف
            </a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="ap-btn-primary">
                <x-admin-panel::icon name="check" class="size-4" />
                ایجاد تراکنش
            </button>
        </div>
    </form>

    {{-- راهنما --}}
    <div class="ap-card flex items-start gap-3 p-4">
        <x-admin-panel::icon name="info" class="mt-0.5 size-4.5 shrink-0 text-brand-600 dark:text-brand-400" />
        <p class="text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
            تراکنش با وضعیت «موفق» ثبت و موجودی کیف پول به‌صورت خودکار بروزرسانی می‌شود.
            در حالت «کاهش اعتبار»، مبلغ نمی‌تواند از موجودی فعلی بیشتر باشد.
        </p>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="save"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال ذخیره…</span>
    </div>
</div>
