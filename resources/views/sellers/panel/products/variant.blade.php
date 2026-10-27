{{-- مدیریت تنوع‌های قیمت محصول (فاز ۱۰۸) — پورت sellers-old/panel/products/variant.blade.php
     فیلدها عین قدیمی: prices[i][attributes][] / [price] / [discount] / [cart_max] / [cart_min]
     / [stock] / [published] / [discount_expire] — سطرها با Alpine اضافه می‌شوند.
     ارسال: فرم کلاسیک POST به seller.products.variant.store (تمام منطق قدیمی همانجا است) --}}
<div class="mx-auto max-w-7xl space-y-5"
     x-data="{
        rows: @js($rows),
        addRow() {
            this.rows.push({ id: null, attributes: {}, price: '', discount: '', cart_max: '', cart_min: '', stock: '', published: 1, discount_expire: '' });
        },
        removeRow(i) {
            this.rows.splice(i, 1);
        }
     }">

    {{-- سربرگ محصول --}}
    <div class="ap-card p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                    <x-admin-panel::icon name="layers" class="size-5" />
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-sm font-extrabold text-ink dark:text-night-ink">تنوع‌های قیمت — {{ $product->title }}</h1>
                    <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
                        قیمت و موجودی هر تنوع را مدیریت کنید
                        @if ($product->category?->commission)
                            — کمیسیون فروش این کالا: <span class="fa-digits font-bold text-amber-600 dark:text-amber-400">{{ $product->category->commission }}٪</span>
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('seller.products.index') }}" wire:navigate class="ap-btn-outline py-2! text-xs!">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به محصولات
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('seller.products.variant.store', $product) }}">
        @csrf

        {{-- سطرهای تنوع --}}
        <template x-for="(row, i) in rows" :key="i">
            <div class="ap-card mb-4 p-5" x-cloak>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[13px] font-extrabold text-ink dark:text-night-ink">
                        تنوع <span class="fa-digits" x-text="i + 1"></span>
                    </p>
                    <button type="button" class="flex min-h-9 items-center gap-1 rounded-lg px-2.5 py-1.5 text-[11px] font-bold text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-500/10"
                            x-on:click="removeRow(i)" x-show="rows.length > 1">
                        <x-admin-panel::icon name="trash-2" class="size-3.5" />
                        حذف سطر
                    </button>
                </div>

                @if ($product->isPhysical())
                    {{-- ویژگی‌ها (یک سلکت برای هر گروه — عین قدیمی) --}}
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($attributeGroups as $group)
                            <div>
                                <label class="mb-1.5 block text-xs font-bold" x-text="{{ \Illuminate\Support\Js::from($group->name) }}"></label>
                                <select class="ap-input py-2.5!" x-bind:name="'prices[' + i + '][attributes][]'">
                                    <option value="">انتخاب کنید</option>
                                    @foreach ($group->get_attributes as $attribute)
                                        <option value="{{ $attribute->id }}"
                                                x-text="{{ \Illuminate\Support\Js::from($attribute->name) }}"
                                                x-bind:selected="(row.attributes || []).includes({{ $attribute->id }})"></option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold">قیمت (تومان) <span class="text-red-500">*</span></label>
                        <input type="number" min="0" required class="ap-input py-2.5! fa-digits" x-model="row.price" x-bind:name="'prices[' + i + '][price]'">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold">تخفیف (٪)</label>
                        <input type="number" min="0" max="100" class="ap-input py-2.5! fa-digits" x-model="row.discount" x-bind:name="'prices[' + i + '][discount]'">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold">حداکثر در سبد</label>
                        <input type="number" min="1" class="ap-input py-2.5! fa-digits" x-model="row.cart_max" x-bind:name="'prices[' + i + '][cart_max]'">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold">حداقل در سبد</label>
                        <input type="number" min="1" class="ap-input py-2.5! fa-digits" x-model="row.cart_min" x-bind:name="'prices[' + i + '][cart_min]'">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold">موجودی <span class="text-red-500">*</span></label>
                        <input type="number" min="0" required class="ap-input py-2.5! fa-digits" x-model="row.stock" x-bind:name="'prices[' + i + '][stock]'">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold">وضعیت انتشار</label>
                        <select class="ap-input py-2.5!" x-model="row.published" x-bind:name="'prices[' + i + '][published]'">
                            <option value="1">منتشر شود</option>
                            <option value="0">پیش‌نویس</option>
                        </select>
                    </div>
                </div>
            </div>
        </template>

        <button type="button" class="ap-btn-outline w-full py-3! border-dashed!" x-on:click="addRow()">
            <x-admin-panel::icon name="plus" class="size-4" />
            افزودن تنوع
        </button>

        <div class="mt-5 flex flex-wrap items-center gap-2">
            <button type="submit" class="ap-btn-primary py-2.5!">
                <x-admin-panel::icon name="save" class="size-4" />
                ذخیرهٔ تنوع‌ها
            </button>
            <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">قیمت و موجودی هر تنوع اجباری است</p>
        </div>
    </form>
</div>
