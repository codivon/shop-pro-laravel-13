<footer class="footer">
    {{-- سانیار فاز K — سئو: JSON-LD سازمان + وب‌سایت + اکشن جستجو (SearchAction)
         برای موتورهای جستجو؛ همهٔ فیلدها از optionهای دیتابیس --}}
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@@graph' => [
            [
                '@@type' => 'Organization',
                '@@id' => url('/') . '#organization',
                'name' => option('info_site_title', config('front.demo.name')),
                'url' => url('/'),
                'logo' => option('info_logo', theme_asset('image/logo.png')),
                'description' => option('info_short_description'),
                'telephone' => option('info_tel'),
                'email' => option('info_email'),
                'address' => option('info_address') ? ['@@type' => 'PostalAddress', 'streetAddress' => option('info_address'), 'addressCountry' => 'IR'] : null,
                'sameAs' => array_values(array_filter([
                    option('social_instagram'),
                    option('social_telegram'),
                    option('social_whatsapp'),
                    option('social_twitter'),
                ])),
            ],
            [
                '@@type' => 'WebSite',
                '@@id' => url('/') . '#website',
                'url' => url('/'),
                'name' => option('info_site_title', config('front.demo.name')),
                'inLanguage' => 'fa-IR',
                'publisher' => ['@@id' => url('/') . '#organization'],
                'potentialAction' => [
                    '@@type' => 'SearchAction',
                    'target' => ['@@type' => 'EntryPoint', 'urlTemplate' => route('front.products.index') . '?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <div class="container-fluid">
        <div class="footer-socail">
            <div class="row gy-3">
                <div class="col-lg-3">
                    <div class="contact-item">
                        <div class="title">
                            <i class="bi bi-telephone-fill main-color-one-color"></i>
                            <h4 class="font-16"><span class="main-color-one-color">شماره های </span>ارتباطی</h4>
                        </div>
                        <div class="desc">
                            @php $tel = option('info_tel', ''); @endphp
                            @if(!empty($tel))
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $tel) }}" class="text-muted">{{ $tel }}</a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
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
                            @php $email = option('info_email', ''); @endphp
                            @if(!empty($email))
                                <a href="mailto:{{ $email }}" class="text-muted">{{ $email }}</a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
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
                            <span class="text-muted">{{ option('info_address', 'آدرس خود را از تنظیمات وارد کنید') }}</span>
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
                                @foreach(config('front.socials') as $social)
                                    @if(!empty(option($social['key'])))
                                        <a href="{{ option($social['key']) }}" aria-label="{{ $social['name'] }}" rel="noopener">
                                            <i class="{{ $social['icon'] }}"></i>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- لینک‌های فوتر (از پنل: گروه‌های لینک) --}}
        <div class="footer-menu">
            <div class="row gy-3">
                @foreach($footer_links as $group)
                    <div class="col-lg-3 col-6">
                        <div class="footer-menu-title">
                            <h4 class="font-16">{{ option('link_groups_' . $group['key'], $group['name']) }}</h4>
                            <ul class="navbar-nav">
                                @foreach($links->where('link_group_id', $group['key']) as $link)
                                    <li class="nav-item"><a href="{{ $link->link }}" wire:navigate class="nav-link">{{ $link->title }}</a></li>
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
                        <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" class="img-fluid" alt="{{ option('info_site_title', config('front.demo.name')) }}">
                        <p class="text-justify mb-2">
                            {{ option('info_short_description', option('info_footer_text')) }}
                        </p>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="footer-namad">
                        @if(option('info_enamad'))
                            <a href="{{ option('info_enamad_link', '#') }}">
                                <img src="{{ asset(option('info_enamad')) }}" alt="نماد اعتماد" class="img-thumbnail">
                            </a>
                        @endif
                        @if(option('info_samandehi'))
                            <a href="{{ option('info_samandehi_link', '#') }}">
                                <img src="{{ asset(option('info_samandehi')) }}" alt="ساماندهی" class="img-thumbnail">
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-copy-right">
            <p class="text-center mb-0">
                {{ option('info_footer_text', 'تمامی حقوق برای ' . option('info_site_title', config('front.demo.name')) . ' محفوظ است') }}
            </p>
        </div>
    </div>
</footer>
