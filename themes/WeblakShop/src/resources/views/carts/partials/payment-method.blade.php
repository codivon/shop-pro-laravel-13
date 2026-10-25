@php
    $carrier_found = false;
@endphp

<div  class=" shopping-page">
    {{-- Hook برای پرداخت اقساطی — فاز ۸۸: پکیج ادغامی CreditPay (پرداخت قسطی و اعتباری) جایگزین پکیج قدیمی اقساطی شد --}}
    @if(module_is_ready('CreditPay', ['installment_excluded_products']))
        @include('credit-pay::front.installment_checkout_hook', ['cart' => $cart])
    @endif

    @if(module_is_ready('CreditPay', ['credit_product_markups']))
        @include('credit-pay::front.checkout_hook', ['cart' => $cart])
    @endif



    <div class="w-100 display-inline-block pt-3 pb-3">
        <div class="checkout-pack-row checkout-pack mb-0">
            <div class="checkout-pack-header">
                <span>انتخاب شیوه پرداخت</span>
            </div>

             <div class="row checkout-time-table checkout-time-table-time d-flex">




                        @if ($wallet->balance)
                                <div class="col-12 wallet-select">
                                    <div class="radio-box custom-control custom-radio pl-0 pr-3">
                                        <input type="radio" class="custom-control-input" name="gateway" id="wallet" value="wallet">
                                        <label for="wallet" class="custom-control-label">
                                            <i class="mdi mdi-credit-card-multiple-outline checkout-additional-options-checkbox-image"></i>
                                            <div class="content-box">
                                                <div class="checkout-time-table-title-bar checkout-time-table-title-bar-city">
                                                    <span class="increase-balance">پرداخت با کیف پول</span>
                                                </div>
                                                <ul class="checkout-time-table-subtitle-bar">
                                                    <li id="wallet-balance" data-value="{{ $wallet->balance }}">
                                                     موجودی:    {{ number_format($wallet->balance) .' تومان  '}}
                                                    </li>
                                                </ul>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            @endif

                            @foreach ($gateways as $gateway)

                                <div class="col-12">
                                    <div class="radio-box custom-control custom-radio pl-0 pr-3">
                                        <input type="radio" class="custom-control-input" name="gateway" id="{{ $gateway->key }}" value="{{ $gateway->key }}" {{ $loop->first ? 'checked' : '' }}>
                                        <label for="{{ $gateway->key }}" class="custom-control-label">
                                            <i class="mdi mdi-credit-card-outline checkout-additional-options-checkbox-image"></i>
                                            <div class="content-box">
                                                <div class="checkout-time-table-title-bar checkout-time-table-title-bar-city">
                                                    پرداخت اینترنتی {{ $gateway->name }}
                                                </div>
                                                <ul class="checkout-time-table-subtitle-bar">
                                                    <li>
                                                        آنلاین با تمامی کارت‌های بانکی
                                                    </li>
                                                </ul>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                            @endforeach

                            @if(function_exists('module_is_active') && module_is_active('DigiPay'))
                                      @include('digipay::front.checkout_hook', ['cart' => $cart])
                             @endif
                        </div>



        </div>
    </div>




</div>
