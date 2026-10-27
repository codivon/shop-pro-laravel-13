@extends('sellers-old.panel.layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('back/assets/css/pages/order.css') }}">
    <style>
        .info-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .info-label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .info-value {
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
        }

        .price-value {
            font-size: 18px;
            font-weight: 700;
            color: #28a745;
        }

        .badge-custom {
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
        }

        .table-product th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .border-left-success {
            border-left: 4px solid #28a745;
        }

        .border-left-warning {
            border-left: 4px solid #ffc107;
        }

        .border-left-info {
            border-left: 4px solid #17a2b8;
        }

        .border-left-danger {
            border-left: 4px solid #dc3545;
        }

        .table thead th {
            color: #000;
        }

        table .bg-light {
            background-color: #babfc70d !important;
        }

        table .table-success, .table-success > th, .table-success > td {
            background-color: #c3efd70d;
            border: unset;
        }

        .order-product-color {
            width: 15px;
            height: 15px;
            border-radius: 5px;
            margin: 3px 0;
            border: 1px solid;
            margin-left: 5px;
        }

        .btn-save {
            text-align: center;
            width: max-content;
            display: block;
            height: 45px;
        }
        #update-tracking{
            z-index: 0;
        }
    </style>
@endpush

@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col d-flex justify-content-between">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">جزئیات سفارش‌</span>
                <span class="c-content-page__header-desc">اینجا می‌توانید جزئیات سفارش‌های خود را ببینید.</span>
            </div>
            <a href="{{ route('seller.orders.index') }}" class="btn btn-outline-info waves-effect waves-light">
                <i class="feather icon-arrow-right"></i> بازگشت به سفارش
            </a>
        </div>
    </div>
    @php
        $orderItem=$order->items()->where('seller_id',sellerID())->first();
            $totalAmount = [($orderItem->shipping_cost ?? 0)];
            $totalDiscount = 0;
            $totalDiscountAmount = 0;
            $finalPayable=[($orderItem->shipping_cost ?? 0)];
            $products = $orderItem->products();
    @endphp


    @foreach($products as $index => $item)
        @php

            $itemPrice = $item->real_price * $item->quantity;
            $itemDiscountPercent = ($item->discount ?? 0);

            $itemDiscountAmount = ($itemPrice * $itemDiscountPercent / 100);
            $itemFinal = $itemPrice - $itemDiscountAmount;
            $totalDiscount += $itemDiscountAmount;
            $totalAmount[] += $itemFinal;


            //$finalPayable[] = $totalAmount - $totalDiscount;
        @endphp
    @endforeach

    <div class="app-content content">
        <div class="content-wrapper mt-0 pt-0">
            {{-- Header --}}
            <div class="content-header ">


            {{-- فرم تغییر وضعیت --}}
            @php
                $disableStatus = false;

                // اگر سفارش قبلاً لغو شده باشد
                if (isset($orderCanceled) && $orderCanceled->orderCanceled) {
                    $disableStatus = true;
                }

                // اگر سفارش پرداخت نشده باشد
                if ($orderItem->order->status != 'paid') {
                    $disableStatus = true;
                }

                // اگر این آیتم قبلاً برگشت داده شده باشد
                if ($orderItem->refunded) {
                    $disableStatus = true;
                }

                // اگر وضعیت فعلی canceled باشد
                if ($orderItem->shipping_status == 'canceled') {
                    $disableStatus = true;
                }
            @endphp
            <div class="row ">
                <div class="col-12">
                    <div class="card info-card">
                        <div class="card-header d-flex">
                            <h5 class="card-title">⚙️ تغییر وضعیت مرسوله</h5>
                            <a href="{{ route('seller.orders.print', ['order' => $order]) }}" target="_blank" class="btn btn-outline-primary waves-effect waves-light"><i class="feather icon-printer"></i> چاپ</a>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>وضعیت ارسال</label>
                                        <select class="form-control" id="shipping-status-change" {{ $disableStatus ? 'disabled' : '' }}>
                                            <option value="w-pending" {{ $orderItem->shipping_status == 'w-pending' ? 'selected' : '' }}>
                                                در انتظار بررسی
                                            </option>
                                            <option value="pending" {{ $orderItem->shipping_status == 'pending' ? 'selected' : '' }}>
                                                در حال بررسی
                                            </option>
                                            <option value="processing" {{ $orderItem->shipping_status == 'processing' ? 'selected' : '' }}>
                                                در حال پردازش
                                            </option>
                                            <option value="waiting" {{ $orderItem->shipping_status == 'waiting' ? 'selected' : '' }}>
                                                منتظر ارسال
                                            </option>
                                            <option value="sent" {{ $orderItem->shipping_status == 'sent' ? 'selected' : '' }}>
                                                ارسال شد
                                            </option>
                                            <option value="post-sent" {{ $orderItem->shipping_status == 'post-sent' ? 'selected' : '' }}>
                                                تحویل به پست
                                            </option>
                                            <option value="delivered" {{ $orderItem->shipping_status == 'delivered' ? 'selected' : '' }}>
                                                تحویل داده شد
                                            </option>
                                            <option value="canceled" {{ $orderItem->shipping_status == 'canceled' ? 'selected' : '' }}>
                                                لغو شد
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button class="btn btn-primary btn-block btn-save" id="update-status" {{ $disableStatus ? 'disabled' : '' }} data-action="{{ route('seller.orders.shipping-status', ['order' => $order]) }}">
                                            <i class="feather icon-save"></i> ذخیره تغییرات
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @if($orderItem->order && $orderItem->order->status != 'paid')
                                <div class="alert alert-warning ">
                                    <i class="feather icon-alert-circle"></i>
                                    سفارش اصلی هنوز پرداخت نشده است. تغییر وضعیت ارسال فقط پس از پرداخت امکان‌پذیر
                                    است.
                                </div>
                            @endif

                            @if(isset($orderCanceled) && $orderCanceled->orderCanceled)
                                <div class="alert alert-danger mt-2">
                                    <i class="feather icon-info"></i>
                                    سفارش قبلاً لغو شده و وجه آن برگشت داده شده است. امکان تغییر وضعیت وجود ندارد.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>


            <div class="content-body">
                {{-- کارت‌های اطلاعاتی --}}
                <div class="row">
                    {{-- کارت فاکتور --}}
                    <div class="col-md-4">
                        <div class="card info-card border-left-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 text-success">📄 فاکتور مرسوله</h6>
                                    <i class="feather icon-file-text text-success" style="font-size: 24px;"></i>
                                </div>
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="info-label">شماره مرسوله</div>
                                        <div class="info-value mb-2">{{ $orderItem->id }}</div>

                                    </div>

                                    <div class="col-md-7">
                                        <div class="info-label">تاریخ ثبت</div>
                                        <div class="info-value mb-2">{{ jdate($orderItem->created_at)->format('Y/m/d - H:i') }}</div>

                                    </div>

                                    <div class="col-md-5">
                                        <div class="info-label">وضعیت پرداخت</div>
                                        <div class="info-value mb-2">
                                            <span class="badge {{ $orderItem->order->status == 'paid' ? 'badge-success' : 'badge-warning' }}">
                                            {{ $orderItem->order->status == 'paid' ? 'پرداخت شده' : 'پرداخت نشده' }}
                                        </span>
                                        </div>
                                    </div>


                                </div>


                                <div class="info-label">مبلغ پرداخت شده</div>
                                <div class="price-value">{{ number_format(array_sum($totalAmount)) }}
                                    تومان
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- کارت فروشنده --}}
                    <div class="col-md-4">
                        <div class="card info-card border-left-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 text-info">🏪 اطلاعات فروشنده</h6>
                                    <i class="feather icon-shopping-cart text-info" style="font-size: 24px;"></i>
                                </div>
                                <div class="info-label">نام فروشنده</div>
                                <div class="info-value mb-2 d-flex justify-content-between">
                                    <div>
                                        <strong>{{ seller_info()->full_name }}</strong>
                                        @if($orderItem->seller && $orderItem->seller->mobile)
                                            <br><small class="text-muted">{{ $orderItem->seller->mobile }}</small>
                                        @endif
                                    </div>

                                </div>


                                <div class="info-label">کمیسیون فروشنده</div>
                                <div class="info-value  mb-2">
                                    @if($orderItem->order->getAmountSellerCommission() and $orderItem->seller_id)
                                        {{number_format($orderItem->order->getAmountSellerCommission()['priceForSite']).' تومان'}}
                                        ({{$orderItem->order->getAmountSellerCommission()['commission']}}%)
                                    @else
                                        -
                                    @endif
                                </div>
                                <div class="info-label">قیمت نهایی با کمیسیون
                                    ({{$orderItem->order->getAmountSellerCommission()['commission']}}%)
                                </div>
                                <div class="info-value">
                                    @if($orderItem->order->getAmountSellerCommission() and $orderItem->seller_id)
                                        {{number_format($orderItem->order->priceSeller()-$orderItem->order->getAmountSellerCommission()['priceForSite'])}}
                                        تومان
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- کارت وضعیت --}}
                    <div class="col-md-4">
                        <div class="card info-card border-left-{{ $orderItem->shipping_status == 'sent' ? 'success' : ($orderItem->shipping_status == 'canceled' ? 'danger' : 'warning') }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 text-warning">⚡ وضعیت مرسوله</h6>
                                    <i class="feather icon-truck text-warning" style="font-size: 24px;"></i>
                                </div>
                                <div class="info-label">وضعیت ارسال</div>
                                <div class="info-value mb-3">
                                <span class="badge badge-custom
                                    @if($orderItem->shipping_status == 'sent') badge-success
                                    @elseif($orderItem->shipping_status == 'delivered') badge-info
                                    @elseif($orderItem->shipping_status == 'canceled') badge-danger
                                    @else badge-warning
                                    @endif">
                                    @switch($orderItem->shipping_status)
                                        @case('w-pending') در انتظار بررسی @break
                                        @case('pending') در حال بررسی @break
                                        @case('processing') در حال پردازش @break
                                        @case('waiting') منتظر ارسال @break
                                        @case('sent') ارسال شد @break
                                        @case('post-sent') تحویل به پست @break
                                        @case('delivered') تحویل داده شد @break
                                        @case('canceled') لغو شد @break
                                        @default {{ $orderItem->shipping_status }}
                                    @endswitch
                                </span>
                                </div>

                                <div class="info-label">کد رهگیری</div>
                                <div class="info-value">
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="tracking-code" {{ $disableStatus ? 'disabled' : '' }}
                                        value="{{ $orderItem->tracking_code }}" placeholder="کد رهگیری...">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary btn-sm" id="update-tracking" {{ $disableStatus ? 'disabled' : '' }} data-action="{{ route('admin.orders.set-tracking-code', ['order' => $order]) }}">
                                                <i class="feather icon-save"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- اطلاعات گیرنده --}}
                <div class="row mt-1">
                    <div class="col-md-12">
                        <div class="card info-card">
                            <div class="card-header d-flex justify-content-between">
                                <h5 class="card-title">
                                    👤 اطلاعات گیرنده
                                    و آدرس ارسال
                                </h5>
                                @if($orderItem->order->location)
                                    <button type="button"
                                            class="btn btn-sm btn-outline-info show-location-btn" data-toggle="modal" data-target="#locationMapModal" >
                                        <i class="fa fa-map-marker-alt"></i> نمایش روی نقشه
                                    </button>
                                @endif

                            </div>
                            <div class="card-body">
                                <div class="row">


                                <div class="col-md-4 mb-1">
                                    <div class="info-label">نام و نام خانوادگی</div>
                                    <div class="info-value">{{ $orderItem->order->name ?? '-' }}</div>
                                </div>

                                <div class="col-md-4 mb-1">
                                    <div class="info-label">شماره موبایل</div>
                                    <div class="info-value">{{ $orderItem->order->mobile ?? '-' }}</div>
                                </div>


                            @if($orderItem->order && $orderItem->order->hasPhysicalProduct())
                                        <div class="col-md-4 mb-1">
                                            <div class="info-label">استان و شهر</div>
                                            <div class="info-value">{{ $orderItem->order->province->name ?? '' }}
                                                - {{ $orderItem->order->city->name ?? '' }}</div>
                                        </div>


                                        <div class="col-md-4">
                                            <div class="info-label">کد پستی</div>
                                            <div class="info-value">{{ $orderItem->order->postal_code ?? '-' }}</div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-label">شیوه تحویل</div>
                                            <div class="info-value">{{ $orderItem->carrier ? $orderItem->carrier->title : 'پیک' }}</div>
                                        </div>
                                        <div class="col-md-4">
                                            @php
                                                $deliveryDate = null;
                                                if($orderItem->delivery_date) {
                                                    try {
                                                        // اگر تاریخ میلادی است
                                                        $deliveryDate = jdate($orderItem->delivery_date)->format('Y/m/d');
                                                    } catch(Exception $e) {
                                                        // اگر تاریخ شمسی است یا فرمت نامعتبر
                                                        $deliveryDate = $orderItem->delivery_date;
                                                    }
                                                }
                                            @endphp
                                            <div class="info-label">تاریخ تحویل</div>
                                            <div class="info-value">{{ $deliveryDate }}</div>
                                        </div>
                                    <div class="col-md-12 mt-1">
                                        <div class="info-label">آدرس کامل</div>
                                        <div class="info-value">{{ $orderItem->order->address ?? '-' }}</div>
                                    </div>


                                @else
                                    <div class="text-muted">این سفارش محصول فیزیکی ندارد</div>
                                @endif

                                </div>
                            </div>


                        </div>
                    </div>

                </div>


                {{-- جدول محصولات --}}
                <div class="row mt-1">
                    <div class="col-12">
                        <div class="card info-card">
                            <div class="card-header">
                                <h5 class="card-title">📦 جزئیات محصولات</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-product">
                                        <thead>
                                        <tr>
                                            <th class="text-center" width="50">#</th>
                                            <th width="80">تصویر</th>
                                            <th>عنوان محصول</th>
                                            <th width="100">کد محصول</th>
                                            <th width="80" class="text-center">تعداد</th>
                                            <th width="120" class="text-center">قیمت واحد</th>
                                            <th width="120" class="text-center">تخفیف (تومان)</th>
                                            <th width="150" class="text-center">قیمت نهایی</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @php
                                            $totalAmount = 0;
                                            $totalDiscount = 0;
                                            $totalDiscountAmount = 0;
                                            $products = $orderItem->products();
                                        @endphp

                                        @foreach($products as $index => $item)

                                            @php

                                                $itemPrice = $item->real_price * $item->quantity;
                                                $itemDiscountPercent = ($item->discount ?? 0);
                                                $itemDiscountAmount = ($itemPrice * $itemDiscountPercent / 100);
                                                $itemFinal = $itemPrice - $itemDiscountAmount;

                                                $totalDiscount += $itemDiscountAmount;
                                                $totalAmount += $itemFinal;

                                            @endphp
                                            <tr>
                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                <td class="text-center">
                                                    @if($item->product && $item->product->image)
                                                        <img src="{{ asset($item->product->image) }}"
                                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                                    @else
                                                        <img src="{{ asset('empty.svg') }}" style="width: 50px;">
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong>
                                                        <a href="{{route('front.products.show',$item->product)}}">{{ $item->title }}</a>
                                                        @if ($item->get_price)
                                                            <br>
                                                            @foreach ($item->get_price->get_attributes as $attribute)

                                                                @if ($attribute->group->type == 'color')
                                                                    <span class="d-flex">
                                                                        <span class="order-product-color d-print-none" style="background-color: {{ $attribute->value }};"></span>
                                                                        <span>{{ $attribute->group->name }}: {{ $attribute->name }}</span>
                                                                    </span>

                                                                @else
                                                                    <span>{{ $attribute->group->name }}: {{ $attribute->name }}</span>
                                                                @endif

                                                            @endforeach
                                                        @endif
                                                    </strong>
                                                    @if($item->product && $item->product->title_fa)
                                                        <br>
                                                        <small class="text-muted">{{ $item->product->title_fa }}</small>
                                                    @endif
                                                    {{-- @if($item->seller_id)
                                                         <br>
                                                         <small class="text-info">فروشنده: {{ get_seller_info($item->seller_id)->business_name ?? '-' }}</small>
                                                     @endif--}}
                                                </td>
                                                <td class="text-center">{{ $item->product_id ?? '-' }}</td>
                                                <td class="text-center">{{ number_format($item->quantity) }}</td>
                                                <td class="text-center">{{ number_format($item->real_price) }}تومان
                                                </td>
                                                <td class="text-center text-danger">
                                                    {{ $item->discount.'%' ?? '-' }}
                                                </td>
                                                <td class="text-center text-success font-weight-bold">
                                                    {{ number_format($itemFinal) }} تومان
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot class="bg-light">
                                        <tr>
                                            <td colspan="6" class="text-end font-weight-bold">جمع تخفیف محصولات:</td>
                                            <td class="text-center text-danger font-weight-bold">{{ number_format($totalDiscount) }}
                                                تومان
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end font-weight-bold">جمع کل محصولات:</td>
                                            <td colspan="2" class="text-center price-value">{{ number_format($totalAmount) }}
                                                تومان
                                            </td>
                                        </tr>
                                        @if(($orderItem->shipping_cost ?? 0) > 0)
                                            <tr>
                                                <td colspan="6" class="text-end">هزینه ارسال:</td>
                                                <td colspan="2" class="text-center">{{ number_format($orderItem->shipping_cost ?? 0) }}
                                                    تومان
                                                </td>
                                            </tr>
                                        @endif

                                        {{-- مبلغ قابل پرداخت نهایی --}}
                                        @php
                                            $finalPayable = $totalAmount  + ($orderItem->shipping_cost ?? 0);
                                        @endphp

                                        <tr class="table-success">
                                            <td colspan="6" class="text-end font-weight-bold fs-5">مبلغ قابل پرداخت
                                                نهایی:
                                            </td>
                                            <td colspan="2" class="text-center price-value fs-4">
                                                {{ number_format($finalPayable) }} تومان
                                            </td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- توضیحات --}}
                @if($orderItem->order->description)
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card info-card">
                                <div class="card-header">
                                    <h5 class="card-title">📝 توضیحات سفارش</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-secondary mb-0">
                                        {{ $orderItem->order->description }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>



    {{-- مودال تایید لغو سفارش --}}
    <div class="modal fade" id="cancelOrderModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأیید لغو سفارش</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        {{--  <fieldset class="checkbox">
                              <div class="vs-checkbox-con vs-checkbox-primary ">
                                  <input type="checkbox" name="published" id="canceled_refund_amount" checked>
                                  <span class="vs-checkbox">
                                                          <span class="vs-checkbox--check">
                                                              <i class="vs-icon feather icon-check"></i>
                                                          </span>
                                                      </span>
                                  <span> بازگشت مبلغ به کیف پول کاربر</span>
                              </div>
                          </fieldset>--}}

                        <div class="alert alert-info mt-2 mb-0" id="refund-info">
                            <i class="fa fa-info-circle"></i>
                            در صورت تایید بازگشت وجه، مجموعا به میزان <strong id="order-amount">{{ number_format($finalPayable) }}</strong> تومان
                            از سفارش لغو شده است.
                            وجه مورد نظر بلافاصله بعد از لغو سفارش به کیف پول کاربر برگشت داده میشود.
                            کاربر میتواند از این مبلغ برای خرید مجدد استفاده کند و یا از طریق پنل کاربری خود درخواست
                            برداشت به حساب بانکی را ثبت نماید.

                            {{-- شرط برای فروشنده --}}
                            @if(isset($orderItem) && $orderItem->seller_id)
                                <hr class="my-2">
                                <div class="alert alert-warning mt-2 mb-0" style="background-color: #fff3cd;">
                                    <i class="fa fa-exchange-alt"></i>
                                    <strong>گردش مالی:</strong>
                                    <ul class="mt-2 mb-0 pr-3">
                                        <li>➖ کسر از کیف پول فروشنده: <strong>{{number_format($orderItem->order->priceSeller()-$orderItem->order->getAmountSellerCommission()['priceForSite'])}} تومان</strong></li>
                                        <li>➕ افزودن به کیف پول کاربر: <strong>{{ number_format($finalPayable) }} تومان</strong></li>
                                    </ul>
                                </div>
                                <div class="alert alert-danger mt-2 mb-0" style="background-color: #f8d7da;">
                                    <i class="fa fa-exclamation-triangle"></i>
                                    <strong>شرط لغو سفارش:</strong>
                                    لغو سفارش فقط در صورتی انجام می‌شود که موجودی کیف پول فروشنده
                                    ({{ $orderItem->seller->business_name ?? $orderItem->seller->name }})
                                    حداقل به میزان <strong>{{number_format($orderItem->order->priceSeller()-$orderItem->order->getAmountSellerCommission()['priceForSite'])}} تومان</strong> باشد.
                                    <br>
                                    <small class="d-block mt-1">
                                        <i class="fa fa-info-circle"></i>
                                        در صورت عدم موجودی کافی، عملیات لغو انجام نخواهد شد و پیغام خطا نمایش داده می‌شود.
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <label for="cancel_reason">دلیل لغو سفارش</label>
                        <textarea class="form-control" id="cancel_reason" rows="3" placeholder="دلیل لغو سفارش را وارد کنید..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-danger" id="confirm-cancel">تأیید و لغو سفارش</button>
                </div>
            </div>
        </div>
    </div>

    {{-- مودال نمایش موقعیت روی نقشه --}}
    <div class="modal fade" id="locationMapModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-map-marker-alt text-danger"></i> موقعیت مکانی
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div id="map" style="height: 400px; width: 100%;"></div>
                </div>

            </div>
        </div>
    </div>

@endsection

@include('back.partials.plugins', ['plugins' => [ 'map']])

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/orders/show.js') }}"></script>

    <script>

        @php
            $lat=null;
            $lng=null;
            if ($orderItem->order->location){
                  $location=explode(',',$orderItem->order->location);
                   $lat=$location[0];
                $lng=$location[1];
            }


        @endphp
        var info_map_type = "{{ option('info_map_type', 'google') }}"
        var info_latitude = "{{ $lat }}";
        var info_Longitude = "{{ $lng }}";
        var info_site_title = "{{ option('info_site_title', 'او پی شاپ') }}";

        var mapIrApiKey = '{{ option('map_api') }}';


    </script>
@endpush
