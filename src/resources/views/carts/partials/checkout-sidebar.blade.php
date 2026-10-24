{{-- سایدبار سفارش (سبد خرید + چک‌اوت) — قرارداد getPrices: این پارشال با کلید checkout_sidebar رندر و جایگزین می‌شود
    متغیرها (همه اختیاری جز $cart که با کامپوزر تزریق می‌شود):
    $subtotal / $discount / $totalShippingCost / $sellerShippingCosts / $finalPrice  ← از AJAX
    بدون AJAX: مقادیر از خود مدل Cart محاسبه می‌شود
--}}
<div id="checkout-sidebar">
    <div class="item">
        <div class="factor">
            <div class="title">
                <div class="d-flex align-items-center">
                    <img src="{{ theme_asset('image/shopping-bag.png') }}" class="img-fluid" alt="">
                    <h6 class="font-16 mb-0">سفارش شما</h6>
                </div>
            </div>

            <div class="d-flex mb-3 align-items-center justify-content-between">
                <p class="fw-bold mb-0">شرح</p>
                <p class="fw-bold mb-0">مبلغ</p>
            </div>

            @php
                // فاز D (۵۳): با داده real (regular_price=NULL) متدهای میزبان خراب‌اند؛ helperهای سانیار جایگزین شدند
                $sidebar_subtotal = $subtotal ?? saniar_cart_price_without_discount($cart);
                $sidebar_discount = $discount ?? saniar_cart_discount($cart);
                $sidebar_final = $finalPrice ?? saniar_cart_final_price($cart);
            @endphp

            <div class="factor-item p-2 rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-between">
                <p class="mb-0">مبلغ کل ({{ $cart->quantity }} کالا)</p>
                <p class="mb-0">{{ number_format($sidebar_subtotal) }} تومان</p>
            </div>

            @if($sidebar_discount > 0)
                <div class="factor-item p-2 rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-between">
                    <p class="mb-0">تخفیف</p>
                    <p class="mb-0 text-danger">{{ number_format($sidebar_discount) }} تومان</p>
                </div>
            @endif

            @if(isset($sellerShippingCosts) && count($sellerShippingCosts) > 0)
                @foreach($sellerShippingCosts as $sellerName => $cost)
                    <div class="factor-item p-2 rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-between">
                        <p class="mb-0">هزینه ارسال {{ $sellerName }}</p>
                        <p class="mb-0">{{ number_format($cost) }} تومان</p>
                    </div>
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

            <div class="factor-item p-2 rounded-3 shadow-sm d-flex align-items-center justify-content-between">
                <p class="mb-0 fw-bold">مبلغ قابل پرداخت</p>
                <p class="mb-0 fw-bold main-color-one-color">
                    <span id="final-price" data-value="{{ $sidebar_final }}">{{ number_format($sidebar_final) }}</span>
                    تومان
                </p>
            </div>

            <div class="action mt-3 d-flex align-items-center justify-content-center">
                <button type="button" id="checkout-link" class="btn border-0 main-color-one-bg rounded-3 d-block w-100 waves-effect waves-light"
                        data-action="{{ route('front.cart') }}" data-redirect="{{ route('front.checkout') }}">
                    {{ request()->routeIs('front.checkout*') ? 'تایید و ثبت سفارش' : 'ادامه فرآیند خرید' }}
                </button>
            </div>

            <p class="font-12 text-muted mt-3 mb-0 text-center">
                کالاهای موجود در سبد شما ثبت و رزرو نشده‌اند و ممکن است در پایان پرداخت، موجودی آنها به اتمام برسد.
            </p>
        </div>
    </div>
</div>
