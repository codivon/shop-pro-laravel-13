{{-- ویجت «کادر محصولات با پس زمینه» سانیار — فاز B --}}
{{-- قرارداد میزبان: block_color (رنگ پس‌زمینه) + image (تصویر ستون کنار) + link/link_title داینامیک از پنل --}}
@php
    $variables   = get_widget($widget);
    $products    = $variables['products'] ?? collect();
    $block_color = $widget->option('block_color') ?: '#ef394e';
    $promo_image = $widget->option('image');
@endphp
@if($products->count())
    <div class="colorful-block py-20" style="background: {{ $block_color }};">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">
                        {{ $widget->option('title', 'پیشنهاد ویژه') }}
                    </h5>
                </div>
                <div class="link">
                    @if($widget->option('link'))
                        <a href="{{ $widget->option('link') }}" class="border-animate fromCenter pb-1 fw-bold">
                            {{ $widget->option('link_title', 'مشاهده همه') }}
                            <i class="bi bi-chevron-double-left main-color-one-color"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="row gy-3 align-items-stretch">
                <div class="{{ $promo_image ? 'col-lg-9' : 'col-lg-12' }}">
                    <div class="swiper product-slider-swiper">
                        <div class="swiper-wrapper">
                            @foreach($products as $product)
                                <div class="swiper-slide">
                                    @include('front::products.partials.product-card', ['product' => $product])
                                </div>
                            @endforeach
                        </div>
                        <div class="swiper-button-next d-lg-flex d-none"></div>
                        <div class="swiper-button-prev d-lg-flex d-none"></div>
                    </div>
                </div>

                @if($promo_image)
                    <div class="col-lg-3 d-none d-lg-flex">
                        <div class="colorful-promo rounded-3 shadow-box p-4 w-100 d-flex flex-column align-items-center justify-content-center text-center">
                            <div class="image mb-4">
                                <img src="{{ asset($promo_image) }}" class="img-fluid"
                                     alt="{{ $widget->option('title', 'پیشنهاد ویژه') }}">
                            </div>
                            @if($widget->option('link'))
                                <a href="{{ $widget->option('link') }}"
                                   class="btn border-0 rounded-3 main-color-one-bg text-white px-4 fw-bold">
                                    {{ $widget->option('link_title', 'مشاهده همه') }}
                                    <i class="bi bi-chevron-double-left"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
