@php
    // سانیار فاز K — تماس با ما: کارت‌های تماس کاملاً از optionهای دیتابیس؛
    // فرم AJAX با کپچای میزبان؛ نقشه به‌جای پلاگین سنگین → لینک بیرونی
    // (الگوی فاز I نمایش آدرس‌ها)
    $contactChannels = [];
    if (option('info_tel')) {
        $contactChannels[] = [
            'title' => 'شماره‌های', 'highlight' => 'ارتباطی', 'icon' => 'bi bi-telephone',
            'items' => array_map(fn($tel) => ['label' => trim($tel), 'href' => 'tel:' . preg_replace('/[^0-9+]/', '', $tel)],
                array_filter(explode(',', option('info_tel')))),
        ];
    }
    if (option('info_email')) {
        $contactChannels[] = [
            'title' => 'ایمیل‌های', 'highlight' => 'ارتباطی', 'icon' => 'bi bi-envelope',
            'items' => array_map(fn($mail) => ['label' => trim($mail), 'href' => 'mailto:' . trim($mail)],
                array_filter(explode(',', option('info_email')))),
        ];
    }
    if (option('info_address')) {
        $lat = option('info_latitude', '38.07709880960678');
        $lng = option('info_Longitude', '46.28582686185837');
        $contactChannels[] = [
            'title' => 'آدرس', 'highlight' => 'شرکت', 'icon' => 'bi bi-geo-alt',
            'items' => [['label' => option('info_address'), 'href' => 'https://maps.google.com/?q=' . $lat . ',' . $lng, 'external' => true]],
        ];
    }
    $socials = array_filter([
        ['name' => 'اینستاگرام', 'icon' => 'bi bi-instagram', 'url' => option('social_instagram')],
        ['name' => 'واتساپ', 'icon' => 'bi bi-whatsapp', 'url' => option('social_whatsapp')],
        ['name' => 'تلگرام', 'icon' => 'bi bi-telegram', 'url' => option('social_telegram')],
        ['name' => 'توییتر', 'icon' => 'bi bi-twitter-x', 'url' => option('social_twitter')],
    ], fn($s) => (bool) $s['url']);
    if ($socials) {
        $contactChannels[] = [
            'title' => 'فروشگاه در', 'highlight' => 'رسانه‌ها', 'icon' => 'bi bi-share',
            'items' => array_map(fn($s) => ['label' => $s['name'], 'href' => $s['url'], 'icon' => $s['icon'], 'external' => true], $socials),
        ];
    }
@endphp
@extends('front::layouts.master', [
    'title' => 'تماس با ما',
    'description' => 'راه‌های ارتباط با ' . option('info_site_title', config('front.demo.name')) . ' — شماره تلفن، ایمیل، آدرس و فرم پیام مستقیم',
])

@push('meta')
    <link rel="canonical" href="{{ route('front.contact.index') }}">
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@@type' => 'ContactPage',
        'name' => 'تماس با ما',
        'url' => route('front.contact.index'),
        'inLanguage' => 'fa-IR',
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
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">تماس با ما</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content py-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <div class="title-panel mb-4">
                        <h1 class="font-20 fw-bold">تماس با <span class="main-color-one-color">{{ option('info_site_title', config('front.demo.name')) }}</span></h1>
                    </div>

                    @if(option('dt_show_contact_top_description'))
                        <div class="dynamic-content font-14 mb-4">
                            {!! option('dt_show_contact_top_description') !!}
                        </div>
                    @endif

                    {{-- کارت‌های اطلاعات تماس --}}
                    @if($contactChannels)
                        <div class="row gy-3 mb-4">
                            @foreach($contactChannels as $channel)
                                <div class="col-lg-3 col-md-6">
                                    <div class="contact-item content-box h-100">
                                        <div class="title">
                                            <h4 class="font-15 mb-0"><i class="{{ $channel['icon'] }} me-2 main-color-one-color"></i>{{ $channel['title'] }} <span class="main-color-one-color">{{ $channel['highlight'] }}</span></h4>
                                        </div>
                                        <div class="desc mt-2">
                                            @foreach($channel['items'] as $item)
                                                <a href="{{ $item['href'] }}" @if(($item['external'] ?? false)) target="_blank" rel="noopener noreferrer" @endif
                                                   class="d-block text-muted font-13 mb-1 contact-link">
                                                    @if(isset($item['icon']))<i class="{{ $item['icon'] }} me-1"></i>@endif
                                                    {{ $item['label'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- فرم تماس (تنظیم از پنل: dt_show_form_in_contact) --}}
                    @if(option('dt_show_form_in_contact', 'yes') == 'yes')
                        <div class="comment-form mt-4" data-saniar-contact-form data-store-url="{{ route('front.contact.store') }}">
                            <div class="title-panel mb-3">
                                <h6 class="font-18 mb-0">پیام خود را با ما در میان بگذارید</h6>
                            </div>
                            <form class="contact-form-body" novalidate>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="contactName" class="form-label">نام و نام خانوادگی <span class="text-danger">*</span></label>
                                            <input type="text" id="contactName" name="name" class="form-control" required maxlength="191"
                                                   value="{{ auth('web')->check() ? (auth('web')->user()->full_name ?? '') : old('name') }}"
                                                   autocomplete="name">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="contactEmail" class="form-label">ایمیل <span class="text-danger">*</span></label>
                                            <input type="email" id="contactEmail" name="email" class="form-control" required maxlength="191" dir="ltr"
                                                   value="{{ auth('web')->check() ? (auth('web')->user()->email ?? '') : old('email') }}"
                                                   autocomplete="email">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group mb-3">
                                            <label for="contactSubject" class="form-label">موضوع <span class="text-danger">*</span></label>
                                            <input type="text" id="contactSubject" name="subject" class="form-control" required maxlength="191">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group mb-3">
                                            <label for="contactMessage" class="form-label">متن پیام <span class="text-danger">*</span></label>
                                            <textarea id="contactMessage" name="message" rows="6" class="form-control" required maxlength="2000"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <x-captcha />
                                    </div>
                                </div>
                                <button type="submit" class="btn main-color-one-bg text-white border-0 rounded-pill px-5 mt-2 contact-submit-btn">
                                    <i class="bi bi-send me-1"></i>ثبت و ارسال پیام
                                </button>
                            </form>
                        </div>
                    @endif

                    @if(option('dt_show_contact_bottom_description') == 'yes')
                        <hr class="my-4">
                        <div class="dynamic-content font-14">
                            {!! option('dt_contact_bottom_description') !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/contact.js') }}"></script>
@endpush
