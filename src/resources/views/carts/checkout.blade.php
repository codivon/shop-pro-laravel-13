@extends('front::layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/vendor/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/mapir/css/mapp.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/mapir/css/fa/style.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/map-selected-styles.css') }}" />
    <link rel="stylesheet" href="{{ theme_asset('css/checkout.css') }}?v=170" />
@endpush

@section('content')
    <!-- Start main-content -->
    <main class="main-content">

        <!-- start breadcroumb -->
        <div class="bread-crumb pt-3">
            <div class="container-fluid">
                <div class="content-box">
                    <div class="container-fluid">
                        <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('front.index') }}"
                                        class="font-14 text-muted">خانه</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('front.cart') }}"
                                        class="font-14 text-muted">سبد خرید</a></li>
                                <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">
                                    جزییات پرداخت</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- end breadcroumb -->

        <!-- start content -->
        <div class="content">
            <div class="container-fluid">

                <div class="line-step-container d-sm-block d-none">
                    <div class="line-step">
                        <div class="line-step-boxs">
                            <div class="line-step-box complete">
                                <a href="{{ route('front.cart') }}">
                                    <p>سبد خرید</p>
                                    <div class="icon">1</div>
                                </a>
                            </div>
                            <div class="line-step-box complete">
                                <a href="javascript:void(0)">
                                    <p>جزییات پرداخت</p>
                                    <div class="icon">2</div>
                                </a>
                            </div>
                            <div class="line-step-box disabled">
                                <a href="javascript:void(0)">
                                    <p>تکمیل سفارش</p>
                                    <div class="icon">3</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="container-fluid">
                <div class="check-out-form">

                    {{-- کد تخفیف --}}
                    @if ($cart->discount)
                        <div class="content-box rounded-3 p-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-patch-check-fill main-color-one-color me-1 font-20"></i>
                                <span class="font-14">کد تخفیف ثبت شده:</span>
                                <strong class="ms-1 font-14">{{ $cart->discount->code }}</strong>
                            </div>
                            <form action="{{ route('front.discount.destroy') }}" method="POST" class="mb-0">
                                @csrf
                                @method('delete')
                                <button type="submit" class="btn btn-sm btn-danger rounded-3">حذف کد تخفیف</button>
                            </form>
                        </div>
                    @else
                        <div class="show-discount-modal pointer py-3 mb-3" data-bs-toggle="modal"
                            data-bs-target="#discountModal">
                            <i class="bi bi-patch-exclamation main-color-one-color me-1" style="font-size: 40px;"></i>
                            کوپن تخفیف دارید برای نوشتن کد اینجا کلیک کنید
                        </div>
                    @endif

                    <div class="row gy-3">
                        {{-- فاز ۱۷۰ — سایدبار «سفارش شما» کوچک‌تر شد (col-lg-3) تا محصولات مرسوله کنار هم ۲تایی جا شوند --}}
                        <div class="col-lg-9 block-div">
                            <form id="checkout-form" data-price-action="{{ route('front.checkout.prices') }}"
                                action="{{ route('front.orders.store') }}" class="setting_form" method="POST">
                                @csrf

                                @if(session('error'))
                                    <div class="alert alert-danger">
                                        <p class="mb-0">{{ session('error') }}</p>
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        @foreach ($errors->all() as $error)
                                            <p class="mb-1">{{ $error }}</p>
                                        @endforeach
                                    </div>
                                @endif

                                @if(!$discount_status['status'])
                                    <div class="alert alert-danger" role="alert">
                                        <p class="mb-0">کد تخفیف وارد شده معتبر نیست. {{ $discount_status['message'] }}</p>
                                    </div>
                                @endif

                                <div class="content-box rounded-3 mb-4">
                                    <div class="container-fluid p-3 p-lg-4">

                                        <div class="checkout-form-title d-flex align-items-center mb-3">
                                            <i class="bi bi-geo-alt-fill me-2 main-color-one-color font-20"></i>
                                            <h5 class="mb-0">اطلاعات ارسال</h5>
                                        </div>

                                        @php
                                            $hasPhysical = isset($sellerGroups) && count($sellerGroups) > 0;
                                        @endphp

                                        @if($hasPhysical)
                                            @include('front::carts.partials.address-list', ['addresses' => $addresses])
                                        @else
                                            {{-- پیام برای محصولات دانلودی --}}
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle me-1"></i>
                                                محصولات شما دانلودی هستند و نیازی به ثبت آدرس ندارید.
                                            </div>
                                        @endif

                                        {{-- نکته: id="checkout-carrier-container" روی ریشه‌ی خود پارشال است؛
                                             چون js/pages/checkout.js بعد از هر AJAX کل آن را replaceWith می‌کند و
                                             برای اینکه id بعد از جایگزینی هم باقی بماند باید داخل HTML پاسخ باشد. --}}
                                        @include('front::carts.partials.carriers-container', ['cart' => $cart, 'sellerGroups' => $sellerGroups])

                                    </div>
                                </div>

                                @include('front::carts.partials.payment-method', ['cart' => $cart])
                            </form>

                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                                <a href="{{ route('front.cart') }}" class="btn btn-light rounded-3 font-14">
                                    <i class="bi bi-arrow-right me-1"></i>
                                    بازگشت به سبد خرید
                                </a>
                                <button id="checkout-link"
                                    class="save-shipping-data checkout_link selenium-next-step-shipping cursor-pointer btn main-color-one-bg border-0 rounded-3"
                                    data-action="{{ route('front.cart') }}" data-redirect="{{ route('front.checkout') }}">
                                    تایید و ادامه ثبت سفارش
                                    <i class="bi bi-arrow-left ms-1"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="checkout-side position-sticky top-0">
                                @include('front::carts.partials.checkout-sidebar')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- end content -->
    </main>
    <!-- End main-content -->

    {{-- مودال ثبت / ویرایش آدرس + نقشه --}}
    {{-- data-store-url: فاز ۱۶۱ — مسیر ذخیرهٔ آدرس برای بازنشانی اکشن فرم
         هنگام بستن مودال (رفع خطای ۴۰۵ پس از خالی‌شدن اکشن) --}}
    <div class="modal fade" id="add-edit-address-modal" tabindex="-1" aria-hidden="true"
        data-store-url="{{ route('front.addresses.store') }}">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div id="showMap" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        آدرس جدید
                        <div class="font-12 text-muted mt-1">موقعیت مکانی آدرس را مشخص کنید.</div>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            @include('front::carts.partials.map')
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-sm-between position-relative">
                    <p class="pt-4 mb-0 font-12">مرسوله‌های شما به این موقعیت ارسال خواهد شد.</p>
                    <div>
                        <button id="next-add-address-btn" class="btn main-color-one-bg border-0 rounded-3 disabled"
                            disabled>
                            <i class="bi bi-check2 me-1"></i>
                            تایید و ادامه
                        </button>
                    </div>
                </div>
            </div>
            <div id="more-information" class="modal-content d-none">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <a id="back-to-map" class="cursor-pointer font-14 text-muted">
                            <i class="bi bi-arrow-right me-1"></i>بازگشت
                        </a>
                        <span class="mx-2">|</span>
                        جزییات آدرس
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <form id="add-update-address-form" action="{{ route('front.addresses.store') }}"
                                class="setting_form" method="POST">
                                @csrf

                                <div class="more-information">

                                    <div class="row">
                                        <div class="col-12">
                                            <label for="address" class="form-label">نشانی پستی
                                                <span class="text-danger">*</span></label>
                                            <textarea id="address" name="address" class="form-control rounded-3 mb-2"
                                                placeholder="آدرس خود را وارد نمایید" style="height:80px;"></textarea>
                                            <input type="hidden" id="lat" name="lat">
                                            <input type="hidden" id="lng" name="lng">
                                            <p class="font-12 text-muted">آدرس بالا بر اساس موقعیت انتخابی شما وارد شده
                                                است.</p>
                                        </div>
                                    </div>
                                    <hr>
                                    @php
                                        $cities = [];
                                        $city_id = null;
                                    @endphp
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="province" class="form-label">استان
                                                <span class="text-danger">*</span></label>
                                            <select class="form-select rounded-3" name="province_id" id="province">
                                                <option value="" selected="selected">شهر مورد نظر خود را انتخاب کنید
                                                </option>
                                                @foreach($provinces as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="city" class="form-label">شهر
                                                <span class="text-danger">*</span></label>
                                            <select class="form-select rounded-3" name="city_id" id="city">
                                                <option value="" selected="selected">شهر مورد نظر خود را انتخاب کنید
                                                </option>
                                                @foreach($cities as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="row">
                                                <div class="col-6">
                                                    <label for="buildingNumber" class="form-label">پلاک<span
                                                            class="text-danger">*</span></label>
                                                    <input type="number" name="buildingNumber" id="buildingNumber"
                                                        class="form-control rounded-3" placeholder="پلاک">
                                                </div>
                                                <div class="col-6">
                                                    <label for="unit" class="form-label">واحد</label>
                                                    <input type="text" name="unit" id="unit"
                                                        class="form-control rounded-3" placeholder="واحد">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="postalCode" class="form-label">کد پستی<span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="postal_code" id="postalCode"
                                                class="form-control rounded-3"
                                                placeholder="کد‌پستی باید ۱۰ رقم و بدون خط تیره باشد.">
                                        </div>
                                    </div>

                                    <hr>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">نام و نام خانوادگی تحویل گیرنده <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="name" name="fullname"
                                                class="form-control rounded-3" placeholder="نام تحویل گیرنده را وارد نمایید">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="phone-number" class="form-label">شماره موبایل <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="phone-number" name="mobile"
                                                class="form-control rounded-3" placeholder="09xxxxxxxxx"
                                                style="text-align:left;direction: ltr">
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-3 mt-4 mb-2">
                                        <button type="submit" class="btn main-color-one-bg border-0 rounded-3">
                                            ثبت آدرس
                                            <i class="bi bi-check2 me-1"></i>
                                        </button>
                                        <a class="cancel-edit-address cursor-pointer font-14 text-muted"
                                            data-bs-dismiss="modal">انصراف و بازگشت</a>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- مودال کد تخفیف --}}
    @if (!$cart->discount)
        <div class="discount-modal">
            <div class="modal fade" id="discountModal" tabindex="-1" aria-labelledby="discountModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="discountModalLabel">کد تخفیف</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <h4 class="font-16 mb-3">
                                اگر شما کد تخفیف دارید ، برای ثبت آن از طریق زیر اقدام کنید.
                            </h4>
                            <form id="discount-create-form" action="{{ route('front.discount.store') }}"
                                class="mb-0">
                                @csrf
                                <div class="form-group mb-3">
                                    <label for="discount-code" class="form-label">کد تخفیف:</label>
                                    <input type="text" name="code" class="form-control rounded-3"
                                        placeholder="برای مثال eyd1402" id="discount-code" required>
                                </div>
                                <button type="submit" class="btn main-color-one-bg border-0 rounded-3">ثبت کد
                                    تخفیف</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <input name="map_api" type="hidden" value="{{ option('map_api') }}">
@endsection

@push('scripts-top-js')
    <script src="{{ theme_asset('js/plugins/map.ir/mapp.env.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/map.ir/mapp.min.js') }}"></script>
@endpush

@push('scripts')
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>
    <script src="{{ theme_asset('js/jquery.nice-select.min.js') }}"></script>
    <script src="{{ theme_asset('js/pages/addresses/map-select-scripts.js') }}?v=161"></script>
    <script src="{{ theme_asset('js/pages/carts/checkout.js') }}?v=161"></script>
    <script src="{{ theme_asset('js/pages/checkout.js') }}?v=11"></script>
    <script src="{{ theme_asset('js/pages/addresses/index.js') }}?v=161"></script>
    <script src="{{ theme_asset('js/pages/addresses/add-edit-address.js?v=2') }}"></script>
@endpush
