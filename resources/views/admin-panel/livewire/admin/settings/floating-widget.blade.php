{{-- ویجت شناور پشتیبانی — فاز ۱۸: بازطراحی کامل + پیش‌نمایش زنده
     پورت ۱:۱ کلیدهای fw_* (FloatingWidget) — پیش‌نمایش، کپی رندر فرانت تم WeblakShop است.
     نکته‌ی کارایی (فاز ۱۷-د): ست‌های $wire.set(..., false) یعنی بدون رفت‌وبرگشت سرور؛
     فقط دکمه‌ی ذخیره رفت‌وبرگشت دارد. --}}
<div class="mx-auto max-w-7xl space-y-5"
     x-data="{
        pvOpen: false,
        colors: ['#5b6af7', '#0d9488', '#10b981', '#f59e0b', '#ef4444', '#111827', '#2AABEE', '#25D366'],
     }"
     x-init="setTimeout(() => pvOpen = true, 800)">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="life-buoy" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-bold">ویجت شناور پشتیبانی</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">دکمه‌ی شناور گفتگو، شبکه‌های اجتماعی و اطلاعات تماس در سایت</p>
            </div>
        </div>
        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border px-3.5 py-2.5 transition-colors"
              :class="$wire.fw_enabled ? 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-500/30 dark:bg-emerald-500/10' : 'border-line bg-surface-2 dark:border-night-line dark:bg-night-3'">
            <input type="checkbox" wire:model="fw_enabled" x-on:change="$wire.set('fw_enabled', $event.target.checked, false)"
                   class="sr-only">
            <span class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors duration-300"
                  :class="$wire.fw_enabled ? 'bg-emerald-500' : 'bg-ink-2/30 dark:bg-night-line'">
                <span class="absolute right-1 size-4.5 rounded-full bg-white shadow transition-transform duration-300"
                      :class="$wire.fw_enabled ? '-translate-x-5' : ''"></span>
            </span>
            <span class="text-[13px] font-bold" x-text="$wire.fw_enabled ? 'ویجت فعال است' : 'ویجت خاموش است'"></span>
        </label>
    </div>

    <form wire:submit="save" class="space-y-5"
          x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_400px]">

            {{-- ================= ستون تنظیمات ================= --}}
            <div class="space-y-5">

                {{-- ظاهر دکمه --}}
                <section class="ap-card p-4 sm:p-6">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                            <x-admin-panel::icon name="palette" class="size-4" />
                        </span>
                        ظاهر دکمه‌ی شناور
                    </h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="fw-main-color">رنگ اصلی دکمه</label>
                            <div class="flex items-center gap-2">
                                <span class="size-10 shrink-0 rounded-xl border border-line shadow-inner transition-colors dark:border-night-line"
                                      :style="'background-color: ' + ($wire.fw_main_color || '#5b6af7')"></span>
                                <input id="fw-main-color" type="color" wire:model="fw_main_color"
                                       x-on:input="$wire.set('fw_main_color', $event.target.value, false)"
                                       class="h-10 w-full cursor-pointer rounded-xl border border-line bg-transparent p-1 dark:border-night-line">
                            </div>
                            <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                                <template x-for="c in colors" :key="c">
                                    <button type="button" class="size-7 rounded-lg border-2 transition-transform hover:scale-110"
                                            :style="'background-color: ' + c"
                                            :class="$wire.fw_main_color === c ? 'border-ink-2 ring-2 ring-brand-500/40' : 'border-transparent'"
                                            x-on:click="$wire.set('fw_main_color', c, false)"
                                            :aria-label="'انتخاب رنگ ' + c"></button>
                                </template>
                            </div>
                            @error('fw_main_color') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="fw-button-label">متن کنار دکمه‌ی شناور</label>
                            <input id="fw-button-label" type="text" wire:model="fw_button_label"
                                   x-on:input="$wire.set('fw_button_label', $event.target.value, false)"
                                   class="ap-input" placeholder="تماس با ما">
                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">خالی بماند نمایش داده نمی‌شود؛ پیش‌فرض «پشتیبانی».</p>
                            @error('fw_button_label') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                {{-- پنل گفتگو --}}
                <section class="ap-card p-4 sm:p-6">
                    <h3 class="mb-1 flex items-center gap-2 text-sm font-bold">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                            <x-admin-panel::icon name="message-circle" class="size-4" />
                        </span>
                        پنل گفتگو و خوشامدگویی
                    </h3>
                    <p class="mb-4 text-xs text-ink-3 dark:text-night-ink-2">متنی که بالای پنل بازشده‌ی ویجت نمایش داده می‌شود</p>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="fw-greeting">پیام خوشامدگویی</label>
                            <input id="fw-greeting" type="text" wire:model="fw_greeting"
                                   x-on:input="$wire.set('fw_greeting', $event.target.value, false)"
                                   class="ap-input" placeholder="سلام، چطور می‌تونم کمکتون کنم؟">
                            @error('fw_greeting') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="fw-sub-greeting">زیرعنوان پنل</label>
                            <input id="fw-sub-greeting" type="text" wire:model="fw_sub_greeting"
                                   x-on:input="$wire.set('fw_sub_greeting', $event.target.value, false)"
                                   class="ap-input" placeholder="تیم پشتیبانی ما آماده‌ی پاسخ‌گویی است">
                            @error('fw_sub_greeting') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold" for="fw-working-hours">ساعات کاری</label>
                            <input id="fw-working-hours" type="text" wire:model="fw_working_hours"
                                   x-on:input="$wire.set('fw_working_hours', $event.target.value, false)"
                                   class="ap-input" placeholder="شنبه تا چهارشنبه ۹–۱۸">
                            @error('fw_working_hours') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                {{-- اطلاعات تماس --}}
                <section class="ap-card p-4 sm:p-6">
                    <h3 class="mb-1 flex items-center gap-2 text-sm font-bold">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                            <x-admin-panel::icon name="phone" class="size-4" />
                        </span>
                        اطلاعات تماس
                    </h3>
                    <p class="mb-4 text-xs text-ink-3 dark:text-night-ink-2">فیلدهای خالی در پنل ویجت نمایش داده نمی‌شوند</p>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="fw-phone">شماره تماس</label>
                            <input id="fw-phone" type="tel" dir="ltr" wire:model="fw_phone"
                                   x-on:input="$wire.set('fw_phone', $event.target.value, false)"
                                   class="ap-input font-mono" placeholder="021-12345678">
                            @error('fw_phone') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="fw-email">ایمیل</label>
                            <input id="fw-email" type="email" dir="ltr" wire:model="fw_email"
                                   x-on:input="$wire.set('fw_email', $event.target.value, false)"
                                   class="ap-input font-mono" placeholder="info@example.com">
                            @error('fw_email') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold" for="fw-address">آدرس</label>
                            <textarea id="fw-address" rows="2" wire:model="fw_address"
                                      x-on:input="$wire.set('fw_address', $event.target.value, false)"
                                      class="ap-input" placeholder="تهران، خیابان ..."></textarea>
                            @error('fw_address') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                {{-- شبکه‌های اجتماعی و کانال‌های گفتگو --}}
                <section class="ap-card p-4 sm:p-6">
                    <h3 class="mb-1 flex items-center gap-2 text-sm font-bold">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                            <x-admin-panel::icon name="share-2" class="size-4" />
                        </span>
                        شبکه‌های اجتماعی و کانال‌های گفتگو
                    </h3>
                    <p class="mb-4 text-xs text-ink-3 dark:text-night-ink-2">
                        واتساپ و تلگرام به‌صورت دکمه‌ی گفتگو و بقیه در ردیف شبکه‌های اجتماعی پنل نمایش داده می‌شوند — فقط شبکه‌هایی که لینک دارند.
                    </p>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        @foreach ($this->socialNetworks() as $network => $meta)
                            <div class="rounded-2xl border border-line bg-surface-2/50 p-3.5 transition-colors focus-within:border-brand-400 dark:border-night-line dark:bg-night-3/40 dark:focus-within:border-brand-500/50">
                                <div class="mb-2.5 flex items-center gap-2.5">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl text-white shadow-sm" style="background-color: {{ $meta['color'] }}">
                                        <x-admin-panel::icon name="{{ $meta['icon'] }}" class="size-4.5" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-bold">{{ $meta['label'] }}</p>
                                        <p class="text-[10px] text-ink-3 dark:text-night-ink-2">{{ in_array($network, ['whatsapp', 'telegram']) ? 'دکمه‌ی گفتگو' : 'ردیف شبکه‌های اجتماعی' }}</p>
                                    </div>
                                    <span class="ap-badge ms-auto"
                                          :class="($wire.fw_{{ $network }}_url || '').trim() ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400' : 'bg-surface-3 text-ink-3 dark:bg-night-2 dark:text-night-ink-2'"
                                          x-text="($wire.fw_{{ $network }}_url || '').trim() ? 'دارای لینک' : 'بدون لینک'"></span>
                                </div>
                                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-[11px] font-semibold" for="fw-{{ $network }}-label">برچسب</label>
                                        <input id="fw-{{ $network }}-label" type="text" wire:model="fw_{{ $network }}_label"
                                               x-on:input="$wire.set('fw_{{ $network }}_label', $event.target.value, false)"
                                               class="ap-input !py-1.5 text-xs" placeholder="پشتیبانی {{ $meta['label'] }}">
                                        @error('fw_' . $network . '_label') <p class="mt-1 text-[10px] font-medium text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[11px] font-semibold" for="fw-{{ $network }}-url">لینک</label>
                                        <input id="fw-{{ $network }}-url" type="url" dir="ltr" wire:model="fw_{{ $network }}_url"
                                               x-on:input="$wire.set('fw_{{ $network }}_url', $event.target.value, false)"
                                               class="ap-input !py-1.5 font-mono text-xs" placeholder="{{ $meta['placeholder_url'] }}">
                                        @error('fw_' . $network . '_url') <p class="mt-1 text-[10px] font-medium text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            {{-- ================= ستون پیش‌نمایش زنده ================= --}}
            <aside class="xl:sticky xl:top-24 xl:self-start">
                <div class="ap-card overflow-hidden">
                    <div class="flex items-center justify-between gap-2 border-b border-line bg-surface-2/60 px-4 py-3 dark:border-night-line dark:bg-night-3/60">
                        <div class="flex items-center gap-2">
                            <span class="relative flex size-2">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                                <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
                            </span>
                            <h3 class="text-[13px] font-bold">پیش‌نمایش زنده</h3>
                        </div>
                        <button type="button" class="text-[11px] font-bold text-brand-600 transition-colors hover:text-brand-700 dark:text-brand-400"
                                x-on:click="pvOpen = !pvOpen" x-text="pvOpen ? 'بستن پنل' : 'باز کردن پنل'"></button>
                    </div>

                    {{-- قاب سایت شبیه‌سازی‌شده --}}
                    <div class="relative h-[560px] select-none overflow-hidden bg-surface-2 dark:bg-night-3"
                         dir="rtl"
                         :style="'--fw-c: ' + ($wire.fw_main_color || '#5b6af7')">

                        {{-- اسکلت سایت --}}
                        <div class="pointer-events-none absolute inset-0 opacity-80">
                            <div class="flex items-center justify-between border-b border-line bg-surface px-4 py-2.5 dark:border-night-line dark:bg-night-2">
                                <span class="h-2.5 w-16 rounded-full bg-surface-3 dark:bg-night-line"></span>
                                <span class="flex gap-1.5">
                                    <span class="h-2 w-8 rounded-full bg-surface-3 dark:bg-night-line"></span>
                                    <span class="h-2 w-8 rounded-full bg-surface-3 dark:bg-night-line"></span>
                                    <span class="h-2 w-8 rounded-full bg-surface-3 dark:bg-night-line"></span>
                                </span>
                            </div>
                            <div class="space-y-2.5 p-4">
                                <div class="h-24 rounded-2xl bg-gradient-to-l from-surface-3 to-surface-2 dark:from-night-line dark:to-night-3"></div>
                                <div class="grid grid-cols-3 gap-2.5">
                                    <div class="h-20 rounded-xl bg-surface-3/80 dark:bg-night-line/70"></div>
                                    <div class="h-20 rounded-xl bg-surface-3/80 dark:bg-night-line/70"></div>
                                    <div class="h-20 rounded-xl bg-surface-3/80 dark:bg-night-line/70"></div>
                                </div>
                                <div class="h-3 w-2/3 rounded-full bg-surface-3 dark:bg-night-line"></div>
                                <div class="h-3 w-1/2 rounded-full bg-surface-3 dark:bg-night-line"></div>
                            </div>
                        </div>

                        {{-- ویجت (پورت floating-widget تم) --}}
                        <div x-show="$wire.fw_enabled" x-cloak class="absolute bottom-5 left-5 z-10" dir="rtl">
                            <div class="relative">
                                {{-- پنل --}}
                                <div x-show="pvOpen"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                     x-cloak
                                     class="absolute bottom-[72px] left-0 w-[290px] overflow-hidden rounded-3xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2">
                                    <div class="p-4" :style="'background: linear-gradient(135deg, ' + ($wire.fw_main_color || '#5b6af7') + ', color-mix(in srgb, ' + ($wire.fw_main_color || '#5b6af7') + ' 75%, #000))'">
                                        <p class="text-[13px] font-bold text-white" x-text="$wire.fw_greeting || 'سلام! چطور می‌تونیم کمکتون کنیم؟'"></p>
                                        <p class="mt-1 text-[11px] leading-5 text-white/80" x-text="$wire.fw_sub_greeting || 'تیم پشتیبانی آماده پاسخ‌گویی است'"></p>
                                    </div>
                                    <div class="space-y-2.5 p-3.5">
                                        <div x-show="($wire.fw_working_hours || '').trim()" class="flex items-center gap-2 rounded-xl bg-surface-2 px-3 py-2 text-[11px] font-semibold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                            <span :style="'color: ' + ($wire.fw_main_color || '#5b6af7')" class="flex"><x-admin-panel::icon name="clock" class="size-3.5 shrink-0" /></span>
                                            <span x-text="$wire.fw_working_hours"></span>
                                        </div>
                                        <div x-show="($wire.fw_phone || '').trim()" class="flex items-center gap-2 rounded-xl bg-surface-2 px-3 py-2 text-[11px] font-semibold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                            <span :style="'color: ' + ($wire.fw_main_color || '#5b6af7')" class="flex"><x-admin-panel::icon name="phone" class="size-3.5 shrink-0" /></span>
                                            <span class="ltr font-mono" x-text="$wire.fw_phone"></span>
                                        </div>
                                        <div x-show="($wire.fw_email || '').trim()" class="flex items-center gap-2 rounded-xl bg-surface-2 px-3 py-2 text-[11px] font-semibold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                            <span :style="'color: ' + ($wire.fw_main_color || '#5b6af7')" class="flex"><x-admin-panel::icon name="mail" class="size-3.5 shrink-0" /></span>
                                            <span class="ltr font-mono" x-text="$wire.fw_email"></span>
                                        </div>

                                        {{-- کانال‌های گفتگو --}}
                                        <div class="space-y-1.5 pt-1">
                                            <template x-if="($wire.fw_whatsapp_url || '').trim()">
                                                <span class="flex items-center justify-between rounded-xl border border-line px-3 py-2 dark:border-night-line">
                                                    <span class="flex items-center gap-2 text-[11px] font-bold">
                                                        <span class="flex size-6 items-center justify-center rounded-lg bg-[#25D366] text-white"><x-admin-panel::icon name="message-circle" class="size-3.5" /></span>
                                                        <span x-text="$wire.fw_whatsapp_label || 'گفتگو در واتساپ'"></span>
                                                    </span>
                                                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                                </span>
                                            </template>
                                            <template x-if="($wire.fw_telegram_url || '').trim()">
                                                <span class="flex items-center justify-between rounded-xl border border-line px-3 py-2 dark:border-night-line">
                                                    <span class="flex items-center gap-2 text-[11px] font-bold">
                                                        <span class="flex size-6 items-center justify-center rounded-lg bg-[#2AABEE] text-white"><x-admin-panel::icon name="send" class="size-3.5" /></span>
                                                        <span x-text="$wire.fw_telegram_label || 'گفتگو در تلگرام'"></span>
                                                    </span>
                                                    <span class="size-1.5 rounded-full bg-sky-500"></span>
                                                </span>
                                            </template>
                                        </div>

                                        {{-- ردیف شبکه‌های اجتماعی --}}
                                        <div class="flex items-center gap-1.5 pt-1"
                                             x-show="($wire.fw_instagram_url || $wire.fw_twitter_url || $wire.fw_youtube_url || $wire.fw_linkedin_url || '').trim()">
                                            <template x-if="($wire.fw_instagram_url || '').trim()">
                                                <span class="flex size-7 items-center justify-center rounded-lg text-white" style="background-color:#E1306C"><x-admin-panel::icon name="instagram" class="size-3.5" /></span>
                                            </template>
                                            <template x-if="($wire.fw_twitter_url || '').trim()">
                                                <span class="flex size-7 items-center justify-center rounded-lg bg-slate-900 text-white"><x-admin-panel::icon name="twitter" class="size-3.5" /></span>
                                            </template>
                                            <template x-if="($wire.fw_youtube_url || '').trim()">
                                                <span class="flex size-7 items-center justify-center rounded-lg bg-[#FF0000] text-white"><x-admin-panel::icon name="youtube" class="size-3.5" /></span>
                                            </template>
                                            <template x-if="($wire.fw_linkedin_url || '').trim()">
                                                <span class="flex size-7 items-center justify-center rounded-lg bg-[#0077B5] text-white"><x-admin-panel::icon name="linkedin" class="size-3.5" /></span>
                                            </template>
                                        </div>

                                        {{-- آدرس --}}
                                        <div x-show="($wire.fw_address || '').trim()" class="flex items-start gap-2 border-t border-line pt-2.5 text-[10px] leading-5 text-ink-3 dark:border-night-line dark:text-night-ink-2">
                                            <x-admin-panel::icon name="map-pin" class="mt-0.5 size-3.5 shrink-0" />
                                            <span x-text="$wire.fw_address"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- دکمه شناور --}}
                                <button type="button" x-on:click="pvOpen = !pvOpen" aria-label="پیش‌نمایش ویجت"
                                        class="relative flex size-14 items-center justify-center rounded-full text-white transition-transform duration-300 hover:scale-105"
                                        :style="'background-color: ' + ($wire.fw_main_color || '#5b6af7') + '; box-shadow: 0 10px 26px color-mix(in srgb, ' + ($wire.fw_main_color || '#5b6af7') + ' 55%, transparent)'">
                                    <span class="absolute -inset-1.5 rounded-full border-2 opacity-60 animate-ping"
                                          :style="'border-color: ' + ($wire.fw_main_color || '#5b6af7')"></span>
                                    <x-admin-panel::icon name="message-circle" class="size-6 transition-opacity" x-bind:class="pvOpen ? 'opacity-0' : 'opacity-100'" />
                                    <x-admin-panel::icon name="x" class="absolute size-5 transition-opacity" x-bind:class="pvOpen ? 'opacity-100' : 'opacity-0'" />
                                </button>

                                {{-- برچسب دکمه --}}
                                <span x-show="!pvOpen && ($wire.fw_button_label || '').trim()"
                                      class="absolute right-full top-1/2 me-3 -translate-y-1/2 whitespace-nowrap rounded-full bg-surface px-3.5 py-1.5 text-[12px] font-bold text-ink-2 shadow-lg dark:bg-night-2 dark:text-night-ink"
                                      x-text="$wire.fw_button_label"></span>
                            </div>
                        </div>

                        {{-- حالت غیرفعال --}}
                        <div x-show="!$wire.fw_enabled" x-cloak class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-2 bg-surface/70 backdrop-blur-[2px] dark:bg-night-2/70">
                            <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-3 text-ink-3 dark:bg-night-line dark:text-night-ink-2">
                                <x-admin-panel::icon name="power" class="size-6" />
                            </span>
                            <p class="text-xs font-bold text-ink-2 dark:text-night-ink-2">ویجت غیرفعال است</p>
                            <p class="text-[11px] text-ink-3 dark:text-night-ink-2">با فعال‌سازی، پیش‌نمایش نمایش داده می‌شود</p>
                        </div>
                    </div>
                </div>

                <p class="mt-2 flex items-center justify-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="info" class="size-3.5" />
                    پیش‌نمایش، همان خروجی سایت است — بدون ذخیره هم زنده به‌روز می‌شود.
                </p>
            </aside>
        </div>

        {{-- نوار ذخیره --}}
        <div class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-lg backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <span class="flex items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="info" class="size-3.5" />
                تغییرات فوراً در پیش‌نمایش اعمال می‌شود؛ برای اعمال در سایت ذخیره کنید.
            </span>
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn-primary !py-2.5">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <x-admin-panel::icon name="check-circle" class="size-4" />
                    ذخیره تنظیمات
                </span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                    </svg>
                    در حال ذخیره…
                </span>
            </button>
        </div>
    </form>
</div>
