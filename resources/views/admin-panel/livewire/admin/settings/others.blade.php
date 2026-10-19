{{-- تنظیمات دیگر — پنل جدید (فاز ۷-ج۳) — پورت back/settings/others.blade.php --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-5xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="sliders-horizontal" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-bold">تنظیمات دیگر</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">چند فروشندگی، فاکتور، کاربران، تصاویر، Pusher و ایمیل</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" x-data="{ tab: 'vendor' }" class="space-y-5">

        <div class="ap-card overflow-hidden">
            <div class="flex gap-1 overflow-x-auto border-b border-line bg-surface-2 px-2 pt-2 dark:border-night-line dark:bg-night-3/50" role="tablist">
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'vendor'"
                        x-bind:class="tab === 'vendor' ? 'is-active' : ''">
                    <x-admin-panel::icon name="shopping-bag" class="size-4" />
                    چند فروشندگی
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'factor'"
                        x-bind:class="tab === 'factor' ? 'is-active' : ''">
                    <x-admin-panel::icon name="receipt" class="size-4" />
                    فاکتور
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'users'"
                        x-bind:class="tab === 'users' ? 'is-active' : ''">
                    <x-admin-panel::icon name="users" class="size-4" />
                    کاربران
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'images'"
                        x-bind:class="tab === 'images' ? 'is-active' : ''">
                    <x-admin-panel::icon name="image" class="size-4" />
                    تصاویر
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'pusher'"
                        x-bind:class="tab === 'pusher' ? 'is-active' : ''">
                    <x-admin-panel::icon name="waypoints" class="size-4" />
                    Pusher
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'mail'"
                        x-bind:class="tab === 'mail' ? 'is-active' : ''">
                    <x-admin-panel::icon name="mail" class="size-4" />
                    ایمیل
                </button>
            </div>

            {{-- ============ چند فروشندگی ============ --}}
            <div x-show="tab === 'vendor'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="max-w-md">
                    <label class="mb-1.5 block text-xs font-semibold">سیستم چند فروشندگی</label>
                    <select wire:model="multi_vendor_system_status" class="ap-input">
                        <option value="true">فعال</option>
                        <option value="false">غیرفعال</option>
                    </select>
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">غیرفعال کردن، صفحات فروشندگان را از سایت و پنل برمی‌دارد.</p>
                </div>
            </div>

            {{-- ============ فاکتور ============ --}}
            <div x-show="tab === 'factor'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">لوگو</label>
                        @if ($factor_logo)
                            <img src="{{ $factor_logo->temporaryUrl() }}" alt="پیش‌نمایش لوگو فاکتور" class="mb-2 h-14 w-auto rounded-lg border border-line bg-surface-2 object-contain p-1 dark:border-night-line dark:bg-night-3">
                        @elseif ($current_factor_logo)
                            <img src="{{ asset($current_factor_logo) }}" alt="لوگو فاکتور" class="mb-2 h-14 w-auto rounded-lg border border-line bg-surface-2 object-contain p-1 dark:border-night-line dark:bg-night-3">
                        @endif
                        <input type="file" accept="image/*" wire:model="factor_logo" class="w-full cursor-pointer rounded-xl border border-line bg-transparent p-1.5 text-xs file:me-2 file:rounded-lg file:border-0 file:bg-brand-50 file:px-2 file:py-1 file:text-brand-700 dark:border-night-line dark:file:bg-night-3 dark:file:text-night-ink-1">
                        @error('factor_logo') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">عنوان فاکتور</label>
                        <input type="text" wire:model="factor_title" class="ap-input">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">فروشنده</label>
                        <input type="text" wire:model="factor_seller_name" class="ap-input">
                    </div>
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
            </div>

            {{-- ============ کاربران ============ --}}
            <div x-show="tab === 'users'" x-cloak class="animate-fade-in space-y-6 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">اعتبار هدیه ثبت‌نام کاربر</label>
                        <input type="number" min="0" dir="ltr" wire:model="user_register_gift_credit" class="ap-input font-mono">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">فعال کردن امکان معرفی افراد</label>
                        <select wire:model="user_referrals_enable" class="ap-input">
                            <option value="true">بله</option>
                            <option value="false">خیر</option>
                        </select>
                    </div>
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
            </div>

            {{-- ============ تصاویر ============ --}}
            <div x-show="tab === 'images'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">درصد بهینه‌سازی و کاهش حجم (۱ تا ۹۹)</label>
                        <input type="text" dir="ltr" wire:model="optimizeImage" class="ap-input font-mono">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">تبدیل تصاویر به</label>
                        <select wire:model="changePhotoFormat" class="ap-input">
                            <option value="webp">webp</option>
                            <option value="jpg">jpg</option>
                            <option value="png">png</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">وضعیت واترمارک</label>
                        <select wire:model="watermarkStatus" class="ap-input">
                            <option value="true">فعال</option>
                            <option value="false">غیرفعال</option>
                        </select>
                    </div>
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
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">تصویر واترمارک</label>
                        @if ($watermarkImage)
                            <img src="{{ $watermarkImage->temporaryUrl() }}" alt="پیش‌نمایش واترمارک" class="mb-2 h-14 w-auto rounded-lg border border-line bg-surface-2 object-contain p-1 dark:border-night-line dark:bg-night-3">
                        @elseif ($current_watermarkImage)
                            <img src="{{ asset($current_watermarkImage) }}" alt="واترمارک" class="mb-2 h-14 w-auto rounded-lg border border-line bg-surface-2 object-contain p-1 dark:border-night-line dark:bg-night-3">
                        @endif
                        <input type="file" accept="image/*" wire:model="watermarkImage" class="w-full cursor-pointer rounded-xl border border-line bg-transparent p-1.5 text-xs file:me-2 file:rounded-lg file:border-0 file:bg-brand-50 file:px-2 file:py-1 file:text-brand-700 dark:border-night-line dark:file:bg-night-3 dark:file:text-night-ink-1">
                        @error('watermarkImage') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ============ Pusher ============ --}}
            <div x-show="tab === 'pusher'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="rounded-xl border border-sky-200 bg-sky-50 p-3 text-xs leading-6 text-sky-800 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                    <x-admin-panel::icon name="info" class="me-1 inline size-3.5" />
                    اگر هر سه مقدار شناسه، کلید و رمز Pusher پر شوند، درایور پخش به‌صورت خودکار روی pusher تنظیم می‌شود (وگرنه log).
                </div>
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
                        <input type="text" dir="ltr" wire:model="PUSHER_APP_CLUSTER" class="ap-input font-mono">
                    </div>
                </div>
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
                        <label class="mb-1.5 block text-xs font-semibold">MAIL_HOST</label>
                        <input type="text" dir="ltr" wire:model="MAIL_HOST" class="ap-input font-mono" placeholder="smtp.site.ir">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">MAIL_PORT</label>
                        <input type="text" dir="ltr" wire:model="MAIL_PORT" class="ap-input font-mono" placeholder="587">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">MAIL_USERNAME</label>
                        <input type="text" dir="ltr" wire:model="MAIL_USERNAME" class="ap-input font-mono">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">MAIL_PASSWORD</label>
                        <input type="password" dir="ltr" wire:model="MAIL_PASSWORD" class="ap-input font-mono">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">MAIL_ENCRYPTION</label>
                        <input type="text" dir="ltr" wire:model="MAIL_ENCRYPTION" class="ap-input font-mono" placeholder="tls">
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
