{{-- تنظیمات پیامک — پنل جدید (فاز ۷-ج۳) — پورت back/settings/sms.blade.php + پارشیال‌ها --}}
<div class="mx-auto max-w-6xl space-y-5">

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

    <form wire:submit="save" class="space-y-5">

        {{-- انتخاب ارائه‌دهنده --}}
        <div class="ap-card p-4 sm:p-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">ارائه دهنده پنل پیامک</label>
                    <select wire:model.live="sms.sms_panel_provider" class="ap-input">
                        @foreach (\App\Livewire\Admin\Settings\SmsSettings::providers() as $providerKey => $providerName)
                            <option value="{{ $providerKey }}">{{ $providerName }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">تغییر ارائه‌دهنده، بخش تنظیمات مخصوص همان پنل را در پایین صفحه فعال می‌کند.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">شماره تلفن مدیر برای ارسال اطلاع‌رسانی‌ها</label>
                    <input type="text" dir="ltr" wire:model="sms.admin_mobile_number" class="ap-input font-mono" placeholder="09xxxxxxxxx">
                </div>
            </div>
        </div>

        {{-- گروه: ثبت‌نام، ورود و احراز هویت --}}
        <div class="ap-card p-4 sm:p-5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="shield" class="size-4 text-brand-600 dark:text-brand-400" />
                ثبت‌نام، ورود و احراز هویت
            </h3>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->toggleCards()['register'] as $card)
                    @include('admin-panel::livewire.admin.settings.partials.sms-toggle', ['card' => $card])
                @endforeach
            </div>
        </div>

        {{-- گروه: سفارش‌ها --}}
        <div class="ap-card p-4 sm:p-5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="shopping-cart" class="size-4 text-brand-600 dark:text-brand-400" />
                سفارش‌ها
            </h3>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->toggleCards()['orders'] as $card)
                    @include('admin-panel::livewire.admin.settings.partials.sms-toggle', ['card' => $card])
                @endforeach
            </div>
        </div>

        {{-- گروه: کیف پول --}}
        <div class="ap-card p-4 sm:p-5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="wallet" class="size-4 text-brand-600 dark:text-brand-400" />
                کیف پول
            </h3>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->toggleCards()['wallet'] as $card)
                    @include('admin-panel::livewire.admin.settings.partials.sms-toggle', ['card' => $card])
                @endforeach
            </div>
        </div>

        {{-- گروه: پیامک مناسبتی --}}
        <div class="rounded-2xl border border-dashed border-pink-300 bg-pink-50/40 p-4 dark:border-pink-500/30 dark:bg-pink-500/5 sm:p-5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="gift" class="size-4 text-pink-600 dark:text-pink-400" />
                پیامک مناسبتی
            </h3>
            <div class="grid grid-cols-1 gap-3">
                @foreach ($this->toggleCards()['birthday'] as $card)
                    @include('admin-panel::livewire.admin.settings.partials.sms-toggle', ['card' => $card])
                @endforeach
            </div>
            <div class="mt-3 flex items-start gap-2 rounded-xl border border-sky-200 bg-sky-50 p-3 text-[12px] leading-6 text-sky-800 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                <span>
                    این گزینه به هیچ رویداد خاصی (مثل ثبت سفارش یا ورود کاربر) وابسته نیست. با فعال بودن آن، سامانه هر روز به‌صورت خودکار تاریخ تولد ثبت‌شده کاربران را بررسی می‌کند و برای کاربرانی که امروز تولدشان است، پیامک تبریک ارسال می‌شود. کد پترن مربوطه را در بخش تنظیمات همان ارائه‌دهنده در پایین همین صفحه وارد کنید.
                </span>
            </div>
        </div>

        {{-- تنظیمات اختصاصی هر ارائه‌دهنده --}}
        @foreach (\App\Livewire\Admin\Settings\SmsSettings::providers() as $providerKey => $providerName)
            <div x-show="$wire.sms.sms_panel_provider === '{{ $providerKey }}'" x-cloak class="space-y-5">

                {{-- اطلاعات اتصال --}}
                <div class="ap-card p-4 sm:p-5">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="settings" class="size-4 text-brand-600 dark:text-brand-400" />
                        اطلاعات اتصال به پنل {{ $providerName }}
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        @foreach (\App\Livewire\Admin\Settings\SmsSettings::credentials($providerKey) as $credential)
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold">{{ $credential['label'] }}</label>
                                <input type="text" dir="ltr" wire:model="sms.{{ $credential['key'] }}" class="ap-input font-mono">
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- پترن‌ها --}}
                @foreach ([
                    'پترن‌های ثبت‌نام و احراز هویت' => ['seller_register', 'user_register', 'user_verify'],
                    'پترن‌های پرداخت سفارش' => ['order_paid', 'seller_order_paid', 'user_order_paid'],
                    'پترن‌های لغو سفارش' => ['order_cancelled', 'seller_order_cancelled', 'user_order_cancelled'],
                    'پترن‌های کیف پول' => ['wallet_refund', 'wallet_increase', 'wallet_decrease'],
                    'پترن مناسبتی' => ['happy_birthday'],
                ] as $sectionTitle => $baseNames)
                    <div class="ap-card p-4 sm:p-5">
                        <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                            <x-admin-panel::icon name="hash" class="size-4 text-brand-600 dark:text-brand-400" />
                            {{ $sectionTitle }}
                        </h3>
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            @foreach ($baseNames as $baseName)
                                @php($patternKey = $providerKey === 'ippanel'
                                    ? ($baseName === 'happy_birthday' ? 'happy_birthday_pattern_code_ippanel'
                                        : (in_array($baseName, ['wallet_increase', 'wallet_decrease']) ? $baseName.'_pattern_code_ippanel' : $baseName.'_pattern_code'))
                                    : $baseName.'_pattern_code_'.$providerKey)
                                @php($meta = $this->patternMeta($providerKey, $patternKey))
                                @continue(! $meta)
                                <div class="rounded-xl border border-line bg-surface-2/60 p-4 dark:border-night-line dark:bg-night-3/40">
                                    <label class="mb-1.5 block text-xs font-semibold">
                                        <x-admin-panel::icon name="hash" class="me-1 inline size-3.5 text-brand-600 dark:text-brand-400" />
                                        {{ $meta['label'] }}
                                    </label>
                                    <input type="text" dir="ltr" wire:model="sms.{{ $patternKey }}" class="ap-input font-mono">
                                    <div class="mt-2" x-data="{ copied: false }">
                                        <div class="flex items-center justify-between">
                                            <small class="text-ink-3 dark:text-night-ink-2">متن نمونه ایجاد پترن</small>
                                            <button type="button" class="rounded-lg p-1.5 text-brand-600 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-night-3"
                                                    x-on:click="navigator.clipboard && navigator.clipboard.writeText($refs.sample.value); copied = true; setTimeout(() => copied = false, 1500)">
                                                <x-admin-panel::icon name="copy" class="size-4" x-show="! copied" />
                                                <x-admin-panel::icon name="check" class="size-4 text-emerald-500" x-show="copied" x-cloak />
                                            </button>
                                        </div>
                                        <textarea x-ref="sample" readonly rows="4" class="ap-input mt-1 font-mono text-[11px] leading-5">{{ $this->sample($meta['sample']) }}</textarea>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

        {{-- نوار ذخیره --}}
        <div class="sticky bottom-4 z-10 flex items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-lg backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">پترن‌ها را از پنل ارائه‌دهنده دریافت و اینجا وارد کنید.</span>
            <button type="submit" class="ap-btn-primary !py-2.5">
                <x-admin-panel::icon name="check-circle" class="size-4" />
                ذخیره تغییرات
            </button>
        </div>
    </form>
</div>
