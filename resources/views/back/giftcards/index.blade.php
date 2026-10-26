@extends(\Modules\CustomerClub\Support\ActivePanel::useNewPanel() ? 'admin-panel::layouts.module' : 'back.layouts.master')

@section('title', 'کارت‌های هدیه — باشگاه مشتریان')

@push('styles')
    <link rel="stylesheet" href="{{ module_asset('CustomerClub', 'css/cc-admin.css') }}?v=1.0.0">
@endpush

@section('content')
<div class="app-content content">
    <div class="content-wrapper">
        <div class="content-body cc-scope">

            <div class="cc-page-head">
                <div class="cc-page-head-main">
                    <span class="cc-page-icon"><i class="fas fa-gift"></i></span>
                    <div>
                        <h4 class="cc-page-title">کارت‌های هدیه</h4>
                        <p class="cc-page-desc">صدور دسته‌ای (تا ۵۰۰ کارت) + فعال/غیرفعال‌سازی — فعال‌سازی مشتری مستقیم به کیف پول شارژ می‌شود</p>
                    </div>
                </div>
            </div>

            @if (session('toast-success'))
                <div class="cc-alert cc-alert--success"><i class="fas fa-circle-check"></i>{{ session('toast-success') }}</div>
            @endif
            @if (session('toast-error'))
                <div class="cc-alert cc-alert--danger"><i class="fas fa-circle-xmark"></i>{{ session('toast-error') }}</div>
            @endif
            @if (session('giftcards_csv'))
                <div class="cc-alert cc-alert--info"><i class="fas fa-file-csv"></i>
                    فایل کدها: <code>storage/app/giftcards/{{ session('giftcards_csv') }}</code>
                </div>
            @endif

            @if (!$feature)
                <div class="cc-alert cc-alert--warn"><i class="fas fa-triangle-exclamation"></i>
                    کارت هدیه از تنظیمات غیرفعال است — مشتریان فرم فعال‌سازی نمی‌بینند.
                    <a href="{{ route('admin.customerclub.settings.edit') }}" class="cc-link">فعال‌سازی از تنظیمات</a>
                </div>
            @endif

            <div class="cc-grid cc-grid--main-side">
                {{-- صدور دسته‌ای --}}
                <form method="POST" action="{{ route('admin.customerclub.giftcards.issue') }}" class="cc-card">
                    @csrf
                    <div class="cc-card__head">
                        <span class="cc-card__chip cc-card__chip--brand"><i class="fas fa-stamp"></i></span>
                        <div>
                            <h5 class="cc-card__title">صدور دسته‌ای کارت هدیه</h5>
                            <p class="cc-card__desc">کدها با فرمت CC-XXXX-XXXX تولید و در CSV ذخیره می‌شوند.</p>
                        </div>
                    </div>
                    <div class="cc-row2">
                        <div class="cc-field">
                            <label class="cc-label">تعداد (۱ تا ۵۰۰)</label>
                            <input type="number" name="count" value="10" min="1" max="500" class="cc-input" required>
                        </div>
                        <div class="cc-field">
                            <label class="cc-label">مبلغ هر کارت (تومان)</label>
                            <input type="number" name="amount" value="50000" min="1000" step="1000" class="cc-input" required>
                        </div>
                    </div>
                    <div class="cc-row2">
                        <div class="cc-field">
                            <label class="cc-label">انقضا (اختیاری)</label>
                            <input type="date" name="expires_at" class="cc-input">
                        </div>
                        <div class="cc-field">
                            <label class="cc-label">یادداشت (اختیاری)</label>
                            <input type="text" name="note" class="cc-input" placeholder="مثلاً: کمپین نوروز ۱۴۰۵">
                        </div>
                    </div>
                    <button type="submit" class="cc-btn"><i class="fas fa-stamp"></i> صدور کارت‌ها</button>
                </form>

                {{-- آمار --}}
                <div class="cc-card">
                    <div class="cc-card__head">
                        <span class="cc-card__chip"><i class="fas fa-chart-simple"></i></span>
                        <div><h5 class="cc-card__title">وضعیت کارت‌ها</h5></div>
                    </div>
                    <ul class="cc-mini-list">
                        <li><span>کل کارت‌ها</span><span class="cc-badge">{{ number_format($stats['total']) }}</span></li>
                        <li><span>فعال</span><span class="cc-badge cc-badge--on">{{ number_format($stats['active']) }}</span></li>
                        <li><span>مصرف‌شده</span><span class="cc-badge cc-badge--off">{{ number_format($stats['redeemed']) }}</span></li>
                        <li><span>غیرفعال</span><span class="cc-badge cc-badge--off">{{ number_format($stats['disabled']) }}</span></li>
                    </ul>
                </div>
            </div>

            {{-- فیلتر و جدول --}}
            <form method="GET" action="{{ route('admin.customerclub.giftcards.index') }}" class="cc-filter-bar">
                <input type="text" name="code" value="{{ $filterCode }}" class="cc-input cc-input--sm" placeholder="جست‌وجوی کد...">
                <select name="status" class="cc-input cc-input--sm">
                    <option value="">همهٔ وضعیت‌ها</option>
                    <option value="active" {{ $filterStatus === 'active' ? 'selected' : '' }}>فعال</option>
                    <option value="redeemed" {{ $filterStatus === 'redeemed' ? 'selected' : '' }}>مصرف‌شده</option>
                    <option value="disabled" {{ $filterStatus === 'disabled' ? 'selected' : '' }}>غیرفعال</option>
                </select>
                <button type="submit" class="cc-btn cc-btn--sm"><i class="fas fa-filter"></i> اعمال فیلتر</button>
            </form>

            <div class="cc-card cc-card--flush">
                <div class="table-responsive">
                    <table class="cc-table">
                        <thead>
                            <tr><th>کد</th><th>مبلغ</th><th>وضعیت</th><th>مصرف‌کننده</th><th>انقضا</th><th>یادداشت</th><th>اقدام</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $card)
                                <tr>
                                    <td><code>{{ $card->code }}</code></td>
                                    <td>{{ number_format($card->amount) }} ت</td>
                                    <td><span class="cc-badge {{ $card->status === 'active' ? 'cc-badge--on' : 'cc-badge--off' }}">{{ \Modules\CustomerClub\Models\GiftCard::STATUSES[$card->status] ?? $card->status }}</span></td>
                                    <td>{{ $card->user?->username ?? '—' }}</td>
                                    <td>{{ $card->expires_at ? $card->expires_at->format('Y/m/d') : '—' }}</td>
                                    <td class="cc-cell-wide">{{ \Illuminate\Support\Str::limit($card->note, 40) }}</td>
                                    <td>
                                        @if ($card->status !== \Modules\CustomerClub\Models\GiftCard::STATUS_REDEEMED)
                                            <form method="POST" action="{{ route('admin.customerclub.giftcards.toggle', $card) }}">
                                                @csrf
                                                <button type="submit" class="cc-btn cc-btn--sm {{ $card->status === 'active' ? 'cc-btn--danger' : '' }}">
                                                    {{ $card->status === 'active' ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                                                </button>
                                            </form>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="cc-empty">هنوز کارت هدیه‌ای صادر نشده است.</td></tr>
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
