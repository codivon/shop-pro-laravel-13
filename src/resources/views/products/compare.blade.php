@extends('front::layouts.master', ['title' => 'مقایسه محصولات'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex">
@endpush

@section('content')
    {{-- بردکرامب سانیار --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('front.index') }}" class="font-14 text-muted">خانه</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('front.products.index') }}" class="font-14 text-muted">فروشگاه</a></li>
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">مقایسه محصولات</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- هدر بخش --}}
    <div class="content pt-3">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <span class="main-color-one-bg text-white rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-shuffle font-20"></i>
                    </span>
                    <h1 class="font-16 ms-3 mb-0">مقایسه <span class="main-color-one-color d-inline-block ms-1">محصولات</span></h1>
                </div>
            </div>

            <div class="content-box">
                <div class="container-fluid">
                    <div class="compare">
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle compare-table bg-white">
                                        <thead>
                                            <tr>
                                                <th style="min-width:150px;">
                                                    @if ($products->count() < 3)
                                                        <div class="text-center p-3">
                                                            <button type="button" class="btn btn-light rounded-circle mb-2 p-3"
                                                                    data-bs-toggle="modal" data-bs-target="#compareModal"
                                                                    aria-label="افزودن محصول برای مقایسه">
                                                                <i class="bi bi-plus-lg font-20"></i>
                                                            </button>
                                                            <button type="button" class="btn main-color-one-bg text-white rounded-3 d-block w-100"
                                                                    data-bs-toggle="modal" data-bs-target="#compareModal">
                                                                افزودن محصول
                                                            </button>
                                                        </div>
                                                    @endif
                                                </th>
                                                @foreach ($products as $product)
                                                    @php
                                                        $cmp_lowest = $product->prices()->where('stock', '>', 0)->orderBy('price')->first();
                                                    @endphp
                                                    <th style="min-width:220px;">
                                                        <div class="product-box compare-product-box text-center p-2 position-relative">
                                                            @if ($products->count() > 1)
                                                                <a href="{{ remove_id_from_url($product->id) }}" class="position-absolute top-0 end-0 m-1 text-danger" aria-label="حذف از مقایسه">
                                                                    <i class="bi bi-x-circle"></i>
                                                                </a>
                                                            @endif
                                                            <a href="{{ route('front.products.show', ['product' => $product]) }}" class="d-block text-decoration-none">
                                                                <div class="product-image">
                                                                    <img src="{{ $product->image ? asset($product->image) : asset('no-image-product.svg') }}"
                                                                         loading="lazy" alt="{{ $product->title }}" class="img-fluid" style="max-height:140px;object-fit:contain;">
                                                                </div>
                                                                <div class="product-title">
                                                                    <div class="title">
                                                                        <p class="text-overflow-2 font-13 text-dark mb-1">{{ $product->title }}</p>
                                                                        @if($cmp_lowest)
                                                                            <span class="main-color-one-color fw-bold font-14">{{ number_format($cmp_lowest->salePrice()) }} تومان</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </a>
                                                            <a href="{{ route('front.products.show', ['product' => $product]) }}"
                                                               class="btn btn-sm border-0 main-color-one-bg text-white rounded-3 w-100 mt-2">
                                                                مشاهده و خرید
                                                            </a>
                                                        </div>
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($groups as $group)
                                                <tr class="table-light">
                                                    <th class="font-14" colspan="{{ $products->count() + 1 }}">{{ $group->name }}</th>
                                                </tr>
                                                @php
                                                    $specifications = \DB::table('product_specification')
                                                        ->where('specification_group_id', $group->id)
                                                        ->whereIn('product_id', $products->pluck('id'))
                                                        ->orderBy('group_ordering')
                                                        ->get();
                                                @endphp
                                                @foreach ($specifications->unique('specification_id') as $specification)
                                                    @php
                                                        $spec = \DB::table('specifications')->find($specification->specification_id);
                                                    @endphp
                                                    <tr>
                                                        <th class="font-13">{{ $spec->name }}</th>
                                                        @foreach ($products as $product)
                                                            @php
                                                                $specification_value = $specifications
                                                                    ->where('specification_id', $spec->id)
                                                                    ->where('product_id', $product->id)
                                                                    ->first();
                                                            @endphp
                                                            <td class="font-13">{{ $specification_value ? $specification_value->value : '—' }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($products->count() < 3)
        @php
            $similar_products = \App\Models\Product::whereNotIn('id', $products->pluck('id'))
                ->where('spec_type_id', $products->first()->spec_type_id)
                ->orderByStock()
                ->take(12)
                ->get();
        @endphp

        @include('front::products.partials.compare-modal', [
            'similar_products' => $similar_products,
            'current_url' => url()->current(),
        ])
    @endif
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/products/compare.js') }}"></script>
@endpush
