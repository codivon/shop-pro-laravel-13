{{-- گام ۵ ثبت‌نام — پایان و ورود به پنل (فاز ۱۰۸). فرم عین قدیمی:
     POST seller.registration_checkout_store با فیلد register[back_to_training] + نمایش جدول کمیسیون --}}
<div class="space-y-5" x-data="{
        busy: false,
        showCommissions: false,
        async finish() {
            this.busy = true;
            let fd = new FormData();
            fd.append('register[back_to_training]', '1');
            try {
                let data = await spPost('{{ route('seller.registration_checkout_store') }}', fd);
                if (data.redirect) { window.location.href = data.redirect; }
                else { spToast(data.message || 'تکمیل ثبت‌نام ناموفق بود', 'error'); this.busy = false; }
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); this.busy = false; }
        }
    }">
    @include('sellers.auth.partials.sp-fetch')
    @include('sellers.auth.partials.steps', ['active' => 5])

    <div class="ap-card p-6 text-center sm:p-10">
        <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-500/12 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
            <x-admin-panel::icon name="party-popper" class="size-8" />
        </span>
        <h1 class="mt-4 text-xl font-black text-ink dark:text-night-ink">تبریک!</h1>
        <p class="mt-2 text-[13px] font-semibold leading-7 text-ink-2 dark:text-night-ink-2">
            اکنون شما به جمع فروشندگان {{ option('info_site_title', 'او پی شاپ') }} پیوستید.<br>
            از تجارت آنلاین لذت ببرید!
        </p>
        <p class="mx-auto mt-4 max-w-md rounded-2xl border border-line bg-surface-2/60 p-4 text-start text-[12px] font-semibold leading-6 text-ink-3 dark:border-night-line dark:bg-night-3/60 dark:text-night-ink-2">
            پس از ورود به داشبورد پنل فروشندگان، با ورود به صفحهٔ پروفایل می‌توانید نسخهٔ آنلاین قرارداد
            و اطلاعات تجاری خود را مشاهده کنید. نیازی به چاپ و ارسال قرارداد نیست.
        </p>

        <button type="button" class="ap-btn-primary mt-6 w-full py-3!"
                x-on:click="finish()"
                :disabled="busy" x-bind:class="busy ? 'opacity-60' : ''">
            <span x-show="!busy" class="flex items-center justify-center gap-2">
                <x-admin-panel::icon name="layout-dashboard" class="size-4.5" />
                ورود به پنل فروشندگان
            </span>
            <span x-show="busy" x-cloak class="flex items-center justify-center gap-2">
                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                در حال آماده‌سازی پنل…
            </span>
        </button>

        <button type="button" class="mt-3 text-xs font-bold text-brand-600 transition-colors hover:underline dark:text-brand-400"
                x-on:click="showCommissions = !showCommissions">
            مشاهده جدول کمیسیون
        </button>

        <div x-show="showCommissions" x-cloak x-transition class="mt-4 overflow-hidden rounded-2xl border border-line text-start dark:border-night-line">
            <table class="w-full text-[12px]">
                <thead class="bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                    <tr>
                        <th class="p-3 text-start font-bold">دسته‌بندی</th>
                        <th class="p-3 text-start font-bold">کمیسیون (٪)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr class="border-t border-line dark:border-night-line">
                            <td class="p-3 font-bold text-ink dark:text-night-ink">{{ $category->name }}</td>
                            <td class="fa-digits p-3 font-extrabold text-brand-600 dark:text-brand-400">{{ $category->commission }}</td>
                        </tr>
                    @empty
                        <tr class="border-t border-line dark:border-night-line">
                            <td colspan="2" class="p-4 text-center font-bold text-ink-3 dark:text-night-ink-2">جدول کمیسیونی ثبت نشده است</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
