@extends('front::user.layouts.master')

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{theme_asset('css/notifications.css')}}">
@endpush

@section('user-content')
    <div id="messages-page" class="col-xl-9 col-lg-8 col-md-8 col-sm-12" style="margin-top: 60px">
        <div class="notifications-page">

            <div class="notif-page-header">
                <div class="notif-page-title">
                    <div class="notif-page-title-icon">
                        <i class="mdi mdi-bell-ring"></i>
                    </div>
                    <div>
                        <h2>{{ trans('front::messages.profile.all-notifications') }}</h2>
                        <small>آخرین پیام‌ها و اعلان‌های شما در یک نگاه</small>
                    </div>
                </div>

                <div class="notif-page-stats">
                    <div class="notif-stat">
                        <div class="notif-stat-num">{{ $notifications->total() ?? $notifications->count() }}</div>
                        <div class="notif-stat-label">کل اعلان‌ها</div>
                    </div>
                    <div class="notif-stat">
                        <div class="notif-stat-num">
                            {{ Auth::user()->unreadNotifications()->count() }}
                        </div>
                        <div class="notif-stat-label">خوانده‌نشده</div>
                    </div>
                </div>
            </div>

            <div class="content-overlay"></div>
            <div class="header-navbar-shadow"></div>

            <div class="content-body">
                <section id="statistics-card">
                    <div class="row">
                        <div class="col-lg-12 col-12 mb-4">
                            <div class="notif-list-grid">
                                @if($notifications->count())
                                    @foreach($notifications as $notification)
                                        @php
                                            $isRead = ! is_null($notification->userRecipients()->first()->read_at);
                                        @endphp

                                            <div class="notif-item {{ $isRead ? 'read' : 'unread' }}">
                                                @if(! $isRead)
                                                    <span class="notif-item-badge"></span>
                                                @endif

                                                <div class="notif-item-icon">
                                                    <i class="mdi mdi-comment-text-outline"></i>
                                                </div>
                                                <div class="notif-item-body">
                                                    <div class="notif-item-header">
                                                        <h5 class="notif-item-title">
                                                            {{ $notification->title }}
                                                        </h5>
                                                        <span class="notif-item-time">
                                                            <i class="mdi mdi-clock-outline"></i>
                                                            {{ jdate($notification->created_at)->ago() }}
                                                        </span>
                                                    </div>
                                                    <p class="notif-item-text">
                                                        {{ $notification->message }}
                                                    </p>
                                                </div>
                                            </div>
                                    @endforeach
                                @else
                                    <div class="notif-empty-state">
                                        <div class="notif-empty-icon-wrap">
                                            <i class="mdi mdi-bell-off-outline"></i>
                                        </div>
                                        <h5 class="notif-empty-title">صندوق اعلان شما خالی است</h5>
                                        <p class="notif-empty-text">
                                            وقتی اعلان جدیدی دریافت کنید، اینجا نمایش داده می‌شود
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                {{ $notifications->links('front::components.paginate') }}
            </div>
        </div>
    </div>
@endsection

@include('back.partials.plugins', ['plugins' => ['persian-datepicker', 'jquery.validate']])
