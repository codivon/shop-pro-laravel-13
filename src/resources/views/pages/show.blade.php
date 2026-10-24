@php
    // سانیار فاز K — صفحهٔ محتوایی داینامیک (درباره ما / سوالات متداول / قوانین ...)
    // محتوا از جدول pages + شورت‌کدهای میزبان؛
    // اصلاح دو باگ لاتنت WeblakShop: og:image از ستون ناموجود image می‌خواند و
    // canonical اشتباهی به روت «مقاله» پوینت می‌کرد.
    // گارد saniar_seo_value: پیش‌فرض رشته‌ای 'NULL' ستون‌های متا (باگ مهاجرت میزبان).
    $pageTitle = saniar_seo_value($page->meta_title, $page->title);
    $pageDescription = saniar_seo_value($page->meta_description, \Illuminate\Support\Str::limit(trim(strip_tags($page->content)), 160));
    $pageDescription = saniar_seo_value($pageDescription, '');
    $pageUrl = route('front.pages.show', ['page' => $page->slug]);
@endphp
@extends('front::layouts.master', [
    'title' => $pageTitle,
    'description' => $pageDescription,
])

@push('meta')
    <link rel="canonical" href="{{ $pageUrl }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $page->title }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@@type' => 'WebPage',
        'name' => $pageTitle,
        'description' => $pageDescription ?: null,
        'url' => $pageUrl,
        'inLanguage' => 'fa-IR',
        'dateModified' => $page->updated_at->toIso8601String(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/content.css') }}">
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
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">{{ $page->title }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content py-3 mb-4">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <div class="title-panel mb-3">
                        <h1 class="font-20 fw-bold">{{ $page->title }}</h1>
                    </div>
                    <div class="dynamic-content font-15 lh-lg">
                        {!! $page->content !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
