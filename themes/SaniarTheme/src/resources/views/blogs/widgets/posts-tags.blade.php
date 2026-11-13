@php
    $variables    = get_widget($widget);
    $tags   = $variables['tags'];
@endphp
    <!-- Start Category-Section -->
@if ($tags->count())
    <div class="col-12" style="margin-top: 20px; margin-bottom: 20px;">
        <div class="promotion-categories-container inner-card-container hashtag-container card shadow-1 ">
            <div class="card-body sidebar-card-inner-header">
                @if($widget->option('title'))
                    <div class="inner-card-container--title lts-05">{{ $widget->option('title') }}</div>
                    <span class="divider"></span>
                @endif

                <ul class="hashtags">
                    @foreach ($tags as $tag)
                    <li>
                        <a href="{{ route('front.articles.index').'?tag='.$tag->slug }}">{{$tag->name}}</a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

@endif
<!-- End Category-Section -->
