{{-- کانتینر مرسولات و روش‌های ارسال — قرارداد getPrices: با کلید carriers_container رندر و جایگزین #checkout-carrier-container می‌شود
    $sellerGroups : آرایه گروه‌بندی بر اساس فروشنده (کلید: store | seller_{id})
    $cityId / $request_carrier (اختیاری — فقط در AJAX) / $downloadItems / $cart (کامپوزر)
--}}
<div class="consignment-container">

    @foreach($sellerGroups as $groupId => $group)
        @php
            // انتخاب جاری: اولویت با درخواست کاربر؛ در غیر این صورت حامل پیش‌فرض سرور
            $checkedCarrierId = null;
            if (isset($request_carrier) && isset($request_carrier['carrier_id_' . $groupId])) {
                $checkedCarrierId = $request_carrier['carrier_id_' . $groupId];
            } elseif (!empty($group['selected_carrier'])) {
                $checkedCarrierId = $group['selected_carrier']['id'];
            }
        @endphp

        <div class="content-box mb-4 consignments" data-group-id="{{ $groupId }}">
            <div class="container-fluid">
                {{-- هدر مرسوله --}}
                <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pb-3 mb-3">
                    <div class="d-flex align-items-center">
                        @if(!empty($group['logo']))
                            <img src="{{ $group['logo'] }}" class="img-fluid rounded-3 ms-2" width="48" height="48" alt="{{ $group['name'] }}">
                        @else
                            <span class="btn-light rounded-3 p-2 ms-2"><i class="bi bi-shop font-25 main-color-one-color"></i></span>
                        @endif
                        <div class="d-flex flex-column">
                            <h6 class="font-16 mb-0">{{ $group['name'] }}</h6>
                            <p class="font-12 text-muted mb-0">
                                {{ $group['is_store'] ? 'ارسال توسط فروشگاه' : 'ارسال توسط فروشنده' }}
                                — {{ count($group['products']) }} کالا
                            </p>
                        </div>
                    </div>
                    <div class="text-center">
                        <p class="font-12 text-muted mb-0">هزینه ارسال این مرسوله</p>
                        <p class="font-16 mb-0" id="shipping-cost-{{ $groupId }}">
                            @if($group['shipping_cost'] !== false && $group['shipping_cost'] !== null && $group['shipping_cost'] > 0)
                                {{ number_format($group['shipping_cost']) }} تومان
                            @else
                                <span class="main-color-three-color">رایگان</span>
                            @endif
                        </p>
                    </div>
                </div>

                {{-- لیست محصولات مرسوله --}}
                @foreach($group['products'] as $product)
                    <div class="d-flex align-items-start border-bottom py-3">
                        <div class="flex-shrink-0">
                            <img src="{{ $product['image'] }}" class="img-fluid rounded-3" width="70" height="70" alt="{{ $product['title'] }}" loading="lazy">
                        </div>
                        <div class="flex-grow-1 px-3">
                            <a href="{{ route('front.products.show', ['product' => $product['slug']]) }}" class="text-dark text-decoration-none">
                                <h6 class="font-14 mb-1 text-overflow-2">{{ $product['title'] }}</h6>
                            </a>
                            <div class="d-flex align-items-center flex-wrap">
                                @foreach($product['variants'] as $variant)
                                    <span class="btn-light rounded-3 px-2 py-1 font-12 text-muted d-inline-flex align-items-center ms-1 mb-1">
                                        @if($variant['type'] == 'color')
                                            <span class="d-inline-block rounded-circle ms-1" style="width:12px;height:12px;background-color:{{ $variant['color_code'] }};"></span>
                                        @endif
                                        {{ $variant['name'] }}
                                    </span>
                                @endforeach
                                <span class="font-12 text-muted">تعداد: {{ $product['quantity'] }}</span>
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <p class="font-14 mb-0 fw-bold">{{ number_format($product['final_price'] * $product['quantity']) }} تومان</p>
                            @if($product['discount'])
                                <del class="font-12 text-muted">{{ number_format($product['price'] * $product['quantity']) }} تومان</del>
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- انتخاب روش ارسال مرسوله --}}
                <div class="shipping-methods mt-3">
                    <h6 class="font-14 mb-2"><i class="bi bi-rocket-takeoff-fill main-color-one-color ms-1"></i>روش ارسال مرسوله {{ $group['number'] }} را انتخاب کنید:</h6>

                    @foreach($group['carriers'] as $carrier)
                        @if($cart->canUseCarrier($carrier['id'], $cityId)['status'])
                            @php $isCarrierChecked = $checkedCarrierId == $carrier['id']; @endphp
                            <label class="shipping-item d-flex align-items-center justify-content-between w-100 {{ $isCarrierChecked ? 'active' : '' }}">
                                <span class="d-flex align-items-center">
                                    @if($carrier['image'])
                                        <img src="{{ $carrier['image'] }}" class="img-thumbnail" width="48" alt="{{ $carrier['title'] }}">
                                    @else
                                        <i class="bi bi-truck font-25 main-color-one-color"></i>
                                    @endif
                                    <span class="mx-2">
                                        <span class="d-block font-14">{{ $carrier['title'] }}</span>
                                        @if($carrier['description'])
                                            <span class="d-block font-12 text-muted">{{ $carrier['description'] }}</span>
                                        @endif
                                        @if($carrier['delivery_type'] == 'default' && $carrier['delivery_text'])
                                            <span class="d-block font-12 text-muted"><i class="bi bi-clock-history"></i> {{ $carrier['delivery_text'] }}</span>
                                        @endif
                                    </span>
                                </span>
                                <span class="font-14 {{ $carrier['is_free'] ? 'main-color-three-color' : 'text-muted' }}">
                                    @if($carrier['shipping_cost'] === false || $carrier['shipping_cost'] === null)
                                        <span class="font-12">پس از انتخاب آدرس</span>
                                    @elseif($carrier['is_free'])
                                        رایگان
                                    @else
                                        {{ number_format($carrier['shipping_cost']) }} تومان
                                    @endif
                                </span>
                                <input type="radio" class="d-none carrier-select-btn" name="carrier_id_{{ $groupId }}"
                                       id="carrier-{{ $carrier['id'] }}" value="{{ $carrier['id'] }}"
                                       data-group-id="{{ $groupId }}" data-delivery-type="{{ $carrier['delivery_type'] }}"
                                       {{ $isCarrierChecked ? 'checked' : '' }}>
                            </label>
                        @endif
                    @endforeach
                </div>

                {{-- محل تزریق تاریخ‌های تحویل (AJAX) --}}
                <div id="send-period-container-{{ $groupId }}"></div>
            </div>
        </div>
    @endforeach

    {{-- مرسوله دانلودی --}}
    @if($downloadItems && count($downloadItems) > 0)
        <div class="content-box mb-4">
            <div class="container-fluid">
                <div class="d-flex align-items-center border-bottom pb-3 mb-3">
                    <span class="btn-light rounded-3 p-2 ms-2"><i class="bi bi-download font-25 main-color-one-color"></i></span>
                    <div class="d-flex flex-column">
                        <h6 class="font-16 mb-0">کالاهای دانلودی</h6>
                        <p class="font-12 text-muted mb-0">{{ count($downloadItems) }} کالا — بلافاصله پس از پرداخت</p>
                    </div>
                </div>
                @foreach($downloadItems as $product)
                    <div class="d-flex align-items-start border-bottom py-3">
                        <div class="flex-shrink-0">
                            <img src="{{ $product['image'] }}" class="img-fluid rounded-3" width="70" height="70" alt="{{ $product['title'] }}" loading="lazy">
                        </div>
                        <div class="flex-grow-1 px-3">
                            <h6 class="font-14 mb-1">{{ $product['title'] }}</h6>
                            <div class="d-flex align-items-center flex-wrap">
                                @foreach($product['variants'] as $variant)
                                    <span class="btn-light rounded-3 px-2 py-1 font-12 text-muted d-inline-flex align-items-center ms-1 mb-1">
                                        @if($variant['type'] == 'color')
                                            <span class="d-inline-block rounded-circle ms-1" style="width:12px;height:12px;background-color:{{ $variant['color_code'] }};"></span>
                                        @endif
                                        {{ $variant['name'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <p class="font-14 mb-0 fw-bold">{{ number_format($product['final_price'] * $product['quantity']) }} تومان</p>
                        </div>
                    </div>
                @endforeach
                <p class="font-12 text-muted mt-3 mb-0"><i class="bi bi-info-circle"></i> هزینه ارسال کالاهای دانلودی: <span class="main-color-three-color">رایگان (دانلودی)</span></p>
            </div>
        </div>
    @endif
</div>
