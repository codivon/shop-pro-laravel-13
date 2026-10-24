@extends('front::layouts.master', ['title' => 'فروشگاه'])

@push('meta')
    <meta name="description" content="{{ option('info_short_description') }}">
    <meta name="keywords" content="{{ option('info_tags') }}">
    <link rel="canonical" href="{{ route('front.products.index') }}">
    <meta property="og:title" content="فروشگاه {{ option('info_site_title', config('front.demo.name')) }}">
    <meta property="og:url" content="{{ route('front.products.index') }}">
@endpush

@section('content')
    {{-- بردکرامب سانیار --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('front.index') }}" class="font-14 text-muted">خانه</a>
                            </li>
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">فروشگاه</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- هدر بخش --}}
    <div class="content py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <span class="main-color-one-bg text-white rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-grid-fill font-20"></i>
                    </span>
                    <h1 class="font-16 ms-3 mb-0">دسته‌بندی <span class="main-color-one-color d-inline-block ms-1">محصولات</span></h1>
                </div>
                <div class="link">
                    <a href="{{ route('front.products.specials') }}" class="border-animate fromCenter pb-1 fw-bold">
                        پیشنهادهای ویژه
                        <i class="bi bi-chevron-double-left main-color-one-color"></i>
                    </a>
                </div>
            </div>

            @if($categories->count())
                <div class="row g-3">
                    @foreach($categories as $category)
                        <div class="col-lg-3 col-md-4 col-6">
                            <div class="content-box h-100">
                                <div class="container-fluid text-center">
                                    <a href="{{ route('front.products.category', ['category' => $category]) }}" class="d-block text-decoration-none py-3">
                                        <div class="saniar-result-card border-0">
                                            <div class="src-thumb">
                                                <img src="{{ $category->image ? asset($category->image) : asset('no-image-product.svg') }}"
                                                     loading="lazy"
                                                     alt="{{ $category->title }}"
                                                     width="84" height="84">
                                            </div>
                                            <div class="src-title">{{ $category->title }}</div>
                                            <div class="src-sub">مشاهده محصولات دسته</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                @include('front::partials.empty', ['message' => 'دسته‌بندی‌ای برای نمایش وجود ندارد'])
            @endif
        </div>
    </div>
@endsection
