@extends(\Modules\CustomerClub\Support\ActivePanel::useNewPanel() ? 'admin-panel::layouts.module' : 'back.layouts.master')

@section('title', 'قوانین امتیاز — باشگاه مشتریان')

@push('styles')
    <link rel="stylesheet" href="{{ module_asset('CustomerClub', 'css/cc-admin.css') }}?v=1.0.0">
@endpush

@section('content')
<div class="app-content content">
    <div class="content-wrapper">
        <div class="content-body cc-scope">

            <div class="cc-page-head">
                <div class="cc-page-head-main">
                    <span class="cc-page-icon"><i class="fas fa-list-check"></i></span>
                    <div>
                        <h4 class="cc-page-title">قوانین امتیاز</h4>
                        <p class="cc-page-desc">هر رویداد چقدر امتیاز می‌دهد و آیا اصلاً فعال است؟</p>
                    </div>
                </div>
                <div class="cc-page-side">
                    <a href="{{ route('admin.customerclub.guide') }}" class="cc-btn cc-btn--ghost"><i class="fas fa-graduation-cap"></i> راهنما و آموزش</a>
                </div>
            </div>

            @if (session('toast-success'))
                <div class="cc-alert cc-alert--success"><i class="fas fa-circle-check"></i>{{ session('toast-success') }}</div>
            @endif

            <div class="cc-rules-grid">
                @foreach ($rules as $rule)
                    <form method="POST" action="{{ route('admin.customerclub.rules.update', $rule) }}" class="cc-card">
                        @csrf
                        <div class="cc-card__head">
                            <span class="cc-card__chip cc-card__chip--brand"><i class="fas fa-{{ ['fa-cart-shopping','fa-user-plus','fa-comment-dots','fa-cake-candles'][$loop->index % 4] }}"></i></span>
                            <div>
                                <h5 class="cc-card__title">{{ $rule->title }}</h5>
                                <p class="cc-card__desc">{{ $rule->description }}</p>
                            </div>
                        </div>

                        <div class="cc-field">
                            <label class="cc-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" {{ $rule->is_active ? 'checked' : '' }}>
                                <span class="cc-switch-slider"></span>
                                <span class="cc-switch-label">{{ $rule->is_active ? 'قانون فعال است' : 'قانون خاموش است' }}</span>
                            </label>
                        </div>

                        <div class="cc-field">
                            <label class="cc-label">عنوان نمایشی</label>
                            <input type="text" name="title" value="{{ $rule->title }}" class="cc-input" required>
                        </div>

                        @if ($rule->event === \Modules\CustomerClub\Models\LoyaltyRule::EVENT_PURCHASE)
                            <div class="cc-field">
                                <label class="cc-label">پلهٔ مبلغ (هر X تومان خرید = ۱ امتیاز)</label>
                                <input type="number" name="amount_step" value="{{ $rule->amount_step }}" min="1000" step="1000" class="cc-input" required>
                                <small class="cc-hint">الان: هر {{ number_format($rule->amount_step) }} تومان = ۱ امتیاز</small>
                            </div>
                        @else
                            <div class="cc-field">
                                <label class="cc-label">امتیاز ثابت رویداد</label>
                                <input type="number" name="points" value="{{ $rule->points }}" min="0" class="cc-input" required>
                            </div>
                        @endif

                        <button type="submit" class="cc-btn"><i class="fas fa-floppy-disk"></i> ذخیرهٔ قانون</button>
                    </form>
                @endforeach
            </div>

        </div>
    </div>
</div>
@endsection
