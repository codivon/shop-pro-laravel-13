<div class="col-lg-3 col-md-3 col-xs-12 pull-left sticky-sidebar">
    <div class="mini-buy-box-fixed">
        <div class="mini-buy-box js-mini-buy-box">
            <div class="mini-buy-box-product-info">
                <img src="{{$product->image ? asset($product->image) : asset('/no-image-product.svg') }}"
                     class="mini-buy-box-product-info-img" alt="{{$product->title}}"
                     style="margin-left: 5px">
                <div class="mini-buy-box-product-info-info" style="width: 170px">
                    <div class="title">{{$product->title}}</div>
                    @if ($product->addableToCart())
                    <div class="colors ">

                    </div>
                        @endif
                </div>
            </div>
            <div class="mini-warrantyes">

            </div>
           {{-- <div class="mini-buy-box-row mini-buy-box-seller js-mini-not-digikala-seller">
                <i class="mdi mdi-storefront"></i>
                <label class="js-mini-seller-name">مارکت موبایل پایتخت</label>
            </div>--}}
            @foreach ($attributeGroups as $attributeGroup)
                @php
                    $prev_attribute = null;
                    $groups = null;
                    $attributes_id = [];
                @endphp
                @if ($product->get_attributes($attributeGroup, $prev_attribute, $groups, $attributes_id))
                    @php
                        $checked = false;
                        $group_checked = false;
                    @endphp

                    @foreach ($product->get_attributes($attributeGroup, $prev_attribute, $groups, $attributes_id) as $attribute)
                        @php
                            if ($selected_price->get_attributes()->find($attribute->id)) {
                                $checked = true;
                                $prev_attribute = $attribute;
                                $attributes_id[] = $attribute->id;
                                $group_checked = true;
                            } else {
                                $checked = false;
                            }

                            if ($loop->last && $checked == false && $group_checked == false) {
                                $checked = true;
                                $prev_attribute = $attribute;
                                $attributes_id[] = $attribute->id;
                            }

                        @endphp

                      {{--  @if ($attributeGroup->type != 'color')
                            <div class="mini-buy-box-row mini-buy-box-warranty">
                                <i class="mdi mdi-check"></i>
                                {{ $attributeGroup->name .':'. $attribute->name}}
                            </div>
                        @endif
--}}
                    @endforeach

                    @php
                        $groups[] = $attributeGroup;
                    @endphp
                @endif
            @endforeach
            @if ($product->addableToCart())
            <div class="mini-buy-box-row mini-buy-box-stock">
                <i class="mdi mdi-content-save-outline"></i>
                موجود در انبار فروشنده
            </div>
            {{--<div
                class="mini-buy-box-row mini-buy-box-best-price js-data-best-price text-success">
                <i class="mdi mdi-information-outline"></i>
                بهترین قیمت ۳۰ روز گذشته
            </div>--}}

            <div class="product-seller-row product-seller-row-price mini-buy-box-price-row">
                @if ($selected_price->hasDiscount())
                <div class="product-mini-seller-price-real">
                    <del class="product-mini-seller-price-pure js-price-value d-inline-block text-danger font-size-20">
                        {{ number_format($selected_price->regularPrice()) }}
                    </del>
                    <span class="mini-buy-box-toman text-danger">تومان</span>
                    <div class="discount show-discount mr-3 " style="top: 20px">
                        <span>{{ $selected_price->discount() }}%</span>
                    </div>
                </div>
                @endif
                    <div class="product-mini-seller-price-real">
                        <div class="product-mini-seller-price-pure js-price-value d-inline-block">
                            {{ number_format($selected_price->salePrice()) }}
                        </div>
                        <span class="mini-buy-box-toman">تومان</span>
                    </div>
            </div>
            <div class="mini-buy-box-btn-row">
                <button data-price_id="{{ $selected_price->id }}"
                        data-action="{{ route('front.cart.store', ['product' => $product]) }}"
                        data-product="{{ $product->slug }}" type="button"
                        class="btn-add-to-cart btn mt-4 w-100 btn-primary-cm cursor-pointer btn-with-icon add-to-cart product-add-to-cart-btn">
                    افزودن به سبد خرید
                </button>

            </div>
            @elseif (!$product->addableToCart())
                <div class="product-attributes">
                    <div class="product-seller-info">
                        <div class="product-stock-status">
                            <div class="product-stock-title">
                                <span>ناموجود</span>
                            </div>
                            <div class="product-stock-body">
                                متاسفانه این کالا در حال حاضر موجود نیست. می‌توانید از طریق لیست بالای
                                صفحه،
                                از محصولات مشابه این کالا دیدن نمایید
                            </div>
                            <button class="product-stock-action btn btn-secondary" type="submit">موجود
                                شد به
                                من اطلاع بده</button>
                        </div>
                    </div>
                </div>

                @endif
        </div>
    </div>
</div>
@php
    $selected_price = $product->getPriceWithAttributes($attributes_id);
@endphp
