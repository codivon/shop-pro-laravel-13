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

    <!-- Start main-content -->
    <main class="main-content dt-sl mt-4 mb-3">
        <div class="container main-container">

            <div class="row">
                <div class="col-12">
                    <div class="page dt-sl dt-sn pt-3 pb-5">
                        <div class="section-title title-wide mb-1 no-after-title-wide">
                            <h1 class="font-weight-bold">{{ $page->title }}</h1>
                        </div>
                        {!! $page->content !!}
                    </div>
                </div>
            </div>

        </div>
    </main>
    <!-- End main-content -->

@endsection
