@extends(\Modules\CustomerClub\Support\ActivePanel::useNewPanel() ? 'admin-panel::layouts.module' : 'back.layouts.master')

@section('title', 'تنظیمات باشگاه مشتریان')

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
                    <span class="cc-page-icon"><i class="fas fa-gear"></i></span>
                    <div>
                        <h4 class="cc-page-title">تنظیمات باشگاه مشتریان</h4>
                        <p class="cc-page-desc">هر امکان با کلید مستقل فعال/غیرفعال می‌شود — نرخ‌ها و حدود هم قابل شخصی‌سازی است</p>
                    </div>
                </div>
                <div class="cc-page-side">
                    <a href="{{ route('admin.customerclub.guide') }}" class="cc-btn cc-btn--ghost"><i class="fas fa-graduation-cap"></i> راهنما و آموزش</a>
                </div>
            </div>

            {{-- پیام‌های flash --}}
            @if (session('toast-success'))
                <div class="cc-alert cc-alert--success"><i class="fas fa-circle-check"></i>{{ session('toast-success') }}</div>
            @endif
            @if (session('toast-error'))
                <div class="cc-alert cc-alert--danger"><i class="fas fa-circle-xmark"></i>{{ session('toast-error') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.customerclub.settings.update') }}">
            @csrf

            <div class="cc-grid cc-grid--main-side">
                {{-- ═══════════ کلیدهای فعال/غیرفعال ═══════════ --}}
                <div class="cc-card">
                    <div class="cc-card__head">
                        <span class="cc-card__chip cc-card__chip--brand"><i class="fas fa-toggle-on"></i></span>
                        <div>
                            <h5 class="cc-card__title">فعال/غیرفعال هر امکان</h5>
                            <p class="cc-card__desc">خاموش کردن کلید اصلی، کل بخش مشتری باشگاه را پنهان می‌کند.</p>
                        </div>
                    </div>

                    @php
                        $toggles = [
                            'club_enabled' => ['کلید اصلی باشگاه مشتریان', 'با خاموشی آن، منو + صفحات مشتری از دسترس خارج می‌شوند (رویدادهای خودکار هم متوقف می‌شوند).'],
                            'loyalty_enabled' => ['امتیاز وفاداری', 'کسب امتیاز از خرید/ثبت‌نام/نظر/تولد + تبدیل به کیف پول.'],
                            'giftcards_enabled' => ['کارت هدیه', 'فرم فعال‌سازی کارت هدیه در پنل مشتری.'],
                            'referral_program_enabled' => ['معرفی دوستان', 'بخش معرفی در پنل مشتری + صفحهٔ فرود لینک دعوت.'],
                            'referral_bonus_enabled' => ['پاداش سطوح معرفی', 'پاداش یک‌بارمصرف برنزی/نقره‌ای/طلایی با خریدِ دعوت‌شده.'],
                        ];
                    @endphp

                    @foreach ($toggles as $key => [$title, $desc])
                        <div class="cc-toggle-row">
                            <div>
                                <b class="cc-toggle-title">{{ $title }}</b>
                                <small class="cc-hint">{{ $desc }}</small>
                            </div>
                            <label class="cc-switch">
                                <input type="hidden" name="{{ $key }}" value="0">
                                <input type="checkbox" name="{{ $key }}" value="1" {{ $settings[$key] === '1' ? 'checked' : '' }}>
                                <span class="cc-switch-slider"></span>
                            </label>
                        </div>
                    @endforeach
                </div>

                {{-- ═══════════ مقادیر عددی ═══════════ --}}
                <div class="cc-card">
                    <div class="cc-card__head">
                        <span class="cc-card__chip"><i class="fas fa-sliders"></i></span>
                        <div>
                            <h5 class="cc-card__title">مقادیر و حدود</h5>
                            <p class="cc-card__desc">نرخ تبدیل، حداقل تبدیل و انقضای امتیاز</p>
                        </div>
                    </div>

                    <div class="cc-field">
                        <label class="cc-label">نرخ تبدیل — هر ۱ امتیاز چند تومان اعتبار؟</label>
                        <input type="number" name="redeem_rate_toman" value="{{ $settings['redeem_rate_toman'] }}" min="1" max="10000000" class="cc-input" required>
                        <small class="cc-hint">مثلاً ۱۰۰۰ یعنی ۱۰۰ امتیاز = ۱۰۰٬۰۰۰ تومان اعتبار کیف پول</small>
                    </div>

                    <div class="cc-field">
                        <label class="cc-label">حداقل امتیاز برای تبدیل</label>
                        <input type="number" name="redeem_min_points" value="{{ $settings['redeem_min_points'] }}" min="1" max="1000000" class="cc-input" required>
                    </div>

                    <div class="cc-field">
                        <label class="cc-label">انقضای امتیاز (روز — صفر = بی‌انقضا)</label>
                        <input type="number" name="points_expiry_days" value="{{ $settings['points_expiry_days'] }}" min="0" max="36500" class="cc-input" required>
                        <small class="cc-hint">امتیازهای جدید از همین لحظه با این بازه منقضی می‌شوند (کامند شبانه ۰۳:۱۵).</small>
                    </div>

                    <button type="submit" class="cc-btn"><i class="fas fa-floppy-disk"></i> ذخیرهٔ تنظیمات</button>
                </div>
            </div>

            </form>

        </div>
    </div>
</div>
@endsection
