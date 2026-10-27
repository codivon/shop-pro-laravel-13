{{-- تنظیمات دیگر — پنل جدید — پورت back/settings/others.blade.php
     فاز ۳۰: بازطراحی مدرن کامل + سوییچ به‌جای select برای فعال/غیرفعال (درخواست کاربر) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-5xl space-y-5">

    {{-- سربرگ — فاز ۸۴: دکمهٔ ذخیرهٔ بالایی حذف شد (به درخواست کاربر)؛ ذخیره فقط از نوار پایین --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="sliders-horizontal" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-bold">تنظیمات دیگر</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">فاکتور، کاربران، تصاویر، Pusher، ایمیل و قابلیت‌های فروشگاه — تنظیمات چندفروشندگی به تب «چندفروشندگی» در برنامه‌نویس منتقل شد (فاز ۱۰۸)</p>
            </div>
        </div>
    </div>

    <form id="others-form" wire:submit="save" x-data="{ tab: 'factor' }" class="space-y-5">

        <div class="ap-card overflow-hidden">
            <div class="flex gap-1 overflow-x-auto border-b border-line bg-surface-2 px-2 pt-2 dark:border-night-line dark:bg-night-3/50" role="tablist">
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'factor'"
                        x-bind:aria-selected="(tab === 'factor').toString()" role="tab"
                        x-bind:class="tab === 'factor' ? 'is-active' : ''">
                    <x-admin-panel::icon name="receipt" class="size-4" />
                    فاکتور
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'users'"
                        x-bind:aria-selected="(tab === 'users').toString()" role="tab"
                        x-bind:class="tab === 'users' ? 'is-active' : ''">
                    <x-admin-panel::icon name="users" class="size-4" />
                    کاربران
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'images'"
                        x-bind:aria-selected="(tab === 'images').toString()" role="tab"
                        x-bind:class="tab === 'images' ? 'is-active' : ''">
                    <x-admin-panel::icon name="image" class="size-4" />
                    تصاویر
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'pusher'"
                        x-bind:aria-selected="(tab === 'pusher').toString()" role="tab"
                        x-bind:class="tab === 'pusher' ? 'is-active' : ''">
                    <x-admin-panel::icon name="waypoints" class="size-4" />
                    Pusher
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'mail'"
                        x-bind:aria-selected="(tab === 'mail').toString()" role="tab"
                        x-bind:class="tab === 'mail' ? 'is-active' : ''">
                    <x-admin-panel::icon name="mail" class="size-4" />
                    ایمیل
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'features'"
                        x-bind:aria-selected="(tab === 'features').toString()" role="tab"
                        x-bind:class="tab === 'features' ? 'is-active' : ''">
                    <x-admin-panel::icon name="trending-down" class="size-4" />
                    قابلیت‌های فروشگاه
                </button>
            </div>

            {{-- فاز ۱۰۸ — تب «چند فروشندگی» حذف شد؛ سوییچ سیستم چندفروشندگی فقط در
                 برنامه‌نویس ← تب «چندفروشندگی» (فاز ۱۰۳) مدیریت می‌شود تا دو مالک برای یک کلید نداشته باشیم. --}}

            {{-- ============ فاکتور ============ --}}
            <div x-show="tab === 'factor'" x-cloak class="animate-fade-in space-y-5 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-line p-4 dark:border-night-line">
                        <x-admin-panel::media-picker
                            wireModel="factor_logo"
                            :value="$current_factor_logo"
                            label="لوگو"
                            hint="از رسانه‌های سایت انتخاب کنید"
                            height="h-24"
                            aspect="contain"
                        />
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">عنوان فاکتور</label>
                            <input type="text" wire:model="factor_title" class="ap-input" placeholder="مثلاً فاکتور فروش">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">فروشنده</label>
                            <input type="text" wire:model="factor_seller_name" class="ap-input" placeholder="نام فروشگاه شما">
                        </div>
                    </div>
                </div>
                <section>
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="id-card" class="size-4 text-brand-600 dark:text-brand-400" />
                        اطلاعات حقوقی
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">شناسه ملی</label>
                            <input type="text" dir="ltr" wire:model="factor_national_code" class="ap-input font-mono">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">شناسه ثبت</label>
                            <input type="text" dir="ltr" wire:model="factor_registeration_id" class="ap-input font-mono">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">شماره اقتصادی</label>
                            <input type="text" dir="ltr" wire:model="factor_economical_number" class="ap-input font-mono">
                        </div>
                    </div>
                </section>
            </div>

            {{-- ============ کاربران ============ --}}
            <div x-show="tab === 'users'" x-cloak class="animate-fade-in space-y-5 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">اعتبار هدیه ثبت‌نام کاربر ({{ currencyTitle() }})</label>
                        <input type="number" min="0" dir="ltr" wire:model="user_register_gift_credit" class="ap-input font-mono">
                        <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">به کیف پول کاربر جدید اضافه می‌شود؛ صفر = بدون هدیه.</p>
                    </div>
                </div>

                {{-- ═══════════ فاز ۹۲: سیستم معرفی افراد (ریفرال) به پکیج «باشگاه مشتریان» منتقل شد ═══════════
                     مدیریت کامل امکان معرفی افراد اکنون در: پنل ادمین → باشگاه مشتریان → تنظیمات (بخش «پاداش معرفی افراد»)
                     و پنل مشتری: باشگاه مشتریان → معرفی دوستان. این بخش عمداً مخفی است تا امکان فقط در پکیج باشد.
                <section>
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="users-round" class="size-4 text-brand-600 dark:text-brand-400" />
                        سیستم معرفی افراد (ریفرال)
                    </h3>
                    @include('admin-panel::livewire.admin.settings.partials.setting-switch', [
                        'name'  => 'user_referrals_enable',
                        'live'  => true,
                        'icon'  => 'gift',
                        'title' => 'فعال کردن امکان معرفی افراد',
                        'desc'  => 'کاربران با اشتراک‌گذاری لینک معرفی، برای خود و دوستانشان هدیه می‌گیرند.',
                    ])
                    <div @class([
                        'mt-4 grid grid-cols-1 gap-4 rounded-xl border border-line p-4 transition-opacity sm:grid-cols-2 dark:border-night-line',
                        'opacity-50 pointer-events-none select-none' => ! $user_referrals_enable,
                    ])>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">هدیه فرد معرفی‌کننده</label>
                            <select wire:model="user_referrals_gift_type" class="ap-input">
                                <option value="discount_code">ارسال کد تخفیف</option>
                                <option value="wallet">ارسال به کیف پول</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">نوع تخفیف</label>
                            <select wire:model="user_referrals_gift_discount_type" class="ap-input">
                                <option value="percent">درصد</option>
                                <option value="amount">مبلغ</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">تخفیف معرفی کننده</label>
                            <input type="number" min="0" dir="ltr" wire:model="owner_referrals_amount" class="ap-input font-mono">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">تخفیف معرفی شونده</label>
                            <input type="number" min="0" dir="ltr" wire:model="user_referrals_amount" class="ap-input font-mono">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">حداقل مبلغ خرید برای دریافت هدیه ({{ currencyTitle() }})</label>
                            <input type="number" min="0" dir="ltr" wire:model="minimum_amount_gift" class="ap-input font-mono">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">حداقل تعداد محصول برای دریافت هدیه</label>
                            <input type="number" min="1" max="10" dir="ltr" wire:model="minimum_product_gift" class="ap-input font-mono">
                        </div>
                    </div>
                </section>
                ═══════════ پایان بخش منتقل‌شده به پکیج ═══════════ --}}
            </div>

            {{-- ============ تصاویر ============ --}}
            <div x-show="tab === 'images'" x-cloak class="animate-fade-in space-y-5 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">درصد بهینه‌سازی و کاهش حجم (۱ تا ۹۹)</label>
                        <input type="text" dir="ltr" wire:model="optimizeImage" class="ap-input font-mono">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">تبدیل تصاویر به</label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (['webp' => 'WebP', 'jpg' => 'JPG', 'png' => 'PNG'] as $format => $label)
                                <label class="flex cursor-pointer items-center justify-center gap-1.5 rounded-xl border p-2.5 text-xs font-bold transition-colors has-checked:border-brand-500 has-checked:bg-brand-50/60 dark:has-checked:bg-brand-500/10 @if($changePhotoFormat === $format) border-brand-500 bg-brand-50/60 dark:bg-brand-500/10 @else border-line dark:border-night-line @endif">
                                    <input type="radio" value="{{ $format }}" wire:model="changePhotoFormat" class="sr-only">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <section>
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="droplets" class="size-4 text-brand-600 dark:text-brand-400" />
                        واترمارک تصاویر
                    </h3>
                    @include('admin-panel::livewire.admin.settings.partials.setting-switch', [
                        'name'  => 'watermarkStatus',
                        'live'  => true,
                        'icon'  => 'stamp',
                        'title' => 'وضعیت واترمارک',
                        'desc'  => 'روی تصاویر محصولات آپلودی، واترمارک سایت درج می‌شود.',
                    ])
                    <div @class([
                        'mt-4 grid grid-cols-1 gap-4 rounded-xl border border-line p-4 transition-opacity sm:grid-cols-2 dark:border-night-line',
                        'opacity-50 pointer-events-none select-none' => ! $watermarkStatus,
                    ])>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">موقعیت واترمارک</label>
                            <select wire:model="watermarkImagePosition" class="ap-input">
                                <option value="top-left">بالا چپ</option>
                                <option value="top">بالا وسط</option>
                                <option value="top-right">بالا راست</option>
                                <option value="left">وسط چپ</option>
                                <option value="center">وسط</option>
                                <option value="right">وسط راست</option>
                                <option value="bottom-left">پایین چپ</option>
                                <option value="bottom">پایین وسط</option>
                                <option value="bottom-right">پایین راست</option>
                            </select>
                        </div>
                        <div>
                            <x-admin-panel::media-picker
                                wireModel="watermarkImage"
                                :value="$current_watermarkImage"
                                label="تصویر واترمارک"
                                hint="از رسانه‌های سایت انتخاب کنید"
                                height="h-24"
                                aspect="contain"
                            />
                        </div>
                    </div>
                </section>
            </div>

            {{-- ============ Pusher ============ --}}
            <div x-show="tab === 'pusher'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                @php
                    $pusherReady = $PUSHER_APP_ID !== '' && $PUSHER_APP_KEY !== '' && $PUSHER_APP_SECRET !== '';
                @endphp
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-4
                            {{ $pusherReady
                                ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/20 dark:bg-emerald-500/10'
                                : 'border-line bg-surface-2 dark:border-night-line dark:bg-night-3' }}">
                    <span class="flex items-center gap-2 text-xs font-bold {{ $pusherReady ? 'text-emerald-700 dark:text-emerald-400' : 'text-ink-3 dark:text-night-ink-2' }}">
                        <x-admin-panel::icon name="{{ $pusherReady ? 'check-circle' : 'circle-off' }}" class="size-4" />
                        @if ($pusherReady)
                            پخش رویدادها با Pusher فعال است
                        @else
                            پخش رویدادها در حالت log است (Pusher کامل نشده)
                        @endif
                    </span>
                    <span class="rounded-lg bg-surface px-2.5 py-1 font-mono text-[11px] text-ink-3 dark:bg-night-2 dark:text-night-ink-2" dir="ltr">
                        BROADCAST_DRIVER={{ $pusherReady ? 'pusher' : 'log' }}
                    </span>
                </div>
                <p class="text-xs leading-6 text-ink-3 dark:text-night-ink-2">
                    اگر هر سه مقدار شناسه، کلید و رمز Pusher پر شوند، درایور پخش به‌صورت خودکار روی pusher تنظیم می‌شود (وگرنه log).
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">PUSHER_APP_ID</label>
                        <input type="text" dir="ltr" wire:model="PUSHER_APP_ID" class="ap-input font-mono">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">PUSHER_APP_KEY</label>
                        <input type="text" dir="ltr" wire:model="PUSHER_APP_KEY" class="ap-input font-mono">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">PUSHER_APP_SECRET</label>
                        <input type="password" dir="ltr" wire:model="PUSHER_APP_SECRET" class="ap-input font-mono">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">PUSHER_APP_CLUSTER</label>
                        <input type="text" dir="ltr" wire:model="PUSHER_APP_CLUSTER" class="ap-input font-mono" placeholder="ap2">
                    </div>
                </div>
            </div>

            {{-- ============ قابلیت‌های فروشگاه (فاز ۸۲/۸۳ — تنظیمات سایت + شخصی‌سازی کامل با پیش‌نمایش زنده) ============ --}}
            <div x-show="tab === 'features'" x-cloak class="animate-fade-in space-y-5 p-5 sm:p-6">
                @include('admin-panel::livewire.admin.settings.partials.price-alert-settings')

                {{-- پیش‌فروش --}}
                <section>
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="hourglass" class="size-4 text-brand-600 dark:text-brand-400" />
                        پیش‌فروش
                    </h3>
                    @include('admin-panel::livewire.admin.settings.partials.setting-switch', [
                        'name'  => 'dt_pre_order_status',
                        'live'  => true,
                        'icon'  => 'hourglass',
                        'title' => 'قابلیت پیش‌فروش محصولات',
                        'desc'  => 'محصولاتی که مدیر برایشان تیک پیش‌فروش زده باشد با نشان «پیش‌فروش» در سایت دیده می‌شوند و مشتری می‌تواند حداکثر یک عدد پیش‌خرید کند.',
                    ])
                    <div @class([
                        'mt-4 grid grid-cols-1 gap-4 rounded-xl border border-line p-4 transition-opacity sm:grid-cols-2 dark:border-night-line',
                        'opacity-50 pointer-events-none select-none' => ! $dt_pre_order_status,
                    ])>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">متن دکمه پیش‌فروش</label>
                            <input type="text" wire:model="dt_pre_order_button_text" class="ap-input" placeholder="ثبت پیش‌فروش">
                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">خالی بماند = پیش‌فرض «ثبت پیش‌فروش»</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">متن نشان پیش‌فروش</label>
                            <input type="text" wire:model="dt_pre_order_badge_text" class="ap-input" placeholder="پیش‌فروش">
                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">در کارت‌ها، صفحه محصول، سبد و سفارش‌ها — خالی بماند = پیش‌فرض «پیش‌فروش»</p>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ============ ایمیل ============ --}}
            <div x-show="tab === 'mail'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">Transport</label>
                        <input type="text" dir="ltr" wire:model="transport" class="ap-input font-mono" placeholder="smtp">
                        <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">مثل قدیمی به‌عنوان تنظیم ذخیره می‌شود.</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">MAIL_ENCRYPTION</label>
                        <input type="text" dir="ltr" wire:model="MAIL_ENCRYPTION" class="ap-input font-mono" placeholder="tls">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">MAIL_HOST</label>
                        <input type="text" dir="ltr" wire:model="MAIL_HOST" class="ap-input font-mono" placeholder="smtp.site.ir">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">MAIL_PORT</label>
                        <input type="number" dir="ltr" wire:model="MAIL_PORT" class="ap-input font-mono" placeholder="587">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">MAIL_USERNAME</label>
                        <input type="text" dir="ltr" wire:model="MAIL_USERNAME" class="ap-input font-mono" autocomplete="off">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">MAIL_PASSWORD</label>
                        <input type="password" dir="ltr" wire:model="MAIL_PASSWORD" class="ap-input font-mono" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>

        {{-- نوار ذخیره --}}
        <div class="sticky bottom-4 z-10 flex items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-lg backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <span class="flex items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="shield" class="size-3.5" />
                تغییرات پس از ذخیره‌سازی روی کل سایت اعمال می‌شوند.
            </span>
            <button type="submit" class="ap-btn-primary !py-2.5">
                <x-admin-panel::icon name="check-circle" class="size-4" />
                ذخیره تغییرات
            </button>
        </div>
    </form>
</div>
