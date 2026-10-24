{{-- پیشخوان حساب کاربری — قرارداد UserController@profile : $user, $last_orders, $special_products, $widgets, $active --}}
@extends('front::user.layouts.master', ['title' => 'پیشخوان حساب کاربری'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    @php
        $ordersCount   = $user->orders()->count();
        $addressesCount = $user->addresses()->count();
        $favoritesCount = $user->favorites()->count();
    @endphp

    {{-- توست موفقیت (بازگشت از صفحه‌های reset/ویرایش پروفایل) --}}
    @if(request('password') == 'changed')
        <div class="saniar-panel-toast saniar-panel-toast-show saniar-panel-toast-success" role="status">
            <i class="bi bi-check-circle-fill"></i>
            <span>رمز عبور شما با موفقیت تغییر کرد.</span>
        </div>
    @elseif(request('profile') == 'updated')
        <div class="saniar-panel-toast saniar-panel-toast-show saniar-panel-toast-success" role="status">
            <i class="bi bi-check-circle-fill"></i>
            <span>اطلاعات کاربری شما با موفقیت به‌روزرسانی شد.</span>
        </div>
    @endif

    {{-- جعبه خوش‌آمد (مطابق user-panel.html) --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="row gy-3 align-items-center">
                <div class="col-lg-8">
                    <h2 class="font-16 main-color-one-color mb-2">سلام {{ $user->first_name ?: $user->mobile }} عزیز</h2>
                    <h3 class="font-14 my-2">به {{ option('info_site_title', config('front.demo.name')) }} خوش آمدید</h3>
                    <p class="text-muted font-14 mb-0 line-height-2">
                        از پیشخوان حساب کاربری خود می‌توانید
                        <span class="def-color fw-bold">آخرین سفارش‌ها</span> را ببینید، به‌راحتی
                        <span class="def-color fw-bold">آدرس حمل و نقل و صورت‌حساب</span> را مدیریت کنید و
                        <span class="def-color fw-bold">اطلاعات حساب کاربری و رمز عبور</span> خود را تغییر دهید.
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="panel-image text-center">
                        <img src="{{ theme_asset('image/panel.svg') }}" alt="پیشخوان حساب کاربری" class="img-fluid" width="220" loading="lazy">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- کارت‌های وضعیت (status-panel-user) --}}
    <div class="status-panel-user mb-3">
        <div class="row g-3">
            <div class="col-lg-3 col-6 w-100-in-400">
                <a href="{{ route('front.orders.index') }}">
                    <div class="content-box">
                        <div class="container-fluid">
                            <div class="item">
                                <div class="row align-items-center">
                                    <div class="col-2">
                                        <div class="icon">
                                            <i class="bi bi-cart main-color-one-color font-30"></i>
                                        </div>
                                    </div>
                                    <div class="col-10">
                                        <h3 class="font-16 mb-0">سفارشات من
                                            <span class="badge rounded-pill badge-spn">{{ $ordersCount }}</span>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-6 w-100-in-400">
                <a href="{{ route('front.addresses.index') }}">
                    <div class="content-box">
                        <div class="container-fluid">
                            <div class="item">
                                <div class="row align-items-center">
                                    <div class="col-2">
                                        <div class="icon">
                                            <i class="bi bi-pin-map main-color-one-color font-30"></i>
                                        </div>
                                    </div>
                                    <div class="col-10">
                                        <h3 class="font-16 mb-0">آدرس های من
                                            <span class="badge rounded-pill badge-spn">{{ $addressesCount }}</span>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-6 w-100-in-400">
                <a href="{{ route('front.favorites.index') }}">
                    <div class="content-box">
                        <div class="container-fluid">
                            <div class="item">
                                <div class="row align-items-center">
                                    <div class="col-2">
                                        <div class="icon">
                                            <i class="bi bi-heart main-color-one-color font-30"></i>
                                        </div>
                                    </div>
                                    <div class="col-10">
                                        <h3 class="font-16 mb-0">علاقه‌مندی‌ها
                                            <span class="badge rounded-pill badge-spn">{{ $favoritesCount }}</span>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-6 w-100-in-400">
                <a href="{{ route('front.wallet.index') }}">
                    <div class="content-box">
                        <div class="container-fluid">
                            <div class="item">
                                <div class="row align-items-center">
                                    <div class="col-2">
                                        <div class="icon">
                                            <i class="bi bi-wallet2 main-color-one-color font-30"></i>
                                        </div>
                                    </div>
                                    <div class="col-10">
                                        <h3 class="font-16 mb-0">کیف پول من
                                            <span class="badge rounded-pill badge-spn">{{ number_format($user->getWallet()->balance()) }}</span>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="row gy-3">
        {{-- اطلاعات شخصی --}}
        <div class="col-lg-6">
            <div class="content-box h-100">
                <div class="container-fluid">
                    <div class="title title-panel d-flex align-items-baseline mb-3">
                        <i class="bi bi-person-vcard font-25 main-color-one-color"></i>
                        <h3 class="font-16 ms-2 mb-0">اطلاعات <span class="main-color-one-color">شخصی</span></h3>
                    </div>
                    <div class="row g-2 font-14">
                        <div class="col-sm-6">
                            <div class="saniar-info-cell">
                                <span class="text-muted">نام و نام خانوادگی</span>
                                <strong>{{ $user->fullname ?: '—' }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="saniar-info-cell">
                                <span class="text-muted">شماره موبایل</span>
                                <strong class="ltr-inline">{{ $user->mobile }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="saniar-info-cell">
                                <span class="text-muted">پست الکترونیک</span>
                                <strong class="ltr-inline">{{ $user->email ?: '—' }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="saniar-info-cell">
                                <span class="text-muted">کد ملی</span>
                                <strong>{{ $user->national_code ?: '—' }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="saniar-info-cell">
                                <span class="text-muted">دریافت خبرنامه</span>
                                <strong>{{ $user->newsletter == 1 ? 'بله' : 'خیر' }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="saniar-info-cell">
                                <span class="text-muted">شماره کارت</span>
                                <strong class="ltr-inline">{{ $user->card_number ?: '—' }}</strong>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('front.user.profile.edit') }}"
                       class="btn btn-sm border-0 main-color-one-bg text-white rounded-3 px-4 mt-3 waves-effect waves-light">
                        <i class="bi bi-pencil-square me-1"></i> ویرایش اطلاعات شخصی
                    </a>
                </div>
            </div>
        </div>

        {{-- لیست علاقه‌مندی (۲ آیتم اخیر) --}}
        <div class="col-lg-6">
            <div class="content-box h-100">
                <div class="container-fluid">
                    <div class="title title-panel d-flex align-items-baseline mb-3">
                        <i class="bi bi-heart font-25 main-color-one-color"></i>
                        <h3 class="font-16 ms-2 mb-0">لیست <span class="main-color-one-color">علاقه‌مندی</span></h3>
                    </div>
                    @if ($favoritesCount)
                        @foreach ($user->favorites()->latest()->take(2)->get() as $favorite)
                            <div class="d-flex align-items-center gap-2 saniar-mini-product mb-2">
                                <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}">
                                    <img src="{{ $favorite->product->image ? asset($favorite->product->image) : asset('no-image-product.svg') }}"
                                         alt="{{ $favorite->product->title }}" loading="lazy" width="64" height="64" class="rounded-3">
                                </a>
                                <div class="flex-grow-1 min-w-0">
                                    <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}"
                                       class="d-block font-13 text-dark text-decoration-none text-overflow-1">{{ \Illuminate\Support\Str::limit($favorite->product->title, 44) }}</a>
                                    <span class="font-12 main-color-one-color fw-bold">{{ $favorite->product->getLowestPrice() }}</span>
                                </div>
                            </div>
                        @endforeach
                        <a href="{{ route('front.favorites.index') }}"
                           class="btn btn-sm btn-light rounded-3 px-4 mt-2 border">
                            <i class="bi bi-arrow-left me-1"></i> مشاهده و ویرایش لیست علاقه‌مندی
                        </a>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-heart text-muted" style="font-size:2.4rem;"></i>
                            <p class="font-14 text-muted mt-2 mb-3">هنوز محصولی به لیست علاقه‌مندی اضافه نکرده‌اید.</p>
                            <a href="{{ route('front.products.index') }}" class="btn btn-sm border-0 main-color-one-bg text-white rounded-3 px-4">مشاهده محصولات</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- آخرین سفارش‌ها (site-tabel مطابق user-panel.html) --}}
    <div class="lastest-order mt-3">
        <div class="content-box">
            <div class="container-fluid">
                <div class="site-tabel">
                    <div class="title title-panel d-flex align-items-baseline">
                        <i class="bi bi-cart-check font-25"></i>
                        <h3 class="font-16 ms-2"> آخرین <span class="main-color-one-color">سفارشات</span></h3>
                    </div>

                    @if(count($last_orders))
                        @foreach ($last_orders as $order)
                            @php $firstItem = $order->items->first(); @endphp
                            <div class="site-tabel-content">
                                <div class="image st-dv st-dv-1">
                                    @if($firstItem && $firstItem->product && $firstItem->product->image)
                                        <img src="{{ asset($firstItem->product->image) }}" alt="{{ $firstItem->title }}" class="img-fluid" loading="lazy">
                                    @else
                                        <img src="{{ asset('no-image-product.svg') }}" alt="محصول" class="img-fluid" loading="lazy">
                                    @endif
                                </div>
                                <div class="pro-title st-dv st-dv-2">
                                    <a href="{{ route('front.orders.show', ['order' => $order]) }}" class="text-dark">
                                        <p class="fw-bold mb-0 font-13">{{ \Illuminate\Support\Str::limit($firstItem ? $firstItem->title : 'سفارش شماره '.$order->id, 52) }}</p>
                                    </a>
                                </div>
                                <div class="order-detail st-dv st-dv-3">
                                    <div class="d-flex align-items-center">
                                        <p class="mb-0 text-muted">کد سفارش</p>
                                        <p class="mb-0 ms-2 fw-bold">{{ $order->id }}</p>
                                    </div>
                                    <p class="mb-0 text-muted mt-2 font-13">{{ \Morilog\Jalali\Jalalian::fromDateTime($order->created_at)->format('j F Y') }}</p>
                                </div>
                                <div class="order-detail st-dv st-dv-4">
                                    <div class="d-flex align-items-center">
                                        <p class="mb-0 text-muted">مبلغ</p>
                                        <p class="mb-0 ms-2 fw-bold">{{ number_format($order->price) }} تومان</p>
                                    </div>
                                    <p class="mb-0 fw-bold mt-2 font-13">
                                        @if($order->status == 'paid')
                                            <i class="bi bi-check-circle-fill main-color-three-color"></i> پرداخت شده
                                        @elseif($order->status == 'unpaid')
                                            <i class="bi bi-hourglass-split text-warning"></i> در انتظار پرداخت
                                        @else
                                            <i class="bi bi-x-circle-fill text-danger"></i> لغو شده
                                        @endif
                                    </p>
                                </div>
                                <div class="order-detail st-dv st-dv-5">
                                    <a href="{{ route('front.orders.show', ['order' => $order]) }}">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-printer me-2 main-color-one-color"></i>
                                            <p class="mb-0 main-color-one-color fw-bold">مشاهده سفارش</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                        <div class="text-center py-3">
                            <a href="{{ route('front.orders.index') }}" class="btn btn-light rounded-3 px-4 border">
                                <i class="bi bi-list-ul me-1"></i> مشاهده لیست سفارش‌ها
                            </a>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-cart-x text-muted" style="font-size:2.6rem;"></i>
                            <h4 class="font-16 mt-3">هنوز سفارشی ثبت نکرده‌اید</h4>
                            <p class="text-muted font-14">اولین خرید خود را همین حالا شروع کنید.</p>
                            <a href="{{ route('front.products.index') }}" class="btn border-0 main-color-one-bg text-white rounded-3 px-4">
                                <i class="bi bi-grid me-1"></i> شروع خرید
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- بنرهای میانی (ویجت داینامیک) --}}
    @if($widgets)
        <div class="mt-3 saniar-profile-banners">
            @include('front::widgets.middle-banners-4', ['widget' => $widgets])
        </div>
    @endif
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush
@push('scripts')
    <script src="{{ theme_asset('js/pages/user/panel.js') }}?v=1"></script>
@endpush
