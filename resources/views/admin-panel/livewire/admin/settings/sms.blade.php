{{-- تنظیمات پیامک — فاز ۱۷: بازطراحی مدرن (تب‌بندی + کارت انتخاب ارائه‌دهنده + سوییچ‌های مدرن)
     پورت ۱:۱ کلیدها: sms.* و toggles.* (SmsSettings) — پنل قدیمی back/settings/sms.blade.php --}}
<div class="mx-auto max-w-6xl space-y-5"
     x-data="{ tab: 'general', provider: @js($sms['sms_panel_provider'] ?? 'ippanel') }">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="message-circle" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-bold">تنظیمات پنل و پیامک‌های خودکار</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">ارائه‌دهنده پیامک، اطلاعات اتصال و رویدادهای ارسال خودکار</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-5" x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- ============ تب‌ها ============ --}}
        <div class="ap-card overflow-hidden">
            <div class="flex gap-1 overflow-x-auto border-b border-line bg-surface-2 px-2 pt-2 dark:border-night-line dark:bg-night-3/50" role="tablist">
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'general'"
                        x-bind:aria-selected="(tab === 'general').toString()" role="tab"
                        x-bind:class="tab === 'general' ? 'is-active' : ''">
                    <x-admin-panel::icon name="settings" class="size-4" />
                    تنظیمات عمومی
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'events'"
                        x-bind:aria-selected="(tab === 'events').toString()" role="tab"
                        x-bind:class="tab === 'events' ? 'is-active' : ''">
                    <x-admin-panel::icon name="bell-ring" class="size-4" />
                    رویدادهای پیامکی
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'provider'"
                        x-bind:aria-selected="(tab === 'provider').toString()" role="tab"
                        x-bind:class="tab === 'provider' ? 'is-active' : ''">
                    <x-admin-panel::icon name="hash" class="size-4" />
                    پترن‌های ارسال
                </button>
            </div>
        </div>

        {{-- ============ تب ۱: تنظیمات عمومی ============ --}}
        <div x-show="tab === 'general'" x-cloak class="animate-fade-in space-y-5">
            <div class="ap-card p-4 sm:p-6">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                        <x-admin-panel::icon name="radio" class="size-4" />
                    </span>
                    ارائه دهنده پنل پیامک
                </h3>

                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (\App\Livewire\Admin\Settings\SmsSettings::providers() as $providerKey => $providerName)
                        <button type="button"
                                x-on:click="provider = @js($providerKey); $wire.set('sms.sms_panel_provider', @js($providerKey), false)"
                                class="flex items-center justify-between gap-2 rounded-xl border-2 p-3.5 text-start transition-all"
                                x-bind:class="provider === @js($providerKey)
                                    ? 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10'
                                    : 'border-line bg-surface hover:border-brand-300 dark:border-night-line dark:bg-night-2 dark:hover:border-brand-500/40'">
                            <span class="flex min-w-0 items-center gap-2.5">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2"
                                      x-bind:class="provider === @js($providerKey) ? '!bg-brand-500 !text-white' : ''">
                                    <x-admin-panel::icon name="message-square" class="size-4" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-[13px] font-bold">{{ $providerName }}</span>
                                    <span class="ltr block truncate text-[10px] text-ink-3 dark:text-night-ink-2">{{ $providerKey }}</span>
                                </span>
                            </span>
                            <span class="flex size-4.5 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                                  x-bind:class="provider === @js($providerKey) ? 'border-brand-500 bg-brand-500' : 'border-line dark:border-night-line'">
                                <span class="size-1.5 rounded-full bg-white" x-show="provider === @js($providerKey)"></span>
                            </span>
                        </button>
                    @endforeach
                </div>

                <p class="mt-3 flex items-start gap-2 rounded-xl border border-sky-200 bg-sky-50 p-3 text-[12px] leading-6 text-sky-800 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    <span>
                        با تغییر ارائه‌دهنده، اطلاعات اتصال در همین تب و کدهای پترن در تب «پترن‌های ارسال» برای همان سرویس تغییر می‌کند؛ اطلاعات را از پنل همان سرویس دریافت و وارد کنید.
                    </span>
                </p>
            </div>

            <div class="ap-card p-4 sm:p-6">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                        <x-admin-panel::icon name="phone" class="size-4" />
                    </span>
                    شماره تلفن مدیر
                </h3>
                <div class="max-w-sm">
                    <label class="mb-1.5 block text-xs font-semibold" for="sms-admin-mobile">شماره موبایل مدیر (برای اطلاع‌رسانی‌ها)</label>
                    <input id="sms-admin-mobile" type="text" dir="ltr" wire:model="sms.admin_mobile_number" class="ap-input font-mono" placeholder="09xxxxxxxxx">
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">پیامک‌های سیستمیِ مخصوص مدیر (مثل ثبت سفارش جدید) به این شماره ارسال می‌شود.</p>
                </div>
            </div>

            {{-- اطلاعات اتصال به پنل — فاز ۱۸: به تب اول منتقل شد و بسته به ارائه‌دهنده انتخابی تغییر می‌کند --}}
            <div class="ap-card p-4 sm:p-6">
                <h3 class="mb-1 flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                        <x-admin-panel::icon name="plug" class="size-4" />
                    </span>
                    اطلاعات اتصال به پنل
                </h3>
                <p class="mb-4 text-xs text-ink-3 dark:text-night-ink-2">
                    این بخش بسته به ارائه‌دهنده‌ی انتخاب‌شده در بالا تغییر می‌کند؛ اطلاعات را از پنل همان سرویس دریافت کنید.
                </p>

                @foreach (\App\Livewire\Admin\Settings\SmsSettings::providers() as $providerKey => $providerName)
                    <div x-show="provider === @js($providerKey)" x-cloak class="space-y-3" wire:key="sms-cred-general-{{ $providerKey }}">
                        <div class="mb-1 flex items-center gap-2">
                            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">{{ $providerName }}</span>
                            <span class="ltr text-[10px] text-ink-3 dark:text-night-ink-2">{{ $providerKey }}</span>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            @foreach (\App\Livewire\Admin\Settings\SmsSettings::credentials($providerKey) as $credential)
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" for="cred-general-{{ $providerKey }}-{{ $loop->index }}">{{ $credential['label'] }}</label>
                                    <input id="cred-general-{{ $providerKey }}-{{ $loop->index }}" type="text" dir="ltr" wire:model="sms.{{ $credential['key'] }}" class="ap-input font-mono" autocomplete="off">
                                </div>
                            @endforeach
                        </div>
                        <p class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-[12px] leading-6 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                            <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                            <span>
                                پس از ذخیره، سامانه پیامک‌های رویدادی را از همین پنل ارسال می‌کند. کدهای پترن مربوط به {{ $providerName }} در تب «پترن‌های ارسال» وارد می‌شود.
                            </span>
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ============ تب ۲: رویدادهای پیامکی ============ --}}
        <div x-show="tab === 'events'" x-cloak class="animate-fade-in space-y-5">

            @foreach ([
                'register' => ['title' => 'ثبت‌نام، ورود و احراز هویت', 'icon' => 'shield'],
                'orders'   => ['title' => 'سفارش‌ها', 'icon' => 'shopping-cart'],
                'wallet'   => ['title' => 'کیف پول', 'icon' => 'wallet'],
            ] as $groupKey => $group)
                <div class="ap-card p-4 sm:p-6">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                            <x-admin-panel::icon name="{{ $group['icon'] }}" class="size-4" />
                        </span>
                        {{ $group['title'] }}
                    </h3>
                    <div class="grid grid-cols-1 gap-2.5 lg:grid-cols-2">
                        @foreach ($this->toggleCards()[$groupKey] as $card)
                            @include('admin-panel::livewire.admin.settings.partials.sms-switch', ['card' => $card])
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- پیامک مناسبتی --}}
            <div class="rounded-2xl border border-dashed border-pink-300 bg-pink-50/40 p-4 dark:border-pink-500/30 dark:bg-pink-500/5 sm:p-6">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-pink-100 text-pink-600 dark:bg-pink-500/15 dark:text-pink-400">
                        <x-admin-panel::icon name="gift" class="size-4" />
                    </span>
                    پیامک مناسبتی
                </h3>
                <div class="space-y-2.5">
                    @foreach ($this->toggleCards()['birthday'] as $card)
                        @include('admin-panel::livewire.admin.settings.partials.sms-switch', ['card' => $card])
                    @endforeach
                </div>
                <div class="mt-3 flex items-start gap-2 rounded-xl border border-sky-200 bg-sky-50 p-3 text-[12px] leading-6 text-sky-800 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    <span>
                        این گزینه به هیچ رویداد خاصی (مثل ثبت سفارش یا ورود کاربر) وابسته نیست. با فعال بودن آن، سامانه هر روز به‌صورت خودکار تاریخ تولد ثبت‌شده کاربران را بررسی می‌کند و برای کاربرانی که امروز تولدشان است، پیامک تبریک ارسال می‌شود. کد پترن مربوطه را در تب «ارائه‌دهنده و پترن‌ها» وارد کنید.
                    </span>
                </div>
            </div>
        </div>

        {{-- ============ تب ۳: ارائه‌دهنده و پترن‌ها ============ --}}
        <div x-show="tab === 'provider'" x-cloak class="animate-fade-in space-y-5">

            @foreach (\App\Livewire\Admin\Settings\SmsSettings::providers() as $providerKey => $providerName)
                <div x-show="provider === @js($providerKey)" x-cloak class="space-y-5" wire:key="sms-provider-{{ $providerKey }}">

                    {{-- پترن‌ها — گروه‌بندی مثل قدیمی (اطلاعات اتصال از فاز ۱۸ به تب اول منتقل شد) --}}
                    @foreach ([
                        'پترن‌های ثبت‌نام و احراز هویت' => ['seller_register', 'user_register', 'user_verify'],
                        'پترن‌های پرداخت سفارش' => ['order_paid', 'seller_order_paid', 'user_order_paid'],
                        'پترن‌های لغو سفارش' => ['order_cancelled', 'seller_order_cancelled', 'user_order_cancelled'],
                        'پترن‌های کیف پول' => ['wallet_refund', 'wallet_increase', 'wallet_decrease'],
                        'پترن مناسبتی' => ['happy_birthday'],
                    ] as $sectionTitle => $baseNames)
                        @php
                            $sectionPatterns = [];
                            foreach ($baseNames as $baseName) {
                                $patternKey = $providerKey === 'ippanel'
                                    ? ($baseName === 'happy_birthday' ? 'happy_birthday_pattern_code_ippanel'
                                        : (in_array($baseName, ['wallet_increase', 'wallet_decrease']) ? $baseName.'_pattern_code_ippanel' : $baseName.'_pattern_code'))
                                    : $baseName.'_pattern_code_'.$providerKey;
                                $meta = $this->patternMeta($providerKey, $patternKey);
                                if ($meta) {
                                    $sectionPatterns[] = ['key' => $patternKey, 'meta' => $meta];
                                }
                            }
                        @endphp
                        @if (count($sectionPatterns))
                            <div class="ap-card overflow-hidden" wire:key="sms-patterns-{{ $providerKey }}-{{ $loop->index }}">
                                <div class="flex items-center gap-2 border-b border-line bg-surface-2/60 px-4 py-3 dark:border-night-line dark:bg-night-3/60">
                                    <x-admin-panel::icon name="hash" class="size-4 text-brand-600 dark:text-brand-400" />
                                    <h4 class="text-[13px] font-bold">{{ $sectionTitle }}</h4>
                                    <span class="fa-digits ms-auto rounded-lg bg-surface px-2 py-0.5 text-[10px] font-bold text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        {{ count($sectionPatterns) }} پترن
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 gap-3 p-4 lg:grid-cols-2">
                                    @foreach ($sectionPatterns as $pattern)
                                        @php($meta = $pattern['meta'])
                                        <div class="rounded-xl border border-line bg-surface-2/50 p-3.5 transition-colors focus-within:border-brand-400 dark:border-night-line dark:bg-night-3/40 dark:focus-within:border-brand-500/50">
                                            <label class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold" for="pat-{{ $pattern['key'] }}">
                                                {{ $meta['label'] }}
                                                @if (! empty($meta['required']))
                                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">ضروری</span>
                                                @endif
                                                @if (! empty($meta['toggle']) && ($toggles[$meta['toggle']] ?? false))
                                                    <span class="ap-badge bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">فعال</span>
                                                @endif
                                            </label>
                                            <input id="pat-{{ $pattern['key'] }}" type="text" dir="ltr" wire:model="sms.{{ $pattern['key'] }}" class="ap-input font-mono" placeholder="کد پترن را از پنل ارائه‌دهنده وارد کنید">
                                            <div class="mt-2" x-data="{ open: false, copied: false }">
                                                <div class="flex items-center justify-between gap-2">
                                                    <button type="button" class="flex items-center gap-1 text-[11px] font-bold text-ink-3 transition-colors hover:text-brand-600 dark:text-night-ink-2 dark:hover:text-brand-400"
                                                            x-on:click="open = ! open">
                                                        <x-admin-panel::icon name="eye" class="size-3.5" />
                                                        متن نمونه ایجاد پترن
                                                    </button>
                                                    <button type="button" class="flex items-center gap-1 rounded-lg p-1.5 text-[11px] font-bold text-brand-600 transition-colors hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-night-2"
                                                            x-on:click="navigator.clipboard && navigator.clipboard.writeText($refs.sample.value); copied = true; setTimeout(() => copied = false, 1800)">
                                                        <x-admin-panel::icon name="check" class="size-3.5 text-emerald-500" x-show="copied" x-cloak />
                                                        <x-admin-panel::icon name="copy" class="size-3.5" x-show="! copied" />
                                                        <span x-text="copied ? 'کپی شد' : 'کپی'"></span>
                                                    </button>
                                                </div>
                                                <textarea x-ref="sample" readonly rows="3" x-show="open" x-cloak
                                                          class="ap-input mt-1.5 font-mono text-[11px] leading-5">{{ $this->sample($meta['sample']) }}</textarea>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- نوار ذخیره --}}
        <div class="sticky bottom-4 z-10 flex items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-lg backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">اطلاعات اتصال در تب اول و کد پترن‌ها در تب «پترن‌های ارسال» وارد می‌شود.</span>
            <button type="submit" class="ap-btn-primary !py-2.5">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <x-admin-panel::icon name="check-circle" class="size-4" />
                    ذخیره تغییرات
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
