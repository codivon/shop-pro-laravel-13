
<!--search-category------------------------->
<div class="col-lg-3 col-md-4 col-xs-12 float-right pl-0 sidebar-d-show-mobile">
    <div class="sidebar-wrapper">

        {{--    <div class="box-sidebar">
                <div class="profile-box" style="border: none;">
                    <img src="{{theme_asset('images/profile/1.jpg')}}" class="profile-box-img-banner" alt="profile">
                </div>
            </div>

        @if (option('user_referrals_enable', 0) == 1)
            <div class="box-sidebar">
                <div class="profile-box">
                    <p>با دعوت از دوستان تان به {{ option('info_site_title') }}
                        <b>{{ option('owner_referrals_amount', 0) }}</b> درصد کد تخفیف بگیرید.</p>
                    <span>کد معرفی شما:</span><strong class="text-info"> {{ seller_info()->referral_code }}</strong>
                </div>
            </div>
        @endif--}}
        <div class="box-sidebar">
            <div class="profile-box">
                <div class="profile-box-avator">
                    @if(seller_info()->logo)
                        <img class="round" src="{{ asset(seller_info()->logo) }}" alt="{{ seller_info()->fullname }}">
                    @else
                        <span class="c-profile-nav__avatar"><?= mb_substr(seller_info()->business_name,0,1,'UTF-8') ?></span>
                    @endif

                </div>

                @php
                    $ids=[];
                    foreach (seller()->products()->get() as $product){
                    $questions=$product->comments()->get();
                    foreach ($questions as $question){
                        $ids[]=$question->id;
                    }
                    }
                    $questions_noanswer_count=\App\Models\Comment::whereIn('id',$ids)->where('status','noanswer')->get()
                 @endphp
                <h4 class="text-center font-weight-bold"> {{ seller_info()->business_name }}</h4>
                <div class="row profile-sidebar-menu mt-3">
                    <div class="col-4">
                        <a class="item" href="{{route('seller.questions.index')}}">
                            @if(count($questions_noanswer_count))
                                <span class="badge badge badge-danger notifications-count-number ">{{count($questions_noanswer_count)}}</span>

                            @endif
                            <i class="fa-regular fa-message"></i>
                            <p>پرسش ها</p>
                        </a>
                    </div>

                    <div class="col-4">
                        <a class="item" href="{{ route('seller.notifications.index') }}">
                            @if(count(seller()->notifications()->where('read',0)->get()))
                                <span class="badge badge badge-danger notifications-count-number ">{{count(seller()->notifications()->where('read',0)->get())}}</span>

                            @endif
                            <i class="fa-regular fa-envelope"></i>
                            <p>پیام ها</p>
                        </a>
                    </div>

                    <div class="col-4">
                        <a class="item" href="{{ route('seller.profile.index') }}">
                            <i class="fa-regular fa-user"></i>
                            <p>پروفایل</p>
                        </a>
                    </div>
                </div>

            </div>

            <div class="toggle-box profile-menu-items-mobile">
                <div class="toggle-box-active">

                </div>
            </div>


        </div>

    </div>

    <div class="sidebar-wrapper">

        <div class="c-card">
            <div class="c-card__header">
                <h2 class="c-card__title">امتیاز عملکرد شما</h2>
            </div>
            <div class="c-card__body c-card__body--grow">
                <div class="c-rating-chart">
                    <div class="c-rating-chart__stars-container c-rating-chart__stars-container--rating-stars">
                        <div class="c-rating-chart__stars-summary"></div>

                        <div class="product-star mb-1 font-size-20 text-center position-relative">
                            @for($i=1;$i<=5;$i++)
                                <i class="fa fa-star @if(floor(seller_info()->operation)>=$i) active @endif"></i>
                            @endfor
                        </div>

                    </div>
                    <div class="c-rating-chart__reg-from c-ui--mt-0 c-ui--mb-40">
                        عضویت {{ jdate(seller_info()->created_at)->ago() }}
                    </div>

                    <div class="c-rating-chart__stats">
                        <div class="c-rating-chart__stat">
                            <div class="c-rating-chart__stat-desc">عملکرد</div>
                            <div class="c-rating-chart__stat-value c-rating-chart__stat-value--desc">
                                {{ seller_info()->operation ? seller_info()->operation.'%' : 'ثبت نشده' }}
                            </div>
                        </div>
                        <div class="c-rating-chart__stat">
                            <div class="c-rating-chart__stat-desc">رضایت از کالا </div>
                            <div class="c-rating-chart__stat-value c-rating-chart__stat-value--desc">
                                {{ seller_info()->satisfaction ? seller_info()->satisfaction.'%' : 'ثبت نشده' }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-wrapper mt-2">
        <div class="c-card c-wallet-card">
            <div class="c-card__header">
                <h2 class="c-card__title">کیف پول</h2>
            </div>
            <div class="c-card__body">
                <div class="c-wallet-card__inventory uk-flex uk-flex-column uk-flex-middle">
                <span class="o-font-size-12 o-text-color-n-500 o-spacing-m-b-1">
                    موجودی کیف پول شما
                </span>
                    <div class="o-font-size-16 uk-text-bold uk-flex uk-flex-middle o-spacing-m-t-1">
                    <span class="o-font-size-30 uk-margin-small-left">
                        {{number_format(seller()->getWallet()->balance())}}
                    </span>
                        ریال
                    </div>
                </div>
                <div class="uk-flex uk-flex-between c-wallet-card__quantity">
                    <div class="uk-flex">
                        <span class="c-wallet-card__quantity--income-icon">
                           <i class="fa-solid fa-arrow-trend-up"></i>
                        </span>
                        <div>
                            <div class="o-font-size-14 o-text-color-n-500">
                                میزان واریزی
                            </div>
                            <div class="c-wallet--light c-wallet--fz-11">
                                در ۳۰ روز گذشته
                            </div>
                        </div>
                    </div>
                    <div class="uk-text-bold o-font-size-16 o-text-color-seller-secondary">
                        {{ number_format(App\Models\WalletHistory::where(['wallet_id'=>seller()->getWallet()->id,'status'=> 'success','type'=>'deposit'])->where('created_at', '>', now()->subDays(30)->endOfDay())->sum('amount'))}}
                        تومان
                    </div>
                </div>
                <div class="uk-flex uk-flex-between c-wallet-card__quantity">
                    <div class="uk-flex">
                        <span class="c-wallet-card__quantity--expenses-icon">
                           <i class="fa-solid fa-arrow-trend-down"></i>
                        </span>
                        <div>
                            <div class="o-font-size-14 o-text-color-n-500">
                                میزان برداشت
                            </div>
                            <div class="c-wallet--light c-wallet--fz-11">
                                در ۳۰ روز گذشته
                            </div>
                        </div>
                    </div>
                    <div class="uk-text-bold o-font-size-16 o-text-color-seller-error">
                        {{ number_format(App\Models\WalletHistory::where(['wallet_id'=>seller()->getWallet()->id,'status'=> 'success','type'=>'withdraw','status_pay'=>'pay'])->where('created_at', '>', now()->subDays(30)->endOfDay())->sum('amount'))}}
                        تومان
                    </div>
                </div>
                <div class="uk-flex uk-flex-center uk-flex-middle">
                    <a href="{{route('seller.wallet.index')}}" class="c-wallet-card__btn">
                        جزییات کیف پول
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
