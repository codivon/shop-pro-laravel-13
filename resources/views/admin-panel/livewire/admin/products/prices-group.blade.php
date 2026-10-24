{{-- تغییر گروهی قیمت — پورت products/prices-group قدیمی (فاز ۷-ج۲) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-5xl space-y-5"
     x-data="{ tab: '{{ $type }}' }">

    <div class="ap-card overflow-hidden">
        <header class="flex items-center gap-2.5 border-b border-line px-5 py-4 dark:border-night-line">
            <x-admin-panel::icon name="coins" class="size-4.5 text-brand-600 dark:text-brand-400" />
            <h3 class="text-sm font-bold">تغییر گروهی قیمت</h3>
        </header>

        <div class="p-4 sm:p-5">
            {{-- تب‌های هدف (پورت tabs قدیمی: محصول/دسته‌بندی/برند) --}}
            <div class="mb-5 flex flex-wrap gap-1.5">
                @foreach (['product' => 'بر اساس محصول', 'category' => 'بر اساس دسته بندی', 'brand' => 'بر اساس برند'] as $key => $label)
                    <button type="button"
                            x-on:click="tab = '{{ $key }}'; $wire.set('type', '{{ $key }}', false)"
                            class="rounded-xl px-3.5 py-2 text-[13px] font-bold transition-colors"
                            :class="tab === '{{ $key }}'
                                ? 'bg-brand-600 text-white shadow-sm'
                                : 'bg-surface-2 text-ink-2 hover:bg-surface-3 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-night-2'">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- فیلدهای مشترک (نوع تغییر / درصد / مبلغ ثابت / تنوع فروشنده) --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="pg-type-change">نوع تغییر <span class="text-red-500">*</span></label>
                    <select id="pg-type-change" wire:model="typeChenge" class="ap-input !py-2">
                        <option value="" disabled selected>انتخاب کنید</option>
                        <option value="increase">افزایش قیمت</option>
                        <option value="decrease">کاهش قیمت</option>
                    </select>
                    @error('typeChenge') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                        <input type="checkbox" wire:model="sellerPrice"
                               class="size-4 rounded border-line text-brand-600 focus:ring-brand-500 dark:border-night-line">
                        اعمال روی تنوع‌های فروشنده‌ها؟
                    </label>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="pg-percent">درصد تغییر (0,100)</label>
                    <input id="pg-percent" type="text" wire:model="percent" dir="ltr"
                           class="ap-input !py-2 font-mono" placeholder="مثال: 10">
                    @error('percent') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="pg-price">مبلغ ثابت</label>
                    <input id="pg-price" type="text" wire:model="price" dir="ltr"
                           class="ap-input !py-2 font-mono" placeholder="مثال: 50000">
                    @error('price') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- هدف‌ها بر اساس تب --}}
            <div class="mt-5">
                {{-- محصول (چیپ‌پیکر جای اتوکامپلیت قدیمی) --}}
                <div x-show="tab === 'product'" x-cloak>
                    <label class="mb-1.5 block text-xs font-semibold">افزودن محصولات</label>
                    <div class="relative">
                        <input type="text" wire:model="productsSearch" autocomplete="off"
                               class="ap-input !py-2" placeholder="حداقل ۳ کلمه وارد کنید…">
                        <div wire:loading wire:target="productsSearch"
                             class="absolute inset-y-0 end-3 flex items-center">
                            <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                            </svg>
                        </div>

                        {{-- نتایج جستجو --}}
                        @if (count($productsResults))
                            <div class="absolute inset-x-0 top-full z-30 mt-1.5 max-h-64 overflow-auto rounded-xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2">
                                @foreach ($productsResults as $result)
                                    <button type="button" wire:click="addProduct({{ $result['id'] }})"
                                            class="block w-full px-4 py-2.5 text-start text-[13px] transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                                        {{ $result['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- چیپ‌های انتخاب‌شده --}}
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($productsSelected as $chip)
                            <span wire:key="pg-chip-{{ $chip['id'] }}"
                                  class="flex items-center gap-1.5 rounded-full border border-line bg-surface-2 py-1 pe-1.5 ps-3 text-[12px] font-semibold dark:border-night-line dark:bg-night-3">
                                {{ $chip['label'] }}
                                <button type="button" wire:click="removeProduct({{ $chip['id'] }})"
                                        class="rounded-full p-0.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                        aria-label="حذف">
                                    <x-admin-panel::icon name="x" class="size-3.5" />
                                </button>
                            </span>
                        @endforeach
                    </div>
                    @error('productsSelected') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- دسته‌بندی‌ها --}}
                <div x-show="tab === 'category'" x-cloak>
                    <label class="mb-1.5 block text-xs font-semibold">دسته بندی‌ها</label>
                    <select wire:model="categories" multiple size="10" class="ap-input !py-2 h-auto">
                        @foreach ($this->categoryList as $category)
                            <option value="{{ $category->id }}">
                                @for ($i = 0; $i < $category->parents()->count(); $i++)—@endfor
                                {{ $category->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('categories') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- برندها --}}
                <div x-show="tab === 'brand'" x-cloak>
                    <label class="mb-1.5 block text-xs font-semibold">برندها</label>
                    <select wire:model="brands" multiple size="10" class="ap-input !py-2 h-auto">
                        @foreach ($this->brandList as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    @error('brands') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- اعمال --}}
            <div class="mt-6 flex justify-center border-t border-line pt-5 dark:border-night-line">
                <button type="button" wire:click="updatePricesGroup" wire:loading.attr="disabled" wire:target="updatePricesGroup"
                        class="ap-btn min-w-40 !py-2.5">
                    <x-admin-panel::icon name="check" class="size-4" />
                    اعمال
                </button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="updatePricesGroup"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال اعمال تغییرات…</span>
    </div>
</div>
