{{-- تنظیمات فروشندگان — صفحهٔ فرود (فاز ۱۰۹) — محتوای لندینگ تک‌صفحه‌ای /seller --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-4xl space-y-5" x-data="{ confirmingReset: false }">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="layout-template" class="size-5" />
            </span>
            <div>
                <h1 class="text-base font-black">صفحهٔ فرود فروشندگان</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">محتوای صفحهٔ /seller — «چرا ما»، «مزایا» و «سوالات پر تکرار» از صفحات خودشان مدیریت می‌شوند</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('seller.index') }}" target="_blank" rel="noopener" class="ap-btn-outline px-3 py-2 text-[12px]">
                <x-admin-panel::icon name="external-link" class="size-4" />
                مشاهدهٔ صفحه
            </a>
            <button type="button" x-on:click="confirmingReset = true" class="ap-btn-outline px-3 py-2 text-[12px]">
                <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                بازگشت به پیش‌فرض
            </button>
        </div>
    </div>

    {{-- ================= هیرو ================= --}}
    <div class="ap-card p-5">
        <h2 class="flex items-center gap-2 text-sm font-extrabold">
            <x-admin-panel::icon name="layout-panel-top" class="size-4 text-brand-600 dark:text-brand-400" />
            هیرو (بالای صفحه)
        </h2>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold">عنوان <span class="text-red-500">*</span></label>
                <input type="text" wire:model="heroTitle" class="ap-input" placeholder="امروز ثبت‌نام کن و فروشت را شروع کن!">
                @error('heroTitle') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold">زیرعنوان</label>
                <textarea wire:model="heroSubtitle" rows="2" class="ap-input"></textarea>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">برچسب فیلد نام فروشگاه</label>
                <input type="text" wire:model="heroShopLabel" class="ap-input" placeholder="نام فروشگاه">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">برچسب فیلد موبایل</label>
                <input type="text" wire:model="heroMobileLabel" class="ap-input" placeholder="شماره موبایل">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">متن دکمه <span class="text-red-500">*</span></label>
                <input type="text" wire:model="heroButton" class="ap-input" placeholder="ادامه">
                @error('heroButton') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">متن ساعت پاسخ‌گویی</label>
                <input type="text" wire:model="heroResponseTime" class="ap-input" placeholder="میانگین زمان پاسخ‌گویی: کمتر از ۲ روز کاری">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold">تصویر هیرو (مسیر public — خالی = پیش‌فرض)</label>
                <input type="text" wire:model="heroImage" dir="ltr" class="ap-input" placeholder="images/seller-landing/hero.png">
            </div>
        </div>
    </div>

    {{-- ================= آمار ================= --}}
    <div class="ap-card p-5">
        <div class="flex items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-sm font-extrabold">
                <x-admin-panel::icon name="chart-column" class="size-4 text-brand-600 dark:text-brand-400" />
                نوار آمار
                <span class="rounded-lg bg-surface-2 px-2 py-0.5 text-[10px] font-bold text-ink-3 dark:bg-night-3 dark:text-night-ink-2">عدد خالی = محاسبهٔ واقعی از دیتابیس</span>
            </h2>
            <button type="button" wire:click="addStat" class="ap-btn-outline px-3 py-1.5 text-[12px]">
                <x-admin-panel::icon name="plus" class="size-4" />
                افزودن
            </button>
        </div>
        <div class="mt-4 space-y-3">
            @foreach ($stats as $i => $stat)
                <div class="flex items-end gap-2" wire:key="stat-{{ $i }}">
                    <div class="flex-1">
                        <label class="mb-1 block text-[11px] font-semibold">عدد (خالی = خودکار)</label>
                        <input type="text" wire:model="stats.{{ $i }}.value" class="ap-input py-2! fa-digits" placeholder="+۱۴۰ میلیون">
                    </div>
                    <div class="flex-1">
                        <label class="mb-1 block text-[11px] font-semibold">برچسب</label>
                        <input type="text" wire:model="stats.{{ $i }}.label" class="ap-input py-2!" placeholder="فروشندهٔ فعال">
                    </div>
                    <button type="button" wire:click="removeStat({{ $i }})"
                            class="mb-0.5 rounded-lg p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                            title="حذف" aria-label="حذف">
                        <x-admin-panel::icon name="trash-2" class="size-4" />
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ================= پلن‌ها ================= --}}
    <div class="ap-card p-5">
        <div class="flex items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-sm font-extrabold">
                <x-admin-panel::icon name="package-2" class="size-4 text-brand-600 dark:text-brand-400" />
                کارت‌های پلن همکاری
            </h2>
            <button type="button" wire:click="addPlan" class="ap-btn-outline px-3 py-1.5 text-[12px]">
                <x-admin-panel::icon name="plus" class="size-4" />
                افزودن
            </button>
        </div>
        <div class="mt-4 space-y-4">
            @foreach ($plans as $i => $plan)
                <div class="rounded-2xl border border-line p-4 dark:border-night-line" wire:key="plan-{{ $i }}">
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label class="mb-1 block text-[11px] font-semibold">عنوان پلن</label>
                            <input type="text" wire:model="plans.{{ $i }}.title" class="ap-input py-2!" placeholder="فروشندهٔ حرفه‌ای">
                        </div>
                        <button type="button" wire:click="removePlan({{ $i }})"
                                class="mb-0.5 rounded-lg p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                title="حذف" aria-label="حذف">
                            <x-admin-panel::icon name="trash-2" class="size-4" />
                        </button>
                    </div>
                    <div class="mt-3">
                        <label class="mb-1 block text-[11px] font-semibold">ویژگی‌ها (هر خط یک مورد)</label>
                        <textarea wire:model="plans.{{ $i }}.features" rows="4" class="ap-input"></textarea>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ================= مراحل ================= --}}
    <div class="ap-card p-5">
        <div class="flex items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-sm font-extrabold">
                <x-admin-panel::icon name="map" class="size-4 text-brand-600 dark:text-brand-400" />
                مراحل شروع فروش
            </h2>
            <button type="button" wire:click="addStep" class="ap-btn-outline px-3 py-1.5 text-[12px]">
                <x-admin-panel::icon name="plus" class="size-4" />
                افزودن
            </button>
        </div>
        <div class="mt-4 space-y-3">
            @foreach ($steps as $i => $step)
                <div class="flex items-end gap-2" wire:key="step-{{ $i }}">
                    <div class="flex-1">
                        <label class="mb-1 block text-[11px] font-semibold">عنوان مرحله {{ $i + 1 }}</label>
                        <input type="text" wire:model="steps.{{ $i }}.title" class="ap-input py-2!" placeholder="ثبت‌نام">
                    </div>
                    <div class="flex-[2]">
                        <label class="mb-1 block text-[11px] font-semibold">توضیح</label>
                        <input type="text" wire:model="steps.{{ $i }}.description" class="ap-input py-2!">
                    </div>
                    <button type="button" wire:click="removeStep({{ $i }})"
                            class="mb-0.5 rounded-lg p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                            title="حذف" aria-label="حذف">
                        <x-admin-panel::icon name="trash-2" class="size-4" />
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ================= تجربهٔ فروشندگان ================= --}}
    <div class="ap-card p-5">
        <div class="flex items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-sm font-extrabold">
                <x-admin-panel::icon name="message-square" class="size-4 text-brand-600 dark:text-brand-400" />
                تجربهٔ فروشندگان (کاروسل)
            </h2>
            <button type="button" wire:click="addTestimonial" class="ap-btn-outline px-3 py-1.5 text-[12px]">
                <x-admin-panel::icon name="plus" class="size-4" />
                افزودن
            </button>
        </div>
        <div class="mt-4 space-y-4">
            @foreach ($testimonials as $i => $testimonial)
                <div class="rounded-2xl border border-line p-4 dark:border-night-line" wire:key="testimonial-{{ $i }}">
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label class="mb-1 block text-[11px] font-semibold">نام</label>
                            <input type="text" wire:model="testimonials.{{ $i }}.name" class="ap-input py-2!" placeholder="آقای محمدی">
                        </div>
                        <div class="flex-1">
                            <label class="mb-1 block text-[11px] font-semibold">کسب‌وکار</label>
                            <input type="text" wire:model="testimonials.{{ $i }}.business" class="ap-input py-2!" placeholder="فروشگاه پوشاک">
                        </div>
                        <button type="button" wire:click="removeTestimonial({{ $i }})"
                                class="mb-0.5 rounded-lg p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                title="حذف" aria-label="حذف">
                            <x-admin-panel::icon name="trash-2" class="size-4" />
                        </button>
                    </div>
                    <div class="mt-3">
                        <label class="mb-1 block text-[11px] font-semibold">متن نظر</label>
                        <textarea wire:model="testimonials.{{ $i }}.quote" rows="2" class="ap-input"></textarea>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ================= فروشندگان برتر ================= --}}
    <div class="ap-card p-5">
        <div class="flex items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-sm font-extrabold">
                <x-admin-panel::icon name="trophy" class="size-4 text-brand-600 dark:text-brand-400" />
                فروشندگان برتر
                <span class="rounded-lg bg-surface-2 px-2 py-0.5 text-[10px] font-bold text-ink-3 dark:bg-night-3 dark:text-night-ink-2">بدون ردیف = بخش مخفی</span>
            </h2>
            <button type="button" wire:click="addBrand" class="ap-btn-outline px-3 py-1.5 text-[12px]">
                <x-admin-panel::icon name="plus" class="size-4" />
                افزودن
            </button>
        </div>
        <div class="mt-4 space-y-3">
            @foreach ($brands as $i => $brand)
                <div class="flex items-end gap-2" wire:key="brand-{{ $i }}">
                    <div class="flex-1">
                        <label class="mb-1 block text-[11px] font-semibold">نام فروشگاه</label>
                        <input type="text" wire:model="brands.{{ $i }}.name" class="ap-input py-2!">
                    </div>
                    <div class="flex-1">
                        <label class="mb-1 block text-[11px] font-semibold">دسته/برچسب</label>
                        <input type="text" wire:model="brands.{{ $i }}.tag" class="ap-input py-2!" placeholder="پوشاک">
                    </div>
                    <div class="flex-1">
                        <label class="mb-1 block text-[11px] font-semibold">عدد نمایشی</label>
                        <input type="text" wire:model="brands.{{ $i }}.metric" class="ap-input py-2! fa-digits" placeholder="۴٫۹ امتیاز">
                    </div>
                    <button type="button" wire:click="removeBrand({{ $i }})"
                            class="mb-0.5 rounded-lg p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                            title="حذف" aria-label="حذف">
                        <x-admin-panel::icon name="trash-2" class="size-4" />
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ================= CTA پایین ================= --}}
    <div class="ap-card p-5">
        <h2 class="flex items-center gap-2 text-sm font-extrabold">
            <x-admin-panel::icon name="rocket" class="size-4 text-brand-600 dark:text-brand-400" />
            دعوت پایین صفحه (CTA)
        </h2>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold">عنوان</label>
                <input type="text" wire:model="app.title" class="ap-input" placeholder="همه چیز را از یک پنل مدیریت کنید">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">متن دکمه</label>
                <input type="text" wire:model="app.button" class="ap-input" placeholder="شروع کنید">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">لینک دکمه (خالی = صفحهٔ ثبت‌نام)</label>
                <input type="text" wire:model="app.link" dir="ltr" class="ap-input" placeholder="https://">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold">برچسب‌ها (با ویرگول جدا شود)</label>
                <input type="text" wire:model="app.pills" class="ap-input" placeholder="مدیریت سفارش‌ها، مدیریت موجودی، قیمت‌گذاری">
            </div>
        </div>
    </div>

    {{-- ذخیره --}}
    <div class="sticky bottom-4 z-30">
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="ap-btn-primary w-full py-3! shadow-lg">
            <x-admin-panel::icon name="save" class="size-4.5" />
            ذخیرهٔ محتوای صفحهٔ فرود
        </button>
    </div>

    {{-- مودال بازگشت به پیش‌فرض --}}
    <div x-show="confirmingReset" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-on:click="confirmingReset=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="confirmingReset" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">بازگشت به پیش‌فرض؟</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">همهٔ تغییرات این صفحه با مقادیر پیش‌فرض جایگزین می‌شود.</p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="resetDefaults" wire:loading.attr="disabled"
                        x-on:click="confirmingReset=false"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله، بازگردانی
                </button>
                <button type="button" x-on:click="confirmingReset=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>
</div>
