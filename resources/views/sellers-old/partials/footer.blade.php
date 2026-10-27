<!-- Start footer -->
<footer>
    <div class="footer-jump">
        <a class="cursor-pointer">
            <span class="footer-jump-angle"><i class="fa fa-angle-up"></i>برگشت به بالا</span>
        </a>
    </div>
    <?php
    $sevices_sliders=App\Models\Slider::whereJsonContains('groups', 'sevices_sliders')
        ->where('published', true)
        ->get()
    ?>
@if(count($sevices_sliders))

    <div class="footer-inner-box">
            @foreach($sevices_sliders as $sevices_slider)
            <a href="{{$sevices_slider->link}}" class="footer-badge">
                <img src="{{asset($sevices_slider->image)}}" alt="{{$sevices_slider->title}}">
                <span class="item-feature">{{$sevices_slider->title}}</span>
            </a>
            @endforeach


        </div>

@endif
    <div class="col-12">
        <div class="middle-bar-footer">
            <div class="col-lg-6 col-xs-12 pull-right">
                <div class="footer-links">
                    @foreach($footer_links as $group)
                    <div class="links-col">
                        <a class="head-line">{{ option('link_groups_' . $group['key'], $group['name']) }}</a>
                        <ul class="links-ul">
                            @foreach($links->where('link_group_id', $group['key']) as $link)
                                <li>
                                    <a href="{{ $link->link }}">{{ $link->title }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="col-lg-4 col-xs-12 pull-left">
                <div class="footer-form">
                        <span class="newslitter-form">از تخفیف‌ها و جدیدترین‌های    {{ option('info_site_title', 'او پی شاپ') }} باخبر
                            شوید:
                        </span>

                    <form id="newsletter-form" method="post" action="{{route('front.newsletter.subscribe')}}">
                        <input type="text" name="contact" class="input-footer" placeholder="آدرس ایمیل یا شماره موبایل خود را وارد کنید">

                        <button class="btn-footer-post">ارسال</button>
                    </form>
                </div>

                <div class="footer-social">
                    <span class="newslitter-form-social">   {{ option('info_site_title', 'او پی شاپ') }} را در شبکه‌های اجتماعی دنبال کنید:</span>

                    <div class="social-links">
                        @if(option('social_instagram'))
                            <a href="{{ option('social_instagram') }}"><i class="fa fa-instagram"></i></a>
                        @endif

                        @if(option('social_whatsapp'))
                            <a href="{{ option('social_whatsapp') }}"><i class="fa fa-whatsapp"></i></a>
                        @endif

                        @if(option('social_telegram'))
                            <a href="{{ option('social_telegram') }}"><i class="fa fa-telegram"></i></a>
                        @endif

                        @if(option('social_facebook'))
                            <a href="{{ option('social_facebook') }}"><i class="fa fa-facebook"></i></a>
                        @endif

                        @if(option('social_twitter'))
                            <a href="{{ option('social_twitter') }}"><i class="fa fa-twitter"></i></a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="footer-address">
            <div class="footer-contact">
                <ul>
                    <li>{{option('info_footer_text')}}</li>
                    <li style="float:right">شماره تماس : <a class="phone-contact">{{option('info_tel')}}</a></li>
                    <li class="email-title">آدرس ایمیل : <a >{{option('info_email')}}</a></li>
                </ul>
            </div>

            <div class="address-images">
                @if(option('info_enamad'))
                    {!! option('info_enamad') !!}
                @endif

                @if(option('info_samandehi'))
                    {!! option('info_samandehi') !!}
                @endif

            </div>
        </div>
    </div>


</footer>
<!-- End footer -->
