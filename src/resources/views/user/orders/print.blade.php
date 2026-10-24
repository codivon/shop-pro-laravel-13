{{-- فاکتور چاپی سفارش — قرارداد OrderController@print : $order — صفحه مستقل (بدون لایه‌وت سایت) --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>فاکتور سفارش {{ $order->id }}</title>
    <link rel="stylesheet" href="{{ theme_asset('css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/factor.css') }}">
</head>
<body class="factor-page">

<div class="container-xl factor-wrapper">
    {{-- سرصفحه --}}
    <div class="row align-items-center factor-header">
        <div class="col-3 text-center">
            <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" class="img-fluid factor-logo" alt="{{ option('info_site_title', 'فروشگاه') }}">
        </div>
        <div class="col-6 text-center">
            <h1 class="factor-title">صورتحساب فروش کالا و خدمات</h1>
        </div>
        <div class="col-3 text-start font-12">
            @php
                $paidTransaction = $order->transactions()->where('status', 1)->latest()->first();
            @endphp
            <p class="mb-1">شماره سفارش: <strong>{{ $order->id }}</strong></p>
            <p class="mb-1">تاریخ سفارش: <strong>{{ \Morilog\Jalali\Jalalian::fromDateTime($order->created_at)->format('Y/m/d') }}</strong></p>
            @if($order->status == 'paid' && $paidTransaction)
                <p class="mb-1">تاریخ پرداخت: <strong>{{ \Morilog\Jalali\Jalalian::fromDateTime($paidTransaction->created_at)->format('Y/m/d') }}</strong></p>
                @if($paidTransaction->transId)
                    <p class="mb-1">شماره تراکنش: <strong dir="ltr">{{ $paidTransaction->transId }}</strong></p>
                @endif
            @endif
        </div>
    </div>

    <table class="table table-factor">
        {{-- مشخصات فروشنده --}}
        <thead>
        <tr>
            <th colspan="3" class="factor-section-title">مشخصات فروشنده</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="col-4">
                <span class="factor-label">نام فروشگاه:</span>
                <strong>{{ option('info_site_title', 'فروشگاه اینترنتی') }}</strong>
            </td>
            <td class="col-4">
                <span class="factor-label">شماره تماس:</span>
                <strong dir="ltr">{{ option('info_tel', '—') }}</strong>
                <span class="factor-label d-block">ایمیل:</span>
                <strong dir="ltr">{{ option('info_email', '—') }}</strong>
            </td>
            <td class="col-4">
                <span class="factor-label">نشانی:</span>
                <strong>{{ option('info_address', '—') }}</strong>
            </td>
        </tr>
        </tbody>

        {{-- مشخصات خریدار --}}
        <thead>
        <tr>
            <th colspan="3" class="factor-section-title">مشخصات خریدار</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="col-4">
                <span class="factor-label">نام و نام خانوادگی:</span>
                <strong>{{ $order->name }}</strong>
            </td>
            <td class="col-4">
                <span class="factor-label">شماره همراه:</span>
                <strong dir="ltr">{{ $order->mobile }}</strong>
                <span class="factor-label d-block">کد پستی:</span>
                <strong dir="ltr">{{ $order->postal_code ?? '—' }}</strong>
            </td>
            <td class="col-4">
                <span class="factor-label">نشانی:</span>
                <strong>
                    {{ $order->province ? $order->province->name . '، ' : '' }}{{ $order->city ? $order->city->name . '، ' : '' }}{{ $order->address ?? '—' }}
                </strong>
            </td>
        </tr>
        </tbody>

        {{-- اقلام --}}
        <thead>
        <tr>
            <th colspan="6" class="factor-section-title">مشخصات کالا یا خدمات مورد معامله</th>
        </tr>
        <tr class="factor-cols-head">
            <th class="col-row">ردیف</th>
            <th class="col-code">کد کالا</th>
            <th class="col-desc">شرح کالا</th>
            <th class="col-qty">تعداد</th>
            <th class="col-unit">مبلغ واحد (تومان)</th>
            <th class="col-total">مبلغ کل (تومان)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $item->product_id }}</td>
                <td>
                    {{ $item->title }}
                    @if($item->product_type == 'download' || $item->is_downloadable)
                        <span class="factor-download-tag">(دانلودی)</span>
                    @endif
                </td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-center">{{ number_format($item->price) }}</td>
                <td class="text-center">{{ number_format($item->price * $item->quantity) }}</td>
            </tr>
        @endforeach

        {{-- جمع‌ها --}}
        @php
            $itemsOriginal = $order->items->sum(fn($i) => $i->real_price * $i->quantity);
            $itemsPaid = $order->items->sum(fn($i) => $i->price * $i->quantity);
        @endphp
        <tr class="factor-totals">
            <td colspan="3" class="factor-label">جمع کل اقلام</td>
            <td colspan="3" class="text-center">{{ number_format($itemsOriginal) }} تومان</td>
        </tr>
        @if($order->discount_amount > 0)
            <tr class="factor-totals">
                <td colspan="3" class="factor-label">مبلغ تخفیف</td>
                <td colspan="3" class="text-center">{{ number_format($order->discount_amount) }} تومان</td>
            </tr>
        @endif
        @if($order->shipping_cost > 0)
            <tr class="factor-totals">
                <td colspan="3" class="factor-label">هزینه ارسال</td>
                <td colspan="3" class="text-center">{{ number_format($order->shipping_cost) }} تومان</td>
            </tr>
        @endif
        <tr class="factor-totals factor-grand">
            <td colspan="3" class="factor-label">مبلغ قابل پرداخت</td>
            <td colspan="3" class="text-center">{{ number_format($order->price) }} تومان</td>
        </tr>
        <tr class="factor-totals">
            <td colspan="3" class="factor-label">وضعیت پرداخت</td>
            <td colspan="3" class="text-center {{ $order->status == 'paid' ? 'factor-paid' : 'factor-unpaid' }}">
                {{ $order->status == 'paid' ? 'پرداخت شده' : ($order->status == 'unpaid' ? 'پرداخت نشده' : 'لغو شده') }}
            </td>
        </tr>
        <tr class="factor-totals">
            <td colspan="3" class="factor-label">نحوه فروش</td>
            <td colspan="3" class="text-center">نقدی (پرداخت اینترنتی)</td>
        </tr>

        {{-- امضاها --}}
        <tr class="factor-signature">
            <td colspan="3" class="factor-label">مهر و امضای فروشنده</td>
            <td colspan="3" class="factor-label">مهر و امضای خریدار</td>
        </tr>
        </tbody>
    </table>

    <a href="#" class="btn btn-dark" id="printPageButton">
        <i class="bi bi-printer"></i> پرینت
    </a>
</div>

<script src="{{ theme_asset('js/jquery.min.js') }}"></script>
<script src="{{ theme_asset('js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ theme_asset('js/pages/carts/factor.js') }}"></script>
</body>
</html>
