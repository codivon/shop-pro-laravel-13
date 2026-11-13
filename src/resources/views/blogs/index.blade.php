@extends('front::layouts.master', ['title' => 'وبلاگ'])

@push('meta')
    <link rel="canonical" href="{{ route('front.blog.index') }}" />
@endpush

@section('content')
    @foreach ($widgets as $widget)
        @switch($widget->key)
            @case('fullscreen-slider')
                @include('front::blogs.widgets.fullscreen-slider')
                @break

            @case('main-slider')
                @include('front::blogs.widgets.main-slider')
                @break

            @case('main-story')
                @include('front::blogs.widgets.main-story')
                @break

            @case('products-moment-block')
            @case('products-default-block')
                @include('front::blogs.widgets.products-default-block')
                @break

            @case('posts-default-block')
                @include('front::blogs.widgets.posts-default-block')
                @break

            @case('products-colorful-block')
                @include('front::blogs.widgets.products-colorful-block')
                @break

            @case('middle-banners')
                @include('front::blogs.widgets.middle-banners')
                @break

            @case('middle-banners-2')
                @include('front::blogs.widgets.middle-banners-2')
                @break

            @case('middle-banners-4')
                @include('front::blogs.widgets.middle-banners-4')
                @break

            @case('coworker-sliders')
                @include('front::blogs.widgets.coworker-sliders')
                @break

            @case('sevices-sliders')
                @include('front::blogs.widgets.sevices-sliders')
                @break

            @case('categories')
                @include('front::blogs.widgets.categories')
                @break

            @case('post-categories')
                @include('front::blogs.widgets.post-categories')
                @break

            @case('posts')
                @include('front::blogs.widgets.posts')
                @break

            @case('posts-three-box-block')
                @include('front::blogs.widgets.posts-three-box-block')
                @break

            @case('posts-big-box-block')
                @include('front::blogs.widgets.posts-big-box-block')
                @break

            @case('posts-tags')
                @include('front::blogs.widgets.posts-tags')
                @break

            @case('faqs')
                @include('front::blogs.widgets.faqs')
                @break
        @endswitch
    @endforeach

@endsection
