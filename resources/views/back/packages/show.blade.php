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
                                    <li class="breadcrumb-item"><a href="{{ route('admin.packages.index') }}">بازار پکیج‌ها</a></li>
                                    <li class="breadcrumb-item active">{{ $package['name'] ?? $package['slug'] ?? '' }}</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-body">
                <div class="row">
                    {{-- Left: details --}}
                    <div class="col-12 col-lg-8">

                        {{-- Header card --}}
                        <section class="card pkg-card mb-2">
                            <div class="card-body">
                                <div class="d-flex align-items-start gap-2 flex-wrap">
                                    <div class="pkg-thumb-large" style="background-image: url('{{ $package['thumbnail'] ?? asset('back/assets/images/package-default.png') }}')"></div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-1 mb-1 flex-wrap">
                                            <h3 class="mb-0 font-weight-bolder">{{ $package['name'] ?? '' }}</h3>
                                            <span class="pkg-version-large">v{{ $package['latest_version'] ?? ($package['version'] ?? '') }}</span>
                                            @if ($package['is_free'] ?? false)
                                                <span class="pkg-badge pkg-badge-free">رایگان</span>
                                            @else
                                                <span class="pkg-badge pkg-badge-paid">پولی</span>
                                            @endif
                                        </div>
                                        <div class="pkg-meta mb-1">
                                            @if (!empty($package['author']))
                                                <span class="pkg-meta-chip"><i class="feather icon-user"></i> {{ $package['author'] }}</span>
                                            @endif
                                            @if (!empty($package['category']))
                                                <span class="pkg-meta-chip"><i class="feather icon-tag"></i> {{ $package['category'] }}</span>
                                            @endif
                                            @if (!empty($package['downloads']))
                                                <span class="pkg-meta-chip"><i class="feather icon-download"></i> {{ number_format($package['downloads']) }} دانلود</span>
                                            @endif
                                        </div>
                                        <p class="pkg-desc text-muted mb-0">{!! sanitize_rich_text($package['short_description'] ?? '') !!}</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- Description / changelog --}}
                        @if (!empty($package['long_description']) || !empty($package['changelog']))
                            <section class="card pkg-card mb-2">
                                <div class="card-header msg-card-header">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="msg-card-badge"><i class="feather icon-file-text"></i></span>
                                        <h4 class="card-title mb-0">توضیحات کامل</h4>
                                    </div>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        @if (!empty($package['long_description']))
                                            <div class="pkg-long-desc mb-2">{!! sanitize_rich_text($package['long_description']) !!}</div>
                                        @endif

                                        @if (!empty($package['changelog']))
                                            <h6 class="mb-1">تاریخچه نسخه‌ها</h6>
                                            <ul class="pkg-changelog">
                                                @foreach ($package['changelog'] as $ver => $changes)
                                                    <li>
                                                        <strong>v{{ $ver }}</strong>
                                                        <span class="text-muted">{!! sanitize_rich_text(is_array($changes) ? implode('<br>', e($changes)) : e($changes)) !!}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            </section>
                        @endif

                        {{-- Installation logs --}}
                        @if ($logs->isNotEmpty())
                            <section class="card pkg-card mb-2">
                                <div class="card-header msg-card-header">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="msg-card-badge bg-soft-sky"><i class="feather icon-list"></i></span>
                                        <h4 class="card-title mb-0">لاگ‌های اخیر</h4>
                                    </div>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table msg-table mb-0">
                                                <thead>
                                                <tr>
                                                    <th class="text-center">#</th>
                                                    <th class="text-center">عملیات</th>
                                                    <th class="text-center">از نسخه</th>
                                                    <th class="text-center">به نسخه</th>
                                                    <th class="text-center">وضعیت</th>
                                                    <th class="text-center">تاریخ</th>
                                                    <th>پیام</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($logs as $log)
                                                    <tr>
                                                        <td class="text-center text-muted">{{ $log->id }}</td>
                                                        <td class="text-center">
                                                            @switch($log->action)
                                                                @case('install') <span class="channel-pill ch-notif">نصب</span> @break
                                                                @case('update') <span class="channel-pill ch-sms">آپدیت</span> @break
                                                                @case('uninstall') <span class="channel-pill ch-email">حذف</span> @break
                                                                @case('activate') <span class="channel-pill ch-notif">فعال‌سازی</span> @break
                                                                @case('deactivate') <span class="channel-pill ch-email">غیرفعال‌سازی</span> @break
                                                            @endswitch
                                                        </td>
                                                        <td class="text-center text-muted">{{ $log->from_version ?: '—' }}</td>
                                                        <td class="text-center">{{ $log->to_version ?: '—' }}</td>
                                                        <td class="text-center">
                                                            @if ($log->status === 'running')
                                                                <span class="status-pill status-pending"><span class="dot"></span> در حال اجرا</span>
                                                            @elseif ($log->status === 'success')
                                                                <span class="status-pill status-sent"><span class="dot"></span> موفق</span>
                                                            @else
                                                                <span class="status-pill status-failed"><span class="dot"></span> ناموفق</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center text-muted text-nowrap">{{ jdate($log->created_at) }}</td>
                                                        <td class="text-muted" style="max-width: 250px;">
                                                            <span class="d-inline-block text-truncate" style="max-width:250px" title="{{ $log->message }}">
                                                                {{ $log->message ?: '—' }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        @endif
                    </div>

                    {{-- Right: action panel --}}
                    <div class="col-12 col-lg-4">

                        <section class="card pkg-card mb-2" id="action-panel">
                            <div class="card-header msg-card-header">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="msg-card-badge bg-soft-emerald"><i class="feather icon-settings"></i></span>
                                    <h4 class="card-title mb-0">عملیات پکیج</h4>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="card-body">

                                    {{-- Status --}}
                                    <div id="install-status-box" class="pkg-status-box mb-2 @if($installed) @if($installed->status === 'updating') status-running @elseif($installed->status === 'failed') status-failed @else status-installed @endif @else status-not-installed @endif">
                                        @if (!$installed)
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="feather icon-info"></i>
                                                <span>این پکیج نصب نشده است.</span>
                                            </div>
                                        @elseif ($installed->status === 'updating')
                                            <div class="d-flex align-items-center gap-1">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                                <span>در حال نصب/آپدیت...</span>
                                            </div>
                                        @elseif ($installed->status === 'failed')
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="feather icon-alert-triangle text-danger"></i>
                                                <span>نصب با خطا مواجه شد.</span>
                                            </div>
                                            @if ($installed->last_error)
                                                <small class="d-block mt-1 text-danger">{{ $installed->last_error }}</small>
                                            @endif
                                        @else
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="feather icon-check-circle text-success"></i>
                                                <span>نصب‌شده - نسخه {{ $installed->version }}</span>
                                            </div>
                                            @if ($installed->license_expires_at)
                                                <small class="d-block mt-1 text-muted">
                                                    <i class="feather icon-clock"></i>
                                                    انقضای لایسنس: {{ jdate($installed->license_expires_at) }}
                                                    @if ($installed->isExpired())
                                                        <span class="text-danger">(منقضی شده)</span>
                                                    @endif
                                                </small>
                                            @endif
                                        @endif
                                    </div>

                                    {{-- Actions --}}
                                    <div class="d-grid gap-1">
                                        @if (!$installed)
                                            <button type="button"
                                                    class="btn btn-primary btn-block waves-effect waves-light btn-install"
                                                    data-slug="{{ $package['slug'] }}"
                                                    data-name="{{ $package['name'] }}"
                                                    data-free="{{ $package['is_free'] ?? false ? '1' : '0' }}"
                                                    data-price="{{ $package['price'] ?? 0 }}">
                                                <i class="feather icon-download-cloud"></i>
                                                @if ($package['is_free'] ?? false)
                                                    نصب پکیج
                                                @else
                                                    پرداخت و نصب
                                                @endif
                                            </button>
                                        @elseif ($installed->status === 'updating')
                                            <button type="button" class="btn btn-warning btn-block" disabled>
                                                <span class="spinner-border spinner-border-sm"></span> در حال نصب...
                                            </button>
                                        @else
                                            {{-- Update button if available --}}
                                            @php
                                                $latestVersion = $package['latest_version'] ?? $package['version'] ?? '';
                                                $hasUpdate = version_compare($latestVersion, $installed->version, '>');
                                            @endphp
                                            @if ($hasUpdate)
                                                <button type="button"
                                                        class="btn btn-warning btn-block waves-effect waves-light btn-update"
                                                        data-slug="{{ $package['slug'] }}"
                                                        @if($installed->isExpired()) disabled @endif>
                                                    <i class="feather icon-arrow-up"></i> آپدیت به نسخه {{ $latestVersion }}
                                                </button>
                                                @if ($installed->isExpired())
                                                    <small class="text-danger text-center d-block">لایسنس منقضی شده است.</small>
                                                @endif
                                            @else
                                                <div class="alert alert-success text-center mb-0">
                                                    <i class="feather icon-check-circle"></i> آخرین نسخه نصب شده است.
                                                </div>
                                            @endif

                                            {{-- Activate/Deactivate --}}
                                            <button type="button"
                                                    class="btn btn-{{ $installed->is_active ? 'outline-warning' : 'success' }} btn-block waves-effect waves-light btn-toggle"
                                                    data-slug="{{ $package['slug'] }}">
                                                <i class="feather icon-{{ $installed->is_active ? 'eye-off' : 'eye' }}"></i>
                                                {{ $installed->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                                            </button>

                                            {{-- Uninstall --}}
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-block waves-effect waves-light btn-uninstall"
                                                    data-slug="{{ $package['slug'] }}"
                                                    data-name="{{ $package['name'] }}">
                                                <i class="feather icon-trash-2"></i> حذف ماژول
                                            </button>
                                        @endif
                                    </div>

                                </div>
                            </div>
                        </section>

                        {{-- Requirements --}}
                        @if (!empty($package['requirements']))
                            <section class="card pkg-card">
                                <div class="card-header msg-card-header">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="msg-card-badge"><i class="feather icon-info"></i></span>
                                        <h4 class="card-title mb-0">پیش‌نیازها</h4>
                                    </div>
                                </div>
                                <div class="card-content">
                                    <div class="card-body">
                                        <ul class="pkg-requirements mb-0">
                                            @foreach ($package['requirements'] as $req => $ver)
                                                <li><code>{{ $req }}</code> >= {{ $ver }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </section>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm install modal --}}
    <div class="modal fade text-left" id="install-confirm-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content msg-modal">
                <div class="modal-header msg-modal-header">
                    <h4 class="modal-title"><i class="feather icon-download-cloud text-primary"></i> نصب پکیج</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>آیا از نصب پکیج <strong id="confirm-pkg-name"></strong> مطمئن هستید؟</p>
                    <div id="confirm-payment-info" class="alert alert-info d-none">
                        <i class="feather icon-info"></i>
                        <span>این پکیج پولی است و مبلغ <strong id="confirm-pkg-price"></strong> تومان باید پرداخت شود. برای ادامه به درگاه پرداخت هدایت می‌شوید.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light waves-effect waves-light" data-dismiss="modal">انصراف</button>
                    <button type="button" id="confirm-install-btn" class="btn btn-primary waves-effect waves-light">
                        <i class="feather icon-check"></i> <span id="confirm-btn-text">شروع نصب</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Uninstall modal --}}
    <div class="modal fade text-left" id="uninstall-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content msg-modal">
                <div class="modal-header msg-modal-header">
                    <h4 class="modal-title"><i class="feather icon-alert-triangle text-danger"></i> حذف ماژول</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>آیا از حذف <strong id="uninstall-pkg-name"></strong> مطمئن هستید؟</p>
                    <div class="alert alert-warning">
                        <i class="feather icon-alert-triangle"></i>
                        <span>تمام فایل‌های ماژول از پوشه Modules حذف می‌شوند و در صورت وجود، جداول ماژول rollback می‌شوند. این عمل غیرقابل بازگشت است.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <form id="uninstall-form">
                        @csrf
                        <button type="button" class="btn btn-light waves-effect waves-light" data-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-danger waves-effect waves-light">
                            <i class="feather icon-trash-2"></i> بله، حذف شود
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@include('back.partials.plugins', ['plugins' => ['sweetalert2']])

@push('scripts')
    <script>
        window.PACKAGE_SLUG = "{{ $package['slug'] ?? '' }}";
        window.PACKAGE_INSTALLED = @json($installed ? true : false);
    </script>
    <script src="{{ asset('back/assets/js/pages/packages/show.js') }}?v=1"></script>
@endpush
