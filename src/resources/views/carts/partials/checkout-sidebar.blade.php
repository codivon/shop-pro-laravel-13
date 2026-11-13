<div id="checkout-sidebar" class="position-sticky top-0" style="{{ @$mt }}">
    <div class="cart-canvases">
        <div class="item">
            <div class="factor checkout-summary page-aside">
                <div class="title">
                    <div class="d-flex align-items-center">
                        <img src="{{ theme_asset('image/shopping-bag.png') }}" class="img-fluid" alt="">
                        <h6 class="font-16">سفارش شما</h6>
                    </div>
                </div>
                <div class="d-flex mb-3 align-items-center justify-content-between">
                    <p class="fw-bold mb-0">محصول</p>
                    <p class="fw-bold mb-0">قیمت کل</p>
                </div>

                <div class="factor-item p-2 rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-between">
                    <p class="mb-0">مبلغ کل ({{ $cart->quantity }} کالا)</p>
                    <p class="mb-0">{{ number_format($subtotal ?? $cart->priceWithoutDiscount()) }} تومان</p>
                </div>

                @if(($discount ?? $cart->totalDiscount()) > 0)
                    <div class="factor-item p-2 rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-between">
                        <p class="mb-0 fw-bold">تخفیف:</p>
                        <p class="mb-0 text-danger">{{ number_format($discount ?? $cart->totalDiscount()) }} تومان</p>
                    </div>
                @endif

                {{-- هزینه ارسال تفکیک شده --}}
                @if(isset($sellerShippingCosts) && count($sellerShippingCosts) > 0)
                    @foreach($sellerShippingCosts as $sellerName => $cost)
                        @if($cost > 0)
                            <div class="factor-item p-2 rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-between">
                                <p class="mb-0 font-12">هزینه ارسال {{ $sellerName }}</p>
                                <p class="mb-0 font-12">{{ number_format($cost) }} تومان</p>
                            </div>
                        @endif
                    @endforeach
                    <div class="factor-item p-2 rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-between">
                        <p class="mb-0 fw-bold">جمع هزینه ارسال</p>
                        <p class="mb-0 fw-bold">{{ number_format($totalShippingCost ?? 0) }} تومان</p>
                    </div>
                @elseif(isset($totalShippingCost) && $totalShippingCost > 0)
                    <div class="factor-item p-2 rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-between">
                        <p class="mb-0">هزینه ارسال</p>
                        <p class="mb-0">{{ number_format($totalShippingCost) }} تومان</p>
                    </div>
                @endif

                {{-- Hook برای نمایش اطلاعات اقساط در sidebar — فاز ۸۸: پکیج ادغامی CreditPay جایگزین پکیج قدیمی اقساطی شد --}}
                @if(function_exists('module_is_active') && module_is_active('CreditPay'))
                    @include('credit-pay::front.installment_sidebar_hook')
                @endif

                @if(function_exists('module_is_active') && module_is_active('CreditPay'))
                    @include('credit-pay::front.sidebar_hook')
                @endif

                <div class="factor-item p-2 rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-between last-price-tag">
                    <p class="mb-0 fw-bold">مبلغ قابل پرداخت</p>
                    <p class="mb-0 fw-bold" id="final-price"
                        data-value="{{ $finalPrice ?? $cart->finalPrice() }}">
                        {{ number_format($finalPrice ?? $cart->finalPrice()) }} تومان
                    </p>
                </div>

                <div class="action mt-3 d-flex align-items-center justify-content-center">
                    <a href="javascript:void(0)" id="checkout-link"
                        class="btn border-0 main-color-one-bg rounded-3 d-block w-100 checkout_link selenium-next-step-shipping"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true"
                        title="کالاهای موجود در سبد شما ثبت و رزرو نشده‌اند، برای ثبت سفارش مراحل بعدی را تکمیل کنید."
                        data-action="{{ route('front.cart') }}" data-redirect="{{ route('front.checkout') }}">
                        تسویه حساب
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="checkout-summary-content mt-3">
        <p class="font-12 text-muted mb-0">
            کالاهای موجود در سبد شما ثبت و رزرو نشده‌اند، برای ثبت سفارش مراحل بعدی را تکمیل کنید.
        </p>
    </div>
</div>
