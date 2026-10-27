@extends('sellers-old.panel.layouts.master')

@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">صورت‌حساب تسویه</span>
                <span class="c-content-page__header-desc">ردیف‌به‌ردیفِ کمیسیون و سهم هر سفارشِ تسویه‌شده — شفافیت کامل مالی فروش شما.</span>
            </div>
        </div>
    </div>

    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">

        <style>
            /* اسکوپ‌شده */
            .stm-card { background: #fff; border: 1px solid #eef0f4; border-radius: 12px; padding: 14px 16px; height: 100%; }
            .stm-card__label { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
            .stm-card__value { font-size: 20px; font-weight: 700; color: #1f2937; }
            .stm-card__value--good { color: #16a34a; }
            .stm-card__value--warn { color: #d97706; }
            .stm-table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .stm-table th { background: #f8fafc; color: #374151; font-weight: 700; padding: 10px 8px; text-align: center; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
            .stm-table td { padding: 10px 8px; text-align: center; border-bottom: 1px solid #f1f5f9; color: #1f2937; }
            .stm-table tr:hover td { background: #fafbfc; }
            .stm-badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
            .stm-badge--success { background: #dcfce7; color: #15803d; }
            .stm-badge--fail { background: #fee2e2; color: #b91c1c; }
        </style>

        {{-- جمع‌های صورت‌حساب --}}
        <div class="row mb-2">
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="stm-card">
                    <div class="stm-card__label">سفارش‌های تسویه‌شده</div>
                    <div class="stm-card__value">{{ number_format($stats['settled_orders']) }}</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="stm-card">
                    <div class="stm-card__label">سهم واریزشده به کیف پول</div>
                    <div class="stm-card__value stm-card__value--good">{{ number_format($stats['total_net']) }} <small class="text-muted">تومان</small></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="stm-card">
                    <div class="stm-card__label">کمیسیون کسرشدهٔ سایت</div>
                    <div class="stm-card__value">{{ number_format($stats['total_commission']) }} <small class="text-muted">تومان</small></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="stm-card">
                    <div class="stm-card__label">برداشت در انتظار پرداخت</div>
                    <div class="stm-card__value stm-card__value--warn">{{ number_format($stats['waiting_withdraw_amount']) }} <small class="text-muted">تومان</small></div>
                </div>
            </div>
        </div>

        {{-- جدول ردیف‌به‌ردیف --}}
        <div class="row mb-2">
            <div class="col-12">
                <div class="c-card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">ریز تسویهٔ سفارش‌ها</h2>
                        <a class="btn btn-sm btn-primary float-left" href="{{ route('seller.wallet.settlements.export') }}">
                            <i class="feather icon-download"></i> خروجی اکسل
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="stm-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>سفارش</th>
                                        <th>تاریخ</th>
                                        <th>مبلغ سفارش</th>
                                        <th>درصد کمیسیون</th>
                                        <th>کمیسیون سایت</th>
                                        <th>سهم شما</th>
                                        <th>وضعیت</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($deposits as $deposit)
                                        <tr>
                                            <td>{{ ($deposits->currentPage() - 1) * $deposits->perPage() + $loop->iteration }}</td>
                                            <td>#{{ $deposit->order_id }}</td>
                                            <td>{{ $deposit->created_at ? jdate($deposit->created_at)->format('Y/m/d') : '-' }}</td>
                                            <td>{{ number_format((int) ($deposit->order->price ?? 0)) }}</td>
                                            <td>{{ is_null($deposit->percent) ? '-' : ((int) $deposit->percent.'٪') }}</td>
                                            <td>{{ number_format((int) $deposit->amount) }}</td>
                                            <td class="uk-text-bold">{{ number_format((int) ($netByOrder[$deposit->order_id] ?? 0)) }}</td>
                                            <td>
                                                <span class="stm-badge {{ $deposit->status === 'success' ? 'stm-badge--success' : 'stm-badge--fail' }}">
                                                    {{ $deposit->status === 'success' ? 'تسویه‌شده' : 'ناموفق' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-muted py-4">هنوز سفارشی تسویه نشده است — پس از اولین فروش، ریز تسویهٔ هر سفارش اینجا ثبت می‌شود.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-2">
                            {{ $deposits->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
