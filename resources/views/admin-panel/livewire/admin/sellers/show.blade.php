{{-- پرونده فروشنده — پنل جدید (فاز ۳) — ۷ تب + آمار + جداول زیرین --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <img src="{{ $this->sellerInfo->logo ? asset($this->sellerInfo->logo) : asset('/empty.svg') }}"
                 alt="لوگو" class="size-12 rounded-2xl border border-line object-cover dark:border-night-line">
            <div>
                <h1 class="text-lg font-black">{{ $this->sellerInfo->business_name ?? 'فروشنده #' . $this->seller->id }}</h1>
                <p class="fa-digits mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">
                    کد فروشگاه: {{ number_format($this->seller->id) }} — {{ $this->privateBusiness === 'private' ? 'حقیقی' : 'حقوقی' }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('np.sellers.index') }}" wire:navigate class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به لیست
            </a>
            @can('sellers.delete')
                {{-- $wire در onclick خام تعریف نیست؛ از wire:confirm لایوایر استفاده می‌کنیم --}}
                <button type="button" wire:click="delete"
                        wire:confirm="با حذف فروشنده تمام داده‌های مرتبط از بین می‌رود. مطمئن هستید؟"
                        class="ap-btn !py-2 bg-red-600 text-white hover:bg-red-700">
                    <x-admin-panel::icon name="trash-2" class="size-4" />
                    حذف فروشنده
                </button>
            @endcan
        </div>
    </div>

    {{-- کارت‌های آماری --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">موجودی کیف پول</p>
                <p class="fa-digits mt-1 truncate text-xl font-black">{{ number_format($this->walletBalance) }} <span class="text-[11px] font-medium text-ink-3">تومان</span></p>
                <a href="{{ route('admin.wallets.show', ['wallet' => $this->seller->getWallet()]) }}" target="_blank" rel="noopener"
                   class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-brand-600 hover:underline dark:text-brand-400">
                    تاریخچه تراکنش‌ها
                    <x-admin-panel::icon name="external-link" class="size-3" />
                </a>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="wallet" class="size-5" />
            </span>
        </div>
        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">ارزش سفارشات موفق</p>
                <p class="fa-digits mt-1 truncate text-xl font-black">{{ \formatPriceUnits($this->paidOrdersSum) }} <span class="text-[11px] font-medium text-ink-3">تومان</span></p>
                <a href="{{ route('np.sellers.seller-orders', $this->seller) }}" wire:navigate
                   class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده همه</a>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                <x-admin-panel::icon name="banknote" class="size-5" />
            </span>
        </div>
        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">بازدید امروز</p>
                <p class="fa-digits mt-1 truncate text-xl font-black" title="کل: {{ number_format($this->viewsTotal) }}">{{ number_format($this->viewsToday) }}</p>
                <a href="{{ route('np.sellers.views', $this->seller) }}" wire:navigate
                   class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-brand-600 hover:underline dark:text-brand-400">جزئیات</a>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                <x-admin-panel::icon name="eye" class="size-5" />
            </span>
        </div>
        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">سفارشات</p>
                <p class="fa-digits mt-1 text-xl font-black">{{ number_format($this->ordersCount) }}</p>
                <a href="{{ route('np.sellers.seller-orders', $this->seller) }}" wire:navigate
                   class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده همه</a>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                <x-admin-panel::icon name="shopping-bag" class="size-5" />
            </span>
        </div>
        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">محصولات</p>
                <p class="fa-digits mt-1 text-xl font-black">{{ number_format($this->productsCount) }}</p>
                <a href="{{ route('np.sellers.seller-products', $this->seller) }}" wire:navigate
                   class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده همه</a>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                <x-admin-panel::icon name="package" class="size-5" />
            </span>
        </div>
        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">تنوع کالا</p>
                <p class="fa-digits mt-1 text-xl font-black">{{ number_format($this->variantsCount) }}</p>
                <a href="{{ route('np.sellers.seller-variants', $this->seller) }}" wire:navigate
                   class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده همه</a>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="layers" class="size-5" />
            </span>
        </div>
        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">اعلان‌های ارسالی</p>
                <p class="fa-digits mt-1 text-xl font-black">{{ number_format($this->notificationsCount) }}</p>
                <a href="{{ route('np.sellers.notifications', $this->seller) }}" wire:navigate
                   class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده همه</a>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                <x-admin-panel::icon name="bell" class="size-5" />
            </span>
        </div>
    </div>

    {{-- فرم ویرایش --}}
    <form wire:submit="save" class="space-y-5">

        {{-- ردیف وضعیت‌ها --}}
        <div class="ap-card grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1.5 block text-xs font-semibold">وضعیت حساب</label>
                <select wire:model="status" class="ap-input !py-2">
                    <option value="ACTIVE">✅ فعال</option>
                    <option value="INACTIVE">⛔ غیر فعال</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">وضعیت ثبت‌نام</label>
                <select wire:model="statusRegister" class="ap-input !py-2">
                    <option value="business-details">در حال تکمیل اطلاعات</option>
                    <option value="documents">بارگذاری مدارک</option>
                    <option value="complete">تکمیل ثبت‌نام</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">وضعیت مدارک</label>
                <select wire:model="statusDocuments" class="ap-input !py-2">
                    <option value="Accept">✅ تایید شده</option>
                    <option value="Waiting">⏳ در انتظار</option>
                    <option value="Reject">❌ رد شده</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">وضعیت کاری</label>
                <select wire:model="statusWork" class="ap-input !py-2">
                    <option value="ACTIVE">فعال</option>
                    <option value="EditProfile">ویرایش اطلاعات</option>
                    <option value="Stop">متوقف</option>
                </select>
            </div>
        </div>

        {{-- کارت ۷ تب --}}
        <div class="ap-card overflow-hidden" x-data="{ tab: 'basics' }">
            <div class="flex gap-1 overflow-x-auto border-b border-line p-2 dark:border-night-line">
                @foreach ([
                    'basics' => ['user', 'اطلاعات پایه'],
                    'bank' => ['credit-card', 'حساب بانکی'],
                    'contact' => ['map-pin', 'تماس و آدرس'],
                    'contract' => ['file-text', 'قرارداد'],
                    'docs' => ['file-signature', 'مدارک'],
                    'login' => ['key-round', 'اطلاعات ورود'],
                    'perf' => ['bar-chart-3', 'عملکرد'],
                ] as $tab => [$icon, $label])
                    <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = '{{ $tab }}'"
                            x-bind:aria-selected="(tab === '{{ $tab }}').toString()" role="tab"
                            x-bind:class="tab === '{{ $tab }}' ? 'is-active' : ''">
                        <x-admin-panel::icon name="{{ $icon }}" class="size-4" />
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="p-5 sm:p-6">

                {{-- تب اطلاعات پایه --}}
                <div x-show="tab === 'basics'" x-cloak class="animate-fade-in space-y-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">کد فروشنده</label>
                            <input type="text" value="{{ $this->seller->id }}" disabled class="ap-input fa-digits bg-surface-2 dark:bg-night-3">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">نوع فروشنده</label>
                            <select wire:model="privateBusiness" class="ap-input">
                                <option value="private">حقیقی</option>
                                <option value="business">حقوقی</option>
                            </select>
                            @error('privateBusiness') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">نام تجاری</label>
                            <input type="text" wire:model="businessName" class="ap-input">
                            @error('businessName') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- بلوک حقیقی --}}
                    <div x-show="$wire.privateBusiness === 'private'" x-cloak class="space-y-4 rounded-2xl border border-line p-4 dark:border-night-line">
                        <p class="text-xs font-bold text-ink-3 dark:text-night-ink-2">اطلاعات شخصی</p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">نام</label>
                                <input type="text" wire:model="firstName" class="ap-input">
                                @error('firstName') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">نام خانوادگی</label>
                                <input type="text" wire:model="lastName" class="ap-input">
                                @error('lastName') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">جنسیت</label>
                                <select wire:model="gender" class="ap-input">
                                    <option value="male">مرد</option>
                                    <option value="female">زن</option>
                                </select>
                                @error('gender') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">تاریخ تولد</label>
                                <input type="text" wire:model="birthDay" data-jdp data-jdp-only-date="true" class="ap-input fa-digits text-center">
                                @error('birthDay') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">کد ملی</label>
                                <input type="text" wire:model="nationalIdentityNumber" class="ap-input fa-digits">
                                @error('nationalIdentityNumber') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">شماره شناسنامه</label>
                                <input type="text" wire:model="identityCardNumber" class="ap-input fa-digits">
                                @error('identityCardNumber') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- بلوک حقوقی --}}
                    <div x-show="$wire.privateBusiness === 'business'" x-cloak class="space-y-4 rounded-2xl border border-line p-4 dark:border-night-line">
                        <p class="text-xs font-bold text-ink-3 dark:text-night-ink-2">اطلاعات شرکت</p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">نام شرکت</label>
                                <input type="text" wire:model="companyName" class="ap-input">
                                @error('companyName') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">نوع شرکت</label>
                                <select wire:model="companyType" class="ap-input">
                                    <option value="">انتخاب کنید</option>
                                    <option value="public">سهامی عام</option>
                                    <option value="joint_stock">سهامی خاص</option>
                                    <option value="ltd">مسئولیت محدود</option>
                                    <option value="coop">تعاونی</option>
                                    <option value="solidarity">تضامنی</option>
                                </select>
                                @error('companyType') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">شماره ثبت</label>
                                <input type="text" wire:model="companyRegistrationNumber" class="ap-input fa-digits">
                                @error('companyRegistrationNumber') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">شناسه ملی</label>
                                <input type="text" wire:model="companyNationalIdentityNumber" class="ap-input fa-digits">
                                @error('companyNationalIdentityNumber') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">کد اقتصادی</label>
                                <input type="text" wire:model="companyEconomicNumber" class="ap-input fa-digits">
                                @error('companyEconomicNumber') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">دسته‌بندی اصلی کالا</label>
                            <select wire:model="mainSupplyCategoryId" class="ap-input">
                                <option value="">انتخاب کنید</option>
                                @foreach ($this->categoryTree as $category)
                                    <option value="{{ $category['id'] }}">{{ $category['title'] }}</option>
                                @endforeach
                            </select>
                            @error('mainSupplyCategoryId') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">تعداد تقریبی تنوع کالا</label>
                            <select wire:model="numberOfProducts" class="ap-input">
                                <option value="">انتخاب کنید</option>
                                @foreach (['10' => '۱–۱۰', '50' => '۱۱–۵۰', '100' => '۵۱–۱۰۰', '300' => '۱۰۱–۳۰۰', '1000' => '۳۰۱–۱۰۰۰', '3000' => '۱۰۰۱–۳۰۰۰', '10000' => '۳۰۰۱–۱۰۰۰۰', '30000' => '۱۰۰۰۱–۳۰۰۰۰'] as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('numberOfProducts') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- لوگو و معرفی --}}
                    <div class="flex flex-wrap items-start gap-4 rounded-2xl border-2 border-dashed border-line p-4 dark:border-night-line">
                        <label class="relative size-20 shrink-0 cursor-pointer overflow-hidden rounded-xl border border-line bg-surface-2 dark:border-night-line dark:bg-night-3">
                            @if ($logo)
                                <img src="{{ $logo->temporaryUrl() }}" alt="پیش‌نمایش لوگو" class="absolute inset-0 size-full object-cover">
                            @elseif ($this->sellerInfo->logo)
                                <img src="{{ asset($this->sellerInfo->logo) }}" alt="لوگوی فعلی" class="absolute inset-0 size-full object-cover">
                            @else
                                <span class="flex size-full items-center justify-center text-ink-3">
                                    <x-admin-panel::icon name="image-plus" class="size-7" />
                                </span>
                            @endif
                            <input type="file" wire:model="logo" accept="image/*" class="absolute inset-0 cursor-pointer opacity-0">
                        </label>
                        <div class="min-w-52 flex-1">
                            <label class="mb-1.5 block text-[13px] font-semibold">تغییر لوگو</label>
                            <p class="text-xs text-ink-3 dark:text-night-ink-2">بهترین اندازه <strong>۶۰۰×۶۰۰</strong> پیکسل — فرمت JPG یا PNG</p>
                            @error('logo') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            @if ($logo)
                                <button type="button" wire:click="$set('logo', null)" class="mt-2 flex items-center gap-1 text-xs font-semibold text-red-500 hover:underline">
                                    <x-admin-panel::icon name="x" class="size-3.5" />
                                    حذف تصویر انتخابی
                                </button>
                            @endif
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">درباره فروشنده</label>
                        <textarea wire:model="bio" rows="3" class="ap-input">{{ $bio }}</textarea>
                    </div>
                </div>

                {{-- تب حساب بانکی --}}
                <div x-show="tab === 'bank'" x-cloak class="animate-fade-in space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">شماره شبا</label>
                            <input type="text" wire:model="shabaNumber" class="ap-input fa-digits" placeholder="IR…">
                            @error('shabaNumber') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">صاحب حساب</label>
                            <input type="text" value="{{ trim($firstName . ' ' . $lastName) ?: '—' }}" disabled class="ap-input bg-surface-2 dark:bg-night-3">
                        </div>
                    </div>
                </div>

                {{-- تب تماس و آدرس --}}
                <div x-show="tab === 'contact'" x-cloak class="animate-fade-in space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">ایمیل</label>
                            <input type="email" wire:model="email" class="ap-input" dir="ltr">
                            @error('email') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">تلفن همراه</label>
                            <input type="text" wire:model="mobile" class="ap-input fa-digits" placeholder="09…">
                            @error('mobile') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">تلفن ثابت</label>
                            <input type="text" wire:model="phone" class="ap-input fa-digits">
                            @error('phone') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">وب‌سایت</label>
                            <input type="text" wire:model="website" class="ap-input" dir="ltr">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">استان</label>
                            <select wire:model.live="stateId" class="ap-input">
                                <option value="">انتخاب کنید</option>
                                @foreach ($this->provinces as $province)
                                    <option value="{{ $province->id }}">{{ $province->name }}</option>
                                @endforeach
                            </select>
                            @error('stateId') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">شهر</label>
                            <select wire:model="cityId" class="ap-input">
                                <option value="">انتخاب کنید</option>
                                @foreach ($this->cities as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
                            @error('cityId') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">کد پستی</label>
                            <input type="text" wire:model="postCode" class="ap-input fa-digits">
                            @error('postCode') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <label class="mb-1.5 block text-[13px] font-semibold">آدرس کامل</label>
                            <input type="text" wire:model="address" class="ap-input">
                            @error('address') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-1">
                            <label class="mb-1.5 block text-[13px] font-semibold">موقعیت مکانی (lat,long)</label>
                            <input type="text" wire:model="location" class="ap-input" dir="ltr">
                        </div>
                    </div>
                </div>

                {{-- تب قرارداد --}}
                <div x-show="tab === 'contract'" x-cloak class="animate-fade-in">
                    @if ($this->sellerInfo->seller_contract_number || $this->sellerInfo->contract_start_date || $this->sellerInfo->contract_end_date)
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">شماره قرارداد</label>
                                <input type="text" value="{{ $this->sellerInfo->seller_contract_number ?? '—' }}" disabled class="ap-input bg-surface-2 fa-digits dark:bg-night-3">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">تاریخ شروع</label>
                                <input type="text" value="{{ $this->sellerInfo->contract_start_date ?? '—' }}" disabled class="ap-input bg-surface-2 fa-digits dark:bg-night-3">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">تاریخ پایان</label>
                                <input type="text" value="{{ $this->sellerInfo->contract_end_date ?? '—' }}" disabled class="ap-input bg-surface-2 fa-digits dark:bg-night-3">
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-line py-10 text-ink-3 dark:border-night-line dark:text-night-ink-2">
                            <x-admin-panel::icon name="file-text" class="size-10 opacity-40" />
                            <p class="text-sm font-medium">اطلاعات قرارداد هنوز ثبت نشده است.</p>
                        </div>
                    @endif
                </div>

                {{-- تب مدارک --}}
                <div x-show="tab === 'docs'" x-cloak class="animate-fade-in space-y-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                            <div class="flex h-40 items-center justify-center bg-surface-2 dark:bg-night-3">
                                @if ($cardImage)
                                    <img src="{{ $cardImage->temporaryUrl() }}" alt="پیش‌نمایش روی کارت ملی" class="max-h-full object-contain">
                                @elseif ($this->sellerInfo->card_image)
                                    <img src="{{ asset($this->sellerInfo->card_image) }}" alt="روی کارت ملی" class="max-h-full object-contain">
                                @else
                                    <x-admin-panel::icon name="image" class="size-8 text-ink-3" />
                                @endif
                            </div>
                            <div class="border-t border-line p-3 dark:border-night-line">
                                <label class="mb-1.5 block text-[13px] font-semibold">روی کارت ملی</label>
                                <input type="file" wire:model="cardImage" accept="image/*" class="ap-input !py-1.5 file:mr-2 file:rounded-lg file:border-0 file:bg-surface-3 file:px-2 file:py-1 file:text-xs dark:file:bg-night-3">
                                @error('cardImage') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                            <div class="flex h-40 items-center justify-center bg-surface-2 dark:bg-night-3">
                                @if ($cardImageBack)
                                    <img src="{{ $cardImageBack->temporaryUrl() }}" alt="پیش‌نمایش پشت کارت ملی" class="max-h-full object-contain">
                                @elseif ($this->sellerInfo->card_image_back)
                                    <img src="{{ asset($this->sellerInfo->card_image_back) }}" alt="پشت کارت ملی" class="max-h-full object-contain">
                                @else
                                    <x-admin-panel::icon name="image" class="size-8 text-ink-3" />
                                @endif
                            </div>
                            <div class="border-t border-line p-3 dark:border-night-line">
                                <label class="mb-1.5 block text-[13px] font-semibold">پشت کارت ملی</label>
                                <input type="file" wire:model="cardImageBack" accept="image/*" class="ap-input !py-1.5 file:mr-2 file:rounded-lg file:border-0 file:bg-surface-3 file:px-2 file:py-1 file:text-xs dark:file:bg-night-3">
                                @error('cardImageBack') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">مشمول مالیات ارزش افزوده</label>
                            <select wire:model.live="vatFree" class="ap-input">
                                <option value="1">بله</option>
                                <option value="2">خیر</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2" x-show="$wire.vatFree != '2'" x-cloak>
                            <div class="overflow-hidden rounded-2xl border border-line dark:border-night-line">
                                <div class="flex h-28 items-center justify-center bg-surface-2 dark:bg-night-3">
                                    @if ($vatImage)
                                        <img src="{{ $vatImage->temporaryUrl() }}" alt="پیش‌نمایش گواهی ارزش افزوده" class="max-h-full object-contain">
                                    @elseif ($this->sellerInfo->vat_image)
                                        <img src="{{ asset($this->sellerInfo->vat_image) }}" alt="گواهی ارزش افزوده" class="max-h-full object-contain">
                                    @else
                                        <x-admin-panel::icon name="image" class="size-8 text-ink-3" />
                                    @endif
                                </div>
                                <div class="border-t border-line p-3 dark:border-night-line">
                                    <label class="mb-1.5 block text-[13px] font-semibold">گواهی ارزش افزوده</label>
                                    <input type="file" wire:model="vatImage" accept="image/*" class="ap-input !py-1.5 file:mr-2 file:rounded-lg file:border-0 file:bg-surface-3 file:px-2 file:py-1 file:text-xs dark:file:bg-night-3">
                                    @error('vatImage') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- تب اطلاعات ورود --}}
                <div x-show="tab === 'login'" x-cloak class="animate-fade-in space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">رمز عبور جدید</label>
                            <input type="password" wire:model="password" class="ap-input" autocomplete="new-password">
                            @error('password') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">تکرار رمز عبور</label>
                            <input type="password" wire:model="passwordConfirmation" class="ap-input" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="flex items-center gap-2 rounded-xl bg-brand-50 p-3 text-[13px] font-medium text-brand-800 dark:bg-brand-500/10 dark:text-brand-300">
                        <x-admin-panel::icon name="info" class="size-4 shrink-0" />
                        در صورت عدم تغییر رمز، فیلدها را خالی بگذارید.
                    </div>
                </div>

                {{-- تب عملکرد --}}
                <div x-show="tab === 'perf'" x-cloak class="animate-fade-in space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">امتیاز عملکرد (از ۵)</label>
                            <select wire:model="operation" class="ap-input fa-digits">
                                <option value="">انتخاب کنید</option>
                                @for ($i = 0.5; $i <= 5; $i += 0.5)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">رضایت از کالا (درصد)</label>
                            <input type="number" wire:model="satisfaction" min="0" max="100" class="ap-input fa-digits">
                            @error('satisfaction') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex items-center gap-2 rounded-xl bg-brand-50 p-3 text-[13px] font-medium text-brand-800 dark:bg-brand-500/10 dark:text-brand-300">
                        <x-admin-panel::icon name="info" class="size-4 shrink-0" />
                        اگر خالی بماند، فروشنده به‌عنوان «تازه‌وارد» نمایش داده می‌شود.
                    </div>
                </div>
            </div>

            {{-- دکمه ذخیره --}}
            <div class="flex items-center justify-end gap-2.5 border-t border-line bg-surface-2 px-5 py-4 dark:border-night-line dark:bg-night-3">
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn-primary">
                    <x-admin-panel::icon name="check" class="size-4" />
                    ذخیره تغییرات
                </button>
            </div>
        </div>
    </form>

    {{-- آخرین اعلان‌ها --}}
    <div class="ap-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="bell" class="size-4.5 text-emerald-600 dark:text-emerald-400" />
                آخرین اعلان‌های ارسالی
            </h3>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="openNotifModal" class="ap-btn-outline !py-1.5 !text-xs">
                    <x-admin-panel::icon name="plus" class="size-3.5" />
                    اعلان جدید
                </button>
                <a href="{{ route('np.sellers.notifications', $this->seller) }}" wire:navigate class="text-xs font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده همه</a>
            </div>
        </div>
        @if (count($this->notifications))
            <div class="overflow-x-auto">
                <table class="w-full text-start text-[13px]">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                            <th class="px-3 py-2.5 text-start font-bold">#</th>
                            <th class="px-3 py-2.5 text-start font-bold">عنوان</th>
                            <th class="px-3 py-2.5 text-start font-bold">متن پیام</th>
                            <th class="px-3 py-2.5 text-start font-bold">اولویت</th>
                            <th class="px-3 py-2.5 text-center font-bold">خوانده‌شده</th>
                            <th class="px-3 py-2.5 text-center font-bold">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->notifications as $notification)
                            @php $p = $notification->priorityText(); @endphp
                            <tr wire:key="notif-{{ $notification->id }}" class="border-b border-line/60 last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                <td class="px-3 py-2.5 fa-digits">{{ $loop->iteration }}</td>
                                <td class="max-w-40 px-3 py-2.5"><p class="truncate font-semibold">{{ $notification->title ?: 'بدون عنوان' }}</p></td>
                                <td class="max-w-64 px-3 py-2.5 text-ink-2 dark:text-night-ink-2"><p class="truncate">{{ \Illuminate\Support\Str::limit(strip_tags($notification->message), 80) }}</p></td>
                                <td class="px-3 py-2.5">
                                    <span class="ap-badge {{ $p['color'] === 'success' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' : ($p['color'] === 'warning' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' : 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400') }}">{{ $p['title'] }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    @if ($this->notifRead($notification))
                                        <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">خوانده شد</span>
                                    @else
                                        <span class="text-ink-3">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center justify-center gap-0.5">
                                        <button type="button" wire:click="editNotif({{ $notification->id }})" class="rounded-lg p-1.5 text-ink-3 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10" title="ویرایش">
                                            <x-admin-panel::icon name="pencil" class="size-4" />
                                        </button>
                                        <button type="button" wire:click="deleteNotif({{ $notification->id }})" wire:confirm="این اعلان پس از حذف قابل بازیابی نخواهد بود." class="rounded-lg p-1.5 text-ink-3 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10" title="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="bell" class="size-8 opacity-40" />
                <p class="text-sm font-medium">هنوز اعلانی ارسال نشده است.</p>
            </div>
        @endif
    </div>

    {{-- آخرین سفارشات --}}
    @if (count($this->latestOrders))
        <div class="ap-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="shopping-bag" class="size-4.5 text-violet-600 dark:text-violet-400" />
                    آخرین سفارشات
                </h3>
                <a href="{{ route('np.sellers.seller-orders', $this->seller) }}" wire:navigate class="text-xs font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده همه سفارشات</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-start text-[13px]">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                            <th class="px-3 py-2.5 text-start font-bold">شماره سفارش</th>
                            <th class="px-3 py-2.5 text-start font-bold">تاریخ</th>
                            <th class="px-3 py-2.5 text-start font-bold">مبلغ</th>
                            <th class="px-3 py-2.5 text-start font-bold">وضعیت</th>
                            <th class="px-3 py-2.5 text-center font-bold">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->latestOrders as $order)
                            <tr wire:key="order-{{ $order->id }}" class="border-b border-line/60 last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                <td class="px-3 py-2.5 font-bold text-brand-600 dark:text-brand-400">#{{ number_format($order->id) }}</td>
                                <td class="px-3 py-2.5 fa-digits">{{ $this->jdate($order->created_at) }}</td>
                                <td class="px-3 py-2.5 fa-digits">{{ number_format($order->priceSeller($this->seller->id)) }} <span class="text-[10px] text-ink-3">تومان</span></td>
                                <td class="px-3 py-2.5">
                                    <span class="ap-badge {{ $order->status === 'paid' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' : ($order->status === 'unpaid' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' : 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400') }}">{{ $this->statusLabel($order->status) }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <a href="{{ route('np.orders.show', $order->id) }}" wire:navigate class="ap-btn-outline !px-2.5 !py-1 !text-xs">مشاهده</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- آخرین محصولات --}}
    <div class="ap-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="package" class="size-4.5 text-amber-600 dark:text-amber-400" />
                آخرین محصولات
            </h3>
            <a href="{{ route('np.sellers.seller-products', $this->seller) }}" wire:navigate class="text-xs font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده همه محصولات</a>
        </div>
        @if (count($this->latestProducts))
            <div class="overflow-x-auto">
                <table class="w-full text-start text-[13px]">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                            <th class="px-3 py-2.5 text-start font-bold">ID</th>
                            <th class="px-3 py-2.5 text-start font-bold">تصویر</th>
                            <th class="px-3 py-2.5 text-start font-bold">عنوان محصول</th>
                            <th class="px-3 py-2.5 text-start font-bold">تاریخ ایجاد</th>
                            <th class="px-3 py-2.5 text-center font-bold">موجودی</th>
                            <th class="px-3 py-2.5 text-start font-bold">وضعیت</th>
                            <th class="px-3 py-2.5 text-center font-bold">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->latestProducts as $product)
                            <tr wire:key="p-{{ $product->id }}" class="border-b border-line/60 last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                <td class="px-3 py-2.5 text-ink-3">#{{ number_format($product->id) }}</td>
                                <td class="px-3 py-2.5">
                                    <img src="{{ $product->image ? asset($product->image) : asset('/empty.svg') }}" alt="{{ $product->title }}" class="size-10 rounded-lg border border-line object-cover dark:border-night-line">
                                </td>
                                <td class="max-w-48 px-3 py-2.5"><p class="truncate font-semibold">{{ $product->title }}</p></td>
                                <td class="px-3 py-2.5 fa-digits">{{ $this->jdate($product->created_at) }}</td>
                                <td class="px-3 py-2.5 text-center fa-digits">{{ number_format($product->prices_sum_stock ?? $product->prices()->sum('stock')) }}</td>
                                <td class="px-3 py-2.5">
                                    <div class="flex flex-wrap items-center gap-1">
                                        @if ($product->isPublished())
                                            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">منتشر شده</span>
                                        @else
                                            <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">پیش‌نویس</span>
                                        @endif
                                        @if ($product->status === 'Accept')
                                            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">تایید شده</span>
                                        @elseif ($product->status === 'Waiting')
                                            <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">در انتظار</span>
                                        @elseif ($product->status === 'Reject')
                                            <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">رد شده</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center justify-center gap-0.5">
                                        @if (\Illuminate\Support\Facades\Route::has('front.products.show'))
                                            <a href="{{ route('front.products.show', ['product' => $product]) }}" target="_blank" rel="noopener" class="rounded-lg p-1.5 text-ink-3 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10" title="نمایش فرانت">
                                                <x-admin-panel::icon name="external-link" class="size-4" />
                                            </a>
                                        @endif
                                        <a href="{{ route('np.products.edit', $product) }}" wire:navigate class="rounded-lg p-1.5 text-ink-3 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10" title="ویرایش">
                                            <x-admin-panel::icon name="pencil" class="size-4" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="package" class="size-8 opacity-40" />
                <p class="text-sm font-medium">هنوز محصولی ثبت نشده است.</p>
            </div>
        @endif
    </div>

    {{-- آخرین تنوع‌ها --}}
    <div class="ap-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="layers" class="size-4.5 text-brand-600 dark:text-brand-400" />
                آخرین تنوع‌ها
            </h3>
            <a href="{{ route('np.sellers.seller-variants', $this->seller) }}" wire:navigate class="text-xs font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده همه تنوع‌ها</a>
        </div>
        @if (count($this->latestVariants))
            <div class="overflow-x-auto">
                <table class="w-full text-start text-[13px]">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                            <th class="px-3 py-2.5 text-start font-bold">ID</th>
                            <th class="px-3 py-2.5 text-start font-bold">محصول</th>
                            <th class="px-3 py-2.5 text-start font-bold">تاریخ</th>
                            <th class="px-3 py-2.5 text-center font-bold">موجودی</th>
                            <th class="px-3 py-2.5 text-center font-bold">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->latestVariants as $variant)
                            @php $product = \App\Models\Product::find($variant->product_id); @endphp
                            @if ($product)
                                <tr wire:key="v-{{ $variant->id }}" class="border-b border-line/60 last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                    <td class="px-3 py-2.5 text-ink-3">#{{ number_format($product->id) }}</td>
                                    <td class="max-w-48 px-3 py-2.5"><p class="truncate font-semibold">{{ $product->title }}</p></td>
                                    <td class="px-3 py-2.5 fa-digits">{{ $this->jdate($product->created_at) }}</td>
                                    <td class="px-3 py-2.5 text-center fa-digits">{{ number_format($product->prices()->sum('stock')) }}</td>
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center justify-center gap-0.5">
                                            @if (\Illuminate\Support\Facades\Route::has('front.products.show'))
                                                <a href="{{ route('front.products.show', ['product' => $product]) }}" target="_blank" rel="noopener" class="rounded-lg p-1.5 text-ink-3 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10" title="نمایش">
                                                    <x-admin-panel::icon name="external-link" class="size-4" />
                                                </a>
                                            @endif
                                            <a href="{{ route('np.products.edit', $product) }}" wire:navigate class="rounded-lg p-1.5 text-ink-3 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10" title="ویرایش">
                                                <x-admin-panel::icon name="pencil" class="size-4" />
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="layers" class="size-8 opacity-40" />
                <p class="text-sm font-medium">هنوز تنوعی ثبت نشده است.</p>
            </div>
        @endif
    </div>

    {{-- آخرین بازدیدها --}}
    <div class="ap-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="eye" class="size-4.5 text-amber-600 dark:text-amber-400" />
                آخرین بازدیدها
            </h3>
            <a href="{{ route('np.sellers.views', $this->seller) }}" wire:navigate class="text-xs font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده همه بازدیدها</a>
        </div>
        @if (count($this->latestViews))
            <div class="overflow-x-auto">
                <table class="w-full text-start text-[13px]">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                            <th class="px-3 py-2.5 text-start font-bold">تاریخ</th>
                            <th class="px-3 py-2.5 text-start font-bold">IP</th>
                            <th class="px-3 py-2.5 text-start font-bold">پلتفرم</th>
                            <th class="px-3 py-2.5 text-start font-bold">صفحه</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->latestViews as $view)
                            <tr wire:key="view-{{ $view->id }}" class="border-b border-line/60 last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                <td class="px-3 py-2.5 fa-digits">{{ $this->jdate($view->created_at, 'Y/m/d H:i') }}</td>
                                <td class="px-3 py-2.5 font-mono text-xs text-ink-2 dark:text-night-ink-2">{{ $view->ip }}</td>
                                <td class="px-3 py-2.5">{{ \get_option_property($view->options, 'platform') ?: '—' }}</td>
                                <td class="px-3 py-2.5" dir="ltr">
                                    <a href="{{ url(urldecode($view->path)) }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline dark:text-brand-400">{{ \Illuminate\Support\Str::limit(urldecode($view->path), 60) }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="eye" class="size-8 opacity-40" />
                <p class="text-sm font-medium">هنوز بازدیدی ثبت نشده است.</p>
            </div>
        @endif
    </div>

    {{-- روش‌های ارسال --}}
    <div class="ap-card overflow-hidden">
        <div class="border-b border-line px-5 py-4 dark:border-night-line">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="truck" class="size-4.5 text-emerald-600 dark:text-emerald-400" />
                روش‌های ارسال
            </h3>
        </div>
        @if ($this->carriers->count())
            <div class="overflow-x-auto">
                <table class="w-full text-start text-[13px]">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                            <th class="px-3 py-2.5 text-start font-bold">#</th>
                            <th class="px-3 py-2.5 text-start font-bold">عنوان</th>
                            <th class="px-3 py-2.5 text-start font-bold">شهر فروشگاه</th>
                            <th class="px-3 py-2.5 text-start font-bold">شهرهای تحت پوشش</th>
                            <th class="px-3 py-2.5 text-start font-bold">پس‌کرایه</th>
                            <th class="px-3 py-2.5 text-center font-bold">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->carriers as $carrier)
                            <tr wire:key="c-{{ $carrier->id }}" class="border-b border-line/60 last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                <td class="px-3 py-2.5 text-ink-3">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2.5 font-semibold">{{ $carrier->title }}</td>
                                <td class="px-3 py-2.5 text-xs">{{ $carrier->province?->name ?? '—' }} — {{ $carrier->city?->name ?? '—' }}</td>
                                <td class="px-3 py-2.5">
                                    @if ($carrier->covered_cities == 'all')
                                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">همه شهرها</span>
                                    @else
                                        <span class="text-xs font-bold text-brand-600 dark:text-brand-400">مشاهده لیست</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5">
                                    @if ($carrier->carrige_forward)
                                        <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">بله</span>
                                    @else
                                        <span class="text-xs text-ink-3">خیر</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center justify-center gap-0.5">
                                        @if (\Illuminate\Support\Facades\Route::has('admin.tariffs.index'))
                                            <a href="{{ route('admin.tariffs.index', ['carrier' => $carrier]) }}" target="_blank" rel="noopener" class="rounded-lg p-1.5 text-ink-3 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10" title="تعرفه‌ها">
                                                <x-admin-panel::icon name="receipt" class="size-4" />
                                            </a>
                                        @endif
                                        @if (\Illuminate\Support\Facades\Route::has('admin.carriers.edit'))
                                            <a href="{{ route('admin.carriers.edit', ['carrier' => $carrier]) }}" target="_blank" rel="noopener" class="rounded-lg p-1.5 text-ink-3 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10" title="ویرایش">
                                                <x-admin-panel::icon name="pencil" class="size-4" />
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="truck" class="size-8 opacity-40" />
                <p class="text-sm font-medium">هنوز روش ارسالی تعریف نشده است.</p>
            </div>
        @endif
    </div>

    {{-- مودال اعلان (ایجاد/ویرایش) --}}
    <div x-data="{ open: @entangle('notifModal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-lg rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">{{ $editingNotifId ? 'ویرایش اعلان' : 'اعلان جدید' }}</h3>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">عنوان</label>
                    <input type="text" wire:model="notifTitle" class="ap-input">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">اولویت</label>
                    <select wire:model="notifPriority" class="ap-input">
                        <option value="low">عادی</option>
                        <option value="medium">مهم</option>
                        <option value="high">خیلی مهم</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">متن پیام <span class="text-red-500">*</span></label>
                    <textarea wire:model="notifMessage" rows="4" class="ap-input"></textarea>
                    @error('notifMessage') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <label class="flex cursor-pointer items-center gap-2.5 text-[13px]">
                    <input type="checkbox" wire:model="notifPopup" class="size-4 rounded-md border-line text-brand-600 dark:border-night-line">
                    نمایش پاپ‌آپ به فروشنده
                </label>
            </div>

            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="{{ $editingNotifId ? 'updateNotif' : 'storeNotif' }}" wire:loading.attr="disabled"
                        wire:target="{{ $editingNotifId ? 'updateNotif' : 'storeNotif' }}" class="ap-btn-primary flex-1">
                    {{ $editingNotifId ? 'ذخیره تغییرات' : 'ایجاد اعلان' }}
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="save, delete, storeNotif, updateNotif, deleteNotif"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال پردازش…</span>
    </div>
</div>
