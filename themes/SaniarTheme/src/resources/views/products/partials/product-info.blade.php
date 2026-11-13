
<div class="col-lg-8 col-md-12 col-xs-12 pull-left px-0 product-info-block" style="transform: none;">
    <section style="transform: none;">
        @if ($product->category)
        <div class="product-headline">
            <div class="product-title-container">
                <div class="product-directory">
                    <ul class="mb-0">
                        @foreach ($product->category->parents() as $parent)
                        <li>
                            <a class="link-border" href="{{ route('front.products.category', ['category' => $parent]) }}">{{ $parent->title }}</a>
                        </li>
                            <li>
                                <span>/</span>
                            </li>
                        @endforeach

                        <li>
                            <a class="link-border" href="{{ route('front.products.category', ['category' => $product->category]) }}">{{ $product->category->title }}</a>
                        </li>
                    </ul>
                    <h1 class="product-title">{{ $product->title }}</h1>
                </div>
            </div>
        </div>
        @endif


            @if ($product->isPhysical())
                @php
                    $prev_attribute = null;
                    $groups = null;
                    $attributes_id = [];
                @endphp

         <div class="product-attributes product-info" style="transform: none;">
            <div class="col-lg-8 col-md-8 col-xs-12 pull-right pr-0">
                <div class="product-config">
                    <span class="product-title-en">{{ $product->title_en }}</span>
                    @if ($product->rating)
                    <div class="product-engagement">
                        <div class="product-engagement-item">
                            <div class="product-engagement-rating">{{ $product->rating }}
                                <span class="product-engagement-rating-num">
                                                    ({{ $product->reviews_count }})
                                                </span>
                            </div>
                        </div>

                        <div class="product-engagement-item">
                            <div class="product-engagement-set"></div>
                            <div class="product-engagement-link" data-activate-tab="comments">
                                {{ $product->reviews_count }}
                                دیدگاه کاربران
                            </div>
                        </div>
                        <div class="product-engagement-item">
                            <div class="product-engagement-set"></div>
                            <div class="product-engagement-link" data-activate-tab="questions">
                                {{ $product->comments()->accepted()->count() }}
                                پرسش و پاسخ
                            </div>
                        </div>
                    </div>
                    @endif
                    @if ($product->suggestionCount())
                        <div class="col-12 d-flex">
                            <i class="mdi mdi mdi-thumb-up-outline text-success mx-0"></i>
                            <p class="text-muted commodity mx-2"><span>{{ $product->suggestionPercent() }}%</span>({{ $product->suggestionCount() }}
                                ) نفر از خریداران این کالا را پیشنهاد کردن </p>
                        </div>
                    @endif


                    <div class="product-config-wrapper d-flex">
                        @if ($product->brand)
                            <div class="product-params p-0">
                                <ul class="m-0" data-title="برند">
                                        <li class="mb-0">
                                            <span>برند: </span>
                                            <span>
                                             <a  href="{{ route('front.brands.show', ['brand' => $product->brand]) }}" class="link--with-border-bottom link-border">{{ $product->brand->name }}</a>
                                            </span>
                                        </li>
                                </ul>

                            </div>
                        @endif
                    </div>
                    {{--color--}}
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
                            {{-- نمایش ویژگی‌های رنگی --}}
                            <div class="product-variants mb-0 d-flex flex-column">
                                @php
                                    $selectedColor = $selected_price->get_attributes()->where('attribute_group_id', $attributeGroup->id)->first();
                                @endphp
                                <div class="mb-3 d-flex">
                                    <h6 class=" ml-1"> {{ $attributeGroup->name }}:</h6>
                                    <span>{{ $selectedColor ? $selectedColor->name : '' }}</span>
                                </div>

                                <ul>
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

                                        <li class="js-c-ui-variant product-attribute">
                                            <label class="ui-variant-color">
                            <span class="ui-variant-shape"
                                  data-colorname="{{ $attribute->name }}"
                                  style="background-color: {{ $attribute->value }}">
                            </span>

                                                <input type="radio"
                                                       title="{{ $attribute->name }}"
                                                       data-product="{{ $product->slug }}"
                                                       value="{{ $attribute->id }}"
                                                       name="attributes_group[{{ $loop->parent->iteration }}][]"
                                                       id="variant"
                                                       class="js-variant-selector variant-selector"
                                                    {{ $isSelected ? 'checked' : '' }}>

                                                <span class="ui-variant-check @if($attribute->name == "سفید") ui-variant-check-white @endif"></span>
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                        @else
                            {{-- نمایش سایر انواع ویژگی‌ها --}}
                            <div class="product-variant dt-sl type-container">
                                <div class="section-title d-flex align-items-baseline text-sm-title no-after-title-wide mb-1">
                                    <h6 class="mb-0 mx-1 d-block">
                                        {{ $attributeGroup->name }}: <span id="attributeGroup-{{ $attributeGroup->id }}"></span>
                                    </h6>
                                </div>

                                <ul class="product-variants float-right type-list ml-3 mt-2 {{ $attributeGroup->type == 'select' ? 'mt-2' : '' }}">
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

                                        <li class="js-c-ui-variant product-attribute {{ $hasStock ? '' : 'unavailable' }}"
                                            title="{{ $hasStock ? '' : 'ناموجود' }}">

                                            <label for="attribute-{{$loop->parent->iteration}}-{{ $attribute->id }}" class="ui-variant mb-0 variant-selector">
                                                <input data-product="{{ $product->slug }}"
                                                       type="radio"
                                                       value="{{ $attribute->id }}"
                                                       name="attributes_group[{{ $loop->parent->iteration }}][]"
                                                       class="variant-selector"
                                                       id="attribute-{{$loop->parent->iteration}}-{{ $attribute->id }}"
                                                    {{ $isSelected ? 'checked' : '' }}
                                                    {{ $hasStock ? '' : 'disabled' }}>

                                                <div class="ui-variant--check {{ $attributeGroup->type == 'select' ? 'select' : '' }}">
                                <span class="m-0" {{ $attributeGroup->type != 'color' ? 'product-warranty-span' : '' }}>
                                    {{ $attributeGroup->type != 'color' ? $attribute->name : '' }}
                                </span>
                                                </div>
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @php
                            $groups[] = $attributeGroup;
                            $groupIndex++;
                        @endphp
                    @endforeach

                    {{-- تنظیم مجدد متغیرها در صورت نیاز به استفاده مجدد --}}
                    @php
                        $prevAttribute = null;
                        $groups = null;
                        $selectedAttributesIds = [];
                        $groupIndex = 0;
                    @endphp
                            {{--end-color--}}

                        @if ($product->specialSpecifications()->count())
                        <div class="product-params">
                            <ul data-title="ویژگی‌های محصول">

                                <li class="title-product-features">
                                    ویژگی‌های محصول
                                </li>
                                    @foreach ($product->specialSpecifications() as $specification)
                                    <li class='{{$loop->index>=3 ? 'product-params-more' : ''}}'>
                                        <span>{{ $specification->name }}: </span>
                                        <span> {{ $specification->pivot->value }} </span>
                                    </li>
                                @endforeach
                                @if ($product->specialSpecifications()->count() > 3)
                                <li class="product-params-more-handler">
                                    <a class="link-border" href="">
                                        <span class="show-more">موارد بیشتر</span>
                                        <span class="show-less">بستن</span>
                                    </a>
                                </li>
                                @endif
                            </ul>


                                <p class="little-des pt-0 mt-0"></p>

                            @if ($product->short_description)
                            <div class="product-additional-info">
                                <div class="product-additional-item is-masked">
                                    <p>{!! nl2br($product->short_description) !!}</p>
                                    <a class="mask-handler link-border" href=''>
                                        <span class="show-more">مشاهده بیشتر</span>
                                        <span class="show-less">مشاهده کمتر</span>
                                    </a>
                                    <div class="shadow-box"></div>
                                </div>
                            </div>
                                @endif
                        </div>
                        @endif


                    @if ($product->sizeType)
                        <div class="mt-3 size-guide">
                            <img src="{{ theme_asset('img/size.png') }}" width="25" alt="size">
                            <a href="javascript:void(0)" data-toggle="modal" data-target="#size-modal" class="mt-4 link--with-border-bottom">راهنمای سایزبندی</a>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-xs-12 pull-left" style="padding: 0px; position: relative; overflow: visible; box-sizing: border-box; min-height: 1px;">
                @include('front::products.partials.product-info-sidebar')
            </div>
        </div>

                @php
                    $selected_price = $product->getPriceWithAttributes($attributes_id);
                @endphp
            @endif


            <?php
            $sevices_sliders = App\Models\Slider::whereJsonContains('groups', 'sevices_sliders')
                ->where('published', true)
                ->get()
            ?>
            @if(count($sevices_sliders))
                <div class="product-feature-body" id="stores-tag">
                    <div class="product-feature">
                        <div class="row">
                            @foreach($sevices_sliders->take(5) as $sevices_slider)

                                <div class="product-feature-col">
                                    <a class="product-feature-item">
                                        <img src="{{asset($sevices_slider->image)}}"
                                             alt="{{$sevices_slider->title}}">
                                        <span><br>{{$sevices_slider->title}}
                                            </span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
    </section>
</div>

