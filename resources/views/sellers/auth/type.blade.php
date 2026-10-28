{{-- گام ۳ ثبت‌نام — فاز ۱۱۰: انتخاب وضعیت فروشنده (حقیقی/حقوقی) — کارت‌های
     بزرگ مثل لندینگ مرجع؛ انتخاب حقوقی فیلدهای شرکت را به گام بعد اضافه می‌کند --}}
<div class="space-y-5" x-data="{
        busy: false,
        errors: {},
        async pick(type) {
            this.busy = true;
            this.errors = {};
            let fd = new FormData();
            fd.append('type', type);
            try {
                let data = await spPost('{{ route('seller.registration_type_store') }}', fd);
                if (data.redirect) { window.location.href = data.redirect; return; }
                this.errors = data.errors || {};
                spToast(data.message || 'ثبت وضعیت ناموفق بود', 'error');
                this.busy = false;
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); this.busy = false; }
        }
    }">
    @include('sellers.auth.partials.sp-fetch')
    {{-- ⑨-۲ — گام قبل از «وضعیت»، گام رمز عبور یک‌بارمصرف است؛ دکمهٔ بازگشت به آن ممنوع --}}
    @include('sellers.auth.partials.steps', ['active' => 4])

    <div class="ap-card p-6 sm:p-8">
        <h1 class="text-center text-lg font-extrabold text-ink dark:text-night-ink">وضعیت خود را انتخاب کنید</h1>
        <p class="mt-1 text-center text-xs font-semibold text-ink-3 dark:text-night-ink-2">فروشندهٔ حقیقی یا حقوقی؟ اطلاعات لازم برای هر کدام متفاوت است</p>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            {{-- حقیقی --}}
            <div class="flex flex-col rounded-2xl border border-line p-5 transition-colors hover:border-brand-300 dark:border-night-line dark:hover:border-brand-500/40">
                <span class="mx-auto flex size-24 items-center justify-center rounded-3xl bg-brand-500/10 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    <x-admin-panel::icon name="user" class="size-12" />
                </span>
                <h2 class="mt-4 text-center text-[15px] font-black text-ink dark:text-night-ink">فروشندهٔ حقیقی</h2>
                <p class="mt-2 flex-1 text-center text-[12px] font-semibold leading-7 text-ink-3 dark:text-night-ink-2">
                    مناسب کسب‌وکارهای خانگی و فروشندگان مستقل؛ فقط اطلاعات هویتی شخص مانند
                    نام، نام خانوادگی، تاریخ تولد و کد ملی ثبت می‌شود.
                </p>
                <button type="button" x-on:click="pick('real')" :disabled="busy"
                        x-bind:class="busy ? 'opacity-60' : ''"
                        class="ap-btn-primary mt-5 w-full py-3!">
                    فروشندهٔ حقیقی هستم
                    <x-admin-panel::icon name="chevron-left" class="size-4" />
                </button>
            </div>

            {{-- حقوقی --}}
            <div class="flex flex-col rounded-2xl border border-line p-5 transition-colors hover:border-brand-300 dark:border-night-line dark:hover:border-brand-500/40">
                <span class="mx-auto flex size-24 items-center justify-center rounded-3xl bg-brand-500/10 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    <x-admin-panel::icon name="building-2" class="size-12" />
                </span>
                <h2 class="mt-4 text-center text-[15px] font-black text-ink dark:text-night-ink">فروشندهٔ حقوقی</h2>
                <p class="mt-2 flex-1 text-center text-[12px] font-semibold leading-7 text-ink-3 dark:text-night-ink-2">
                    مناسب شرکت‌ها و مؤسسات؛ علاوه بر اطلاعات نماینده، اطلاعات شرکت مانند
                    نام شرکت، شمارهٔ ثبت، شناسهٔ ملی و شمارهٔ اقتصادی ثبت می‌شود.
                </p>
                <button type="button" x-on:click="pick('legal')" :disabled="busy"
                        x-bind:class="busy ? 'opacity-60' : ''"
                        class="ap-btn-primary mt-5 w-full py-3!">
                    فروشندهٔ حقوقی هستم
                    <x-admin-panel::icon name="chevron-left" class="size-4" />
                </button>
            </div>
        </div>

        <p class="mt-5 text-center text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
            وضعیت را اشتباه انتخاب کردید؟ در گام‌های بعد می‌توانید برگردید و تغییر دهید.
        </p>
    </div>
</div>
