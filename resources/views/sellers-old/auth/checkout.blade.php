@extends('sellers-old.auth.layouts', ['title' => 'اطلاعات فروشنده'])

@push('styles')

@endpush

@section('content')
    <div class="new-login_seller">
        <div class='row w-100 m-0'>
            <div class='col-md-4 p-0'>
                <div class="new-login_seller-sidebar">
                <div class="new-login_seller_sidebar-content">
                    <header>
                        <a href="{{ route('front.index') }}">
                            <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}"
                                 alt="{{ option('info_site_title', 'او پی شاپ') }}">
                        </a>
                        <h1>ثبت‌نام در مرکز فروشندگان</h1>
                    </header>

                    <ul class="c-reg-steps d-flex-r">
                        <li class="c-reg-steps__item">

                            <div class="c-reg-steps__icon c-reg-steps__icon--info c-reg-steps__icon--done">
                                <i class="fa-solid fa-file-pen"></i>
                            </div>
                            <h2 class="c-reg-steps__header">۱. اطلاعات فروشنده</h2>
                            <p class="c-reg-steps__description">اطلاعات شخصی فروشنده، اطلاعات تجاری، اطلاعات تماس</p>
                        </li>
                        <li class="c-reg-steps__item c-reg-steps__item--next">

                            <div class="c-reg-steps__icon c-reg-steps__icon--documents c-reg-steps__icon--done">
                                <i class="fas fa-file-upload"></i>
                            </div>
                            <h2 class="c-reg-steps__header">۲. بارگذاری مدارک</h2>
                            <p class="c-reg-steps__description">اطلاعات مربوط به مالیات بر ارزش افزوده، تصویر مدارک شخصی و تجاری</p>
                        </li>
                        <li class="c-reg-steps__item c-reg-steps__item--next">

                            <div class="c-reg-steps__icon c-reg-steps__icon--checkout c-reg-steps__icon--current">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <h2 class="c-reg-steps__header">۳. اتمام ثبت نام</h2>
                            <p class="c-reg-steps__description">به جمع فروشندگان {{ option('info_site_title', 'او پی شاپ') }} خوش آمدید.</p>
                        </li>
                    </ul>
                </div>
            </div>
            </div>
            <div class='col-md-8' id="main">
                <div class="new-login_seller_main registration-checkout">
                <div class="c-reg-form">
                    <div class="c-reg-form__row">
                        <div class="c-reg-form__col c-reg-form__col--12">
                            <div class="c-reg-form__success-img"></div>
                        </div>
                    </div>

                    <div class="c-reg-form__row c-reg-form__row--align-center">
                        <div class="c-reg-form__col c-reg-form__col--12">
                            <h2 class="c-reg-form__header">تبریک!</h2>
                        </div>
                    </div>

                    <div class="c-reg-form__row c-reg-form__row--align-center c-reg-form__row--gap-20">
                        <div class="c-reg-form__col c-reg-form__col--12">
                            <p class="c-reg-form__text c-reg-form__text--condensed">اکنون شما به جمع فروشندگان {{ option('info_site_title', 'او پی شاپ') }} پیوستید.<br>از تجارت آنلاین لذت ببرید! </p>
                        </div>
                    </div>

                    <div class="c-reg-form__row c-reg-form__row--gap-20">
                        <div class="c-reg-form__col c-reg-form__col--12">
                            <p class="c-reg-form__text c-reg-form__text--justify c-reg-form__text--condensed">
                                پس از ورود به داشبورد پنل فروشندگان {{ option('info_site_title', 'او پی شاپ') }}، با ورود به صفحه پروفایل شما می توانید نسخه آنلاین قرارداد و اطلاعات تجاری خود را مشاهده کنید. لطفا توجه کنید که نیازی به چاپ و ارسال قرارداد نیست.
                            </p>
                        </div>
                    </div>
                    <form method="post" id="seller-register-checkout" action="{{route('seller.registration_checkout_store')}}" data-name="register">
                        <input type="hidden" name="register[back_to_training]" value="1">
                        <div class="c-reg-form__row c-reg-form__row--align-center c-reg-form__row--gap-40">
                            <div class="c-reg-form__col c-reg-form__col--8 m-auto">
                                <button type="submit" class="c-reg-form__submit-btn c-reg-form__submit-btn--block c-reg-form__submit-btn--secondary" id="btnSubmit">ورود به پنل فروشندگان</button>
                            </div>
                        </div>
                    </form>


                    <div class="c-reg-form__row c-reg-form__row--align-center mt-2">
                        <div class="c-reg-form__col m-auto">
                            <span  class="commission-button ml-0" data-toggle="modal" data-target="#commission-modal">مشاهده جدول کمیسیون</span>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="commission-modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="now-ui-icons location_pin"></i>
                        جدول کمیسیون
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"
                            aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table">
                        <thead class="thead-light">
                        <tr>
                            <th scope="col">عناوین گروه ها</th>
                            <th scope="col">درصد کمیسیون</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($categories as $category)
                        <tr>
                            <td>{{$category->title}}</td>
                            <td class="text-center">{{$category->commission}}%</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <div class="uk-modal-footer">
                        <div class="c-reg-calc">
                            <h4 class="c-reg-calc__title">نحوه محاسبه قیمت کالا</h4>
                            <div class="c-reg-calc__example">
                                <div class="c-reg-calc__result">
                                    <img src="{{theme_asset('img/a28ed027.jpg')}}" alt="گوشی موبایل اپل مدل iPhone 7" class="c-reg-calc__result-img">
                                    <div class="c-reg-calc__exclamation">مبلغ دریافتی شما از فروش</div>
                                    <div class="c-reg-calc__spec">گوشی موبایل اپل مدل iPhone 7<br>ظرفیت ۲۵۶ گیگابایت</div>
                                    <div class="c-reg-calc__result-reward">۳٬۴۳۵٬۰۰۰ <span>تومان</span></div>
                                </div>

                                <div class="c-reg-calc__steps">

                                    <div class="c-reg-calc__step c-reg-calc__step--equal"></div>

                                    <div class="c-reg-calc__step">
                                        <div class="c-reg-calc__step-img c-reg-calc__step-img--tax"></div>
                                        <div class="c-reg-calc__step-cost">۵۰۰ <span>تومان</span></div>
                                        <div class="c-reg-calc__step-desc">مالیات بر<br>ارزش افزوده</div>
                                    </div>
                                    <div class="c-reg-calc__step c-reg-calc__step--minus"></div>

                                    <div class="c-reg-calc__step">
                                        <div class="c-reg-calc__step-img c-reg-calc__step-img--delivery"></div>
                                        <div class="c-reg-calc__step-cost">۴٬۰۰۰ <span>تومان</span></div>
                                        <div class="c-reg-calc__step-desc">هزینه ارسال<br>کالا به مشتری</div>
                                    </div>
                                    <div class="c-reg-calc__step c-reg-calc__step--minus"></div>

                                    <div class="c-reg-calc__step">
                                        <div class="c-reg-calc__step-img c-reg-calc__step-img--packing"></div>
                                        <div class="c-reg-calc__step-cost">۵٬۰۰۰ <span>تومان</span></div>
                                        <div class="c-reg-calc__step-desc">هزینه پردازش<br>و بسته بندی</div>
                                    </div>
                                    <div class="c-reg-calc__step c-reg-calc__step--minus"></div>

                                    <div class="c-reg-calc__step">
                                        <div class="c-reg-calc__step-img c-reg-calc__step-img--commission"></div>
                                        <div class="c-reg-calc__step-cost">۱۶۷٬۵۰۰ <span>تومان</span></div>
                                        <div class="c-reg-calc__step-desc">کمیسیون<br>(٪۵ برای مثال)</div>
                                    </div>
                                    <div class="c-reg-calc__step c-reg-calc__step--minus"></div>

                                    <div class="c-reg-calc__step">
                                        <div class="c-reg-calc__step-img c-reg-calc__step-img--dk-price">
                                            <img src="{{theme_asset('img/a28ed027.jpg')}}" alt="گوشی موبایل اپل مدل iPhone 7" class="c-reg-calc__result-img">
                                        </div>
                                        <div class="c-reg-calc__step-cost">۳٬۵۴۷٬۰۰۰ <span>تومان</span></div>
                                        <div class="c-reg-calc__step-desc">قیمت فروش شما<br>در دیجی‌کالا</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/register/checkout.js') }}"></script>

@endpush
