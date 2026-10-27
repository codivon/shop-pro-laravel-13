{{-- تنظیمات توسعه‌دهنده — پنل جدید (فاز ۷-ج۳) — پورت back/developer/settings.blade.php + updater.blade.php --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-5xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">
                <x-admin-panel::icon name="code-2" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-bold">تنظیمات توسعه‌دهنده</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">کرون جاب، حالت بروزرسانی، وب‌پوش، کش و آپدیت‌کننده</p>
            </div>
        </div>
        <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">فقط سازنده</span>
    </div>

    <div x-data="{ tab: 'cron' }" class="space-y-5">

        {{-- تب‌ها --}}
        <div class="ap-card overflow-hidden">
            <div class="flex gap-1 overflow-x-auto border-b border-line bg-surface-2 px-2 pt-2 dark:border-night-line dark:bg-night-3/50" role="tablist">
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'cron'"
                        x-bind:class="tab === 'cron' ? 'is-active' : ''">
                    <x-admin-panel::icon name="clock" class="size-4" />
                    کرون جاب
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'maintenance'"
                        x-bind:class="tab === 'maintenance' ? 'is-active' : ''">
                    <x-admin-panel::icon name="triangle-alert" class="size-4" />
                    حالت بروزرسانی سایت
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'general'"
                        x-bind:class="tab === 'general' ? 'is-active' : ''">
                    <x-admin-panel::icon name="settings" class="size-4" />
                    تنظیمات دیگر
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'updater'"
                        x-bind:class="tab === 'updater' ? 'is-active' : ''">
                    <x-admin-panel::icon name="refresh-cw" class="size-4" />
                    آپدیت‌کننده
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'security'"
                        x-bind:class="tab === 'security' ? 'is-active' : ''">
                    <x-admin-panel::icon name="shield" class="size-4" />
                    امنیت
                </button>
                {{-- فاز ۹۹ — تنظیمات بازارچه --}}
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'marketplace'"
                        x-bind:class="tab === 'marketplace' ? 'is-active' : ''">
                    <x-admin-panel::icon name="store" class="size-4" />
                    بازارچه
                </button>
                {{-- فاز ۱۰۳ — تنظیمات چندفروشندگی --}}
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'vendors'"
                        x-bind:class="tab === 'vendors' ? 'is-active' : ''">
                    <x-admin-panel::icon name="store" class="size-4" />
                    چندفروشندگی
                </button>
            </div>

            {{-- ============ تب کرون جاب ============ --}}
            <div x-show="tab === 'cron'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">

                @if ($scheduleRun)
                    <div class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <x-admin-panel::icon name="check-circle" class="size-4 shrink-0" />
                        <div>
                            <p class="text-[13px] font-bold">کرون جاب فعال است</p>
                            <p class="text-xs">زمان‌بندی خودکار لاراول روی سرور در حال اجراست. آخرین اجرا: <span class="font-mono" dir="ltr">{{ $scheduleLastRun }}</span></p>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                        <p class="flex items-center gap-1.5 text-[13px] font-bold">
                            <x-admin-panel::icon name="triangle-alert" class="size-4" />
                            کرون جاب تنظیم نشده است
                        </p>
                        <p class="mt-1.5 text-xs leading-6">برای فعال‌سازی زمان‌بندی لاراول، باید یک کرون جاب روی سرور تنظیم کنید. دستور کرون به شکل زیر است:</p>
                        <code class="mt-2 block select-all rounded-lg bg-surface px-3 py-2 font-mono text-[11px] dark:bg-night-3" dir="ltr">* * * * * [مسیر PHP] [مسیر artisan] schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>
                        @if ($scheduleLastRun)
                            <p class="mt-2 text-xs">آخرین اجرا قبل از قطع شدن: <span class="font-mono" dir="ltr">{{ $scheduleLastRun }}</span></p>
                        @endif
                    </div>
                @endif

                <div class="flex items-start gap-2 rounded-xl border border-sky-200 bg-sky-50 p-3.5 text-sky-800 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    <div class="text-xs leading-6">
                        <p class="font-bold">اجرای صف از طریق Cron Job، مخصوص هاست اشتراکی</p>
                        <p>اگر هاست شما اجازه اجرای دائمی Queue Worker را نمی‌دهد، این حالت را فعال کنید. Queue به جای اجرای مداوم، توسط دو Cron Job مدیریت می‌شود.</p>
                    </div>
                </div>

                <form wire:submit="saveSharedHosting" class="space-y-3">
                    <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                        <span class="text-[13px] font-bold">فعال‌سازی اجرای صف با دو Cron Job (هاست اشتراکی)</span>
                        <x-admin-panel::toggle wire:model="sharedHostingCronEnabled" aria-label="فعال‌سازی اجرای صف با دو Cron Job" />
                    </div>

                    <div class="rounded-xl border border-line p-4 dark:border-night-line">
                        <p class="text-xs font-bold">دستورهایی که باید در پنل Cron Job هاست خود وارد کنید:</p>
                        <div class="mt-3 space-y-3">
                            <div>
                                <small class="text-[11px] text-ink-3 dark:text-night-ink-2">① زمان‌بندی لاراول، هر دقیقه</small>
                                <code class="mt-1 block select-all rounded-lg bg-surface-2 px-3 py-2 font-mono text-[11px] dark:bg-night-3" dir="ltr">* * * * * [مسیر PHP] [مسیر artisan] schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>
                            </div>
                            <div>
                                <small class="text-[11px] text-ink-3 dark:text-night-ink-2">② اجرای صف، یک‌بار خالی‌سازی و توقف</small>
                                <code class="mt-1 block select-all rounded-lg bg-surface-2 px-3 py-2 font-mono text-[11px] dark:bg-night-3" dir="ltr">* * * * * [مسیر PHP] [مسیر artisan] queue:work --tries=3 --sleep=3 --queue=default --stop-when-empty</code>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="ap-btn-primary !py-2.5">
                        <x-admin-panel::icon name="check-circle" class="size-4" />
                        ذخیره تنظیمات صف
                    </button>
                </form>
            </div>

            {{-- ============ تب حالت بروزرسانی ============ --}}
            <div x-show="tab === 'maintenance'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">

                @if ($this->maintenanceIsDown)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                        <p class="text-[13px] font-bold">سایت هم‌اکنون در حالت بروزرسانی است.</p>
                        <p class="mt-1 text-xs">برای بازگشت به حالت عادی، دکمه‌ی زیر را بزنید.</p>
                        <button type="button" wire:click="upApplication" wire:confirm="حالت بروزرسانی غیرفعال شود؟"
                                class="ap-btn-primary mt-3 !py-2.5">
                            <x-admin-panel::icon name="check-circle" class="size-4" />
                            غیرفعال کردن حالت بروزرسانی
                        </button>
                    </div>
                @else
                    <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sky-800 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                        <p class="text-[13px] font-bold">فعال‌سازی حالت بروزرسانی (سایت برای بازدیدکنندگان بسته می‌شود)</p>
                        <p class="mt-1 text-xs leading-6">
                            پس از فعال کردن حالت بروزرسانی، شما می‌توانید از این آدرس وارد وبسایت شوید:
                            <span class="font-mono" dir="ltr">{{ url('/') }}/{{ $maintenanceSecret }}</span>
                        </p>
                    </div>

                    <form wire:submit="downApplication" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold">Secret <span class="text-red-500">*</span></label>
                                <input type="text" dir="ltr" wire:model="maintenanceSecret" class="ap-input font-mono">
                                @error('maintenanceSecret') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold">عنوان</label>
                                <input type="text" wire:model="info_maintenance_mode_title" class="ap-input">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold">توضیحات</label>
                                <textarea rows="4" wire:model="info_maintenance_mode_description" class="ap-input"></textarea>
                            </div>
                        </div>
                        <button type="submit" wire:confirm="سایت برای همه‌ی بازدیدکنندگان بسته شود؟"
                                class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-red-700">
                            <x-admin-panel::icon name="triangle-alert" class="me-1 inline size-4" />
                            فعال کردن حالت بروزرسانی
                        </button>
                    </form>
                @endif

                {{-- وب‌پوش --}}
                <div class="border-t border-line pt-4 dark:border-night-line">
                    <h3 class="mb-2 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="bell-ring" class="size-4 text-brand-600 dark:text-brand-400" />
                        اعلانات وب پوش
                    </h3>
                    <button type="button" wire:click="webpushNotification" wire:confirm="کلیدهای جدید VAPID ساخته و در .env نوشته شود؟"
                            class="ap-btn-primary !py-2.5">
                        <x-admin-panel::icon name="key-round" class="size-4" />
                        تنظیم کلیدهای وب پوش
                    </button>
                    <div class="mt-3 rounded-xl border border-line bg-surface-2 p-3.5 text-xs leading-6 dark:border-night-line dark:bg-night-3/60">
                        <p><strong>VAPID_PUBLIC_KEY:</strong> <span class="font-mono break-all" dir="ltr">{{ config('webpush.vapid.public_key') }}</span></p>
                        <p class="mt-1"><strong>VAPID_PRIVATE_KEY:</strong> <span class="font-mono break-all" dir="ltr">{{ config('webpush.vapid.private_key') }}</span></p>
                    </div>
                </div>
            </div>

            {{-- ============ تب تنظیمات دیگر ============ --}}
            <div x-show="tab === 'general'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <form wire:submit="saveGeneral" class="space-y-5">
                    <div class="max-w-md">
                        <label class="mb-1.5 block text-xs font-semibold">شماره سفارش (توکن آپدیت‌کننده)</label>
                        <input type="text" dir="ltr" wire:model="updaterToken" class="ap-input font-mono">
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                            <span>
                                <span class="block text-[13px] font-bold">حالت توسعه</span>
                                <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">APP_DEBUG — در حالت توسعه فایل‌ها کامپایل نشده‌اند</span>
                            </span>
                            <x-admin-panel::toggle wire:model="appDebugMode" size="sm" aria-label="حالت توسعه (APP_DEBUG)" />
                        </div>

                        <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                            <span>
                                <span class="block text-[13px] font-bold">دیباگ بار</span>
                                <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">DEBUGBAR_ENABLED</span>
                            </span>
                            <x-admin-panel::toggle wire:model="debugbarEnabled" size="sm" aria-label="دیباگ بار" />
                        </div>

                        <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                            <span>
                                <span class="block text-[13px] font-bold">متد قدیمی آپدیت</span>
                                <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">این متد اکسپایر شده</span>
                            </span>
                            <x-admin-panel::toggle wire:model="enableOldUpdates" size="sm" aria-label="متد قدیمی آپدیت" />
                        </div>

                        <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                            <span>
                                <span class="block text-[13px] font-bold">داشبورد Pulse برای مدیران</span>
                                <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">آدرس /pulse (پایش سرور و کوئری‌ها) برای ادمین‌های لاگین‌کردهٔ پنل باز شود</span>
                            </span>
                            <x-admin-panel::toggle wire:model="pulseAdminAccess" size="sm" aria-label="داشبورد Pulse برای مدیران" />
                        </div>
                    </div>

                    <button type="submit" class="ap-btn-primary !py-2.5">
                        <x-admin-panel::icon name="check-circle" class="size-4" />
                        ذخیره تغییرات
                    </button>
                </form>

                {{-- پاک‌سازی کش --}}
                <div class="border-t border-line pt-4 dark:border-night-line">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface-2 p-4 dark:border-night-line dark:bg-night-3/60">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                                <x-admin-panel::icon name="database" class="size-5" />
                            </span>
                            <div>
                                <p class="text-[13px] font-bold">پاک‌سازی کش برنامه</p>
                                <p class="text-xs text-ink-3 dark:text-night-ink-2">کش تنظیمات، مسیرها، ویوها و سایر کش‌های لاراول پاک می‌شوند.</p>
                            </div>
                        </div>
                        <button type="button" wire:click="clearCache"
                                class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-[13px] font-bold text-red-700 transition-colors hover:bg-red-100 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
                            <x-admin-panel::icon name="trash-2" class="me-1 inline size-4" />
                            پاک‌سازی کش
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============ تب آپدیت‌کننده ============ --}}
            <div x-show="tab === 'updater'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs leading-6 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                    <x-admin-panel::icon name="triangle-alert" class="me-1 inline size-3.5" />
                    لطفا توجه داشته باشید در صورتی که نرم افزار را شخصی سازی کرده اید و در کدها تغییراتی ایجاد کرده اید، با بروزرسانی نرم افزار تغییرات از بین می روند.
                </div>

                <div class="rounded-xl border border-line p-4 dark:border-night-line">
                    <p class="text-[13px] font-bold">نسخه فعلی نرم‌افزار شما <span class="ap-badge bg-brand-50 font-mono text-brand-700 dark:bg-brand-500/12 dark:text-brand-400" dir="ltr">{{ $versionInstalled }}</span></p>

                    @if ($updateErrors)
                        <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
                            {{ $updateErrors }}
                        </div>
                    @elseif ($isNewVersionAvailable)
                        <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400">
                            نسخه {{ $versionAvailable }} موجود است و هم اکنون می‌توانید نرم‌افزار را بروزرسانی کنید.
                        </div>
                        @if ($updateDescription)
                            <div class="mt-3 rounded-lg border border-brand-200 bg-brand-50/60 p-3 dark:border-brand-500/20 dark:bg-brand-500/10">
                                <p class="mb-2 flex items-center gap-1.5 text-xs font-bold text-brand-700 dark:text-brand-400">
                                    <x-admin-panel::icon name="layers" class="size-3.5" />
                                    تغییرات نسخه {{ $versionAvailable }}
                                </p>
                                <div class="space-y-1 text-xs leading-6 text-ink-2 dark:text-night-ink-2">
                                    {!! nl2br(e($updateDescription)) !!}
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="mt-3 rounded-lg border border-line bg-surface-2 p-3 text-xs text-ink-3 dark:border-night-line dark:bg-night-3/60 dark:text-night-ink-2">
                            شما از آخرین نسخه‌ی موجود نرم‌افزار استفاده می‌کنید.
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-2.5">
                        <button type="button" wire:click="checkUpdate" wire:loading.attr="disabled" wire:target="checkUpdate"
                                class="rounded-xl border border-line bg-surface px-4 py-2.5 text-[13px] font-bold transition-colors hover:bg-surface-3 dark:border-night-line dark:bg-night-2 dark:hover:bg-night-3">
                            <x-admin-panel::icon name="refresh-cw" class="me-1 inline size-4" wire:loading wire:target="checkUpdate" />
                            بررسی مجدد
                        </button>

                        @if ($isNewVersionAvailable)
                            <button type="button" wire:click="updateApplication" wire:confirm="بروزرسانی نرم‌افزار شروع شود؟ تغییرات شخصی‌سازی از بین می‌رود."
                                    class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-emerald-700">
                                <x-admin-panel::icon name="refresh-cw" class="me-1 inline size-4" />
                                بروزرسانی
                            </button>
                        @endif

                        <button type="button" wire:click="updaterAfter"
                                class="ap-btn-primary !py-2.5">
                            <x-admin-panel::icon name="settings-2" class="size-4" />
                            اجرای دستورات بعد از بروزرسانی
                        </button>

                        @if ($this->updateStatus['status'] !== 'idle')
                            <button type="button" wire:click="resetUpdate" wire:confirm="وضعیت بروزرسانی ریست شود؟"
                                    class="ap-btn-outline !py-2.5">
                                ریست وضعیت بروزرسانی
                            </button>
                        @endif
                    </div>
                </div>

                {{-- وضعیت زنده --}}
                @if ($this->updateStatus['status'] !== 'idle')
                    <div wire:poll.2s class="rounded-xl border border-line p-4 dark:border-night-line">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[13px] font-bold">پیشرفت بروزرسانی</p>
                            <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2" dir="ltr">
                                {{ $this->updateStatus['status'] }} @if(isset($this->updateStatus['progress'])) — {{ $this->updateStatus['progress'] }}% @endif
                            </span>
                        </div>
                        <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-surface-3 dark:bg-night-3">
                            <div class="h-full rounded-full bg-brand-500 transition-all"
                                 style="width: {{ $this->updateStatus['progress'] ?? 0 }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-ink-3 dark:text-night-ink-2">{{ $this->updateStatus['message'] ?? 'در حال آماده‌سازی...' }}</p>
                    </div>
                @endif

                {{-- تاریخچه نسخه‌ها (پورت ثابت قدیمی) --}}
                <div class="rounded-xl border border-line p-4 dark:border-night-line">
                    <p class="mb-3 text-[13px] font-bold">تغییرات بروزرسانی ها</p>
                    <ul class="space-y-3 text-xs leading-6">
                        <li class="flex gap-2.5">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full bg-emerald-500"></span>
                            <span><strong>نسخه 1.1.1</strong> — انتشار اولین نسخه، ییبی، بلبیب <small class="text-ink-3">(07 تیر 1405)</small></span>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full bg-emerald-500"></span>
                            <span><strong>نسخه 1.0.0</strong> — انتشار اولین نسخه <small class="text-ink-3">(06 تیر 1405)</small></span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- ============ تب امنیت (فاز ۴۴ — بازطراحی مدرن در فاز ۴۵) ============ --}}
            <div x-show="tab === 'security'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <form wire:submit="saveSecurity" class="space-y-5">

                    {{-- ───── کپچا (ربات‌گیر) ───── --}}
                    <section x-data="{ main: @js((bool) $securityCaptchaEnabled) }" class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="bot" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">ربات‌گیر (کپچا)</h3>
                            <span class="ms-auto text-[10px] font-semibold text-ink-3 dark:text-night-ink-2">تشخیص انسان از ربات</span>
                        </div>
                        <div class="space-y-3 p-4">
                            {{-- کلید اصلی — سوییچ بزرگ --}}
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-brand-200 bg-gradient-to-l from-brand-50/90 via-brand-50/40 to-transparent p-3.5 dark:border-brand-500/20 dark:from-brand-500/10 dark:via-brand-500/5 dark:to-transparent">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                        <x-admin-panel::icon name="key-round" class="size-4.5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-1.5">
                                            <span class="text-[13px] font-bold">کلید اصلی کپچا</span>
                                            <span class="rounded-md bg-brand-600/10 px-1.5 py-0.5 text-[10px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">کلید اصلی</span>
                                        </span>
                                        <span class="block text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">با خاموش‌کردن این کلید، کپچا در همه‌ی نقاط غیرفعال می‌شود</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="securityCaptchaEnabled" x-on:change="main = $event.target.checked" aria-label="کلید اصلی کپچا" />
                            </div>

                            {{-- نقاط فعال‌سازی — با خاموشی کلید اصلی کم‌رنگ می‌شوند --}}
                            <div class="grid grid-cols-1 gap-3 transition-opacity duration-300 sm:grid-cols-2" x-bind:class="!main ? 'pointer-events-none opacity-40' : ''" aria-hidden="{{ !((bool) $securityCaptchaEnabled) ? 'true' : 'false' }}">
                                @foreach ([
                                    ['key' => 'securityCaptchaAdminLogin',    'label' => 'ورود مدیر',            'desc' => 'فرم ورود هر دو پنل مدیریت',        'icon' => 'user-cog'],
                                    ['key' => 'securityCaptchaRegister',      'label' => 'ثبت‌نام مشتری',        'desc' => 'فرم ایجاد حساب جدید فروشگاه',      'icon' => 'users'],
                                    ['key' => 'securityCaptchaCustomerLogin', 'label' => 'ورود مشتری',           'desc' => 'قدم اول ورود (موبایل/ایمیل)',      'icon' => 'user'],
                                    ['key' => 'securityCaptchaComments',      'label' => 'دیدگاه‌ها و بررسی‌ها', 'desc' => 'سوال محصول، دیدگاه وبلاگ، امتیاز', 'icon' => 'message-square'],
                                ] as $point)
                                    <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                        <span class="flex min-w-0 items-center gap-2.5">
                                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                                <x-admin-panel::icon name="{{ $point['icon'] }}" class="size-4" />
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-[13px] font-bold">{{ $point['label'] }}</span>
                                                <span class="block truncate text-[11px] text-ink-3 dark:text-night-ink-2">{{ $point['desc'] }}</span>
                                            </span>
                                        </span>
                                        <x-admin-panel::toggle wire:model="{{ $point['key'] }}" size="sm" aria-label="کپچای {{ $point['label'] }}" />
                                    </div>
                                @endforeach
                            </div>

                            {{-- تلهٔ زمان --}}
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line p-3.5 transition-opacity duration-300 dark:border-night-line" x-bind:class="!main ? 'pointer-events-none opacity-40' : ''">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="clock" class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[13px] font-bold">تلهٔ زمان — حداقل ثانیه‌ی پرکردن فرم</span>
                                        <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">ارسال سریع‌تر از این مقدار، رفتار ربات تلقی و رد می‌شود (صفر = غیرفعال)</span>
                                    </span>
                                </span>
                                <span class="flex items-center gap-2">
                                    <input type="number" min="0" max="300" wire:model="securityTimeTrapSeconds" class="ap-input w-24 text-center font-mono" aria-label="حداقل ثانیه پر کردن فرم">
                                    <span class="text-[11px] text-ink-3 dark:text-night-ink-2">ثانیه</span>
                                </span>
                            </div>
                            @error('securityTimeTrapSeconds') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </section>

                    {{-- ───── هانی‌پات ───── --}}
                    <section class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="eye-off" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">هانی‌پات</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                                        <x-admin-panel::icon name="triangle-alert" class="size-4.5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[13px] font-bold">فعال‌بودن هانی‌پات</span>
                                        <span class="block text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">فیلد مخفی‌ای که فقط ربات‌ها پر می‌کنند؛ پرشدنش به مسدودسازی IP منجر می‌شود</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="securityHoneypotEnabled" aria-label="فعال بودن هانی‌پات" />
                            </div>
                        </div>
                    </section>

                    {{-- ───── تلهٔ ربات (فاز ۷۱ — کلید اصلی + لیست مسدودی‌ها) ───── --}}
                    <section class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="bot" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">تلهٔ ربات و مسدودسازی</h3>
                            <span class="ms-auto text-[10px] font-semibold text-ink-3 dark:text-night-ink-2">ضد اسکنر و ربات مخرب</span>
                        </div>
                        <div class="space-y-3 p-4">
                            {{-- کلید اصلی --}}
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-brand-200 bg-gradient-to-l from-brand-50/90 via-brand-50/40 to-transparent p-3.5 dark:border-brand-500/20 dark:from-brand-500/10 dark:via-brand-500/5 dark:to-transparent">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                        <x-admin-panel::icon name="key-round" class="size-4.5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-1.5">
                                            <span class="text-[13px] font-bold">کلید اصلی تلهٔ ربات</span>
                                            <span class="rounded-md bg-brand-600/10 px-1.5 py-0.5 text-[10px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">کلید اصلی</span>
                                        </span>
                                        <span class="block text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">با خاموش‌کردن آن، هیچ آی‌پی‌ای مسدود نمی‌شود و مسدودی‌های موجود هم فوراً بی‌اثر می‌شوند (توصیه: فقط برای رفع قفل موقت خاموش کنید)</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="securityBotTrapEnabled" aria-label="کلید اصلی تله ربات" />
                            </div>

                            {{-- نکتهٔ کش — ریشهٔ سردرگمی «حذف رکورد ولی Forbidden ادامه داشت» --}}
                            <p class="flex items-start gap-2 rounded-xl bg-amber-500/10 p-3 text-[11px] leading-5 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-3.5 shrink-0" />
                                <span>اگر رکورد را مستقیماً از دیتابیس حذف کنید، تا ۱۰ دقیقه مسدودی ممکن است باقی بماند (لیست در کش است). با دکمهٔ «رفع مسدودی» از همین‌جا، کش هم پاک می‌شود و آزادسازی فوراً اثر می‌کند. مسدودی‌ها هم به‌طور خودکار پس از ۲۴ ساعت می‌بَرند.</span>
                            </p>

                            {{-- لیست مسدودی‌های فعال --}}
                            <div class="overflow-hidden rounded-xl border border-line dark:border-night-line">
                                @if (empty($this->activeTraps))
                                    <div class="flex items-center justify-center gap-2 p-6 text-[12px] font-medium text-ink-3 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="shield" class="size-4" />
                                        هیچ آی‌پی مسدودشده‌ای وجود ندارد — همه‌چیز سالم است.
                                    </div>
                                @else
                                    <div class="max-h-64 overflow-y-auto">
                                        <table class="w-full text-[13px]">
                                            <thead>
                                                <tr class="bg-surface-2 text-start dark:bg-night-3/50">
                                                    <th class="p-3 text-start font-bold">آی‌پی</th>
                                                    <th class="p-3 text-start font-bold">تله</th>
                                                    <th class="p-3 text-start font-bold">ضربه‌ها</th>
                                                    <th class="p-3 text-start font-bold">آخرین ضربه</th>
                                                    <th class="p-3 text-start font-bold"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-line dark:divide-night-line">
                                                @foreach ($this->activeTraps as $trap)
                                                    <tr wire:key="trap-{{ $trap['id'] }}">
                                                        <td class="p-3 font-mono text-[12px]" dir="ltr">{{ $trap['ip_address'] }}</td>
                                                        <td class="max-w-40 truncate p-3 text-[12px] text-ink-3 dark:text-night-ink-2" dir="ltr" title="{{ $trap['trap_path'] }}">{{ $trap['trap_path'] }}</td>
                                                        <td class="p-3">
                                                            <span class="rounded-md bg-red-500/10 px-2 py-0.5 text-[11px] font-bold text-red-600 dark:text-red-400">{{ $trap['hit_count'] }}</span>
                                                        </td>
                                                        <td class="p-3 text-[12px] text-ink-3 dark:text-night-ink-2">{{ \Carbon\Carbon::parse($trap['last_hit_at'])->format('Y-m-d H:i') }}</td>
                                                        <td class="p-3 text-end">
                                                            <button type="button" wire:click="unblockIp({{ $trap['id'] }})" class="inline-flex items-center gap-1.5 rounded-lg border border-line px-2.5 py-1.5 text-[11px] font-bold text-ink-2 transition hover:bg-emerald-500/10 hover:text-emerald-600 dark:border-night-line dark:text-night-ink-2 dark:hover:text-emerald-400">
                                                                <x-admin-panel::icon name="rotate-ccw" class="size-3.5" />
                                                                رفع مسدودی
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 border-t border-line p-3 dark:border-night-line">
                                        <span class="text-[11px] text-ink-3 dark:text-night-ink-2">رفع مسدودی، رکورد را حذف و کش را فوراً پاک می‌کند.</span>
                                        <button type="button" wire:click="unblockAllIps" wire:confirm="همهٔ آی‌پی‌های مسدودشده آزاد شوند؟ (تعداد: {{ count($this->activeTraps) }})" class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-500/5 px-3 py-1.5 text-[11px] font-bold text-red-600 transition hover:bg-red-500/10 dark:border-red-500/30 dark:text-red-400">
                                            <x-admin-panel::icon name="trash-2" class="size-3.5" />
                                            رفع مسدودی همه
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>

                    {{-- ───── قفل تلاش‌ها ───── --}}
                    <section x-data="{ main: @js((bool) $securityLockoutEnabled) }" class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="lock" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">قفل تلاش‌ها</h3>
                            <span class="ms-auto text-[10px] font-semibold text-ink-3 dark:text-night-ink-2">ضد حمله‌ی brute-force</span>
                        </div>
                        <div class="space-y-3 p-4">
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-brand-200 bg-gradient-to-l from-brand-50/90 via-brand-50/40 to-transparent p-3.5 dark:border-brand-500/20 dark:from-brand-500/10 dark:via-brand-500/5 dark:to-transparent">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                        <x-admin-panel::icon name="key-round" class="size-4.5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-1.5">
                                            <span class="text-[13px] font-bold">کلید اصلی قفل تلاش‌ها</span>
                                            <span class="rounded-md bg-brand-600/10 px-1.5 py-0.5 text-[10px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">کلید اصلی</span>
                                        </span>
                                        <span class="block text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">با خاموش‌کردن آن، محدودیت تلاش در همه‌ی نقاط برداشته می‌شود (توصیه نمی‌شود)</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="securityLockoutEnabled" x-on:change="main = $event.target.checked" aria-label="کلید اصلی قفل تلاش ها" />
                            </div>

                            <div class="overflow-hidden rounded-xl border border-line transition-opacity duration-300 dark:border-night-line" x-bind:class="!main ? 'pointer-events-none opacity-40' : ''">
                                <table class="w-full text-[13px]">
                                    <thead>
                                        <tr class="bg-surface-2 text-start dark:bg-night-3/50">
                                            <th class="p-3 text-start font-bold">نقطه</th>
                                            <th class="p-3 text-start font-bold">سقف تلاش</th>
                                            <th class="p-3 text-start font-bold">پنجره (دقیقه)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-line dark:divide-night-line">
                                        @foreach ([
                                            ['label' => 'ورود مدیر',             'max' => 'securityLockoutAdminMax',        'minutes' => 'securityLockoutAdminMinutes'],
                                            ['label' => 'ورود مشتری',            'max' => 'securityLockoutCustomerMax',     'minutes' => 'securityLockoutCustomerMinutes'],
                                            ['label' => 'دیدگاه‌ها و بررسی‌ها', 'max' => 'securityLockoutCommentsMax',     'minutes' => 'securityLockoutCommentsMinutes'],
                                        ] as $row)
                                            <tr>
                                                <td class="p-3 font-bold">{{ $row['label'] }}</td>
                                                <td class="p-3">
                                                    <input type="number" min="1" max="100" wire:model="{{ $row['max'] }}" class="ap-input w-24 text-center font-mono" aria-label="سقف تلاش {{ $row['label'] }}">
                                                    @error($row['max']) <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                                                </td>
                                                <td class="p-3">
                                                    <input type="number" min="1" max="1440" wire:model="{{ $row['minutes'] }}" class="ap-input w-24 text-center font-mono" aria-label="پنجره دقیقه {{ $row['label'] }}">
                                                    @error($row['minutes']) <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    {{-- ───── هدرهای امنیتی ───── --}}
                    <section class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="shield" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">هدرهای امنیتی</h3>
                        </div>
                        <div class="space-y-3 p-4">
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400">
                                        <x-admin-panel::icon name="code-2" class="size-4.5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[13px] font-bold">هدرهای استاندارد امنیتی</span>
                                        <span class="block text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">X-Frame-Options، X-Content-Type-Options، Referrer-Policy و Permissions-Policy</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="securityHeadersEnabled" aria-label="هدرهای استاندارد امنیتی" />
                            </div>
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span class="flex min-w-0 items-center justify-between gap-3">
                                    <span class="flex min-w-0 items-center gap-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                                            <x-admin-panel::icon name="shield" class="size-4.5" />
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-[13px] font-bold">HSTS <small class="font-normal text-ink-3 dark:text-night-ink-2" dir="ltr">(Strict-Transport-Security)</small></span>
                                            <span class="block text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">مرورگر را مجبور می‌کند همیشه HTTPS — فقط روی اتصال امن ارسال می‌شود</span>
                                        </span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="securityHstsEnabled" aria-label="HSTS" />
                            </div>
                        </div>
                    </section>

                    {{-- ───── ورود دومرحله‌ای مدیر (2FA) ───── --}}
                    <section x-data="{ main: @js((bool) $adminTwoFactorEnabled) }" class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="user-cog" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">ورود دومرحله‌ای مدیر (2FA)</h3>
                            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">جدید</span>
                        </div>
                        <div class="space-y-3 p-4">
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-brand-200 bg-gradient-to-l from-brand-50/90 via-brand-50/40 to-transparent p-3.5 dark:border-brand-500/20 dark:from-brand-500/10 dark:via-brand-500/5 dark:to-transparent">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                        <x-admin-panel::icon name="key-round" class="size-4.5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[13px] font-bold">فعال‌سازی ورود دومرحله‌ای</span>
                                        <span class="block text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">پس از ورودِ رمز، کد ۶ رقمی یک‌بارمصرف درخواست می‌شود (قفل ۵ تلاش در ۵ دقیقه، ارسال مجدد هر ۱ دقیقه) — برای هر دو پنل</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="adminTwoFactorEnabled" x-on:change="main = $event.target.checked" aria-label="فعال سازی ورود دومرحله ای" />
                            </div>

                            <div class="rounded-xl border border-line p-4 transition-opacity duration-300 dark:border-night-line" x-bind:class="!main ? 'pointer-events-none opacity-40' : ''">
                                <p class="mb-2.5 text-xs font-semibold">کانال ارسال کد</p>
                                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                                    @foreach ([
                                        ['value' => 'email', 'label' => 'ایمیل',  'icon' => 'mail',       'desc' => 'ایمیل ثبت‌شدهٔ مدیر'],
                                        ['value' => 'sms',   'label' => 'پیامک', 'icon' => 'smartphone', 'desc' => 'شماره تلفن مدیر'],
                                        ['value' => 'both',  'label' => 'هر دو', 'icon' => 'layers',     'desc' => 'ایمیل + پیامک هم‌زمان'],
                                    ] as $channel)
                                        <label class="relative block cursor-pointer">
                                            <input type="radio" value="{{ $channel['value'] }}" wire:model="adminTwoFactorChannel" class="peer sr-only">
                                            <div class="flex items-center gap-2.5 rounded-xl border p-3.5 transition-all duration-200 border-line bg-surface-2 hover:border-brand-300/70 dark:border-night-line dark:bg-night-3 dark:hover:border-brand-500/30 peer-checked:border-brand-500 peer-checked:bg-brand-50/70 peer-checked:shadow-sm peer-checked:shadow-brand-500/10 dark:peer-checked:border-brand-500/60 dark:peer-checked:bg-brand-500/10">
                                                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                                    <x-admin-panel::icon name="{{ $channel['icon'] }}" class="size-4" />
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block text-[13px] font-bold">{{ $channel['label'] }}</span>
                                                    <span class="block truncate text-[10px] text-ink-3 dark:text-night-ink-2">{{ $channel['desc'] }}</span>
                                                </span>
                                            </div>
                                            {{-- نشان تیک — خواهرِ ورودی است تا peer-checked رویش اثر بگذارد --}}
                                            <span class="pointer-events-none absolute end-2.5 top-1/2 flex size-5 -translate-y-1/2 scale-50 items-center justify-center rounded-full bg-brand-600 text-white opacity-0 shadow transition-all duration-200 peer-checked:scale-100 peer-checked:opacity-100">
                                                <x-admin-panel::icon name="check" class="size-3" />
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="mt-3 flex items-start gap-1.5 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="info" class="mt-0.5 size-3.5 shrink-0" />
                                    <span>پیامک به «شماره تلفن مدیر» (admin_mobile_number) از تنظیمات پیامک ارسال می‌شود؛ کانال ایمیل از ایمیل ثبت‌شدهٔ هر مدیر استفاده می‌کند. اگر مقصد کانال انتخابی خالی باشد، کد ارسال نمی‌شود.</span>
                                </p>
                                @error('adminTwoFactorChannel') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    <div class="flex items-center justify-end gap-3">
                        <button type="submit" class="ap-btn-primary !py-2.5">
                            <x-admin-panel::icon name="check-circle" class="size-4" />
                            ذخیره تنظیمات امنیتی
                        </button>
                    </div>
                </form>
            </div>

            {{-- ============ تب بازارچه (فاز ۹۹ — ۷ کلید گیت‌دار App\Support\MarketplaceSettings) ============ --}}
            <div x-show="tab === 'marketplace'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <form wire:submit="saveMarketplace" class="space-y-5">

                    {{-- ───── کلید اصلی ───── --}}
                    <section class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="store" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">کلید اصلی بازارچه</h3>
                            <span class="ms-auto text-[10px] font-semibold text-ink-3 dark:text-night-ink-2">منو + صفحات پکیج‌ها و قالب‌ها</span>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-brand-200 bg-gradient-to-l from-brand-50/90 via-brand-50/40 to-transparent p-3.5 dark:border-brand-500/20 dark:from-brand-500/10 dark:via-brand-500/5 dark:to-transparent">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                        <x-admin-panel::icon name="key-round" class="size-4.5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-1.5">
                                            <span class="text-[13px] font-bold">فعال‌بودن بازارچه</span>
                                            <span class="rounded-md bg-brand-600/10 px-1.5 py-0.5 text-[10px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">کلید اصلی</span>
                                        </span>
                                        <span class="block text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">با خاموش‌کردن این کلید، آیتم منوی «پکیج‌ها و قالب‌ها» مخفی و صفحهٔ بازار با پیام غیرفعال رندر می‌شود — مدیریت «ماژول‌های نصب‌شده» سر جای خودش می‌ماند</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="marketEnabled" aria-label="کلید اصلی بازارچه" />
                            </div>
                        </div>
                    </section>

                    {{-- ───── عملیات نصب/حذف ───── --}}
                    <section x-data="{ install: @js((bool) $marketAllowInstall), uninstall: @js((bool) $marketAllowUninstall) }" class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="package" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">نصب و حذف</h3>
                            <span class="ms-auto text-[10px] font-semibold text-ink-3 dark:text-night-ink-2">گیت سروری + مخفی‌شدن دکمه‌ها</span>
                        </div>
                        <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2">
                            <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="download" class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-[13px] font-bold">اجازهٔ نصب و خرید</span>
                                        <span class="block truncate text-[11px] text-ink-3 dark:text-night-ink-2">خاموش = فقط مرور (نصب/خرید/آپدیت مسدود)</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="marketAllowInstall" x-on:change="install = $event.target.checked" size="sm" aria-label="اجازهٔ نصب" />
                            </div>
                            <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-[13px] font-bold">اجازهٔ حذف پکیج/قالب</span>
                                        <span class="block truncate text-[11px] text-ink-3 dark:text-night-ink-2">خاموش = دکمهٔ حذف مخفی + گارد سروری</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="marketAllowUninstall" x-on:change="uninstall = $event.target.checked" size="sm" aria-label="اجازهٔ حذف" />
                            </div>

                            {{-- قالب‌ها — با خاموشی اجازهٔ نصب هم کم‌رنگ نمی‌شود (مستقل است) --}}
                            <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="layout-template" class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-[13px] font-bold">نمایش قالب‌ها</span>
                                        <span class="block truncate text-[11px] text-ink-3 dark:text-night-ink-2">خاموش = تب «قالب‌ها» مخفی (حتی در تب «همه»)</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="marketShowTemplates" size="sm" aria-label="نمایش قالب‌ها" />
                            </div>
                            <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 transition-opacity duration-300 dark:border-night-line dark:bg-night-3" x-bind:class="!install ? 'pointer-events-none opacity-40' : ''">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="refresh-cw" class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-[13px] font-bold">چک خودکار به‌روزرسانی</span>
                                        <span class="block truncate text-[11px] text-ink-3 dark:text-night-ink-2">هنگام ورود به بازار؛ پیش‌فرض خاموش (دکمهٔ دستی می‌ماند)</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="marketAutoCheckUpdates" size="sm" aria-label="چک خودکار به‌روزرسانی" />
                            </div>
                        </div>
                    </section>

                    {{-- ───── نمایش ───── --}}
                    <section class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="sliders-horizontal" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">نمایش</h3>
                        </div>
                        <div class="space-y-3 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line p-3.5 dark:border-night-line">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="list" class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[13px] font-bold">تعداد آیتم هر صفحهٔ بازار</span>
                                        <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">برای پکیج‌ها و قالب‌ها (۱ تا ۶۰)</span>
                                    </span>
                                </span>
                                <input type="number" min="1" max="60" wire:model="marketItemsPerPage" class="ap-input !w-24 !py-2 text-center" aria-label="تعداد آیتم هر صفحه">
                            </div>
                            <div class="rounded-xl border border-line p-3.5 dark:border-night-line">
                                <label class="mb-1.5 block text-[13px] font-bold" for="market-notice-text">متن بنر بالای بازارچه (اختیاری)</label>
                                <p class="mb-2 text-[11px] text-ink-3 dark:text-night-ink-2">خالی = بدون بنر؛ متن اطلاع‌رسانی (مثل «نگارش جدید بازار به‌زودی…») بالای لیست نمایش داده می‌شود.</p>
                                <textarea id="market-notice-text" wire:model="marketNoticeText" rows="2" class="ap-input !py-2" placeholder="مثلاً: تخفیف نوروزی روی همهٔ قالب‌ها فعال است…">{{ $marketNoticeText }}</textarea>
                                @error('marketNoticeText') <p class="mt-1 text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror
                                @error('marketItemsPerPage') <p class="mt-1 text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    {{-- ذخیره --}}
                    <div class="flex items-center justify-end gap-3">
                        <button type="submit" class="ap-btn-primary !py-2.5">
                            <x-admin-panel::icon name="check-circle" class="size-4" />
                            ذخیره تنظیمات بازارچه
                        </button>
                    </div>
                </form>
            </div>

            {{-- ============ تب چندفروشندگی (فاز ۱۰۳ — گیت‌دار App\Support\MultiVendorSettings) ============ --}}
            <div x-show="tab === 'vendors'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <form wire:submit="saveMultiVendor" class="space-y-5">

                    {{-- ───── کلید اصلی ───── --}}
                    <section class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="store" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">کلید اصلی سیستم چندفروشندگی</h3>
                            <span class="ms-auto text-[10px] font-semibold text-ink-3 dark:text-night-ink-2">همان کلید «تنظیمات دیگر» — پنل فروشنده، منوی فروشندگان و محصولات فروشنده‌دار</span>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-brand-200 bg-gradient-to-l from-brand-50/90 via-brand-50/40 to-transparent p-3.5 dark:border-brand-500/20 dark:from-brand-500/10 dark:via-brand-500/5 dark:to-transparent">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                        <x-admin-panel::icon name="key-round" class="size-4.5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-1.5">
                                            <span class="text-[13px] font-bold">فعال‌بودن چندفروشندگی</span>
                                            <span class="rounded-md bg-brand-600/10 px-1.5 py-0.5 text-[10px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">کلید اصلی</span>
                                        </span>
                                        <span class="block text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">این همان سوییچ قدیمی «سیستم چند فروشندگی» در تنظیمات دیگر است (هر دو هم‌زمان تغییر می‌کنند)؛ با خاموشی، پنل فروشنده (۴۰۴)، گروه منوی فروشندگان و گیت‌های این تب بی‌اثر می‌شوند</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="vendorEnabled" aria-label="کلید اصلی چندفروشندگی" />
                            </div>
                        </div>
                    </section>

                    {{-- ───── تسویه ───── --}}
                    <section class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="wallet" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">تسویهٔ فروشندگان</h3>
                            <span class="ms-auto text-[10px] font-semibold text-ink-3 dark:text-night-ink-2">واریز سهم فروشنده به کیف پولش پس از فروش</span>
                        </div>
                        <div class="space-y-3 p-4">
                            <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="badge-check" class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[13px] font-bold">تسویهٔ خودکار پس از پرداخت</span>
                                        <span class="block text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">سهم فروشنده (پس از کمیسیون) بلافاصله بعد از پرداختِ موفقِ سفارش — با هر روش (درگاه، کیف پول، …) — به کیف پولش واریز و در لیست کمیسیون‌ها ثبت شود. خاموش = فقط سفارش‌های کیف‌پولی مثل قبل واریز می‌شوند</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="vendorAutoSettlement" size="sm" aria-label="تسویهٔ خودکار" />
                            </div>
                            <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="layers" class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[13px] font-bold">پرداخت دسته‌جمعی تسویه</span>
                                        <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">در صفحهٔ «درخواستی‌ها» دکمهٔ پرداخت گروهیِ چند درخواستِ در انتظار با یک شماره پیگیری مشترک نمایش شود</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="vendorBulkSettlement" size="sm" aria-label="پرداخت دسته‌جمعی" />
                            </div>
                            <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="bar-chart-3" class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[13px] font-bold">کارت آمار تسویه در داشبورد فروشنده</span>
                                        <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">موجودی، فروش موفق، کمیسیون پرداخت‌شده و درخواست‌های در انتظار، بالای داشبورد پنل فروشنده</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="vendorDashboardStats" size="sm" aria-label="آمار تسویه داشبورد" />
                            </div>
                            <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="file-text" class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[13px] font-bold">صورت‌حساب تسویهٔ فروشنده</span>
                                        <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">صفحهٔ ردیف‌به‌ردیفِ کمیسیون و سهم هر سفارش در پنل فروشنده + خروجی اکسل (منبع: دفتر واریزهای فاز ۱۰۳)</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="vendorSettlementStatement" size="sm" aria-label="صورت‌حساب تسویه" />
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line p-3.5 dark:border-night-line">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="banknote" class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[13px] font-bold">حداقل مبلغ درخواست برداشت فروشنده (تومان)</span>
                                        <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">۰ = بدون حد اضافه (حداقل همیشگی ۱۰٬۰۰۰ تومان فرم برداشت برقرار است)</span>
                                    </span>
                                </span>
                                <input type="number" min="0" max="50000000" wire:model="vendorMinWithdraw" class="ap-input !w-36 !py-2 text-center fa-digits" aria-label="حداقل مبلغ برداشت">
                                @error('vendorMinWithdraw') <p class="w-full text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    {{-- ───── کمیسیون ───── --}}
                    <section class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="percent" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">کمیسیون فروشندگان</h3>
                            <span class="ms-auto text-[10px] font-semibold text-ink-3 dark:text-night-ink-2">اولویت: فروشنده ← دسته‌بندی ← پیش‌فرض سراسری</span>
                        </div>
                        <div class="space-y-3 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line p-3.5 dark:border-night-line">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="globe" class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[13px] font-bold">کمیسیون پیش‌فرض سراسری (درصد)</span>
                                        <span class="block text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">وقتی هیچ دسته‌بندیِ مسیرِ محصول کمیسیون نداشته باشد اعمال می‌شود؛ خالی = بدون پیش‌فرض (۰٪ مثل قبل). کمیسیونِ صریحِ دسته‌بندی همیشه بر آن مقدم است</span>
                                    </span>
                                </span>
                                <input type="number" min="0" max="100" wire:model="vendorDefaultCommission" class="ap-input !w-24 !py-2 text-center fa-digits" aria-label="کمیسیون پیش‌فرض سراسری">
                                @error('vendorDefaultCommission') <p class="w-full text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="user-cog" class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[13px] font-bold">اعتبار کمیسیون اختصاصی فروشنده</span>
                                        <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">درجهٔ هر فروشنده در «پنل کمیسیون فروشندگان» قابل تنظیم است؛ خاموش = فیلدهای اختصاصی نادیده گرفته می‌شوند</span>
                                    </span>
                                </span>
                                <x-admin-panel::toggle wire:model="vendorSellerOverride" size="sm" aria-label="کمیسیون اختصاصی فروشنده" />
                            </div>
                        </div>
                    </section>

                    {{-- ذخیره --}}
                    <div class="flex items-center justify-end gap-3">
                        <button type="submit" class="ap-btn-primary !py-2.5">
                            <x-admin-panel::icon name="check-circle" class="size-4" />
                            ذخیره تنظیمات چندفروشندگی
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
