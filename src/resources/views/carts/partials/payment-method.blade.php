<div class="content-box rounded-3">
    <div class="container-fluid p-3 p-lg-4">
        {{-- فاز ۱۷۰ — عنوان «انتخاب شیوه پرداخت» همیشه بالای کارت‌ها باشد --}}
        <div class="checkout-form-title d-flex align-items-center mb-3">
            <i class="bi bi-credit-card-2-front-fill me-2 main-color-one-color font-20"></i>
            <h5 class="mb-0">انتخاب شیوه پرداخت</h5>
        </div>

        {{-- پرداخت اقساطی / اعتباری (پکیج CreditPay) — فاز ۱۷۱: در یک ناحیهٔ جدا
             با فاصلهٔ مشخص از درگاه‌های آنلاین قرار می‌گیرد --}}
        @if(module_is_ready('CreditPay', ['installment_excluded_products']))
            <div class="snr-credit-pay-area">
                @include('credit-pay::front.installment_checkout_hook', ['cart' => $cart])
            </div>
        @endif

        @if(module_is_ready('CreditPay', ['credit_product_markups']))
            <div class="snr-credit-pay-area">
                @include('credit-pay::front.checkout_hook', ['cart' => $cart])
            </div>
        @endif

        {{-- کیف پول --}}
        @if ($wallet->balance)
            <div class="wallet-select mb-3">
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

        {{-- فاز ۱۷۱ — درگاه‌های پرداخت آنلاین به‌صورت کارت‌های تصویری کنار هم
             (با تصویر درگاه اگر موجود بود، در غیر این صورت آیکون پیش‌فرض) --}}
        <div class="snr-gateways-head d-flex align-items-center justify-content-between flex-wrap gap-1">
            <h6 class="mb-0 font-14 fw-bold">پرداخت اینترنتی</h6>
            <span class="font-12 text-muted">آنلاین با کلیه کارت‌های عضو شتاب</span>
        </div>

        <div class="row g-2 g-md-3 snr-gateway-grid">
            @foreach ($gateways as $gateway)
                @php
                    /* فاز ۱۷۱ — تصویر درگاه: اگر مدل Gateway ستون تصویر داشت و فایل
                       واقعاً روی دیسک (یا لینک کامل) بود نمایش داده می‌شود؛ وگرنه
                       آیکون پیش‌فرض کارت بانکی. بدون خطای SQL حتی اگر ستون نبود. */
                    $gwImage = trim((string) ($gateway->image ?? $gateway->logo ?? ''));
                    $gwUrl = '';
                    if ($gwImage !== '') {
                        if (\Illuminate\Support\Str::startsWith($gwImage, ['http://', 'https://'])) {
                            $gwUrl = $gwImage;
                        } else {
                            $gwAbs = public_path('uploads/' . ltrim($gwImage, '/'));
                            if (is_file($gwAbs)) {
                                $gwUrl = asset('uploads/' . ltrim($gwImage, '/'));
                            }
                        }
                    }
                @endphp
                <div class="col-6 col-md-4 gateway-slot">
                    <input type="radio" class="btn-check" name="gateway" id="{{ $gateway->key }}"
                        value="{{ $gateway->key }}" {{ $loop->first ? 'checked' : '' }} autocomplete="off">
                    <label for="{{ $gateway->key }}" class="snr-gateway-card bank-item w-100 h-100 mb-0 cursor-pointer">
                        <span class="snr-gateway-logo">
                            @if($gwUrl)
                                <img src="{{ $gwUrl }}" alt="{{ $gateway->name }}" loading="lazy">
                            @else
                                <i class="bi bi-credit-card-2-back" aria-hidden="true"></i>
                            @endif
                        </span>
                        <span class="snr-gateway-name">{{ $gateway->name }}</span>
                        <span class="snr-gateway-sub">درگاه پرداخت اینترنتی</span>
                    </label>
                </div>
            @endforeach

            @if(function_exists('module_is_active') && module_is_active('DigiPay'))
                <div class="col-12">
                    @include('digipay::front.checkout_hook', ['cart' => $cart])
                </div>
            @endif
        </div>

        {{-- فاز ۱۷۱ — کد تخفیف: زیر درگاه‌های پرداخت و جدا با hr (نمونهٔ تصویر سوم کاربر)
             نکته: داخل فرم چک‌اوت هستیم؛ فرم تو در تو مجاز نیست، پس ثبت/حذف کد
             با ایجکسی و از طریق دکمه انجام می‌شود (js/pages/checkout.js) --}}
        <hr class="snr-discount-divider my-4">

        <div id="discount-inline-box"
            data-store-url="{{ route('front.discount.store') }}"
            @if($cart->discount) data-remove-url="{{ route('front.discount.destroy') }}" @endif>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-ticket-perforated main-color-one-color font-18" aria-hidden="true"></i>
                    <span class="fw-bold font-14">کد تخفیف</span>
                </div>
                @if(!$cart->discount)
                    <label class="snr-switch mb-0" for="discount-toggle">
                        <input type="checkbox" id="discount-toggle"
                            data-bs-toggle="collapse" data-bs-target="#discount-inline-body"
                            aria-expanded="false" aria-controls="discount-inline-body">
                        <span class="snr-switch-slider" aria-hidden="true"></span>
                    </label>
                @endif
            </div>

            <div id="discount-inline-body" class="collapse @if($cart->discount) show @endif">
                @if($cart->discount)
                    <div class="snr-discount-applied d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-patch-check-fill main-color-one-color me-1 font-18"></i>
                            <span class="font-14">کد تخفیف ثبت شده:</span>
                            <strong class="ms-1 font-14">{{ $cart->discount->code }}</strong>
                        </div>
                        <button type="button" id="discount-inline-remove" class="btn btn-sm btn-danger rounded-3">
                            حذف کد تخفیف
                        </button>
                    </div>
                @else
                    <div class="d-flex align-items-center flex-wrap gap-2 mt-3 snr-discount-form-row">
                        <input type="text" id="discount-inline-code" class="form-control rounded-3"
                            placeholder="کد تخفیف جدید را وارد کنید" autocomplete="off">
                        <button type="button" id="discount-inline-submit"
                            class="btn main-color-one-bg border-0 rounded-3 px-4 flex-shrink-0">ثبت</button>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
