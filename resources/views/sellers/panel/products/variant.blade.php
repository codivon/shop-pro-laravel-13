{{-- فاز ۱۱۹ — مدیریت تنوع‌های قیمت محصول با «ظاهر پنل مدیریت» (سندباکس ادمین:
     فرم تنوع با چیپ‌های ویژگی، کارت رَوند با بَج «تنوع N»، انقضای تخفیف جلالی و
     «قیمت نهایی»). منطق ارسال دست‌نخورده ماند: فرم کلاسیک POST به
     seller.products.variant.store با فیلدهای عین قدیمی:
     prices[i][attributes][] / [price] / [discount] / [discount_expire] /
     [cart_max] / [cart_min] / [stock] / [published] --}}
<div class="mx-auto max-w-7xl space-y-5"
     x-data="{
        rows: @js($rows),
        attrGroups: @js($attrGroupsJs),
        addRow() {
            this.rows.push({ id: null, attributes: [], price: '', discount: '', cart_max: '', cart_min: '', stock: '', published: 1, discount_expire: '' });
        },
        removeRow(i) {
            this.rows.splice(i, 1);
        },
        isPicked(row, groupId, attrId) {
            return (row.attributes || []).includes(attrId);
        },
        toggleAttr(row, groupId, attrId) {
            if (! Array.isArray(row.attributes)) row.attributes = [];
            const idx = row.attributes.indexOf(attrId);
            if (idx >= 0) {
                row.attributes.splice(idx, 1);
                return;
            }
            // تک‌انتخاب در هر گروه (رفتار pickAttribute ادمین): مقدار قبلیِ همان گروه حذف می‌شود
            const group = this.attrGroups[String(groupId)] || this.attrGroups[groupId];
            if (group) {
                group.attrs.forEach((a) => {
                    const gi = row.attributes.indexOf(a.id);
                    if (gi >= 0) row.attributes.splice(gi, 1);
                });
            }
            row.attributes.push(attrId);
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

        {{-- سطرهای تنوع — همان کارت‌های فرم ادمین --}}
        <template x-for="(row, i) in rows" :key="i">
            <div class="mb-4 rounded-2xl border border-line bg-surface-2 p-4 dark:border-night-line dark:bg-night-3/60"
                 x-data="apFinalPrice()" x-init="init(i)" x-cloak>

                <div class="mb-4 flex items-center justify-between">
                    <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                        تنوع <span class="fa-digits" x-text="ApFormat.faDigits(String(i + 1))"></span>
                        <span x-show="row.id" class="fa-digits opacity-60" x-text="'#' + row.id"></span>
                    </span>
                    <button type="button" x-on:click="removeRow(i)" x-show="rows.length > 1"
                            class="rounded-lg p-1.5 text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-500/10"
                            title="حذف تنوع" aria-label="حذف تنوع">
                        <x-admin-panel::icon name="trash-2" class="size-4" />
                    </button>
                </div>

                {{-- ویژگی‌های انتخاب‌شده → hidden inputs (همان prices[i][attributes][] قدیمی) --}}
                <template x-for="attrId in (row.attributes || [])" :key="'hid-' + i + '-' + attrId">
                    <input type="hidden" x-bind:name="'prices[' + i + '][attributes][]'" x-bind:value="attrId">
                </template>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">قیمت (تومان) <span class="text-red-500">*</span></label>
                        <input type="number" min="0" required
                               x-model="row.price" x-bind:name="'prices[' + i + '][price]'"
                               data-price-input x-on:input="calc()"
                               class="ap-input fa-digits">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">موجودی <span class="text-red-500">*</span></label>
                        <input type="number" min="0" required
                               x-model="row.stock" x-bind:name="'prices[' + i + '][stock]'"
                               class="ap-input fa-digits">
                    </div>
                </div>

                @if ($product->isPhysical())
                    {{-- ویژگی‌های این تنوع — چیپ‌های ویژگی مثل فرم ادمین --}}
                    <div class="mt-4 space-y-3">
                        <p class="text-xs font-bold text-ink-2 dark:text-night-ink-2">ویژگی‌های این تنوع:</p>
                        @foreach ($attributeGroups as $group)
                            @if ($group->get_attributes->count())
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="min-w-24 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">{{ $group->name }}:</span>
                                    @foreach ($group->get_attributes as $attribute)
                                        <button type="button"
                                                x-on:click="toggleAttr(row, {{ $group->id }}, {{ (int) $attribute->id }})"
                                                class="group flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold transition-all"
                                                x-bind:class="isPicked(row, {{ $group->id }}, {{ (int) $attribute->id }})
                                                                ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-300'
                                                                : 'border-line bg-surface text-ink-2 hover:border-brand-300 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2'">
                                            @if ($group->type === 'color')
                                                <span class="size-3.5 rounded-full border border-black/10"
                                                      x-bind:style="'background: ' + {{ \Illuminate\Support\Js::from($attribute->value) }}"></span>
                                            @endif
                                            {{ $attribute->name }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-5">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">تخفیف (٪)</label>
                        <input type="number" min="0" max="100"
                               x-model="row.discount" x-bind:name="'prices[' + i + '][discount]'"
                               data-discount-input x-on:input="calc()"
                               class="ap-input fa-digits">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">انقضای تخفیف</label>
                        <input type="text" autocomplete="off"
                               x-model="row.discount_expire" x-bind:name="'prices[' + i + '][discount_expire]'"
                               data-jdp placeholder="۱۴۰۴/۰۱/۰۱ ۱۲:۰۰"
                               class="ap-input fa-digits text-center">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">حداکثر خرید</label>
                        <input type="number" min="0"
                               x-model="row.cart_max" x-bind:name="'prices[' + i + '][cart_max]'"
                               class="ap-input fa-digits">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">حداقل خرید</label>
                        <input type="number" min="0"
                               x-model="row.cart_min" x-bind:name="'prices[' + i + '][cart_min]'"
                               class="ap-input fa-digits">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">وضعیت نمایش</label>
                        <select x-model="row.published" x-bind:name="'prices[' + i + '][published]'" class="ap-input">
                            <option value="1">نمایش</option>
                            <option value="0">عدم نمایش</option>
                        </select>
                    </div>
                </div>

                <p class="mt-3 rounded-xl bg-brand-50 px-3.5 py-2 text-xs font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                    قیمت نهایی: <span class="fa-digits" data-final-price>—</span> تومان
                </p>
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
