@extends(\Modules\CustomerClub\Support\ActivePanel::useNewPanel() ? 'admin-panel::layouts.module' : 'back.layouts.master')

@section('title', 'معرفی دوستان — باشگاه مشتریان')

@push('styles')
    <link rel="stylesheet" href="{{ module_asset('CustomerClub', 'css/cc-admin.css') }}?v=1.0.0">
@endpush

@section('content')
<div class="app-content content">
    <div class="content-wrapper">
        <div class="content-body cc-scope">

            <div class="cc-page-head">
                <div class="cc-page-head-main">
                    <span class="cc-page-icon"><i class="fas fa-share-nodes"></i></span>
                    <div>
                        <h4 class="cc-page-title">معرفی دوستان</h4>
                        <p class="cc-page-desc">معرف‌های فعال، آمار معرفی موفق و سطوح پاداش</p>
                    </div>
                </div>
                <div class="cc-page-side">
                    <span class="cc-badge {{ $bonusOn ? 'cc-badge--on' : 'cc-badge--off' }}">
                        پاداش سطوح: {{ $bonusOn ? 'فعال' : 'خاموش' }}
                    </span>
                </div>
            </div>

            {{-- سطوح --}}
            <div class="cc-stats">
                @foreach ($tiers as $tier)
                    <div class="cc-stat">
                        <span class="cc-stat-icon" style="background: {{ $tier->color ?? '#0d9488' }}"></span>
                        <span class="cc-stat-label">سطح {{ $tier->title }}</span>
                        <span class="cc-stat-value">{{ number_format($tier->min_referrals) }}+ <small>معرفی</small></span>
                        <small class="cc-muted" style="color:#e5e7eb">پاداش: {{ number_format($tier->bonus_points) }} امتیاز</small>
                    </div>
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.customerclub.referrals.index') }}" class="cc-filter-bar">
                <input type="text" name="q" value="{{ $term }}" class="cc-input cc-input--sm" placeholder="کد معرفی / نام مشتری...">
                <button type="submit" class="cc-btn cc-btn--sm"><i class="fas fa-magnifying-glass"></i> جست‌وجو</button>
            </form>

            <div class="cc-card cc-card--flush">
                <div class="table-responsive">
                    <table class="cc-table">
                        <thead>
                            <tr><th>معرف</th><th>کد دعوت</th><th>دعوت‌شده</th><th>معرفی موفق</th><th>سطح فعلی</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php
                                    $best = \Modules\CustomerClub\Models\ReferralTier::bestFor((int) $row->referred_paid);
                                @endphp
                                <tr>
                                    <td>
                                        <b>{{ $row->first_name }} {{ $row->last_name }}</b>
                                        <small class="cc-muted d-block">{{ $row->username }}</small>
                                    </td>
                                    <td><code>{{ $row->referral_code }}</code></td>
                                    <td>{{ number_format($row->referred_total) }} نفر</td>
                                    <td><b class="cc-text-success">{{ number_format($row->referred_paid) }} نفر</b></td>
                                    <td>
                                        @if ($best)
                                            <span class="cc-badge cc-badge--on">{{ $best->title }}</span>
                                        @else
                                            <span class="cc-badge cc-badge--off">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="cc-empty">هنوز هیچ معرفی‌ای ثبت نشده است.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="cc-paginate">{{ $rows->links() }}</div>

        </div>
    </div>
</div>
@endsection
