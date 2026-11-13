@extends('front::layouts.master', ['title' => 'آرشیو مقالات'])
@push('styles')
    <link rel="stylesheet" href="{{theme_asset('css/single-articles.css')}}">
@endpush
@section('content')
    <div class="articles-index-page col-lg-12 col-md-12 col-xs-12 pull-right">
        <div class="row">

            @if($theAuthor)
                <div class="col-12 author-articles-index">
                    {{-- اطلاعات نویسنده --}}
                    <div class="author card shadow-1 mb-4">
                        <div class="card-body flex-grow-1">
                            <div class="author--avatar">
                                <img class="shadow-1 mb-2" width="100" height="100"
                                     src="{{$theAuthor->imageUrl}}"
                                     alt="{{ $theAuthor->full_name ?? 'ناشناس' }}" loading="lazy">
                                <div class="author--badge"><i class="fa-solid fa-crown"></i> مدیریت</div>
                            </div>
                            <div class="author--meta">
                                <div class="author--name">
                                    <a href="{{ route('front.articles.index', ['profile' => $theAuthor->username]) }}" style="text-decoration: none;">
                                        <span>{{ $theAuthor->full_name ?? 'ناشناس' }}</span>
                                    </a>
                                </div>
                                <p class="author--description mb-3">{{ $theAuthor->bio ?? 'توضیحاتی برای نویسنده ثبت نشده است.' }}</p>
                                <div class="autor--meta-details">
                                    <ul>
                                        <li><i class="fa-regular fa-newspaper ml-2"></i> منتشر شده: <span>{{ $theAuthor->published_posts_count }}</span></li>
                                        <li class="divider"></li>
                                        <li><i class="fa-regular fa-calendar ml-2"></i> تاریخ پیوستن: <span>{{ jdate($theAuthor->created_at)->format('d F Y') }}</span></li>
                                    </ul>
                                    <div class="social">
                                        @php
                                            $socials = [
                                                'instagram' => ['icon' => '<i class="fa-brands fa-instagram"></i>', 'name' => 'اینستاگرام'],
                                                'whatsapp' => ['icon' => '<i class="fa-brands fa-whatsapp"></i>', 'name' => 'واتساپ'],
                                                'telegram' => ['icon' => '<i class="fa-brands fa-telegram"></i>', 'name' => 'تلگرام'],
                                                'twitter' => ['icon' => '<i class="fa-brands fa-twitter"></i>', 'name' => 'توییتر'],
                                                'facebook' => ['icon' => '<i class="fa-brands fa-facebook"></i>', 'name' => 'فیسبوک'],
                                                'rubika' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/rubika.png').'" style="margin-top: -7px;width:17px">', 'name' => 'روبیکا'],
                                                'eitaa' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/eita.png').'" style="margin-top: -7px;width:17px">', 'name' => 'ایتا'],
                                                'bale' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/bale.png').'" style="margin-top: -7px;width:17px">', 'name' => 'بله'],
                                            ];
                                        @endphp

                                        @foreach($socials as $platform => $info)
                                            @php $link = $theAuthor->getSocialLink($platform); @endphp
                                            @if($link)
                                                <a href="{{ $link }}" class="" title="{{$info['name']}}" target="_blank" rel="noopener noreferrer">
                                                    {!! $info['icon'] !!}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="blog-archive-details w-100 mb-3">منتشر شده توسط نویسنده:</div>
                </div>
            @endif


            <div class="col-12">
                <div class="filters simplebar-container mb-3 w-100" data-simplebar="init" style="overflow-x: auto;">




                    <div class="simplebar-wrapper position-relative" style="margin: 0px -5px;">
                        <div class="simplebar-height-auto-observer-wrapper">
                            <div class="simplebar-height-auto-observer"></div>
                        </div>
                        <div class="simplebar-mask">
                            <div class="simplebar-offset" style="left: 0px; bottom: 0px;">
                                <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                     aria-label="scrollable content" style="height: auto; overflow: hidden;">
                                    <div class="simplebar-content">
                                        <ul class="nav nav-pills nav-tabs align-items-center" id="sort-tab" role="tablist"
                                            style="display: inline-flex; white-space: nowrap; flex-wrap: nowrap;">
                                            <li class="d-inline-flex filter-list-title lts-05 align-items-center nav-item ml-3 text-muted">
                                                <i class="fa-solid fa-chart-simple ml-2"></i> مرتب سازی بر اساس:
                                            </li>
                                            <li class="nav-item" role="presentation">
                                            <span class="nav-link lts-05 filter-link {{ !request('sort') || request('sort') == 'latest' ? 'active' : '' }}"
                                                  data-sort="latest">جدیدترین</span>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                            <span class="nav-link lts-05 filter-link {{ request('sort') == 'most_viewed' ? 'active' : '' }}"
                                                  data-sort="most_viewed">پربازدید</span>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                            <span class="nav-link lts-05 filter-link {{ request('sort') == 'most_popular' ? 'active' : '' }}"
                                                  data-sort="most_popular">محبوب‌ترین</span>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                            <span class="nav-link lts-05 filter-link {{ request('sort') == 'most_commented' ? 'active' : '' }}"
                                                  data-sort="most_commented">پربحث‌ترین</span>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <span class="nav-link lts-05 filter-link {{ request('sort') == 'editor_pick' ? 'active' : '' }}"
                                                      data-sort="editor_pick">انتخاب سردبیر</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="simplebar-placeholder" style="width: 800px; height: 42px;"></div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div id="articles-container">
                    @include('front::articles.partials.articles-list', ['posts' => $posts])
                </div>
                <div id="pagination-container">
                    @include('front::articles.partials.pagination', ['posts' => $posts])
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/articles/index.js') }}"></script>
    <script>
        var articlesIndexUrl='{{ route("front.articles.index") }}'
    </script>

@endpush
