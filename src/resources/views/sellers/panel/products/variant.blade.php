{{-- مدیریت تنوع و قیمت‌گذاری محصول — سانیار فاز H
    قرارداد ProductSellerController@variant : $product, $attributeGroups
    ثبت از طریق variant_store (POST) → ریدایرکت سروری به لیست محصولات + توست سشن --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'مدیریت تنوع', 'active' => 'products'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=2">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush

@section('seller-content')
    {{-- سربرگ --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-sliders main-color-one-color ms-1"></i>مدیریت تنوع و قیمت‌گذاری</h5>
                    <p class="font-13 text-muted mb-0">فعال/غیرفعال کردن تنوع‌ها، تغییر قیمت، موجودی و اعمال تخفیف از این بخش امکان‌پذیر است.</p>
                </div>
                <a href="{{ route('seller.products.find') }}" class="btn btn-outline-secondary rounded-3 font-14">
                    <i class="bi bi-arrow-right ms-1"></i>بازگشت به جستجو
                </a>
            </div>
        </div>
    </div>

    <form id="product-variant-form" method="post"
          action="{{ route('seller.products.variant.store', ['product' => $product]) }}">
        @csrf

        {{-- کارت محصول --}}
        <div class="content-box mb-3">
            <div class="container-fluid">
                <div class="row g-3 py-3">
                    <div class="col-md-3 col-5">
                        <img src="{{ $product->image ? asset($product->image) : asset('/no-image-product.svg') }}"
                             alt="{{ $product->title }}" class="img-fluid rounded-3 w-100"
                             style="max-height:180px;object-fit:contain;">
                    </div>
                    <div class="col-md-9 col-7">
                        <h6 class="fw-bold mb-2">{{ $product->title }}</h6>
                        <ul class="list-unstyled mb-0 font-13">
                            <li class="d-flex justify-content-between border-bottom py-1">
                                <span class="text-muted">دسته‌بندی:</span>
                                <span class="fw-bold">{{ $product->category->title }}</span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-1">
                                <span class="text-muted">برند کالا:</span>
                                <span class="fw-bold">{{ $product->brand ? $product->brand->name : 'بدون برند' }}</span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-1">
                                <span class="text-muted">کمترین قیمت:</span>
                                <span class="fw-bold">{{ number_format($product->lowestPrice->discount_price ?? 0) }} تومان</span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span class="text-muted">کمیسیون فروش این کالا:</span>
                                <span class="fw-bold">{{ $product->category->commission ? $product->category->commission.'٪' : 'بدون کمیسیون' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- هشدار تطابق مشخصات --}}
        <div class="alert alert-warning border-0 font-13 mb-3 d-flex gap-2" role="alert">
            <i class="bi bi-exclamation-triangle mt-1"></i>
            <span>لطفاً قبل از درج تنوع، مشخصات فنی کالا (رنگ، ابعاد، اقلام همراه و…) را در سایت بررسی کنید و مطمئن شوید تنوع شما با آن مطابقت دارد. عدم تطابق موجب مرجوعی سفارش‌ها و غیرفعال‌شدن کالا خواهد شد.</span>
        </div>

        {{-- تنوع‌های من --}}
        <div class="content-box">
            <div class="container-fluid">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                    <h2 class="font-15 mb-0"><i class="bi bi-cash-stack main-color-one-color me-1"></i>تنوع‌های من برای این کالا</h2>
                    <button type="button" class="btn btn-sm main-color-one-bg text-white rounded-3 font-13" id="sfp-add-price">
                        <i class="bi bi-plus-lg ms-1"></i>افزودن تنوع
                    </button>
                </div>

                <div id="product-prices-div" class="d-grid gap-3 pb-3"
                     data-count="{{ $product->isPhysical() ? count($product->prices_seller) : 0 }}">
                    @if ($product->isPhysical())
                        @foreach ($product->prices_seller as $price)
                            @include('front::sellers.panel.products.partials.prices-include', ['price' => $price, 'pi' => $loop->iteration])
                        @endforeach
                    @endif
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2 pb-3">
                    <button type="submit" class="btn main-color-one-bg text-white rounded-3 font-14 px-4">
                        <i class="bi bi-check2-circle ms-1"></i>
                        @if (count($product->prices_seller))
                            ویرایش لیست تنوع
                        @else
                            فروش این محصول و افزودن به لیست تنوع
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </form>

    @include('front::sellers.panel.products.partials.prices-template')
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-products.js') }}?v=1"
            data-saniar-products="variant"></script>
@endpush
