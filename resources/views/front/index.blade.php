@extends('front::user.layouts.master')

@section('title', 'باشگاه مشتریان')

@section('user-content')
    <div class="headline-profile">
        <div class="row">
            <div class="col-12">
                <h4 class="mb-4">باشگاه مشتریان</h4>

                {{-- فاز ۸۹ — راهنما و آموزش درون‌صفحه‌ای (به‌جای منوی سایدبار) --}}
                @include('customer-club::front.partials.guide_cta')

                @if(session('toast-success'))
                    <div class="alert alert-success">{{ session('toast-success') }}</div>
                @endif
                @if(session('toast-error'))
                    <div class="alert alert-danger">{{ session('toast-error') }}</div>
                @endif

                {{-- ═══════════ کارت خلاصه ═══════════ --}}
                <style>
                    .cc-club{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-bottom:20px}
                    .cc-stat{background:linear-gradient(135deg,#0f766e 0%,#0d9488 100%);border-radius:18px;padding:20px 22px;color:#fff;position:relative;overflow:hidden;box-shadow:0 8px 22px rgba(13,148,136,.25)}
                    .cc-stat--ghost{background:#fff;color:#111827;box-shadow:0 2px 10px rgba(0,0,0,.06);border:1px solid #e5e7eb}
                    .cc-stat--ghost .cc-stat-label{opacity:.75}
                    .cc-stat::after{content:'';position:absolute;top:-40px;left:-30px;width:120px;height:120px;background:rgba(255,255,255,.12);border-radius:50%}
                    .cc-stat-label{font-size:13px;opacity:.9;margin-bottom:6px;display:block}
                    .cc-stat-value{font-size:26px;font-weight:800;margin:0;position:relative;z-index:1}
                    .cc-stat-value small{font-size:13px;font-weight:600;opacity:.85}
                    .cc-card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
                    .cc-card h5{font-size:16px;font-weight:800;margin:0 0 4px;display:flex;align-items:center;gap:8px}
                    .cc-card .cc-hint{font-size:12.5px;color:#6b7280;margin:0 0 14px}
                    .cc-input{width:100%;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;font-size:14px;outline:none;transition:border .15s}
                    .cc-input:focus{border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.15)}
                    .cc-btn{display:inline-flex;align-items:center;gap:8px;background:#0d9488;color:#fff;border:none;border-radius:12px;padding:10px 20px;font-weight:700;font-size:14px;cursor:pointer;transition:background .15s,transform .15s;text-decoration:none}
                    .cc-btn:hover{background:#0f766e;transform:translateY(-1px);color:#fff}
                    .cc-btn--outline{background:#fff;color:#0f766e;border:1.5px solid #0d9488}
                    .cc-btn--outline:hover{background:#f0fdfa;color:#0f766e}
                    .cc-badge{display:inline-block;background:#f0fdfa;color:#0f766e;border:1px solid #99f6e4;border-radius:999px;padding:3px 10px;font-size:12px;font-weight:700}
                    .cc-table{width:100%;border-collapse:collapse;font-size:13.5px}
                    .cc-table th{background:#f8fafc;color:#374151;font-weight:700;padding:10px 12px;text-align:right;border-bottom:2px solid #e5e7eb}
                    .cc-table td{padding:10px 12px;border-bottom:1px solid #f1f5f9}
                    .cc-plus{color:#059669;font-weight:800}.cc-minus{color:#dc2626;font-weight:800}
                    .cc-tier{display:flex;align-items:center;gap:10px;margin-bottom:10px}
                    .cc-tier-bar{flex:1;height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden}
                    .cc-tier-fill{height:100%;background:linear-gradient(90deg,#0d9488,#14b8a6);border-radius:99px}
                    .cc-copy{border:1px dashed #0d9488;background:#f0fdfa;border-radius:12px;padding:10px 14px;font-size:14px;font-weight:700;color:#0f766e;display:flex;align-items:center;justify-content:space-between;gap:10px;word-break:break-all}
                    @media (max-width:576px){.cc-table thead{display:none}.cc-table tr{display:block;border:1px solid #e5e7eb;border-radius:12px;margin-bottom:8px;padding:6px}}
                    @media (max-width:576px){.cc-table td{display:flex;justify-content:space-between;border:none}}
                </style>

                <div class="cc-club">
                    <div class="cc-stat">
                        <span class="cc-stat-label"><i class="fas fa-star ml-1"></i> امتیاز فعلی شما</span>
                        <p class="cc-stat-value">{{ number_format($balance) }} <small>امتیاز</small></p>
                    </div>
                    <div class="cc-stat cc-stat--ghost">
                        <span class="cc-stat-label"><i class="fas fa-wallet ml-1"></i> ارزش تبدیل</span>
                        <p class="cc-stat-value">{{ number_format($balance * $rate) }} <small>تومان اعتبار</small></p>
                    </div>
                    <div class="cc-stat cc-stat--ghost">
                        <span class="cc-stat-label"><i class="fas fa-users ml-1"></i> معرفی موفق</span>
                        <p class="cc-stat-value">{{ number_format($successfulReferrals) }} <small>نفر</small></p>
                    </div>
                </div>

                @if ($loyaltyOn)
                    {{-- ═══════════ تبدیل امتیاز به کیف پول ═══════════ --}}
                    <div class="cc-card">
                        <h5><i class="fas fa-right-left text-info"></i> تبدیل امتیاز به اعتبار کیف پول</h5>
                        <p class="cc-hint">هر {{ number_format(1) }} امتیاز = {{ number_format($rate) }} تومان اعتبار کیف پول — حداقل تبدیل {{ number_format($minPoints) }} امتیاز.
                            @if ($expiryDays > 0) امتیازها تا {{ $expiryDays }} روز معتبرند. @endif
                        </p>
                        <form method="POST" action="{{ route('front.club.redeem') }}" class="row align-items-end g-2">
                            @csrf
                            <div class="col-12 col-md-6">
                                <input type="number" name="points" value="{{ $minPoints }}" min="{{ $minPoints }}" max="{{ $balance }}" step="1"
                                       class="cc-input" placeholder="مثلاً {{ number_format($minPoints) }}" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <button type="submit" class="cc-btn w-100 justify-content-center" @if($balance < $minPoints) disabled @endif>
                                    <i class="fas fa-coins"></i> تبدیل به کیف پول
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                @if ($giftcardsOn)
                    {{-- ═══════════ کارت هدیه ═══════════ --}}
                    <div class="cc-card">
                        <h5><i class="fas fa-gift text-warning"></i> فعال‌سازی کارت هدیه</h5>
                        <p class="cc-hint">کد کارت هدیه (CC-XXXX-XXXX) را وارد کنید تا مبلغ آن مستقیم به کیف پول شما شارژ شود.</p>
                        <form method="POST" action="{{ route('front.club.giftcard.redeem') }}" class="row align-items-end g-2">
                            @csrf
                            <div class="col-12 col-md-6">
                                <input type="text" name="code" class="cc-input text-center" style="letter-spacing:2px" placeholder="CC-XXXX-XXXX" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <button type="submit" class="cc-btn cc-btn--outline w-100 justify-content-center">
                                    <i class="fas fa-bolt"></i> فعال‌سازی
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                @if ($referralOn)
                    {{-- ═══════════ معرفی دوستان ═══════════ --}}
                    <div class="cc-card">
                        <h5><i class="fas fa-share-nodes text-success"></i> معرفی دوستان</h5>
                        <p class="cc-hint">لینک زیر را برای دوستانتان بفرستید؛ با ثبت‌نام و خرید آن‌ها، به سطوح برنزی/نقره‌ای/طلایی پاداش یک‌بارمصرف می‌گیرید.</p>

                        <div class="cc-copy mb-3">
                            <span><i class="fas fa-link ml-1"></i> {{ url('referral/'.$user->referral_code) }}</span>
                            <button type="button" class="cc-btn cc-btn--outline py-1 px-3" style="font-size:12.5px"
                                    onclick="ccCopy(this)" data-cc-link="{{ url('referral/'.$user->referral_code) }}">
                                <i class="fas fa-copy"></i> کپی
                            </button>
                        </div>

                        <p class="mb-2" style="font-size:13px;color:#6b7280">
                            {{ number_format($totalReferrals) }} دعوت‌شده — {{ number_format($successfulReferrals) }} معرفی موفق
                        </p>

                        @foreach ($tiers as $tier)
                            @php
                                $reached = $successfulReferrals >= $tier->min_referrals;
                                $pct = min(100, ($tier->min_referrals > 0 ? ($successfulReferrals / $tier->min_referrals) * 100 : 0));
                            @endphp
                            <div class="cc-tier">
                                <span class="cc-badge" style="{{ $reached ? '' : 'background:#f9fafb;color:#9ca3af;border-color:#e5e7eb' }}">
                                    {{ $tier->title }} ({{ number_format($tier->bonus_points) }} امتیاز)
                                </span>
                                <div class="cc-tier-bar"><div class="cc-tier-fill" style="width: {{ $pct }}%"></div></div>
                                <small style="color:#9ca3af;white-space:nowrap">{{ $reached ? '✔ رسیده‌اید' : number_format($tier->min_referrals).' معرفی' }}</small>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- ═══════════ دفتر امتیاز من ═══════════ --}}
                <div class="cc-card">
                    <h5><i class="fas fa-scroll text-secondary"></i> آخرین امتیازهای من</h5>
                    @if ($ledger->isEmpty())
                        <p class="cc-hint mb-0">هنوز امتیازی ندارید — با اولین خرید عضو باشگاه شوید!</p>
                    @else
                        <div class="table-responsive">
                            <table class="cc-table">
                                <thead>
                                    <tr><th>توضیح</th><th>امتیاز</th><th>انقضا</th><th>تاریخ</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($ledger as $row)
                                        <tr>
                                            <td>{{ $row->description }}</td>
                                            <td class="{{ $row->points > 0 ? 'cc-plus' : 'cc-minus' }}">
                                                {{ $row->points > 0 ? '+' : '' }}{{ number_format($row->points) }}
                                            </td>
                                            <td>{{ $row->expires_at ? $row->expires_at->format('Y/m/d') : '—' }}</td>
                                            <td>{{ $row->created_at->format('Y/m/d') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <script>
                    function ccCopy(btn) {
                        var link = btn.getAttribute('data-cc-link');
                        if (navigator.clipboard) { navigator.clipboard.writeText(link); }
                        else {
                            var ta = document.createElement('textarea'); ta.value = link;
                            document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove();
                        }
                        var old = btn.innerHTML; btn.innerHTML = '<i class="fas fa-check"></i> کپی شد';
                        setTimeout(function(){ btn.innerHTML = old; }, 1800);
                    }
                </script>
            </div>
        </div>
    </div>
@endsection
