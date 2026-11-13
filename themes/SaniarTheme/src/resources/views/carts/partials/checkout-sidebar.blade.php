<div id="checkout-sidebar" class="page-aside " style="{{@$mt}}" >
    <div class="checkout-summary">
        <ul class="checkout-summary-summary">
            <li>
                <span>مبلغ کل ({{ $cart->quantity }} کالا)</span>
                <span>{{ number_format($subtotal ?? $cart->priceWithoutDiscount()) }} تومان</span>
            </li>
            @if(($discount ?? $cart->totalDiscount()) > 0)
                <li class="checkout-summary-discount">
                    <span>تخفیف</span>
                    <span> {{ number_format($discount ?? $cart->totalDiscount()) }} تومان</span>
                </li>
            @endif

            {{-- هزینه ارسال تفکیک شده --}}
            @if(isset($sellerShippingCosts) && count($sellerShippingCosts) > 0)
                <ul>
                    <li class="checkout-summary-shipping-item border-0">
                        <span class="shipping-seller-name">هزینه ارسال</span>
                        <span class="shipping-cost"></span>
                    </li>
                    @foreach($sellerShippingCosts as $sellerName => $cost)
                        @if($cost > 0)
                            <li class="checkout-summary-shipping-item">
                                <span class="shipping-seller-name">{{ $sellerName }}</span>
                                <span class="shipping-cost">{{ number_format($cost) }} تومان</span>
                            </li>
                        @endif
                    @endforeach
                    <li class="checkout-summary-shipping-total border-0">
                        <span class="font-weight-bold">جمع هزینه ارسال</span>
                        <span class="font-weight-bold">{{ number_format($totalShippingCost ?? 0) }} تومان</span>
                    </li>
                </ul>
            @elseif(isset($totalShippingCost) && $totalShippingCost > 0)
                <li>
                    <span style="color: #424750; font-size:14px;">هزینه ارسال</span>
                    <span>{{ number_format($totalShippingCost) }} تومان</span>
                </li>
            @endif

           {{-- <li class="checkout-summary-final-divider mt-2 border-0">
                <hr>
            </li>--}}


            {{-- Hook برای نمایش اطلاعات اقساط در sidebar — فاز ۸۸: پکیج ادغامی CreditPay جایگزین پکیج قدیمی اقساطی شد --}}
            @if(function_exists('module_is_active') && module_is_active('CreditPay'))
                @include('credit-pay::front.installment_sidebar_hook')
            @endif

            @if(function_exists('module_is_active') && module_is_active('CreditPay'))
                @include('credit-pay::front.sidebar_hook')
            @endif
            <li class="last-price-tag">
                <span class="font-weight-bold">مبلغ قابل پرداخت</span>
                <span id="final-price" class="checkout-summary-price-value-amount checkout_link font-weight-bold" data-value="{{ $finalPrice ?? $cart->finalPrice() }}">
            {{ number_format($finalPrice ?? $cart->finalPrice()) }} تومان
                </span>
            </li>


            <li>
                <div class="checkout-to-shipping-sticky">
                    <span id="checkout-link" data-toggle="tooltip" data-html="true" data-placement="bottom" data-action="{{ route('front.cart') }}" data-redirect="{{ route('front.checkout') }}" class="selenium-next-step-shipping checkout_link">ادامه فرآیند خرید</span>
                </div>
            </li>


        </ul>


    </div>
    <div class="checkout-summary-content">
        <p>کالاهای موجود در سبد شما ثبت و رزرو نشده‌اند، برای ثبت سفارش مراحل بعدی را تکمیل کنید.</p>
    </div>
</div>
