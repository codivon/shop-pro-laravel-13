{{-- صفحه چک‌اوت — قرارداد کنترلر: $provinces, $discount_status, $gateways, $wallet, $cityId, $provinceId, $addresses, $sellerGroups, $downloadItems (+ $cart کامپوزر) --}}
@extends('front::layouts.master', ['title' => 'تکمیل خرید و پرداخت'])

@push('meta')
    <meta name="robots" content="noindex, follow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/checkout.css') }}">
@endpush

@section('content')
    {{-- بردکرامب --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('front.index') }}" class="font-14 text-muted">خانه</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('front.cart') }}" class="font-14 text-muted">سبد خرید</a></li>
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">تکمیل خرید</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            @include('front::carts.partials.steps', ['step' => 2, 'done' => 1])

            @php $hasPhysical = isset($sellerGroups) && count($sellerGroups) > 0; @endphp

            <form id="checkout-form" method="POST" action="{{ route('front.orders.store') }}"
                  data-price-action="{{ route('front.checkout.prices') }}">
                @csrf

                {{-- خطاهای ولیدیشن / نشست --}}
                @if(session('error'))
                    <div class="alert alert-danger rounded-3 font-14" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger rounded-3 font-14" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                {{-- کد تخفیف نامعتبر --}}
                @if(!$discount_status['status'])
                    <div class="alert alert-warning rounded-3 font-14" role="alert">
                        <i class="bi bi-patch-exclamation"></i> {{ $discount_status['message'] }}
                    </div>
                @endif

                {{-- خطاهای جاوااسکریپت (ولیدیشن سمت کلاینت) --}}
                <div id="checkout-js-errors" class="d-none">
                    <div class="alert alert-danger rounded-3 font-14" role="alert">
                        <ul class="mb-0"></ul>
                    </div>
                </div>

                <div class="check-out-form">
                    <div class="row gy-4">

                        {{-- ستون اصلی --}}
                        <div class="col-lg-8">

                            {{-- آدرس تحویل (محصولات فیزیکی) --}}
                            @if($hasPhysical)
                                @include('front::carts.partials.address-list', ['addresses' => $addresses])
                            @else
                                <div class="content-box mb-4">
                                    <div class="container-fluid py-3">
                                        <p class="font-14 mb-0">
                                            <i class="bi bi-download main-color-one-color"></i>
                                            تمام کالاهای این سفارش دانلودی هستند؛ بلافاصله پس از پرداخت در پنل کاربری قابل دریافت خواهند بود و نیازی به آدرس ارسال ندارند.
                                        </p>
                                    </div>
                                </div>
                            @endif

                            {{-- مرسولات و روش ارسال (محل جایگزینی AJAX) --}}
                            <div id="checkout-carrier-container">
                                @include('front::carts.partials.carriers-container', [
                                    'cart' => $cart,
                                    'cityId' => $cityId,
                                    'sellerGroups' => $sellerGroups,
                                    'downloadItems' => $downloadItems,
                                ])
                            </div>

                            {{-- یادداشت سفارش --}}
                            <div class="content-box mb-4">
                                <div class="container-fluid py-3">
                                    <label for="checkout-description" class="form-label font-16 fw-bold">
                                        <i class="bi bi-chat-left-text main-color-one-color ms-1"></i>یادداشت سفارش (اختیاری)
                                    </label>
                                    <textarea class="form-control rounded-3 mt-2" rows="3" id="checkout-description"
                                              name="description" maxlength="1000"
                                              placeholder="نکاتی در مورد سفارش خود دارید؟ مثلاً زمان یا نحوه ارسال..."></textarea>
                                </div>
                            </div>

                            {{-- بازگشت به سبد --}}
                            <a href="{{ route('front.cart') }}" class="btn btn-light rounded-3 px-4">
                                <i class="bi bi-arrow-right"></i> بازگشت به سبد خرید
                            </a>
                        </div>

                        {{-- ستون کنار: پرداخت + خلاصه سفارش --}}
                        <div class="col-lg-4">
                            <div class="checkout-side position-sticky top-0">

                                {{-- کد تخفیف (فرم حذف با form-attribute به فرم بیرونی وصل می‌شود تا nested form نشود) --}}
                                <div class="cart-canvases mb-4">
                                    <div class="item">
                                        @if($cart->discount)
                                            <div class="d-flex align-items-center justify-content-between py-2">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-ticket-perforated-fill main-color-one-color font-25 ms-2"></i>
                                                    <div class="d-flex flex-column">
                                                        <h6 class="font-14 mb-0">کد تخفیف «{{ $cart->discount->code }}» فعال است</h6>
                                                        <span class="font-12 text-muted">تخفیف در مبلغ قابل پرداخت اعمال شد</span>
                                                    </div>
                                                </div>
                                                <button type="submit" form="coupon-delete-form" class="btn btn-sm btn-light rounded-3 text-danger" aria-label="حذف کد تخفیف">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        @else
                                            <div class="show-discount-modal pointer py-2" data-bs-toggle="modal" data-bs-target="#discountModal">
                                                <i class="bi bi-patch-exclamation main-color-one-color me-1 font-25"></i>
                                                <span class="font-14">کوپن تخفیف دارید؟ برای نوشتن کد اینجا کلیک کنید</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- روش پرداخت --}}
                                @include('front::carts.partials.payment-method', ['gateways' => $gateways, 'wallet' => $wallet])

                                {{-- خلاصه سفارش (محل جایگزینی AJAX) --}}
                                <div id="checkout-sidebar-wrapper">
                                    @include('front::carts.partials.checkout-sidebar')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            {{-- فرم حذف کد تخفیف — عمداً بیرون از #checkout-form (جلوگیری از nested form) --}}
            @if($cart->discount)
                <form id="coupon-delete-form" action="{{ route('front.discount.destroy') }}" method="POST" class="d-none">
                    @csrf
                    @method('delete')
                </form>
            @endif
        </div>
    </div>

    {{-- مودال کد تخفیف (بیرون از فرم اصلی) --}}
    <div class="modal fade" id="discountModal" tabindex="-1" aria-hidden="true" aria-labelledby="discountModalLabel">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title font-16" id="discountModalLabel"><i class="bi bi-ticket-perforated main-color-one-color ms-1"></i>کد تخفیف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <form id="discount-create-form" action="{{ route('front.discount.store') }}" method="POST" novalidate>
                    @csrf
                    <div class="modal-body">
                        <p class="font-14 text-muted">در صورت داشتن کد تخفیف، آن را در کادر زیر وارد کنید.</p>
                        <input type="text" class="form-control rounded-3" name="code" id="discount"
                               placeholder="برای مثال eyd1402" autocomplete="off" required>
                        <div id="discount-error" class="font-12 text-danger mt-2 d-none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn border-0 main-color-one-bg rounded-3 px-4">
                            <span class="spinner-border spinner-border-sm d-none" id="discount-spinner" role="status" aria-hidden="true"></span>
                            ثبت کد تخفیف
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- مودال افزودن/ویرایش آدرس (بدون نقشه — فرم ساده سانیار) --}}
    @if($hasPhysical)
        <div class="modal fade" id="add-edit-address-modal" tabindex="-1" aria-hidden="true" aria-labelledby="add-edit-address-modal-label">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content rounded-3">
                    <div class="modal-header">
                        <h5 class="modal-title font-16" id="add-edit-address-modal-label">ثبت آدرس جدید</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                    </div>
                    <form id="add-update-address-form" method="POST" action="{{ route('front.addresses.store') }}" novalidate>
                        @csrf
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="comment-item">
                                        <input type="text" class="form-control rounded-3" id="address-fullname" name="fullname"
                                               placeholder="نام و نام خانوادگی گیرنده" required>
                                        <label class="label-float fw-bold font-14" for="address-fullname">نام گیرنده <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="comment-item">
                                        <input type="text" class="form-control rounded-3" id="address-mobile" name="mobile"
                                               placeholder="09xxxxxxxxx" inputmode="numeric" required>
                                        <label class="label-float fw-bold font-14" for="address-mobile">شماره همراه <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-14 fw-bold" for="province">استان <span class="text-danger">*</span></label>
                                    <select class="form-select rounded-3" id="province" name="province_id" required>
                                        <option value="">انتخاب استان...</option>
                                        @foreach($provinces as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-14 fw-bold" for="city">شهر <span class="text-danger">*</span></label>
                                    <select class="form-select rounded-3" id="city" name="city_id" required>
                                        <option value="">ابتدا استان را انتخاب کنید...</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <div class="comment-item">
                                        <input type="text" class="form-control rounded-3" id="address-postal" name="postal_code"
                                               placeholder="کد پستی ۱۰ رقمی" inputmode="numeric" required>
                                        <label class="label-float fw-bold font-14" for="address-postal">کد پستی <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="comment-item">
                                        <input type="text" class="form-control rounded-3" id="address-building" name="buildingNumber"
                                               placeholder="پلاک" inputmode="numeric" required>
                                        <label class="label-float fw-bold font-14" for="address-building">پلاک <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="comment-item">
                                        <input type="text" class="form-control rounded-3" id="address-unit" name="unit" placeholder="واحد (اختیاری)">
                                        <label class="label-float fw-bold font-14" for="address-unit">واحد</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="comment-item">
                                        <textarea class="form-control rounded-3 py-3" id="address" name="address" rows="3"
                                                  placeholder="آدرس کامل: خیابان، کوچه، ..." required></textarea>
                                        <label class="label-float fw-bold font-14" for="address">آدرس <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                                <input type="hidden" name="lat" value="">
                                <input type="hidden" name="lng" value="">
                            </div>
                            <div id="address-form-error" class="font-12 text-danger mt-2 d-none"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">انصراف</button>
                            <button type="submit" class="btn border-0 main-color-one-bg rounded-3 px-4" id="address-submit-btn">
                                ثبت آدرس
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/carts/checkout.js') }}"></script>
@endpush
