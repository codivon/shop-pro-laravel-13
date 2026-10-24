{{-- پارشال فاکتور فروش سانیار (فاز I) — مشترک بین print و print-all
    قرارداد: $order + اقلام فیلترشده به sellerID() + جمع‌های مخصوص فروشنده --}}
<div class="container mt-1 p-0 factor-wrap single-order-form" dir="rtl">
    <div class="row">
        <div class="col-md-9">
            <div class="row">
                <div class="col-12 text-center pt-1">
                    <h4 class="mb-0 font-14">صورتحساب فروش کالا و خدمات</h4>
                </div>
                <div class="col-12 text-center pt-2">
                    <h2 class="font-20 mb-0">{{ option('factor_title', option('info_site_title', config('front.demo.name'))) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 print-border text-start p-2">
            <div class="font-13"><b>شماره فاکتور:</b> <span>{{ $order->id }}</span></div>
            <div class="font-13"><b>تاریخ فاکتور:</b> <span>{{ jdate($order->created_at)->format('Y/m/d') }}</span></div>
            <div class="factor-barcode mt-1">
                <img src="{{ barcode($order->id) }}" alt="بارکد فاکتور {{ $order->id }}">
            </div>
        </div>

        {{-- مشخصات فروشنده --}}
        <div class="col-12">
            <div class="row mx-0 mt-2">
                <div class="col-12 mb-1 print-border-top factor-table-color px-2 py-1">
                    <h6 class="font-13 fw-bold mb-0">مشخصات فروشنده</h6>
                </div>
                <div class="col-4 font-13"><b class="title-col">فروشنده:</b> <span>{{ seller_info()->business_name ?? seller_info()->full_name ?? '—' }}</span></div>
                @if(option('factor_national_code'))
                    <div class="col-4 font-13"><b class="title-col">شناسه ملی:</b> <span>{{ option('factor_national_code') }}</span></div>
                @endif
                @if(option('factor_registeration_id'))
                    <div class="col-4 font-13"><b class="title-col">شناسه ثبت:</b> <span>{{ option('factor_registeration_id') }}</span></div>
                @endif
                @if(option('factor_economical_number'))
                    <div class="col-4 font-13"><b class="title-col">شماره اقتصادی:</b> <span>{{ option('factor_economical_number') }}</span></div>
                @endif
                <div class="col-4 font-13"><b class="title-col">کدپستی:</b> <span>{{ option('info_postal_code') }}</span></div>
                <div class="col-4 font-13"><b class="title-col">شماره تماس:</b> <span>{{ option('info_tel') }}</span></div>
                <div class="col-12 font-13"><b class="title-col">نشانی:</b> <span>{{ option('info_address') }}</span></div>
            </div>
        </div>

        {{-- مشخصات خریدار --}}
        <div class="col-12">
            <div class="row mx-0 mt-1">
                <div class="col-12 mb-1 factor-table-color px-2 py-1">
                    <h6 class="font-13 fw-bold mb-0">مشخصات خریدار</h6>
                </div>
                <div class="col-4 font-13"><b class="title-col">نام:</b> <span>{{ $order->name }}</span></div>
                <div class="col-4 font-13"><b class="title-col">شماره ملی:</b> <span>{{ $order->user->national_code ?? '—' }}</span></div>
                <div class="col-4 font-13"><b class="title-col">شماره تماس:</b> <span>{{ $order->mobile }}</span></div>
                <div class="col-4 font-13"><b class="title-col">کدپستی:</b> <span>{{ $order->postal_code ?? '—' }}</span></div>
                <div class="col-4 font-13"><b class="title-col">شیوه حمل‌ونقل:</b> <span>{{ $order->carrier->title ?? 'پیک' }}</span></div>
                <div class="col-4 font-13"><b class="title-col">وضعیت پرداخت:</b> <span>{{ $order->status == 'paid' ? 'پرداخت شده' : 'پرداخت نشده' }}</span></div>
                <div class="col-12 font-13"><b class="title-col">نشانی:</b> <span>{{ $order->province->name ?? '' }} {{ $order->city ? ' - ' . $order->city->name : '' }} {{ $order->address ? ' - ' . $order->address : '' }}</span></div>
            </div>
        </div>

        {{-- اقلام این فروشنده --}}
        <div class="col-12 mt-2">
            <table class="table table-bordered mb-0">
                <thead>
                <tr class="factor-table-color">
                    <th class="border-0" scope="col">ردیف</th>
                    <th class="border-0" scope="col">کد کالا</th>
                    <th class="border-0 text-center" scope="col">شرح کالا</th>
                    <th class="border-0" scope="col">تعداد</th>
                    <th class="border-0" scope="col">مبلغ واحد</th>
                    <th class="border-0" scope="col">مبلغ کل</th>
                    <th class="border-0" scope="col">تخفیف</th>
                    <th class="border-0" scope="col">مبلغ نهایی</th>
                </tr>
                </thead>
                <tbody>
                @foreach($order->items()->where('seller_id', sellerID())->get() as $item)
                    <tr>
                        <th scope="row">{{ $loop->iteration }}</th>
                        <td>{{ $item->product_id }}</td>
                        <td class="text-start">
                            {{ $item->title }}
                            @if($item->get_price)
                                <p class="font-12 mb-0">{{ $item->get_price->getAttributesName() }}</p>
                            @endif
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->realPrice()) }}</td>
                        <td>{{ number_format($item->quantity * $item->realPrice()) }}</td>
                        <td>{{ ($item->discount ?? 0) ? $item->discount . '%' : 0 }}</td>
                        <td>{{ number_format($item->price * $item->quantity) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- جمع‌ها --}}
        <div class="col-12">
            <div class="row justify-content-end m-0">
                <div class="col-8 text-muted pt-1">
                    <b class="font-13">توضیحات:</b>
                </div>
                <div class="col-4 p-0">
                    <table class="table factor-totals mb-0">
                        <tbody>
                        <tr>
                            <th class="p-0 font-13">مجموع:</th>
                            <td class="p-0 font-13">{{ number_format($order->priceSellerWithoutDiscount(sellerID())) }}</td>
                        </tr>
                        <tr>
                            <th class="p-0 font-13">هزینه حمل‌ونقل:</th>
                            <td class="p-0 font-13">{{ number_format($order->shippingCostSeller()) }}</td>
                        </tr>
                        <tr>
                            <th class="p-0 font-13">مجموع تخفیف:</th>
                            <td class="p-0 font-13">{{ number_format($order->totalDiscountSeller(sellerID())) }}</td>
                        </tr>
                        <tr class="factor-table-color">
                            <th class="p-0 font-13">مجموع فاکتور:</th>
                            <td class="p-0 font-13 fw-bold">{{ number_format($order->priceSeller(sellerID())) }} تومان</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
