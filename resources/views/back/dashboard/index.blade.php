@extends(\Modules\CustomerClub\Support\ActivePanel::useNewPanel() ? 'admin-panel::layouts.module' : 'back.layouts.master')

@section('title', 'داشبورد باشگاه مشتریان')

@push('styles')
    <link rel="stylesheet" href="{{ module_asset('CustomerClub', 'css/cc-admin.css') }}?v=1.0.0">
@endpush

@section('content')
<div class="app-content content">
    <div class="content-wrapper">
        <div class="content-body cc-scope">

            {{-- ═══════════ سربرگ صفحه ═══════════ --}}
            <div class="cc-page-head">
                <div class="cc-page-head-main">
                    <span class="cc-page-icon"><i class="fas fa-award"></i></span>
                    <div>
                        <h4 class="cc-page-title">داشبورد باشگاه مشتریان</h4>
                        <p class="cc-page-desc">وضعیت کلی امتیاز وفاداری، کارت هدیه و معرفی دوستان</p>
                    </div>
                </div>
                <div class="cc-page-side">
                    <a href="{{ route('admin.customerclub.guide') }}" class="cc-btn cc-btn--ghost"><i class="fas fa-graduation-cap"></i> راهنما و آموزش</a>
                    <a href="{{ route('admin.customerclub.settings.edit') }}" class="cc-btn cc-btn--ghost"><i class="fas fa-gear"></i> تنظیمات</a>
                </div>
            </div>

            @if (!$clubOn)
                <div class="cc-alert cc-alert--warn"><i class="fas fa-triangle-exclamation"></i>
                    باشگاه مشتریان از تنظیمات <b>غیرفعال</b> است — مشتریان هیچ‌کدام از این امکانات را نمی‌بینند.
                    <a href="{{ route('admin.customerclub.settings.edit') }}" class="cc-link">فعال‌سازی از تنظیمات</a>
                </div>
            @endif

            {{-- ═══════════ کارت‌های آمار ═══════════ --}}
            <div class="cc-stats">
                <div class="cc-stat cc-stat--brand">
                    <span class="cc-stat-icon"><i class="fas fa-users"></i></span>
                    <span class="cc-stat-label">اعضای باشگاه</span>
                    <span class="cc-stat-value">{{ number_format($stats['members']) }}</span>
                </div>
                <div class="cc-stat">
                    <span class="cc-stat-icon"><i class="fas fa-star"></i></span>
                    <span class="cc-stat-label">امتیاز زندهٔ مشتریان</span>
                    <span class="cc-stat-value">{{ number_format($stats['points_live']) }}</span>
                </div>
                <div class="cc-stat">
                    <span class="cc-stat-icon"><i class="fas fa-right-left"></i></span>
                    <span class="cc-stat-label">تبدیل‌شده به کیف پول</span>
                    <span class="cc-stat-value">{{ number_format($stats['points_redeemed']) }}</span>
                </div>
                <div class="cc-stat">
                    <span class="cc-stat-icon"><i class="fas fa-hourglass-end"></i></span>
                    <span class="cc-stat-label">منقضی‌شده</span>
                    <span class="cc-stat-value">{{ number_format($stats['points_expired']) }}</span>
                </div>
                <div class="cc-stat">
                    <span class="cc-stat-icon"><i class="fas fa-gift"></i></span>
                    <span class="cc-stat-label">کارت هدیهٔ فعال</span>
                    <span class="cc-stat-value">{{ number_format($stats['giftcards_active']) }} <small>از {{ number_format($stats['giftcards_total']) }}</small></span>
                </div>
                <div class="cc-stat">
                    <span class="cc-stat-icon"><i class="fas fa-share-nodes"></i></span>
                    <span class="cc-stat-label">سفارش‌های معرفی‌شده</span>
                    <span class="cc-stat-value">{{ number_format($stats['referral_orders']) }}</span>
                </div>
            </div>

            <div class="cc-grid cc-grid--main-side">
                {{-- ═══════════ نمودار کسب امتیاز ۱۴ روز ═══════════ --}}
                <div class="cc-card">
                    <div class="cc-card__head">
                        <span class="cc-card__chip cc-card__chip--brand"><i class="fas fa-chart-column"></i></span>
                        <div>
                            <h5 class="cc-card__title">روند کسب امتیاز — ۱۴ روز اخیر</h5>
                        </div>
                    </div>
                    <div class="cc-chart">
                        @php($max = max(1, $chart->max('total')))
                        @foreach ($chart as $bar)
                            <div class="cc-chart-col" title="{{ $bar['day'] }}: {{ number_format($bar['total']) }}">
                                <div class="cc-chart-bar" style="height: {{ max(4, round(($bar['total'] / $max) * 100)) }}%"></div>
                                <span class="cc-chart-day">{{ \Illuminate\Support\Str::substr(\Illuminate\Support\Carbon::parse($bar['day'])->format('D'), 0, 1) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- ═══════════ خلاصهٔ قوانین و سطوح ═══════════ --}}
                <div class="cc-card">
                    <div class="cc-card__head">
                        <span class="cc-card__chip"><i class="fas fa-list-check"></i></span>
                        <div><h5 class="cc-card__title">قوانین امتیاز</h5></div>
                    </div>
                    <ul class="cc-mini-list">
                        @foreach ($rules as $rule)
                            <li>
                                <span>{{ $rule->title }}</span>
                                <span class="cc-badge {{ $rule->is_active ? 'cc-badge--on' : 'cc-badge--off' }}">{{ $rule->is_active ? 'فعال' : 'خاموش' }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="cc-card__head mt-3">
                        <span class="cc-card__chip"><i class="fas fa-medal"></i></span>
                        <div><h5 class="cc-card__title">سطوح معرفی</h5></div>
                    </div>
                    <ul class="cc-mini-list">
                        @foreach ($tiers as $tier)
                            <li>
                                <span>{{ $tier->title }} — از {{ number_format($tier->min_referrals) }} معرفی</span>
                                <span class="cc-badge cc-badge--on">{{ number_format($tier->bonus_points) }} امتیاز</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
