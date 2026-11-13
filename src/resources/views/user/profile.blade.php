@extends('front::user.layouts.master')

@section('user-content')

    {{-- اطلاعات شخصی — ریسکین‌شده با ui-box قالب سانیار (profile.html) --}}
    <div class="ui-boxs">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold">اطلاعات شخصی</h4>
                        <a href="{{ route('front.user.profile.edit') }}"
                           class="btn btn-sm main-color-one-bg waves-effect waves-light">ویرایش <i class="bi bi-pencil-fill text-white"></i></a>
                    </div>
                </div>
                <div class="ui-box-item-desc p-0">
                    <table class="table main-table shadow-none main-table-2">
                        <tbody>
                        <tr>
                            <td colspan="2" class="text-start p-3">
                                <h6>نام و نام خانوادگی</h6>
                                <p class="mt-2">{{ $user->fullname }}</p>
                            </td>
                            <td colspan="2" class="text-start p-3">
                                <h6>پست الکترونیک</h6>
                                <p class="mt-2">{{ $user->email }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="text-start p-3">
                                <h6>شماره تلفن همراه</h6>
                                <p class="mt-2">{{ $user->mobile }}</p>
                            </td>
                            <td colspan="2" class="text-start p-3">
                                <h6>کد ملی</h6>
                                <p class="mt-2">@if($user->national_code) {{ $user->national_code }} @else - @endif</p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="text-start p-3">
                                <h6>دریافت خبرنامه</h6>
                                <p class="mt-2">@if($user->newsletter==1) بله @else خیر @endif</p>
                            </td>
                            <td colspan="2" class="text-start p-3">
                                <h6>شماره کارت</h6>
                                <p class="mt-2">@if($user->card_number) {{ $user->card_number }} @else - @endif</p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- لیست علاقه مندی ها --}}
    <div class="ui-boxs mt-3">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold">لیست علاقه مندی</h4>
                        @if ($user->favorites()->count())
                            <a href="{{ route('front.favorites.index') }}"
                               class="btn btn-sm main-color-one-bg waves-effect waves-light">مشاهده و ویرایش لیست علاقه مندی <i class="bi bi-chevron-left text-white"></i></a>
                        @endif
                    </div>
                </div>
                <div class="ui-box-item-desc">
                    @if ($user->favorites()->count())
                        <div class="product-list-row">
                            @foreach ($user->favorites()->latest()->take(2)->get() as $favorite)
                                <div class="product-row">
                                    <div class="product-row-desc">
                                        <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}">
                                            <div class="product-row-desc-item">
                                                <div class="product-row-img">
                                                    <img src="{{ $favorite->product->image ? asset($favorite->product->image) : asset('/no-image-product.svg') }}"
                                                         alt="{{ $favorite->product->title }}" width="100">
                                                </div>
                                                <div class="product-row-title">
                                                    <h6>{{ $favorite->product->title }}</h6>
                                                    <div class="product-price">
                                                        <p>{{ $favorite->product->getLowestPrice() }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-muted py-3">چیزی برای نمایش وجود ندارد!</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- آخرین سفارش ها --}}
    <div class="ui-boxs mt-3">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold">آخرین سفارش ها</h4>
                        @if(count($last_orders))
                            <a href="{{ route('front.orders.index') }}"
                               class="btn btn-sm main-color-one-bg waves-effect waves-light">مشاهده لیست سفارش‌ها <i class="bi bi-chevron-left text-white"></i></a>
                        @endif
                    </div>
                </div>
                <div class="ui-box-item-desc p-0">
                    <div class="responsive-table p-0">
                        <table class="table main-table rounded-0">
                            <thead class="text-bg-dark bg-opacity-75 text-center">
                            <tr>
                                <th>#</th>
                                <th>شماره سفارش</th>
                                <th>تاریخ ثبت سفارش</th>
                                <th>تخفیف</th>
                                <th>مبلغ کل</th>
                                <th>عملیات پرداخت</th>
                                <th>جزئیات</th>
                            </tr>
                            </thead>
                            <tbody class="text-center">
                            @if(count($last_orders))
                                @foreach ($last_orders as $order)
                                    <tr>
                                        <td class="font-14">{{ $loop->iteration }}</td>
                                        <td class="font-14 main-color-one-color">{{ $order->id }}</td>
                                        <td class="font-14">{{ jdate($order->created_at)->format('%d %B %Y') }}</td>
                                        <td class="font-14">
                                            @if($order->totalDiscount()!=0)
                                                {{ number_format($order->totalDiscount()) }} تومان
                                            @else
                                                بدون تخفیف
                                            @endif
                                        </td>
                                        <td class="font-14">{{ number_format($order->price) }} تومان</td>
                                        <td class="font-14">
                                            @if($order->status == 'paid')
                                                <span class="success-label rounded-pill">پرداخت شده</span>
                                            @elseif($order->status == 'unpaid')
                                                <span class="danger-label rounded-pill">پرداخت نشده</span>
                                            @else
                                                <span class="danger-label rounded-pill">لغو شده</span>
                                            @endif
                                        </td>
                                        <td class="font-14">
                                            <a href="{{ route('front.orders.show', ['order' => $order]) }}"
                                               class="btn btn-sm border-0 main-color-one-bg waves-effect waves-light">مشاهده <i
                                                    class="bi bi-chevron-left text-white font-16"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">چیزی برای نمایش وجود ندارد!</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('user-content-bottom')
    <div class="content mt-4">
        <div class="container-fluid">
            <div class="adplacement">
                {{-- فاز ۱۵۳ — در نصب تازه ممکن است ویجت «بنر چهارتایی» هنوز ساخته/فعال نشده باشد؛
                     پاس دادن null به پارشال ویجت باعث Fatal «Attempt to read property key on null» می‌شد. --}}
                @if($widgets)
                    @include('front::widgets.middle-banners-4',['widget'=>$widgets])
                @endif
            </div>

            @if($special_products->count())
                <div class="content-box mt-4">
                    <div class="container-fluid py-3">
                        <div class="title title-panel d-flex align-items-baseline">
                            <i class="bi bi-stars font-25 main-color-one-color"></i>
                            <h6 class="font-16 ms-2">محصولات ویژه</h6>
                        </div>
                        <div class="product-carousel owl-carousel owl-theme owl-rtl owl-loaded owl-drag">
                            <div class="owl-stage-outer">
                                <div class="owl-stage"
                                     style="transform: translate3d(0px, 0px, 0px); transition: all 0s ease 0s; width: 2234px;">
                                    @php $i=1; @endphp
                                    @foreach ($special_products as $special_product)
                                        @include('front::partials.product-block', ['product' => $special_product])
                                        @php $i++; @endphp
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    @include('front::user.partials.favorite-delete-modal')
    <script src="{{ theme_asset('js/pages/user/jquery-bs5-modal-compat.js') }}"></script>
    <script src="{{ theme_asset('js/pages/favorites/index.js') }}"></script>
@endpush