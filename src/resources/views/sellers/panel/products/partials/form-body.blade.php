{{-- ============================================================
   بدنهٔ مشترک فرم محصول فروشنده — سانیار (فاز H)
   استفاده در create.blade.php (بدون $product) و edit.blade.php (با $product)
   ورودی‌ها: $mode(create|edit), $product?, $copy_product?,
             $categories, $specTypes, $sizetypes, $attributeGroups, $brands, $abilityScores?
   قرارداد ثابت میزبان: نیم‌اسپیس‌های prices[i][…], download_files[i][…],
   specification_group[i][…], AbilityProductScore[i][…], sizes[g][size_id]
   ============================================================ --}}
@php
    $p        = $product ?? $copy_product;
    $is_edit  = ($mode ?? 'create') === 'edit';
    $ability  = $abilityScores ?? collect();
    $labels_csv = $p ? $p->getLabels() : '';
@endphp

<div class="saniar-form-wrap">
    <div class="row g-3">
        {{-- ناوبری تب‌ها --}}
        <div class="col-lg-3">
            <div class="content-box h-100">
                <div class="container-fluid">
                    <div class="nav nav-pills saniar-form-tab-nav flex-lg-column gap-1 py-3" role="tablist" aria-label="بخش‌های فرم محصول">
                        <button class="saniar-form-tab-link active" type="button" role="tab"
                                data-bs-toggle="pill" data-bs-target="#sfp-general" aria-selected="true">
                            <i class="bi bi-info-circle"></i><span>اطلاعات کلی</span>
                        </button>
                        <button class="saniar-form-tab-link sfp-physical-item" type="button" role="tab"
                                data-bs-toggle="pill" data-bs-target="#sfp-prices">
                            <i class="bi bi-cash-stack"></i><span>قیمت (تنوع)</span>
                        </button>
                        <button class="saniar-form-tab-link sfp-download-item" type="button" role="tab"
                                data-bs-toggle="pill" data-bs-target="#sfp-files">
                            <i class="bi bi-file-earmark-arrow-down"></i><span>فایل‌ها</span>
                        </button>
                        <button class="saniar-form-tab-link" type="button" role="tab"
                                data-bs-toggle="pill" data-bs-target="#sfp-images">
                            <i class="bi bi-images"></i><span>تصاویر</span>
                        </button>
                        <button class="saniar-form-tab-link" type="button" role="tab"
                                data-bs-toggle="pill" data-bs-target="#sfp-specs">
                            <i class="bi bi-list-check"></i><span>مشخصات</span>
                        </button>
                        <button class="saniar-form-tab-link" type="button" role="tab"
                                data-bs-toggle="pill" data-bs-target="#sfp-sizes">
                            <i class="bi bi-rulers"></i><span>سایزبندی</span>
                        </button>
                        <button class="saniar-form-tab-link" type="button" role="tab"
                                data-bs-toggle="pill" data-bs-target="#sfp-rating">
                            <i class="bi bi-star-half"></i><span>امتیاز ویژگی‌ها</span>
                        </button>
                        <button class="saniar-form-tab-link" type="button" role="tab"
                                data-bs-toggle="pill" data-bs-target="#sfp-seo">
                            <i class="bi bi-search"></i><span>سئو</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- محتوای تب‌ها --}}
        <div class="col-lg-9">
            <div class="tab-content">

                {{-- ============ اطلاعات کلی ============ --}}
                <div class="tab-pane fade show active" id="sfp-general" role="tabpanel">
                    <div class="content-box">
                        <div class="container-fluid">
                            <div class="title-panel mb-3">
                                <h2 class="font-16 mb-0"><i class="bi bi-info-circle main-color-one-color me-1"></i>اطلاعات کلی محصول</h2>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label font-13 fw-bold" for="sfp-title">عنوان محصول <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="sfp-title" name="title" required
                                           maxlength="191" placeholder="مثال: گوشی موبایل سامسونگ مدل A54"
                                           value="{{ $p->title ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-13 fw-bold" for="sfp-title-en">عنوان انگلیسی</label>
                                    <input type="text" class="form-control" id="sfp-title-en" name="title_en"
                                           maxlength="191" placeholder="Samsung Galaxy A54"
                                           value="{{ $p->title_en ?? '' }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-13 fw-bold" for="sfp-category">دسته‌بندی اصلی <span class="text-danger">*</span></label>
                                    <select class="form-select" id="sfp-category" name="category_id" required>
                                        <option value="">انتخاب کنید</option>
                                        @foreach ($categories as $category)
                                            @php $depth = $category->parents()->count(); @endphp
                                            <option value="{{ $category->id }}"
                                                {{ ($p && $p->category_id == $category->id) ? 'selected' : '' }}>
                                                {{ str_repeat('— ', $depth) }}{{ $category->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-13 fw-bold" for="sfp-type">نوع محصول <span class="text-danger">*</span></label>
                                    <select class="form-select" id="sfp-type" name="type">
                                        <option value="physical" {{ $p && $p->isPhysical() ? 'selected' : '' }}>محصول فیزیکی</option>
                                        <option value="download" {{ $p && $p->isDownload() ? 'selected' : '' }}>محصول دانلودی</option>
                                    </select>
                                </div>

                                <div class="col-md-4 sfp-physical-item">
                                    <label class="form-label font-13 fw-bold" for="sfp-brand">برند</label>
                                    <select class="form-select" id="sfp-brand" name="brand">
                                        <option value="">انتخاب کنید</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->name }}"
                                                {{ ($p && $p->brand && $p->brand->name == $brand->name) ? 'selected' : '' }}>{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 sfp-physical-item">
                                    <label class="form-label font-13 fw-bold" for="sfp-weight">وزن (گرم) <span class="text-danger sfp-req">*</span></label>
                                    <input type="number" class="form-control" id="sfp-weight" name="weight" min="0"
                                           placeholder="مثال: 350" value="{{ $p->weight ?? '' }}">
                                </div>
                                <div class="col-md-4 sfp-physical-item">
                                    <label class="form-label font-13 fw-bold" for="sfp-unit">واحد <span class="text-danger sfp-req">*</span></label>
                                    <input type="text" class="form-control" id="sfp-unit" name="unit"
                                           placeholder="مثال: عدد" value="{{ $p->unit ?? 'تعداد' }}">
                                </div>
                                <div class="col-md-4 sfp-physical-item">
                                    <label class="form-label font-13 fw-bold" for="sfp-nature">ماهیت کالا <span class="text-danger sfp-req">*</span></label>
                                    <select class="form-select" id="sfp-nature" name="shipping_nature">
                                        <option value="small" {{ $p && $p->small ? 'selected' : '' }}>کوچک</option>
                                        <option value="medium" {{ $p && $p->medium ? 'selected' : '' }}>متوسط</option>
                                        <option value="large" {{ $p && $p->large ? 'selected' : '' }}>بزرگ</option>
                                    </select>
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label font-13 fw-bold" for="sfp-categories">دسته‌بندی‌های دیگر</label>
                                    <select class="form-select" id="sfp-categories" name="categories[]" multiple size="5">
                                        @foreach ($categories as $category)
                                            @php $depth = $category->parents()->count(); @endphp
                                            <option value="{{ $category->id }}"
                                                {{ ($p && $p->categories()->find($category->id)) ? 'selected' : '' }}>
                                                {{ str_repeat('— ', $depth) }}{{ $category->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text font-12">با نگه‌داشتن Ctrl چند دسته را انتخاب کنید.</div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-13 fw-bold" for="sfp-labels-input">برچسب‌ها</label>
                                    <input type="text" class="form-control" id="sfp-labels-input"
                                           placeholder="تایپ کنید و Enter بزنید" autocomplete="off">
                                    <input type="hidden" name="labels" id="sfp-labels-value" value="{{ $labels_csv }}">
                                    <div class="saniar-tags-chips" id="sfp-labels-chips" aria-live="polite"></div>
                                    <div class="form-text font-12">برچسب جدید به‌صورت خودکار ساخته می‌شود.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label font-13 fw-bold" for="sfp-short-desc">توضیحات کوتاه</label>
                                    <textarea class="form-control" id="sfp-short-desc" name="short_description" rows="3"
                                              placeholder="خلاصه‌ای از محصول که در لیست‌ها نمایش داده می‌شود">{{ $p->short_description ?? '' }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label font-13 fw-bold" for="sfp-description">توضیحات کامل</label>
                                    <textarea class="form-control" id="sfp-description" name="description" rows="7"
                                              placeholder="توضیحات کامل محصول (متن یا HTML ساده)">{{ $p->description ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============ قیمت (تنوع) — فقط فیزیکی ============ --}}
                <div class="tab-pane fade sfp-physical-item" id="sfp-prices" role="tabpanel">
                    <div class="content-box">
                        <div class="container-fluid">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div class="title-panel mb-0">
                                    <h2 class="font-16 mb-0"><i class="bi bi-cash-stack main-color-one-color me-1"></i>تنوع‌ها و قیمت‌ها</h2>
                                </div>
                                <button type="button" class="btn btn-sm main-color-one-bg text-white rounded-3 font-13" id="sfp-add-price">
                                    <i class="bi bi-plus-lg ms-1"></i>افزودن تنوع
                                </button>
                            </div>

                            <div class="alert alert-light border font-13 d-flex gap-2" role="note">
                                <i class="bi bi-lightbulb text-warning mt-1"></i>
                                <span>برای هر ترکیب از ویژگی‌ها (رنگ، گارانتی و…) یک تنوع با قیمت و موجودی خودتان ثبت کنید. قیمت و موجودی الزامی‌اند.</span>
                            </div>

                            <div id="product-prices-div" class="d-grid gap-3"
                                 data-count="{{ $is_edit ? ($product->isPhysical() ? $product->prices()->where('seller_id', sellerID())->count() : 0) : ($copy_product && $copy_product->isPhysical() ? $copy_product->prices->count() : 0) }}">
                                @if ($p && $p->isPhysical())
                                    @if ($is_edit)
                                        @foreach ($product->prices()->where('seller_id', sellerID())->get() as $price)
                                            @include('front::sellers.panel.products.partials.prices-include', ['price' => $price, 'pi' => $loop->iteration])
                                        @endforeach
                                    @elseif ($copy_product)
                                        @foreach ($copy_product->prices as $price)
                                            @include('front::sellers.panel.products.partials.prices-include', ['price' => $price, 'pi' => $loop->iteration])
                                        @endforeach
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============ فایل‌ها — فقط دانلودی ============ --}}
                <div class="tab-pane fade sfp-download-item" id="sfp-files" role="tabpanel">
                    <div class="content-box">
                        <div class="container-fluid">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div class="title-panel mb-0">
                                    <h2 class="font-16 mb-0"><i class="bi bi-file-earmark-arrow-down main-color-one-color me-1"></i>فایل‌های قابل دانلود</h2>
                                </div>
                                <button type="button" class="btn btn-sm main-color-one-bg text-white rounded-3 font-13" id="sfp-add-file">
                                    <i class="bi bi-plus-lg ms-1"></i>افزودن فایل
                                </button>
                            </div>

                            <div id="product-files-area" class="d-grid gap-3"
                                 data-count="{{ $is_edit && $product->isDownload() ? $product->prices()->orderBy('ordering')->get()->filter(fn($pr) => $pr->file)->count() : 0 }}">
                                @if ($is_edit && $product->isDownload())
                                    @foreach ($product->prices()->orderBy('ordering')->get() as $price)
                                        @include('front::sellers.panel.products.partials.files-include', ['price' => $price, 'fi' => $loop->iteration])
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============ تصاویر ============ --}}
                <div class="tab-pane fade" id="sfp-images" role="tabpanel">
                    <div class="content-box">
                        <div class="container-fluid">
                            <div class="title-panel mb-3">
                                <h2 class="font-16 mb-0"><i class="bi bi-images main-color-one-color me-1"></i>تصاویر محصول</h2>
                            </div>

                            <label class="form-label font-13 fw-bold" for="sfp-gallery-input">گالری تصاویر</label>
                            <div class="saniar-upload-box">
                                <label class="saniar-upload-cta" for="sfp-gallery-input">
                                    <i class="bi bi-cloud-arrow-up"></i>
                                    <span class="fw-bold font-14">انتخاب تصاویر گالری</span>
                                    <span class="font-12 text-muted">jpg / png / webp — حداکثر ۱۰ مگابایت برای هر تصویر</span>
                                </label>
                                <input type="file" id="sfp-gallery-input" accept="image/*" multiple class="d-none">
                            </div>

                            <div class="saniar-gallery-grid" id="sfp-gallery-grid"
                                 data-store-url="{{ route('seller.products.image-store') }}"
                                 data-delete-url="{{ route('seller.products.image-delete') }}">
                                @if ($is_edit && $product->gallery()->count())
                                    @foreach ($product->gallery()->orderBy('ordering')->get() as $img)
                                        <div class="saniar-gallery-tile" data-name="{{ $img->image }}">
                                            <img src="{{ asset($img->image) }}" alt="{{ $product->image_alt ?: $product->title }}">
                                            <div class="saniar-gallery-tile-actions">
                                                <button type="button" class="btn tile-move" data-dir="up" title="جابه‌جایی به قبل" aria-label="جابه‌جایی به قبل"><i class="bi bi-arrow-right"></i></button>
                                                <button type="button" class="btn tile-remove" title="حذف تصویر" aria-label="حذف تصویر"><i class="bi bi-trash"></i></button>
                                                <button type="button" class="btn tile-move" data-dir="down" title="جابه‌جایی به بعد" aria-label="جابه‌جایی به بعد"><i class="bi bi-arrow-left"></i></button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <input type="hidden" name="images" id="sfp-images-input"
                                   value="{{ $is_edit ? $product->gallery()->orderBy('ordering')->pluck('image')->implode(',') : '' }}">

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label font-13 fw-bold" for="sfp-main-image">تصویر شاخص</label>
                                    <input type="file" class="form-control" id="sfp-main-image" name="image" accept="image/*">
                                    @if ($is_edit && $product->image)
                                        <div class="mt-2">
                                            <img src="{{ asset($product->image) }}" alt="{{ $product->image_alt ?: $product->title }}"
                                                 class="rounded-3" style="width:96px;height:96px;object-fit:cover;">
                                            <div class="form-text font-12">تصویر شاخص فعلی (با انتخاب فایل جدید جایگزین می‌شود).</div>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-13 fw-bold" for="sfp-image-alt">متن جایگزین تصویر (ALT)</label>
                                    <input type="text" class="form-control" id="sfp-image-alt" name="image_alt"
                                           placeholder="مثال: گوشی موبایل سامسونگ A54 رنگ مشکی"
                                           value="{{ $p->image_alt ?? '' }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============ مشخصات ============ --}}
                <div class="tab-pane fade" id="sfp-specs" role="tabpanel">
                    <div class="content-box" id="specifications-card">
                        <div class="container-fluid">
                            <div class="title-panel mb-3">
                                <h2 class="font-16 mb-0"><i class="bi bi-list-check main-color-one-color me-1"></i>مشخصات محصول</h2>
                            </div>

                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-md-7">
                                    <p class="font-13 text-muted mb-0">محصولات بر اساس «نوع مشخصات» با هم مقایسه می‌شوند؛ مثلاً محصولی با نوع «گوشی موبایل» فقط با محصولات هم‌نوع مقایسه می‌شود.</p>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label font-13 fw-bold" for="sfp-spec-type">نوع مشخصات</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="sfp-spec-type" name="spec_type"
                                               list="sfp-spec-type-list" placeholder="مثلا: گوشی موبایل"
                                               value="{{ $p && $p->specType ? $p->specType->name : '' }}">
                                        <datalist id="sfp-spec-type-list">
                                            @foreach ($specTypes as $spec_type)
                                                <option value="{{ $spec_type->name }}"></option>
                                            @endforeach
                                        </datalist>
                                        <button class="btn btn-outline-secondary font-13" type="button" id="sfp-load-spec-type"
                                                data-action="{{ route('seller.products.spec-type-data') }}">
                                            <i class="bi bi-arrow-down-circle"></i> بارگذاری
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div id="specifications-area" class="d-grid gap-3"
                                 data-group-count="{{ $p ? $p->specificationGroups->unique()->count() : 0 }}"
                                 data-spec-count="{{ $p ? $p->specifications->unique()->count() : 0 }}">
                                @if ($p)
                                    @foreach ($p->specificationGroups->unique() as $group)
                                        @php $gi = $loop->index; @endphp
                                        <div class="saniar-spec-group specification-group" data-group="{{ $gi }}">
                                            <div class="row g-2 align-items-center mb-2">
                                                <div class="col-auto">
                                                    <span class="badge rounded-pill main-color-one-bg text-white font-12">گروه {{ $loop->iteration }}</span>
                                                </div>
                                                <div class="col">
                                                    <input type="text" class="form-control group-input" required
                                                           name="specification_group[{{ $gi }}][name]"
                                                           placeholder="مثال: مشخصات کلی" value="{{ $group->name }}"
                                                           aria-label="نام گروه مشخصات">
                                                </div>
                                                <div class="col-auto">
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-3 remove-group" title="حذف گروه">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="all-specifications d-grid gap-2">
                                                @foreach ($p->specifications()->where('specification_group_id', $group->id)->get() as $specification)
                                                    @include('front::sellers.panel.products.partials.specification-row', [
                                                        'gi' => $gi,
                                                        'si' => $loop->iteration - 1,
                                                        'spec_name' => $specification->name,
                                                        'spec_value' => $specification->pivot->value,
                                                        'spec_special' => $specification->pivot->special,
                                                    ])
                                                @endforeach
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-success rounded-3 font-13 add-specifaction mt-2">
                                                <i class="bi bi-plus-lg ms-1"></i>افزودن مشخصات
                                            </button>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-3 font-13" id="sfp-add-spec-group">
                                    <i class="bi bi-plus-lg ms-1"></i>افزودن گروه مشخصات
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============ سایزبندی ============ --}}
                <div class="tab-pane fade" id="sfp-sizes" role="tabpanel">
                    <div class="content-box" id="sizes-card">
                        <div class="container-fluid">
                            <div class="title-panel mb-3">
                                <h2 class="font-16 mb-0"><i class="bi bi-rulers main-color-one-color me-1"></i>راهنمای سایزبندی</h2>
                            </div>

                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-md-7">
                                    <p class="font-13 text-muted mb-0">در این بخش می‌توانید برای محصول، راهنمای سایز (مثلاً جدول سایز لباس) وارد کنید.</p>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label font-13 fw-bold" for="size_type_id">انتخاب راهنمای سایز</label>
                                    <select id="size_type_id" name="size_type_id" class="form-select"
                                            data-values-url="{{ route('seller.products.size-type-values', ['sizetype' => 'SIZETYPE_ID']) }}">
                                        <option value="">انتخاب کنید</option>
                                        @foreach ($sizetypes as $sizetype)
                                            <option value="{{ $sizetype->id }}"
                                                {{ $p && $p->size_type_id == $sizetype->id ? 'selected' : '' }}>{{ $sizetype->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div id="sizes-area" class="d-grid gap-2 all-sizes"
                                 data-count="{{ $p && $p->sizetype ? $p->sizes->groupBy('pivot.group')->count() : 0 }}">
                                @if ($p && $p->sizetype)
                                    @foreach ($p->sizes->groupBy('pivot.group') as $sizes)
                                        @php $gKey = $sizes->first()->pivot->group; @endphp
                                        <div class="saniar-size-row single-value row g-2 align-items-center">
                                            <div class="col-auto">
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-3 remove-value" title="حذف ردیف">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="col">
                                                <div class="row g-2">
                                                    @foreach ($p->sizetype->sizes as $size)
                                                        @php
                                                            $value = $p->sizes()->where('size_id', $size->id)->where('group', $gKey)->first()->pivot->value ?? '';
                                                        @endphp
                                                        <div class="col-md-3 col-6">
                                                            <label class="form-label font-12 mb-1">{{ $size->title }}</label>
                                                            <input type="text" class="form-control" data-size-id="{{ $size->id }}"
                                                                   name="sizes[{{ $gKey }}][{{ $size->id }}]" value="{{ $value }}">
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <div class="text-center mt-3 {{ ($p && $p->sizetype) ? '' : 'd-none' }}" id="sfp-add-value-wrap">
                                <button type="button" class="btn btn-sm btn-outline-success rounded-3 font-13 add-value">
                                    <i class="bi bi-plus-lg ms-1"></i>افزودن مقدار جدید
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============ امتیاز ویژگی‌ها ============ --}}
                <div class="tab-pane fade" id="sfp-rating" role="tabpanel">
                    <div class="content-box">
                        <div class="container-fluid">
                            <div class="title-panel mb-3">
                                <h2 class="font-16 mb-0"><i class="bi bi-star-half main-color-one-color me-1"></i>امتیاز به ویژگی‌های محصول</h2>
                            </div>

                            <div class="alert alert-light border font-13 d-flex gap-2 mb-3" role="note">
                                <i class="bi bi-lightbulb text-warning mt-1"></i>
                                <span>عنوان ویژگی و امتیاز آن را وارد کنید (عدد بین ۰ تا ۵؛ مثلاً «کیفیت ساخت: 4.5»). این امتیازها در صفحهٔ محصول نمایش داده می‌شوند.</span>
                            </div>

                            <div id="AbilityProductScore-area" class="d-grid gap-2" data-count="{{ $ability->count() }}">
                                @foreach ($ability as $abilityScore)
                                    <div class="saniar-ability-row row g-2 align-items-center">
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" name="AbilityProductScore[{{ $loop->iteration }}][name]"
                                                   placeholder="مثال: کیفیت ساخت" value="{{ $abilityScore->name }}" aria-label="عنوان ویژگی">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" step="any" min="0" max="5" class="form-control"
                                                   name="AbilityProductScore[{{ $loop->iteration }}][value]"
                                                   placeholder="مثال: 4.5" value="{{ $abilityScore->value }}" aria-label="امتیاز">
                                        </div>
                                        <div class="col-md-2 col-auto">
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-3 remove-ability" title="حذف">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-3 font-13" id="sfp-add-ability">
                                    <i class="bi bi-plus-lg ms-1"></i>افزودن ویژگی جدید
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============ سئو ============ --}}
                <div class="tab-pane fade" id="sfp-seo" role="tabpanel">
                    <div class="content-box">
                        <div class="container-fluid">
                            <div class="title-panel mb-3">
                                <h2 class="font-16 mb-0"><i class="bi bi-search main-color-one-color me-1"></i>تنظیمات سئو</h2>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label font-13 fw-bold" for="sfp-meta-title">عنوان سئو</label>
                                    <input type="text" class="form-control" id="sfp-meta-title" name="meta_title"
                                           placeholder="در صورت خالی بودن، عنوان محصول استفاده می‌شود"
                                           value="{{ $p->meta_title ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-13 fw-bold" for="sfp-slug">آدرس (Slug)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="sfp-slug" name="slug"
                                               placeholder="در صورت خالی بودن از عنوان ساخته می‌شود"
                                               value="{{ $p->slug ?? '' }}">
                                        <button class="btn btn-outline-secondary font-13" type="button" id="sfp-generate-slug" title="ایجاد خودکار از عنوان">
                                            <i class="bi bi-magic"></i> خودکار
                                        </button>
                                    </div>
                                    <div class="form-text font-12">فقط حروف، عدد و خط تیره؛ در صورت تکراری بودن خطا می‌گیرید.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label font-13 fw-bold" for="sfp-meta-desc">توضیحات سئو</label>
                                    <textarea class="form-control" id="sfp-meta-desc" name="meta_description" rows="3"
                                              placeholder="توضیح کوتاه برای نتایج موتورهای جستجو">{{ $p->meta_description ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- مودال تأیید بارگذاری گروه‌های نوع مشخصات --}}
<div class="modal fade" id="specifications-modal" tabindex="-1" aria-hidden="true"
     aria-labelledby="specifications-modal-title">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-15" id="specifications-modal-title">بارگذاری گروه‌های آماده</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body font-14">
                گروه‌ها و مشخصاتِ از پیش تعریف‌شدهٔ این «نوع مشخصات» بارگذاری شوند؟
                <br>
                <span class="text-danger font-13">مشخصات واردشدهٔ فعلی حذف خواهند شد.</span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-3 font-14" data-bs-dismiss="modal">خیر</button>
                <button type="button" class="btn btn-danger rounded-3 font-14" id="add-spec-type-data" data-bs-dismiss="modal">بله، بارگذاری شود</button>
            </div>
        </div>
    </div>
</div>
