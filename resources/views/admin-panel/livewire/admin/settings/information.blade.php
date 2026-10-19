{{-- تنظیمات کلی سایت — پنل جدید (فاز ۷-ج۳) — پورت back/settings/information.blade.php --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="store" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-bold">تنظیمات کلی</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">اطلاعات پایه و پیکربندی عمومی وبسایت</p>
            </div>
        </div>
        <button type="submit" form="information-form" class="ap-btn-primary !py-2.5">
            <x-admin-panel::icon name="check-circle" class="size-4" />
            ذخیره تغییرات
        </button>
    </div>

    {{-- novalidate: اعتبارسنجی با Livewire است — inputهای type=email/url با داده‌های واقعی
         (مثلاً شماره تماس در ایمیل) مانع submit بومی HTML5 می‌شدند و فرم هرگز ذخیره نمی‌شد --}}
    <form id="information-form" wire:submit="save" x-data="{ tab: 'base' }" class="space-y-5" novalidate>

        {{-- تب‌ها --}}
        <div class="ap-card overflow-hidden">
            <div class="flex gap-1 overflow-x-auto border-b border-line bg-surface-2 px-2 pt-2 dark:border-night-line dark:bg-night-3/50" role="tablist">
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'base'"
                        x-bind:aria-selected="(tab === 'base').toString()" role="tab"
                        x-bind:class="tab === 'base' ? 'is-active' : ''">
                    <x-admin-panel::icon name="info" class="size-4" />
                    اطلاعات کلی
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'seo'"
                        x-bind:aria-selected="(tab === 'seo').toString()" role="tab"
                        x-bind:class="tab === 'seo' ? 'is-active' : ''">
                    <x-admin-panel::icon name="search" class="size-4" />
                    سئو و متا
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'scripts'"
                        x-bind:aria-selected="(tab === 'scripts').toString()" role="tab"
                        x-bind:class="tab === 'scripts' ? 'is-active' : ''">
                    <x-admin-panel::icon name="terminal" class="size-4" />
                    اسکریپت‌ها
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'map'"
                        x-bind:aria-selected="(tab === 'map').toString()" role="tab"
                        x-bind:class="tab === 'map' ? 'is-active' : ''">
                    <x-admin-panel::icon name="map" class="size-4" />
                    نقشه
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'torob'"
                        x-bind:aria-selected="(tab === 'torob').toString()" role="tab"
                        x-bind:class="tab === 'torob' ? 'is-active' : ''">
                    <x-admin-panel::icon name="link-2" class="size-4" />
                    ترب و ایمالز
                </button>
            </div>

            {{-- ============ تب اطلاعات کلی ============ --}}
            <div x-show="tab === 'base'" x-cloak class="animate-fade-in space-y-6 p-5 sm:p-6">

                {{-- تصاویر و لوگو --}}
                <section>
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="image" class="size-4 text-brand-600 dark:text-brand-400" />
                        تصاویر و لوگو
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            ['key' => 'info_icon', 'current' => 'current_info_icon', 'label' => 'آیکون سایت', 'hint' => 'بهترین اندازه: ۳۲×۳۲ پیکسل'],
                            ['key' => 'info_logo', 'current' => 'current_info_logo', 'label' => 'لوگوی شرکت', 'hint' => 'بهترین اندازه: ۲۰۰×۶۰ پیکسل'],
                            ['key' => 'info_logo_seller', 'current' => 'current_info_logo_seller', 'label' => 'لوگوی شرکت فروشندگان', 'hint' => 'بهترین اندازه: ۲۰۰×۶۰ پیکسل'],
                            ['key' => 'info_logo_panel_seller', 'current' => 'current_info_logo_panel_seller', 'label' => 'لوگوی پنل فروشندگان', 'hint' => 'بهترین اندازه: ۲۰۰×۶۰ پیکسل'],
                            ['key' => 'info_og_image', 'current' => 'current_info_og_image', 'label' => 'تصویر OG (اشتراک‌گذاری)', 'hint' => 'اندازه پیشنهادی: ۱۲۰۰×۶۳۰ پیکسل'],
                        ] as $img)
                            <div class="rounded-xl border border-line p-4 dark:border-night-line">
                                <x-admin-panel::media-picker
                                    wireModel="{{ $img['key'] }}"
                                    :value="${$img['current']}"
                                    :label="$img['label']"
                                    :hint="$img['hint']"
                                    height="h-28"
                                    aspect="contain"
                                />
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- اطلاعات پایه --}}
                <section>
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="info" class="size-4 text-brand-600 dark:text-brand-400" />
                        اطلاعات پایه
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">عنوان وبسایت <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="info_site_title" class="ap-input" placeholder="فروشگاه آنلاین ایران">
                            @error('info_site_title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">ایمیل</label>
                            <input type="email" dir="ltr" wire:model="info_email" class="ap-input" placeholder="info@site.ir">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">تلفن</label>
                            <input type="text" dir="ltr" wire:model="info_tel" class="ap-input" placeholder="021-12345678">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">فکس</label>
                            <input type="text" dir="ltr" wire:model="info_fax" class="ap-input" placeholder="021-12345679">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">کد پستی</label>
                            <input type="text" dir="ltr" wire:model="info_postal_code" class="ap-input" placeholder="1234567890">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">شماره پشتیبانی</label>
                            <input type="text" dir="ltr" wire:model="info_support_phone" class="ap-input" placeholder="021-00000000">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">ساعات کاری</label>
                            <input type="text" wire:model="info_working_hours" class="ap-input" placeholder="شنبه تا پنجشنبه ۸ تا ۱۷">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">رنگ اصلی سایت</label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model="info_primary_color" class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-line bg-transparent p-1 dark:border-night-line">
                                <input type="text" dir="ltr" wire:model="info_primary_color_text" class="ap-input font-mono" placeholder="#6366f1" maxlength="7">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">استان</label>
                            <select wire:model="info_province_id" class="ap-input">
                                <option value="">— انتخاب استان —</option>
                                @foreach ($this->provinces() as $province)
                                    <option value="{{ $province->id }}">{{ $province->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">شهر</label>
                            <select wire:model="info_city_id" class="ap-input">
                                <option value="">— انتخاب شهر —</option>
                                @foreach ($this->cities as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
                            @error('info_city_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold">آدرس</label>
                            <textarea rows="3" wire:model="info_address" class="ap-input" placeholder="تهران، خیابان ولیعصر، پلاک ۱۰۰"></textarea>
                        </div>
                    </div>
                </section>

                {{-- پیکربندی پیشرفته --}}
                <section>
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="sliders-horizontal" class="size-4 text-brand-600 dark:text-brand-400" />
                        پیکربندی پیشرفته
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">پیشوند آدرس ورود به بخش مدیریت</label>
                            <div class="flex items-center gap-1.5">
                                <input type="text" dir="ltr" wire:model="admin_route_prefix" class="ap-input font-mono" placeholder="admin">
                                <span class="shrink-0 text-[11px] text-ink-3">/site.ir</span>
                            </div>
                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">پس از تغییر، کش مسیرها/کانفیگ باید روی سرور تازه شود.</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">فعال کردن سایت چند زبانه <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">بزودی</span></label>
                            <select wire:model="multi_language_enabled" disabled class="ap-input cursor-not-allowed opacity-60">
                                <option value="0">🔒 غیرفعال</option>
                                <option value="1">🌐 فعال</option>
                            </select>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ============ تب سئو و متا ============ --}}
            <div x-show="tab === 'seo'" x-cloak class="animate-fade-in space-y-6 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">کلمات کلیدی</label>
                        <input type="text" wire:model="info_tags" class="ap-input" placeholder="کلمات کلیدی را با کاما جدا کنید">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">آدرس کانونیکال</label>
                        <input type="url" dir="ltr" wire:model="info_canonical" class="ap-input font-mono" placeholder="https://site.ir">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">
                            توضیحات کوتاه (Meta Description)
                            <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">حداکثر ۱۶۰ کاراکتر</span>
                        </label>
                        <textarea rows="3" maxlength="160" wire:model="info_short_description" class="ap-input" placeholder="توضیحات مختصری درباره سایت..."></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">متن فوتر</label>
                        <textarea rows="3" wire:model="info_footer_text" class="ap-input" placeholder="متن پایین صفحه سایت..."></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">Robots.txt</label>
                        <textarea rows="5" dir="ltr" wire:model="info_robots_txt" class="ap-input font-mono" placeholder="User-agent: *&#10;Allow: /"></textarea>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">اسکریپت نماد اعتماد (eNamad)</label>
                        <textarea rows="6" dir="ltr" wire:model="info_enamad" class="ap-input font-mono text-xs"></textarea>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">کد ساماندهی</label>
                        <textarea rows="6" dir="ltr" wire:model="info_samandehi" class="ap-input font-mono text-xs"></textarea>
                    </div>
                </div>
            </div>

            {{-- ============ تب اسکریپت‌ها ============ --}}
            <div x-show="tab === 'scripts'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">شناسه Google Tag Manager</label>
                        <input type="text" dir="ltr" wire:model="info_gtm_id" class="ap-input font-mono" placeholder="GTM-XXXXXXX">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">شناسه Meta Pixel (Facebook)</label>
                        <input type="text" dir="ltr" wire:model="info_meta_pixel" class="ap-input font-mono" placeholder="000000000000000">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">کدهای هدر</label>
                        <p class="mb-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">این کدها داخل تگ <code class="font-mono">&lt;head&gt;</code> قرار می‌گیرند</p>
                        <textarea rows="7" dir="ltr" wire:model="info_header_codes" class="ap-input font-mono text-xs"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">اسکریپت‌های اضافه (قبل از بسته شدن body)</label>
                        <p class="mb-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">این کدها قبل از بسته شدن تگ <code class="font-mono">&lt;/body&gt;</code> قرار می‌گیرند</p>
                        <textarea rows="7" dir="ltr" wire:model="info_scripts" class="ap-input font-mono text-xs"></textarea>
                    </div>
                </div>
            </div>

            {{-- ============ تب نقشه ============ --}}
            <div x-show="tab === 'map'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">عرض جغرافیایی (Latitude)</label>
                        <input type="number" step="any" dir="ltr" wire:model="info_latitude" class="ap-input font-mono" placeholder="38.07709880960678">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">طول جغرافیایی (Longitude)</label>
                        <input type="number" step="any" dir="ltr" wire:model="info_Longitude" class="ap-input font-mono" placeholder="46.28582686185837">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">سطح زوم</label>
                        <input type="number" min="1" max="20" dir="ltr" wire:model="info_map_zoom" class="ap-input font-mono" placeholder="13">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">کلید API نقشه (map.ir)</label>
                        <input type="text" dir="ltr" wire:model="map_api" class="ap-input font-mono" placeholder="eyJ0eXAiOiJKV1Qi...">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">نوع نقشه</label>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition-colors has-checked:border-brand-500 has-checked:bg-brand-50/60 dark:has-checked:bg-brand-500/10"
                                   @class(['border-line dark:border-night-line', 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10' => $info_map_type === 'google'])>
                                <input type="radio" value="google" wire:model="info_map_type" class="size-4 text-brand-600">
                                <span class="flex size-9 items-center justify-center rounded-lg bg-red-50 text-red-500">
                                    <x-admin-panel::icon name="map" class="size-4.5" />
                                </span>
                                <span>
                                    <span class="block text-[13px] font-bold">نقشه گوگل</span>
                                    <span class="block text-[11px] text-ink-3">Google Maps</span>
                                </span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition-colors has-checked:border-brand-500 has-checked:bg-brand-50/60 dark:has-checked:bg-brand-500/10"
                                   @class(['border-line dark:border-night-line', 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10' => $info_map_type !== 'google'])>
                                <input type="radio" value="mapir" wire:model="info_map_type" class="size-4 text-brand-600">
                                <span class="flex size-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                    <x-admin-panel::icon name="map-pin" class="size-4.5" />
                                </span>
                                <span>
                                    <span class="block text-[13px] font-bold">نقشه Map.ir</span>
                                    <span class="block text-[11px] text-ink-3">map.ir</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ تب ترب و ایمالز ============ --}}
            <div x-show="tab === 'torob'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="rounded-xl border border-line p-4 dark:border-night-line">
                    <h4 class="text-[13px] font-bold">آدرس API ترب</h4>
                    <p class="mt-1 text-xs leading-6 text-ink-3 dark:text-night-ink-2">
                        اطلاعات meta tag ترب برای محصولات قرار داده شده است. همچنین با ارائه api زیر به ترب می‌توانید مکانیزم ایندکس شدن محصولات در ترب را به‌صورت خودکار و سریع انجام دهید.
                    </p>
                    @if (Route::has('api.torob.products'))
                        <code class="mt-2 block select-all rounded-lg bg-surface-2 px-3 py-2 font-mono text-[11px] dark:bg-night-3" dir="ltr">{{ route('api.torob.products') }}</code>
                    @endif
                </div>
                <div class="rounded-xl border border-line p-4 dark:border-night-line">
                    <h4 class="text-[13px] font-bold">آدرس API ایمالز</h4>
                    <p class="mt-1 text-xs leading-6 text-ink-3 dark:text-night-ink-2">
                        اطلاعات meta tag ایمالز برای محصولات قرار داده شده است. همچنین با ارائه api زیر به ایمالز می‌توانید مکانیزم ایندکس شدن محصولات در ایمالز را به‌صورت خودکار و سریع انجام دهید.
                    </p>
                    @if (Route::has('api.emalls.products'))
                        <code class="mt-2 block select-all rounded-lg bg-surface-2 px-3 py-2 font-mono text-[11px] dark:bg-night-3" dir="ltr">{{ route('api.emalls.products') }}</code>
                    @endif
                </div>
            </div>
        </div>

        {{-- نوار ذخیره‌ی چسبان --}}
        <div class="sticky bottom-4 z-10 flex items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-lg backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">تغییرات پس از ذخیره روی کل سایت اعمال می‌شوند.</span>
            <button type="submit" class="ap-btn-primary !py-2.5">
                <x-admin-panel::icon name="check-circle" class="size-4" />
                ذخیره تغییرات
            </button>
        </div>
    </form>
</div>
