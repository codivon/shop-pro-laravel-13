@if ($product->addableToCart() && $selected_price)

    @php

        // جمع‌آوری همه seller_id های یکتا (بدون تکرار)
        $stores_attribute_prices=[];
        if ($get_stores){
            $stores_attribute_prices=$get_stores['attribute_prices'];
        }
        $uniqueSellers = [];
        foreach($stores_attribute_prices as $attribute_prices){
            $sellerKey = $attribute_prices->seller_id ?? 'site';
            $uniqueSellers[$sellerKey] = true;
        }
        // تعداد فروشنده‌های یکتا
        $totalUniqueSellers = count($uniqueSellers);

        // فروشنده فعلی
        $currentSeller = $selected_price->seller_id ?? 'site';

        // تعداد فروشنده‌های دیگر (غیر از فروشنده فعلی)
        $otherSellersCount = 0;
        foreach($uniqueSellers as $seller => $value){
            if($seller != $currentSeller){
                $otherSellersCount++;
            }
        }
    @endphp

    <div class="theiaStickySidebar" style="padding-top: 0px; padding-bottom: 1px; position: static; transform: none;">
        <div class="product-seller-info">
            <div class="js-seller-info-changable product-actions">
                @if(option('multi_vendor_system_status','false')=="true")
                    <div class="product-seller-row">
                        <div>
                            <div class="product-seller-first-line d-inline-block"> فروشنده:</div>
                            <a href="#stores-tag" class="js-seller-count-row">
                                <span class="js-seller-count u-text-bold count_stores"></span>
                                @if($totalUniqueSellers > 1)
                                    <a href="#stores-tag" class="js-seller-count-row">
                                        <span class="js-seller-count u-text-bold count_stores"></span>
                                        <span class="u-text-bold">{{$otherSellersCount}} فروشنده دیگر</span>
                                    </a>
                                @endif

                            </a>
                        </div>
                        <div class="seller-container">
                            <ul>
                                @if($selected_price->seller)
                                <li>

                                        <div class="seller-avatar shadow-1 ml-2">
                                            @if($selected_price->seller->seller_info->logo)
                                                <img src="{{ asset($selected_price->seller->seller_info->logo) }}" class="rounded-circle object-fit-cover">
                                            @else
                                                <i class="fas fa-store fs-4 text-secondary"></i>
                                            @endif
                                        </div>
                                        <a class="table-name lts-05" href="{{route('front.showSellerStore',$selected_price->seller)}}">{{$selected_price->seller->seller_info->business_name}}
                                            <i class="ri-verified-badge-fill verify me-0"></i>
                                        </a>
                                        <span class="divider"></span>
                                        <span class="table-flag lts-05">فروشنده</span>
                                </li>
                                <li>
                                    <i class=" fas fa-chart-column"></i><span class="table-name lts-05" href="#">عملکرد</span><span class="divider"></span><span class="text-success-dark fw-bold">عالی</span>
                                </li>
                                @else
                                    <li>

                                        <div class="seller-avatar shadow-1 ml-2">
                                            <i class="fas fa-store fs-4 text-secondary m-0"></i>
                                        </div>
                                        <a class="table-name lts-05" href="/"> {{ option('info_site_title', 'او پی شاپ') }}
                                            <i class="ri-verified-badge-fill verify me-0"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <i class=" fas fa-chart-column"></i><span class="table-name lts-05" href="#">عملکرد</span><span class="divider"></span><span class="text-success-dark fw-bold">عالی</span>
                                    </li>
                                @endif
                                <li class="clickable" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="کپی شناسه محصول">
                                    <i class=" fas fa-qrcode"></i><span class="table-name lts-05">شناسه محصول</span><span class="divider"></span><span class="table-flag lts-05">{{$selected_price->product->product_id}}</span>
                                </li>
                                <li>
                                    @if($selected_price->seller)
                                        <i class="fa-solid fa-truck-fast"></i><span class="table-name lts-05">ارسال توسط فروشنده</span><span class="divider"></span><span class="table-flag lts-05 fs-9">آماده ارسال</span>
                                    @else
                                        <i class="fa-solid fa-truck-fast"></i><span class="table-name lts-05">ارسال فروشگاه اصلی</span><span class="divider"></span><span class="table-flag lts-05 fs-9">آماده ارسال</span>
                                    @endif
                                </li>
                                    @if ($product->labels->count())
                                <li>
                                    @foreach ($product->labels as $label)
                                        <span class="table-name badge bg-success fs-9 lts-05" style="padding: 4px 6px !important;">{{ $label->title }} </span>
                                    <span class="divider"></span>
                                    @endforeach
                                </li>
                                    @endif
                            </ul>
                        </div>


                    </div>
                @endif


              {{--  <div class="product-seller-row js-seller-info-shipment">
                    <div class="js-guarantee-text">
                        موجود در انبار فروشنده
                        <i class="mdi mdi-content-save-outline"></i>
                    </div>
                    <div class="product-delivery-warehouse">آماده ارسال</div>
                </div>--}}

                <div class="product-seller-row">
                    <div class="product-seller-row-price mb-3">
                        <div class="product-seller-price-label">
                            @if($selected_price->seller)
                                قیمت فروشنده
                            @else
                                قیمت
                            @endif
                        </div>
                        @if ($selected_price->hasDiscount())
                            <div class="product-seller-price-real text-danger">
                                <del class="product-seller-price-prev text-danger font-size-20">{{ number_format($selected_price->regularPrice()) }}</del>
                                تومان
                                <div class="discount show-discount mr-3 ">
                                    <span>{{ $selected_price->discount() }}%</span>
                                </div>
                            </div>
                        @endif
                        <div class="product-seller-price-real" id="price-real">
                            <div class="product-seller-price-prev">{{ number_format($selected_price->salePrice()) }} </div>
                            تومان
                        </div>
                    </div>


                    <button data-price_id="{{ $selected_price->id }}"
                            data-action="{{ route('front.cart.store', ['product' => $product]) }}"
                            data-product="{{ $product->slug }}" type="button"
                            class="btn-add-to-cart mt-4 w-100 btn-primary-cm cursor-pointer btn-with-icon add-to-cart btn-show-product">
                        {{ $product->pre_order && option('dt_pre_order_status', '1') == '1' ? option('dt_pre_order_button_text', 'ثبت پیش‌فروش') : 'افزودن به سبد خرید' }}
                    </button>

                    {{-- فاز ۷۸/۸۱ — هشدار کاهش قیمت (قابل غیرفعال‌سازی و شخصی‌سازی از تنظیمات تم) --}}
                    @if(option('dt_price_alert_status', '1') == '1')
                        <button id="price_alert_btn" data-user="{{ auth('web')->check() ? auth('web')->user()->id : '' }}"
                                data-product="{{ $product->id }}" type="button"
                                class="product-stock-action btn btn-secondary cart-not-available w-100 mt-2">
                            <i class="{{ option('dt_price_alert_button_icon', 'mdi mdi-bell-ring-outline') }}"></i>
                            {{ option('dt_price_alert_button_text', 'هشدار کاهش قیمت') }}
                        </button>
                    @endif


                </div>
            </div>

        </div>
        @elseif (!$product->addableToCart() || !$selected_price)
            <div class="theiaStickySidebar" style="padding-top: 0px; padding-bottom: 1px; position: static; transform: none;">
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
                        <button id="stock_notify_btn" data-user="{{ auth('web')->check() ? auth('web')->user()->id : '' }}"
                                data-product="{{ $product->id }}" class="product-stock-action btn btn-secondary  cart-not-available" type="submit">
                            <i class="mdi mdi-information"></i>
                            موجود شد به من اطلاع بده
                        </button>
                    </div>
                </div>
            </div>
@endif
