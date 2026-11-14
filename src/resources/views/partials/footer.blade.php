{{-- فوتر سانیار --}}
<footer class="footer">
    <div class="container-fluid">

        @php
            $sevices_sliders = App\Models\Slider::whereJsonContains('groups', 'sevices_sliders')
                ->where('published', true)
                ->get();
        @endphp

        @if(count($sevices_sliders))
            <div class="footer-inner-box mb-4">
                <div class="row gy-3">
                    @foreach($sevices_sliders as $sevices_slider)
                        <div class="col">
                            <a href="{{ $sevices_slider->link }}" class="footer-badge">
                                <img loading="lazy" decoding="async" src="{{ asset($sevices_slider->image) }}" alt="{{ $sevices_slider->title }}">
                                <span class="item-feature">{{ $sevices_slider->title }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="footer-socail">
            <div class="row gy-3">
                <div class="col-lg-3">
                    <div class="contact-item">
                        <div class="title">
                            <i class="bi bi-telephone-fill main-color-one-color"></i>
                            <h4 class="font-16"><span class="main-color-one-color">شماره های </span>ارتباطی</h4>
                        </div>
                        <div class="desc">
                            <a href="tel:{{ option('info_tel', '') }}" class="text-muted" dir="ltr">{{ option('info_tel', '') }}</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="contact-item">
                        <div class="title">
                            <i class="bi bi-envelope-fill main-color-one-color"></i>
                            <h4 class="font-16"><span class="main-color-one-color"> ایمیل های </span>ارتباطی</h4>
                        </div>
                        <div class="desc">
                            <a href="mailto:{{ option('info_email', '') }}" class="text-muted">{{ option('info_email', '') }}</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="contact-item">
                        <div class="title">
                            <i class="bi bi-geo-alt-fill main-color-one-color"></i>
                            <h4 class="font-16"><span class="main-color-one-color"> آدرس </span>شرکت</h4>
                        </div>
                        <div class="desc">
                            <span class="text-muted">{{ option('info_footer_text', '') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="contact-item">
                        <div class="title">
                            <i class="bi bi-chevron-down main-color-one-color"></i>
                            <h4 class="font-16"><span class="main-color-one-color"> فروشگاه </span>در رسانه ها</h4>
                        </div>
                        <div class="desc">
                            <div class="social">
                                @if(option('social_instagram'))
                                    <a href="{{ option('social_instagram') }}"><i class="bi bi-instagram"></i></a>
                                @endif
                                @if(option('social_whatsapp'))
                                    <a href="{{ option('social_whatsapp') }}"><i class="bi bi-whatsapp"></i></a>
                                @endif
                                @if(option('social_telegram'))
                                    <a href="{{ option('social_telegram') }}"><i class="bi bi-telegram"></i></a>
                                @endif
                                @if(option('social_youtube'))
                                    <a href="{{ option('social_youtube') }}"><i class="bi bi-youtube"></i></a>
                                @endif
                                @if(option('social_twitter'))
                                    <a href="{{ option('social_twitter') }}"><i class="bi bi-twitter"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-menu">
            <div class="row gy-3">
                @foreach($footer_links as $group)
                    <div class="col-lg-3 col-6">
                        <div class="footer-menu-title">
                            <h4 class="font-16">{{ option('link_groups_' . $group['key'], $group['name']) }}</h4>
                            <ul class="navbar-nav">
                                @foreach($links->where('link_group_id', $group['key']) as $link)
                                    <li class="nav-item"><a href="{{ $link->link }}" class="nav-link">{{ $link->title }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="footer-text">
            <div class="row gy-3">
                <div class="col-lg-9">
                    <div class="footer-menu-text">
                        <img loading="lazy" decoding="async" src="{{ option('info_logo', theme_asset('image/logo.png')) }}" class="img-fluid" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                        <p class="text-justify mb-2">
                            {{ option('info_about_us', option('info_short_description', '')) }}
                        </p>
                        <a href="{{ route('front.contact.index') }}" class="main-color-one-color"><i
                                class="bi bi-arrow-left main-color-one-color me-1"></i> اطلاعات بیشتر</a>

                        <form id="newsletter-form" method="post" action="{{ route('front.newsletter.subscribe') }}"
                              class="footer-newsletter-form mt-3 d-flex gap-2">
                            @csrf
                            <input type="text" name="contact" class="form-control rounded-pill border-0"
                                   placeholder="آدرس ایمیل یا شماره موبایل خود را وارد کنید">
                            <button class="btn main-color-one-bg rounded-pill border-0" type="submit">ارسال</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="footer-namad">
                        @if(option('info_enamad'))
                            {!! option('info_enamad') !!}
                        @endif

                        @if(option('info_samandehi'))
                            {!! option('info_samandehi') !!}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-copy-right">
            <p class="text-center mb-0">تمامی حقوق این فروشگاه متعلق به {{ option('info_site_title', 'او پی شاپ') }} می‌باشد.</p>
        </div>
    </div>
</footer>
