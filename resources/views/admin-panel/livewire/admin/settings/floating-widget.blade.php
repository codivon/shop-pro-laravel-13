{{-- ویجت شناور پشتیبانی — پنل جدید (فاز ۶) — پورت ۱:۱ back/settings/floating-widget.blade.php --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-4xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="flex items-center gap-2 text-base font-bold sm:text-lg">
            <x-admin-panel::icon name="life-buoy" class="size-5 text-brand-600 dark:text-brand-400" />
            ویجت شناور پشتیبانی
        </h1>
        <span class="ap-badge {{ $fw_enabled ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' : 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400' }}">
            <span class="size-2 rounded-full {{ $fw_enabled ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
            {{ $fw_enabled ? 'فعال' : 'غیرفعال' }}
        </span>
    </div>

    <form wire:submit="save" class="space-y-5">

        {{-- تنظیمات عمومی --}}
        <div class="ap-card p-4 sm:p-5">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="settings" class="size-4.5 text-brand-600 dark:text-brand-400" />
                تنظیمات عمومی
            </h2>

            <label class="mb-5 flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                <span>
                    <span class="block text-[13px] font-bold">فعال‌سازی ویجت</span>
                    <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">نمایش دکمه شناور در سایت</span>
                </span>
                <input type="checkbox" wire:model="fw_enabled"
                       class="size-5 cursor-pointer rounded border-line text-brand-600">
            </label>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="fw-main-color">رنگ اصلی دکمه</label>
                    <div class="flex items-center gap-2">
                        <span class="size-9 shrink-0 rounded-lg border border-line dark:border-night-line"
                              x-data x-bind:style="'background-color: ' + ($wire.fw_main_color || '#5b6af7')"></span>
                        <input id="fw-main-color" type="color" wire:model="fw_main_color"
                               class="h-10 w-full cursor-pointer rounded-xl border border-line bg-transparent p-1 dark:border-night-line">
                    </div>
                    @error('fw_main_color') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="fw-button-label">متن روی دکمه شناور</label>
                    <input id="fw-button-label" type="text" wire:model="fw_button_label" class="ap-input" placeholder="تماس با ما">
                    @error('fw_button_label') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="fw-greeting">پیام خوشامدگویی</label>
                    <input id="fw-greeting" type="text" wire:model="fw_greeting" class="ap-input" placeholder="سلام، چطور می‌تونم کمکتون کنم؟">
                    @error('fw_greeting') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="fw-sub-greeting">زیرعنوان پنل</label>
                    <input id="fw-sub-greeting" type="text" wire:model="fw_sub_greeting" class="ap-input" placeholder="تیم پشتیبانی ما آماده‌ی پاسخ‌گویی است">
                    @error('fw_sub_greeting') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold" for="fw-working-hours">ساعات کاری <span class="font-normal text-ink-3">(نمایش در تب گفتگو)</span></label>
                    <input id="fw-working-hours" type="text" wire:model="fw_working_hours" class="ap-input" placeholder="شنبه تا چهارشنبه ۹–۱۸">
                    @error('fw_working_hours') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- شبکه‌های اجتماعی + کانال‌های گفتگو (۶ شبکه × label + url) --}}
        <div class="ap-card p-4 sm:p-5">
            <h2 class="mb-1 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="share-2" class="size-4.5 text-brand-600 dark:text-brand-400" />
                شبکه‌های اجتماعی و کانال‌های گفتگو
            </h2>
            <p class="mb-4 text-xs text-ink-3 dark:text-night-ink-2">برچسب و لینک هر شبکه برای تب‌های «گفتگو» و «شبکه‌ها»</p>

            <div class="divide-y divide-line/70 dark:divide-night-line/70">
                @foreach ($this->socialNetworks() as $network => $meta)
                    <div class="grid grid-cols-1 items-center gap-3 py-4 sm:grid-cols-10">
                        <div class="sm:col-span-2">
                            <span class="flex items-center gap-2">
                                <span class="size-9 shrink-0 rounded-xl" style="background-color: {{ $meta['color'] }}"></span>
                                <span class="text-[13px] font-bold">{{ $meta['label'] }}</span>
                            </span>
                        </div>
                        <div class="sm:col-span-4">
                            <label class="mb-1.5 block text-xs font-semibold" for="fw-{{ $network }}-label">برچسب</label>
                            <input id="fw-{{ $network }}-label" type="text" wire:model="fw_{{ $network }}_label" class="ap-input" placeholder="پشتیبانی {{ $meta['label'] }}">
                            @error('fw_' . $network . '_label') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-4">
                            <label class="mb-1.5 block text-xs font-semibold" for="fw-{{ $network }}-url">لینک</label>
                            <input id="fw-{{ $network }}-url" type="url" dir="ltr" wire:model="fw_{{ $network }}_url" class="ap-input font-mono" placeholder="{{ $meta['placeholder_url'] }}">
                            @error('fw_' . $network . '_url') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- اطلاعات تماس --}}
        <div class="ap-card p-4 sm:p-5">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="phone" class="size-4.5 text-brand-600 dark:text-brand-400" />
                اطلاعات تماس
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="fw-phone">شماره تماس</label>
                    <input id="fw-phone" type="tel" dir="ltr" wire:model="fw_phone" class="ap-input" placeholder="021-12345678">
                    @error('fw_phone') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="fw-email">ایمیل</label>
                    <input id="fw-email" type="email" dir="ltr" wire:model="fw_email" class="ap-input" placeholder="info@example.com">
                    @error('fw_email') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold" for="fw-address">آدرس</label>
                    <textarea id="fw-address" rows="2" wire:model="fw_address" class="ap-input" placeholder="تهران، خیابان ..."></textarea>
                    @error('fw_address') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ذخیره --}}
        <div class="flex items-center justify-start gap-3">
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn">
                <x-admin-panel::icon name="check" class="size-4" />
                ذخیره تنظیمات
            </button>
        </div>
    </form>
</div>
