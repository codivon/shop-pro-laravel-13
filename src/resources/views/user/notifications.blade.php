{{-- اعلان‌ها — قرارداد UserController@notifications : $notifications (هر آیتم: title, message, created_at, userRecipients()->first()->read_at), $active --}}
@extends('front::user.layouts.master', ['title' => 'اطلاعیه‌ها'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    <div class="ui-box">
        <div class="ui-box-item ui-box-white">
            <div class="ui-box-item-title">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h2 class="fw-bold font-16 mb-0">
                        <i class="bi bi-bell main-color-one-color me-1"></i>
                        اطلاعیه ها
                    </h2>
                    <span class="font-12 text-muted">
                        <i class="bi bi-envelope-open me-1"></i>
                        کل: {{ $notifications->total() }} |
                        خوانده‌نشده: {{ auth()->user()->unreadNotifications()->count() }}
                    </span>
                </div>
            </div>
            <div class="ui-box-item-desc">
                @if($notifications->count())
                    @foreach($notifications as $notification)
                        @php
                            $recipient = $notification->userRecipients()->first();
                            $isRead = $recipient ? ! is_null($recipient->read_at) : true;
                        @endphp
                        <div class="alert {{ $isRead ? 'alert-light' : 'alert-warning' }} rounded-3" role="alert">
                            <div class="notifi-title d-flex justify-content-between align-items-center flex-wrap gap-1">
                                <h3 class="alert-link font-15 mb-0">{{ $notification->title }}</h3>
                                <p class="text-muted font-13 mb-0">
                                    @unless($isRead)
                                        <span class="badge bg-danger rounded-pill font-10 me-1">جدید</span>
                                    @endunless
                                    {{ \Morilog\Jalali\Jalalian::fromDateTime($notification->created_at)->format('j F Y — H:i') }}
                                </p>
                            </div>
                            <div class="notifi-desc">
                                <p class="font-14 mb-0">{{ $notification->message }}</p>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-bell-slash text-muted" style="font-size:3rem;"></i>
                        <h3 class="font-16 mt-3">صندوق اطلاع شما خالی است</h3>
                        <p class="text-muted font-14 mb-0">وقتی اطلاع جدیدی دریافت کنید، اینجا نمایش داده می‌شود.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($notifications->hasPages())
        <div class="my-paginate mt-4">
            {{ $notifications->links('front::components.paginate') }}
        </div>
    @endif
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush
