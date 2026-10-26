@extends(\Modules\CustomerClub\Support\ActivePanel::useNewPanel() ? 'admin-panel::layouts.module' : 'back.layouts.master')

@section('title', 'اعضای باشگاه — باشگاه مشتریان')

@push('styles')
    <link rel="stylesheet" href="{{ module_asset('CustomerClub', 'css/cc-admin.css') }}?v=1.0.0">
@endpush

@section('content')
<div class="app-content content">
    <div class="content-wrapper">
        <div class="content-body cc-scope">

            <div class="cc-page-head">
                <div class="cc-page-head-main">
                    <span class="cc-page-icon"><i class="fas fa-users"></i></span>
                    <div>
                        <h4 class="cc-page-title">اعضای باشگاه</h4>
                        <p class="cc-page-desc">مشتریان دارای امتیاز + تنظیم دستی (با دلیل اجباری — audit کامل)</p>
                    </div>
                </div>
                <div class="cc-page-side">
                    <a href="{{ route('admin.customerclub.points.index') }}" class="cc-btn cc-btn--ghost"><i class="fas fa-scroll"></i> دفتر امتیازها</a>
                </div>
            </div>

            @if (session('toast-success'))
                <div class="cc-alert cc-alert--success"><i class="fas fa-circle-check"></i>{{ session('toast-success') }}</div>
            @endif
            @if (session('toast-error'))
                <div class="cc-alert cc-alert--danger"><i class="fas fa-circle-xmark"></i>{{ session('toast-error') }}</div>
            @endif

            <form method="GET" action="{{ route('admin.customerclub.members.index') }}" class="cc-filter-bar">
                <input type="text" name="q" value="{{ $term }}" class="cc-input cc-input--sm" placeholder="نام / موبایل / ایمیل...">
                <button type="submit" class="cc-btn cc-btn--sm"><i class="fas fa-magnifying-glass"></i> جست‌وجو</button>
            </form>

            <div class="cc-card cc-card--flush">
                <div class="table-responsive">
                    <table class="cc-table">
                        <thead>
                            <tr>
                                <th>مشتری</th><th>موجودی امتیاز</th><th>کد معرفی</th><th>تنظیم دستی امتیاز</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>
                                        <b>{{ $row->first_name }} {{ $row->last_name }}</b>
                                        <small class="cc-muted d-block">{{ $row->username }}</small>
                                    </td>
                                    <td><span class="cc-balance">{{ number_format($row->live_balance) }}</span></td>
                                    <td>{{ $row->referral_code ?? '—' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.customerclub.members.adjust', $row) }}" class="cc-inline-form">
                                            @csrf
                                            <input type="number" name="points" class="cc-input cc-input--sm" style="width:110px" placeholder="±امتیاز" required>
                                            <input type="text" name="description" class="cc-input cc-input--sm" style="width:180px" placeholder="دلیل (اجباری)" required>
                                            <button type="submit" class="cc-btn cc-btn--sm"><i class="fas fa-sliders"></i> ثبت</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="cc-empty">هنوز عضوی امتیاز نگرفته است.</td></tr>
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
