<div class="content-box rounded-3">
    <div class="container-fluid p-3 p-lg-4">
        {{-- Hook برای پرداخت اقساطی — فاز ۸۸: پکیج ادغامی CreditPay (پرداخت قسطی و اعتباری) جایگزین پکیج قدیمی اقساطی شد --}}
        @if(module_is_ready('CreditPay', ['installment_excluded_products']))
            @include('credit-pay::front.installment_checkout_hook', ['cart' => $cart])
        @endif

        @if(module_is_ready('CreditPay', ['credit_product_markups']))
            @include('credit-pay::front.checkout_hook', ['cart' => $cart])
        @endif

        <div class="checkout-form-title d-flex align-items-center mb-3">
            <i class="bi bi-credit-card-2-front-fill me-2 main-color-one-color font-20"></i>
            <h5 class="mb-0">انتخاب شیوه پرداخت</h5>
        </div>

        <div class="row gy-2">
            @if ($wallet->balance)
                <div class="col-12 wallet-select">
                    <input type="radio" class="btn-check" name="gateway" id="wallet" value="wallet" autocomplete="off">
                    <label for="wallet" class="bank-item w-100 mb-0 cursor-pointer">
                        <i class="bi bi-wallet2 main-color-one-color font-18"></i>
                        <h6 class="font-14 mx-2 mb-0">پرداخت با کیف پول</h6>
                        <span class="has-balance font-12 mb-0 text-success" style="display: none;">موجودی کیف پول برای این پرداخت کافی است</span>
                        <a href="{{ route('front.wallet.index') }}"
                            class="increase-balance font-12 mb-0 text-danger" style="display: none;">موجودی کیف پول کافی نیست؛ افزایش موجودی</a>
                        <p class="mb-0 text-muted font-12" id="wallet-balance"
                            data-value="{{ $wallet->balance }}">
                            موجودی: {{ number_format($wallet->balance) }} تومان
                        </p>
                    </label>
                </div>
            @endif

            @foreach ($gateways as $gateway)
                <div class="col-12">
                    <input type="radio" class="btn-check" name="gateway" id="{{ $gateway->key }}"
                        value="{{ $gateway->key }}" {{ $loop->first ? 'checked' : '' }} autocomplete="off">
                    <label for="{{ $gateway->key }}" class="bank-item w-100 mb-0 cursor-pointer">
                        <i class="bi bi-credit-card-2-back main-color-one-color font-18"></i>
                        <h6 class="font-14 mx-2 mb-0">پرداخت اینترنتی {{ $gateway->name }}</h6>
                        <p class="mb-0 text-muted font-12">
                            آنلاین با تمامی کارت‌های بانکی
                        </p>
                    </label>
                </div>
            @endforeach

            @if(function_exists('module_is_active') && module_is_active('DigiPay'))
                @include('digipay::front.checkout_hook', ['cart' => $cart])
            @endif
        </div>
    </div>
</div>
