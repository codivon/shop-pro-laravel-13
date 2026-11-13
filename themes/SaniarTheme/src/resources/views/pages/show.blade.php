@extends('front::layouts.master', ['title' => $page->title])

@section('og_image')
    <meta property="og:image" content="{{ asset($page->image) }}">
@endsection

@push('meta')
    <meta property="og:type" content="article">
    <meta property="og:description" content="{{ $page->meta_description ?? $page->short_description }}">
    <meta name="description" content="{{ $page->meta_description ?? $page->short_description }}">
    <meta name="twitter:description" content="{{ $page->meta_description ?? $page->short_description }}">
    <meta name="twitter:image" content="{{ asset($page->image) }}">
    <link rel="canonical" href="{{ route('front.articles.show', $page) }}">
@endpush

@section('content')
    {{-- صفحه محتوایی CMS (درباره ما / سوالات متداول / قوانین) — سانیار content-box --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('front.index') }}" class="font-14 text-muted">خانه</a>
                            </li>
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">{{ $page->title }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid py-3">
            <div class="content-box page-content-box">
                <div class="container-fluid">
                    <div class="mb-4 title-panel">
                        <h1 class="font-18">{{ $page->title }}</h1>
                    </div>
                    <div class="page-body">
                        {!! $page->content !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
