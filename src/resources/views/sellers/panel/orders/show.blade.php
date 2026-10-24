{{-- جزئیات سفارش فروشنده — سانیار فاز I
    قرارداد OrderSellerController@show : $order (اسکوپ فروشنده در کنترلر اعمال شده)
    محاسبات مبلغ از اقلامِ همین فروشنده (products()) + هزینه ارسال آیتم نخست --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'جزئیات سفارش #' . $order->id, 'active' => 'orders'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=3">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush

@section('seller-content')
    @php
        $orderItem = $order->items()->where('seller_id', sellerID())->first();
        $products = $orderItem->products();

        $totalAmount = 0;
        $totalDiscount = 0;
        foreach ($products as $item) {
            $itemPrice = $item->real_price * $item->quantity;
            $itemDiscountAmount = $itemPrice * ($item->discount ?? 0) / 100;
            $totalDiscount += $itemDiscountAmount;
            $totalAmount += $itemPrice - $itemDiscountAmount;
        }
        $shippingCost = (int) ($orderItem->shipping_cost ?? 0);
        $finalPayable = $totalAmount + $shippingCost;

        $commissionInfo = $orderItem->seller_id ? $order->getAmountSellerCommission() : null;

        $disableStatus = false;
        if ($orderItem->refunded || $orderItem->shipping_status == 'canceled' || $order->status != 'paid') {
            $disableStatus = true;
        }

        $deliveryDate = null;
        if ($orderItem->delivery_date) {
            try {
                $deliveryDate = jdate($orderItem->delivery_date)->format('Y/m/d');
            } catch (\Throwable $e) {
                $deliveryDate = $orderItem->delivery_date;
            }
        }
    @endphp

    {{-- سربرگ صفحه --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-receipt main-color-one-color ms-1"></i>جزئیات سفارش #{{ $order->id }}</h5>
                    <p class="font-13 text-muted mb-0">ثبت شده در {{ jdate($order->created_at)->format('Y/m/d - H:i') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('seller.orders.print', ['order' => $order]) }}" target="_blank"
                       class="btn btn-outline-primary rounded-3 font-14">
                        <i class="bi bi-printer ms-1"></i>چاپ فاکتور
                    </a>
                    @if($order->hasPhysicalProduct())
                        <a href="{{ route('seller.orders.shipping-form', ['order' => $order]) }}" target="_blank"
                           class="btn btn-outline-success rounded-3 font-14">
                            <i class="bi bi-postcard ms-1"></i>چاپ برچسب مرسوله
                        </a>
                    @endif
                    <a href="{{ route('seller.orders.index') }}" class="btn btn-outline-secondary rounded-3 font-14">
                        <i class="bi bi-arrow-right ms-1"></i>بازگشت
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- کارت‌های خلاصه --}}
    <div class="row g-3 mb-3">
        {{-- فاکتور مرسوله --}}
        <div class="col-md-4">
            <div class="saniar-order-card border-top-success">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0 font-14"><i class="bi bi-file-earmark-text text-success ms-1"></i>فاکتور مرسوله</h6>
                    <span class="saniar-order-card-icon text-success"><i class="bi bi-file-earmark-text"></i></span>
                </div>
                <ul class="list-unstyled mb-0 font-13">
                    <li class="d-flex justify-content-between py-1"><span class="text-muted">شماره مرسوله</span><span class="fw-bold">#{{ $orderItem->id }}</span></li>
                    <li class="d-flex justify-content-between py-1 border-top"><span class="text-muted">وضعیت پرداخت</span>
                        <span class="fw-bold">
                            @if($order->status == 'paid')<span class="badge rounded-pill success-label">پرداخت شده</span>
                            @elseif($order->status == 'canceled')<span class="badge rounded-pill danger-label">لغو شده</span>
                            @else<span class="badge rounded-pill warning-label">پرداخت نشده</span>@endif
                        </span>
                    </li>
                    <li class="d-flex justify-content-between py-1 border-top"><span class="text-muted">مبلغ پرداخت شده</span>
                        <span class="fw-bold main-color-one-color">{{ number_format($finalPayable) }} <span class="font-12 text-muted">تومان</span></span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- اطلاعات فروشنده + کمیسیون --}}
        <div class="col-md-4">
            <div class="saniar-order-card border-top-info">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0 font-14"><i class="bi bi-shop text-info ms-1"></i>اطلاعات فروشنده</h6>
                    <span class="saniar-order-card-icon text-info"><i class="bi bi-shop"></i></span>
                </div>
                <ul class="list-unstyled mb-0 font-13">
                    <li class="d-flex justify-content-between py-1"><span class="text-muted">نام فروشنده</span>
                        <span class="fw-bold">{{ seller_info()->business_name ?? seller_info()->full_name ?? '—' }}</span>
                    </li>
                    @if($commissionInfo && $commissionInfo['commission'] > 0)
                        <li class="d-flex justify-content-between py-1 border-top"><span class="text-muted">کمیسیون سایت</span>
                            <span class="fw-bold text-danger">{{ number_format($commissionInfo['priceForSite']) }} <span class="font-12">({{ $commissionInfo['commission'] }}%)</span></span>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-top"><span class="text-muted"> تسویه من</span>
                            <span class="fw-bold text-success">{{ number_format($order->priceSeller() - $commissionInfo['priceForSite']) }} <span class="font-12 text-muted">تومان</span></span>
                        </li>
                    @else
                        <li class="d-flex justify-content-between py-1 border-top"><span class="text-muted">کمیسیون سایت</span><span class="fw-bold">۰ تومان</span></li>
                        <li class="d-flex justify-content-between py-1 border-top"><span class="text-muted"> تسویه من</span>
                            <span class="fw-bold text-success">{{ number_format($order->priceSeller()) }} <span class="font-12 text-muted">تومان</span></span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        {{-- وضعیت مرسوله + کد رهگیری --}}
        <div class="col-md-4">
            <div class="saniar-order-card {{ $orderItem->shipping_status == 'sent' || $orderItem->shipping_status == 'delivered' ? 'border-top-success' : ($orderItem->shipping_status == 'canceled' ? 'border-top-danger' : 'border-top-warning') }}">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0 font-14"><i class="bi bi-truck text-warning ms-1"></i>وضعیت مرسوله</h6>
                    <span class="saniar-order-card-icon text-warning"><i class="bi bi-truck"></i></span>
                </div>
                <div class="mb-2">
                    <span class="text-muted font-13">وضعیت ارسال:</span>
                    @php $ship_label = ['w-pending' => 'در انتظار بررسی', 'pending' => 'در حال بررسی', 'processing' => 'در حال پردازش', 'waiting' => 'منتظر ارسال', 'sent' => 'ارسال شد', 'post-sent' => 'تحویل به پست', 'delivered' => 'تحویل داده شد', 'canceled' => 'لغو شد'][$orderItem->shipping_status] ?? $orderItem->shipping_status; @endphp
                    <span class="badge rounded-pill {{ $orderItem->shipping_status == 'sent' || $orderItem->shipping_status == 'delivered' ? 'success-label' : ($orderItem->shipping_status == 'canceled' ? 'danger-label' : 'warning-label') }}">{{ $ship_label }}</span>
                </div>
                <label class="form-label font-13 fw-bold mb-1" for="sfo-tracking-code">کد رهگیری</label>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control font-13" id="sfo-tracking-code" dir="ltr"
                           value="{{ $orderItem->tracking_code }}" placeholder="کد رهگیری مرسوله…" {{ $disableStatus ? 'disabled' : '' }}>
                    <button class="btn btn-outline-primary" type="button" id="sfo-save-tracking"
                            data-action="{{ route('seller.orders.tracking-code', ['order' => $order]) }}" {{ $disableStatus ? 'disabled' : '' }}>
                        <i class="bi bi-save"></i>
                    </button>
                </div>
                @if($orderItem->refunded)
                    <div class="alert alert-danger font-12 mt-2 mb-0 py-2" role="alert">
                        <i class="bi bi-arrow-counterclockwise ms-1"></i>این آیتم برگشت وجه شده است.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- تغییر وضعیت ارسال --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-3 border-bottom">
                <h6 class="fw-bold mb-0 font-14"><i class="bi bi-gear main-color-one-color ms-1"></i>تغییر وضعیت مرسوله</h6>
            </div>
            <div class="row g-2 align-items-end py-3">
                <div class="col-md-4 col-8">
                    <label class="form-label font-13 fw-bold" for="sfo-shipping-status">وضعیت ارسال</label>
                    <select class="form-select font-13" id="sfo-shipping-status" {{ $disableStatus ? 'disabled' : '' }}>
                        @foreach(['w-pending' => 'در انتظار بررسی', 'pending' => 'در حال بررسی', 'processing' => 'در حال پردازش', 'waiting' => 'منتظر ارسال', 'sent' => 'ارسال شد', 'post-sent' => 'تحویل به پست', 'delivered' => 'تحویل داده شد', 'canceled' => 'لغو شد'] as $value => $label)
                            <option value="{{ $value }}" {{ $orderItem->shipping_status == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-4">
                    <button class="btn main-color-one-bg text-white rounded-3 font-14 w-100" id="sfo-save-status"
                            data-action="{{ route('seller.orders.shipping-status', ['order' => $order]) }}" {{ $disableStatus ? 'disabled' : '' }}>
                        <i class="bi bi-check-lg ms-1"></i>ذخیره تغییرات
                    </button>
                </div>
            </div>
            @if($order->status != 'paid')
                <div class="alert alert-warning font-13 mb-0" role="alert">
                    <i class="bi bi-exclamation-triangle ms-1"></i>سفارش اصلی هنوز پرداخت نشده است؛ تغییر وضعیت ارسال فقط پس از پرداخت امکان‌پذیر است.
                </div>
            @elseif($orderItem->shipping_status == 'canceled')
                <div class="alert alert-danger font-13 mb-0" role="alert">
                    <i class="bi bi-x-circle ms-1"></i>این مرسوله لغو شده و امکان تغییر وضعیت وجود ندارد.
                </div>
            @endif
        </div>
    </div>

    {{-- اطلاعات گیرنده --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-3 border-bottom">
                <h6 class="fw-bold mb-0 font-14"><i class="bi bi-person-badge main-color-one-color ms-1"></i>اطلاعات گیرنده و آدرس ارسال</h6>
                @if($order->location)
                    @php $loc = explode(',', $order->location); @endphp
                    <a href="https://maps.google.com/?q={{ $loc[0] ?? '' }},{{ $loc[1] ?? '' }}" target="_blank" rel="noopener"
                       class="btn btn-sm btn-outline-primary rounded-3 font-13">
                        <i class="bi bi-geo-alt ms-1"></i>نمایش موقعیت روی نقشه
                    </a>
                @endif
            </div>
            <div class="row g-0 py-3">
                <div class="col-md-4 col-6 mb-2">
                    <div class="font-12 text-muted">نام و نام خانوادگی</div>
                    <div class="font-13 fw-bold">{{ $order->name ?? '—' }}</div>
                </div>
                <div class="col-md-4 col-6 mb-2">
                    <div class="font-12 text-muted">شماره موبایل</div>
                    <div class="font-13 fw-bold" dir="ltr">{{ $order->mobile ?? '—' }}</div>
                </div>
                @if($order->hasPhysicalProduct())
                    <div class="col-md-4 col-6 mb-2">
                        <div class="font-12 text-muted">استان و شهر</div>
                        <div class="font-13 fw-bold">{{ $order->province->name ?? '' }} - {{ $order->city->name ?? '' }}</div>
                    </div>
                    <div class="col-md-4 col-6 mb-2">
                        <div class="font-12 text-muted">کد پستی</div>
                        <div class="font-13 fw-bold" dir="ltr">{{ $order->postal_code ?? '—' }}</div>
                    </div>
                    <div class="col-md-4 col-6 mb-2">
                        <div class="font-12 text-muted">شیوه تحویل</div>
                        <div class="font-13 fw-bold">{{ $orderItem->carrier ? $orderItem->carrier->title : 'پیک' }}</div>
                    </div>
                    @if($deliveryDate)
                        <div class="col-md-4 col-6 mb-2">
                            <div class="font-12 text-muted">تاریخ تحویل</div>
                            <div class="font-13 fw-bold">{{ $deliveryDate }}</div>
                        </div>
                    @endif
                    <div class="col-12 mb-2">
                        <div class="font-12 text-muted">آدرس کامل</div>
                        <div class="font-13">{{ $order->address ?? '—' }}</div>
                    </div>
                @else
                    <div class="col-12 mb-2">
                        <span class="font-13 text-muted"><i class="bi bi-info-circle ms-1"></i>این سفارش محصول فیزیکی ندارد (تحویل دانلودی).</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- جدول اقلام --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="py-3 border-bottom">
                <h6 class="fw-bold mb-0 font-14"><i class="bi bi-box-seam main-color-one-color ms-1"></i>اقلام این سفارش از فروشگاه من</h6>
            </div>
            <div class="table-responsive py-2">
                <table class="table align-middle site-tabel mb-2 saniar-order-items">
                    <thead class="text-bg-dark bg-opacity-75 text-center">
                    <tr>
                        <th scope="col" style="width:50px;">#</th>
                        <th scope="col" style="width:80px;">تصویر</th>
                        <th scope="col">عنوان محصول</th>
                        <th scope="col" class="text-nowrap">کد محصول</th>
                        <th scope="col" class="text-nowrap">تعداد</th>
                        <th scope="col" class="text-nowrap">قیمت واحد</th>
                        <th scope="col" class="text-nowrap">تخفیف</th>
                        <th scope="col" class="text-nowrap">قیمت نهایی</th>
                    </tr>
                    </thead>
                    <tbody class="text-center">
                    @foreach($products as $item)
                        @php
                            $itemPrice = $item->real_price * $item->quantity;
                            $itemDiscountAmount = $itemPrice * ($item->discount ?? 0) / 100;
                            $itemFinal = $itemPrice - $itemDiscountAmount;
                        @endphp
                        <tr>
                            <td class="font-13">{{ $loop->iteration }}</td>
                            <td>
                                @if($item->product && $item->product->image)
                                    <img src="{{ asset($item->product->image) }}" alt="{{ $item->title }}"
                                         style="width:48px;height:48px;object-fit:cover;" class="rounded-3">
                                @else
                                    <img src="{{ asset('no-image-product.svg') }}" alt="{{ $item->title }}"
                                         style="width:48px;height:48px;object-fit:contain;" class="rounded-3">
                                @endif
                            </td>
                            <td class="text-start">
                                <a href="{{ $item->product ? route('front.products.show', $item->product) : '#' }}" class="font-13 fw-bold text-decoration-none main-color-one-color">{{ $item->title }}</a>
                                @if($item->get_price)
                                    <div class="font-12 text-muted mt-1">
                                        @foreach($item->get_price->get_attributes as $attribute)
                                            @if($attribute->group->type == 'color')
                                                <span class="d-inline-flex align-items-center gap-1">
                                                    <span class="d-inline-block" style="width:12px;height:12px;border-radius:4px;background-color:{{ $attribute->value }};"></span>
                                                    {{ $attribute->group->name }}: {{ $attribute->name }}
                                                </span>
                                            @else
                                                <span>{{ $attribute->group->name }}: {{ $attribute->name }}</span>
                                            @endif
                                            @if(!$loop->last) <span class="mx-1">|</span> @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="font-13">{{ $item->product_id ?? '—' }}</td>
                            <td class="font-13">{{ number_format($item->quantity) }}</td>
                            <td class="font-13">{{ number_format($item->real_price) }} <span class="font-11 text-muted">تومان</span></td>
                            <td class="font-13 text-danger">{{ ($item->discount ?? 0) ? $item->discount . '%' : '—' }}</td>
                            <td class="font-13 fw-bold text-success">{{ number_format($itemFinal) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="6" class="text-end font-13 fw-bold">جمع تخفیف اقلام:</td>
                        <td colspan="2" class="text-center font-13 fw-bold text-danger">{{ number_format($totalDiscount) }} <span class="font-11">تومان</span></td>
                    </tr>
                    <tr>
                        <td colspan="6" class="text-end font-13 fw-bold">جمع کل اقلام:</td>
                        <td colspan="2" class="text-center font-13 fw-bold">{{ number_format($totalAmount) }} <span class="font-11">تومان</span></td>
                    </tr>
                    @if($shippingCost > 0)
                        <tr>
                            <td colspan="6" class="text-end font-13 fw-bold">هزینه ارسال:</td>
                            <td colspan="2" class="text-center font-13 fw-bold">{{ number_format($shippingCost) }} <span class="font-11">تومان</span></td>
                        </tr>
                    @endif
                    <tr class="saniar-order-total-row">
                        <td colspan="6" class="text-end font-14 fw-bold">مبلغ قابل پرداخت نهایی:</td>
                        <td colspan="2" class="text-center font-16 fw-bold main-color-one-color">{{ number_format($finalPayable) }} <span class="font-12">تومان</span></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- توضیحات سفارش --}}
    @if($order->description)
        <div class="content-box mb-3">
            <div class="container-fluid">
                <div class="py-3 border-bottom">
                    <h6 class="fw-bold mb-0 font-14"><i class="bi bi-chat-left-text main-color-one-color ms-1"></i>توضیحات سفارش</h6>
                </div>
                <p class="font-13 py-3 mb-0">{{ $order->description }}</p>
            </div>
        </div>
    @endif

    {{-- مودال تأیید لغو سفارش --}}
    <div class="modal fade" id="sfo-cancel-modal" tabindex="-1" aria-hidden="true" aria-labelledby="sfo-cancel-title">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-15" id="sfo-cancel-title"><i class="bi bi-x-circle text-danger ms-1"></i>تأیید لغو سفارش</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info font-13 mb-3" role="alert">
                        <i class="bi bi-info-circle ms-1"></i>
                        در صورت تأیید، مرسوله به مبلغ <strong>{{ number_format($finalPayable) }}</strong> تومان لغو می‌شود.
                    </div>
                    @if($orderItem->seller_id && $commissionInfo)
                        <div class="alert alert-warning font-13" role="alert">
                            <strong><i class="bi bi-arrow-left-right ms-1"></i>گردش مالی:</strong>
                            <ul class="mb-0 mt-2 font-12">
                                <li>کسر از کیف پول فروشنده: <strong class="text-danger">{{ number_format($order->priceSeller() - $commissionInfo['priceForSite']) }} تومان</strong></li>
                                <li>افزودن به کیف پول خریدار: <strong class="text-success">{{ number_format($finalPayable) }} تومان</strong></li>
                            </ul>
                        </div>
                        <div class="alert alert-danger font-12" role="alert">
                            <i class="bi bi-exclamation-triangle ms-1"></i>
                            <strong>شرط لغو:</strong> موجودی کیف پول شما باید حداقل <strong>{{ number_format($order->priceSeller() - $commissionInfo['priceForSite']) }}</strong> تومان باشد؛ در غیر این صورت عملیات لغو انجام نمی‌شود.
                        </div>
                    @endif
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="sfo-cancel-refund" checked>
                        <label class="form-check-label font-13" for="sfo-cancel-refund">بازگشت مبلغ به کیف پول خریدار</label>
                    </div>
                    <label class="form-label font-13 fw-bold" for="sfo-cancel-reason">دلیل لغو سفارش</label>
                    <textarea class="form-control font-13" id="sfo-cancel-reason" rows="3" placeholder="دلیل لغو سفارش را وارد کنید…"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-3 font-13" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-danger rounded-3 font-13" id="sfo-confirm-cancel">
                        <i class="bi bi-check-lg ms-1"></i>تأیید و لغو سفارش
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-orders.js') }}?v=1"
            data-saniar-orders="show"></script>
@endpush
