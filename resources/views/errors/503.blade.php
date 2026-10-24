{{-- فاز ۲۸ — صفحه ۵۰۳ مدرن (نسخه قبلی: errors/old/503.blade.php) --}}
@extends('errors.layouts.modern', [
    'code'     => '503',
    'title'    => 'در حال بروزرسانی فروشگاه',
    'subtitle' => 'فروشگاه موقتاً در دست تعمیر و بروزرسانی است. به‌زودی با ظاهری بهتر و سرعتی بیشتر برمی‌گردیم — لطفاً چند دقیقه دیگر دوباره سر بزنید.',
    'accent'   => '#0ea5e9',
    'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
])

@section('actions')
    <button type="button" onclick="window.location.reload()" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        بررسی مجدد
    </button>
    <a href="{{ url('/') }}" class="btn btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        بازگشت به فروشگاه
    </a>
@endsection
