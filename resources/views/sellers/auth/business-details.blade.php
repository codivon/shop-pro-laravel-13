{{-- گام ۵ ثبت‌نام — فاز ۱۱۰: اطلاعات کسب‌وکار و ورود.
     اطلاعات هویتی از گام «اطلاعات تکمیلی» (پیش‌نویس) می‌آید؛ اینجا ایمیل/رمز
     (ورود به پنل) + آدرس + فروشگاه + قرارداد جمع و حساب Seller ساخته می‌شود.
     ارسال: fetch به seller.registration_business_store (کنترلر جدید فاز ۱۱۰) --}}
@php
    $isLegal = (($draftType ?? 'real') === 'legal');
@endphp
<div class="space-y-5" x-data="{
        busy: false,
        cities: [],
        async loadCities() {
            var pid = this.$refs.province.value;
            if (! pid) { this.cities = []; return; }
            try {
                var r = await fetch('/api/v1/provinces/' + pid + '/cities', { headers: { 'Accept': 'application/json' } });
                if (r.ok) {
                    var j = await r.json();
                    this.cities = (j.data && j.data.data) ? j.data.data : (j.data || []);
                    return;
                }
            } catch (e) {}
            this.cities = [];
        },
        async submit() {
            this.busy = true;
            let fd = new FormData(this.$refs.form);
            try {
                let data = await spPost('{{ route('seller.registration_business_store') }}', fd);
                if (data.redirect) { window.location.href = data.redirect; }
                else { spToast(data.message || 'ثبت اطلاعات ناموفق بود', 'error'); this.busy = false; }
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); this.busy = false; }
        }
    }">
    @include('sellers.auth.partials.sp-fetch')
    @include('sellers.auth.partials.steps', ['active' => 5])

    <div class="ap-card p-6 sm:p-8">
        <h1 class="text-lg font-extrabold text-ink dark:text-night-ink">اطلاعات کسب‌وکار</h1>
        <p class="mt-1 text-xs font-semibold text-ink-3 dark:text-night-ink-2">اطلاعات ورود، آدرس و فروشگاه خود را تکمیل کنید</p>

        <form class="mt-6 space-y-6" x-on:submit.prevent="submit()" x-ref="form">
            <input type="hidden" name="lat_and_long" value="">

            {{-- اطلاعات ورود به پنل --}}
            <section class="space-y-4">
                <h2 class="flex items-center gap-2 text-[13px] font-extrabold text-ink dark:text-night-ink">
                    <x-admin-panel::icon name="lock" class="size-4 text-brand-600 dark:text-brand-400" />
                    اطلاعات ورود به پنل
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-email">ایمیل <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <x-admin-panel::icon name="mail" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                            <input id="bd-email" type="email" name="email" dir="ltr" required value="{{ $draftEmail ?? '' }}"
                                   class="ap-input py-3! ps-10! text-start" placeholder="you@example.com" autocomplete="email">
                        </div>
                        <p class="mt-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">این ایمیل برای ورود به پنل و ارتباط با شما استفاده می‌شود</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-pass">رمز عبور <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <x-admin-panel::icon name="lock" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                            <input id="bd-pass" type="password" name="password" required
                                   class="ap-input py-3! pe-10!" placeholder="حداقل ۸ کاراکتر" autocomplete="new-password">
                        </div>
                        <p class="mt-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">با این رمز به پنل فروشندگان وارد می‌شوید</p>
                    </div>
                </div>
            </section>

            {{-- آدرس --}}
            <section class="space-y-4">
                <h2 class="flex items-center gap-2 text-[13px] font-extrabold text-ink dark:text-night-ink">
                    <x-admin-panel::icon name="map-pin" class="size-4 text-brand-600 dark:text-brand-400" />
                    آدرس و تماس
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-state">استان <span class="text-red-500">*</span></label>
                        <select id="bd-state" name="state_id" x-ref="province" required
                                x-on:change="loadCities()" class="ap-input py-3!">
                            <option value="">انتخاب استان…</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-city">شهر <span class="text-red-500">*</span></label>
                        <select id="bd-city" name="city_id" class="ap-input py-3!" required>
                            <option value="">ابتدا استان را انتخاب کنید</option>
                            <template x-for="city in cities" :key="city.id">
                                <option x-text="city.name" x-bind:value="city.id"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="bd-address">آدرس کامل <span class="text-red-500">*</span></label>
                    <input id="bd-address" type="text" name="address" required class="ap-input py-3!"
                           placeholder="محله - خیابان اصلی - کوچه - پلاک - واحد">
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-post">کد پستی <span class="text-red-500">*</span></label>
                        <input id="bd-post" type="text" name="post_code" inputmode="numeric" maxlength="10" required class="ap-input py-3! fa-digits" placeholder="۱۲۳۴۵-۶۷۸۹۰">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-phone">تلفن ثابت <span class="text-red-500">*</span></label>
                        <input id="bd-phone" type="text" name="phone" inputmode="numeric" required class="ap-input py-3! fa-digits" placeholder="021-56000000">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="bd-mob">شمارهٔ موبایل (تأیید شده)</label>
                    <input id="bd-mob" type="tel" dir="ltr" disabled value="{{ $seller['mobile'] }}"
                           class="ap-input py-3! cursor-not-allowed bg-surface-2 opacity-70 fa-digits text-start">
                </div>
            </section>

            {{-- فروشگاه --}}
            <section class="space-y-4">
                <h2 class="flex items-center gap-2 text-[13px] font-extrabold text-ink dark:text-night-ink">
                    <x-admin-panel::icon name="store" class="size-4 text-brand-600 dark:text-brand-400" />
                    اطلاعات فروشگاه
                </h2>
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="bd-bname">نام فروشگاه <span class="text-red-500">*</span></label>
                    <input id="bd-bname" type="text" name="business_name" required class="ap-input py-3!"
                           placeholder="نام فروشگاه شما در سایت {{ option('info_site_title', 'او پی شاپ') }}">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="bd-shaba">شمارهٔ شبا (برای تسویه) <span class="text-red-500">*</span></label>
                    <input id="bd-shaba" type="text" dir="ltr" name="shaba_number" required class="ap-input py-3! font-mono text-start" placeholder="IR______________________________">
                    <p class="mt-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">شبا باید متعلق به همان شخص/شرکت باشد تا تسویه بدون مشکل انجام شود</p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-cat">دستهٔ اصلی تأمین کالا <span class="text-red-500">*</span></label>
                        <select id="bd-cat" name="main_supply_category_id" class="ap-input py-3!" required>
                            <option value="">انتخاب کنید…</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-count">تعداد محصولات قابل ارائه <span class="text-red-500">*</span></label>
                        {{-- مقادیر عین قدیمی (ستون sellers_info.number_of_products عددی است) --}}
                        <select id="bd-count" name="number_of_products" class="ap-input py-3!" required>
                            <option value="10">۱ تا ۱۰</option>
                            <option value="50">۱۱ تا ۵۰</option>
                            <option value="100">۵۱ تا ۱۰۰</option>
                            <option value="300">۱۰۱ تا ۳۰۰</option>
                            <option value="1000">۳۰۱ تا ۱۰۰۰</option>
                            <option value="3000">بیش از ۱۰۰۰</option>
                        </select>
                    </div>
                </div>

                {{-- قرارداد الکترونیکی --}}
                @if ($econtract)
                    <div class="rounded-2xl border border-line p-4 dark:border-night-line">
                        <label class="flex min-h-11 cursor-pointer items-start gap-3 text-[12px] font-semibold leading-6 text-ink-2 dark:text-night-ink-2">
                            <input type="checkbox" value="1" name="econtract" required class="mt-1 size-4 rounded border-line accent-brand-600">
                            <span>
                                شرایط و قوانین <b class="text-ink dark:text-night-ink">قرارداد الکترونیکی</b> فروشندگان را می‌خوانم و می‌پذیرم.
                                @if (! empty($econtract->description))
                                    <a href="{{ route('front.index') }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline dark:text-brand-400">متن قرارداد</a>
                                @endif
                            </span>
                        </label>
                    </div>
                @endif
            </section>

            <button type="submit" class="ap-btn-primary w-full py-3.5!" :disabled="busy" x-bind:class="busy ? 'opacity-60' : ''">
                <span x-show="!busy" class="flex items-center justify-center gap-2">
                    ثبت و ادامه
                    <x-admin-panel::icon name="chevron-left" class="size-4" />
                </span>
                <span x-show="busy" x-cloak class="flex items-center justify-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                    در حال ثبت اطلاعات…
                </span>
            </button>
        </form>
    </div>
</div>
