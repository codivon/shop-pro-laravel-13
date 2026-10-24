{{-- بلاک اطلاعات محصول سانیار — $product, $selected_price, $attributeGroups, $get_stores (AJAX-reload)
     این پارشال با تغییر واریانت از GET /product/{slug}/prices جایگزین می‌شود؛
     ریشه باید .product-info-block با کلاس‌های ساختاری ثابت باشد --}}
@php
    $prevAttribute = null;
    $groups = [];
    $selectedAttributesIds = [];
    $groupIndex = 0;
@endphp

<div class="col-lg-8 product-info-block">
    <section>
        {{-- عنوان + برند (داینامیک) --}}
        <div class="product-meta-title">
            <div class="row align-items-baseline gy-3">
                <div class="col-lg-8">
                    <h1 class="font-16 mb-0" id="saniar-product-title">{{ $product->title }}</h1>
                    @if($product->title_en)
                        <p class="mb-0 mt-2 text-muted" dir="ltr" style="text-align:right;">{{ $product->title_en }}</p>
                    @endif
                </div>
                <div class="col-lg-4">
                    @if ($product->brand)
                        <a href="{{ route('front.brands.show', ['brand' => $product->brand]) }}" class="text-lg-end d-block text-decoration-none">
                            @if($product->brand->image)
                                <img src="{{ asset($product->brand->image) }}" alt="{{ $product->brand->name }}" loading="lazy" class="img-fluid" style="max-height:44px;">
                            @else
                                <span class="font-14 fw-bold text-muted">
                                    <i class="bi bi-award me-1"></i>{{ $product->brand->name }}
                                </span>
                            @endif
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- امتیاز + آمار تعامل (داینامیک) --}}
        @if ($product->rating)
            <div class="d-flex align-items-center flex-wrap gap-3 mt-3 product-meta-rating">
                <div class="d-flex align-items-center gap-1">
                    <i class="bi bi-star-fill text-warning"></i>
                    <span class="fw-bold font-16">{{ $product->rating }}</span>
                    <span class="text-muted font-12">({{ $product->reviews_count }} دیدگاه)</span>
                </div>
                <span class="text-muted font-12">|</span>
                <a href="#prodesc" class="font-13 text-decoration-none js-activate-tab" data-tab="comments">
                    <i class="bi bi-chat-dots me-1"></i>{{ $product->reviews_count }} دیدگاه کاربران
                </a>
                <span class="text-muted font-12">|</span>
                <a href="#prodesc" class="font-13 text-decoration-none js-activate-tab" data-tab="questions">
                    <i class="bi bi-question-circle me-1"></i>{{ $product->comments()->accepted()->count() }} پرسش و پاسخ
                </a>
            </div>
        @endif

        @if ($product->suggestionCount())
            <div class="d-flex align-items-center mt-2">
                <i class="bi bi-hand-thumbs-up text-success"></i>
                <p class="text-muted font-13 mx-2 mb-0">
                    <span class="fw-bold">{{ $product->suggestionPercent() }}%</span>
                    ({{ $product->suggestionCount() }} نفر) از خریداران این کالا را پیشنهاد کرده‌اند
                </p>
            </div>
        @endif

        {{-- واریانت‌ها: رنگ‌ها + سایر گروه‌های ویژگی (داینامیک — همان قرارداد WeblakShop) --}}
        @foreach ($attributeGroups as $attributeGroup)
            @php
                $attributes = $product->get_attributes($attributeGroup, $prevAttribute, $groups, $selectedAttributesIds);
                if (!$attributes) continue;

                $isFirstGroup = ($groupIndex == 0);
                $hasSelectedInGroup = false;
                $currentSelectedAttrIds = $selectedAttributesIds;
            @endphp

            @if ($attributeGroup->type == 'color')
                @php
                    $selectedColor = $selected_price ? $selected_price->get_attributes()->where('attribute_group_id', $attributeGroup->id)->first() : null;
                @endphp
                <div class="product-meta-color mt-4">
                    <h6 class="font-16 mb-2">
                        {{ $attributeGroup->name }}:
                        <span class="text-muted js-color-name">{{ $selectedColor ? $selectedColor->name : '' }}</span>
                    </h6>
                    <div class="product-meta-color-items">
                        @foreach ($attributes as $attribute)
                            @php
                                $hasStock = $isFirstGroup
                                    ? $product->hasAttributeStock($attribute)
                                    : $product->hasAttributeStock($attribute, $currentSelectedAttrIds);
                                $isSelected = $selected_price ? (bool) $selected_price->get_attributes()->find($attribute->id) : false;

                                if ($isSelected) {
                                    $hasSelectedInGroup = true;
                                    $prevAttribute = $attribute;
                                    $selectedAttributesIds[] = $attribute->id;
                                }
                            @endphp
                            <input type="radio" class="btn-check js-variant-selector product-attribute-input"
                                   name="attributes_group[{{ $loop->parent->iteration }}][]"
                                   id="svariant-{{ $attribute->id }}" value="{{ $attribute->id }}"
                                   data-product="{{ $product->slug }}" autocomplete="off"
                                   {{ $isSelected ? 'checked' : '' }} {{ $hasStock ? '' : 'disabled' }}>
                            <label class="btn btt-light {{ $hasStock ? '' : 'opacity-50' }}" for="svariant-{{ $attribute->id }}"
                                   title="{{ $attribute->name }}{{ $hasStock ? '' : ' (ناموجود)' }}">
                                <span style="background-color: {{ $attribute->value }};"></span>
                                {{ $attribute->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="product-meta-color mt-4">
                    <h6 class="font-16 mb-2">
                        {{ $attributeGroup->name }}:
                        <span class="text-muted js-variant-name-{{ $attributeGroup->id }}">
                            {{ $selected_price ? optional($selected_price->get_attributes()->where('attribute_group_id', $attributeGroup->id)->first())->name : '' }}
                        </span>
                    </h6>
                    <div class="product-meta-color-items">
                        @foreach ($attributes as $attribute)
                            @php
                                $hasStock = $isFirstGroup
                                    ? $product->hasAttributeStock($attribute)
                                    : $product->hasAttributeStock($attribute, $currentSelectedAttrIds);
                                $isSelected = $selected_price ? (bool) $selected_price->get_attributes()->find($attribute->id) : false;

                                if ($isSelected) {
                                    $hasSelectedInGroup = true;
                                    $prevAttribute = $attribute;
                                    $selectedAttributesIds[] = $attribute->id;
                                }

                                if ($loop->last && !$isSelected && !$hasSelectedInGroup) {
                                    $isSelected = true;
                                    $hasSelectedInGroup = true;
                                    $prevAttribute = $attribute;
                                    $selectedAttributesIds[] = $attribute->id;
                                }
                            @endphp
                            <input type="radio" class="btn-check js-variant-selector product-attribute-input"
                                   name="attributes_group[{{ $loop->parent->iteration }}][]"
                                   id="svariant-{{ $attribute->id }}" value="{{ $attribute->id }}"
                                   data-product="{{ $product->slug }}" autocomplete="off"
                                   {{ $isSelected ? 'checked' : '' }} {{ $hasStock ? '' : 'disabled' }}>
                            <label class="btn btt-light {{ $hasStock ? '' : 'opacity-50' }}" for="svariant-{{ $attribute->id }}"
                                   title="{{ $attribute->name }}{{ $hasStock ? '' : ' (ناموجود)' }}">
                                {{ $attribute->name }}
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

        {{-- مشخصات ویژه (داینامیک) --}}
        @if ($product->specialSpecifications()->count())
            <div class="product-meta-feature mt-4">
                <div class="row gy-3">
                    <div class="col-lg-12">
                        <div class="product-meta-feature-items">
                            <h5 class="title font-16 mb-2">ویژگی های کالا</h5>
                            <ul class="navbar-nav">
                                @foreach ($product->specialSpecifications()->take(4) as $specification)
                                    <li class="nav item d-flex gap-2">
                                        <span class="text-muted">{{ $specification->name }}:</span>
                                        <strong>{{ $specification->pivot->value }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- گارانتی/خدمات (داینامیک از تنظیمات) --}}
        @if (option('info_warranty_text', 'گارانتی اصالت و سلامت فیزیکی کالا'))
            <div class="product-meta-garanty mt-3 d-flex align-items-center gap-2">
                <i class="bi bi-shield-fill-check text-success"></i>
                <span class="text-muted font-14">{{ option('info_warranty_text', 'گارانتی اصالت و سلامت فیزیکی کالا') }}</span>
            </div>
        @endif

        {{-- توضیح کوتاه (داینامیک) --}}
        @if ($product->short_description)
            <div class="product-meta-short mt-3">
                <div class="product-additional-item is-masked saniar-mask">
                    <p class="text-muted font-14 mb-0">{!! nl2br(strip_tags($product->short_description)) !!}</p>
                    <a class="mask-handler link-border font-13 main-color-one-color text-decoration-none d-none" href="javascript:void(0)">
                        <span class="show-more">مشاهده بیشتر</span>
                        <span class="show-less d-none">مشاهده کمتر</span>
                    </a>
                </div>
            </div>
        @endif

        {{-- راهنمای سایز (داینامیک) --}}
        @if ($product->sizeType)
            <div class="mt-3 size-guide">
                <i class="bi bi-rulers text-muted"></i>
                <a href="javascript:void(0)" class="ms-2 font-13 main-color-one-color" data-bs-toggle="modal" data-bs-target="#sizeModal">راهنمای سایزبندی</a>
            </div>
        @endif

        {{-- باکس خرید (قیمت + دکمه + شمارنده) — داینامیک با selected_price --}}
        <div class="product-meta-action mt-4">
            @include('front::products.partials.product-info-sidebar')
        </div>
    </section>
</div>
