@extends('front::carts.layout')

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/vendor/nouislider.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/vendor/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/mapir/css/mapp.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/mapir/css/fa/style.css') }}">
    <link rel="stylesheet" href="{{theme_asset('css/map-selected-styles.css')}}" />
    <link rel="stylesheet" href="{{theme_asset('css/checkout.css')}}" />

@endpush

@section('cart-header')
    <li class="is-completed">
        <a href="{{route('front.checkout')}}" class="checkout-steps-item-link active-link-shopping">
            <span>اطلاعات ارسال و پرداخت</span>
        </a>
    </li>
    <li class="is-active">
        <a class="checkout-steps-item active-link">
            <span>اتمام خرید و ارسال</span>
        </a>
    </li>
@endsection

@section('wrapper-classes', 'shopping-page')

@section('content')
    <div class="content-shopping ">


        <div class="col-lg-9 col-md-9 col-xs-12 pull-right block-div">
            <form id="checkout-form" data-price-action="{{ route('front.checkout.prices') }}"
                  action="{{ route('front.orders.store') }}" class="setting_form" method="POST">
                @csrf
                <div class="shipment-page-container">


                    @if(session('error'))
                        <div class="alert alert-danger">
                            <p>  {{ session('error') }}</p>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    @if(!$discount_status['status'])
                        <div class="alert alert-danger" role="alert">
                            <p>کد تخفیف وارد شده معتبر نیست.</p>
                            <span>{{ $discount_status['message'] }}</span>
                        </div>
                    @endif
                </div>


                <div class="shipment-page-container">

                    @php
                        $hasPhysical = isset($sellerGroups) && count($sellerGroups) > 0;
                    @endphp

                    @if($hasPhysical)
                        @include('front::carts.partials.address-list', ['addresses' => $addresses])
                    @else
                        {{-- پیام برای محصولات دانلودی --}}
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            محصولات شما دانلودی هستند و نیازی به ثبت آدرس ندارید.
                        </div>
                    @endif

                    <div id="checkout-carrier-container">
                        @include('front::carts.partials.carriers-container', ['cart' => $cart,'sellerGroups'=>$sellerGroups])

                    </div>





                </div>

                {{-- @if ($cart->hasPhysicalProduct())
                     @include('front::carts.partials.carriers-container', ['cart' => $cart])
                 @endif--}}
                @include('front::carts.partials.payment-method', ['cart' => $cart])
            </form>
            <div class="page-content shopping-page mt-0">

                <div class="w-100 display-inline-block pt-3 pb-3">
                    <div class="checkout-pack-row checkout-pack mb-0">

                        @if ($cart->discount)
                            <div class="checkout-pack-header">
                                <span>کد تخفیف ثبت شده</span>
                            </div>

                            <div class="col-md-4 col-12 px-0">
                                <div class="dt-sn pt-3 pb-3 px-res-1">
                                    <div class="form-ui">
                                        <form action="{{ route('front.discount.destroy') }}" method="POST">
                                            @csrf
                                            @method('delete')
                                            <div class="row text-center mr-20">
                                                <div class="col-xl-6">
                                                    <strong>{{ $cart->discount->code }}</strong>
                                                </div>
                                                <div class="col-xl-6 text-left">
                                                    <button type="submit" class="btn btn-danger mt-res-1">حذف کد تخفیف
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        @else
                            <div class="checkout-pack-header">
                                <span>کد تخفیف دارید؟</span>
                            </div>

                            <div class="col-lg-6 col-md-6 col-sm-12 px-0">
                                <div class="dt-sn pt-3 pb-3 px-res-1">

                                    <div class="form-ui">
                                        <form id="discount-create-form" action="{{ route('front.discount.store') }}">
                                            @csrf
                                            <div class="row text-center pr-4 pl-4">
                                                <div class="col-lg-8 col-md-8 col-sm-8 mb-3">
                                                    <div class="form-row">
                                                        <input type="text" name="code" class="input-ui pr-2"
                                                               placeholder="کد تخفیف را اینجا وارد کنید" required>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2 col-md-2  col-sm-12">
                                                    <button type="submit" class="btn btn-primary mt-res-1">ثبت کد
                                                        تخفیف
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        @endif

                    </div>
                </div>


            </div>


            <div class="checkout-actions">
                <a href="{{route('front.cart')}}" class="btn-link-spoiler">
                    « بازگشت به سبد خرید
                </a>
                <button id="checkout-link" class="save-shipping-data checkout_link cursor-pointer"
                        data-action="{{ route('front.cart') }}" data-redirect="{{ route('front.checkout') }}">
                    تایید و ادامه ثبت سفارش »
                </button>
            </div>
        </div>

        <div class="col-lg-3 col-md-3 col-xs-12 pull-left sticky-sidebar">
            @include('front::carts.partials.checkout-sidebar' ,['mt'=>'margin-top: 50px;'])
        </div>
    </div>

    <div class="modal fade" id="add-edit-address-modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div id="showMap" class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">
                        آدرس جدید
                        <div class="div-bottom-modal-title">موقعیت مکانی آدرس را مشخص کنید.</div>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">

                            <div class="form-ui dt-sl">
                                <form action="#" class="form-checkout">
                                    <div class="form-checkout-row ">
                                        @include('front::carts.partials.map')
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-sm-between position-relative">
                    <p class="pt-4">مرسوله‌های شما به این موقعیت ارسال خواهد شد.</p>
                    <div class="form-checkout-valid-row">
                        <div class="parent-btn">
                            <button id="next-add-address-btn" class="dk-btn dk-btn-info disabled" disabled>
                                <i class="fa fa-check sign-in"></i>
                                تایید و ادامه

                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="more-information" class="modal-content d-none">
                <div class="modal-header">
                    <h4 class="modal-title">
                        <a id="back-to-map" class="profile-navbar-btn-back">بازگشت</a>
                        <p> جزییات آدرس</p>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                </div>
                <div class="modal-body ">
                    <div class="row">
                        <div class="col-12 ">

                            <div class="form-ui dt-sl middle-container">
                                <form id="add-update-address-form" action="{{ route('front.addresses.store') }}"
                                      class="form-checkout setting_form" method="POST">
                                    @csrf

                                    <div class="more-information">

                                        <div class="form-checkout-row ">
                                            <div class="row">
                                                <div class="col-12">
                                                    <label for="address">نشانی پستی
                                                        <span class="required-star" style="color:red;">*</span></label>
                                                    <textarea type="text" id="address" name="address"
                                                              class="input-name-checkout mb-2"
                                                              placeholder="آدرس خود را وارد نمایید"
                                                              style="height:80px;"></textarea>
                                                    <input type="hidden" id="lat" name="lat">
                                                    <input type="hidden" id="lng" name="lng">
                                                    <p class="add-address-bottom-text">آدرس بالا بر اساس موقعیت انتخابی
                                                        شما وارد شده است.</p>
                                                </div>
                                            </div>
                                            <hr>
                                            @php
                                                $cities = [];
                                                $city_id = null;
                                            @endphp
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-checkout-valid-row">
                                                        <label for="province">استان <span class="required-star"
                                                                                          style="color:red;">*</span></label>
                                                        <select class="right" name="province_id" id="province">
                                                            <option value="date-desc" selected="selected">شهر مورد نظر
                                                                خود را انتخاب کنید
                                                            </option>
                                                            @foreach($provinces as $item)
                                                                <option
                                                                    value="{{ $item->id }}">{{ $item->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-6">
                                                    <div class="form-checkout-valid-row w-100">
                                                        <label for="city">شهر
                                                            <span class="required-star"
                                                                  style="color:red;">*</span></label>
                                                        <select class="right" name="city_id" id="city">
                                                            <option value="date-desc" selected="selected">شهر مورد نظر
                                                                خود را انتخاب کنید
                                                            </option>
                                                            @foreach($cities as $item)
                                                                <option
                                                                    value="{{ $item->id }}">{{ $item->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <label for="buildingNumber">پلاک<span class="required-star"
                                                                                                  style="color:red;">*</span></label>
                                                            <input type="number" name="buildingNumber"
                                                                   id="buildingNumber" class="input-name-checkout"
                                                                   placeholder="پلاک">
                                                        </div>
                                                        <div class="col-6">
                                                            <label for="unit">واحد</label>
                                                            <input type="text" name="unit" id="unit"
                                                                   class="input-name-checkout"
                                                                   placeholder="واحد">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label for="postalCode">کد پستی<span class="required-star"
                                                                                         style="color:red;">*</span></label>
                                                    <input type="text" name="postal_code" id="postalCode"
                                                           class="input-name-checkout placeholder-right"
                                                           placeholder="کد‌پستی باید ۱۰ رقم و بدون خط تیره باشد.">
                                                </div>
                                            </div>

                                            <hr>
                                            <div class="row">
                                                <div class="col-6">
                                                    <label for="name">نام و نام خانوادگی تحویل گیرنده <span
                                                            class="required-star"
                                                            style="color:red;">*</span></label>
                                                    <input type="text" id="name" name="fullname"
                                                           class="input-name-checkout"
                                                           placeholder="نام تحویل گیرنده را وارد نمایید">
                                                </div>
                                                <div class="col-6">
                                                    <label for="phone-number">شماره موبایل <span class="required-star"
                                                                                                 style="color:red;">*</span></label>
                                                    <input type="text" id="phone-number" name="mobile"
                                                           class="input-name-checkout" placeholder="09xxxxxxxxx"
                                                           style="text-align:left;direction: ltr">
                                                </div>
                                            </div>


                                            <div class="form-checkout-valid-row">
                                                <div class="parent-btn">
                                                    <button class="dk-btn dk-btn-info">
                                                        ثبت آدرس
                                                        <i class="fa fa-check sign-in"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <a class="cancel-edit-address cursor-pointer" data-dismiss="modal">انصراف و
                                                بازگشت</a>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <input name="map_api" type="hidden" value="{{ option('map_api') }}">
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/vendor/wNumb.js') }}"></script>
    <script src="{{ theme_asset('js/vendor/ResizeSensor.min.js') }}"></script>
    <script src="{{ theme_asset('js/vendor/jquery.nice-select.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>

    <script src="{{ theme_asset('js/pages/cart.js') }}?v=3"></script>
    <script src="{{ theme_asset('js/pages/checkout.js') }}?v=11"></script>
    <script src="{{ theme_asset('js/pages/addresses/index.js') }}"></script>
    <script src="{{ theme_asset('js/pages/addresses/add-edit-address.js?v=2') }}"></script>
    <script>
        /*$(document).ready(function() {
            let selectedCarriers = {};
            let selectedDates = {};

            // ========== انتخاب روش ارسال ==========
            $(document).on('click', '.carrier-select-btn', function() {
                let $btn = $(this);
                let groupId = $btn.data('group-id');
                let carrierId = $btn.data('carrier-id');
                let shippingCost = $btn.data('shipping-cost');
                let deliveryText = $btn.data('delivery-text');
                let deliveryType = $btn.data('delivery-type');
                let hasDates = $btn.data('has-dates');

                // به‌روزرسانی کلاس active
                $(`.radio-send-method .custom-radio`).removeClass('custom-radio--active');
                $btn.closest('.custom-radio').addClass('custom-radio--active');

                // به‌روزرسانی هزینه ارسال و متن تحویل
                let shippingCostText = shippingCost > 0 ? number_format(shippingCost) + ' <span class="unit unit-sm"></span>' : 'رایگان';
                $(`#shipping-cost-${groupId}`).html(shippingCostText);

                if (deliveryText) {
                    $(`#delivery-text-${groupId}`).text(deliveryText);
                }

                // مخفی کردن همه انتخابگرهای تاریخ
                $('.delivery-dates-container').hide();

                // اگر روش ارسال نیاز به انتخاب تاریخ دارد
                if (hasDates == 'true') {
                    $(`#delivery-dates-${groupId}-${carrierId}`).show();
                }

                // ذخیره انتخاب
                selectedCarriers[groupId] = {
                    carrier_id: carrierId,
                    shipping_cost: shippingCost,
                    delivery_type: deliveryType
                };

                // حذف تاریخ انتخاب شده قبلی برای این گروه
                if (selectedDates[groupId]) {
                    delete selectedDates[groupId];
                }

                updateTotals();
            });

            // ========== انتخاب تاریخ ارسال ==========
            $(document).on('change', '.delivery-date-radio', function() {
                let $radio = $(this);
                let groupId = $radio.data('group-id');
                let jalaliDate = $radio.data('jalali');

                selectedDates[groupId] = jalaliDate;

                // نمایش تاریخ انتخاب شده
                $(`#delivery-text-${groupId}`).text('ارسال در تاریخ ' + jalaliDate);

                // مخفی کردن انتخابگر تاریخ
                $(`#delivery-dates-${groupId}-${selectedCarriers[groupId]?.carrier_id}`).hide();
            });

            // ========== محاسبه مجموع ==========
            function updateTotals() {
                let subtotal = 0;
                let totalShipping = 0;

                @foreach($sellerGroups as $groupId => $group)
                    subtotal += {{ $group['total_price'] }};
                if (selectedCarriers['{{ $groupId }}']) {
                    totalShipping += selectedCarriers['{{ $groupId }}'].shipping_cost;
                } else {
                    totalShipping += {{ $group['shipping_cost'] }};
                }
                @endforeach

                let discount = 0;
                @if($discount_status['status'] && $cart->discount)
                    discount = {{ $cart->discount_amount ?? 0 }};
                @endif

                let finalPrice = subtotal + totalShipping - discount;

                $('#subtotal-price').text(number_format(subtotal));
                $('#shipping-total').text(number_format(totalShipping));
                $('#discount-amount').text(number_format(discount));
                $('#final-price').text(number_format(finalPrice));
            }

            // ========== ارسال فرم ==========
            $('#checkout-form').on('submit', function(e) {
                let shippingData = {
                    carriers: selectedCarriers,
                    delivery_dates: selectedDates
                };
                $('#selected_shipping_data').val(JSON.stringify(shippingData));
                return true;
            });

            // ========== تغییر آدرس ==========
            $(document).on('change', 'input[name="address"]', function() {
                // می‌توانید اینجا درخواست AJAX برای به‌روزرسانی روش‌های ارسال ارسال کنید
                console.log('Address changed, need to update shipping methods');
            });

            updateTotals();

            function number_format(number) {
                return new Intl.NumberFormat('fa-IR').format(number);
            }
        });*/
    </script>
@endpush
