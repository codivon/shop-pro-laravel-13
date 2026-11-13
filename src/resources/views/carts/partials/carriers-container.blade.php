<div id="checkout-carrier-container" class="consignment-container">
    @foreach($sellerGroups as $groupId => $group)

        <div class="card shadow-1 consignments" data-group-id="{{ $groupId }}">
            <div class="card-body">
                <div class="consignment text-right">
                    <div class="main-title d-flex align-items-center mb-3">
                        <img src="{{ @$group['logo'] }}"
                            alt="{{ $group['name'] }}"
                            loading="lazy"
                            style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                        <div class="d-flex flex-column justify-content-center ms-3">
                            <span class="fw-light fs-9 text-gray">مرسوله شماره {{ $group['number'] }}</span>
                            <span class="fw-bold lts-05 text-dark">
                                @if($group['is_store'])
                                    <span>ارسال توسط</span>
                                    <span class="ms-1">{{ $group['name'] }}</span>
                                @else
                                    <span>ارسال توسط فروشنده:</span>
                                    <a class="link ms-1" href="">
                                        {{ $group['name'] }}
                                    </a>
                                @endif
                                <span class="fs-9 text-gray fw-light badge bg-light ms-1">{{ count($group['products']) }} کالا</span>
                            </span>
                        </div>
                    </div>

                    <div class="send-time mb-3">
                        @if(!empty($group['delivery_info']))
                            <span class="d-inline-flex lts-05 alert alert-light text-dark m-0 mb-2 me-2"
                                id="delivery-text-{{ $groupId }}">
                                {{ $group['delivery_info'] }}
                            </span>
                        @endif

                        <span class="d-inline-flex lts-05 alert alert-light text-dark m-0">
                            <span class="me-1">هزینه ارسال: </span>
                            <span class="fw-bold" id="shipping-cost-{{ $groupId }}">
                                @if($group['shipping_cost'] > 0)
                                    {{ number_format($group['shipping_cost']) }} تومان
                                @else
                                    رایگان
                                @endif
                            </span>
                        </span>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-auto pe-0 w-100">
                            <ul class="consignment-items ps-0">
                                @foreach($group['products'] as $product)
                                    <li class="item item-full">
                                        <div class="thumbnail">
                                            <a href="{{ route('front.products.show', $product['slug']) }}">
                                                <img src="{{ $product['image'] }}" alt="{{ $product['title'] }}" loading="lazy">
                                            </a>
                                        </div>
                                        <div class="body d-flex flex-column justify-content-center">
                                            <h4 class="mb-2">
                                                <a class="link fs-8 lts-05" href="{{ route('front.products.show', $product['slug']) }}">
                                                    {{ $product['title'] }}
                                                </a>
                                            </h4>
                                            <ul class="mb-2">
                                                <div class="cart-item--data">
                                                    <ul>
                                                        @foreach($product['variants'] as $variant)
                                                            <li class="mb-0 me-2" style="vertical-align: middle;">
                                                                <div class="cart-item--variant">
                                                                    @if($variant['type']=="color")
                                                                        <span class="color" style="background-color: {{ $variant['value'] }};"></span>
                                                                    @endif
                                                                    @if(str_contains($variant['name'], 'گارانتی'))
                                                                        <i class="fas fa-shield-halved"></i>
                                                                    @endif
                                                                    <span class="color-name lts-05 ms-1">{{ $variant['name'] }}</span>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </ul>
                                            <div class="cart-item--price fa-num">
                                                <div class="cart-item--price-now">
                                                    <span>{{ number_format($product['final_price']) }}</span>
                                                    <span class="unit unit-sm">تومان</span>
                                                </div>
                                                @if($product['discount'])
                                                    <div class="cart-item--discount me-2">
                                                        <del>{{ number_format($product['price']) }}</del>
                                                        <span class="unit-red unit-sm">تومان</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="col-12">
                            <div class="time-and-send-container d-block mb-2">
                                <div class="send-address-title fs-8 mt-0">شیوه ارسال:</div>
                                <div class="radio-send-method">
                                    <div class="row" data-group-id="{{ $groupId }}">
                                        @foreach($group['carriers'] as $carrierIndex => $carrier)

                                            @if ($cart->canUseCarrier($carrier['id'], $cityId)['status'])
                                                <div class="custom-radio col-auto {{ isset($request_carrier['carrier_id_' . $groupId]) && $request_carrier['carrier_id_' . $groupId] == $carrier['id'] ? 'custom-radio--active' : '' }}">
                                                    <input type="radio"
                                                        class="custom-control-input form-control carrier-select-btn"
                                                        name="carrier_id_{{ $groupId }}"
                                                        id="carrier-{{ $carrier['id'] }}"
                                                        value="{{ $carrier['id'] }}"
                                                        {{ isset($request_carrier['carrier_id_' . $groupId]) && $request_carrier['carrier_id_' . $groupId] == $carrier['id'] ? 'checked' : '' }}
                                                        data-group-id="{{ $groupId }}">
                                                    <label for="carrier-{{ $carrier['id'] }}"
                                                        class="inner custom-radio-label white-space-nowrap carrier-select-btn">
                                                        <span class="label pe-2">
                                                            <span class="carriers-select-address">
                                                                <i class="fas fa-circle-dot"></i>
                                                            </span>

                                                            @if ($carrier['image'])
                                                                <img src="{{ asset($carrier['image']) }}"
                                                                    class="checkout-additional-options-checkbox-image" alt="{{ $carrier['title'] }}" />
                                                            @endif
                                                            <span class="detail ms-2">
                                                                <span class="title lts-05">{{ $carrier['title'] }}</span>
                                                                <span class="subtitle lts-05">{{ $carrier['description'] }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- جای‌گذاری روزهای ارسال (روش‌های user_select) — از طریق AJAX در js/pages/checkout.js --}}
                        <div id="send-period-container-{{ $groupId }}"></div>

                    </div>
                </div>
            </div>
        </div>

    @endforeach


    {{-- نمایش محصولات دانلودی (بدون ارسال) --}}
    @if($downloadItems && count($downloadItems) > 0)
        <div class="card shadow-1">
            <div class="card-body">
                <div class="consignment text-right">
                    <div class="main-title d-flex align-items-center mb-3">
                        <div class="d-flex flex-column justify-content-center">
                            <span class="fw-light fs-9 text-gray">محصولات دانلودی</span>
                            <span class="fw-bold lts-05 text-dark">
                                <span class="fs-9 text-gray fw-light badge bg-light ms-1">{{ count($downloadItems) }} کالا</span>
                            </span>
                        </div>
                    </div>

                    <div class="send-time mb-3">
                        <span class="d-inline-flex lts-05 alert alert-success text-dark m-0">
                            <span class="me-1">هزینه ارسال: </span>
                            <span class="fw-bold">رایگان (دانلودی)</span>
                        </span>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-auto pe-0">
                            <ul class="consignment-items ps-0">
                                @foreach($downloadItems as $product)
                                    <li class="item item-full">
                                        <div class="thumbnail">
                                            <a href="{{ route('front.products.show', $product['slug']) }}">
                                                <img src="{{ $product['image'] }}" alt="{{ $product['title'] }}" loading="lazy">
                                            </a>
                                        </div>
                                        <div class="body d-flex flex-column justify-content-center">
                                            <h4 class="mb-2">
                                                <a class="link fs-8 lts-05" href="{{ route('front.products.show', $product['slug']) }}">
                                                    {{ $product['title'] }}
                                                </a>
                                            </h4>
                                            <ul class="mb-2">
                                                <div class="cart-item--data">
                                                    <ul>
                                                        @foreach($product['variants'] as $variant)
                                                            <li class="mb-0 me-2" style="vertical-align: middle;">
                                                                <div class="cart-item--variant">
                                                                    @if($variant['type']=="color")
                                                                        <span class="color" style="background-color: {{ $variant['value'] }};"></span>
                                                                    @endif
                                                                    @if(str_contains($variant['name'], 'گارانتی'))
                                                                        <i class="fas fa-shield-halved"></i>
                                                                    @endif
                                                                    <span class="color-name lts-05 ms-1">{{ $variant['name'] }}</span>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </ul>
                                            <div class="cart-item--price fa-num">
                                                <div class="cart-item--price-now">
                                                    <span>{{ number_format($product['final_price']) }}</span>
                                                    <span class="unit unit-sm">تومان</span>
                                                </div>
                                                @if($product['discount'])
                                                    <div class="cart-item--discount me-2">
                                                        <del>{{ number_format($product['price']) }}</del>
                                                        <span class="unit-red unit-sm">تومان</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
