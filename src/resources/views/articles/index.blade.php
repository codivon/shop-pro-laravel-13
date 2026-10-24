@php
    $activeFilters = [];
    if (request('q')) { $activeFilters['q'] = 'جستجو: ' . request('q'); }
    if (request('cat')) {
        $activeCat = \App\Models\Category::where('slug', request('cat'))->where('type', 'postcat')->first();
        if ($activeCat) { $activeFilters['cat'] = 'دسته: ' . $activeCat->title; }
    }
    if (request('tag')) { $activeFilters['tag'] = 'تگ: ' . request('tag'); }
    if ($theAuthor) { $activeFilters['profile'] = 'نویسنده: ' . ($theAuthor->full_name ?? request('profile')); }
@endphp
@extends('front::layouts.master', [
    'title' => ($theAuthor ? 'مقالات ' . ($theAuthor->full_name ?? '') : 'آرشیو مقالات'),
    'description' => 'آرشیو مقالات' . ($activeFilters ? ' — ' . implode('، ', $activeFilters) : '') . ' — وبلاگ ' . option('info_site_title', config('front.demo.name')),
])

@push('meta')
    <link rel="canonical" href="{{ route('front.articles.index', array_filter(['cat' => request('cat'), 'tag' => request('tag'), 'profile' => request('profile')])) }}">
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
                            <li class="breadcrumb-item">
                                <a href="{{ route('front.blog.index') }}" wire:navigate class="font-14 text-muted">وبلاگ</a>
                            </li>
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">آرشیو مقالات</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- هدر آرشیو: عنوان + شمارنده + جستجو --}}
    <div class="py-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid py-3">
                    <div class="row align-items-center gy-3">
                        <div class="col-lg-5 col-md-6">
                            <h1 class="font-18 fw-bold mb-1">
                                آرشیو مقالات
                                @if($theAuthor)
                                    <span class="main-color-one-color">{{ $theAuthor->full_name }}</span>
                                @endif
                            </h1>
                            <p class="text-muted font-13 mb-0" id="articles-total-counter" data-saniar-total="{{ $posts->total() }}">
                                {{ number_format($posts->total()) }} مقاله منتشر شده
                            </p>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <form data-saniar-articles-search class="blog-search-form" role="search">
                                <div class="search-filed">
                                    <input type="text" name="q" value="{{ request('q') }}" class="form-control search-input"
                                           placeholder="جستجو در مقالات ..." autocomplete="off" aria-label="جستجو در مقالات">
                                    <button type="submit" class="btn search-btn main-color-one-bg rounded-pill" aria-label="جستجو">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- فیلترهای فعال (chips) --}}
                    @if($activeFilters)
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3" id="articles-active-filters">
                            @foreach($activeFilters as $key => $label)
                                <span class="badge rounded-pill bg-light text-dark fw-normal border d-flex align-items-center gap-1">
                                    <i class="bi bi-funnel main-color-one-color"></i>{{ $label }}
                                    <a href="{{ route('front.articles.index', array_filter([
                                        'cat' => $key == 'cat' ? null : request('cat'),
                                        'tag' => $key == 'tag' ? null : request('tag'),
                                        'profile' => $key == 'profile' ? null : request('profile'),
                                        'q' => $key == 'q' ? null : request('q'),
                                    ])) }}" wire:navigate class="text-danger ms-1" aria-label="حذف فیلتر {{ $label }}">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- کارت نویسنده (در حالت فیلتر نویسنده) --}}
    @if($theAuthor)
        <div class="pb-3">
            <div class="container-fluid">
                <div class="content-box">
                    <div class="container-fluid">
                        <div class="row align-items-center gy-3 py-3">
                            <div class="col-auto">
                                <img src="{{ $theAuthor->imageUrl }}" alt="{{ $theAuthor->full_name ?? 'نویسنده' }}"
                                     class="rounded-circle shadow-box" width="88" height="88" loading="lazy">
                            </div>
                            <div class="col">
                                <h2 class="font-16 fw-bold mb-1">{{ $theAuthor->full_name ?? 'نویسنده' }}</h2>
                                <p class="text-muted font-13 mb-2">{{ $theAuthor->bio ?? 'توضیحاتی برای این نویسنده ثبت نشده است.' }}</p>
                                <div class="d-flex flex-wrap gap-3 text-muted font-13">
                                    <span><i class="bi bi-newspaper me-1"></i>{{ number_format($theAuthor->published_posts_count) }} مقاله منتشر شده</span>
                                    <span><i class="bi bi-calendar-event me-1"></i>تاریخ پیوستن: {{ jdate($theAuthor->created_at)->format('d F Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- نوار مرتب‌سازی (AJAX) --}}
    <div class="pb-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 py-3 overflow-auto"
                         id="articles-sort-bar"
                         data-saniar-articles-index
                         data-base-url="{{ route('front.articles.index', array_filter(['cat' => request('cat'), 'tag' => request('tag'), 'profile' => request('profile')])) }}">
                        <span class="text-muted font-14 text-nowrap">
                            <i class="bi bi-bar-chart me-1"></i>مرتب سازی بر اساس:
                        </span>
                        <ul class="nav nav-pills flex-nowrap gap-1" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link {{ ! request('sort') || request('sort') == 'latest' ? 'active' : '' }}"
                                        data-sort="latest">جدیدترین</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link {{ request('sort') == 'most_viewed' ? 'active' : '' }}"
                                        data-sort="most_viewed">پربازدید</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link {{ request('sort') == 'most_popular' ? 'active' : '' }}"
                                        data-sort="most_popular">محبوب‌ترین</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link {{ request('sort') == 'most_commented' ? 'active' : '' }}"
                                        data-sort="most_commented">پربحث‌ترین</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link {{ request('sort') == 'editor_pick' ? 'active' : '' }}"
                                        data-sort="editor_pick">انتخاب سردبیر</button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- گرید مقالات (هدف AJAX) --}}
    <div class="pb-20">
        <div class="container-fluid">
            <div id="articles-container">
                @include('front::articles.partials.articles-list', ['posts' => $posts])
            </div>
            <div id="pagination-container">
                @include('front::articles.partials.pagination', ['posts' => $posts])
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/blog/articles-index.js') }}"></script>
@endpush
