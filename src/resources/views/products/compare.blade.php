@extends('front::layouts.master', ['title' => 'مقایسه محصول'])

@push('meta')
    <meta name="robots" content="noindex, nofollow" />
    <meta name="googlebot" content="noindex">
@endpush

@section('content')
    <!-- Start main-content -->

    <main class="main-content">
        <div class="container-fluid">

            <!-- start breadcroumb -->
            <div class="bread-crumb pt-3">
                <div class="container-fluid">
                    <div class="content-box">
                        <div class="container-fluid">
                            <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{ url('/') }}" class="font-14 text-muted">خانه</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('front.products.index') }}" class="font-14 text-muted">محصولات</a></li>
                                    <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">مقایسه محصول</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end breadcroumb -->

            <div class="content py-20">
                <div class="container-fluid">
                    <div class="content-box">
                        <div class="container-fluid">

                            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                                <div class="title d-flex align-items-center">
                                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                                    <h5 class="font-16 ms-3">مقایسه <span class="main-color-one-color d-inline-block">محصول</span></h5>
                                </div>
                            </div>

                            <div class="compare shadow-box rounded-3 p-3 js-product">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="table-responsive">
                                            <table class="table table-bordered fixed compare-table">
                                                <thead>
                                                    <tr>
                                                        <th>
                                                            @if ($products->count() < 3)
                                                                <div class="compare-add">
                                                                    <div class="compare-add-product">
                                                                        <div class="cap-icon">
                                                                            <i class="bi bi-box-arrow-down"></i>
                                                                        </div>
                                                                        <div class="cap-title">
                                                                            <p class="text-muted mb-0">برای افزودن محصول کلیک کنید</p>
                                                                        </div>
                                                                        <div class="cap-btn">
                                                                            <button type="button" class="btn border-0 main-color-one-bg"
                                                                                    data-bs-toggle="modal" data-bs-target="#compareModal">افزودن کالا
                                                                                به مقایسه</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </th>
                                                        @foreach ($products as $product)
                                                            <th>
                                                                <div class="product-box compare-product-box">
                                                                    <a href="{{ route('front.products.show', ['product' => $product]) }}">
                                                                        <div class="product-image">
                                                                            <img src="{{ $product->image ? asset($product->image) : asset('/no-image-product.svg') }}"
                                                                                 loading="lazy" alt="{{ $product->title }}" class="img-fluid">
                                                                        </div>
                                                                    </a>
                                                                    <div class="product-title">
                                                                        <div class="title">
                                                                            <p class="text-overflow-1">{{ $product->title }}</p>
                                                                            <span class="text-muted text-overflow-1">{{ $product->category->title ?? '' }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="compare-box">
                                                                    <div class="compare-delete">
                                                                        @if ($products->count() > 1)
                                                                            <a href="{{ remove_id_from_url($product->id) }}" class="hint--top" data-hint="حذف از مقایسه"><i class="bi bi-x-circle-fill"></i></a>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </th>
                                                        @endforeach

                                                    </tr>
                                                </thead>
                                                <tbody>

                                                    @foreach ($groups as $group)
                                                        <tr>
                                                            <td colspan="{{ $products->count() + ($products->count() < 3 ? 1 : 0) }}" class="td-head">
                                                                <i class="bi bi-chevron-double-left"></i>
                                                                <span>{{ $group->name }}</span>
                                                            </td>
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
                                                                <td class="fw-bold">{{ $spec->name }}</td>

                                                                @foreach ($products as $product)
                                                                    @php
                                                                        $specification_value = $specifications
                                                                            ->where('specification_id', $spec->id)
                                                                            ->where('product_id', $product->id)
                                                                            ->first();
                                                                    @endphp
                                                                    <td>{{ $specification_value ? $specification_value->value : '-' }}</td>
                                                                @endforeach

                                                            </tr>
                                                        @endforeach
                                                    @endforeach

                                                    <tr>
                                                        <td></td>
                                                        @foreach ($products as $product)
                                                            <td>
                                                                <a class="btn w-100 border-0 main-color-one-bg" href="{{ route('front.products.show', ['product' => $product]) }}">مشاهده و خرید</a>
                                                            </td>
                                                        @endforeach

                                                    </tr>
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

        </div>
    </main>
    <!-- End main-content -->

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
