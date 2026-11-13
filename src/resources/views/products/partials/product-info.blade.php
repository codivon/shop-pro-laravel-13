{{-- ستون اطلاعات محصول — مارکاپ سانیار product-meta --}}
{{-- مهم: ریشهٔ .product-info-block با replaceWith در show.js جایگزین می‌شود؛ تغییر ندهید --}}
<div class="col-lg-8 col-md-12 col-12 product-info-block">

    @if ($product->category)
        {{-- مسیر دسته‌بندی کوچک بالای عنوان --}}
        <div class="bread-crumb pb-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    @foreach ($product->category->parents() as $parent)
                        <li class="breadcrumb-item">
                            <a href="{{ route('front.products.category', ['category' => $parent]) }}"
                               class="font-14 text-muted">{{ $parent->title }}</a>
                        </li>
                    @endforeach
                    <li class="breadcrumb-item">
                        <a href="{{ route('front.products.category', ['category' => $product->category]) }}"
                           class="font-14 text-muted">{{ $product->category->title }}</a>
                    </li>
                </ol>
            </nav>
        </div>
    @endif

    <div class="product-meta-title">
        <div class="row align-items-baseline gy-2">
            <div class="col-lg-8">
                <h1 class="font-18 fw-bold mb-1">{{ $product->title }}</h1>
                @if ($product->title_en)
                    <p class="mb-0 mt-1 text-muted font-13" dir="ltr">{{ $product->title_en }}</p>
                @endif
            </div>
            <div class="col-lg-4">
                @if ($product->brand)
                    <a href="{{ route('front.brands.show', ['brand' => $product->brand]) }}"
                       class="text-lg-end d-block font-14 main-color-one-color fw-bold">
                        برند: {{ $product->brand->name }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="product-meta-feature mt-3">
        <div class="row gy-3">
            <div class="col-lg-8">
                @if ($product->specialSpecifications()->count())
                    <div class="product-meta-feature-items">
                        <h5 class="title font-16 mb-2">ویژگی های کالا</h5>
                        <ul class="navbar-nav">
                            @foreach ($product->specialSpecifications() as $specification)
                                <li class="nav item {{ $loop->index >= 3 ? 'product-params-more' : '' }}">
                                    <span>{{ $specification->name }}:</span>
                                    <strong>{{ $specification->pivot->value }}</strong>
                                </li>
                            @endforeach
                        </ul>
                        @if ($product->specialSpecifications()->count() > 3)
                            <a class="link-border product-params-more-handler" href="">
                                <span class="show-more">موارد بیشتر</span>
                                <span class="show-less">بستن</span>
                            </a>
                        @endif
                    </div>
                @endif

                @if ($product->short_description)
                    <div class="product-additional-info mt-2">
                        <div class="product-additional-item is-masked">
                            <p class="mb-0 font-13">{!! nl2br($product->short_description) !!}</p>
                            <a class="mask-handler link-border" href="">
                                <span class="show-more">مشاهده بیشتر</span>
                                <span class="show-less">مشاهده کمتر</span>
                            </a>
                            <div class="shadow-box"></div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="col-lg-4">
                <div class="product-meta-rating">
                    @if ($product->rating)
                        <div class="d-flex align-items-center justify-content-lg-end">
                            <div class="count-comment text-muted cursor-pointer" data-activate-tab="comments">
                                {{ $product->reviews_count }} دیدگاه
                            </div>
                            <div class="count-rating ms-2">
                                <span>({{ $product->reviews_count }}) {{ $product->rating }}</span>
                                <i class="bi bi-star-fill"></i>
                            </div>
                        </div>
                        @if ($product->comments()->accepted()->count())
                            <div class="d-flex align-items-center justify-content-lg-end mt-1">
                                <div class="count-comment text-muted cursor-pointer" data-activate-tab="questions">
                                    {{ $product->comments()->accepted()->count() }} پرسش و پاسخ
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
                @if ($product->suggestionCount())
                    <div class="d-flex align-items-center justify-content-lg-end mt-2">
                        <i class="bi bi-hand-thumbs-up-fill text-success"></i>
                        <p class="text-muted font-13 mb-0 ms-1">
                            <span>{{ $product->suggestionPercent() }}٪</span>
                            ({{ $product->suggestionCount() }}) نفر از خریداران این کالا را پیشنهاد کردن
                        </p>
                    </div>
                @endif
                <div class="product-meta-garanty justify-content-lg-end justify-content-start mt-2">
                    <i class="bi bi-shield-fill-check main-color-one-color"></i>
                    <span class="text-muted font-13"> گارانتی اصالت و سلامت فیزیکی کالا </span>
                </div>
            </div>
        </div>
    </div>

    @php
        $prevAttribute = null;
        $groups = [];
        $selectedAttributesIds = [];
        $groupIndex = 0;
    @endphp

    @foreach ($attributeGroups as $attributeGroup)
        @php
            $attributes = $product->get_attributes($attributeGroup, $prevAttribute, $groups, $selectedAttributesIds);

            // اگر گروه ویژگی قابل نمایش نباشد، رد شو
            if (!$attributes) continue;

            $isFirstGroup = ($groupIndex == 0);
            $hasSelectedInGroup = false;
            $currentSelectedAttrIds = $selectedAttributesIds;
        @endphp

        @if ($attributeGroup->type == 'color')
            {{-- انتخاب رنگ — چیپ‌های سانیار (ساختار js حفظ شده: ui-variant-shape/data-colorname) --}}
            <div class="product-meta-color mt-3 product-variants">
                <h5 class="font-16 mb-2">
                    {{ $attributeGroup->name }}:
                    @php
                        $selectedColor = $selected_price->get_attributes()->where('attribute_group_id', $attributeGroup->id)->first();
                    @endphp
                    <span class="main-color-one-color">{{ $selectedColor ? $selectedColor->name : '' }}</span>
                </h5>
                <div class="product-meta-color-items d-flex flex-wrap gap-2">
                    @foreach ($attributes as $attribute)
                        @php
                            // بررسی موجودی
                            $hasStock = $isFirstGroup
                                ? $product->hasAttributeStock($attribute)
                                : $product->hasAttributeStock($attribute, $currentSelectedAttrIds);

                            // بررسی انتخاب شده بودن
                            $isSelected = (bool) $selected_price->get_attributes()->find($attribute->id);

                            if ($isSelected) {
                                $hasSelectedInGroup = true;
                                $prevAttribute = $attribute;
                                $selectedAttributesIds[] = $attribute->id;
                            }
                        @endphp

                        <label class="js-c-ui-variant product-attribute ui-variant-color {{ $hasStock ? '' : 'unavailable' }} {{ $isSelected ? 'selected' : '' }}"
                               for="variant-color-{{ $attribute->id }}"
                               title="{{ $hasStock ? $attribute->name : $attribute->name . ' (ناموجود)' }}">
                            <span class="ui-variant-shape"
                                  data-colorname="{{ $attribute->name }}"
                                  style="background-color: {{ $attribute->value }}"></span>
                            <input type="radio"
                                   title="{{ $attribute->name }}"
                                   data-product="{{ $product->slug }}"
                                   value="{{ $attribute->id }}"
                                   name="attributes_group[{{ $loop->parent->iteration }}][]"
                                   id="variant-color-{{ $attribute->id }}"
                                   class="js-variant-selector variant-selector"
                                {{ $isSelected ? 'checked' : '' }}
                                {{ $hasStock ? '' : 'disabled' }}>
                            <span class="ui-variant-name font-14">{{ $attribute->name }}</span>
                            <span class="ui-variant-check @if($attribute->name == "سفید") ui-variant-check-white @endif"></span>
                        </label>
                    @endforeach
                </div>
            </div>

        @else
            {{-- سایر ویژگی‌ها (سایز/انتخابی) — چیپ‌های متنی سانیار --}}
            <div class="product-meta-color mt-3 product-variant product-variants">
                <h5 class="font-16 mb-2">
                    {{ $attributeGroup->name }}:
                    @php
                        /* فاز ۱۵۴ — نام تنوع انتخاب‌شده در جای خالی جلوی عنوان گروه نمایش
                           داده می‌شود (همان رفتار «رنگ: مشکی»)؛ اگر تنوعی از این گروه
                           انتخاب نشده بود، همان آیتم آخر که انتخاب خودکار بلید است نشان داده
                           می‌شود تا جلوی عنوان گروه خالی نماند. گارد null برای کالای
                           ناموجود که selected_price ندارد. */
                        $selectedGroupAttr = $selected_price
                            ? $selected_price->get_attributes()->where('attribute_group_id', $attributeGroup->id)->first()
                            : null;
                        if (!$selectedGroupAttr) {
                            $selectedGroupAttr = collect($attributes)->last();
                        }
                    @endphp
                    <span class="main-color-one-color" id="attributeGroup-{{ $attributeGroup->id }}">{{ $selectedGroupAttr ? $selectedGroupAttr->name : '' }}</span>
                </h5>
                <div class="product-meta-color-items d-flex flex-wrap gap-2 type-list">
                    @foreach ($attributes as $attribute)
                        @php
                            // بررسی موجودی
                            $hasStock = $isFirstGroup
                                ? $product->hasAttributeStock($attribute)
                                : $product->hasAttributeStock($attribute, $currentSelectedAttrIds);

                            // بررسی انتخاب شده بودن
                            $isSelected = (bool) $selected_price->get_attributes()->find($attribute->id);

                            if ($isSelected) {
                                $hasSelectedInGroup = true;
                                $prevAttribute = $attribute;
                                $selectedAttributesIds[] = $attribute->id;
                            }

                            // تنظیم انتخاب پیش‌فرض برای آخرین آیتم در صورت عدم انتخاب
                            if ($loop->last && !$isSelected && !$hasSelectedInGroup) {
                                $isSelected = true;
                                $hasSelectedInGroup = true;
                                $prevAttribute = $attribute;
                                $selectedAttributesIds[] = $attribute->id;
                            }
                        @endphp

                        <label class="js-c-ui-variant product-attribute ui-variant mb-0 {{ $hasStock ? '' : 'unavailable' }} {{ $isSelected ? 'selected' : '' }}"
                               for="attribute-{{ $loop->parent->iteration }}-{{ $attribute->id }}"
                               title="{{ $hasStock ? '' : 'ناموجود' }}">
                            <input data-product="{{ $product->slug }}"
                                   type="radio"
                                   value="{{ $attribute->id }}"
                                   name="attributes_group[{{ $loop->parent->iteration }}][]"
                                   class="variant-selector"
                                   id="attribute-{{ $loop->parent->iteration }}-{{ $attribute->id }}"
                                {{ $isSelected ? 'checked' : '' }}
                                {{ $hasStock ? '' : 'disabled' }}>
                            <div class="ui-variant--check {{ $attributeGroup->type == 'select' ? 'select' : '' }}">
                                <span class="font-14">{{ $attribute->name }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        @php
            $groups[] = $attributeGroup;
            $groupIndex++;
        @endphp
    @endforeach

    @php
        $prevAttribute = null;
        $groups = null;
        $selectedAttributesIds = [];
        $groupIndex = 0;
    @endphp

    @if ($product->sizeType)
        <div class="mt-3 size-guide d-flex align-items-center gap-2">
            <img src="{{ theme_asset('img/size.png') }}" width="25" alt="size">
            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#size-modal"
               class="mt-0 link--with-border-bottom font-14">راهنمای سایزبندی</a>
        </div>
    @endif

    {{-- باکس اقدام: فروشنده + قیمت + افزودن به سبد --}}
    @include('front::products.partials.product-info-sidebar')

</div>
