{{-- فاز ۱۱۱ — مودال «فروشنده با این شماره از قبل ثبت‌نام کرده است»
     وقتی کاربر شمارهٔ تکمیل‌شدهٔ یک فروشندهٔ فعال را وارد کند (پاسخ status=exists از
     seller.registration_phone_send)، رویداد sp-registration-exists این مودال را باز می‌کند.
     دو دکمه: رفتن به صفحهٔ ورود / تغییر شماره (بستن مودال و ادامهٔ ویرایش فرم) --}}
<div x-data="{ open: false, loginUrl: '{{ route('seller.login') }}' }"
     x-on:sp-registration-exists.document="
        if ($event.detail && $event.detail.login_url) { loginUrl = $event.detail.login_url; }
        open = true;
     "
     x-cloak x-show="open"
     class="fixed inset-0 z-[95] flex items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-label="فروشنده با این شماره از قبل ثبت‌نام کرده است"
     x-on:keydown.escape.window="open = false">

    {{-- پس‌زمینه --}}
    <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open = false" aria-hidden="true"></div>

    <div class="relative w-full max-w-sm rounded-3xl border border-line bg-surface p-6 text-center shadow-2xl dark:border-night-line dark:bg-night-2"
         x-show="open"
         x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-500 dark:bg-amber-500/12 dark:text-amber-400">
            <x-admin-panel::icon name="circle-alert" class="size-7" />
        </span>
        <h2 class="mt-3 text-base font-extrabold text-ink dark:text-night-ink">فروشنده با این شماره از قبل ثبت‌نام کرده است</h2>
        <p class="mt-1.5 text-xs font-semibold leading-6 text-ink-3 dark:text-night-ink-2">
            برای ادامه می‌توانید به صفحهٔ ورود بروید یا شمارهٔ دیگری را برای ثبت‌نام وارد کنید
        </p>

        <div class="mt-5 grid gap-2.5">
            <a :href="loginUrl"
               class="ap-btn-primary w-full py-3!">
                <x-admin-panel::icon name="log-in" class="size-4" />
                رفتن به صفحهٔ ورود
            </a>
            <button type="button" x-on:click="open = false"
                    class="ap-btn-outline w-full py-3!">
                <x-admin-panel::icon name="pencil" class="size-4" />
                تغییر شمارهٔ موبایل
            </button>
        </div>
    </div>
</div>
