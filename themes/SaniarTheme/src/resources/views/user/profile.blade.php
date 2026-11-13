@extends('front::user.layouts.master')

@section('user-content')

    <div class="col-lg-6 col-xs-12 pull-right">
        <div class="headline-profile">
            <span>اطلاعات شخصی</span>
        </div>
        <div class="profile-stats mt-3">
            <div class="profile-stats-row">
                <div class="col-lg-6 col-md-6 col-xs-12 pull-right" style="padding:0;">
                    <div class="profile-stats-col">
                        <p><span> نام و نام خانوادگی :</span>{{$user->fullname}}</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-xs-12 pull-right" style="padding:0;">
                    <div class="profile-stats-col">
                        <p><span>پست الکترونیک :</span>{{$user->email}}</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-xs-12 pull-right" style="padding:0;">
                    <div class="profile-stats-col">
                        <p><span>شماره تلفن همراه :</span>{{$user->mobile}}</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-xs-12 pull-right" style="padding:0;">
                    <div class="profile-stats-col">
                        <p><span>کد ملی :</span>@if($user->national_code) {{$user->national_code}} @else - @endif</p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-xs-12 pull-right" style="padding:0;">
                    <div class="profile-stats-col">
                        @if($user->newsletter==1)
                        <p><span>دریافت خبرنامه :</span>بله</p>
                            @else
                            <p><span>دریافت خبرنامه :</span>خیر</p>
                            @endif
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-xs-12 pull-right" style="padding:0;">
                    <div class="profile-stats-col">
                        <p><span>شماره کارت :</span>@if($user->card_number) {{$user->card_number}} @else - @endif</p>
                    </div>
                </div>
                <div class="profile-stats-action">
                    <a href="{{ route('front.user.profile.edit') }}" class="link-spoiler-edit"><i
                            class="fa fa-pencil"></i>ویرایش اطلاعات شخصی</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 col-xs-12 pull-right">
        <div class="headline-profile headline-profile-favorites">
            <span>لیست علاقه مندی</span>
        </div>
        <div class="profile-stats mt-3">
            @if ($user->favorites()->count())
                @foreach ($user->favorites()->latest()->take(2)->get() as $favorite)
            <div class="profile-recent-fav">
                <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}"><img src="{{  $favorite->product->image ? asset($favorite->product->image) : asset('/no-image-product.svg') }}" alt="{{ $favorite->product->title }}"></a>
                <div class="profile-recent-fav-col">
                    <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}">{{ $favorite->product->title }}</a>
                    <div class="profile-recent-fav-price">{{ $favorite->product->getLowestPrice() }}</div>
                </div>
            </div>
            @endforeach
            <div class="profile-stats-action">
                <a href="{{ route('front.favorites.index') }}" class="link-spoiler-edit"><i class="fa fa-pencil"></i>مشاهده و ویرایش لیست علاقه
                    مندی</a>
            </div>
            @else
                <div class="row">
                    <div class="col-12">
                        <div class="page dt-sl dt-sn pt-3">
                            <p class="text-center">چیزی برای نمایش وجود ندارد!</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="headline-profile order-end" style="margin-top:0;">
        <span>آخرین سفارش ها</span>
    </div>
    <div class="profile-stats profile-table">
        <div class="table-orders">
            <table class="table">
                <thead class="thead-light">
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">شماره سفارش</th>
                    <th scope="col">تاریخ ثبت سفارش</th>
                    <th scope="col">تخفیف</th>
                    <th scope="col">مبلغ کل</th>
                    <th scope="col">عملیات پرداخت</th>
                    <th scope="col">جزئیات</th>
                </tr>
                </thead>
                <tbody>
                @if(count($last_orders))
                    @foreach ($last_orders as $order)

                        <tr>
                            <td>{{ $loop->iteration}}</td>
                            <td class="text-info">{{ $order->id }}</td>
                            <td>{{ jdate($order->created_at)->format('%d %B %Y') }}</td>
                            @if($order->totalDiscount()!=0)
                                <td>{{ number_format($order->totalDiscount()) }} تومان</td>
                            @else
                                <td>بدون تخفیف</td>
                            @endif
                            <td>{{ number_format($order->price) }} تومان</td>
                            <td>
                                @if($order->status == 'paid')
                                    <span class="text-success">پرداخت شده</span>
                                @elseif($order->status == 'unpaid')
                                    <span class="text-danger">پرداخت نشده</span>
                                @else
                                    <span class="text-danger">لغو شده</span>
                                @endif
                            </td>
                            <td class="details-link">
                                <a href="{{ route('front.orders.show', ['order' => $order]) }}">
                                    <i class="mdi mdi-chevron-left"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7">چیزی برای نمایش وجود ندارد!</td>
                    </tr>
                @endif
                </tbody>
            </table>
            @if(count($last_orders))
            <a href="{{ route('front.orders.index') }}" class="table-orders-show-more">مشاهده لیست سفارش‌ها</a>
            @endif
        </div>
    </div>
    <div class="page-profile headline-profile-favorites">
        <div class="page-navigation">
            <div class="page-navigation-title">آخرین سفارش‌های من</div>
        </div>
        <div class="profile-orders">
            @if(count($last_orders))
                @foreach ($last_orders as $order)
            <div class="collapse">
                <div class="profile-orders-item">
                    <div class="profile-orders-header">
                        <a href="{{ route('front.orders.show', ['order' => $order]) }}" class="profile-orders-header-details">
                            <div class="profile-orders-header-summary">
                                <div class="profile-orders-header-row">
                                    <span class="profile-orders-header-id">{{ $order->id }}</span>
                                    <span class="profile-orders-header-state">

                                        @if($order->status == 'paid')
                                            <span class="text-success">پرداخت شده</span>
                                        @elseif($order->status == 'unpaid')
                                            <span class="text-danger">پرداخت نشده</span>
                                        @else
                                            <span class="text-danger">لغو شده</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </a>
                        <hr class="ui-separator">
                        <div class="profile-orders-header-data">
                            <div class="profile-info-row">
                                <div class="profile-info-label">تاریخ ثبت سفارش</div>
                                <div class="profile-info-value">{{ jdate($order->created_at)->format('%d %B %Y') }}</div>
                            </div>
                            <div class="profile-info-row">
                                <div class="profile-info-label">تخفیف</div>
                                <div class="profile-info-value">
                                    @if($order->totalDiscount()!=0)
                                        {{ number_format($order->totalDiscount()) }} تومان
                                    @else
                                        بدون تخفیف
                                    @endif
                                </div>
                            </div>
                            <div class="profile-info-row">
                                <div class="profile-info-label">مبلغ کل</div>
                                <div class="profile-info-value">{{ number_format($order->price) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                @endforeach
            @else
                <div class="collapse">
                    <div class="profile-orders-item text-center">
                        چیزی برای نمایش وجود ندارد!
                    </div>
                </div>
            @endif
        </div>
    </div>


@endsection

@section('user-content-bottom')
    <div class="adplacement pull-right">
        @include('front::widgets.middle-banners-4',['widget'=>$widgets])
    </div>

    <!--    adplacement--------------------------->
    @if($special_products->count())
    <div class="col-lg-12 col-md-12 col-xs-12 d-none pull-right">
        <div class="row">
            <div class="col-12">
                <div class="widget widget-product card">
                    <header class="card-header">
                        <span class="title-one">محصولات ویژه</span>
                    </header>
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
        </div>
    </div>
    <!--search-category------------------------->
    @endif

@endsection
@push('scripts')
    <!-- Start favorite delete -->
    <div class="modal fade" id="favorite-delete-modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="now-ui-icons location_pin"></i>
                        حذف از لیست علاقمندی ها
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <p>آیا تمایل به حذف این محصول از لیست علاقمندی ها دارید؟</p>

                            <div class="form-ui dt-sl">
                                <form id="favorite-remove-form" action="#" method="POST">
                                    <div class="modal-body text-center">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-danger btn-md">بله حذف شود</button>
                                        <button class="btn btn-light" data-dismiss="modal">لغو</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End favorite delete -->

    <script src="{{ theme_asset('js/pages/favorites/index.js') }}"></script>
@endpush
