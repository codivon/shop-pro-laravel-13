@extends('front::user.layouts.master')
@push('styles')
    <link rel="stylesheet" href="{{theme_asset('css/referrals.css')}}">
@endpush
@section('user-content')
    <!-- Start Content -->
    <div class="col-xl-12 col-lg-8 col-md-8 col-sm-12 headline-profile">
       {{-- <div class="section-title text-sm-title title-wide mb-1 no-after-title-wide dt-sl mb-2 px-res-1">
            <h2>کد معرف شما</h2>
        </div>--}}
        {{-- ================= خلاصه آماری ================= --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-6">
                <div class="referral-stat-card stat-total">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-body">
                        <span class="stat-label">کل زیرمجموعه‌ها</span>
                        <span class="stat-value">{{ $directReferrals->total() ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="referral-stat-card stat-success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-body">
                        <span class="stat-label">خرید موفق</span>
                        <span class="stat-value">{{ $successfulReferrals ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="referral-stat-card stat-reward">
                    <div class="stat-icon">
                        <i class="fas fa-gift"></i>
                    </div>
                    <div class="stat-body">
                        <span class="stat-label">جوایز دریافت‌شده</span>
                        <span class="stat-value">{{ $refrrals->total() ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= بخش اول: کد معرف کاربر ================= --}}
        <div class="row">
            <div class="col-12">

                <div class="dt-sl referral-code-box p-3 p-md-4 mb-4">
                    <div class="row align-items-center g-4">
                        {{-- کد و لینک --}}
                        <div class="col-lg-5 col-12">
                            <div class="referral-code-label mb-2">
                                <i class="feather icon-award"></i>
                                <span>کد اختصاصی شما</span>
                            </div>
                            <div class="referral-code-value d-flex align-items-center justify-content-between">
                                <span id="referralCodeText" class="referral-code-text">{{ $referralCode ?? auth('web')->user()->referral_code }}</span>
                                <button type="button" class="btn btn-sm btn-primary btn-copy" onclick="copyReferralCode(this)" data-original-text='<i class="feather icon-copy"></i> کپی'>
                                     کپی
                                </button>
                            </div>

                            <div class="referral-share mt-3">
                                <span class="hint-text d-block mb-2"><i class="feather icon-share-2"></i> لینک دعوت شما:</span>
                                <div class="d-flex align-items-center referral-link-wrapper">
                                    <input type="text" id="referralLinkInput" class="form-control form-control-sm" readonly
                                           value="{{ url('/login?ref=') . ($referralCode ?? auth('web')->user()->referral_code) }}">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-copy ms-2" onclick="copyReferralLink(this)" title="کپی لینک">
                                        کپی
                                    </button>
                                </div>
                            </div>

                            {{-- دکمه‌های اشتراک‌گذاری --}}
                            <div class="referral-social-share mt-3">
                                <span class="hint-text d-block mb-2"><i class="feather icon-send"></i> اشتراک‌گذاری سریع:</span>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="#" class="social-btn social-telegram" onclick="shareTelegram(event)" title="تلگرام">
                                        <i class="fab fa-telegram"></i>
                                    </a>
                                    <a href="#" class="social-btn social-whatsapp" onclick="shareWhatsapp(event)" title="واتساپ">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                    <a href="#" class="social-btn social-twitter" onclick="shareTwitter(event)" title="توییتر">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                    <a href="#" class="social-btn social-email" onclick="shareEmail(event)" title="ایمیل">
                                        <i class=" far fa-envelope"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- توضیحات و قوانین --}}
                        <div class="col-lg-7 col-12">
                            <div class="referral-description">
                                <p class="referral-intro mb-3">
                                    <i class="feather icon-info text-info"></i>
                                    این کد را برای دوستان خود ارسال کنید. به ازای هر نفری که با این کد در سایت ثبت‌نام کند
                                    و خرید موفق انجام دهد، جایزه‌ای برای شما و او در نظر گرفته می‌شود.
                                </p>
                                <div class="referral-rules-grid">
                                    <div class="referral-rule-card rule-owner">
                                        <div class="rule-icon">
                                            <i class="fas fa-user-check"></i>
                                        </div>
                                        <div class="rule-content">
                                            <span class="rule-title">پاداش شما (معرف)</span>
                                            <span class="rule-value">
                                                @if(($settings['user_referrals_gift_discount_type'] ?? 'amount') == 'percent')
                                                    <strong>{{ $settings['owner_referrals_amount'] ?? 5 }}٪</strong> تخفیف
                                                @else
                                                    <strong>{{ number_format($settings['owner_referrals_amount'] ?? 5) }} {{ currencyTitle() }}</strong> تخفیف
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <div class="referral-rule-card rule-friend">
                                        <div class="rule-icon">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                        <div class="rule-content">
                                            <span class="rule-title">پاداش فرد معرفی‌شده</span>
                                            <span class="rule-value">
                                                @if(($settings['user_referrals_gift_discount_type'] ?? 'amount') == 'percent')
                                                    <strong>{{ $settings['user_referrals_amount'] ?? 10 }}٪</strong> تخفیف
                                                @else
                                                    <strong>{{ number_format($settings['user_referrals_amount'] ?? 10) }} {{ currencyTitle() }}</strong> تخفیف
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="referral-condition mt-3">
                                    <i class="feather icon-alert-circle text-warning"></i>
                                    <span>
                                        شرط دریافت جایزه: حداقل خرید
                                        <strong>{{ number_format($settings['minimum_amount_gift'] ?? 0) }} {{ currencyTitle() }}</strong>
                                        و حداقل
                                        <strong>{{ $settings['minimum_product_gift'] ?? 1 }}</strong> محصول.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= بخش دوم: جوایز و تخفیف‌های دریافتی ================= --}}
        <div class="row">
            <div class="col-12">
                <div class="section-title text-sm-title title-wide mb-1 no-after-title-wide dt-sl mb-2 px-res-1">
                    <h2>جوایز و کد های تخفیفی که دریافت کرده‌اید</h2>
                </div>

                @if ($refrrals->count())
                    <div class="dt-sl referral-table-card">
                        <div class="table-responsive">
                            <table class="table table-order referral-table">
                                <thead>
                                <tr>
                                    <th>ردیف</th>
                                    <th>نوع جایزه</th>
                                    <th>کد تخفیف / مبلغ</th>
                                    <th>مقدار جایزه</th>
                                    <th>مربوط به معرفی</th>
                                    <th>تاریخ دریافت</th>
                                    <th>تاریخ اعتبار</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($refrrals as $refrral)
                                    @php
                                        $isOwner = $refrral->owner_id == auth('web')->id();

                                        // تعیین نوع جایزه
                                        $isWallet = false;
                                        $discount = null;
                                        $walletHistory = null;

                                        if ($isOwner) {
                                            // جایزه برای معرف (owner)
                                            if ($refrral->owner_wallet_history_id) {
                                                $isWallet = true;
                                                $walletHistory = $refrral->ownerWalletHistory;
                                                $amount = $walletHistory->amount ?? 0;
                                                $code = 'واریز به کیف پول';
                                            } else {
                                                $discount = $refrral->referralDiscount;
                                                $amount = $discount->amount ?? 0;
                                                $code = $discount->code ?? '-';
                                            }
                                        } else {
                                            // جایزه برای معرفی‌شونده (user)
                                            if ($refrral->user_wallet_history_id) {
                                                $isWallet = true;
                                                $walletHistory = $refrral->userWalletHistory;
                                                $amount = $walletHistory->amount ?? 0;
                                                $code = 'واریز به کیف پول';
                                            } else {
                                                $discount = $refrral->userDiscount;
                                                $amount = $discount->amount ?? 0;
                                                $code = $discount->code ?? '-';
                                            }
                                        }

                                        $discountType = ($discount && $discount->type == 'percent') ? '٪' : ' ' . currencyTitle();
                                        $giftType = $isWallet ? 'کیف پول' : 'کد تخفیف';
                                        $badgeClass = 'bg-light-primary';

                                        // نام کاربر مرتبط
                                        $relatedUser = $isOwner ? ($refrral->user ?? null) : ($refrral->owner ?? null);
                                        $relatedName = $relatedUser ? ($relatedUser->fullname ?? $relatedUser->username) : '-';
                                    @endphp

                                    <tr>
                                        <td class="text-info">{{ $loop->iteration }}</td>
                                        <td>
                                    <span class="badge {{ $badgeClass }}">
                                     {{--   <i class="{{ $isWallet ? 'fas fa-wallet' : 'fas fa-ticket-alt' }}"></i>--}}
                                        {{ $giftType }}
                                    </span>
                                        </td>
                                        <td>
                                            @if ($isWallet)
                                                <span class="text-success">{{ $code }}</span>
                                            @else
                                                <span class="badge bg-light-primary cursor-pointer" onclick="copyDiscountCode(this)">{{ $code }}</span>
                                            @endif
                                        </td>
                                        <td class="text-success fw-bold">
                                            {{ number_format($amount) }}{{ $discountType }}
                                        </td>
                                        <td>{{ $relatedName }}</td>
                                        <td class="text-muted">{{jdate($refrral->created_at)->format('%d %B %Y') }}</td>
                                        <td class="text-muted">{{ $isWallet ? '-' :  jdate($discount->end_date)->format('%d %B %Y') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $refrrals->links('front::components.paginate') }}
                        </div>
                    </div>
                @else
                    <div class="dt-sl referral-table-card empty-state-card">
                        <div class="empty-state-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <p>{{ trans('front::messages.profile.there-nothing-show') }}</p>
                        <span class="empty-state-hint">با اشتراک‌گذاری کد معرف خود، جوایز بیشتری کسب کنید.</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- ================= بخش سوم: زیرمجموعه مستقیم ================= --}}
        <div class="row mt-4">
            <div class="col-12">
                <div class="section-title text-sm-title title-wide mb-1 no-after-title-wide dt-sl mb-2 px-res-1">
                    <h2>افراد معرفی شده</h2>
                </div>

                @if (isset($directReferrals) && $directReferrals->count())
                    <div class="dt-sl referral-table-card">
                        <div class="table-responsive">
                            <table class="table table-order referral-table">
                                <thead>
                                <tr>
                                    <th>ردیف</th>
                                    <th>نام و نام خانوادگی</th>
                                    <th>شماره تلفن</th>
                                    <th>تاریخ ثبت‌نام</th>
                                    <th>وضعیت</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($directReferrals as $referredUser)
                                    <tr>
                                        <td class="text-info">{{ $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $referredUser->fullname ?? $referredUser->username}}</td>
                                        <td dir="ltr" class="text-muted">{{ $referredUser->username }}</td>
                                        <td class="text-muted">{{ jdate($referredUser->created_at)->format('%d %B %Y') }}</td>
                                        <td>
                                            @if($referredUser->has_qualified_purchase ?? false)
                                                <span class="badge bg-light-success">
                                                        <i class="feather icon-check-circle"></i> خرید موفق
                                                    </span>
                                            @else
                                                <span class="badge bg-light-warning">
                                                        <i class="feather icon-clock"></i> در انتظار خرید
                                                    </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $directReferrals->links('front::components.paginate') }}
                        </div>
                    </div>
                @else
                    <div class="dt-sl referral-table-card empty-state-card">
                        <div class="empty-state-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <p>{{ trans('front::messages.profile.there-nothing-show') }}</p>
                        <span class="empty-state-hint">هنوز کسی با کد معرف شما ثبت‌نام نکرده است.</span>
                    </div>
                @endif
            </div>
        </div>

    </div>
    <!-- End Content -->




@endsection
@push('scripts')
    <script>
        var referralLink = "{{ url('/register?ref=') . ($referralCode ?? auth('web')->user()->referral_code) }}";
        var referralCode = "{{ $referralCode ?? auth('web')->user()->referral_code }}";
    </script>
    <script src="{{theme_asset('js/pages/profile/referrals.js')}}"></script>

@endpush
