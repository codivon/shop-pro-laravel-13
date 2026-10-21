{{-- فاز ۲۸ — صفحه ۴۰۳ مدرن (نسخه قبلی: errors/old/403.blade.php) --}}
@extends('errors.layouts.modern', [
    'code'     => '403',
    'title'    => 'اجازه دسترسی ندارید',
    'subtitle' => 'شما اجازه‌ی دسترسی به این بخش را ندارید. اگر فکر می‌کنید این یک اشتباه است، با مدیر سیستم تماس بگیرید یا با حساب کاربری دیگری وارد شوید.',
    'accent'   => '#ef4444',
    'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><rect x="9" y="10" width="6" height="5" rx="1"/><path d="M10.5 10v-1.5a1.5 1.5 0 1 1 3 0V10"/></svg>',
])

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        بازگشت به فروشگاه
    </a>
    <a href="{{ url()->previous() ?: url('/') }}" class="btn btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        بازگشت به صفحه قبل
    </a>
@endsection
