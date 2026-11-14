{{-- گرید مطالب وبلاگ سانیار (gird-post-blog) — فاز ۱۶۸: کارت مدرن مشترک (blog-card) --}}
@php
    $variables = get_widget($widget);
    $posts     = $variables['posts'];
@endphp

@if ($posts->count())
    <div class="gird-post-blog py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">{{ $widget->option('title', 'آخرین مطالب') }} <span class="main-color-one-color d-inline-block">وبلاگ</span></h5>
                </div>
                @if($widget->option('link_title') and $widget->option('link'))
                    <div class="link">
                        <a href="{{ $widget->option('link') }}" class="border-animate fromCenter pb-1 fw-bold">
                            {{ $widget->option('link_title') }}
                            <i class="bi bi-chevron-double-left main-color-one-color"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="container-fluid">
            <div class="row gy-4 justify-content-center">
                @foreach($posts as $post)
                    <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                        @include('front::partials.blog-card', ['post' => $post])
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
