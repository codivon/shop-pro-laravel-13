{{-- تنظیمات پیام تبریک تولد — پورت back/messages/birthday.blade.php — پنل جدید (فاز ۷) --}}
<div class="mx-auto max-w-3xl space-y-5">

    {{-- هدر --}}
    <div class="ap-card p-5">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('np.messages.index') }}" wire:navigate
               class="rounded-xl p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3"
               aria-label="بازگشت به ارسال پیام">
                <x-admin-panel::icon name="arrow-right" class="size-5" />
            </a>
            <div>
                <h2 class="text-base font-extrabold">تنظیمات پیام تبریک تولد</h2>
                <p class="mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">پیام تبریک برای کاربرانی که تاریخ تولد و موبایل ثبت‌شده دارند ارسال می‌شود.</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="ap-card space-y-5 p-5 sm:p-6">
        <div class="flex items-start gap-3 rounded-xl border border-line bg-surface-2 p-4 dark:border-night-line dark:bg-night-3">
            <label class="flex flex-1 cursor-pointer items-center gap-2.5" title="در صورت غیرفعال بودن، پیام ارسال نخواهد شد">
                <input type="checkbox" wire:model="user_birthday_sms_send"
                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                <span class="text-[13px] font-semibold">ارسال پیام تبریک تولد</span>
            </label>
            @error('user_birthday_sms_send') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold" for="birthday-pattern">کد پترن تبریک تولد <span class="text-red-500">*</span></label>
            <input id="birthday-pattern" type="text" wire:model="user_birthday_pattern_code" dir="ltr"
                   class="ap-input font-mono" placeholder="مثلاً: birthday-12345">
            @error('user_birthday_pattern_code') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold">متن نمونه ایجاد پترن</label>
            <textarea readonly rows="4" class="ap-input cursor-default bg-surface-2 opacity-80 dark:bg-night-3">%fullname% عزیز زندگی بسیار کوتاه است از هر لحظه آن لذت ببرید و با تکیه بر تجربه های سال های گذشته سال های آتی زندگی را به بهترین شکل ممکن بگذرانید تولدتان مبارک.
{{ option('info_site_title') }}</textarea>
        </div>

        <div>
            <h3 class="mb-1.5 text-[13px] font-bold">پیام تبریک به این کاربران ارسال می‌شود</h3>
            <p class="mb-3 text-[11px] text-ink-3 dark:text-night-ink-2">
                با ذخیره، اعلان همه‌ی کاربران تولددار خاموش و فقط کاربران انتخاب‌شده فعال می‌شود (پورت birthdayStore قدیمی).
            </p>

            @include('admin-panel::livewire.admin.discounts.partials.user-picker', [
                'label' => 'جستجوی کاربران تولددار (نام، نام کاربری یا موبایل…)',
                'searchField' => 'usersSearch',
                'resultsField' => 'usersResults',
                'results' => $usersResults,
                'selected' => $usersSelected,
                'addAction' => 'addUser',
                'removeAction' => 'removeUser',
            ])

            <div wire:loading wire:target="usersSearch" class="mt-2 flex items-center gap-2 text-xs font-semibold text-brand-600 dark:text-brand-400">
                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                </svg>
                در حال جستجو…
            </div>
        </div>

        <div class="flex items-center justify-end border-t border-line pt-4 dark:border-night-line">
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn">
                <x-admin-panel::icon name="check" class="size-4" />
                ذخیره تنظیمات
            </button>
        </div>
    </form>
</div>
