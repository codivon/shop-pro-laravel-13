{{-- گام ۳ ثبت‌نام — اطلاعات کسب‌وکار (فاز ۱۰۸). فیلدها عین قدیمی
     (first_name/last_name/birth_*/gender/identity_*/company_*/state_id/city_id/address/
     post_code/lat_and_long/phone/business_name/shaba_number/main_supply_category_id/
     number_of_products/econtract). ارسال: fetch به seller.registration_business_details_store --}}
<div class="space-y-5" x-data="{
        busy: false,
        personType: 'private',
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
        }
    }">
    @include('sellers.auth.partials.sp-fetch')
    @include('sellers.auth.partials.steps', ['active' => 3])

    <div class="ap-card p-6 sm:p-8">
        <h1 class="text-lg font-extrabold text-ink dark:text-night-ink">اطلاعات کسب‌وکار</h1>
        <p class="mt-1 text-xs font-semibold text-ink-3 dark:text-night-ink-2">اطلاعات هویتی و کسب‌وکار خود را دقیق وارد کنید</p>

        <form class="mt-6 space-y-6" x-on:submit.prevent="
            busy = true;
            var fd = new FormData($refs.form);
            try {
                var data = await spPost('{{ route('seller.registration_business_details_store') }}', fd);
                if (data.redirect) { window.location.href = data.redirect; }
                else { spToast(data.message || 'ثبت اطلاعات ناموفق بود', 'error'); busy = false; }
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); busy = false; }
        " x-ref="form">
            <input type="hidden" name="lat_and_long" value="">
            <input type="hidden" name="location" value="">

            {{-- نوع شخص --}}
            <div class="grid grid-cols-2 gap-3">
                <label class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-[13px] font-bold transition-all
                              border-line dark:border-night-line"
                       x-bind:class="personType === 'private' ? 'border-brand-500 bg-brand-50/60 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'text-ink-2 dark:text-night-ink-2'">
                    <input type="radio" name="private_business" value="private" x-model="personType" class="sr-only">
                    <x-admin-panel::icon name="user" class="size-4" />
                    شخص حقیقی
                </label>
                <label class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-[13px] font-bold transition-all
                              border-line dark:border-night-line"
                       x-bind:class="personType === 'business' ? 'border-brand-500 bg-brand-50/60 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'text-ink-2 dark:text-night-ink-2'">
                    <input type="radio" name="private_business" value="business" x-model="personType" class="sr-only">
                    <x-admin-panel::icon name="building-2" class="size-4" />
                    شخص حقوقی
                </label>
            </div>

            {{-- اطلاعات هویتی --}}
            <section class="space-y-4">
                <h2 class="flex items-center gap-2 text-[13px] font-extrabold text-ink dark:text-night-ink">
                    <x-admin-panel::icon name="id-card" class="size-4 text-brand-600 dark:text-brand-400" />
                    اطلاعات هویتی
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-first">نام</label>
                        <input id="bd-first" type="text" name="first_name" required class="ap-input !py-3" placeholder="نام خود را وارد کنید">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-last">نام خانوادگی</label>
                        <input id="bd-last" type="text" name="last_name" required class="ap-input !py-3" placeholder="نام خانوادگی خود را وارد کنید">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-by">سال تولد</label>
                        <select id="bd-by" name="birth_year" class="ap-input !py-3">
                            @for ($y = 1380; $y >= 1320; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-bm">ماه</label>
                        <select id="bd-bm" name="birth_month" class="ap-input !py-3">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-bd">روز</label>
                        <select id="bd-bd" name="birth_day" class="ap-input !py-3">
                            @for ($d = 1; $d <= 31; $d++)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border border-line px-3 py-2.5 text-[13px] font-bold text-ink-2 dark:border-night-line dark:text-night-ink-2">
                        <input type="radio" name="gender" value="male" checked class="accent-brand-600">
                        آقا
                    </label>
                    <label class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border border-line px-3 py-2.5 text-[13px] font-bold text-ink-2 dark:border-night-line dark:text-night-ink-2">
                        <input type="radio" name="gender" value="female" class="accent-brand-600">
                        خانم
                    </label>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-idcard">شمارهٔ کارت ملی</label>
                        <input id="bd-idcard" type="number" name="identity_card_number" class="ap-input !py-3 fa-digits" placeholder="۱۲۳۴۵">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-nin">کد ملی</label>
                        <input id="bd-nin" type="number" name="national_identity_number" class="ap-input !py-3 fa-digits" placeholder="۱۲۳۴۵۶۷۸۹۰">
                    </div>
                </div>
            </section>

            {{-- اطلاعات شرکت (فقط حقوقی) --}}
            <section x-show="personType === 'business'" x-cloak class="space-y-4 rounded-2xl border border-line p-4 dark:border-night-line">
                <h2 class="flex items-center gap-2 text-[13px] font-extrabold text-ink dark:text-night-ink">
                    <x-admin-panel::icon name="building-2" class="size-4 text-brand-600 dark:text-brand-400" />
                    اطلاعات شرکت
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-company">نام شرکت</label>
                        <input id="bd-company" type="text" name="company_name" class="ap-input !py-3" placeholder="نام شرکت را وارد کنید">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-ctype">نوع شرکت</label>
                        <select id="bd-ctype" name="company_type" class="ap-input !py-3">
                            <option value="سهامی خاص">سهامی خاص</option>
                            <option value="سهامی عام">سهامی عام</option>
                            <option value="مسئولیت محدود">مسئولیت محدود</option>
                            <option value="تعاونی">تعاونی</option>
                            <option value="موسسه فردی">موسسه فردی</option>
                            <option value="سایر">سایر</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-crn">شمارهٔ ثبت</label>
                        <input id="bd-crn" type="number" name="company_registration_number" class="ap-input !py-3 fa-digits">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-cnin">شناسهٔ ملی شرکت</label>
                        <input id="bd-cnin" type="number" name="company_national_identity_number" class="ap-input !py-3 fa-digits">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-bold" for="bd-cen">شمارهٔ اقتصادی</label>
                        <input id="bd-cen" type="number" name="company_economic_number" class="ap-input !py-3 fa-digits">
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
                        <label class="mb-1.5 block text-xs font-bold" for="bd-state">استان</label>
                        <select id="bd-state" name="state_id" x-ref="province" required
                                x-on:change="loadCities()" class="ap-input !py-3">
                            <option value="">انتخاب استان…</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-city">شهر</label>
                        <select id="bd-city" name="city_id" class="ap-input !py-3" required>
                            <option value="">ابتدا استان را انتخاب کنید</option>
                            <template x-for="city in cities" :key="city.id">
                                <option x-text="city.name" x-bind:value="city.id"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="bd-address">آدرس کامل</label>
                    <input id="bd-address" type="text" name="address" required class="ap-input !py-3"
                           placeholder="محله - خیابان اصلی - کوچه - پلاک - واحد">
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-post">کد پستی</label>
                        <input id="bd-post" type="number" name="post_code" class="ap-input !py-3 fa-digits" placeholder="۱۲۳۴۵-۶۷۸۹۰">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-phone">تلفن ثابت</label>
                        <input id="bd-phone" type="number" name="phone" class="ap-input !py-3 fa-digits" placeholder="021-56000000">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="bd-mob">شمارهٔ موبایل (تأیید شده)</label>
                    <input id="bd-mob" type="tel" dir="ltr" disabled value="{{ $seller['mobile'] }}"
                           class="ap-input !py-3 cursor-not-allowed bg-surface-2 opacity-70 fa-digits text-start">
                </div>
            </section>

            {{-- فروشگاه --}}
            <section class="space-y-4">
                <h2 class="flex items-center gap-2 text-[13px] font-extrabold text-ink dark:text-night-ink">
                    <x-admin-panel::icon name="store" class="size-4 text-brand-600 dark:text-brand-400" />
                    اطلاعات فروشگاه
                </h2>
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="bd-bname">نام فروشگاه</label>
                    <input id="bd-bname" type="text" name="business_name" required class="ap-input !py-3"
                           placeholder="نام فروشگاه شما در سایت {{ option('info_site_title', 'او پی شاپ') }}">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="bd-shaba">شمارهٔ شبا (برای تسویه)</label>
                    <input id="bd-shaba" type="text" dir="ltr" name="shaba_number" required class="ap-input !py-3 font-mono text-start" placeholder="IR______________________________">
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-cat">دستهٔ اصلی تأمین کالا</label>
                        <select id="bd-cat" name="main_supply_category_id" class="ap-input !py-3" required>
                            <option value="">انتخاب کنید…</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-count">تعداد محصولات قابل ارائه</label>
                        <select id="bd-count" name="number_of_products" class="ap-input !py-3">
                            <option value="1-10">۱ تا ۱۰</option>
                            <option value="10-50">۱۰ تا ۵۰</option>
                            <option value="50-100">۵۰ تا ۱۰۰</option>
                            <option value="100+">بیش از ۱۰۰</option>
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

            <button type="submit" class="ap-btn-primary w-full !py-3" :disabled="busy" x-bind:class="busy ? 'opacity-60' : ''">
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
