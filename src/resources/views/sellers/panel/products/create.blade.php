{{-- درج محصول جدید — سانیار فاز H
    قرارداد ProductSellerController@create :
    $categories, $specTypes, $sizetypes, $attributeGroups, $currencies, $sellers, $brands, $copy_product
    ذخیره از طریق StoreProductRequest → پاسخ متنی «success» + توست سشن --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'درج محصول', 'active' => 'products-find'])

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
                    <h5 class="fw-bold mb-1"><i class="bi bi-plus-square main-color-one-color ms-1"></i>درج محصول جدید</h5>
                    <p class="font-13 text-muted mb-0">
                        @if ($copy_product)
                            در حال کپی از «{{ $copy_product->title }}» هستید؛ اطلاعات قابل ویرایش‌اند.
                        @else
                            اطلاعات محصول‌تان را در بخش‌های مختلف این فرم وارد کنید.
                        @endif
                    </p>
                </div>
                <a href="{{ route('seller.products.index') }}" class="btn btn-outline-secondary rounded-3 font-14">
                    <i class="bi bi-arrow-right ms-1"></i>بازگشت به لیست
                </a>
            </div>
        </div>
    </div>

    <form class="form" id="product-create-form" action="{{ route('seller.products.store') }}" method="post"
          enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="rounding_amount" value="no">
        <input type="hidden" name="rounding_type" value="default">

        @include('front::sellers.panel.products.partials.form-body', ['mode' => 'create'])

        <div class="content-box mt-3">
            <div class="container-fluid">
                <div class="d-flex flex-wrap justify-content-end gap-2 py-3">
                    <button type="submit" class="btn main-color-one-bg text-white rounded-3 font-14 px-4" id="sfp-submit-btn">
                        <span class="saniar-btn-spinner spinner-border spinner-border-sm d-none ms-1" role="status" aria-hidden="true"></span>
                        <i class="bi bi-check2-circle ms-1"></i>ایجاد محصول
                    </button>
                </div>
            </div>
        </div>
    </form>

    @include('front::sellers.panel.products.partials.prices-template')
    @include('front::sellers.panel.products.partials.files-template')
    @include('front::sellers.panel.products.partials.specification-template')
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-products.js') }}?v=1"
            data-saniar-products="form"
            data-redirect="{{ route('seller.products.index') }}"></script>
@endpush
