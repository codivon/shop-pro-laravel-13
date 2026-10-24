{{-- روش پرداخت — داخل فرم #checkout-form ؛ $gateways و $wallet از کنترلر --}}
@php
    $gateways = $gateways ?? collect();
    $wallet = $wallet ?? null;
    $firstPayableGateway = $gateways->first();
@endphp
<div class="cart-canvases mb-4">
    <div class="item">
        <div class="factor">
            <div class="title">
                <div class="d-flex align-items-center">
                    <i class="bi bi-credit-card-2-front-fill main-color-one-color font-25 ms-2"></i>
                    <h6 class="font-16 mb-0">انتخاب شیوه پرداخت</h6>
                </div>
            </div>
            <div class="container-fluid py-3 px-0">

                @if($wallet && $wallet->balance > 0)
                    <label class="bank-item d-flex align-items-center w-100 {{ $firstPayableGateway ? '' : 'active' }}">
                        <i class="bi bi-wallet2 main-color-one-color font-25"></i>
                        <span class="d-flex flex-column mx-2">
                            <h6 class="font-14 mb-0">پرداخت با کیف پول</h6>
                            <span id="wallet-balance" class="font-12 text-muted" data-value="{{ $wallet->balance }}">
                                موجودی: {{ number_format($wallet->balance) }} تومان
                            </span>
                        </span>
                        <input type="radio" class="d-none" name="gateway" id="wallet" value="wallet"
                               {{ $firstPayableGateway ? '' : 'checked' }}>
                    </label>
                @endif

                @foreach($gateways as $gateway)
                    <label class="bank-item d-flex align-items-center w-100 {{ $loop->first ? 'active' : '' }}">
                        <i class="bi bi-credit-card-2-back main-color-one-color font-25"></i>
                        <span class="d-flex flex-column mx-2">
                            <h6 class="font-14 mb-0">پرداخت اینترنتی {{ $gateway->name }}</h6>
                        </span>
                        <input type="radio" class="d-none" name="gateway" id="gateway-{{ $gateway->id }}"
                               value="{{ $gateway->key }}" {{ $loop->first ? 'checked' : '' }}>
                    </label>
                @endforeach
            </div>
        </div>
    </div>
</div>
