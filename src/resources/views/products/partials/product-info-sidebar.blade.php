{{-- باکس اقدام خرید — مارکاپ سانیار product-meta-action --}}
{{-- فاز ۱۵۶ — ردیف فروشنده/عملکرد/شناسه به ستون چپ منتقل شد (پارشال product-seller-aside) --}}
{{-- هوک‌های JS (show.js): .product-seller-info / .product-seller-row > button[data-price_id] / #price-real .product-seller-price-prev --}}
@if ($product->addableToCart() && $selected_price)

    <div class="product-seller-info mt-4">
        <div class="js-seller-info-changable product-actions">

            @php
                /*
                | فاز ۷۸/۸۱/۸۳ — هشدار کاهش قیمت
                | متن/آیکون/رنگ/اندازه/گردی/موقعیت همه از «تنظیمات دیگر» سایت
                | (تب قابلیت‌های فروشگاه) قابل شخصی‌سازی است.
                */
                $paEnabled    = option('dt_price_alert_status', '1') == '1';
                $paText       = option('dt_price_alert_button_text', 'هشدار کاهش قیمت') ?: 'هشدار کاهش قیمت';
                $paIcon       = option('dt_price_alert_button_icon', 'mdi mdi-bell-ring-outline') ?: 'mdi mdi-bell-ring-outline';
                $paColor      = option('dt_price_alert_button_color', 'red') ?: 'red';
                $paSize       = option('dt_price_alert_button_size', 'md') ?: 'md';
                $paRadius     = (int) (option('dt_price_alert_button_radius', '10') ?: 10);
                $paPlacement  = option('dt_price_alert_button_placement', 'below-cart') ?: 'below-cart';

                // فاز ۸۴ — موقعیت دکمهٔ شناور (۹گانه) + سلکتورهای «تگ دلخواه»
                $paFloatPos   = (string) (option('dt_price_alert_float_pos', 'bottom-right') ?: 'bottom-right');
                if (! in_array($paFloatPos, ['top-left', 'top-center', 'top-right', 'middle-left', 'middle-center', 'middle-right', 'bottom-left', 'bottom-center', 'bottom-right'], true)) {
                    $paFloatPos = 'bottom-right';
                }

                $paSelectors  = [];
                $paSelRaw     = trim((string) option('dt_price_alert_selectors', ''));
                if ($paSelRaw !== '') {
                    $decoded     = str_starts_with($paSelRaw, '[') ? json_decode($paSelRaw, true) : preg_split('/[\r\n,]+/', $paSelRaw);
                    $paSelectors = collect(is_array($decoded) ? $decoded : [])
                        ->map(fn ($s) => trim(preg_replace('/[^A-Za-z0-9\-_\.\#\[\]\:"\' >+~*(),=\$]/', '', (string) $s)))
                        ->filter()->unique()->take(10)->values()->all();
                }

                $paBtnHtml    = '<button type="button" id="dt_price_alert_btn" class="dt-pa-btn dt-pa-btn--'.e($paSize).' dt-pa-btn--'.e($paColor).'" style="--dt-pa-radius: '.e((string) $paRadius).'px">'
                    .'<i class="'.e($paIcon).'" aria-hidden="true"></i><span>'.e($paText).'</span></button>';
            @endphp

            @if($paEnabled && $paPlacement === 'above-cart')
                {!! $paBtnHtml !!}
            @endif

            <div class="product-seller-row product-meta-action">
                <div class="row align-items-center gy-3">
                    <div class="col-lg-4 col-6 w-100-in-400">
                        @if ($selected_price->hasDiscount())
                            <div class="product-seller-price-real text-danger">
                                <del class="product-seller-price-prev font-size-20">{{ number_format($selected_price->regularPrice()) }}</del>
                                تومان
                                <div class="discount show-discount">
                                    <span>{{ $selected_price->discount() }}٪</span>
                                </div>
                            </div>
                        @else
                            <div class="product-seller-price-label text-muted font-13">
                                @if($selected_price->seller)
                                    قیمت فروشنده
                                @else
                                    قیمت
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-4 col-6 w-100-in-400">
                        <h6 class="font-16 new-price main-color-one-color mb-0 product-seller-price-real" id="price-real">
                            <span class="product-seller-price-prev">{{ number_format($selected_price->salePrice()) }}</span>
                            تومان
                        </h6>
                    </div>
                    <div class="col-lg-4 col-12">
                        <button data-price_id="{{ $selected_price->id }}"
                                data-action="{{ route('front.cart.store', ['product' => $product]) }}"
                                data-product="{{ $product->slug }}" type="button"
                                class="btn-add-to-cart w-100 btn border-0 main-color-three-bg btn-show-product add-to-cart">
                            <i class="bi bi-basket text-white font-20 me-1"></i>
                            {{ $product->pre_order && option('dt_pre_order_status', '1') == '1' ? option('dt_pre_order_button_text', 'ثبت پیش‌فروش') : 'افزودن به سبد خرید' }}
                        </button>
                    </div>
                </div>

                @if($paEnabled && $paPlacement !== 'above-cart')
                    @if($paPlacement === 'under-price')
                        <div class="dt-pa-under-price">{!! $paBtnHtml !!}</div>
                    @elseif($paPlacement === 'float')
                        {{-- فاز ۸۴ — دکمهٔ شناور (fixed) در گوشهٔ انتخابی صفحه --}}
                        <div class="dt-pa-float dt-pa-float--{{ e($paFloatPos) }}">{!! $paBtnHtml !!}</div>
                    @elseif($paPlacement === 'custom-selector')
                        {{-- فاز ۸۴ — تزریق در تگ دلخواه --}}
                        <span id="dt_pa_inject_config" data-selectors="{{ json_encode($paSelectors) }}" hidden aria-hidden="true"></span>
                        {!! str_replace('<button type="button" id="dt_price_alert_btn"', '<button type="button" id="dt_price_alert_btn" hidden', $paBtnHtml) !!}
                    @else
                        <div class="mt-2">{!! $paBtnHtml !!}</div>
                    @endif
                @endif
            </div>
        </div>
    </div>

@elseif (!$product->addableToCart() || !$selected_price)
    <div class="product-seller-info mt-4">
        <div class="product-stock-status content-box p-4 rounded-3 shadow-box text-center">
            <div class="product-stock-title mb-2">
                <span class="badge bg-secondary rounded-pill px-3 py-2 font-14">ناموجود</span>
            </div>
            <div class="product-stock-body text-muted font-14 mb-3">
                متاسفانه این کالا در حال حاضر موجود نیست. می‌توانید از طریق لیست بالای
                صفحه، از محصولات مشابه این کالا دیدن نمایید
            </div>
            <button id="stock_notify_btn" data-user="{{ auth('web')->check() ? auth('web')->user()->id : '' }}"
                    data-product="{{ $product->id }}"
                    class="product-stock-action btn border-0 main-color-one-bg rounded-pill" type="button">
                <i class="bi bi-info-circle"></i>
                موجود شد به من اطلاع بده
            </button>
        </div>
    </div>
@endif
