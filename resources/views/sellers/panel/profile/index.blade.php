{{-- پروفایل فروشنده (فاز ۱۰۸) — پورت sellers-old/panel/profile/index.blade.php
     یک فرم کلاسیک PUT به seller.profile.update با عیناً همان فیلدهای قدیمی
     + مودال تأیید + امضای قرارداد (POST کلاسیک به seller.profile.set_econtract).
     کنترلر قدیمی response("success") می‌دهد پس ارسال با fetch و ریدایرکت به
     seller.profile.index انجام می‌شود (هم‌الگوی index.js قدیمی). --}}
<div x-data="{
        tab: 'info',
        confirmOpen: false,
        econtractOpen: false,
        econtractChoice: '',
        loading: false,
        stateId: '{{ $seller->seller_info->state_id }}',
        cities: @json($seller_info->province->cities->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()),
        updateUrl: @js($updateUrl),
        setEcontractUrl: @js($setEcontractUrl),
        citiesUrl: @js($citiesUrl),

        // پورت data-action قدیمی province → getCities
        async fetchCities() {
            if (!this.stateId) { this.cities = []; return; }
            try {
                const res = await fetch(this.citiesUrl + '?id=' + this.stateId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                this.cities = await res.json();
            } catch (e) { this.cities = []; }
        },

        // ارسال فرم کلاسیک به seller.profile.update — کنترلر قدیمی response("success") می‌دهد
        async submitForm() {
            this.loading = true;
            try {
                const res = await fetch(this.updateUrl, {
                    method: 'POST',
                    body: new FormData(this.$refs.form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });

                if (res.ok) {
                    this.confirmOpen = false;
                    this.$dispatch('toast', { message: 'تغییرات شما با موفقیت ثبت شد.', type: 'success' });
                    setTimeout(() => window.Livewire ? Livewire.navigate(@js(route('seller.profile.index'))) : (window.location.href = @js(route('seller.profile.index'))), 800);
                    return;
                }

                // خطای اعتبارسنجی (422) — اولین پیام به‌صورت toast
                const data = await res.json().catch(() => ({}));
                const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'خطا در ثبت اطلاعات، دوباره تلاش کنید.');
                this.$dispatch('toast', { message: firstError, type: 'error' });
            } catch (e) {
                this.$dispatch('toast', { message: 'ارتباط با سرور برقرار نشد.', type: 'error' });
            } finally {
                this.loading = false;
            }
        },

        // پورت click .set-econtract قدیمی — POST JSON به seller.profile.set_econtract
        async setEcontract() {
            if (!this.econtractChoice) {
                this.$dispatch('toast', { message: 'یک گزینه را انتخاب کنید', type: 'error' });
                return;
            }
            if (this.econtractChoice == '0') { this.econtractOpen = false; return; }

            this.loading = true;
            try {
                const body = new FormData();
                body.append('econtract', '1');
                body.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                const res = await fetch(this.setEcontractUrl, {
                    method: 'POST',
                    body: body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (data.status == 'success') {
                    this.econtractOpen = false;
                    this.$dispatch('toast', { message: 'قرارداد با موفقیت امضا شد.', type: 'success' });
                    setTimeout(() => window.Livewire ? Livewire.navigate(window.location.href) : window.location.reload(), 800);
                } else if (data.status == 'documents') {
                    this.$dispatch('toast', { message: 'اطلاعات فروشنده تاییده نشده است', type: 'error' });
                }
            } catch (e) {
                this.$dispatch('toast', { message: 'ارتباط با سرور برقرار نشد.', type: 'error' });
            } finally {
                this.loading = false;
            }
        },
     }">
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4">
        <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
            <x-admin-panel::icon name="user" class="size-5 text-brand-600 dark:text-brand-400" />
            پروفایل شما
        </h1>
        <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">اطلاعات حساب، آدرس و مدارک فروشندگی خود را از این قسمت مدیریت کنید.</p>
    </div>

    {{-- ================= تب‌ها ================= --}}
    <div class="mb-4 flex gap-1.5 overflow-x-auto pb-1 sp-scroll">
        @foreach (['info' => 'اطلاعات کلی', 'address' => 'آدرس', 'bank' => 'حساب بانکی', 'econtract' => 'اطلاعات قرارداد', 'documents' => 'مدارک', 'login' => 'گذرواژه'] as $key => $label)
            <button type="button" x-on:click="tab = '{{ $key }}'"
                    class="min-h-11 shrink-0 rounded-xl px-4 text-xs font-bold transition-colors"
                    x-bind:class="tab === '{{ $key }}'
                        ? 'bg-brand-600 text-white shadow-sm'
                        : 'bg-surface-2 text-ink-600 hover:bg-surface-3 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-night-2'"
                    x-bind:aria-selected="tab === '{{ $key }}'" role="tab">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ================= فرم کلاسیک PUT — عیناً فیلدهای ویوی قدیمی ================= --}}
    <form x-ref="form" method="POST" action="{{ $updateUrl }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('put')

        {{-- ---------- تب اطلاعات کلی ---------- --}}
        <section class="ap-card p-4 sm:p-6" x-show="tab === 'info'" role="tabpanel" aria-label="اطلاعات کلی">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نام</span>
                    <input type="text" name="first_name" value="{{ $seller->seller_info->first_name }}" class="ap-input w-full">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نام خانوادگی</span>
                    <input type="text" name="last_name" value="{{ $seller->seller_info->last_name }}" class="ap-input w-full">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">جنسیت</span>
                    <select name="gender" class="ap-input w-full">
                        <option value="male" @selected($seller->seller_info->gender == 'male')>مرد</option>
                        <option value="female" @selected($seller->seller_info->gender == 'female')>زن</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تاریخ تولد</span>
                    <input type="text" name="birth_day" value="{{ $seller->seller_info->birth_day }}" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">کد ملی</span>
                    <input type="number" name="national_identity_number" value="{{ $seller->seller_info->national_identity_number }}" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شماره شناسنامه</span>
                    <input type="number" name="identity_card_number" value="{{ $seller->seller_info->identity_card_number }}" class="ap-input w-full fa-digits">
                </label>
            </div>

            {{-- بخش کسب‌وکار — برای «حقیقی» پنهان (هم‌الگوی business-div قدیمی) --}}
            <div class="mt-4 grid grid-cols-1 gap-4 border-t border-line pt-4 sm:grid-cols-2 lg:grid-cols-3 dark:border-night-line"
                 x-bind:class="{{ json_encode($seller->seller_info->private_business == 'private') }} ? 'hidden' : 'grid'">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نام شرکت</span>
                    <input type="text" name="company_name" value="{{ $seller->seller_info->company_name }}" class="ap-input w-full">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نوع شرکت</span>
                    <select name="company_type" class="ap-input w-full">
                        <option value="public" @selected($seller->seller_info->company_type == 'public')>سهامی عام</option>
                        <option value="joint_stock" @selected($seller->seller_info->company_type == 'joint_stock')>سهامی خاص</option>
                        <option value="ltd" @selected($seller->seller_info->company_type == 'ltd')>مسولیت محدود</option>
                        <option value="coop" @selected($seller->seller_info->company_type == 'coop')>تعاونی</option>
                        <option value="solidarity" @selected($seller->seller_info->company_type == 'solidarity')>تضامنی</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شماره ثبت</span>
                    <input type="text" name="company_registration_number" value="{{ $seller->seller_info->company_registration_number }}" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شناسه ملی</span>
                    <input type="text" name="company_national_identity_number" value="{{ $seller->seller_info->company_national_identity_number }}" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">کد اقتصادی</span>
                    <input type="text" name="company_economic_number" value="{{ $seller->seller_info->company_economic_number }}" class="ap-input w-full fa-digits">
                </label>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 border-t border-line pt-4 lg:grid-cols-2 dark:border-night-line">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">قصد فروش چه کالاهایی را دارید؟</span>
                    <select name="main_supply_category_id" class="ap-input w-full">
                        <option value="">انتخاب کنید</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($seller->seller_info && $seller->seller_info->main_supply_category_id == $category->id)>{{ $category->title }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تعداد حدودی تنوع کالای آماده فروش</span>
                    <select name="number_of_products" class="ap-input w-full">
                        <option value="">انتخاب کنید</option>
                        @foreach ([10 => '1-10', 50 => '11-50', 100 => '51-100', 300 => '101-300', 1000 => '301-1000', 3000 => '1001-3000', 10000 => '3001-10000', 30000 => '10001-30000'] as $value => $label)
                            <option value="{{ $value }}" @selected($seller->seller_info && $seller->seller_info->number_of_products == (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 border-t border-line pt-4 lg:grid-cols-3 dark:border-night-line">
                <div>
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">لوگوی فروشنده</span>
                    <img src="{{ $seller->seller_info->logo ? asset($seller->seller_info->logo) : asset('/empty.svg') }}"
                         class="h-24 w-24 rounded-xl border border-line object-cover dark:border-night-line" alt="لوگوی فروشنده">
                </div>
                <div class="lg:col-span-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">درباره فروشنده</span>
                        <textarea name="bio" rows="3" class="ap-input w-full">{{ $seller->seller_info->bio }}</textarea>
                    </label>
                    <label class="mt-3 block">
                        <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تغییر لوگو</span>
                        <input type="file" accept="image/*" name="image" class="ap-input w-full file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white">
                        <small class="mt-1 block text-[11px] text-ink-400 dark:text-night-ink-2">بهترین اندازه <span class="text-red-500">600 * 600</span> پیکسل میباشد.</small>
                    </label>
                </div>
            </div>
        </section>

        {{-- ---------- تب آدرس ---------- --}}
        <section class="ap-card p-4 sm:p-6" x-show="tab === 'address'" role="tabpanel" aria-label="آدرس" x-cloak>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">ایمیل</span>
                    <input type="email" name="email" value="{{ $seller->email }}" class="ap-input w-full" dir="ltr">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تلفن همراه <span class="text-red-500">(ثبت نام)</span></span>
                    <input type="number" value="{{ $seller->seller_info->mobile }}" disabled class="ap-input w-full cursor-not-allowed opacity-60 fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">وب سایت</span>
                    <input type="text" name="website" value="{{ $seller->seller_info->website }}" class="ap-input w-full" dir="ltr">
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">استان فروشگاه</span>
                    <select name="state_id" class="ap-input w-full" x-model="stateId" x-on:change="fetchCities()">
                        <option value="">انتخاب کنید</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}" @selected($seller->seller_info->state_id == $province->id)>{{ $province->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شهر فروشگاه</span>
                    <select name="city_id" class="ap-input w-full">
                        <template x-for="city in cities" :key="city.id">
                            <option :value="city.id" x-text="city.name" :selected="city.id == {{ (int) $seller->seller_info->city_id }}"></option>
                        </template>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">موقعیت مکانی</span>
                    <input type="text" name="location" value="{{ $seller->seller_info->location }}" class="ap-input w-full">
                </label>

                <label class="block sm:col-span-2 lg:col-span-3">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">آدرس</span>
                    <input type="text" name="address" value="{{ $seller->seller_info->address }}" class="ap-input w-full">
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">کد پستی</span>
                    <input type="number" name="post_code" value="{{ $seller->seller_info->post_code }}" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تلفن ثابت</span>
                    <input type="number" name="phone" value="{{ $seller->seller_info->phone }}" class="ap-input w-full fa-digits">
                </label>
            </div>
        </section>

        {{-- ---------- تب حساب بانکی ---------- --}}
        <section class="ap-card p-4 sm:p-6" x-show="tab === 'bank'" role="tabpanel" aria-label="حساب بانکی" x-cloak>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شماره شبا</span>
                    <input type="number" name="shaba_number" value="{{ $seller->seller_info->shaba_number }}" class="ap-input w-full fa-digits" dir="ltr">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">صاحب حساب</span>
                    <input type="text" value="{{ $seller->seller_info->full_name }}" disabled class="ap-input w-full cursor-not-allowed opacity-60">
                </label>
            </div>
        </section>

        {{-- ---------- تب اطلاعات قرارداد ---------- --}}
        <section class="ap-card p-4 sm:p-6" x-show="tab === 'econtract'" role="tabpanel" aria-label="اطلاعات قرارداد" x-cloak>
            @if ($seller_info->econtract == '1')
                <div class="mb-3 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    قرارداد توسط شما امضا شده است.
                </div>
                <button type="button" x-on:click="econtractOpen = true"
                        class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                    <x-admin-panel::icon name="file-signature" class="size-4" />
                    مشاهده قرارداد
                </button>
            @else
                <div class="mb-3 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    قرارداد توسط شما امضا نشده است.
                </div>
                <button type="button" x-on:click="econtractOpen = true"
                        class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                    <x-admin-panel::icon name="file-signature" class="size-4" />
                    از اینجا امضا کنید
                </button>
            @endif
        </section>

        {{-- ---------- تب مدارک ---------- --}}
        <section class="ap-card p-4 sm:p-6" x-show="tab === 'documents'" role="tabpanel" aria-label="مدارک" x-cloak>
            @if ($seller->status_documents == 'Reject')
                <div class="mb-3 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    مدارک شما توسط کارشناسان ما برسی و مورد تایید قرار نگرفت، لطفا مجددا مدارک خود را بارگذاری کنید.
                </div>
            @elseif ($seller->status_documents == 'Accept')
                <div class="mb-3 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    مدارک شما تایید شده است.
                </div>
            @elseif ($seller->status_documents == 'Waiting')
                <div class="mb-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-bold text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    مدارک شما در حال برسی می باشد.
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تصویر کارت ملی</span>
                    <img src="{{ $seller->seller_info->card_image ? asset($seller->seller_info->card_image) : asset('/empty.svg') }}"
                         class="max-h-52 w-full rounded-xl border border-line object-contain dark:border-night-line" alt="تصویر کارت ملی">
                    @if ($seller->status_documents == 'Reject')
                        <input type="file" accept="image/*" name="card_image" class="ap-input mt-2 w-full file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white">
                    @endif
                </div>
                <div>
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تصویر پشت کارت ملی</span>
                    <img src="{{ $seller->seller_info->card_image_back ? asset($seller->seller_info->card_image_back) : asset('/empty.svg') }}"
                         class="max-h-52 w-full rounded-xl border border-line object-contain dark:border-night-line" alt="تصویر پشت کارت ملی">
                    @if ($seller->status_documents == 'Reject')
                        <input type="file" accept="image/*" name="card_image_back" class="ap-input mt-2 w-full file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white">
                    @endif
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">مشمولیت مالیات بر ارزش افزوده</span>
                    <select name="vat_free" class="ap-input w-full" x-on:change="vatFree = $event.target.value">
                        <option value="1" @selected($seller->seller_info->vat_free == '1')>بله</option>
                        <option value="2" @selected($seller->seller_info->vat_free == '2')>خیر</option>
                    </select>
                </label>
                <div x-show="vatFree !== '2'" x-cloak>
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تصویر گواهی ارزش افزوده</span>
                    <img src="{{ $seller->seller_info->vat_image ? asset($seller->seller_info->vat_image) : asset('/empty.svg') }}"
                         class="max-h-52 w-full rounded-xl border border-line object-contain dark:border-night-line" alt="تصویر گواهی ارزش افزوده">
                    @if ($seller->status_documents == 'Reject')
                        <input type="file" accept="image/*" name="vat_image" class="ap-input mt-2 w-full file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white">
                    @endif
                </div>
            </div>
        </section>

        {{-- ---------- تب گذرواژه ---------- --}}
        <section class="ap-card p-4 sm:p-6" x-show="tab === 'login'" role="tabpanel" aria-label="گذرواژه" x-cloak>
            <div class="mx-auto grid max-w-xl grid-cols-1 gap-4">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">گذرواژه قبلی</span>
                    <input type="password" name="prev_password" class="ap-input w-full" dir="ltr" autocomplete="current-password">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">گذرواژه جدید</span>
                    <input type="password" name="password" class="ap-input w-full" dir="ltr" autocomplete="new-password">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تکرار گذرواژه جدید</span>
                    <input type="password" name="password_confirmation" class="ap-input w-full" dir="ltr" autocomplete="new-password">
                </label>
                <div class="flex items-start gap-2 rounded-xl border border-line bg-surface-2 p-3 text-xs text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    در صورتی که نمیخواهید گذرواژه را عوض کنید، فیلدهای گذرواژه را خالی بگذارید.
                </div>
            </div>
        </section>
    </form>

    {{-- دکمهٔ ثبت تغییرات — با مودال تأیید (پورت error-edit-modal قدیمی) --}}
    <div class="ap-card mt-4 flex items-center justify-between gap-3 p-4 sm:p-6">
        <p class="hidden text-xs text-ink-500 sm:block dark:text-night-ink-2">پس از ثبت، اطلاعات شما برای بررسی به کارشناسان ارسال می‌شود.</p>
        <button type="button" x-on:click="confirmOpen = true"
                class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-5 text-xs font-bold">
            <x-admin-panel::icon name="save" class="size-4" />
            ثبت تغییرات
        </button>
    </div>

    {{-- ================= مودال تأیید ثبت (پورت error-edit-modal قدیمی) ================= --}}
    <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="confirmOpen = false" x-transition.opacity></div>
        <div class="ap-card relative z-10 w-full max-w-sm" x-show="confirmOpen" x-transition.scale.origin.center>
            <header class="border-b border-line px-4 py-3 dark:border-night-line">
                <h3 class="text-sm font-bold">آیا مطمئن هستید؟</h3>
            </header>
            <div class="px-4 py-4 text-xs leading-relaxed text-ink-600 dark:text-night-ink-2">
                با ایجاد تغییرات در پنل،اطلاعات شما باید توسط کارشناسان برسی و تایید شود، این کار ممکن است زمان بر باشد.
            </div>
            <footer class="flex items-center justify-start gap-2 border-t border-line px-4 py-3 dark:border-night-line">
                <button type="button" x-on:click="confirmOpen = false"
                        class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-4 text-xs font-bold">خیر</button>
                <button type="button" x-on:click="submitForm()"
                        class="inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-red-500 px-4 text-xs font-bold text-white transition-colors hover:bg-red-600"
                        :disabled="loading" x-bind:class="loading && 'opacity-60'">
                    <x-admin-panel::icon name="loader-circle" class="size-4" x-show="loading" x-cloak />
                    بله ویرایش شود
                </button>
            </footer>
        </div>
    </div>

    {{-- ================= مودال قرارداد (پورت seller-econtracts-modal قدیمی) ================= --}}
    <div x-show="econtractOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="econtractOpen = false" x-transition.opacity></div>
        <div class="ap-card relative z-10 max-h-[90vh] w-full max-w-3xl overflow-y-auto sp-scroll" x-show="econtractOpen" x-transition.scale.origin.center>
            <header class="sticky top-0 flex items-center justify-between border-b border-line bg-surface px-4 py-3 dark:border-night-line dark:bg-night-1">
                <h3 class="text-sm font-bold">قرارداد فروشندگی</h3>
                <button type="button" x-on:click="econtractOpen = false" class="min-h-11 rounded p-1 text-ink-500 hover:text-red-500" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </header>
            <div class="space-y-3 px-4 py-4 text-xs leading-relaxed text-ink-700 dark:text-night-ink-2">
                <div class="rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-2">{!! $econtract->header !!}</div>
                <div class="rounded-xl border border-line p-3 dark:border-night-line">{!! $econtract->content !!}</div>
            </div>

            @if ($seller_info->econtract == '0')
                <footer class="space-y-3 border-t border-line px-4 py-4 dark:border-night-line">
                    <label class="flex min-h-11 cursor-pointer items-center gap-2 text-xs font-semibold text-ink-700 dark:text-night-ink-2">
                        <input type="radio" value="1" name="econtract" x-model="econtractChoice" class="size-4 accent-emerald-600">
                        قرارداد را کامل خوانده‌ام و موافقم
                    </label>
                    <label class="flex min-h-11 cursor-pointer items-center gap-2 text-xs font-semibold text-ink-700 dark:text-night-ink-2">
                        <input type="radio" value="0" name="econtract" x-model="econtractChoice" class="size-4 accent-red-500">
                        مخالفم و از ثبت‌نام انصراف می‌دهم
                    </label>
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <button type="button" x-on:click="setEcontract()"
                                class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-4 text-xs font-bold"
                                :disabled="loading" x-bind:class="loading && 'opacity-60'">
                            <x-admin-panel::icon name="loader-circle" class="size-4" x-show="loading" x-cloak />
                            ثبت امضا
                        </button>
                        <button type="button" x-on:click="econtractOpen = false"
                                class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-4 text-xs font-bold">بعدا انجام می‌دهم</button>
                    </div>
                </footer>
            @endif
        </div>
    </div>
</div>
