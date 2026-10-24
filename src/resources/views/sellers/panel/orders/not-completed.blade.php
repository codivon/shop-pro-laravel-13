{{-- محصولات منتظر ارسال — سانیار فاز I
    قرارداد OrderSellerController@notCompleted : $prices (Price::paginate(20)
    با scope قیمت‌هایی که اقلام فروشنده در سفارش پرداخت‌شدهٔ ناتمام دارند) --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'محصولات منتظر ارسال', 'active' => 'orders-not'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=3">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush

@section('seller-content')
    {{-- سربرگ صفحه --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-hourglass-split main-color-one-color ms-1"></i>محصولات منتظر ارسال</h5>
                    <p class="font-13 text-muted mb-0">اقلامی که در سفارش‌های پرداخت‌شده هنوز ارسال نشده‌اند؛ برای جلوگیری از تأخیر، این لیست را روزانه بررسی کنید.</p>
                </div>
                <a href="{{ route('seller.orders.index') }}" class="btn btn-outline-primary rounded-3 font-14">
                    <i class="bi bi-cart-check ms-1"></i>تاریخچه سفارش‌ها
                </a>
            </div>
        </div>
    </div>

    <div class="content-box">
        <div class="container-fluid">
            <div class="py-3 border-bottom">
                <h6 class="fw-bold mb-0 font-14"><i class="bi bi-list-check main-color-one-color ms-1"></i>لیست اقلام در انتظار ارسال</h6>
            </div>
            @if($prices->count())
                <div class="table-responsive py-2">
                    <table class="table align-middle site-tabel mb-2">
                        <thead class="text-bg-dark bg-opacity-75 text-center">
                        <tr>
                            <th scope="col" style="width:60px;">ردیف</th>
                            <th scope="col" style="width:90px;">تصویر</th>
                            <th scope="col">محصول</th>
                            <th scope="col" class="text-nowrap">تعداد در انتظار</th>
                        </tr>
                        </thead>
                        <tbody class="text-center">
                        @foreach($prices as $price)
                            <tr>
                                <td class="font-13">{{ ($prices->currentPage() - 1) * $prices->perPage() + $loop->iteration }}</td>
                                <td>
                                    @if($price->product && $price->product->image)
                                        <img src="{{ asset($price->product->image) }}" alt="{{ $price->product->title }}"
                                             style="width:56px;height:56px;object-fit:cover;" class="rounded-3">
                                    @else
                                        <img src="{{ asset('no-image-product.svg') }}" alt="{{ $price->product->title ?? '' }}"
                                             style="width:56px;height:56px;object-fit:contain;" class="rounded-3">
                                    @endif
                                </td>
                                <td class="text-start">
                                    @if($price->product)
                                        <a href="{{ $price->product->link() }}" target="_blank"
                                           class="font-13 fw-bold text-decoration-none main-color-one-color">{{ $price->product->title }}</a>
                                    @else
                                        <span class="font-13">—</span>
                                    @endif
                                    <div class="font-12 text-muted mt-1">{{ $price->getAttributesName() }}</div>
                                </td>
                                <td><span class="badge rounded-pill warning-label font-13">{{ number_format($price->pendingToSend()) }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="py-3">
                    {{ $prices->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-emoji-smile main-color-one-color font-40 d-block mb-2"></i>
                    <p class="font-14 text-muted mb-0">عالی! هیچ محصولی در انتظار ارسال ندارید.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
