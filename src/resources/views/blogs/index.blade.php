@php
    // سانیار فاز K — لندینگ وبلاگ: ویجت‌محور (پنل ادمین چیدمان را کنترل می‌کند)
    // + بخش‌های پیش‌فرض کاملاً داینامیک برای وقتی که هیچ ویجتی برای صفحهٔ
    // «posts» تنظیم نشده باشد (نصب تازه) — هیچ محتوای ثابتی رندر نمی‌شود.
    $hasWidgets = $widgets->isNotEmpty();

    $fallbackLatest   = collect();
    $fallbackPopular  = collect();
    $fallbackCats     = collect();
    if (! $hasWidgets) {
        $fallbackLatest  = \App\Models\Post::with('admin:id,first_name,last_name,image', 'category:id,title,slug')
            ->published()->latest()->limit(9)->get();
        $fallbackPopular = \App\Models\Post::with('category:id,title,slug')
            ->published()->orderByDesc('view')->limit(10)->get();
        $fallbackCats    = \App\Models\Category::published()->where('type', 'postcat')
            ->whereNull('category_id')->orderBy('ordering')->limit(8)->get();
    }
@endphp
@extends('front::layouts.master', [
    'title' => 'وبلاگ',
    'description' => 'وبلاگ '.option('info_site_title', config('front.demo.name')).' — جدیدترین مقالات، آموزش‌ها و اخبار دنیای تکنولوژی',
])

@push('meta')
    <link rel="canonical" href="{{ route('front.blog.index') }}">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/blog.css') }}">
@endpush

@section('content')

    {{-- bredcrumb --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('front.index') }}" wire:navigate class="font-14 text-muted">خانه</a>
                            </li>
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">وبلاگ</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- سربرگ وبلاگ: جستجو + دسترسی سریع به آرشیو --}}
    <div class="blog-hero-box py-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid py-3">
                    <div class="row align-items-center gy-3">
                        <div class="col-lg-5">
                            <h1 class="font-20 fw-bold mb-2">وبلاگ <span class="main-color-one-color">{{ option('info_site_title', config('front.demo.name')) }}</span></h1>
                            <p class="text-muted font-14 mb-0">جدیدترین مقالات، بررسی‌ها و آموزش‌های تخصصی</p>
                        </div>
                        <div class="col-lg-4 col-md-8">
                            <form action="{{ route('front.blog.search') }}" method="GET" role="search" class="blog-search-form">
                                <div class="search-filed">
                                    <input type="text" name="q" value="{{ request('q') }}" class="form-control search-input"
                                           placeholder="جستجو در مقالات ..." autocomplete="off" aria-label="جستجو در مقالات">
                                    <button type="submit" class="btn search-btn main-color-one-bg rounded-pill" aria-label="جستجو">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="col-lg-3 col-md-4 text-lg-start text-center">
                            <a href="{{ route('front.articles.index') }}" wire:navigate
                               class="btn btn-outline-secondary rounded-pill px-4 blog-archive-btn">
                                <i class="bi bi-journal-text me-1"></i>
                                آرشیو مقالات
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($hasWidgets)
        {{-- چیدمان مدیریت‌شده از پنل (ویجت‌های صفحه posts) --}}
        @foreach($widgets as $widget)
            @switch($widget->key)
                @case('post-categories')
                    @include('front::blogs.widgets.post-categories')
                    @break

                @case('posts-three-box-block')
                    @include('front::blogs.widgets.posts-three-box-block')
                    @break

                @case('posts-default-block')
                @case('posts-big-box-block')
                    @include('front::blogs.widgets.posts-default-block')
                    @break

                @case('posts')
                    @include('front::widgets.posts')
                    @break

                @case('middle-banners')
                    @include('front::widgets.middle-banners')
                    @break

                @case('middle-banners-2')
                    @include('front::widgets.middle-banners-2')
                    @break

                @case('middle-banners-4')
                    @include('front::widgets.middle-banners-4')
                    @break

                @case('faqs')
                    @include('front::widgets.faqs')
                    @break

                @case('categories')
                    @include('front::widgets.categories')
                    @break

                @case('sevices-sliders')
                    @include('front::widgets.sevices-sliders')
                    @break

                @case('coworker-sliders')
                    @include('front::widgets.coworker-sliders')
                    @break
            @endswitch
        @endforeach
    @else
        {{-- چیدمان پیش‌فرض داینامیک (بدون ویجت) --}}
        @if($fallbackCats->isNotEmpty())
            @php $widget = null; $categories = $fallbackCats; @endphp
            @include('front::blogs.widgets.post-categories', ['widget' => null, 'categories' => $fallbackCats])
        @endif

        @if($fallbackLatest->isNotEmpty())
            @include('front::blogs.widgets.posts-three-box-block', ['widget' => null, 'posts' => $fallbackLatest, 'fallbackTitle' => 'جدیدترین مطالب'])
        @endif

        @if($fallbackPopular->isNotEmpty())
            @include('front::blogs.widgets.posts-default-block', ['widget' => null, 'posts' => $fallbackPopular, 'fallbackTitle' => 'پربازدیدترین مطالب'])
        @endif
    @endif

@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/blog/blog-slider.js') }}"></script>
@endpush
