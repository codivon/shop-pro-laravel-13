@extends(\Modules\CustomerClub\Support\ActivePanel::useNewPanel() ? 'admin-panel::layouts.module' : 'back.layouts.master')

@section('title', 'دفتر امتیازها — باشگاه مشتریان')

@push('styles')
    <link rel="stylesheet" href="{{ module_asset('CustomerClub', 'css/cc-admin.css') }}?v=1.0.0">
@endpush

@section('content')
<div class="app-content content">
    <div class="content-wrapper">
        <div class="content-body cc-scope">

            <div class="cc-page-head">
                <div class="cc-page-head-main">
                    <span class="cc-page-icon"><i class="fas fa-scroll"></i></span>
                    <div>
                        <h4 class="cc-page-title">دفتر امتیازها</h4>
                        <p class="cc-page-desc">کامل‌ترین سابقه: کسب، تبدیل، تنظیم دستی و انقضا — قابل فیلتر</p>
                    </div>
                </div>
                <div class="cc-page-side">
                    <a href="{{ route('admin.customerclub.members.index') }}" class="cc-btn cc-btn--ghost"><i class="fas fa-users"></i> اعضا و تنظیم دستی</a>
                </div>
            </div>

            @if (session('toast-success'))
                <div class="cc-alert cc-alert--success"><i class="fas fa-circle-check"></i>{{ session('toast-success') }}</div>
            @endif

            {{-- فیلترها --}}
            <form method="GET" action="{{ route('admin.customerclub.points.index') }}" class="cc-filter-bar">
                <input type="text" name="user" value="{{ $filterUser }}" class="cc-input cc-input--sm" placeholder="جست‌وجوی مشتری...">
                <select name="type" class="cc-input cc-input--sm">
                    <option value="">همهٔ انواع</option>
                    @foreach ($types as $tKey => $tLabel)
                        <option value="{{ $tKey }}" {{ $filterType === $tKey ? 'selected' : '' }}>{{ $tLabel }}</option>
                    @endforeach
                </select>
                <select name="event" class="cc-input cc-input--sm">
                    <option value="">همهٔ رویدادها</option>
                    @foreach ($events as $eKey => $eLabel)
                        <option value="{{ $eKey }}" {{ $filterEvent === $eKey ? 'selected' : '' }}>{{ $eLabel }}</option>
                    @endforeach
                </select>
                <button type="submit" class="cc-btn cc-btn--sm"><i class="fas fa-filter"></i> اعمال فیلتر</button>
            </form>

            <div class="cc-card cc-card--flush">
                <div class="table-responsive">
                    <table class="cc-table">
                        <thead>
                            <tr>
                                <th>مشتری</th><th>امتیاز</th><th>باقی‌ماندهٔ لات</th><th>نوع</th>
                                <th>رویداد</th><th>توضیح</th><th>انقضا</th><th>تاریخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>
                                        @if ($row->user)
                                            {{ $row->user->first_name }} {{ $row->user->last_name }}
                                            <small class="cc-muted">({{ $row->user->username }})</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="{{ $row->points > 0 ? 'cc-text-success' : 'cc-text-danger' }}" style="font-weight:800">
                                        {{ $row->points > 0 ? '+' : '' }}{{ number_format($row->points) }}
                                    </td>
                                    <td>{{ $row->remaining > 0 ? number_format($row->remaining) : '—' }}</td>
                                    <td><span class="cc-badge {{ in_array($row->type, ['award','manual','adjust']) ? 'cc-badge--on' : 'cc-badge--off' }}">{{ $types[$row->type] ?? $row->type }}</span></td>
                                    <td>{{ $row->event ? ($events[$row->event] ?? $row->event) : '—' }}</td>
                                    <td class="cc-cell-wide">{{ \Illuminate\Support\Str::limit($row->description, 60) }}</td>
                                    <td>{{ $row->expires_at ? $row->expires_at->format('Y/m/d') : '—' }}</td>
                                    <td>{{ $row->created_at->format('Y/m/d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="cc-empty">سابقه‌ای با این فیلترها یافت نشد.</td></tr>
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
