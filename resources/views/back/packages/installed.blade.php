@extends('back.layouts.master')

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('back/assets/css/pages/packages/index.css') }}">
@endpush

@section('content')
    <div class="app-content content pkg-page">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">

            {{-- Breadcrumbs --}}
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <div class="breadcrumb-wrapper col-12">
                                <ol class="breadcrumb no-border">
                                    <li class="breadcrumb-item">مدیریت</li>
                                    <li class="breadcrumb-item"><a href="{{ route('admin.packages.index') }}">بازار</a></li>
                                    <li class="breadcrumb-item active">ماژول‌های نصب‌شده</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-body">

                {{-- Hero header --}}
                <div class="pkg-hero mb-3" style="background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);">
                    <div class="pkg-hero-content d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <div class="pkg-hero-icon mr-1">
                                <i class="feather icon-grid"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">ماژول‌های نصب‌شده</h3>
                                <small>مدیریت پکیج‌های نصب‌شده روی این پروژه</small>
                            </div>
                        </div>
                        <a href="{{ route('admin.packages.index') }}" class="btn pkg-btn-soft">
                            <i class="feather icon-plus"></i> نصب پکیج جدید
                        </a>
                    </div>
                </div>

                {{-- Stat cards --}}
                @php
                    $total = $modules->total();
                    $activeCount = $modules->where('is_active', true)->count();
                    $updatingCount = $modules->where('status', 'updating')->count();
                    $failedCount = $modules->where('status', 'failed')->count();
                @endphp

                <div class="row match-height mb-3">
                    <div class="col-6 col-xl-3">
                        <div class="pkg-stat" style="--accent:#10b981; --accent-soft:rgba(16,185,129,0.1)">
                            <div class="pkg-stat-icon"><i class="feather icon-package"></i></div>
                            <div class="pkg-stat-meta">
                                <span class="pkg-stat-value">{{ $total }}</span>
                                <span class="pkg-stat-label">کل ماژول‌ها</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="pkg-stat" style="--accent:#0ea5e9; --accent-soft:rgba(14,165,233,0.1)">
                            <div class="pkg-stat-icon"><i class="feather icon-check-circle"></i></div>
                            <div class="pkg-stat-meta">
                                <span class="pkg-stat-value">{{ $activeCount }}</span>
                                <span class="pkg-stat-label">فعال</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="pkg-stat" style="--accent:#f59e0b; --accent-soft:rgba(245,158,11,0.1)">
                            <div class="pkg-stat-icon"><i class="feather icon-clock"></i></div>
                            <div class="pkg-stat-meta">
                                <span class="pkg-stat-value">{{ $updatingCount }}</span>
                                <span class="pkg-stat-label">در حال آپدیت</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="pkg-stat" style="--accent:#ef4444; --accent-soft:rgba(239,68,68,0.1)">
                            <div class="pkg-stat-icon"><i class="feather icon-alert-triangle"></i></div>
                            <div class="pkg-stat-meta">
                                <span class="pkg-stat-value">{{ $failedCount }}</span>
                                <span class="pkg-stat-label">ناموفق</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modules grid --}}
                @if ($modules->count())
                    <div class="pkg-installed-grid">
                        @foreach ($modules as $module)
                            <div class="pkg-installed-card" data-slug="{{ $module->slug }}">
                                <div class="pkg-installed-header">
                                    <div class="pkg-installed-icon">
                                        <i class="feather icon-box"></i>
                                    </div>
                                    <div class="pkg-installed-title-area">
                                        <h5 class="pkg-installed-title btn-show-modal" data-slug="{{ $module->slug }}" style="cursor: pointer;">
                                            {{-- فاز ۸۶ — نمایش «عنوان» ماژول (module.json پکیج → کش بازار → نام فنی) --}}
                                            {{ $module->displayName() }}
                                        </h5>
                                        <code class="pkg-installed-slug">{{ $module->slug }}</code>
                                    </div>
                                    <div class="pkg-installed-status-badge">
                                        @if ($module->status === 'updating')
                                            <span class="pkg-status-chip pkg-status-running">
                                                <span class="spinner-border spinner-border-sm"></span> در حال آپدیت
                                            </span>
                                        @elseif ($module->status === 'failed')
                                            <span class="pkg-status-chip pkg-status-failed">
                                                <i class="feather icon-alert-triangle"></i> ناموفق
                                            </span>
                                        @else
                                            <span class="pkg-status-chip pkg-status-success">
                                                <i class="feather icon-check-circle"></i> نصب‌شده
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="pkg-installed-info">
                                    <div class="pkg-installed-info-row">
                                        <span class="pkg-installed-info-label">
                                            <i class="feather icon-git-commit"></i> نسخه
                                        </span>
                                        <span class="pkg-installed-version-chip">v{{ $module->version }}</span>
                                    </div>
                                    <div class="pkg-installed-info-row">
                                        <span class="pkg-installed-info-label">
                                            <i class="feather icon-clock"></i> انقضای لایسنس
                                        </span>
                                        @if ($module->license_expires_at)
                                            @php
                                                $daysLeft = null;
                                                if (!$module->isExpired()) {
                                                    $daysLeft = (int) now()->diffInDays($module->license_expires_at, false);
                                                }
                                            @endphp
                                            <span class="pkg-installed-info-value @if($module->isExpired()) text-danger @elseif($daysLeft !== null && $daysLeft <= 14) text-warning @endif">
                                                {{ jdate($module->license_expires_at)->format('Y/m/d') }}
                                                @if ($module->isExpired())
                                                    <small>(منقضی)</small>
                                                @elseif($daysLeft !== null)
                                                    <small>({{ $daysLeft }} روز)</small>
                                                @endif
                                            </span>
                                        @else
                                            <span class="pkg-installed-info-value text-success">
                                                <i class="feather icon-infinity"></i> نامحدود
                                            </span>
                                        @endif
                                    </div>
                                    <div class="pkg-installed-info-row">
                                        <span class="pkg-installed-info-label">
                                            <i class="feather icon-calendar"></i> تاریخ نصب
                                        </span>
                                        <span class="pkg-installed-info-value">{{ jdate($module->installed_at)->format('Y/m/d H:i') }}</span>
                                    </div>
                                </div>

                                @if ($module->status === 'failed' && $module->last_error)
                                    <div class="pkg-installed-error">
                                        <i class="feather icon-alert-octagon"></i>
                                        <span>{{ \Illuminate\Support\Str::limit($module->last_error, 120) }}</span>
                                    </div>
                                @endif

                                <div class="pkg-installed-actions">
                                    {{-- فاز ۱۳۳ — دکمهٔ «آپدیت ماژول» → مودال بررسی و بروزرسانی --}}
                                    <button type="button"
                                            class="pkg-modal-btn pkg-modal-btn-warning btn-update-module"
                                            data-slug="{{ $module->slug }}"
                                            data-name="{{ $module->displayName() }}"
                                            data-version="{{ $module->version }}"
                                            data-thumbnail="{{ $module->cache->thumbnail ?? '' }}"
                                            @if ($module->status === 'updating') disabled @endif>
                                        <i class="feather icon-refresh-cw"></i> آپدیت ماژول
                                    </button>
                                    <button type="button"
                                            class="pkg-modal-btn pkg-modal-btn-outline btn-show-modal"
                                            data-slug="{{ $module->slug }}">
                                        <i class="feather icon-eye"></i> جزئیات
                                    </button>
                                    <button type="button"
                                            class="pkg-modal-btn btn-toggle {{ $module->is_active ? 'pkg-modal-btn-outline' : 'pkg-modal-btn-success' }}"
                                            id="modal-btn-toggle"
                                            data-slug="{{ $module->slug }}">
                                        <i class="feather icon-{{ $module->is_active ? 'eye-off' : 'eye' }}"></i>
                                        {{ $module->is_active ? 'غیرفعال' : 'فعال' }}
                                    </button>
                                    <button type="button"
                                            class="pkg-modal-btn pkg-modal-btn-danger btn-uninstall"
                                            id="modal-btn-uninstall"
                                            data-slug="{{ $module->slug }}"
                                            data-name="{{ $module->name }}">
                                        <i class="feather icon-trash-2"></i> حذف
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if ($modules->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $modules->appends(request()->all())->links() }}
                        </div>
                    @endif
                @else
                    <div class="pkg-empty-state">
                        <div class="pkg-empty-icon" style="background:#eff6ff; color:#3b82f6;">
                            <i class="feather icon-inbox"></i>
                        </div>
                        <h5>هیچ ماژولی نصب نشده است!</h5>
                        <p>برای نصب اولین پکیج به بازار بروید.</p>
                        <a href="{{ route('admin.packages.index') }}" class="btn pkg-btn-primary mt-2">
                            <i class="feather icon-package"></i> رفت به بازار
                        </a>
                    </div>
                @endif

            </div>
        </div>
    </div>
    {{-- ====================== --}}
    {{-- مدال‌ها --}}
    {{-- ====================== --}}

    {{-- مدال جزئیات پکیج --}}
    <div class="modal fade pkg-modal" id="package-detail-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content pkg-modal-content">
                <div class="modal-header pkg-modal-header p-1">
                    <h5 class="modal-title pkg-modal-title" id="modal-pkg-title"></h5>
                    <button type="button" class="close pkg-modal-close-btn" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body pkg-modal-body" id="modal-pkg-body"></div>
                <div class="modal-footer pkg-modal-footer" id="modal-pkg-footer"></div>
            </div>
        </div>
    </div>

    {{-- ======================
         فاز ۱۳۳ — مودال آپدیت ماژول (بررسی نسخه جدید ← بروزرسانی)
         ====================== --}}
    <div class="modal fade pkg-update-modal" id="package-update-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content pkg-upm-content">

                {{-- Header --}}
                <div class="pkg-upm-header">
                    <div class="pkg-upm-header-glow"></div>
                    <div class="pkg-upm-header-icon">
                        <i class="feather icon-refresh-cw"></i>
                    </div>
                    <div class="pkg-upm-header-text">
                        <h5 class="pkg-upm-title">آپدیت ماژول</h5>
                        <small class="pkg-upm-subtitle">بررسی نسخه جدید و بروزرسانی پکیج</small>
                    </div>
                    <button type="button" class="pkg-upm-close" data-dismiss="modal" aria-label="بستن">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                {{-- Body --}}
                <div class="pkg-upm-body">

                    {{-- کارت پکیج: تصویر + نام + نسخه --}}
                    <div class="pkg-upm-package">
                        <div class="pkg-upm-thumb" id="upm-thumb">
                            <i class="feather icon-package"></i>
                        </div>
                        <div class="pkg-upm-package-info">
                            <h6 class="pkg-upm-package-name" id="upm-name">—</h6>
                            <div class="pkg-upm-versions">
                                <span class="pkg-upm-version-chip" id="upm-version-chip">
                                    <i class="feather icon-git-commit"></i>
                                    نسخه نصب‌شده: <strong>v<span id="upm-version">—</span></strong>
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- ناحیه وضعیت (state-driven) --}}
                    <div class="pkg-upm-state" id="upm-state">
                        <div class="pkg-upm-hint">
                            <div class="pkg-upm-hint-icon">
                                <i class="feather icon-search"></i>
                            </div>
                            <div class="pkg-upm-hint-text">
                                <strong>بررسی نسخه جدید</strong>
                                <span>برای اتصال به سرور پکیج‌ها و اطلاع از وجود نسخه جدید، روی دکمه «بررسی نسخه جدید» کلیک کنید.</span>
                            </div>
                        </div>
                    </div>

                    {{-- تغییرات نسخه جدید (بعد از بررسی) --}}
                    <div class="pkg-upm-changelog" id="upm-changelog" style="display:none;">
                        <div class="pkg-upm-changelog-title">
                            <i class="feather icon-file-text"></i> تغییرات نسخه جدید
                        </div>
                        <div class="pkg-upm-changelog-body" id="upm-changelog-body"></div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="pkg-upm-footer">
                    <button type="button" class="pkg-upm-btn pkg-upm-btn-ghost" data-dismiss="modal" id="upm-btn-cancel">
                        <i class="feather icon-x"></i> انصراف
                    </button>
                    <button type="button" class="pkg-upm-btn pkg-upm-btn-check" id="upm-btn-check" data-slug="">
                        <span class="pkg-upm-btn-spinner spinner-border spinner-border-sm" style="display:none;"></span>
                        <i class="feather icon-search"></i>
                        <span id="upm-btn-check-text">بررسی نسخه جدید</span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- Confirm uninstall modal --}}
    <div class="modal fade" id="uninstall-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content pkg-confirm-modal">
                <div class="modal-header pkg-confirm-header" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <h5 class="modal-title">
                        <i class="feather icon-alert-triangle"></i> حذف ماژول
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body pkg-confirm-body">
                    <p>آیا از حذف <strong id="uninstall-pkg-name"></strong> مطمئن هستید؟</p>
                    <div class="pkg-confirm-alert" style="background:#fef2f2; border-color:#fecaca; color:#991b1b;">
                        <i class="feather icon-alert-triangle"></i>
                        <span>تمام فایل‌های ماژول از پوشه Modules حذف می‌شوند و در صورت وجود، جداول ماژول rollback می‌شوند. این عمل غیرقابل بازگشت است.</span>
                    </div>
                </div>
                <div class="modal-footer pkg-confirm-footer">
                    <button type="button" class="btn pkg-btn-ghost" data-dismiss="modal">انصراف</button>
                    <form id="uninstall-form" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn pkg-btn-danger">
                            <i class="feather icon-trash-2"></i> بله، حذف شود
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection


@include('back.partials.plugins', ['plugins' => ['sweetalert2']])

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('back/assets/css/pages/packages/update-modal.css') }}?v=1">
@endpush

@push('scripts')
    <script>
        window.csrfToken = '{{ csrf_token() }}';
        window.routes = {
            show:          '{{ route("admin.packages.show", ":slug") }}',
            install:       '{{ route("admin.packages.install", ":slug") }}',
            update:        '{{ route("admin.packages.update", ":slug") }}',
            uninstall:     '{{ route("admin.packages.uninstall", ":slug") }}',
            toggle:        '{{ route("admin.packages.toggle", ":slug") }}',
            status:        '{{ route("admin.packages.status", ":slug") }}',
            'check-version': '{{ route("admin.packages.check-version", ":slug") }}',
        };
    </script>
    <script src="{{ asset('back/assets/js/pages/packages/installed.js') }}?v=3"></script>
    <script src="{{ asset('back/assets/js/pages/packages/modal.js') }}?v=5"></script>
    <script src="{{ asset('back/assets/js/pages/packages/progress.js') }}?v=1"></script>
    <script src="{{ asset('back/assets/js/pages/packages/update-modal.js') }}?v=1"></script>
@endpush
