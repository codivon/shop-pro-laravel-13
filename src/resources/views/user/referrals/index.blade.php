{{-- معرفی به دوستان — UserController@referrals : $refrrals, $directReferrals, $successfulReferrals, $totalReferrals, $totalRewards, $settings, $referralCode, $active --}}
@extends('front::user.layouts.master', ['title' => 'معرفی به دوستان'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    {{-- کارت‌های آمار --}}
    <div class="status-panel-user mb-3">
        <div class="row g-3">
            <div class="col-lg-4 col-6 w-100-in-400">
                <div class="content-box h-100">
                    <div class="container-fluid">
                        <div class="item">
                            <div class="row align-items-center">
                                <div class="col-3">
                                    <div class="icon">
                                        <i class="bi bi-people main-color-one-color font-24"></i>
                                    </div>
                                </div>
                                <div class="col-9">
                                    <p class="font-12 text-muted mb-0">زیرمجموعه‌های من</p>
                                    <h3 class="font-18 fw-bold mb-0">{{ $directReferrals->total() }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-6 w-100-in-400">
                <div class="content-box h-100">
                    <div class="container-fluid">
                        <div class="item">
                            <div class="row align-items-center">
                                <div class="col-3">
                                    <div class="icon">
                                        <i class="bi bi-check2-circle main-color-three-color font-24"></i>
                                    </div>
                                </div>
                                <div class="col-9">
                                    <p class="font-12 text-muted mb-0">خرید موفق زیرمجموعه‌ها</p>
                                    <h3 class="font-18 fw-bold mb-0">{{ $successfulReferrals }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-6 w-100-in-400">
                <div class="content-box h-100">
                    <div class="container-fluid">
                        <div class="item">
                            <div class="row align-items-center">
                                <div class="col-3">
                                    <div class="icon">
                                        <i class="bi bi-gift main-color-one-color font-24"></i>
                                    </div>
                                </div>
                                <div class="col-9">
                                    <p class="font-12 text-muted mb-0">جوایز دریافتی</p>
                                    <h3 class="font-18 fw-bold mb-0">{{ $totalRewards }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- کد معرف + لینک --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="row g-3 align-items-center">
                <div class="col-lg-7">
                    <h2 class="font-16 mb-2"><i class="bi bi-people main-color-one-color me-1"></i>دوستان خود را معرفی کنید و جایزه بگیرید</h2>
                    <p class="font-14 text-muted mb-2">
                        لینک زیر را برای دوستانتان ارسال کنید؛ با ثبت‌نام و اولین خرید موفقشان،
                        @if(($settings['user_referrals_gift_discount_type'] ?? 'amount') == 'percent')
                            <strong class="text-dark">{{ $settings['owner_referrals_amount'] ?? 5 }}٪</strong> تخفیف برای شما و
                            <strong class="text-dark">{{ $settings['user_referrals_amount'] ?? 10 }}٪</strong> تخفیف برای آن‌ها
                        @else
                            <strong class="text-dark">{{ number_format($settings['owner_referrals_amount'] ?? 5) }} {{ currencyTitle() }}</strong> تخفیف برای شما و
                            <strong class="text-dark">{{ number_format($settings['user_referrals_amount'] ?? 10) }} {{ currencyTitle() }}</strong> تخفیف برای آن‌ها
                        @endif
                        صادر می‌شود.
                    </p>
                    <p class="font-12 text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        حداقل مبلغ خرید برای دریافت جایزه: <strong class="text-dark">{{ number_format($settings['minimum_amount_gift'] ?? 0) }} {{ currencyTitle() }}</strong> —
                        حداقل تعداد محصول: <strong class="text-dark">{{ $settings['minimum_product_gift'] ?? 1 }}</strong>
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="saniar-referral-box rounded-3 p-3">
                        <span class="font-12 text-muted d-block mb-1">کد معرف شما</span>
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <strong class="font-20 main-color-one-color ltr-inline" id="referralCodeText">{{ $referralCode }}</strong>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm border-0 main-color-one-bg text-white rounded-3 px-3 js-copy-referral"
                                        data-copy="{{ $referralCode }}" aria-label="کپی کد معرف">
                                    <i class="bi bi-clipboard me-1"></i> کپی کد
                                </button>
                                <button type="button" class="btn btn-sm btn-light border rounded-3 px-3 js-copy-referral"
                                        data-copy="{{ url('/register?ref=') . $referralCode }}" aria-label="کپی لینک معرف">
                                    <i class="bi bi-link-45deg me-1"></i> کپی لینک
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- جوایز دریافتی --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="title-panel mb-3">
                <h2 class="font-16 mb-0"><i class="bi bi-gift main-color-one-color me-1"></i>جوایز و کدهای تخفیف دریافتی</h2>
            </div>

            <div class="responsive-table p-0">
                <table class="table main-table rounded-0">
                    <thead class="text-bg-dark bg-opacity-75 text-center">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">نقش</th>
                        <th scope="col">طرف مقابل</th>
                        <th scope="col">جایزه</th>
                        <th scope="col">تاریخ</th>
                    </tr>
                    </thead>
                    <tbody class="text-center">
                    @if($refrrals->count())
                        @foreach ($refrrals as $referral)
                            <tr>
                                <td class="font-14">{{ $loop->iteration }}</td>
                                <td class="font-14">
                                    @if($referral->owner_id == auth()->id())
                                        <span class="badge bg-success rounded-pill">معرف</span>
                                    @else
                                        <span class="badge bg-info rounded-pill">معرفی‌شده</span>
                                    @endif
                                </td>
                                <td class="font-14">
                                    @if($referral->owner_id == auth()->id())
                                        {{ $referral->user?->fullname ?: $referral->user?->username ?: '—' }}
                                    @else
                                        {{ $referral->owner?->fullname ?: $referral->owner?->username ?: '—' }}
                                    @endif
                                </td>
                                <td class="font-14">
                                    @if($referral->owner_id == auth()->id())
                                        @if($referral->ownerWalletHistory)
                                            <span class="badge bg-success rounded-pill">
                                                <i class="bi bi-wallet2 me-1"></i>
                                                {{ number_format(abs($referral->ownerWalletHistory->amount)) }} {{ currencyTitle() }} اعتبار کیف پول
                                            </span>
                                        @elseif($referral->referralDiscount)
                                            <span class="badge bg-warning text-dark rounded-pill ltr-inline">
                                                <i class="bi bi-ticket-perforated me-1"></i>
                                                {{ $referral->referralDiscount->code }} ({{ number_format($referral->referralDiscount->amount) }}{{ $referral->referralDiscount->type == 'percent' ? '٪' : ' ' . currencyTitle() }})
                                            </span>
                                        @else
                                            —
                                        @endif
                                    @else
                                        @if($referral->userWalletHistory)
                                            <span class="badge bg-success rounded-pill">
                                                <i class="bi bi-wallet2 me-1"></i>
                                                {{ number_format(abs($referral->userWalletHistory->amount)) }} {{ currencyTitle() }} اعتبار کیف پول
                                            </span>
                                        @elseif($referral->userDiscount)
                                            <span class="badge bg-warning text-dark rounded-pill ltr-inline">
                                                <i class="bi bi-ticket-perforated me-1"></i>
                                                {{ $referral->userDiscount->code }} ({{ number_format($referral->userDiscount->amount) }}{{ $referral->userDiscount->type == 'percent' ? '٪' : ' ' . currencyTitle() }})
                                            </span>
                                        @else
                                            —
                                        @endif
                                    @endif
                                </td>
                                <td class="font-14">{{ \Morilog\Jalali\Jalalian::fromDateTime($referral->created_at)->format('Y/m/d') }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" class="py-4 text-muted font-14">
                                هنوز جایزه‌ای دریافت نکرده‌اید؛ لینک معرف را برای دوستانتان بفرستید.
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($refrrals->hasPages())
        <div class="my-paginate mb-3">
            {{ $refrrals->links('front::components.paginate') }}
        </div>
    @endif

    {{-- زیرمجموعه‌های مستقیم --}}
    <div class="content-box">
        <div class="container-fluid">
            <div class="title-panel mb-3">
                <h2 class="font-16 mb-0"><i class="bi bi-person-plus main-color-one-color me-1"></i>زیرمجموعه‌های من</h2>
            </div>

            <div class="responsive-table p-0">
                <table class="table main-table rounded-0">
                    <thead class="text-bg-dark bg-opacity-75 text-center">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">کاربر</th>
                        <th scope="col">نام کاربری</th>
                        <th scope="col">تاریخ عضویت</th>
                        <th scope="col">وضعیت خرید</th>
                    </tr>
                    </thead>
                    <tbody class="text-center">
                    @if($directReferrals->count())
                        @foreach ($directReferrals as $referred)
                            <tr>
                                <td class="font-14">{{ $loop->iteration }}</td>
                                <td class="font-14">{{ $referred->fullname ?: '—' }}</td>
                                <td class="font-14 ltr-inline">{{ $referred->username }}</td>
                                <td class="font-14">{{ \Morilog\Jalali\Jalalian::fromDateTime($referred->created_at)->format('Y/m/d') }}</td>
                                <td class="font-14">
                                    @if($referred->has_qualified_purchase)
                                        <span class="badge bg-success rounded-pill"><i class="bi bi-check2 me-1"></i> خرید موفق</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">هنوز خرید نکرده</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" class="py-4 text-muted font-14">
                                هنوز کسی با کد معرف شما ثبت‌نام نکرده است.
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($directReferrals->hasPages())
        <div class="my-paginate mt-4">
            {{ $directReferrals->links('front::components.paginate') }}
        </div>
    @endif
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush
@push('scripts')
    <script src="{{ theme_asset('js/pages/user/panel.js') }}?v=1"></script>
@endpush
