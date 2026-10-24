@extends('front::layouts.master', ['title' => $category->title])

@push('meta')
    <meta property="og:title" content="{{ $category->meta_title ?: $category->title }}">
    <meta property="og:url" content="{{ route('front.products.category', ['category' => $category]) }}">
    <meta property="og:image" content="{{ $category->image ? asset($category->image) : option('info_logo', theme_asset('image/logo.png')) }}">
    <meta name="description" content="{{ $category->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($category->description ?: $category->title), 160) }}">
    <meta name="keywords" content="{{ $category->getTags }}">
    <link rel="canonical" href="{{ route('front.products.category', ['category' => $category]) }}">
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
                            @foreach ($category->parents() as $parent)
                                <li class="breadcrumb-item">
                                    <a href="{{ route('front.products.category', ['category' => $parent]) }}" class="font-14 text-muted">{{ $parent->title }}</a>
                                </li>
                            @endforeach
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">{{ $category->title }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content py-20">
        <div class="container-fluid">
            {{-- هدر بخش با عنوان دسته --}}
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <span class="main-color-one-bg text-white rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-diagram-3-fill font-20"></i>
                    </span>
                    <h1 class="font-16 ms-3 mb-0">دسته‌بندی <span class="main-color-one-color d-inline-block ms-1">{{ $category->title }}</span></h1>
                </div>
            </div>

            {{-- زیردسته‌ها (این ویو فقط وقتی زیردسته منتشر دارد رندر می‌شود) --}}
            <div class="row g-3">
                @foreach ($category->childrenCategories()->published()->orderBy('ordering')->get() as $child)
                    <div class="col-lg-3 col-md-4 col-6">
                        <div class="content-box h-100">
                            <div class="container-fluid text-center">
                                <a href="{{ route('front.products.category', ['category' => $child]) }}" class="d-block text-decoration-none py-3">
                                    <div class="saniar-result-card border-0">
                                        <div class="src-thumb">
                                            <img src="{{ $child->image ? asset($child->image) : asset('no-image-product.svg') }}"
                                                 loading="lazy"
                                                 alt="{{ $child->title }}"
                                                 width="84" height="84">
                                        </div>
                                        <div class="src-title">{{ $child->title }}</div>
                                        <div class="src-sub">مشاهده محصولات دسته</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- توضیحات دسته (برای SEO) --}}
            @if ($category->description)
                <div class="content-box mt-4">
                    <div class="container-fluid p-4">
                        <h2 class="font-16 mb-3">درباره {{ $category->title }}</h2>
                        <div class="text-muted font-14 line-height-2">{!! $category->description !!}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
