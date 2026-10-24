@php
    $title = 'صفحه مورد نظر یافت نشد';
    // سانیار فاز K — ۴۰۴ اختصاصی‌تر: جستجوی نجات + دسترسی سریع + محصولات ویژهٔ
    // داینامیک (کوئری سبک و کش‌شده) به‌جای تصویر استاتیک تنها
    $specialProducts = \Illuminate\Support\Facades\Cache::remember('front.404.special_products', 600, function () {
        return \App\Models\Product::detectLang()
            ->published()
            ->special()
            ->with(['lowestPrice', 'category:id,title,slug'])
            ->latest()
            ->limit(4)
            ->get();
    });
@endphp
@extends('front::layouts.master', ['robots' => 'noindex, follow'])

@push('meta')
    <meta name="description" content="صفحه مورد نظر یافت نشد — به نظر می‌رسد این صفحه حذف شده یا آدرس آن تغییر کرده است.">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/content.css') }}">
@endpush

@section('content')
    <div class="py-3">
        <div class="container-fluid">

            {{-- هدر ۴۰۴ + جستجوی نجات --}}
            <div class="content-box">
                <div class="container-fluid text-center py-5">
                    <span class="notfound-code main-color-one-color fw-bold">۴۰۴</span>
                    <h1 class="fw-bold font-20 mt-2 mb-3">صفحه مورد نظر یافت نشد!</h1>
                    <p class="text-muted font-14 mb-4">
                        به نظر می‌رسد این صفحه حذف شده یا آدرس آن تغییر کرده است.
                        می‌توانید در کادر زیر جستجو کنید یا از دسترسی‌های سریع استفاده نمایید.
                    </p>
                    <div class="row justify-content-center">
                        <div class="col-lg-5 col-md-8">
                            <form action="{{ route('front.products.index') }}" method="GET" role="search" class="blog-search-form">
                                <div class="search-filed">
                                    <input type="text" name="q" class="form-control search-input"
                                           placeholder="جستجوی محصولات ..." autocomplete="off" aria-label="جستجوی محصولات">
                                    <button type="submit" class="btn search-btn main-color-one-bg rounded-pill" aria-label="جستجو">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                        <a href="{{ route('front.index') }}" wire:navigate class="btn main-color-one-bg text-white border-0 rounded-pill px-4">
                            <i class="bi bi-house me-1"></i>صفحه اصلی
                        </a>
                        <a href="{{ route('front.products.index') }}" wire:navigate class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-basket me-1"></i>محصولات
                        </a>
                        <a href="{{ route('front.articles.index') }}" wire:navigate class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-journal-text me-1"></i>مقالات
                        </a>
                        <a href="{{ route('front.contact.index') }}" wire:navigate class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-headset me-1"></i>تماس با ما
                        </a>
                    </div>
                </div>
            </div>

            {{-- محصولات ویژه (پیشنهاد به‌جای صفحهٔ گمشده) --}}
            @if($specialProducts->isNotEmpty())
                <div class="content-box mt-3">
                    <div class="container-fluid">
                        <div class="title-panel mb-3 mt-2">
                            <h6 class="font-16 mb-0">شاید این <span class="main-color-one-color">پیشنهادها</span> به کارتان بیاید</h6>
                        </div>
                        <div class="row gy-3 pb-3">
                            @foreach($specialProducts as $product)
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="blog-item">
                                        <a href="{{ route('front.products.show', ['product' => $product->slug]) }}" wire:navigate>
                                            <div class="image">
                                                <img src="{{ $product->image ? asset($product->image) : asset('no-image-product.svg') }}"
                                                     alt="{{ $product->name }}" class="img-fluid" loading="lazy">
                                            </div>
                                            <div class="title">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div class="title-item">
                                                        <i class="bi bi-tag me-1"></i>
                                                        <span class="font-12">{{ $product->category?->title ?? 'فروشگاه' }}</span>
                                                    </div>
                                                    @if($product->lowestPrice)
                                                        <div class="title-item">
                                                            <span class="font-12 main-color-one-color fw-bold">{{ number_format($product->lowestPrice->price) }} تومان</span>
                                                        </div>
                                                    @endif
                                                </div>
                                                <h4 class="font-14 text-overflow-2 h4 mt-1">{{ $product->name }}</h4>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection
