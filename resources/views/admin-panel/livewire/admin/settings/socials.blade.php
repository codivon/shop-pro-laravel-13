{{-- شبکه‌های اجتماعی — پنل جدید (فاز ۷-ج۳) — پورت back/settings/socials.blade.php --}}
<div class="mx-auto max-w-4xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="share-2" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-bold">شبکه‌های اجتماعی</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">لینک شبکه‌های اجتماعی سایت (نمایش در فوتر و صفحات تماس)</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-5">
        <div class="ap-card p-4 sm:p-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @forelse ($this->socialsList() as $social)
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="social-{{ $social['key'] }}">{{ $social['name'] }}</label>
                        <input id="social-{{ $social['key'] }}" type="text" dir="ltr"
                               wire:model="values.{{ $social['key'] }}"
                               class="ap-input font-mono"
                               placeholder="https://{{ str_contains($social['key'], 'telegram') ? 't.me/username' : (str_contains($social['key'], 'instagram') ? 'instagram.com/username' : (str_contains($social['key'], 'whatsapp') ? 'wa.me/98912...' : 'site.ir')) }}">
                    </div>
                @empty
                    <p class="text-sm text-ink-3">هیچ شبکه‌ی اجتماعی در تنظیمات تم تعریف نشده است.</p>
                @endforelse
            </div>
        </div>

        <div class="sticky bottom-4 z-10 flex items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-lg backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">لینک‌ها با https وارد شوند.</span>
            <button type="submit" class="ap-btn-primary !py-2.5">
                <x-admin-panel::icon name="check-circle" class="size-4" />
                ذخیره تغییرات
            </button>
        </div>
    </form>
</div>
