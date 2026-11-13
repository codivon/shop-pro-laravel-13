{{-- هشتگ‌های داغ وبلاگ سانیار --}}
@php
    $variables = get_widget($widget);
    $tags      = $variables['tags'];
@endphp

@if ($tags->count())
    <div class="py-20">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <div class="header-content mb-4 d-flex align-items-center justify-content-between flex-wrap">
                        <div class="title d-flex align-items-center">
                            <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                            <h5 class="font-16 ms-3 mb-0">{{ $widget->option('title', 'هشتگ‌های') }} <span class="main-color-one-color d-inline-block">داغ</span></h5>
                        </div>
                    </div>
                    <ul class="hashtags mb-0">
                        @foreach ($tags as $tag)
                            <li>
                                <a href="{{ route('front.articles.index').'?tag='.$tag->slug }}">{{ $tag->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif
