{{-- فاز ۲۸ — صفحه ۴۰۴ پنل مدیریت (نسخه قبلی: errors/old/404-admin.blade.php) --}}
@php
    try {
        $dashboardUrl = \Illuminate\Support\Facades\Route::has('np.dashboard') ? route('np.dashboard') : url('/admin');
    } catch (\Throwable $e) {
        $dashboardUrl = url('/admin');
    }
@endphp
@extends('errors.layouts.modern', [
    'code'     => '404',
    'title'    => 'این صفحه در پنل مدیریت وجود ندارد',
    'subtitle' => 'نشانی وارد شده در پنل مدیریت یافت نشد. ممکن است آدرس تغییر کرده باشد یا صفحه حذف شده باشد.',
    'accent'   => '#f59e0b',
    'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>',
])

@section('actions')
    <a href="{{ $dashboardUrl }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
        داشبورد مدیریت
    </a>
    <a href="{{ url()->previous() ?: $dashboardUrl }}" class="btn btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        بازگشت به صفحه قبل
    </a>
@endsection
