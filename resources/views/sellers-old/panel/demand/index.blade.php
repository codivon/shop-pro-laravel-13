@extends('sellers-old.panel.layouts.master')

@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">سیگنال‌های تقاضا</span>
                <span class="c-content-page__header-desc">مشتریانی که منتظر موجودشدن یا ارزان‌شدن محصولات شما هستند — تقاضای واقعی که نباید از چشمتان دور بماند.</span>
            </div>
        </div>
    </div>

    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">

        <style>
            /* اسکوپ‌شده — هم‌الگوی صورت‌حساب تسویه (فاز ۱۰۶-۱) */
            .sdm-card { background: #fff; border: 1px solid #eef0f4; border-radius: 12px; padding: 14px 16px; height: 100%; }
            .sdm-card__label { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
            .sdm-card__value { font-size: 20px; font-weight: 700; color: #1f2937; }
            .sdm-card__value--stock { color: #d97706; }
            .sdm-card__value--price { color: #7c3aed; }
            .sdm-table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .sdm-table th { background: #f8fafc; color: #374151; font-weight: 700; padding: 10px 8px; text-align: center; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
            .sdm-table td { padding: 10px 8px; text-align: center; border-bottom: 1px solid #f1f5f9; color: #1f2937; }
            .sdm-table tr:hover td { background: #fafbfc; }
            .sdm-badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
            .sdm-badge--stock { background: #fef3c7; color: #b45309; }
            .sdm-badge--price { background: #ede9fe; color: #6d28d9; }
            .sdm-badge--in { background: #dcfce7; color: #15803d; }
            .sdm-badge--out { background: #fee2e2; color: #b91c1c; }
            .sdm-badge--new { background: #cffafe; color: #0e7490; }
        </style>

        {{-- جمع‌های تقاضا --}}
        <div class="row mb-2">
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="sdm-card">
                    <div class="sdm-card__label">مشتریان منتظر موجودی</div>
                    <div class="sdm-card__value sdm-card__value--stock">{{ number_format($stats['waiting_stock']) }}</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="sdm-card">
                    <div class="sdm-card__label">منتظر کاهش قیمت</div>
                    <div class="sdm-card__value sdm-card__value--price">{{ number_format($stats['waiting_price']) }}</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="sdm-card">
                    <div class="sdm-card__label">محصولات دارای تقاضا</div>
                    <div class="sdm-card__value">{{ number_format($stats['products_with_demand']) }}</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="sdm-card">
                    <div class="sdm-card__label">سیگنال ۷ روز اخیر</div>
                    <div class="sdm-card__value">{{ number_format($stats['recent_7days']) }}</div>
                </div>
            </div>
        </div>

        {{-- جدول محصولات دارای تقاضا --}}
        <div class="row mb-2">
            <div class="col-12">
                <div class="c-card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">تقاضا به‌ازای هر محصول</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="sdm-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>محصول</th>
                                        <th>وضعیت موجودی</th>
                                        <th>منتظر موجودی</th>
                                        <th>منتظر کاهش قیمت</th>
                                        <th>آخرین سیگنال</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($products as $product)
                                        <tr>
                                            <td>{{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}</td>
                                            <td class="uk-text-bold">
                                                <a href="{{ route('front.products.show', ['product' => $product->slug]) }}" target="_blank" title="{{ $product->title }}">{{ \Illuminate\Support\Str::limit($product->title, 34) }}</a>
                                            </td>
                                            <td>
                                                <span class="sdm-badge {{ $product->in_stock ? 'sdm-badge--in' : 'sdm-badge--out' }}">
                                                    {{ $product->in_stock ? 'موجود' : 'ناموجود' }}
                                                </span>
                                            </td>
                                            <td>{{ number_format($product->demand_stock) }}</td>
                                            <td>{{ number_format($product->demand_alerts) }}</td>
                                            <td>{{ $product->demand_last_at ? jdate($product->demand_last_at)->format('Y/m/d') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-muted py-4">هنوز سیگنالی ثبت نشده است — وقتی مشتری‌ها منتظر موجودی یا کاهش قیمت محصولات شما باشند، همین‌جا می‌بینید.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($products->hasPages())
                            <div class="mt-2">
                                {{ $products->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- خوراک آخرین سیگنال‌ها --}}
        <div class="row mb-2">
            <div class="col-12">
                <div class="c-card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">آخرین سیگنال‌ها</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="sdm-table">
                                <thead>
                                    <tr>
                                        <th>نوع</th>
                                        <th>مشتری</th>
                                        <th>موبایل</th>
                                        <th>محصول</th>
                                        <th>تاریخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recent as $signal)
                                        <tr>
                                            <td>
                                                <span class="sdm-badge {{ $signal->type === 'stock' ? 'sdm-badge--stock' : 'sdm-badge--price' }}">
                                                    {{ $signal->type === 'stock' ? 'موجودی' : 'کاهش قیمت' }}
                                                </span>
                                                @if ($signal->type === 'stock' && ! $signal->seen)
                                                    <span class="sdm-badge sdm-badge--new">جدید</span>
                                                @endif
                                            </td>
                                            <td>{{ $signal->name ?: 'مشتری' }}</td>
                                            <td class="fa-digits">{{ $signal->mobile }}</td>
                                            <td>
                                                @if ($signal->product)
                                                    <a href="{{ route('front.products.show', ['product' => $signal->product->slug]) }}" target="_blank" title="{{ $signal->product->title }}">{{ \Illuminate\Support\Str::limit($signal->product->title, 30) }}</a>
                                                    @if ($signal->type === 'price' && ! is_null($signal->target_price ?? null))
                                                        <small class="text-muted">— هدف: {{ number_format($signal->target_price) }} تومان</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">محصول حذف‌شده</span>
                                                @endif
                                            </td>
                                            <td>{{ $signal->created_at ? jdate($signal->created_at)->format('Y/m/d') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-muted py-4">هیچ سیگنالی ثبت نشده است.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
