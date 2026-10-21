{{-- فاز ۲۸ — صفحه ۵۰۰ مدرن (نسخه قبلی: errors/old/500.blade.php) --}}
@extends('errors.layouts.modern', [
    'code'     => '500',
    'title'    => 'خطای غیرمنتظره در سرور',
    'subtitle' => 'مشکلی در اجرای درخواست پیش آمد. نگران نباشید — اطلاعات شما سالم است. چند لحظه صبر کنید و دوباره تلاش کنید؛ اگر تکرار شد با پشتیبانی تماس بگیرید.',
    'accent'   => '#ef4444',
    'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="8" x="2" y="2" rx="2"/><rect width="20" height="8" x="2" y="14" rx="2"/><line x1="6" x2="6.01" y1="6" y2="6"/><line x1="6" x2="6.01" y1="18" y2="18"/><path d="m11.5 5.5 2 2.5-2.6 0 1.8 2.3"/><path d="M12 17.5h4"/><path d="M17 5.5h1"/></svg>',
])

@section('actions')
    <button type="button" onclick="window.location.reload()" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
        تلاش دوباره
    </button>
    <a href="{{ url('/') }}" class="btn btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        بازگشت به فروشگاه
    </a>
@endsection
