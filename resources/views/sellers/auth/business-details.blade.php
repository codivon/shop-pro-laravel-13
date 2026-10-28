{{-- گام ۶ ثبت‌نام — فاز ۱۱۰: اطلاعات کسب‌وکار و ورود.
     اطلاعات هویتی از گام «اطلاعات تکمیلی» (پیش‌نویس) می‌آید؛ اینجا ایمیل
     (ورود به پنل) + آدرس + فروشگاه + قرارداد جمع و حساب Seller ساخته می‌شود.
     فاز ۱۱۳: فیلد رمز عبور از این گام حذف شد (رمز فقط در گام «تنظیم رمز عبور»
     بعد از کد تأیید گرفته می‌شود)؛ دستهٔ اصلی تأمین کالا با عنوان + جستجوی
     زنده (کامبوباکس)؛ ولیدیشن کلاینت فارسی؛ دکمهٔ بازگشت به مرحلهٔ قبل.
     ارسال: fetch به seller.registration_business_store (کنترلر فاز ۱۱۰) --}}
@php
    $isLegal = (($draftType ?? 'real') === 'legal');
    $categoryOptions = $categories->map(fn ($c) => ['id' => $c->id, 'title' => $c->title])->values()->all();
@endphp
<div class="space-y-5" x-data="{
        busy: false,
        errors: {},
        cities: [],

        {{-- کامبوباکس دستهٔ اصلی تأمین کالا (فاز ۱۱۳ — جستجوی زنده) --}}
        catOpen: false,
        catQuery: '',
        catId: '',
        catTitle: '',
        catOptions: @js($categoryOptions),
        init() {
            window.spClearErrorOnInput(this.$refs.form, this);
        },
        get catFiltered() {
            let q = this.catQuery.trim();
            if (! q) { return this.catOptions; }
            return this.catOptions.filter(function (o) { return o.title.indexOf(q) !== -1; });
        },
        toggleCat() {
            this.catOpen = ! this.catOpen;
            if (this.catOpen) { this.catQuery = ''; this.$nextTick(() => this.$refs.catSearch?.focus()); }
        },
        pickCat(option) {
            this.catId = String(option.id);
            this.catTitle = option.title;
            this.catOpen = false;
            delete this.errors.main_supply_category_id;
        },
        clearCat() {
            this.catId = '';
            this.catTitle = '';
            this.catQuery = '';
        },

        async loadCities() {
            let pid = this.$refs.province.value;
            if (! pid) { this.cities = []; return; }
            try {
                let r = await fetch('{{ route('provinces.get-cities') }}?id=' + pid, { headers: { 'Accept': 'application/json' } });
                if (r.ok) {
                    this.cities = await r.json();
                    return;
                }
            } catch (e) {}
            this.cities = [];
            spToast('خطا در دریافت فهرست شهرها؛ دوباره تلاش کنید', 'error');
        },

        async submit() {
            this.busy = true;
            this.errors = {};
            let fd = new FormData(this.$refs.form);
            // فاز ۱۱۳ — ولیدیشن کلاینتی فیلدهای الزامی (پیام فارسی، بدون حبابهٔ مرورگر)
            spRequire(this.$refs.form, this.errors, {
                econtract: 'پذیرش قرارداد الکترونیکی الزامی است',
            });
            if (! this.catId) { this.errors.main_supply_category_id = ['دستهٔ اصلی تأمین کالا را انتخاب کنید']; }
            if (Object.keys(this.errors).length) {
                spToast('لطفاً فیلدهای الزامی را تکمیل کنید', 'error');
                this.busy = false;
                return;
            }
            try {
                let data = await spPost('{{ route('seller.registration_business_store') }}', fd);
                if (data.redirect) { window.location.href = data.redirect; return; }
                this.errors = data.errors || {};
                spToast(data.message || 'ثبت اطلاعات ناموفق بود', 'error');
                this.busy = false;
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); this.busy = false; }
        }
    }">
    @include('sellers.auth.partials.sp-fetch')
    @include('sellers.auth.partials.steps', ['active' => 6, 'back' => route('seller.registration_identity')])

    <div class="ap-card p-6 sm:p-8">
        <h1 class="text-lg font-extrabold text-ink dark:text-night-ink">اطلاعات کسب‌وکار</h1>
        <p class="mt-1 text-xs font-semibold text-ink-3 dark:text-night-ink-2">اطلاعات ورود، آدرس و فروشگاه خود را تکمیل کنید</p>

        <form class="mt-6 space-y-6" x-on:submit.prevent="submit()" x-ref="form" novalidate>
            <input type="hidden" name="lat_and_long" value="">

            {{-- اطلاعات ورود به پنل — فاز ۱۱۳: فقط ایمیل؛ رمز در گام «تنظیم رمز عبور» ست شده است --}}
            <section class="space-y-4">
                <h2 class="flex items-center gap-2 text-[13px] font-extrabold text-ink dark:text-night-ink">
                    <x-admin-panel::icon name="lock" class="size-4 text-brand-600 dark:text-brand-400" />
                    اطلاعات ورود به پنل
                </h2>
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="bd-email">ایمیل <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <x-admin-panel::icon name="mail" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                        <input id="bd-email" type="email" name="email" dir="ltr" required value="{{ $draftEmail ?? '' }}"
                               x-on:input="delete errors.email"
                               class="ap-input py-3! ps-10! text-start" placeholder="you@example.com" autocomplete="email">
                    </div>
                    <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.email" x-text="(errors.email || [])[0]" x-cloak></p>
                    <p class="mt-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2" x-show="! errors.email">این ایمیل برای ورود به پنل و ارتباط با شما استفاده می‌شود</p>
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
                                x-on:change="loadCities(); delete errors.state_id" class="ap-input py-3!">
                            <option value="">انتخاب استان…</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.state_id" x-text="(errors.state_id || [])[0]" x-cloak></p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-city">شهر <span class="text-red-500">*</span></label>
                        <select id="bd-city" name="city_id" class="ap-input py-3!" required x-on:change="delete errors.city_id">
                            <option value="">ابتدا استان را انتخاب کنید</option>
                            <template x-for="city in cities" :key="city.id">
                                <option x-text="city.name" x-bind:value="city.id"></option>
                            </template>
                        </select>
                        <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.city_id" x-text="(errors.city_id || [])[0]" x-cloak></p>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="bd-address">آدرس کامل <span class="text-red-500">*</span></label>
                    <input id="bd-address" type="text" name="address" required x-on:input="delete errors.address" class="ap-input py-3!"
                           placeholder="محله - خیابان اصلی - کوچه - پلاک - واحد">
                    <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.address" x-text="(errors.address || [])[0]" x-cloak></p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-post">کد پستی <span class="text-red-500">*</span></label>
                        <input id="bd-post" type="text" name="post_code" inputmode="numeric" maxlength="10" required x-on:input="delete errors.post_code" class="ap-input py-3! fa-digits" placeholder="۱۲۳۴۵-۶۷۸۹۰">
                        <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.post_code" x-text="(errors.post_code || [])[0]" x-cloak></p>
                        <p class="mt-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2" x-show="! errors.post_code">کد پستی ۱۰ رقمی است</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="bd-phone">تلفن ثابت <span class="text-red-500">*</span></label>
                        <input id="bd-phone" type="text" name="phone" inputmode="numeric" required x-on:input="delete errors.phone" class="ap-input py-3! fa-digits" placeholder="021-56000000">
                        <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.phone" x-text="(errors.phone || [])[0]" x-cloak></p>
                        <p class="mt-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2" x-show="! errors.phone">۱۱ رقم با کد شهر</p>
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
                    <input id="bd-bname" type="text" name="business_name" required x-on:input="delete errors.business_name" class="ap-input py-3!"
                           placeholder="نام فروشگاه شما در سایت {{ option('info_site_title', 'او پی شاپ') }}">
                    <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.business_name" x-text="(errors.business_name || [])[0]" x-cloak></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="bd-shaba">شمارهٔ شبا (برای تسویه) <span class="text-red-500">*</span></label>
                    <input id="bd-shaba" type="text" dir="ltr" name="shaba_number" required x-on:input="delete errors.shaba_number" class="ap-input py-3! font-mono text-start" placeholder="IR______________________________">
                    <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.shaba_number" x-text="(errors.shaba_number || [])[0]" x-cloak></p>
                    <p class="mt-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2" x-show="! errors.shaba_number">شبا باید متعلق به همان شخص/شرکت باشد تا تسویه بدون مشکل انجام شود</p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- فاز ۱۱۳ — دستهٔ اصلی تأمین کالا با کامبوباکس جستجودار؛
                         قبلاً کوئری where('category_id', null) هیچ ردیفی برنمی‌گرداند --}}
                    <div class="relative" x-on:click.outside="catOpen = false" x-on:keydown.escape.window="catOpen = false">
                        <label class="mb-1.5 block text-xs font-bold" for="bd-cat">دستهٔ اصلی تأمین کالا <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="hidden" name="main_supply_category_id" x-model="catId">
                        <button type="button" id="bd-cat" class="ap-input flex w-full items-center justify-between gap-2 py-3! text-start"
                                x-on:click="toggleCat()" aria-haspopup="listbox" x-bind:aria-expanded="String(catOpen)">
                            <span class="truncate text-[13px]" x-bind:class="catTitle ? 'font-bold text-ink dark:text-night-ink' : 'font-semibold text-ink-3 dark:text-night-ink-2'"
                                  x-text="catTitle || 'جستجو و انتخاب دسته‌بندی…'"></span>
                            <x-admin-panel::icon name="chevron-down" class="size-4 shrink-0 text-ink-3 transition-transform" x-bind:class="catOpen ? 'rotate-180' : ''" />
                        </button>
                        <button type="button" x-show="catTitle" x-cloak x-on:click="clearCat()"
                                class="absolute end-9 top-1/2 -translate-y-1/2 rounded-lg p-1 text-ink-3 transition-colors hover:text-red-500" aria-label="حذف انتخاب دسته‌بندی">
                            <x-admin-panel::icon name="circle-x" class="size-4.5" />
                        </button>
                    </div>
                        <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.main_supply_category_id" x-text="(errors.main_supply_category_id || [])[0]" x-cloak></p>

                        {{-- پنل جستجو و انتخاب --}}
                        <div x-show="catOpen" x-cloak x-transition.opacity.duration.150ms
                             class="absolute inset-x-0 top-full z-30 mt-1.5 overflow-hidden rounded-2xl border border-line bg-surface shadow-2xl dark:border-night-line dark:bg-night-2"
                             role="listbox" aria-label="فهرست دسته‌بندی‌ها">
                            <div class="border-b border-line p-2 dark:border-night-line">
                                <div class="relative">
                                    <x-admin-panel::icon name="search" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                                    <input x-ref="catSearch" type="text" x-model="catQuery"
                                           class="ap-input w-full py-2.5! pe-10! text-[13px]!" placeholder="جستجوی دسته‌بندی…" aria-label="جستجوی دسته‌بندی">
                                </div>
                            </div>
                            <div class="max-h-56 overflow-y-auto p-1.5">
                                <template x-for="option in catFiltered" :key="option.id">
                                    <button type="button" x-on:click="pickCat(option)" role="option"
                                            class="flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-start text-[13px] font-semibold transition-colors"
                                            x-bind:class="String(catId) === String(option.id)
                                                ? 'bg-brand-50 font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300'
                                                : 'text-ink-2 hover:bg-surface-2 dark:text-night-ink-2 dark:hover:bg-night-3'"
                                            x-text="option.title"></button>
                                </template>
                                <p x-show="! catFiltered.length" class="px-3 py-6 text-center text-xs font-semibold text-ink-3 dark:text-night-ink-2" x-cloak>
                                    دسته‌بندی‌ای با این عنوان پیدا نشد
                                </p>
                            </div>
                        </div>
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
                            <input type="checkbox" value="1" name="econtract" required x-on:change="delete errors.econtract" class="mt-1 size-4 rounded border-line accent-brand-600">
                            <span>
                                شرایط و قوانین <b class="text-ink dark:text-night-ink">قرارداد الکترونیکی</b> فروشندگان را می‌خوانم و می‌پذیرم.
                                @if (! empty($econtract->description))
                                    <a href="{{ route('front.index') }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline dark:text-brand-400">متن قرارداد</a>
                                @endif
                            </span>
                        </label>
                        <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.econtract" x-text="(errors.econtract || [])[0]" x-cloak></p>
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
