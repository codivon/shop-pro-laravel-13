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
                    <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                        <span class="text-[13px] font-bold">فعال‌سازی اجرای صف با دو Cron Job (هاست اشتراکی)</span>
                        <input type="checkbox" wire:model="sharedHostingCronEnabled" class="size-5 cursor-pointer rounded border-line text-brand-600">
                    </label>

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

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                            <span>
                                <span class="block text-[13px] font-bold">حالت توسعه</span>
                                <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">APP_DEBUG — در حالت توسعه فایل‌ها کامپایل نشده‌اند</span>
                            </span>
                            <input type="checkbox" wire:model="appDebugMode" class="size-5 cursor-pointer rounded border-line text-brand-600">
                        </label>

                        <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                            <span>
                                <span class="block text-[13px] font-bold">دیباگ بار</span>
                                <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">DEBUGBAR_ENABLED</span>
                            </span>
                            <input type="checkbox" wire:model="debugbarEnabled" class="size-5 cursor-pointer rounded border-line text-brand-600">
                        </label>

                        <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                            <span>
                                <span class="block text-[13px] font-bold">متد قدیمی آپدیت</span>
                                <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">این متد اکسپایر شده</span>
                            </span>
                            <input type="checkbox" wire:model="enableOldUpdates" class="size-5 cursor-pointer rounded border-line text-brand-600">
                        </label>
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

            {{-- ============ تب امنیت (فاز ۴۴) ============ --}}
            <div x-show="tab === 'security'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <form wire:submit="saveSecurity" class="space-y-6">

                    {{-- ───── کپچا (ربات‌گیر) ───── --}}
                    <section class="rounded-xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="bot" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">ربات‌گیر (کپچا)</h3>
                        </div>
                        <div class="space-y-3 p-4">
                            <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-brand-200 bg-brand-50/60 p-3.5 dark:border-brand-500/20 dark:bg-brand-500/10">
                                <span>
                                    <span class="block text-[13px] font-bold">کلید اصلی کپچا</span>
                                    <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">با خاموش‌کردن این کلید، کپچا در همه‌ی نقاط غیرفعال می‌شود</span>
                                </span>
                                <input type="checkbox" wire:model="securityCaptchaEnabled" class="size-5 cursor-pointer rounded border-line text-brand-600">
                            </label>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                    <span class="text-[13px] font-bold">ورود مدیر (هر دو پنل)</span>
                                    <input type="checkbox" wire:model="securityCaptchaAdminLogin" class="size-5 cursor-pointer rounded border-line text-brand-600">
                                </label>
                                <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                    <span class="text-[13px] font-bold">ثبت‌نام مشتری</span>
                                    <input type="checkbox" wire:model="securityCaptchaRegister" class="size-5 cursor-pointer rounded border-line text-brand-600">
                                </label>
                                <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                    <span>
                                        <span class="block text-[13px] font-bold">ورود مشتری</span>
                                        <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">قدم اول ورود فروشگاه (موبایل/ایمیل)</span>
                                    </span>
                                    <input type="checkbox" wire:model="securityCaptchaCustomerLogin" class="size-5 cursor-pointer rounded border-line text-brand-600">
                                </label>
                                <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                    <span>
                                        <span class="block text-[13px] font-bold">دیدگاه‌ها و بررسی‌ها</span>
                                        <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">سوال محصول، دیدگاه وبلاگ و امتیازدهی</span>
                                    </span>
                                    <input type="checkbox" wire:model="securityCaptchaComments" class="size-5 cursor-pointer rounded border-line text-brand-600">
                                </label>
                            </div>

                            <div class="rounded-xl border border-line p-4 dark:border-night-line">
                                <label class="mb-1.5 block text-xs font-semibold">تلهٔ زمان — حداقل ثانیه‌ی پرکردن فرم</label>
                                <p class="mb-2.5 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">فرم‌های ارسال‌شده سریع‌تر از این مقدار رد می‌شوند (رفتار ربات). صفر = غیرفعال.</p>
                                <div class="flex items-center gap-2">
                                    <input type="number" min="0" max="300" wire:model="securityTimeTrapSeconds" class="ap-input w-28 text-center font-mono">
                                    <span class="text-xs text-ink-3 dark:text-night-ink-2">ثانیه</span>
                                </div>
                                @error('securityTimeTrapSeconds') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    {{-- ───── هانی‌پات ───── --}}
                    <section class="rounded-xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="eye-off" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">هانی‌پات</h3>
                        </div>
                        <div class="p-4">
                            <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span>
                                    <span class="block text-[13px] font-bold">فعال‌بودن هانی‌پات</span>
                                    <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">فیلد مخفی‌ای که فقط ربات‌ها پر می‌کنند؛ پرشدنش به مسدودسازی IP منجر می‌شود</span>
                                </span>
                                <input type="checkbox" wire:model="securityHoneypotEnabled" class="size-5 cursor-pointer rounded border-line text-brand-600">
                            </label>
                        </div>
                    </section>

                    {{-- ───── قفل تلاش‌ها ───── --}}
                    <section class="rounded-xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="lock" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">قفل تلاش‌ها (ضد حملهٔ brute-force)</h3>
                        </div>
                        <div class="space-y-3 p-4">
                            <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-brand-200 bg-brand-50/60 p-3.5 dark:border-brand-500/20 dark:bg-brand-500/10">
                                <span>
                                    <span class="block text-[13px] font-bold">کلید اصلی قفل تلاش‌ها</span>
                                    <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">با خاموش‌کردن این کلید، محدودیت تلاش در همه‌ی نقاط برداشته می‌شود (توصیه نمی‌شود)</span>
                                </span>
                                <input type="checkbox" wire:model="securityLockoutEnabled" class="size-5 cursor-pointer rounded border-line text-brand-600">
                            </label>

                            <div class="overflow-hidden rounded-xl border border-line dark:border-night-line">
                                <table class="w-full text-[13px]">
                                    <thead>
                                        <tr class="bg-surface-2 text-start dark:bg-night-3/50">
                                            <th class="p-3 text-start font-bold">نقطه</th>
                                            <th class="p-3 text-start font-bold">سقف تلاش</th>
                                            <th class="p-3 text-start font-bold">پنجره (دقیقه)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-line dark:divide-night-line">
                                        <tr>
                                            <td class="p-3 font-bold">ورود مدیر</td>
                                            <td class="p-3">
                                                <input type="number" min="1" max="100" wire:model="securityLockoutAdminMax" class="ap-input w-24 text-center font-mono">
                                                @error('securityLockoutAdminMax') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                                            </td>
                                            <td class="p-3">
                                                <input type="number" min="1" max="1440" wire:model="securityLockoutAdminMinutes" class="ap-input w-24 text-center font-mono">
                                                @error('securityLockoutAdminMinutes') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="p-3 font-bold">ورود مشتری</td>
                                            <td class="p-3">
                                                <input type="number" min="1" max="100" wire:model="securityLockoutCustomerMax" class="ap-input w-24 text-center font-mono">
                                                @error('securityLockoutCustomerMax') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                                            </td>
                                            <td class="p-3">
                                                <input type="number" min="1" max="1440" wire:model="securityLockoutCustomerMinutes" class="ap-input w-24 text-center font-mono">
                                                @error('securityLockoutCustomerMinutes') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="p-3 font-bold">دیدگاه‌ها و بررسی‌ها</td>
                                            <td class="p-3">
                                                <input type="number" min="1" max="100" wire:model="securityLockoutCommentsMax" class="ap-input w-24 text-center font-mono">
                                                @error('securityLockoutCommentsMax') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                                            </td>
                                            <td class="p-3">
                                                <input type="number" min="1" max="1440" wire:model="securityLockoutCommentsMinutes" class="ap-input w-24 text-center font-mono">
                                                @error('securityLockoutCommentsMinutes') <p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p> @enderror
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    {{-- ───── هدرهای امنیتی ───── --}}
                    <section class="rounded-xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="shield" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">هدرهای امنیتی</h3>
                        </div>
                        <div class="space-y-3 p-4">
                            <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span>
                                    <span class="block text-[13px] font-bold">هدرهای استاندارد امنیتی</span>
                                    <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">X-Frame-Options، X-Content-Type-Options، Referrer-Policy و Permissions-Policy</span>
                                </span>
                                <input type="checkbox" wire:model="securityHeadersEnabled" class="size-5 cursor-pointer rounded border-line text-brand-600">
                            </label>
                            <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                                <span>
                                    <span class="block text-[13px] font-bold">HSTS (Strict-Transport-Security)</span>
                                    <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">مرورگر را مجبور می‌کند همیشه HTTPS — فقط روی اتصال امن ارسال می‌شود</span>
                                </span>
                                <input type="checkbox" wire:model="securityHstsEnabled" class="size-5 cursor-pointer rounded border-line text-brand-600">
                            </label>
                        </div>
                    </section>

                    {{-- ───── ورود دومرحله‌ای مدیر (2FA) ───── --}}
                    <section class="rounded-xl border border-line dark:border-night-line">
                        <div class="flex items-center gap-2 border-b border-line bg-surface-2 px-4 py-3 dark:border-night-line dark:bg-night-3/50">
                            <x-admin-panel::icon name="user-cog" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            <h3 class="text-[13px] font-bold">ورود دومرحله‌ای مدیر (2FA)</h3>
                            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">جدید</span>
                        </div>
                        <div class="space-y-3 p-4">
                            <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-brand-200 bg-brand-50/60 p-3.5 dark:border-brand-500/20 dark:bg-brand-500/10">
                                <span>
                                    <span class="block text-[13px] font-bold">فعال‌سازی ورود دومرحله‌ای</span>
                                    <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">پس از ورودِ رمز، کد ۶ رقمی یک‌بارمصرف درخواست می‌شود (قفل ۵ تلاش در ۵ دقیقه، ارسال مجدد هر ۱ دقیقه) — برای هر دو پنل</span>
                                </span>
                                <input type="checkbox" wire:model="adminTwoFactorEnabled" class="size-5 cursor-pointer rounded border-line text-brand-600">
                            </label>

                            <div class="rounded-xl border border-line p-4 dark:border-night-line">
                                <p class="mb-2.5 text-xs font-semibold">کانال ارسال کد</p>
                                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border p-3.5 transition-colors dark:border-night-line {{ $adminTwoFactorChannel === 'email' ? 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10' : 'border-line bg-surface-2 dark:bg-night-3' }}">
                                        <input type="radio" value="email" wire:model="adminTwoFactorChannel" class="size-4 cursor-pointer text-brand-600">
                                        <span class="text-[13px] font-bold">ایمیل</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border p-3.5 transition-colors dark:border-night-line {{ $adminTwoFactorChannel === 'sms' ? 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10' : 'border-line bg-surface-2 dark:bg-night-3' }}">
                                        <input type="radio" value="sms" wire:model="adminTwoFactorChannel" class="size-4 cursor-pointer text-brand-600">
                                        <span class="text-[13px] font-bold">پیامک</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border p-3.5 transition-colors dark:border-night-line {{ $adminTwoFactorChannel === 'both' ? 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10' : 'border-line bg-surface-2 dark:bg-night-3' }}">
                                        <input type="radio" value="both" wire:model="adminTwoFactorChannel" class="size-4 cursor-pointer text-brand-600">
                                        <span class="text-[13px] font-bold">هر دو</span>
                                    </label>
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
        </div>
    </div>
</div>
