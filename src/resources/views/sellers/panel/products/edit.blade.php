{{-- ویرایش محصول فروشنده — سانیار فاز H
    قرارداد ProductSellerController@edit :
    $product, $categories, $specTypes, $sizetypes, $attributeGroups, $currencies, $AbilityScores, $sellers, $brands
    به‌روزرسانی از طریق UpdateProductRequest → پاسخ متنی «success» + توست سشن --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'ویرایش محصول', 'active' => 'products'])

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
                    <h5 class="fw-bold mb-1"><i class="bi bi-pencil-square main-color-one-color ms-1"></i>ویرایش محصول</h5>
                    <p class="font-13 text-muted mb-0">
                        «{{ $product->title }}» — پس از ذخیره، محصول تا تایید مجدد مدیر به حالت انتظار می‌رود.
                        @if ($product->status == 'Accept')
                            <span class="badge rounded-pill success-label ms-1">تایید شده</span>
                        @elseif ($product->status == 'Waiting')
                            <span class="badge rounded-pill warning-label ms-1">در انتظار تایید</span>
                        @else
                            <span class="badge rounded-pill danger-label ms-1">رد شده</span>
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('front.products.show', ['product' => $product]) }}" target="_blank"
                       class="btn btn-outline-secondary rounded-3 font-14">
                        <i class="bi bi-eye ms-1"></i>مشاهده در فروشگاه
                    </a>
                    <a href="{{ route('seller.products.index') }}" class="btn btn-outline-secondary rounded-3 font-14">
                        <i class="bi bi-arrow-right ms-1"></i>بازگشت
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form class="form" id="product-edit-form" action="{{ route('seller.products.update', ['product' => $product]) }}"
          method="post" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="rounding_amount" value="no">
        <input type="hidden" name="rounding_type" value="default">

        @include('front::sellers.panel.products.partials.form-body', ['mode' => 'edit'])

        <div class="content-box mt-3">
            <div class="container-fluid">
                <div class="d-flex flex-wrap justify-content-end gap-2 py-3">
                    <button type="submit" class="btn main-color-one-bg text-white rounded-3 font-14 px-4" id="sfp-submit-btn">
                        <span class="saniar-btn-spinner spinner-border spinner-border-sm d-none ms-1" role="status" aria-hidden="true"></span>
                        <i class="bi bi-check2-circle ms-1"></i>ذخیره تغییرات
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
