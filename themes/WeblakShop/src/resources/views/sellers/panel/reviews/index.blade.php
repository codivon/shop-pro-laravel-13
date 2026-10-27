@extends('front::sellers.panel.layouts.master')

@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">نظرات محصولات</span>
                <span class="c-content-page__header-desc">امتیاز و توصیهٔ مشتری‌ها دربارهٔ محصولات شما — همان نظری که در صفحهٔ محصول می‌خوانند. تأیید و رد نظرات بر عهدهٔ مدیر فروشگاه است.</span>
            </div>
        </div>
    </div>

    @include('front::sellers.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">

        <style>
            /* اسکوپ‌شده — هم‌الگوی سیگنال‌های تقاضا (فاز ۱۰۶-۳) */
            .srv-card { background: #fff; border: 1px solid #eef0f4; border-radius: 12px; padding: 14px 16px; height: 100%; }
            .srv-card__label { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
            .srv-card__value { font-size: 20px; font-weight: 700; color: #1f2937; }
            .srv-card__value--good { color: #16a34a; }
            .srv-card__value--warn { color: #d97706; }
            .srv-table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .srv-table th { background: #f8fafc; color: #374151; font-weight: 700; padding: 10px 8px; text-align: center; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
            .srv-table td { padding: 10px 8px; text-align: center; border-bottom: 1px solid #f1f5f9; color: #1f2937; vertical-align: top; }
            .srv-table tr:hover td { background: #fafbfc; }
            .srv-badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
            .srv-badge--accepted { background: #dcfce7; color: #15803d; }
            .srv-badge--pending { background: #fef3c7; color: #b45309; }
            .srv-badge--rejected { background: #fee2e2; color: #b91c1c; }
            .srv-badge--yes { background: #dcfce7; color: #15803d; }
            .srv-badge--no { background: #fee2e2; color: #b91c1c; }
            .srv-badge--notsure { background: #f1f5f9; color: #475569; }
            .srv-stars { color: #f59e0b; letter-spacing: 1px; white-space: nowrap; }
            .srv-stars .off { color: #e5e7eb; }
        </style>

        {{-- جمع‌های رضایت --}}
        <div class="row mb-2">
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="srv-card">
                    <div class="srv-card__label">کل نظرات ثبت‌شده</div>
                    <div class="srv-card__value">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="srv-card">
                    <div class="srv-card__label">میانگین امتیاز (نظرات تأییدشده)</div>
                    <div class="srv-card__value srv-card__value--good">
                        {{ is_null($stats['avg_rating']) ? '-' : number_format($stats['avg_rating'], 1) }}
                        <small class="text-muted">از ۵</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="srv-card">
                    <div class="srv-card__label">توصیه به دیگران</div>
                    <div class="srv-card__value">
                        {{ is_null($stats['recommend_percent']) ? '-' : number_format($stats['recommend_percent'], 1).'٪' }}
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="srv-card">
                    <div class="srv-card__label">در انتظار تأیید مدیر</div>
                    <div class="srv-card__value srv-card__value--warn">{{ number_format($stats['pending']) }}</div>
                </div>
            </div>
        </div>

        {{-- ردیف‌به‌ردیف نظرات --}}
        <div class="row mb-2">
            <div class="col-12">
                <div class="c-card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">ریز نظرات</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="srv-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>محصول</th>
                                        <th>امتیاز</th>
                                        <th>توصیه</th>
                                        <th>نظر</th>
                                        <th>وضعیت</th>
                                        <th>مفید بودن</th>
                                        <th>تاریخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($reviews as $review)
                                        <tr>
                                            <td>{{ ($reviews->currentPage() - 1) * $reviews->perPage() + $loop->iteration }}</td>
                                            <td class="uk-text-bold">
                                                @if ($review->product)
                                                    <a href="{{ route('front.products.show', ['product' => $review->product->slug]) }}" target="_blank" title="{{ $review->product->title }}">{{ \Illuminate\Support\Str::limit($review->product->title, 28) }}</a>
                                                @else
                                                    <span class="text-muted">محصول حذف‌شده</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="srv-stars" aria-label="امتیاز {{ $review->rating }} از ۵">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <span class="{{ $i <= (int) $review->rating ? '' : 'off' }}">★</span>
                                                    @endfor
                                                </span>
                                            </td>
                                            <td>
                                                @switch($review->suggest)
                                                    @case('yes')
                                                        <span class="srv-badge srv-badge--yes">پیشنهاد می‌کنم</span>
                                                        @break
                                                    @case('no')
                                                        <span class="srv-badge srv-badge--no">پیشنهاد نمی‌کنم</span>
                                                        @break
                                                    @case('not_sure')
                                                        <span class="srv-badge srv-badge--notsure">مطمئن نیستم</span>
                                                        @break
                                                    @default
                                                        <span class="text-muted">-</span>
                                                @endswitch
                                            </td>
                                            <td style="max-width: 260px;">
                                                @if ($review->title) <div class="uk-text-bold">{{ \Illuminate\Support\Str::limit($review->title, 40) }}</div> @endif
                                                <div>{{ \Illuminate\Support\Str::limit($review->body, 90) }}</div>
                                            </td>
                                            <td>
                                                <span class="srv-badge srv-badge--{{ $review->status }}">
                                                    @switch($review->status)
                                                        @case('accepted') تأییدشده
                                                            @break
                                                        @case('pending') در انتظار تأیید
                                                            @break
                                                        @case('rejected') ردشده
                                                            @break
                                                        @default {{ $review->status }}
                                                    @endswitch
                                                </span>
                                            </td>
                                            <td class="fa-digits">👍 {{ number_format((int) $review->likes_count) }} / 👎 {{ number_format((int) $review->dislikes_count) }}</td>
                                            <td>{{ $review->created_at ? jdate($review->created_at)->format('Y/m/d') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-muted py-4">هنوز نظری برای محصولات شما ثبت نشده است — نظرات پس از ثبت و تأیید مدیر اینجا دیده می‌شوند.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-2">
                            {{ $reviews->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
