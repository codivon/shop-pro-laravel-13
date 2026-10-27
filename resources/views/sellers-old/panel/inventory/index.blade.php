@extends('sellers-old.panel.layouts.master')

@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">مرکز موجودی</span>
                <span class="c-content-page__header-desc">وضعیت موجودی تنوع‌های شما — بحرانی‌ترها اول — به‌همراه تاریخچهٔ حرکت انبار. تغییر دستی موجودی بر عهدهٔ مدیر فروشگاه است.</span>
            </div>
        </div>
    </div>

    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">

        <style>
            /* اسکوپ‌شده — هم‌الگوی نظرات محصولات (فاز ۱۰۶-۴) */
            .siv-card { background: #fff; border: 1px solid #eef0f4; border-radius: 12px; padding: 14px 16px; height: 100%; }
            .siv-card__label { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
            .siv-card__value { font-size: 20px; font-weight: 700; color: #1f2937; }
            .siv-card__value--danger { color: #dc2626; }
            .siv-card__value--warn { color: #d97706; }
            .siv-table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .siv-table th { background: #f8fafc; color: #374151; font-weight: 700; padding: 10px 8px; text-align: center; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
            .siv-table td { padding: 10px 8px; text-align: center; border-bottom: 1px solid #f1f5f9; color: #1f2937; vertical-align: top; }
            .siv-table tr:hover td { background: #fafbfc; }
            .siv-badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
            .siv-badge--in { background: #dcfce7; color: #15803d; }
            .siv-badge--low { background: #fef3c7; color: #b45309; }
            .siv-badge--out { background: #fee2e2; color: #b91c1c; }
            .siv-badge--move { background: #f1f5f9; color: #334155; }
        </style>

        {{-- جمع‌های موجودی --}}
        <div class="row mb-2">
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="siv-card">
                    <div class="siv-card__label">کل تنوع‌های قیمت</div>
                    <div class="siv-card__value">{{ number_format($stats['total_variants']) }}</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="siv-card">
                    <div class="siv-card__label">ناموجود</div>
                    <div class="siv-card__value siv-card__value--danger">{{ number_format($stats['out_of_stock']) }}</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="siv-card">
                    <div class="siv-card__label">کم‌موجود (۱ تا ۲ عدد)</div>
                    <div class="siv-card__value siv-card__value--warn">{{ number_format($stats['low_stock']) }}</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="siv-card">
                    <div class="siv-card__label">حرکت انبار ۳۰ روز اخیر</div>
                    <div class="siv-card__value">{{ number_format($stats['recent_movements']) }}</div>
                </div>
            </div>
        </div>

        {{-- جدول وضعیت موجودی --}}
        <div class="row mb-2">
            <div class="col-12">
                <div class="c-card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">وضعیت موجودی تنوع‌ها</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="siv-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>محصول</th>
                                        <th>تنوع</th>
                                        <th>موجودی</th>
                                        <th>وضعیت</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($prices as $price)
                                        <tr>
                                            <td>{{ ($prices->currentPage() - 1) * $prices->perPage() + $loop->iteration }}</td>
                                            <td class="uk-text-bold">
                                                @if ($price->product)
                                                    <a href="{{ route('front.products.show', ['product' => $price->product->slug]) }}" target="_blank" title="{{ $price->product->title }}">{{ \Illuminate\Support\Str::limit($price->product->title, 30) }}</a>
                                                @else
                                                    <span class="text-muted">محصول حذف‌شده</span>
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ \Illuminate\Support\Str::limit(trim((string) $price->getAttributesName()) ?: 'تنوع اصلی', 40) }}</td>
                                            <td class="fa-digits uk-text-bold">{{ number_format((int) $price->stock) }}</td>
                                            <td>
                                                @if ($price->stock < 1)
                                                    <span class="siv-badge siv-badge--out">ناموجود</span>
                                                @elseif ($price->stock < 3)
                                                    <span class="siv-badge siv-badge--low">کم‌موجود</span>
                                                @else
                                                    <span class="siv-badge siv-badge--in">موجود</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-muted py-4">هنوز تنوعی برای محصولات شما ثبت نشده است.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-2">
                            {{ $prices->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- تاریخچهٔ حرکت انبار --}}
        <div class="row mb-2">
            <div class="col-12">
                <div class="c-card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">آخرین حرکت‌های انبار</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="siv-table">
                                <thead>
                                    <tr>
                                        <th>نوع</th>
                                        <th>محصول</th>
                                        <th>تعداد</th>
                                        <th>موجودی پس از حرکت</th>
                                        <th>توضیح</th>
                                        <th>تاریخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($movements as $movement)
                                        <tr>
                                            <td><span class="siv-badge siv-badge--move">{{ $movement->type_label }}</span></td>
                                            <td class="uk-text-bold">
                                                @if ($movement->product)
                                                    <a href="{{ route('front.products.show', ['product' => $movement->product->slug]) }}" target="_blank" title="{{ $movement->product->title }}">{{ \Illuminate\Support\Str::limit($movement->product->title, 28) }}</a>
                                                @else
                                                    <span class="text-muted">محصول حذف‌شده</span>
                                                @endif
                                            </td>
                                            <td class="fa-digits">{{ number_format($movement->quantity) }}</td>
                                            <td class="fa-digits">{{ number_format($movement->after_stock) }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($movement->description ?: '-', 46) }}</td>
                                            <td>{{ $movement->created_at ? jdate($movement->created_at)->format('Y/m/d') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-muted py-4">هنوز حرکتی در انبار ثبت نشده است.</td>
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
