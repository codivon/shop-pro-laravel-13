{{-- فرم ایجاد/ویرایش تخفیف — پنل جدید (فاز ۴) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl" x-data="{ tab: 'info' }">

    <form wire:submit="save" class="space-y-6"
          x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- هدر --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="badge-percent" class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-extrabold">
                        {{ $isEditing ? 'ویرایش تخفیف: ' . ($title ?: '—') : 'ایجاد تخفیف جدید' }}
                    </h2>
                    @if ($isEditing)
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                            شناسه: <span class="fa-digits">{{ number_format($discountId) }}</span>
                            — کد فعلی: <code class="text-[10px]">{{ $code }}</code>
                        </p>
                    @endif
                </div>
            </div>
            <a href="{{ route('np.discounts.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به لیست
            </a>
        </div>

        {{-- تب‌ها --}}
        <div class="ap-card overflow-hidden">
            <div class="flex gap-1 overflow-x-auto border-b border-line bg-surface-2 px-2 pt-2 dark:border-night-line dark:bg-night-3/50" role="tablist">
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'info'"
                        x-bind:aria-selected="(tab === 'info').toString()" role="tab"
                        x-bind:class="tab === 'info' ? 'is-active' : ''">
                    <x-admin-panel::icon name="info" class="size-4" />
                    اطلاعات
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'limits'"
                        x-bind:aria-selected="(tab === 'limits').toString()" role="tab"
                        x-bind:class="tab === 'limits' ? 'is-active' : ''">
                    <x-admin-panel::icon name="sliders-horizontal" class="size-4" />
                    محدودیت‌ها
                </button>
            </div>

            {{-- ============ تب اطلاعات ============ --}}
            <div x-show="tab === 'info'" x-cloak class="animate-fade-in space-y-5 p-5 sm:p-6">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="discount-title">عنوان</label>
                        <input id="discount-title" type="text" wire:model="title" class="ap-input" placeholder="مثال: حراج نوروزی">
                        @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="discount-code">کد تخفیف</label>
                        <div class="flex gap-2">
                            <input id="discount-code" type="text" wire:model="code" dir="ltr"
                                   class="ap-input font-mono" placeholder="SALE2025">
                            <button type="button" wire:click="generateCode" class="ap-btn-outline shrink-0 !px-3"
                                    title="ایجاد خودکار کد تخفیف">
                                <x-admin-panel::icon name="wand-2" class="size-4" />
                            </button>
                        </div>
                        @error('code') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="discount-start">تاریخ شروع</label>
                        <input id="discount-start" type="text" wire:model="start_date" data-jdp data-jdp-only-date="false"
                               autocomplete="off" class="ap-input !py-2 fa-digits text-center" placeholder="۱۴۰۴/۰۱/۰۱ ۱۲:۰۰">
                        @error('start_date') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="discount-end">تاریخ پایان</label>
                        <input id="discount-end" type="text" wire:model="end_date" data-jdp data-jdp-only-date="false"
                               autocomplete="off" class="ap-input !py-2 fa-digits text-center" placeholder="۱۴۰۴/۰۱/۳۰ ۱۲:۰۰">
                        @error('end_date') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="discount-type">نوع تخفیف</label>
                        <select id="discount-type" wire:model.live="type" class="ap-input !py-2">
                            <option value="percent">درصد</option>
                            <option value="amount">مبلغ</option>
                        </select>
                        @error('type') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div x-show="$wire.type === 'amount'" x-cloak>
                        <label class="mb-1.5 block text-xs font-semibold" for="discount-price">مقدار تخفیف (تومان)</label>
                        <input id="discount-price" type="number" min="0" wire:model="price" class="ap-input fa-digits" placeholder="۱۰۰۰۰۰">
                        @error('price') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div x-show="$wire.type === 'percent'" x-cloak>
                        <label class="mb-1.5 block text-xs font-semibold" for="discount-percent">درصد تخفیف</label>
                        <input id="discount-percent" type="number" min="0" max="100" step="any" wire:model="percent"
                               class="ap-input fa-digits" placeholder="۲۰">
                        @error('percent') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div x-show="$wire.type === 'percent'" x-cloak>
                        <label class="mb-1.5 block text-xs font-semibold" for="discount-ceiling">سقف تخفیف (تومان)</label>
                        <input id="discount-ceiling" type="number" min="0" wire:model="discount_ceiling"
                               class="ap-input fa-digits" placeholder="بدون سقف">
                        @error('discount_ceiling') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="discount-least-price">کمترین مبلغ سفارش (تومان)</label>
                        <input id="discount-least-price" type="number" min="0" wire:model="least_price"
                               class="ap-input fa-digits" placeholder="بدون محدودیت">
                        @error('least_price') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="discount-least-count">کمترین تعداد محصول در سبد</label>
                        <input id="discount-least-count" type="number" min="0" wire:model="least_products_count"
                               class="ap-input fa-digits" placeholder="بدون محدودیت">
                        @error('least_products_count') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold" for="discount-description">توضیحات</label>
                        <textarea id="discount-description" rows="3" wire:model="description" class="ap-input" placeholder="توضیحات تخفیف…"></textarea>
                        @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ============ تب محدودیت‌ها ============ --}}
            <div x-show="tab === 'limits'" x-cloak class="animate-fade-in space-y-6 p-5 sm:p-6">

                {{-- مشتریان هدف + سهمیه --}}
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        @include('admin-panel::livewire.admin.discounts.partials.user-picker', [
                            'label' => 'انتخاب مشتریان (خالی = همه)',
                            'searchField' => 'usersSearch',
                            'resultsField' => 'usersResults',
                            'results' => $usersResults,
                            'selected' => $usersSelected,
                            'addAction' => 'addUser',
                            'removeAction' => 'removeUser',
                            'errorField' => 'users',
                        ])
                    </div>
                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-1">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="discount-quantity">تعداد مجاز برای استفاده</label>
                            <input id="discount-quantity" type="number" min="0" wire:model="quantity"
                                   class="ap-input fa-digits" placeholder="بدون محدودیت">
                            @error('quantity') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="discount-quantity-user">تعداد مجاز برای هر کاربر</label>
                            <input id="discount-quantity-user" type="number" min="0" wire:model="quantity_per_user"
                                   class="ap-input fa-digits" placeholder="بدون محدودیت">
                            @error('quantity_per_user') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- شامل تخفیف می‌شوند --}}
                <div class="space-y-3 rounded-2xl border border-line p-4 dark:border-night-line">
                    <p class="text-[13px] font-bold">محصولاتی که شامل تخفیف می‌شوند</p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-[13px] transition-colors {{ $include_type === 'all' ? 'border-brand-500 bg-brand-50/70 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'border-line hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3' }}">
                            <input type="radio" value="all" wire:model.live="include_type"
                                   class="size-4 border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            اعمال روی همه محصولات
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-[13px] transition-colors {{ $include_type === 'category' ? 'border-brand-500 bg-brand-50/70 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'border-line hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3' }}">
                            <input type="radio" value="category" wire:model.live="include_type"
                                   class="size-4 border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            انتخاب دسته‌بندی
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-[13px] transition-colors {{ $include_type === 'product' ? 'border-brand-500 bg-brand-50/70 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'border-line hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3' }}">
                            <input type="radio" value="product" wire:model.live="include_type"
                                   class="size-4 border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            انتخاب محصول
                        </label>
                    </div>

                    @if ($include_type === 'category')
                        <div>
                            <div class="max-h-56 overflow-y-auto rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3/50">
                                <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($this->categories as $category)
                                        <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-[13px] transition-colors hover:bg-surface-3 dark:hover:bg-night-3"
                                               wire:key="include-category-{{ $category->id }}">
                                            <input type="checkbox" value="{{ $category->id }}" wire:model="include_categories"
                                                   class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                                            <span class="truncate">{{ $category->title }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            @error('include_categories') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @elseif ($include_type === 'product')
                        @include('admin-panel::livewire.admin.discounts.partials.product-picker', [
                            'label' => 'انتخاب محصولات شامل تخفیف',
                            'searchField' => 'includeProductsSearch',
                            'resultsField' => 'includeProductsResults',
                            'results' => $includeProductsResults,
                            'selected' => $includeProductsSelected,
                            'addAction' => 'addIncludeProduct',
                            'removeAction' => 'removeIncludeProduct',
                            'errorField' => 'include_products',
                        ])
                    @endif
                </div>

                {{-- مستثنی از تخفیف --}}
                <div class="space-y-3 rounded-2xl border border-line p-4 dark:border-night-line">
                    <p class="text-[13px] font-bold">محصولاتی که شامل تخفیف نمی‌شوند</p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-[13px] transition-colors {{ $exclude_type === 'none' ? 'border-brand-500 bg-brand-50/70 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'border-line hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3' }}">
                            <input type="radio" value="none" wire:model.live="exclude_type"
                                   class="size-4 border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            هیچ کدام
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-[13px] transition-colors {{ $exclude_type === 'category' ? 'border-brand-500 bg-brand-50/70 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'border-line hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3' }}">
                            <input type="radio" value="category" wire:model.live="exclude_type"
                                   class="size-4 border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            انتخاب دسته‌بندی
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-[13px] transition-colors {{ $exclude_type === 'product' ? 'border-brand-500 bg-brand-50/70 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'border-line hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3' }}">
                            <input type="radio" value="product" wire:model.live="exclude_type"
                                   class="size-4 border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            انتخاب محصول
                        </label>
                    </div>

                    @if ($exclude_type === 'category')
                        <div>
                            <div class="max-h-56 overflow-y-auto rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3/50">
                                <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($this->categories as $category)
                                        <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-[13px] transition-colors hover:bg-surface-3 dark:hover:bg-night-3"
                                               wire:key="exclude-category-{{ $category->id }}">
                                            <input type="checkbox" value="{{ $category->id }}" wire:model="exclude_categories"
                                                   class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                                            <span class="truncate">{{ $category->title }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            @error('exclude_categories') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @elseif ($exclude_type === 'product')
                        @include('admin-panel::livewire.admin.discounts.partials.product-picker', [
                            'label' => 'انتخاب محصولات مستثنی',
                            'searchField' => 'excludeProductsSearch',
                            'resultsField' => 'excludeProductsResults',
                            'results' => $excludeProductsResults,
                            'selected' => $excludeProductsSelected,
                            'addAction' => 'addExcludeProduct',
                            'removeAction' => 'removeExcludeProduct',
                            'errorField' => 'exclude_products',
                        ])
                    @endif
                </div>

                {{-- سایر محدودیت‌ها --}}
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2.5 text-[13px] transition-colors hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                        <input type="checkbox" wire:model="only_first_purchase"
                               class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                        فقط برای اولین خرید
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2.5 text-[13px] transition-colors hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                        <input type="checkbox" wire:model="not_discount_products"
                               class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                        مستثنی کردن محصولات تخفیف‌دار
                    </label>
                </div>
            </div>
        </div>

        {{-- نوار ذخیره (چسبان) --}}
        <div class="ap-card sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 p-4 shadow-pop sm:p-5">
            <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                <input type="checkbox" wire:model="published"
                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                انتشار تخفیف؟
            </label>
            <div class="flex items-center gap-2">
                <a href="{{ route('np.discounts.index') }}" wire:navigate class="ap-btn-ghost !py-2">انصراف</a>
                <button type="submit" class="ap-btn-primary !py-2.5" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">ذخیره تخفیف</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                        در حال ذخیره…
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
