@extends('front::layouts.master', ['title' => 'آرشیو مقالات'])

@push('meta')
    <link rel="canonical" href="{{ route('front.articles.index') }}" />
@endpush

@section('content')
    <div class="content articles-index-page" data-url="{{ route('front.articles.index') }}">
        <div class="container-fluid">

            @if($theAuthor)
                {{-- اطلاعات نویسنده — سانیار content-box --}}
                <div class="row gy-3 mb-4">
                    <div class="col-12">
                        <div class="content-box">
                            <div class="container-fluid">
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="d-flex align-items-center ms-4 mb-3">
                                        <img class="rounded-circle shadow-box" width="80" height="80"
                                             src="{{ $theAuthor->imageUrl }}"
                                             alt="{{ $theAuthor->full_name ?? 'ناشناس' }}" loading="lazy">
                                        <div class="ms-3">
                                            <h5 class="font-16 mb-1">
                                                <a href="{{ route('front.articles.index', ['profile' => $theAuthor->username]) }}" class="text-decoration-none text-dark">
                                                    {{ $theAuthor->full_name ?? 'ناشناس' }}
                                                </a>
                                                <span class="badge rounded-pill main-color-one-bg text-white ms-1"><i class="bi bi-patch-check-fill"></i> مدیریت</span>
                                            </h5>
                                            <p class="text-muted font-14 mb-0">{{ $theAuthor->bio ?? 'توضیحاتی برای نویسنده ثبت نشده است.' }}</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap ms-auto mb-3">
                                        <span class="text-muted font-14 ms-3"><i class="bi bi-newspaper main-color-one-color me-1"></i> منتشر شده: <span class="fw-bold">{{ $theAuthor->published_posts_count }}</span></span>
                                        <span class="text-muted font-14 ms-3"><i class="bi bi-calendar3 main-color-one-color me-1"></i> تاریخ پیوستن: <span class="fw-bold">{{ jdate($theAuthor->created_at)->format('d F Y') }}</span></span>
                                        <div class="d-flex align-items-center">
                                            @php
                                                $socials = [
                                                    'instagram' => ['icon' => '<i class="bi bi-instagram"></i>', 'name' => 'اینستاگرام'],
                                                    'whatsapp' => ['icon' => '<i class="bi bi-whatsapp"></i>', 'name' => 'واتساپ'],
                                                    'telegram' => ['icon' => '<i class="bi bi-telegram"></i>', 'name' => 'تلگرام'],
                                                    'twitter' => ['icon' => '<i class="bi bi-twitter"></i>', 'name' => 'توییتر'],
                                                    'facebook' => ['icon' => '<i class="bi bi-facebook"></i>', 'name' => 'فیسبوک'],
                                                    'rubika' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/rubika.png').'" class="me-1" width="17">', 'name' => 'روبیکا'],
                                                    'eitaa' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/eita.png').'" class="me-1" width="17">', 'name' => 'ایتا'],
                                                    'bale' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/bale.png').'" class="me-1" width="17">', 'name' => 'بله'],
                                                ];
                                            @endphp

                                            @foreach($socials as $platform => $info)
                                                @php $link = $theAuthor->getSocialLink($platform); @endphp
                                                @if($link)
                                                    <a href="{{ $link }}" class="btn btn-sm light-btn shadow-sm rounded-circle ms-1" title="{{ $info['name'] }}" target="_blank" rel="noopener noreferrer">
                                                        {!! $info['icon'] !!}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                    <div class="title d-flex align-items-center">
                        <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                        <h5 class="font-16 ms-3 mb-0">مطالب <span class="main-color-one-color d-inline-block">منتشر شده توسط نویسنده</span></h5>
                    </div>
                </div>
            @endif

            {{-- مرتب‌سازی — سانیار header-content --}}
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3 mb-0">مرتب سازی <span class="main-color-one-color d-inline-block">بر اساس:</span></h5>
                </div>
                <div class="link d-flex align-items-center flex-wrap overflow-auto py-1">
                    <span class="filter-link border-animate fromCenter pb-1 fw-bold {{ !request('sort') || request('sort') == 'latest' ? 'active' : '' }}"
                          data-sort="latest">جدیدترین</span>
                    <span class="filter-link border-animate fromCenter pb-1 fw-bold ms-4 {{ request('sort') == 'most_viewed' ? 'active' : '' }}"
                          data-sort="most_viewed">پربازدید</span>
                    <span class="filter-link border-animate fromCenter pb-1 fw-bold ms-4 {{ request('sort') == 'most_popular' ? 'active' : '' }}"
                          data-sort="most_popular">محبوب‌ترین</span>
                    <span class="filter-link border-animate fromCenter pb-1 fw-bold ms-4 {{ request('sort') == 'most_commented' ? 'active' : '' }}"
                          data-sort="most_commented">پربحث‌ترین</span>
                    <span class="filter-link border-animate fromCenter pb-1 fw-bold ms-4 {{ request('sort') == 'editor_pick' ? 'active' : '' }}"
                          data-sort="editor_pick">انتخاب سردبیر</span>
                </div>
            </div>

            <div class="row gy-3 pb-4">
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
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/articles/index.js') }}"></script>
@endpush
