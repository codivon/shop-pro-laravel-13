{{-- فاز ۲۸ — صفحه ۴۰۴ مدرن (نسخه قبلی: errors/old/404.blade.php) --}}
@extends('errors.layouts.modern', [
    'code'     => '404',
    'title'    => 'صفحه پیدا نشد',
    'subtitle' => 'صفحه‌ای که به دنبال آن هستید وجود ندارد یا به آدرس دیگری منتقل شده است. ممکن است نشانی را اشتباه وارد کرده باشید.',
    'accent'   => '#10b981',
    'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6"/><path d="M8 11h6"/></svg>',
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
