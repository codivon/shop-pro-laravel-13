{{-- ثبت سفارش دستی — پنل جدید (فاز ۲) | پورت back/orders/create --}}
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('np.orders.index') }}" wire:navigate
               class="rounded-xl p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3"
               aria-label="بازگشت به لیست سفارشات">
                <x-admin-panel::icon name="arrow-right" class="size-5" />
            </a>
            <div>
                <h2 class="text-base font-extrabold">ثبت سفارش جدید</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">سفارش دستی با وضعیت پرداخت‌شده برای مشتری ثبت می‌کند</p>
            </div>
        </div>
    </div>

    <form wire:submit="store" class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        {{-- ستون اصلی (تب‌ها) --}}
        <div class="space-y-5 xl:col-span-2" x-data="{ tab: 'user' }">

            {{-- نوار تب‌ها --}}
            <div class="ap-card flex gap-1 overflow-x-auto p-1.5">
                @foreach (['user' => ['۱', 'اطلاعات مشتری'], 'address' => ['۲', 'آدرس'], 'products' => ['۳', 'محصولات'], 'info' => ['۴', 'مشخصات سفارش']] as $key => [$num, $label])
                    <button type="button" x-on:click="tab = '{{ $key }}'"
                            class="flex-1 whitespace-nowrap rounded-xl px-3 py-2 text-[13px] font-bold transition-all"
                            :class="tab === '{{ $key }}'
                                ? 'bg-brand-600 text-white shadow-[0_6px_16px_-6px_rgb(5_150_105/.45)]'
                                : 'text-ink-2 hover:bg-surface-2 dark:text-night-ink-2 dark:hover:bg-night-3'">
                        <span class="fa-digits me-1">{{ $num }}</span>
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- تب ۱: اطلاعات مشتری --}}
            <section class="ap-card p-5" x-show="tab === 'user'"
                     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="user" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    اطلاعات مشتری
                </h3>

                <div class="space-y-4">
                    <div class="relative" x-data="{ open: false }" x-effect="open = Boolean($wire.showUserResults)">
                        <label class="mb-1.5 block text-xs font-semibold">نام کاربری (موبایل) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live.debounce.400ms="userSearch"
                               x-on:click.outside="open = false"
                               class="ap-input fa-digits" placeholder="حداقل ۳ کاراکتر — جستجوی مشتریان…" autocomplete="off">

                        <div x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute inset-x-0 top-full z-30 mt-1.5 max-h-64 overflow-y-auto rounded-xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2">
                            <template x-for="user in ($wire.userResults || [])" :key="user.id">
                                <button type="button" x-on:click="$wire.selectUser(user.id); open = false"
                                        class="flex w-full items-center justify-between gap-3 border-b border-line/60 px-4 py-2.5 text-start text-[13px] transition-colors last:border-0 hover:bg-brand-50 dark:border-night-line/60 dark:hover:bg-brand-500/10">
                                    <span class="fa-digits font-bold" x-text="user.username"></span>
                                    <span class="truncate text-ink-3 dark:text-night-ink-2" x-text="(user.first_name || '') + ' ' + (user.last_name || '')"></span>
                                </button>
                            </template>
                            <p x-show="! ($wire.userResults || []).length" class="px-4 py-3 text-center text-xs text-ink-3 dark:text-night-ink-2">مشتری با این نام کاربری یافت نشد</p>
                        </div>
                        @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">نام <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="firstName" class="ap-input" placeholder="نام مشتری">
                            @error('firstName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">نام خانوادگی <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="lastName" class="ap-input" placeholder="نام خانوادگی مشتری">
                            @error('lastName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="flex items-start gap-1.5 rounded-xl bg-surface-2 p-2.5 text-[11px] leading-5 text-ink-3 dark:bg-night-3/60 dark:text-night-ink-2">
                        <x-admin-panel::icon name="circle-alert" class="mt-0.5 size-3.5 shrink-0" />
                        با انتخاب مشتری از نتایج، نام و آدرس ثبت‌شده او به‌صورت خودکار وارد می‌شود. اگر مشتری وجود نداشته باشد پس از ثبت سفارش، به‌طور خودکار ساخته می‌شود.
                    </p>
                </div>
            </section>

            {{-- تب ۲: آدرس --}}
            <section class="ap-card p-5" x-show="tab === 'address'" x-cloak
                     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="map" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    آدرس ارسال
                </h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">استان <span class="text-red-500">*</span></label>
                        <select wire:model.live="provinceId" class="ap-input">
                            <option value="">انتخاب استان…</option>
                            @foreach ($this->provinces as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                        @error('provinceId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">شهر <span class="text-red-500">*</span></label>
                        <select wire:model.live="cityId" class="ap-input" @if (! $provinceId) disabled @endif>
                            <option value="">ابتدا استان را انتخاب کنید…</option>
                            @if ($provinceId)
                                @foreach (\App\Models\City::where('province_id', $provinceId)->orderBy('name')->get(['id', 'name']) as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        @error('cityId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">کد پستی</label>
                        <input type="text" wire:model="postalCode" class="ap-input fa-digits" placeholder="کد پستی ۱۰ رقمی">
                        @error('postalCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label class="mb-1.5 block text-xs font-semibold">آدرس کامل</label>
                    <textarea wire:model="address" rows="3" class="ap-input resize-none" placeholder="نشانی کامل پستی…"></textarea>
                    @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </section>

            {{-- تب ۳: محصولات --}}
            <section class="ap-card p-5" x-show="tab === 'products'" x-cloak
                     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="package" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    محصولات سفارش
                </h3>

                {{-- جستجوی محصول --}}
                <div class="relative" x-data="{ open: false }" x-effect="open = Boolean($wire.showProductResults)">
                    <label class="mb-1.5 block text-xs font-semibold">افزودن محصول</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-ink-3">
                            <x-admin-panel::icon name="search" class="size-4" />
                        </span>
                        <input type="text" wire:model.live.debounce.400ms="productSearch"
                               x-on:click.outside="open = false"
                               class="ap-input !ps-9" placeholder="نام محصول (حداقل ۳ کاراکتر)…" autocomplete="off">
                    </div>

                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute inset-x-0 top-full z-30 mt-1.5 max-h-80 overflow-y-auto rounded-xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2">
                        <template x-for="product in ($wire.productResults || [])" :key="product.id">
                            <div class="border-b border-line/60 px-4 py-3 last:border-0 dark:border-night-line/60">
                                <div class="flex items-center gap-2.5">
                                    <img :src="product.image" :alt="product.title" class="size-9 rounded-lg border border-line object-cover dark:border-night-line" loading="lazy">
                                    <p class="min-w-0 flex-1 truncate text-[13px] font-bold" x-text="product.title"></p>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <template x-for="price in product.prices" :key="price.id">
                                        <button type="button"
                                                x-on:click="$wire.addProduct(product.id, price.id); open = false"
                                                class="inline-flex max-w-full items-center gap-1.5 rounded-lg border border-line px-2.5 py-1.5 text-[11px] font-semibold transition-colors hover:border-brand-400 hover:bg-brand-50 dark:border-night-line dark:hover:bg-brand-500/10">
                                            <span class="max-w-44 truncate" x-text="price.label"></span>
                                            <span class="fa-digits shrink-0 text-brand-700 dark:text-brand-400" x-text="ApFormat.faDigits(price.sale.toLocaleString('en-US')) + ' تومان'"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <p x-show="! ($wire.productResults || []).length" class="px-4 py-3 text-center text-xs text-ink-3 dark:text-night-ink-2">محصولی با این عنوان یافت نشد</p>
                    </div>
                </div>

                {{-- لیست محصولات افزوده‌شده (رندر سمت سرور) --}}
                <div class="mt-4 space-y-2.5">
                    @forelse ($orderProducts as $index => $row)
                        <div wire:key="op-{{ $row['price_id'] }}"
                             class="flex flex-wrap items-center gap-3 rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3/60">
                            <img src="{{ $row['image'] }}" alt="{{ $row['title'] }}" width="44" height="44"
                                 class="size-11 rounded-lg border border-line object-cover dark:border-night-line">
                            <div class="min-w-40 flex-1">
                                <p class="truncate text-[13px] font-bold">{{ $row['title'] }}</p>
                                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">{{ $row['variant'] }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] text-ink-3">قیمت واحد</p>
                                <p class="fa-digits text-[13px] font-extrabold">{{ number_format($row['sale']) }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] text-ink-3" for="op-qty-{{ $index }}">تعداد</label>
                                <input type="number" id="op-qty-{{ $index }}" min="1"
                                       max="{{ $row['cart_max'] ?? 999 }}"
                                       wire:model.live="orderProducts.{{ $index }}.quantity"
                                       class="ap-input !w-20 !py-1.5 text-center fa-digits">
                                @error('orderProducts.' . $index . '.quantity') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] text-ink-3">جمع</p>
                                <p class="fa-digits text-[13px] font-extrabold text-brand-700 dark:text-brand-400">{{ number_format($row['sale'] * $row['quantity']) }}</p>
                            </div>
                            <button type="button" wire:click="removeProduct({{ $index }})"
                                    class="rounded-lg p-1.5 text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-500/10"
                                    aria-label="حذف محصول">
                                <x-admin-panel::icon name="trash-2" class="size-4" />
                            </button>
                        </div>
                    @empty
                        <div class="flex flex-col items-center gap-2 py-8 text-ink-3 dark:text-night-ink-2">
                            <x-admin-panel::icon name="package-search" class="size-9 opacity-40" />
                            <p class="text-sm">هنوز محصولی اضافه نشده است — از فیلد بالا جستجو و افزودن کنید</p>
                        </div>
                    @endforelse
                    @error('orderProducts') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </section>

            {{-- تب ۴: مشخصات سفارش --}}
            <section class="ap-card p-5" x-show="tab === 'info'" x-cloak
                     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="clipboard-list" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    مشخصات سفارش
                </h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">شیوه تحویل</label>
                        <select wire:model="carrierId" class="ap-input">
                            <option value="">بدون شرکت پستی</option>
                            @foreach ($this->carriers as $carrier)
                                <option value="{{ $carrier->id }}">{{ $carrier->title }}</option>
                            @endforeach
                        </select>
                        @error('carrierId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">هزینه ارسال (تومان)</label>
                        <input type="text" wire:model.live="shippingCost" class="ap-input fa-digits" placeholder="۰"
                               x-on:input="$el.value = $el.value.replace(/[^\d]/g, '')">
                        @error('shippingCost') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">مبلغ تخفیف (تومان)</label>
                        <input type="text" wire:model.live="discountAmount" class="ap-input fa-digits" placeholder="۰"
                               x-on:input="$el.value = $el.value.replace(/[^\d]/g, '')">
                        @error('discountAmount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">وضعیت ارسال <span class="text-red-500">*</span></label>
                        <select wire:model="shippingStatus" class="ap-input">
                            <option value="pending">در حال بررسی</option>
                            <option value="waiting">منتظر ارسال</option>
                            <option value="sent">ارسال شد</option>
                            <option value="canceled">ارسال لغو شد</option>
                        </select>
                        @error('shippingStatus') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">توضیحات</label>
                        <textarea wire:model="description" rows="3" class="ap-input resize-none" placeholder="توضیحات سفارش…"></textarea>
                        @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        </div>

        {{-- ستون کناری: خلاصه سفارش (محاسبه زنده) --}}
        <aside class="space-y-5">
            <section class="ap-card sticky top-24 p-5"
                     x-data
                     x-init="const calc = () => {
                        const rows = $wire.orderProducts || [];
                        const total = rows.reduce((s, r) => s + (Number(r.sale) * Number(r.quantity)), 0);
                        const ship = parseInt(($wire.shippingCost || '0').replace(/[^\d]/g, '')) || 0;
                        const disc = parseInt(($wire.discountAmount || '0').replace(/[^\d]/g, '')) || 0;
                        const items = document.getElementById('np-sum-items');
                        const final = document.getElementById('np-sum-final');
                        if (items) items.textContent = ApFormat.faDigits(total.toLocaleString('en-US'));
                        if (final) final.textContent = ApFormat.faDigits(Math.max(0, total + ship - disc).toLocaleString('en-US'));
                     };
                     $wire.$watch('orderProducts', calc);
                     $wire.$watch('shippingCost', calc);
                     $wire.$watch('discountAmount', calc);
                     calc();">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="receipt" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    خلاصه سفارش
                </h3>
                <dl class="space-y-3 text-[13px]">
                    <div class="flex items-center justify-between">
                        <dt class="text-ink-2 dark:text-night-ink-2">جمع محصولات</dt>
                        <dd class="fa-digits font-bold"><span id="np-sum-items">۰</span> تومان</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-ink-2 dark:text-night-ink-2">هزینه ارسال</dt>
                        <dd class="text-[12px] text-ink-3 dark:text-night-ink-2">در مبلغ نهایی لحاظ می‌شود</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-ink-2 dark:text-night-ink-2">تخفیف</dt>
                        <dd class="text-[12px] text-ink-3 dark:text-night-ink-2">از مبلغ نهایی کسر می‌شود</dd>
                    </div>
                    <div class="border-t border-line pt-3 dark:border-night-line">
                        <div class="flex items-center justify-between">
                            <dt class="font-bold">قابل پرداخت</dt>
                            <dd class="fa-digits text-base font-extrabold text-brand-700 dark:text-brand-400"><span id="np-sum-final">۰</span> تومان</dd>
                        </div>
                    </div>
                </dl>

                <button type="submit" wire:loading.attr="disabled" wire:target="store"
                        class="ap-btn-primary mt-5 flex w-full items-center justify-center gap-2">
                    <span wire:loading wire:target="store">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="store">
                        <x-admin-panel::icon name="check" class="size-4" />
                    </span>
                    ثبت سفارش
                </button>

                <p class="mt-3 flex items-start gap-1.5 rounded-xl bg-amber-50 p-2.5 text-[11px] leading-5 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                    <x-admin-panel::icon name="circle-alert" class="mt-0.5 size-3.5 shrink-0" />
                    سفارش با وضعیت «پرداخت شده» ثبت می‌شود؛ موجودی محصولات بلافاصله کاهش می‌یابد.
                </p>
            </section>
        </aside>
    </form>

    {{-- لودینگ جستجوها --}}
    <div wire:loading wire:target="userSearch, productSearch"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال جستجو…</span>
    </div>
</div>
