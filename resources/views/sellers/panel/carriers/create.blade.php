{{-- روش ارسال جدید (فاز ۱۰۸) — پورت sellers-old/panel/carriers/create.blade.php
     فرم کلاسیک POST به seller.carriers.store با عیناً همان فیلدهای قدیمی
     + آبشار استان→شهر (provinces.get-cities) + تب‌های اطلاعات کلی/مناطق و هزینه‌ها/بازه زمانی --}}
<div x-data="{
        loading: false,
        provinceId: '',
        cities: [],
        covered: 'all',
        deliveryType: 'default',

        // پورت data-action قدیمی province → getCities
        async fetchCities() {
            if (!this.provinceId) { this.cities = []; return; }
            try {
                const res = await fetch(@js($citiesUrl) + '?id=' + this.provinceId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                this.cities = await res.json();
            } catch (e) { this.cities = []; }
        },

        // ارسال فرم کلاسیک — کنترلر قدیمی response('success') می‌دهد؛ بازگشت به فهرست
        async submitForm() {
            this.loading = true;
            try {
                const res = await fetch(@js($storeUrl), {
                    method: 'POST',
                    body: new FormData(this.$refs.form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });

                if (res.ok) {
                    this.$dispatch('toast', { message: 'روش ارسال با موفقیت ایجاد شد.', type: 'success' });
                    setTimeout(() => window.Livewire ? Livewire.navigate(@js($indexUrl)) : (window.location.href = @js($indexUrl)), 800);
                    return;
                }

                const data = await res.json().catch(() => ({}));
                const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'خطا در ایجاد روش ارسال.');
                this.$dispatch('toast', { message: firstError, type: 'error' });
            } catch (e) {
                this.$dispatch('toast', { message: 'ارتباط با سرور برقرار نشد.', type: 'error' });
            } finally {
                this.loading = false;
            }
        },
     }">
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
                <x-admin-panel::icon name="truck" class="size-5 text-brand-600 dark:text-brand-400" />
                روش ارسال جدید
            </h1>
            <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">برای تعیین هزینه ارسال، فعال یا غیرفعال کردن سرویس‌های پستی و تنظیم محدوده ارسال از این قسمت استفاده نمایید.</p>
        </div>
        <a href="{{ $indexUrl }}" wire:navigate
           class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به فهرست
        </a>
    </div>

    {{-- ================= فرم کلاسیک — عیناً فیلدهای ویوی قدیمی ================= --}}
    <form x-ref="form" method="POST" action="{{ $storeUrl }}" enctype="multipart/form-data" x-on:submit.prevent>
        @csrf

        <section class="ap-card p-4 sm:p-6">
            <h2 class="mb-4 border-b border-line pb-3 text-sm font-bold dark:border-night-line">اطلاعات کلی</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">عنوان<span class="text-red-500">*</span></span>
                    <input type="text" name="title" required class="ap-input w-full">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تصویر</span>
                    <input type="file" accept="image/*" name="image" class="ap-input w-full file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">وضعیت</span>
                    <select name="is_active" class="ap-input w-full">
                        <option value="1">فعال</option>
                        <option value="0">غیر فعال</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">زمان انتظار</span>
                    <input type="text" name="waiting_time" class="ap-input w-full">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">حداکثر وزن بسته (گرم)</span>
                    <input type="number" name="max_package_weight" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">حداقل وزن بسته (گرم)</span>
                    <input type="number" name="min_package_weight" class="ap-input w-full fa-digits">
                </label>
                <label class="block sm:col-span-2 lg:col-span-3">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">توضیحات</span>
                    <textarea name="description" rows="3" class="ap-input w-full"></textarea>
                </label>
            </div>

            <h2 class="mb-4 mt-6 border-b border-line pb-3 text-sm font-bold dark:border-night-line">مناطق و هزینه ها</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">استان فروشگاه<span class="text-red-500">*</span></span>
                    <select name="province_id" required class="ap-input w-full" x-model="provinceId" x-on:change="fetchCities()">
                        <option value="">انتخاب کنید</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شهر فروشگاه<span class="text-red-500">*</span></span>
                    <select name="city_id" required class="ap-input w-full">
                        <option value="">انتخاب کنید</option>
                        <template x-for="city in cities" :key="city.id">
                            <option :value="city.id" x-text="city.name"></option>
                        </template>
                    </select>
                </label>
                <label class="flex min-h-11 cursor-pointer items-center gap-2 self-end px-1 text-xs font-bold text-ink-700 dark:text-night-ink-1">
                    <input type="checkbox" name="carrige_forward" class="size-4 rounded border-line accent-emerald-600">
                    پس کرایه
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">ارسال رایگان برای وزن های بزرگتر از (گرم)</span>
                    <input type="number" name="free_shipping_weight" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">ارسال رایگان برای مبالغ بزرگتر از</span>
                    <input type="number" name="free_shipping_price" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">هزینه اضافی برای بارهای سنگین تر (به ازای هر کیلوگرم)</span>
                    <input type="number" name="extra_cost" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نوع شهرهای تحت پوشش<span class="text-red-500">*</span></span>
                    <select name="covered_cities" class="ap-input w-full" x-model="covered">
                        <option value="all">همه</option>
                        <option value="select_city">انتخاب شهر</option>
                    </select>
                </label>
                <div class="block" x-show="covered === 'select_city'" x-cloak>
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شهرهای تحت پوشش</span>
                    <select name="included_cities[]" multiple size="6" class="ap-input h-auto w-full">
                        @foreach ($provinces as $province)
                            <optgroup label="{{ $province->name }}">
                                @foreach ($province->cities as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <small class="mt-1 block text-[11px] text-ink-400 dark:text-night-ink-2">با Ctrl (یا Cmd) چند شهر را انتخاب کنید</small>
                </div>
            </div>

            <h2 class="mb-4 mt-6 border-b border-line pb-3 text-sm font-bold dark:border-night-line">بازه زمانی ارسال</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نوع بازه زمانی ارسال<span class="text-red-500">*</span></span>
                    <select name="delivery_time_type" class="ap-input w-full" x-model="deliveryType">
                        <option value="default">متن پیشفرض</option>
                        <option value="user_select">انتخاب توسط کاربر</option>
                    </select>
                    <small class="mt-1 block text-[11px] text-ink-400 dark:text-night-ink-2">انتخاب کنید که بازه زمانی ثابت باشد یا کاربر انتخاب کند</small>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">شروع بازه زمانی از (روز بعد از سفارش)<span class="text-red-500">*</span></span>
                    <select name="start_days_after_order" class="ap-input w-full">
                        @for ($days = 1; $days <= 11; $days++)
                            <option value="{{ $days }}">از {{ $days }} روز بعد</option>
                        @endfor
                    </select>
                    <small class="mt-1 block text-[11px] text-ink-400 dark:text-night-ink-2">تعیین کنید که بازه انتخابی از چند روز بعد از سفارش شروع شود</small>
                </label>

                {{-- بازه پیشفرض — فقط برای نوع «متن پیشفرض» --}}
                <label class="block sm:col-span-2 lg:col-span-3" x-show="deliveryType === 'default'" x-cloak>
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">بازه زمانی ارسال (پیشفرض)</span>
                    <input type="text" name="default_delivery_range" placeholder="مثال: ارسال در 3 الی 6 روز کاری" class="ap-input w-full">
                </label>
            </div>

            {{-- انتخاب بازه توسط کاربر — فقط برای نوع «انتخاب توسط کاربر» --}}
            <div class="mt-4 rounded-xl border border-line p-4 dark:border-night-line" x-show="deliveryType === 'user_select'" x-cloak>
                <h3 class="mb-3 text-xs font-bold">انتخاب بازه زمانی ارسال</h3>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ([7 => '۷ روزه', 10 => '۱۰ روزه', 15 => '۱۵ روزه', 20 => '۲۰ روزه'] as $value => $label)
                        <label class="flex min-h-11 cursor-pointer items-center gap-2 rounded-xl border border-line px-3 text-xs font-bold text-ink-700 transition-colors hover:border-brand-500 dark:border-night-line dark:text-night-ink-2">
                            <input type="radio" name="user_select_ranges" value="{{ $value }}" class="size-4 accent-emerald-600">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-start gap-2 text-xs font-bold text-ink-700 dark:text-night-ink-1">
                        <input type="checkbox" name="disable_fridays" value="1" class="mt-0.5 size-4 rounded border-line accent-emerald-600">
                        <span>غیرفعال کردن جمعه‌ها<small class="block font-normal text-ink-400 dark:text-night-ink-3">در صورت فعال بودن، روزهای جمعه غیرقابل انتخاب می‌شوند</small></span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-2 text-xs font-bold text-ink-700 dark:text-night-ink-1">
                        <input type="checkbox" name="disable_holidays" value="1" class="mt-0.5 size-4 rounded border-line accent-emerald-600">
                        <span>غیرفعال کردن تعطیلات رسمی<small class="block font-normal text-ink-400 dark:text-night-ink-3">در صورت فعال بودن، روزهای تعطیل رسمی غیرقابل انتخاب می‌شوند</small></span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-2 border-t border-line pt-4 dark:border-night-line">
                <button type="button" x-on:click="submitForm()"
                        class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-5 text-xs font-bold"
                        :disabled="loading" x-bind:class="loading && 'opacity-60'">
                    <x-admin-panel::icon name="loader-circle" class="size-4" x-show="loading" x-cloak />
                    ایجاد روش ارسال
                </button>
            </div>
        </section>
    </form>
</div>
