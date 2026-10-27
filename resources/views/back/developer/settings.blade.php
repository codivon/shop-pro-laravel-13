@extends('back.layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('back/assets/css/settings-tabs.css') }}?v=1.0.0">
@endpush

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>

        <div class="content-wrapper">
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <div class="breadcrumb-wrapper col-12">
                                <ol class="breadcrumb no-border">
                                    <li class="breadcrumb-item">مدیریت</li>
                                    <li class="breadcrumb-item">توسعه دهنده</li>
                                    <li class="breadcrumb-item active">
                                        تنظیمات توسعه دهنده
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-body">
                @if (session('success'))
                    <div class="alert alert-success" role="alert">
                        <i class="feather icon-check-circle"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger" role="alert">
                        <i class="feather icon-alert-circle"></i>
                        {{ session('error') }}
                    </div>
                @endif

                <section class="card">
                    <div id="main-card" class="card-content">
                        <div class="card-body">

                        {{-- نوار تب‌ها (فاز ۱۰۵ — الگوی مشترک تنظیمات) --}}
                        <nav class="stab-tabs" role="tablist" aria-label="بخش‌های تنظیمات برنامه‌نویس">
                            <button type="button" class="stab-tab is-active" data-stab="cron" role="tab" aria-selected="true"><i class="feather icon-clock"></i> کرون جاب</button>
                            <button type="button" class="stab-tab" data-stab="update" role="tab" aria-selected="false"><i class="feather icon-refresh-cw"></i> حالت بروزرسانی</button>
                            <button type="button" class="stab-tab" data-stab="webpush" role="tab" aria-selected="false"><i class="feather icon-bell"></i> اعلانات وب پوش</button>
                            <button type="button" class="stab-tab" data-stab="general" role="tab" aria-selected="false"><i class="feather icon-settings"></i> تنظیمات دیگر</button>
                        </nav>

                        {{-- تب ۱: کرون جاب --}}
                        <section class="stab-pane is-active" data-stab-pane="cron" role="tabpanel">
                            {{-- Shared hosting cron settings --}}
                            <div class="col-12 mb-4">
                                <h3>کرون جاب (Cron Job)</h3>
                                <hr>

                                @if ($schedule_run)
                                    <div class="cron-alert cron-alert--active" role="alert">
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <i class="feather icon-check-circle"></i>
                                            <strong>کرون جاب فعال است</strong>
                                        </div>

                                        <small>
                                            زمان‌بندی خودکار لاراول روی سرور در حال اجراست.
                                        </small>
                                    </div>

                                    <div class="cron-alert cron-alert--last-run" role="status">
                                        <strong>آخرین اجرا:</strong>
                                        <span class="ltr">
                                            {{ option('schedule_run') }}
                                        </span>
                                    </div>
                                @else
                                    <div class="cron-alert cron-alert--warning" role="alert">
                                        <div class="d-flex align-items-center gap-1 mb-2">
                                            <i class="feather icon-alert-circle"></i>
                                            <strong>کرون جاب تنظیم نشده است</strong>
                                        </div>

                                        <p class="mb-2" style="font-size: .9rem;">
                                            برای فعال‌سازی زمان‌بندی لاراول، باید یک کرون جاب روی سرور تنظیم کنید.
                                            دستور کرون به شکل زیر است:
                                        </p>

                                        <div class="cron-code">
                                            * * * * * [مسیر PHP] [مسیر artisan] schedule:run >> /dev/null 2>&1
                                        </div>

                                        <div class="cron-hint">
                                            <i
                                                class="feather icon-info"
                                                style="margin-top: 2px; flex-shrink: 0;"
                                            ></i>

                                            <span>
                                                مسیر دقیق PHP و فایل
                                                <code>artisan</code>
                                                را از پنل هاست یا سرور خود دریافت کنید و جایگزین
                                                <strong>[مسیر PHP]</strong>
                                                و
                                                <strong>[مسیر artisan]</strong>
                                                نمایید.
                                            </span>
                                        </div>

                                        <a
                                            class="cron-docs-link"
                                            href="https://laravel.com/docs/{{ config('installer.laravel-version') }}/scheduling#running-the-scheduler"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <i
                                                class="feather icon-external-link"
                                                style="font-size: .78rem;"
                                            ></i>
                                            راهنمای رسمی لاراول
                                        </a>
                                    </div>

                                    @if (option('schedule_run'))
                                        <div class="cron-alert cron-alert--stale" role="status">
                                            <strong>آخرین اجرا قبل از قطع شدن:</strong>
                                            <span class="ltr">
                                                {{ option('schedule_run') }}
                                            </span>
                                        </div>
                                    @endif
                                @endif

                                <div class="cron-alert cron-alert--info" role="note">
                                    <div class="d-flex align-items-start gap-2">
                                        <i
                                            class="feather icon-info mt-25 cron-info-icon"
                                            style="flex-shrink: 0;"
                                        ></i>

                                        <div>
                                            <strong>
                                                اجرای صف از طریق Cron Job، مخصوص هاست اشتراکی
                                            </strong>

                                            <p class="mb-0 mt-50">
                                                اگر هاست شما اجازه اجرای دائمی Queue Worker را نمی‌دهد،
                                                این حالت را فعال کنید. Queue به جای اجرای مداوم،
                                                توسط دو Cron Job زیر مدیریت می‌شود.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <form
                                    class="developer-form mt-2 mb-2"
                                    action="{{ route('admin.developer.settings') }}"
                                    method="POST"
                                >
                                    @csrf
                                    @method('PUT')

                                    {{-- Identify the submitted settings group --}}
                                    <input
                                        type="hidden"
                                        name="settings_scope"
                                        value="shared_hosting"
                                    >

                                    <div class="vs-checkbox-con vs-checkbox-primary">
                                        {{-- Send "off" when the checkbox is unchecked --}}
                                        <input
                                            type="hidden"
                                            name="shared_hosting_cron_enabled"
                                            value="off"
                                        >

                                        <input
                                            type="checkbox"
                                            name="shared_hosting_cron_enabled"
                                            value="on"
                                            {{ $shared_hosting_cron_enabled ? 'checked' : '' }}
                                        >

                                        <span class="vs-checkbox">
                                            <span class="vs-checkbox--check">
                                                <i class="vs-icon feather icon-check"></i>
                                            </span>
                                        </span>

                                        <span>
                                            فعال‌سازی اجرای صف با دو Cron Job
                                            (هاست اشتراکی)
                                        </span>
                                    </div>

                                    <div class="cron-commands-box">
                                        <p class="cron-commands-title">
                                            دستورهایی که باید در پنل Cron Job هاست خود وارد کنید:
                                        </p>

                                        <p class="cron-commands-note">
                                            مسیر PHP و مسیر فایل
                                            <code>artisan</code>
                                            را متناسب با هاست خود تنظیم کنید.
                                            نمونه دقیق این مسیرها را در بخش
                                            <strong>Cron Job</strong>
                                            پنل هاست خود می‌توانید مشاهده کنید.
                                        </p>

                                        <div class="mb-3">
                                            <small class="cron-cmd-label">
                                                ① زمان‌بندی لاراول، هر دقیقه
                                            </small>

                                            <div class="cron-cmd-block mt-1">
                                                * * * * * [مسیر PHP] [مسیر artisan] schedule:run >> /dev/null 2>&1
                                            </div>

                                            <div class="cron-hint mt-2">
                                                <i
                                                    class="feather icon-info"
                                                    style="margin-top: 2px; flex-shrink: 0;"
                                                ></i>

                                                <span>
                                                    مسیر PHP و مسیر artisan را از پنل هاست خود دریافت
                                                    و جایگزین کنید.
                                                </span>
                                            </div>
                                        </div>

                                        <div>
                                            <small class="cron-cmd-label">
                                                ② اجرای صف، یک‌بار خالی‌سازی و توقف
                                            </small>

                                            <div class="cron-cmd-block mt-1">
                                                * * * * * [مسیر PHP] [مسیر artisan] queue:work --tries=3 --sleep=3 --queue=default --stop-when-empty
                                            </div>

                                            <div class="cron-hint mt-2">
                                                <i
                                                    class="feather icon-info"
                                                    style="margin-top: 2px; flex-shrink: 0;"
                                                ></i>

                                                <span>
                                                    مسیر PHP و مسیر artisan را از پنل هاست خود دریافت
                                                    و جایگزین کنید.
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        class="cron-submit-btn"
                                    >
                                        <i class="feather icon-save"></i>
                                        ذخیره تنظیمات صف
                                    </button>
                                </form>
                            </div>
                        </section>

                        <div class="col-md-12">

                            {{-- تب ۲: حالت بروزرسانی --}}
                            <section class="stab-pane" data-stab-pane="update" role="tabpanel">

                                {{-- Maintenance mode settings --}}
                                <h3 class="mt-5">حالت بروزرسانی</h3>
                                <hr>

                                @if (is_file(storage_path('framework/down')))
                                    <form
                                        class="developer-form"
                                        action="{{ route('admin.developer.upApplication') }}"
                                        method="POST"
                                    >
                                        @csrf

                                        <div class="row">
                                            <div class="col-12">
                                                <button
                                                    type="submit"
                                                    class="btn btn-lg btn-relief-success mb-1 waves-effect waves-light"
                                                >
                                                    غیرفعال کردن حالت بروزرسانی
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                @else
                                    <form
                                        id="downApplication-form"
                                        class="developer-form"
                                        action="{{ route('admin.developer.downApplication') }}"
                                        method="POST"
                                    >
                                        @csrf

                                        <div class="row">
                                            <div class="col-12">
                                                <div class="alert alert-info" role="alert">
                                                    <p>
                                                        پس از فعال کردن حالت بروزرسانی،
                                                        شما می‌توانید از این آدرس وارد وبسایت شوید.
                                                    </p>

                                                    <p>
                                                        <span>{{ url('/') }}</span>
                                                        <span id="downApplication-secret">
                                                            /{{ $random_str }}
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="downApplication-secret-input">
                                                    Secret
                                                </label>

                                                <div class="input-group mb-1">
                                                    <input
                                                        id="downApplication-secret-input"
                                                        type="text"
                                                        name="secret"
                                                        class="form-control ltr"
                                                        value="{{ $random_str }}"
                                                    >
                                                </div>
                                            </div>

                                            <div class="col-md-3 col-12">
                                                <div class="form-group">
                                                    <label for="maintenance-title">
                                                        عنوان
                                                    </label>

                                                    <input
                                                        id="maintenance-title"
                                                        type="text"
                                                        class="form-control"
                                                        name="info_maintenance_mode_title"
                                                        value="{{ option('info_maintenance_mode_title', 'در حال بروزرسانی هستیم!') }}"
                                                    >
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 col-12">
                                                <div class="form-group">
                                                    <label for="maintenance-description">
                                                        توضیحات
                                                    </label>

                                                    <textarea
                                                        id="maintenance-description"
                                                        class="form-control"
                                                        rows="4"
                                                        name="info_maintenance_mode_description"
                                                    >{{ option('info_maintenance_mode_description', 'در حال بروزرسانی وبسایت هستیم لطفا دقایقی بعد مراجعه کنید.<br>از صبر و شکیبایی شما متشکریم.') }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <button
                                                    type="submit"
                                                    class="btn btn-lg btn-relief-danger mb-1 waves-effect waves-light"
                                                >
                                                    فعال کردن حالت بروزرسانی
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                @endif
                            </section>

                            {{-- تب ۳: اعلانات وب پوش --}}
                            <section class="stab-pane" data-stab-pane="webpush" role="tabpanel">
                                {{-- Web push settings --}}
                                <h3 class="mt-5">اعلانات وب پوش</h3>
                                <hr>

                                <form
                                    class="developer-form"
                                    action="{{ route('admin.developer.webpushNotification') }}"
                                    method="POST"
                                >
                                    @csrf

                                    <div class="row">
                                        <div class="col-12">
                                            <button
                                                type="submit"
                                                class="btn btn-lg btn-relief-success mb-1 waves-effect waves-light"
                                            >
                                                تنظیم کلیدهای وب پوش
                                            </button>
                                        </div>

                                        <div class="col-12">
                                            <div class="alert alert-info" role="alert">
                                                <p>کلیدهای فعلی:</p>

                                                <p class="text-right">
                                                    <strong>VAPID_PUBLIC_KEY:</strong>
                                                    {{ config('webpush.vapid.public_key') }}
                                                </p>

                                                <p class="text-right">
                                                    <strong>VAPID_PRIVATE_KEY:</strong>
                                                    {{ config('webpush.vapid.private_key') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </section>

                            {{-- تب ۴: تنظیمات دیگر + پاک‌سازی کش --}}
                            <section class="stab-pane" data-stab-pane="general" role="tabpanel">
                                {{-- General developer settings --}}
                                <form
                                    class="developer-form"
                                    action="{{ route('admin.developer.settings') }}"
                                    method="POST"
                                >
                                    @csrf
                                    @method('PUT')

                                    {{-- Identify the submitted settings group --}}
                                    <input
                                        type="hidden"
                                        name="settings_scope"
                                        value="general"
                                    >

                                    <h3 class="mt-5">تنظیمات دیگر</h3>
                                    <hr>

                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="updater-token">
                                                شماره سفارش
                                            </label>

                                            <div class="input-group mb-75">
                                                <input
                                                    id="updater-token"
                                                    type="text"
                                                    name="SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN"
                                                    class="form-control ltr"
                                                    value="{{ option('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN') }}"
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-5">
                                        <div class="col-md-3">
                                            <fieldset class="checkbox">
                                                <abbr
                                                    title="در حالت توسعه فایل‌های JS و CSS کامپایل نشده‌اند"
                                                >
                                                    <div class="vs-checkbox-con vs-checkbox-primary">
                                                        {{-- Send "false" when the checkbox is unchecked --}}
                                                        <input
                                                            type="hidden"
                                                            name="app_debug_mode"
                                                            value="false"
                                                        >

                                                        <input
                                                            type="checkbox"
                                                            name="app_debug_mode"
                                                            value="true"
                                                            {{ config('app.debug') == true ? 'checked' : '' }}
                                                        >

                                                        <span class="vs-checkbox">
                                                            <span class="vs-checkbox--check">
                                                                <i class="vs-icon feather icon-check"></i>
                                                            </span>
                                                        </span>

                                                        <span>
                                                            فعال کردن حالت توسعه
                                                        </span>
                                                    </div>
                                                </abbr>
                                            </fieldset>
                                        </div>

                                        <div class="col-md-3">
                                            <fieldset class="checkbox">
                                                <div class="vs-checkbox-con vs-checkbox-primary">
                                                    {{-- Send "false" when the checkbox is unchecked --}}
                                                    <input
                                                        type="hidden"
                                                        name="debugbar_enabled"
                                                        value="false"
                                                    >

                                                    <input
                                                        type="checkbox"
                                                        name="debugbar_enabled"
                                                        value="true"
                                                        {{ config('debugbar.enabled') == true ? 'checked' : '' }}
                                                    >

                                                    <span class="vs-checkbox">
                                                        <span class="vs-checkbox--check">
                                                            <i class="vs-icon feather icon-check"></i>
                                                        </span>
                                                    </span>

                                                    <span>
                                                        فعال کردن دیباگ بار
                                                    </span>
                                                </div>
                                            </fieldset>
                                        </div>

                                        <div class="col-md-3">
                                            <fieldset class="checkbox">
                                                <div class="vs-checkbox-con vs-checkbox-primary">
                                                    {{-- Send "false" when the checkbox is unchecked --}}
                                                    <input
                                                        type="hidden"
                                                        name="enable_old_updates"
                                                        value="false"
                                                    >

                                                    <input
                                                        type="checkbox"
                                                        name="enable_old_updates"
                                                        value="true"
                                                        {{ option('enable_old_updates', 'true') == 'true' ? 'checked' : '' }}
                                                    >

                                                    <span class="vs-checkbox">
                                                        <span class="vs-checkbox--check">
                                                            <i class="vs-icon feather icon-check"></i>
                                                        </span>
                                                    </span>

                                                    <small>
                                                        فعال کردن متد قدیمی آپدیت
                                                        (این متد اکسپایر شده)
                                                    </small>
                                                </div>
                                            </fieldset>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                            <button
                                                type="submit"
                                                class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1"
                                            >
                                                <i class="feather icon-save"></i>
                                                ذخیره تغییرات
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                <hr>
                                {{-- Clear application cache --}}
                                <div class="cache-clear-box">
                                    <div class="cache-clear-content">
                                        <div class="cache-clear-icon">
                                            <i class="feather icon-database"></i>
                                        </div>

                                        <div>
                                            <h5 class="cache-clear-title">
                                                پاک‌سازی کش برنامه
                                            </h5>

                                            <p class="cache-clear-description mb-0">
                                                با اجرای این عملیات، کش تنظیمات، مسیرها، ویوها،
                                                رویدادها و سایر کش‌های لاراول پاک می‌شوند.
                                            </p>
                                        </div>
                                    </div>

                                    <form
                                        class="developer-form"
                                        action="{{ route('admin.developer.clearCache') }}"
                                        method="POST"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="cache-clear-button"
                                        >
                                            <i class="feather icon-trash-2"></i>
                                            <span>پاک‌سازی کش و اجرای Optimize Clear</span>
                                        </button>
                                    </form>
                                </div>
                            </section>

                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

@include('back.partials.plugins', ['plugins' => ['jquery-validation']])

@php
    $help_videos = [
        config('general.video-helpes.cronjob'),
    ];
@endphp

@push('scripts')
    <script src="{{ asset('back/assets/js/pages/developer/settings.js') }}?v=2"></script>
    <script src="{{ asset('back/assets/js/settings-tabs.js') }}?v=1.0.0"></script>
@endpush
