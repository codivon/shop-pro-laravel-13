<div>
    {{-- ================================================================
         فاز ۱۰۸ — «درج محصول» (پورت sellers-old/panel/products/create.blade.php)
         فرم کلاسیک multipart به seller.products.store — نام فیلدها عیناً فرم قدیمی
         تا StoreProductRequest/store() قدیمی بدون تغییر کار کند.
         ================================================================ --}}

    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
                <x-admin-panel::icon name="package" class="size-5 text-brand-600 dark:text-brand-400" />
                درج محصول
            </h1>
            <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">اطلاعات محصول‌تان را در این صفحه وارد کنید</p>
        </div>
        <a href="{{ route('seller.products.index') }}" wire:navigate
           class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به لیست
        </a>
    </div>

    @php $copy = $copy_product; @endphp

    <form id="sp-product-form" method="POST" action="{{ $storeUrl }}" enctype="multipart/form-data"
          data-redirect="{{ $indexUrl }}" data-image-store="{{ $imageStoreUrl }}" data-image-delete="{{ $imageDeleteUrl }}">

        @csrf

        {{-- ================= خطاهای اعتبارسنجی (سربرگ — فرم کلاسیک) ================= --}}
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-500/20 dark:bg-red-500/10" id="sp-form-errors">
                <h3 class="mb-2 flex items-center gap-1.5 text-xs font-extrabold text-red-700 dark:text-red-300">
                    <x-admin-panel::icon name="circle-alert" class="size-4" />
                    لطفاً خطاهای زیر را برطرف کنید:
                </h3>
                <ul class="list-inside list-disc space-y-1 text-[11px] leading-5 text-red-700 dark:text-red-300">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ================= بخش: اطلاعات پایه ================= --}}
        <section class="ap-card mb-4 overflow-hidden">
            <header class="border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="package" class="size-4 text-brand-600 dark:text-brand-400" />
                    اطلاعات کلی
                </h2>
            </header>
            <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">عنوان <span class="text-red-500">*</span></span>
                    <input type="text" name="title" value="{{ $copy ? $copy->title : old('title') }}" class="ap-input w-full">
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">عنوان انگلیسی</span>
                    <input type="text" name="title_en" value="{{ $copy ? $copy->title_en : old('title_en') }}" class="ap-input w-full">
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">دسته بندی اصلی <span class="text-red-500">*</span></span>
                    <select name="category_id" class="ap-input w-full">
                        <option value="">انتخاب کنید</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ ($copy && $copy->category_id == $category->id) ? 'selected' : '' }}>{{ $category->title }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نوع محصول <span class="text-red-500">*</span></span>
                    <select name="type" id="sp-product-type" class="ap-input w-full">
                        <option value="physical" {{ $copy && ($copy->isPhysical()) ? 'selected' : '' }}>محصول فیزیکی</option>
                        <option value="download" {{ $copy && ($copy->isDownload()) ? 'selected' : '' }}>محصول دانلودی</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">برند</span>
                    <select name="brand" class="ap-input w-full">
                        <option value="">انتخاب کنید</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->name }}" @if(@$copy->brand->name == $brand->name) selected @endif>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block sp-physical-item">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">وزن (گرم) <span class="text-red-500">*</span></span>
                    <input type="number" name="weight" value="{{ $copy ? $copy->weight : old('weight') }}" class="ap-input w-full fa-digits">
                </label>

                <label class="block sp-physical-item">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">واحد <span class="text-red-500">*</span></span>
                    <input type="text" name="unit" value="{{ $copy ? $copy->unit : (old('unit') ?: 'تعداد') }}" class="ap-input w-full">
                </label>

                <label class="block sp-physical-item">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">ماهیت کالا</span>
                    <select name="shipping_nature" class="ap-input w-full">
                        <option value="small" {{ $copy && ($copy->small) ? 'selected' : '' }}>کوچک</option>
                        <option value="medium" {{ $copy && ($copy->medium) ? 'selected' : '' }}>متوسط</option>
                        <option value="large" {{ $copy && ($copy->large) ? 'selected' : '' }}>بزرگ</option>
                    </select>
                </label>

                <label class="block md:col-span-2">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">دسته بندی ها</span>
                    <select name="categories[]" multiple size="6" class="ap-input h-auto w-full">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ ($copy && $copy->categories()->find($category->id)) ? 'selected' : '' }}>{{ $category->title }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">برچسب</span>
                    <input type="text" name="labels" value="{{ $copy ? $copy->getLabels() : '' }}" placeholder="برچسب‌ها را با کاما جدا کنید" class="ap-input w-full">
                </label>

                <label class="block md:col-span-2">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">توضیحات کوتاه</span>
                    <textarea name="short_description" rows="3" class="ap-input w-full">{{ $copy ? $copy->short_description : old('short_description') }}</textarea>
                </label>

                <label class="block md:col-span-2">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">توضیحات</span>
                    <textarea id="description" name="description" rows="6" class="ap-input w-full">{{ $copy ? $copy->description : old('description') }}</textarea>
                </label>
            </div>
        </section>

        {{-- ================= بخش: سئو ================= --}}
        <section class="ap-card mb-4 overflow-hidden">
            <header class="border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="search" class="size-4 text-brand-600 dark:text-brand-400" />
                    سئو
                </h2>
            </header>
            <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">عنوان سئو</span>
                    <input type="text" name="meta_title" value="{{ $copy ? $copy->meta_title : old('meta_title') }}" class="ap-input w-full">
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">url</span>
                    <input type="text" name="slug" value="{{ $copy ? $copy->slug : old('slug') }}" class="ap-input w-full" dir="ltr">
                </label>

                <label class="block md:col-span-2">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">توضیحات سئو</span>
                    <textarea name="meta_description" rows="3" class="ap-input w-full">{{ $copy ? $copy->meta_description : old('meta_description') }}</textarea>
                </label>

                <label class="block md:col-span-2">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">کلمات کلیدی</span>
                    <input type="text" name="tags" value="{{ $copy ? $copy->getTags : '' }}" placeholder="کلمات کلیدی را با کاما جدا کنید" class="ap-input w-full">
                </label>
            </div>
        </section>

        {{-- ================= بخش: قیمت (تنوع) — محصولات فیزیکی ================= --}}
        <section class="ap-card mb-4 overflow-hidden sp-physical-item">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="coins" class="size-4 text-brand-600 dark:text-brand-400" />
                    قیمت (تنوع)
                </h2>
                <button type="button" id="sp-add-price"
                        class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن قیمت
                </button>
            </header>
            <div class="p-4">
                <p class="mb-3 rounded-xl bg-surface-2 p-3 text-[11px] leading-5 text-ink-500 dark:bg-night-2 dark:text-night-ink-2">
                    قیمت و موجودی الزامی است. برای هر ترکیب ویژگی (رنگ/سایز و…) یک قیمت اضافه کنید.
                </p>
                <div id="sp-prices-area" class="space-y-4">
                    @if ($copy && $copy->isPhysical())
                        {{-- ردیف‌های قیمتِ محصول کپی‌شده — همان فیلدهای prices[i][…] فرم قدیمی --}}
                        @foreach ($copy->prices as $price)
                            <div class="sp-price-row rounded-xl border border-line p-4 dark:border-night-line">
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                    @foreach ($attributeGroups as $attributeGroup)
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">{{ $attributeGroup->name }}</span>
                                            <select class="ap-input w-full sp-price-attr" name="prices[{{ $loop->parent->iteration }}][attributes][]">
                                                <option value="">انتخاب کنید</option>
                                                @foreach ($attributeGroup->get_attributes as $attribute)
                                                    <option value="{{ $attribute->id }}" {{ $price->get_attributes()->find($attribute->id) ? 'selected' : '' }}>{{ $attribute->name }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @endforeach
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">قیمت <span class="text-red-500">*</span></span>
                                        <input type="number" class="ap-input w-full sp-price-input" name="prices[{{ $loop->iteration }}][price]" value="{{ $price->price() }}" data-unit="تومان" required>
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تخفیف</span>
                                        <input type="number" class="ap-input w-full sp-discount-input" name="prices[{{ $loop->iteration }}][discount]" value="{{ $price->discount }}" min="0" max="100" placeholder="%">
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">بیشترین تعداد مجاز در هر سفارش</span>
                                        <input type="number" class="ap-input w-full" name="prices[{{ $loop->iteration }}][cart_max]" value="{{ $price->cart_max }}" min="1">
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">کمترین تعداد مجاز در هر سفارش</span>
                                        <input type="number" class="ap-input w-full" name="prices[{{ $loop->iteration }}][cart_min]" value="{{ $price->cart_min }}" min="1">
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">موجودی محصول نزد شما <span class="text-red-500">*</span></span>
                                        <input type="number" class="ap-input w-full" name="prices[{{ $loop->iteration }}][stock]" value="{{ $price->stock }}" min="0" required>
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">قیمت نهایی</span>
                                        <input type="text" class="ap-input w-full sp-final-price" disabled>
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">وضعیت</span>
                                        <select class="ap-input w-full" name="prices[{{ $loop->iteration }}][published]">
                                            <option @if($price->published == 1) selected @endif value="1">نمایش</option>
                                            <option @if($price->published == 0) selected @endif value="0">عدم نمایش</option>
                                        </select>
                                    </label>
                                </div>
                                <input type="hidden" name="prices[{{ $loop->iteration }}][attributes_seller][]" value="{{ seller_info()->seller_id }}">
                                <div class="mt-3 flex justify-start border-t border-line/70 pt-3 dark:border-night-line/70">
                                    <button type="button" class="sp-remove-price inline-flex min-h-11 items-center gap-1 rounded-lg px-3 text-xs font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10">
                                        <x-admin-panel::icon name="trash-2" class="size-3.5" />
                                        حذف
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </section>

        {{-- ================= بخش: فایل — محصولات دانلودی ================= --}}
        <section class="ap-card mb-4 overflow-hidden hidden sp-download-item">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="file-down" class="size-4 text-brand-600 dark:text-brand-400" />
                    فایل
                </h2>
                <button type="button" id="sp-add-file"
                        class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن فایل
                </button>
            </header>
            <div class="p-4">
                <div id="sp-files-area" class="space-y-4"></div>
            </div>
        </section>

        {{-- ================= بخش: تصاویر ================= --}}
        <section class="ap-card mb-4 overflow-hidden">
            <header class="border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="images" class="size-4 text-brand-600 dark:text-brand-400" />
                    تصاویر
                </h2>
            </header>
            <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">
                        تصاویر محصول
                        <span class="block text-[11px] font-normal text-ink-400 dark:text-night-ink-3">
                            بهترین اندازه <span class="text-red-500">{{ $productGalleryImageSize }}</span> پیکسل میباشد — تصاویر بلافاصله آپلود و در انتها با فرم ثبت می‌شوند.
                        </span>
                    </label>
                    {{-- گالری — همان اندپوینت‌های قدیمی image-store / image-delete --}}
                    <div x-data="spGallery" data-store="{{ $imageStoreUrl }}" data-delete="{{ $imageDeleteUrl }}"
                         class="rounded-xl border border-dashed border-line p-4 dark:border-night-line">
                        <input type="file" accept="image/*" multiple
                               class="block w-full text-xs text-ink-500 dark:text-night-ink-2
                                      file:me-3 file:min-h-11 file:cursor-pointer file:rounded-lg file:border-0
                                      file:bg-surface-2 file:px-4 file:text-xs file:font-bold file:text-ink-700 dark:file:bg-night-3 dark:file:text-night-ink-1"
                               x-on:change="upload($event)">
                        <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6" x-show="items.length" x-cloak>
                            <template x-for="(item, index) in items" :key="item.name">
                                <div class="group relative overflow-hidden rounded-lg border border-line dark:border-night-line">
                                    <img :src="item.preview" alt="تصویر محصول" class="h-20 w-full object-cover">
                                    <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-black/60 p-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <button type="button" class="rounded p-1 text-white hover:text-emerald-300" x-on:click="move(index, -1)" aria-label="انتقال به عقب">
                                            <x-admin-panel::icon name="chevron-right" class="size-3.5" />
                                        </button>
                                        <button type="button" class="rounded p-1 text-white hover:text-red-400" x-on:click="remove(index)" aria-label="حذف تصویر">
                                            <x-admin-panel::icon name="x" class="size-3.5" />
                                        </button>
                                        <button type="button" class="rounded p-1 text-white hover:text-emerald-300" x-on:click="move(index, 1)" aria-label="انتقال به جلو">
                                            <x-admin-panel::icon name="chevron-left" class="size-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        {{-- فیلد images — مثل formData.append قدیمی (نام‌ها با کاما، به ترتیب نمایش) --}}
                        <input type="hidden" name="images" x-effect="$el.value = items.map(i => i.name).join(',')">
                    </div>
                </div>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تصویر شاخص</span>
                    <input type="file" accept="image/*" name="image"
                           class="block w-full text-xs text-ink-500 dark:text-night-ink-2
                                  file:me-3 file:min-h-11 file:cursor-pointer file:rounded-lg file:border-0
                                  file:bg-surface-2 file:px-4 file:text-xs file:font-bold file:text-ink-700 dark:file:bg-night-3 dark:file:text-night-ink-1">
                    <span class="mt-1 block text-[11px] text-ink-400 dark:text-night-ink-3">بهترین اندازه <span class="text-red-500">{{ $productImageSize }}</span> پیکسل میباشد.</span>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">متن جایگزین تصویر</span>
                    <input type="text" name="image_alt" value="{{ old('image_alt') }}" class="ap-input w-full">
                </label>
            </div>
        </section>

        {{-- ================= بخش: مشخصات ================= --}}
        <section class="ap-card mb-4 overflow-hidden">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="list" class="size-4 text-brand-600 dark:text-brand-400" />
                    مشخصات محصول
                </h2>
                <button type="button" id="sp-add-spec-group"
                        class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن گروه مشخصات
                </button>
            </header>
            <div class="p-4">
                <p class="mb-3 text-[11px] leading-5 text-ink-500 dark:text-night-ink-2">
                    در این بخش میتوانید مشخصات محصول را وارد کنید. دقت کنید که محصولات بر اساس نوع مشخصات با یکدیگر مقایسه میشوند. به عنوان مثال یک محصول با نوع مشخصات "گوشی موبایل" فقط با محصولات این نوع مقایسه میشود
                </p>
                <label class="mb-4 block md:w-1/2">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">نوع مشخصات <span class="text-red-500">*</span></span>
                    <input id="specifications_type" type="text" name="spec_type" list="sp-spec-types" placeholder="مثلا گوشی موبایل"
                           value="{{ ($copy && $copy->specType) ? $copy->specType->name : old('spec_type') }}" class="ap-input w-full">
                    <datalist id="sp-spec-types">
                        @foreach($specTypes as $specType)
                            <option value="{{ $specType->name }}"></option>
                        @endforeach
                    </datalist>
                </label>

                <div id="sp-spec-groups" class="space-y-4">
                    @if ($copy)
                        @foreach ($copy->specificationGroups->unique() as $group)
                            <div class="rounded-xl border border-line p-4 dark:border-night-line">
                                <div class="mb-3 flex items-center gap-2">
                                    <span class="text-xs font-bold text-ink-500 dark:text-night-ink-2">نام گروه</span>
                                    <input type="text" name="specification_group[{{ $loop->index }}][name]" placeholder="مثال: مشخصات کلی"
                                           value="{{ $group->name }}" required class="ap-input w-full">
                                    <button type="button" class="sp-remove-group inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10" aria-label="حذف گروه">
                                        <x-admin-panel::icon name="x" class="size-4" />
                                    </button>
                                </div>
                                <div class="space-y-2">
                                    @foreach($copy->specifications()->where('specification_group_id', $group->id)->get() as $specification)
                                        <div class="grid grid-cols-12 items-start gap-2 rounded-lg bg-surface-2 p-2 dark:bg-night-2">
                                            <label class="col-span-2 flex items-center justify-center gap-1 text-[10px] text-ink-500 dark:text-night-ink-2">
                                                <input type="checkbox" name="specification_group[{{ $loop->parent->index }}][specifications][{{ $loop->index }}][special]" {{ $specification->pivot->special ? 'checked' : '' }} class="size-4 accent-brand-600">
                                                ویژه
                                            </label>
                                            <input type="text" class="ap-input col-span-4" name="specification_group[{{ $loop->parent->index }}][specifications][{{ $loop->index }}][name]" placeholder="مثال: حافظه داخلی" value="{{ $specification->name }}" required>
                                            <textarea rows="1" class="ap-input col-span-5" name="specification_group[{{ $loop->parent->index }}][specifications][{{ $loop->index }}][value]" placeholder="مثال: 32 گیگابایت" required>{{ $specification->pivot->value }}</textarea>
                                            <button type="button" class="sp-remove-spec col-span-1 inline-flex min-h-11 items-center justify-center rounded-lg p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10" aria-label="حذف مشخصه">
                                                <x-admin-panel::icon name="x" class="size-4" />
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="sp-add-spec mt-3 inline-flex min-h-11 items-center gap-1 rounded-lg px-3 text-[11px] font-bold text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                                    <x-admin-panel::icon name="plus" class="size-3.5" />
                                    افزودن مشخصات
                                </button>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </section>

        {{-- ================= بخش: سایز بندی ================= --}}
        <section class="ap-card mb-4 overflow-hidden">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="ruler" class="size-4 text-brand-600 dark:text-brand-400" />
                    سایز بندی
                </h2>
                <button type="button" id="sp-add-size-group"
                        class="ap-btn-outline hidden min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن مقدار جدید
                </button>
            </header>
            <div class="p-4">
                <p class="mb-3 text-[11px] leading-5 text-ink-500 dark:text-night-ink-2">در این قسمت میتوانید برای محصول راهنمای سایز بندی وارد کنید.</p>
                <label class="mb-4 block md:w-1/2">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">انتخاب راهنمای سایز</span>
                    <select id="sp-size-type" name="size_type_id" class="ap-input w-full">
                        <option value="">انتخاب کنید</option>
                        @foreach ($sizetypes as $sizetype)
                            <option value="{{ $sizetype->id }}" {{ ($copy && $copy->size_type_id == $sizetype->id) ? 'selected' : '' }}>{{ $sizetype->title }}</option>
                        @endforeach
                    </select>
                </label>
                <div id="sp-sizes-area" class="space-y-3"></div>
            </div>
        </section>

        {{-- ================= بخش: امتیاز به ویژگی‌های محصول ================= --}}
        <section class="ap-card mb-4 overflow-hidden">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="star" class="size-4 text-brand-600 dark:text-brand-400" />
                    امتیاز به ویژگی های محصول
                </h2>
                <button type="button" id="sp-add-ability"
                        class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن ویژگی جدید
                </button>
            </header>
            <div class="p-4">
                <p class="mb-3 text-[11px] leading-5 text-ink-500 dark:text-night-ink-2">در این بخش میتوانید عنوان ویژگی و امتیاز ویژگی را وارد کنید.</p>
                <div id="sp-ability-area" class="space-y-2"></div>
            </div>
        </section>

        {{-- ================= فیلدهای مخفی + ارسال (عیناً فرم قدیمی) ================= --}}
        <input type="hidden" name="rounding_amount" value="no">
        <input type="hidden" name="rounding_type" value="default">

        <div class="mb-8 flex justify-center">
            <button type="submit" id="sp-submit-btn"
                    class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-6 text-sm font-bold">
                <x-admin-panel::icon name="plus" class="size-4" />
                ایجاد محصول
            </button>
        </div>
    </form>

    {{-- ================================================================
         قالب‌های ردیف‌های داینامیک — نام‌ها با placeholder و در JS شماره‌گذاری می‌شوند
         (همان الگوی prices-template/files-template/specification-template قدیمی)
         ================================================================ --}}

    {{-- قالب ردیف قیمت (تنوع) --}}
    <script type="text/template" id="sp-price-template">
        <div class="sp-price-row rounded-xl border border-line p-4 dark:border-night-line">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                @foreach ($attributeGroups as $attributeGroup)
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">{{ $attributeGroup->name }}</span>
                        <select class="ap-input w-full sp-price-attr" name="prices[__I__][attributes][]">
                            <option value="">انتخاب کنید</option>
                            @foreach ($attributeGroup->get_attributes as $attribute)
                                <option value="{{ $attribute->id }}">{{ $attribute->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">قیمت <span class="text-red-500">*</span></span>
                    <input type="number" class="ap-input w-full sp-price-input" name="prices[__I__][price]" data-unit="تومان" required>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تخفیف</span>
                    <input type="number" class="ap-input w-full sp-discount-input" name="prices[__I__][discount]" min="0" max="100" placeholder="%">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">بیشترین تعداد مجاز در هر سفارش</span>
                    <input type="number" class="ap-input w-full" name="prices[__I__][cart_max]" min="1">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">کمترین تعداد مجاز در هر سفارش</span>
                    <input type="number" class="ap-input w-full" name="prices[__I__][cart_min]" min="1">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">موجودی محصول نزد شما <span class="text-red-500">*</span></span>
                    <input type="number" class="ap-input w-full" name="prices[__I__][stock]" min="0" required>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">قیمت نهایی</span>
                    <input type="text" class="ap-input w-full sp-final-price" disabled>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">وضعیت</span>
                    <select class="ap-input w-full" name="prices[__I__][published]">
                        <option value="1">نمایش</option>
                        <option value="0">عدم نمایش</option>
                    </select>
                </label>
            </div>
            <input type="hidden" name="prices[__I__][attributes_seller][]" value="{{ seller_info()->seller_id }}">
            <div class="mt-3 flex justify-start border-t border-line/70 pt-3 dark:border-night-line/70">
                <button type="button" class="sp-remove-price inline-flex min-h-11 items-center gap-1 rounded-lg px-3 text-xs font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10">
                    <x-admin-panel::icon name="trash-2" class="size-3.5" />
                    حذف
                </button>
            </div>
        </div>
    </script>

    {{-- قالب ردیف فایل (دانلودی) --}}
    <script type="text/template" id="sp-file-template">
        <div class="sp-file-row rounded-xl border border-line p-4 dark:border-night-line">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <label class="block md:col-span-2">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">عنوان فایل <span class="text-red-500">*</span></span>
                    <input type="text" class="ap-input w-full" name="download_files[__I__][title]" required>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">انتخاب فایل <span class="text-red-500">*</span></span>
                    <input type="file" class="block w-full text-xs text-ink-500 dark:text-night-ink-2
                           file:me-2 file:min-h-11 file:cursor-pointer file:rounded-lg file:border-0
                           file:bg-surface-2 file:px-3 file:text-xs file:font-bold file:text-ink-700 dark:file:bg-night-3 dark:file:text-night-ink-1"
                           name="download_files[__I__][file]" required>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">قیمت <span class="text-red-500">*</span></span>
                    <input type="number" class="ap-input w-full" name="download_files[__I__][price]" min="0" required>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">تخفیف</span>
                    <input type="number" step="any" class="ap-input w-full" name="download_files[__I__][discount]" min="0" max="100">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">وضعیت <span class="text-red-500">*</span></span>
                    <select name="download_files[__I__][status]" class="ap-input w-full">
                        <option value="active">فعال</option>
                        <option value="inactive">غیر فعال</option>
                    </select>
                </label>
            </div>
            <div class="mt-3 flex justify-start border-t border-line/70 pt-3 dark:border-night-line/70">
                <button type="button" class="sp-remove-file inline-flex min-h-11 items-center gap-1 rounded-lg px-3 text-xs font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10">
                    <x-admin-panel::icon name="trash-2" class="size-3.5" />
                    حذف
                </button>
            </div>
        </div>
    </script>

    {{-- قالب گروه مشخصات --}}
    <script type="text/template" id="sp-spec-group-template">
        <div class="sp-spec-group rounded-xl border border-line p-4 dark:border-night-line" data-group="__G__">
            <div class="mb-3 flex items-center gap-2">
                <span class="text-xs font-bold text-ink-500 dark:text-night-ink-2">نام گروه</span>
                <input type="text" class="ap-input w-full" name="specification_group[__G__][name]" placeholder="مثال: مشخصات کلی" required>
                <button type="button" class="sp-remove-group inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10" aria-label="حذف گروه">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>
            <div class="sp-spec-rows space-y-2"></div>
            <button type="button" class="sp-add-spec mt-3 inline-flex min-h-11 items-center gap-1 rounded-lg px-3 text-[11px] font-bold text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                <x-admin-panel::icon name="plus" class="size-3.5" />
                افزودن مشخصات
            </button>
        </div>
    </script>

    {{-- قالب ردیف مشخصه --}}
    <script type="text/template" id="sp-spec-row-template">
        <div class="grid grid-cols-12 items-start gap-2 rounded-lg bg-surface-2 p-2 dark:bg-night-2">
            <label class="col-span-2 flex items-center justify-center gap-1 text-[10px] text-ink-500 dark:text-night-ink-2">
                <input type="checkbox" name="specification_group[__G__][specifications][__S__][special]" class="size-4 accent-brand-600">
                ویژه
            </label>
            <input type="text" class="ap-input col-span-4" name="specification_group[__G__][specifications][__S__][name]" placeholder="مثال: حافظه داخلی" required>
            <textarea rows="1" class="ap-input col-span-5" name="specification_group[__G__][specifications][__S__][value]" placeholder="مثال: 32 گیگابایت" required></textarea>
            <button type="button" class="sp-remove-spec col-span-1 inline-flex min-h-11 items-center justify-center rounded-lg p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10" aria-label="حذف مشخصه">
                <x-admin-panel::icon name="x" class="size-4" />
            </button>
        </div>
    </script>

    {{-- قالب ردیف امتیاز ویژگی --}}
    <script type="text/template" id="sp-ability-template">
        <div class="sp-ability-row grid grid-cols-12 items-start gap-2 rounded-lg bg-surface-2 p-2 dark:bg-night-2">
            <label class="col-span-5 block">
                <span class="mb-1 block text-[10px] font-semibold text-ink-500 dark:text-night-ink-2">عنوان</span>
                <input type="text" class="ap-input w-full" name="AbilityProductScore[__A__][name]" placeholder="مثال: کیفیت ساخت" required>
            </label>
            <label class="col-span-5 block">
                <span class="mb-1 block text-[10px] font-semibold text-ink-500 dark:text-night-ink-2">امتیاز</span>
                <input type="text" class="ap-input w-full" name="AbilityProductScore[__A__][value]" max="5" min="0" placeholder="مثال: 4.5" required>
            </label>
            <button type="button" class="sp-remove-ability col-span-2 mt-6 inline-flex min-h-11 items-center justify-center rounded-lg p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10" aria-label="حذف">
                <x-admin-panel::icon name="x" class="size-4" />
            </button>
        </div>
    </script>

    {{-- ================= منطق فرم — پورت all.js/create.js قدیمی با JS خالص ================= --}}
    <script>
        (function () {
            'use strict';

            var form = document.getElementById('sp-product-form');
            if (!form) return;

            /* ---------- شمارنده‌ها (پورت priceCount/groupCount/… قدیمی) ---------- */
            var priceCount = {{ ($copy && $copy->isPhysical()) ? $copy->prices->count() : 0 }};
            var filesCount = 0;
            var groupCount = {{ ($copy) ? $copy->specificationGroups->unique()->count() : 0 }};
            var specCount = {{ ($copy) ? $copy->specifications->unique()->count() : 0 }};
            var abilityCount = 0;

            /* دادهٔ راهنمای سایزها — به‌جای اندپوینت ادمین قدیمی (برای فروشنده باز نبود) */
            {{-- فاز ۱۰۸ — ساخت خروجی جیسون در بلاک پی‌اچ‌پی: Matcher دستورهای Blade روی
                 پرانتزهای تو در توِ کلوژرهای چندخطی داخل دستور جیسون لنگ می‌زند (شمارش زوج پرانتزها) --}}
            @php
                $sizeTypesPayload = $sizetypes->map(function ($st) {
                    return [
                        'id' => $st->id,
                        'sizes' => $st->sizes->map(function ($s) {
                            return ['id' => $s->id, 'title' => $s->title];
                        })->all(),
                    ];
                })->all();
            @endphp
            var sizeTypes = @json($sizeTypesPayload);
            var sizeGroup = 0;

            function replaceAll(str, find, replace) {
                return str.split(find).join(replace);
            }

            /* ---------- نوع محصول: نمایش/مخفی بخش‌ها (پورت product-type change) ---------- */
            var typeSelect = document.getElementById('sp-product-type');

            function toggleTypeSections() {
                var isPhysical = typeSelect.value === 'physical';
                document.querySelectorAll('.sp-physical-item').forEach(function (el) {
                    el.classList.toggle('hidden', !isPhysical);
                });
                document.querySelectorAll('.sp-download-item').forEach(function (el) {
                    el.classList.toggle('hidden', isPhysical);
                });
            }

            typeSelect.addEventListener('change', toggleTypeSections);
            toggleTypeSections();

            /* ---------- ردیف قیمت (پورت addProductPrice) ---------- */
            var priceTemplate = document.getElementById('sp-price-template').innerHTML;
            var pricesArea = document.getElementById('sp-prices-area');

            function addPriceRow() {
                var html = replaceAll(priceTemplate, '__I__', String(++priceCount));
                pricesArea.insertAdjacentHTML('beforeend', html);
            }

            document.getElementById('sp-add-price').addEventListener('click', addPriceRow);
            pricesArea.addEventListener('click', function (e) {
                var btn = e.target.closest('.sp-remove-price');
                if (btn) btn.closest('.sp-price-row').remove();
            });
            pricesArea.addEventListener('input', function (e) {
                var row = e.target.closest('.sp-price-row');
                if (!row) return;
                var price = parseFloat(row.querySelector('.sp-price-input').value) || 0;
                var discount = parseFloat(row.querySelector('.sp-discount-input').value) || 0;
                var finalPrice = price - price * (discount / 100);
                finalPrice = Math.round(finalPrice * 100) / 100;
                row.querySelector('.sp-final-price').value = number_format(finalPrice) + ' تومان';
            });
            if (priceCount === 0) addPriceRow();

            /* ---------- ردیف فایل (پورت addProductFile) ---------- */
            var fileTemplate = document.getElementById('sp-file-template').innerHTML;
            var filesArea = document.getElementById('sp-files-area');

            function addFileRow() {
                var html = replaceAll(fileTemplate, '__I__', String(++filesCount));
                filesArea.insertAdjacentHTML('beforeend', html);
            }

            document.getElementById('sp-add-file').addEventListener('click', addFileRow);
            filesArea.addEventListener('click', function (e) {
                var btn = e.target.closest('.sp-remove-file');
                if (btn) btn.closest('.sp-file-row').remove();
            });

            /* ---------- گروه‌ها و مشخصات (پورت add-product-specification-group) ---------- */
            var groupTemplate = document.getElementById('sp-spec-group-template').innerHTML;
            var specRowTemplate = document.getElementById('sp-spec-row-template').innerHTML;
            var specGroupsArea = document.getElementById('sp-spec-groups');

            document.getElementById('sp-add-spec-group').addEventListener('click', function () {
                var html = replaceAll(replaceAll(groupTemplate, '__G__', String(++groupCount)), '__S__', String(++specCount));
                specGroupsArea.insertAdjacentHTML('beforeend', html);
            });

            specGroupsArea.addEventListener('click', function (e) {
                var addBtn = e.target.closest('.sp-add-spec');
                if (addBtn) {
                    var group = addBtn.closest('.sp-spec-group');
                    var html = replaceAll(replaceAll(specRowTemplate, '__G__', group.dataset.group), '__S__', String(++specCount));
                    group.querySelector('.sp-spec-rows').insertAdjacentHTML('beforeend', html);
                    return;
                }
                var removeSpec = e.target.closest('.sp-remove-spec');
                if (removeSpec) {
                    removeSpec.closest('.grid').remove();
                    return;
                }
                var removeGroup = e.target.closest('.sp-remove-group');
                if (removeGroup) removeGroup.closest('.sp-spec-group').remove();
            });

            /* ---------- سایز بندی — رندر سمت کلاینت از دادهٔ سراسری ---------- */
            var sizeTypeSelect = document.getElementById('sp-size-type');
            var sizesArea = document.getElementById('sp-sizes-area');
            var addSizeGroupBtn = document.getElementById('sp-add-size-group');

            function sizeInput(sizeId, group, value) {
                var input = document.createElement('input');
                input.type = 'text';
                input.className = 'ap-input w-full';
                input.dataset.sizeId = sizeId;
                input.value = value || '';
                input.setAttribute('name', 'sizes[' + group + '][' + sizeId + ']');
                input.setAttribute('aria-label', 'مقدار سایز');
                return input;
            }

            function renderSizeGroup(group, values) {
                var st = sizeTypes.find(function (item) { return Number(item.id) === Number(sizeTypeSelect.value); });
                if (!st) return;

                var wrapper = document.createElement('div');
                wrapper.className = 'sp-size-row flex items-end gap-2 rounded-xl border border-line p-3 dark:border-night-line';

                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'sp-remove-size inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10';
                removeBtn.setAttribute('aria-label', 'حذف مقدار');
                removeBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
                wrapper.appendChild(removeBtn);

                var grid = document.createElement('div');
                grid.className = 'grid flex-1 grid-cols-2 gap-2 md:grid-cols-4';
                st.sizes.forEach(function (size) {
                    var cell = document.createElement('label');
                    cell.className = 'block';
                    var label = document.createElement('span');
                    label.className = 'mb-1 block text-[10px] font-semibold text-ink-500 dark:text-night-ink-2';
                    label.textContent = size.title;
                    cell.appendChild(label);
                    cell.appendChild(sizeInput(size.id, group, values ? values[size.id] : ''));
                    grid.appendChild(cell);
                });
                wrapper.appendChild(grid);
                sizesArea.appendChild(wrapper);
            }

            sizeTypeSelect.addEventListener('change', function () {
                sizesArea.innerHTML = '';
                sizeGroup = 0;
                if (!sizeTypeSelect.value) {
                    addSizeGroupBtn.classList.add('hidden');
                    return;
                }
                addSizeGroupBtn.classList.remove('hidden');
                renderSizeGroup(sizeGroup);
            });

            addSizeGroupBtn.addEventListener('click', function () {
                renderSizeGroup(++sizeGroup);
            });

            sizesArea.addEventListener('click', function (e) {
                var btn = e.target.closest('.sp-remove-size');
                if (btn) btn.closest('.sp-size-row').remove();
            });

            if (sizeTypeSelect.value) {
                addSizeGroupBtn.classList.remove('hidden');
            }

            /* ---------- امتیاز ویژگی‌ها (پورت AbilityProductScore) ---------- */
            var abilityTemplate = document.getElementById('sp-ability-template').innerHTML;
            var abilityArea = document.getElementById('sp-ability-area');

            document.getElementById('sp-add-ability').addEventListener('click', function () {
                abilityArea.insertAdjacentHTML('beforeend', replaceAll(abilityTemplate, '__A__', String(abilityCount++)));
            });

            abilityArea.addEventListener('click', function (e) {
                var btn = e.target.closest('.sp-remove-ability');
                if (btn) btn.closest('.sp-ability-row').remove();
            });

            /* ---------- helper: فرمت عدد (پورت number_format قدیمی) ---------- */
            function number_format(num) {
                return String(num).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            /* ---------- درایور گالری (Alpine) — همان اندپوینت‌های قدیمی image-store/delete ---------- */
            if (!window.spGalleryRegistered) {
                window.spGalleryRegistered = true;
                document.addEventListener('alpine:init', function () {
                    Alpine.data('spGallery', function () {
                        return {
                            items: [],
                            busy: 0,
                            init: function () {
                                /* گالری موجود فقط در ویرایش از قبل پر می‌شود (مثل mockImages قدیم) */
                            },
                            upload: function (event) {
                                var self = this;
                                var files = Array.from(event.target.files || []);
                                if (!files.length) return;

                                var storeUrl = this.$el.closest('[x-data]').dataset.store;

                                files.forEach(function (file) {
                                    var fd = new FormData();
                                    fd.append('file', file);
                                    fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                                    var preview = URL.createObjectURL(file);
                                    self.busy++;

                                    fetch(storeUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } })
                                        .then(function (res) { return res.json(); })
                                        .then(function (data) {
                                            self.items.push({ name: data.imagename, preview: preview });
                                        })
                                        .catch(function () {
                                            window.dispatchEvent(new CustomEvent('sp-toast', { detail: { message: 'خطایی در بارگذاری فایل رخ داده است', type: 'error' } }));
                                        })
                                        .finally(function () { self.busy--; });

                                    event.target.value = '';
                                });
                            },
                            remove: function (index) {
                                var self = this;
                                var item = this.items[index];
                                if (!item) return;

                                var deleteUrl = this.$el.closest('[x-data]').dataset.delete;
                                var fd = new FormData();
                                fd.append('filename', item.name);
                                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                                fetch(deleteUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } })
                                    .finally(function () {
                                        self.items.splice(index, 1);
                                    });
                            },
                            move: function (index, dir) {
                                var target = index + dir;
                                if (target < 0 || target >= this.items.length) return;
                                var arr = this.items;
                                var tmp = arr[index];
                                arr[index] = arr[target];
                                arr[target] = tmp;
                                this.items = arr.slice();
                            },
                        };
                    });
                });
            }

            /* ---------- submit — پورت AJAX قدیمی: ۴۲۲ → نمایش خطا، success → redirect ---------- */
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var submitBtn = document.getElementById('sp-submit-btn');
                submitBtn.disabled = true;

                var formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).then(function (res) {
                    if (res.status === 422) {
                        return res.json().then(function (data) { return { ok: false, errors: data.errors || {} }; });
                    }
                    if (res.ok) {
                        return { ok: true };
                    }
                    return { ok: false, errors: { 'general': ['خطایی در ذخیرهٔ محصول رخ داد. دوباره تلاش کنید.'] } };
                }).then(function (result) {
                    if (result.ok) {
                        /* همان redirect قدیمی: window.location.href = BASE_URL + '/products' */
                        window.location.href = form.dataset.redirect;
                        return;
                    }
                    submitBtn.disabled = false;
                    showFormErrors(result.errors);
                }).catch(function () {
                    submitBtn.disabled = false;
                    /* بدون JS شبکه — فرم کلاسیک fallback نمی‌شود؛ پیام عمومی */
                    showFormErrors({ 'general': ['خطای شبکه — دوباره تلاش کنید.'] });
                });
            });

            function showFormErrors(errors) {
                var old = document.getElementById('sp-form-errors');
                if (old) old.remove();

                var box = document.createElement('div');
                box.className = 'mb-4 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-500/20 dark:bg-red-500/10';
                box.id = 'sp-form-errors';

                var html = '<h3 class="mb-2 text-xs font-extrabold text-red-700 dark:text-red-300">لطفاً خطاهای زیر را برطرف کنید:</h3><ul class="list-inside list-disc space-y-1 text-[11px] leading-5 text-red-700 dark:text-red-300">';
                Object.keys(errors).forEach(function (key) {
                    errors[key].forEach(function (msg) { html += '<li>' + msg + '</li>'; });
                });
                html += '</ul>';
                box.innerHTML = html;

                form.prepend(box);
                box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        })();
    </script>
</div>
