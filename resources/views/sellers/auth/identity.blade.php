{{-- گام ۴ ثبت‌نام — فاز ۱۱۰: اطلاعات تکمیلی (کد ملی و مشخصات؛ حقوقی = اطلاعات شرکت).
     تأکید اجباری: موبایل و کد ملی متعلق به یک نفر؛ اگر فینوتک فعال باشد همان‌جا کنترل می‌شود.
     پیش‌پر کردن از پیش‌نویس (برگشت به مرحله و ویرایش آزاد است) --}}
@php
    $isLegal = ($draft['type'] ?? 'real') === 'legal';
    [$birthYear, $birthMonth, $birthDay] = array_pad(explode('/', (string) ($draft['birth'] ?? '')), 3, '');
@endphp
<div class="space-y-5" x-data="{
        busy: false,
        errors: {},
        gender: '{{ $draft['gender'] ?? 'male' }}',
        async submit() {
            this.busy = true;
            this.errors = {};
            let fd = new FormData(this.$refs.form);
            try {
                let data = await spPost('{{ route('seller.registration_identity_store') }}', fd);
                if (data.warning) { spToast(data.warning, 'success'); }
                if (data.redirect) { window.location.href = data.redirect; return; }
                this.errors = data.errors || {};
                spToast(data.message || 'ثبت اطلاعات ناموفق بود', 'error');
                this.busy = false;
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); this.busy = false; }
        }
    }">
    @include('sellers.auth.partials.sp-fetch')
    @include('sellers.auth.partials.steps', ['active' => 5])

    <div class="ap-card p-6 sm:p-8">
        <h1 class="text-lg font-extrabold text-ink dark:text-night-ink">اطلاعات تکمیلی</h1>
        <p class="mt-1 text-xs font-semibold text-ink-3 dark:text-night-ink-2">
            {{ $isLegal ? 'اطلاعات نمایندهٔ شرکت و اطلاعات شرکت را وارد کنید' : 'مشخصات هویتی خود را دقیق و مطابق کارت ملی وارد کنید' }}
        </p>

        {{-- هشدار مهم: موبایل و کد ملی باید متعلق به یک نفر باشد --}}
        <div class="mt-4 flex items-start gap-2.5 rounded-2xl border border-amber-200 bg-amber-50/80 p-3.5 dark:border-amber-500/25 dark:bg-amber-500/10">
            <x-admin-panel::icon name="shield-alert" class="mt-0.5 size-4.5 shrink-0 text-amber-600 dark:text-amber-400" />
            <div class="text-[12px] font-semibold leading-6 text-amber-800 dark:text-amber-200">
                <b>شمارهٔ موبایل و کد ملی باید متعلق به یک نفر باشد.</b>
                @if ($sellerIdentityKyc ?? false)
                    این تطابق در همین گام با سرویس احراز هویت (فینوتک) کنترل می‌شود و در صورت مغایرت اجازهٔ ادامه داده نمی‌شود.
                @else
                    این تطابق در بررسی مدارک کنترل می‌شود.
                @endif
            </div>
        </div>

        <form class="mt-6 space-y-6" x-on:submit.prevent="submit()" x-ref="form">
            {{-- اطلاعات نماینده / شخص --}}
            <section class="space-y-4">
                <h2 class="flex items-center gap-2 text-[13px] font-extrabold text-ink dark:text-night-ink">
                    <x-admin-panel::icon name="id-card" class="size-4 text-brand-600 dark:text-brand-400" />
                    {{ $isLegal ? 'اطلاعات نمایندهٔ شرکت' : 'اطلاعات هویتی' }}
                </h2>
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="id-nin">
                        کد ملی <span class="text-red-500">*</span>
                    </label>
                    <input id="id-nin" type="text" name="national_code" inputmode="numeric" maxlength="10" required
                           value="{{ $draft['national_code'] }}" x-on:input="delete errors.national_code"
                           class="ap-input py-3! fa-digits" placeholder="کد ملی ۱۰ رقمی بدون خط تیره">
                    <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.national_code" x-text="(errors.national_code || [])[0]" x-cloak></p>
                    <p class="mt-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2" x-show="! errors.national_code">کد ملی باید متعلق به مالک شمارهٔ موبایل <span class="fa-digits font-bold">{{ $draft['mobile'] }}</span> باشد</p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="id-first">نام <span class="text-red-500">*</span></label>
                        <input id="id-first" type="text" name="first_name" required value="{{ $draft['first_name'] }}" x-on:input="delete errors.first_name"
                               class="ap-input py-3!" placeholder="نام خود را وارد کنید">
                        <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.first_name" x-text="(errors.first_name || [])[0]" x-cloak></p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="id-last">نام خانوادگی <span class="text-red-500">*</span></label>
                        <input id="id-last" type="text" name="last_name" required value="{{ $draft['last_name'] }}" x-on:input="delete errors.last_name"
                               class="ap-input py-3!" placeholder="نام خانوادگی خود را وارد کنید">
                        <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.last_name" x-text="(errors.last_name || [])[0]" x-cloak></p>
                    </div>
                </div>
            </section>

            {{-- مشخصات تولد — فقط حقیقی --}}
            @if (! $isLegal)
                <section class="space-y-4">
                    <h2 class="flex items-center gap-2 text-[13px] font-extrabold text-ink dark:text-night-ink">
                        <x-admin-panel::icon name="calendar" class="size-4 text-brand-600 dark:text-brand-400" />
                        تاریخ تولد و جنسیت
                    </h2>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold" for="id-by">سال <span class="text-red-500">*</span></label>
                            <select id="id-by" name="birth_year" class="ap-input py-3!">
                                <option value="">سال…</option>
                                @for ($y = 1380; $y >= 1320; $y--)
                                    <option value="{{ $y }}" @if ((string) $y === (string) $birthYear) selected @endif>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold" for="id-bm">ماه <span class="text-red-500">*</span></label>
                            <select id="id-bm" name="birth_month" class="ap-input py-3!">
                                <option value="">ماه…</option>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" @if ((string) $m === (string) $birthMonth) selected @endif>{{ $m }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold" for="id-bd">روز <span class="text-red-500">*</span></label>
                            <select id="id-bd" name="birth_day" class="ap-input py-3!">
                                <option value="">روز…</option>
                                @for ($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}" @if ((string) $d === (string) $birthDay) selected @endif>{{ $d }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-[13px] font-bold transition-all"
                               x-bind:class="gender === 'male' ? 'border-brand-500 bg-brand-50/60 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'border-line text-ink-2 dark:border-night-line dark:text-night-ink-2'">
                            <input type="radio" name="gender" value="male" x-model="gender" class="sr-only">
                            <x-admin-panel::icon name="user" class="size-4" />
                            آقا
                        </label>
                        <label class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-[13px] font-bold transition-all"
                               x-bind:class="gender === 'female' ? 'border-brand-500 bg-brand-50/60 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'border-line text-ink-2 dark:border-night-line dark:text-night-ink-2'">
                            <input type="radio" name="gender" value="female" x-model="gender" class="sr-only">
                            <x-admin-panel::icon name="user" class="size-4" />
                            خانم
                        </label>
                    </div>
                </section>
            @else
                {{-- اطلاعات شرکت — فقط حقوقی --}}
                <section class="space-y-4 rounded-2xl border border-line p-4 dark:border-night-line">
                    <h2 class="flex items-center gap-2 text-[13px] font-extrabold text-ink dark:text-night-ink">
                        <x-admin-panel::icon name="building-2" class="size-4 text-brand-600 dark:text-brand-400" />
                        اطلاعات شرکت
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold" for="id-company">نام شرکت <span class="text-red-500">*</span></label>
                            <input id="id-company" type="text" name="company_name" required value="{{ $draft['company_name'] }}" x-on:input="delete errors.company_name"
                                   class="ap-input py-3!" placeholder="نام شرکت را وارد کنید">
                            <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.company_name" x-text="(errors.company_name || [])[0]" x-cloak></p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold" for="id-ctype">نوع شرکت <span class="text-red-500">*</span></label>
                            <select id="id-ctype" name="company_type" class="ap-input py-3!">
                                @foreach (['سهامی خاص', 'سهامی عام', 'مسئولیت محدود', 'تعاونی', 'موسسه فردی', 'سایر'] as $companyType)
                                    <option value="{{ $companyType }}" @if ($draft['company_type'] === $companyType) selected @endif>{{ $companyType }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold" for="id-crn">شمارهٔ ثبت <span class="text-red-500">*</span></label>
                            <input id="id-crn" type="text" name="company_registration_number" inputmode="numeric" required value="{{ $draft['company_registration_number'] }}"
                                   class="ap-input py-3! fa-digits">
                            <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.company_registration_number" x-text="(errors.company_registration_number || [])[0]" x-cloak></p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold" for="id-cnin">شناسهٔ ملی شرکت <span class="text-red-500">*</span></label>
                            <input id="id-cnin" type="text" name="company_national_identity_number" inputmode="numeric" maxlength="11" required value="{{ $draft['company_national_identity_number'] }}"
                                   class="ap-input py-3! fa-digits">
                            <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.company_national_identity_number" x-text="(errors.company_national_identity_number || [])[0]" x-cloak></p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-bold" for="id-cen">شمارهٔ اقتصادی</label>
                            <input id="id-cen" type="text" name="company_economic_number" inputmode="numeric" maxlength="12" value="{{ $draft['company_economic_number'] }}"
                                   class="ap-input py-3! fa-digits">
                        </div>
                    </div>
                </section>
            @endif

            {{-- تأییدیه‌ها --}}
            <section class="space-y-2.5 rounded-2xl border border-line p-4 dark:border-night-line">
                <label class="flex cursor-pointer items-start gap-2.5 text-[12px] font-semibold leading-6 text-ink-2 dark:text-night-ink-2">
                    <input type="checkbox" name="ownership_confirm" value="1" required x-on:change="delete errors.ownership_confirm" class="mt-1 size-4 shrink-0 rounded border-line accent-brand-600">
                    <span>
                        تأیید می‌کنم شمارهٔ موبایل <b class="fa-digits">{{ $draft['mobile'] }}</b> و کد ملی وارد شده
                        <b class="text-ink dark:text-night-ink">متعلق به یک نفر</b> است و فروش با اطلاعات دیگران انجام نمی‌شود.
                    </span>
                </label>
                <p class="text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.ownership_confirm" x-text="(errors.ownership_confirm || [])[0]" x-cloak></p>
                <label class="flex cursor-pointer items-start gap-2.5 text-[12px] font-semibold leading-6 text-ink-2 dark:text-night-ink-2">
                    <input type="checkbox" name="info_correct" value="1" required x-on:change="delete errors.info_correct" class="mt-1 size-4 shrink-0 rounded border-line accent-brand-600">
                    <span>
                        صحت اطلاعات وارد شده را تأیید می‌کنم و مسئولیت درستی آن با من است.
                    </span>
                </label>
                <p class="text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.info_correct" x-text="(errors.info_correct || [])[0]" x-cloak></p>
            </section>

            <button type="submit" class="ap-btn-primary w-full py-3.5!" :disabled="busy" x-bind:class="busy ? 'opacity-60' : ''">
                <span x-show="!busy" class="flex items-center justify-center gap-2">
                    تأیید و ادامه
                    <x-admin-panel::icon name="chevron-left" class="size-4" />
                </span>
                <span x-show="busy" x-cloak class="flex items-center justify-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                    {{ ($sellerIdentityKyc ?? false) ? 'در حال بررسی احراز هویت…' : 'در حال ثبت اطلاعات…' }}
                </span>
            </button>
        </form>
    </div>
</div>
