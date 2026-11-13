@extends('front::user.layouts.master')

@section('user-content')

    {{-- اطلاعیه ها — ریسکین‌شده با کامپوننت اطلاعیه قالب سانیار (notification.html) --}}
    <div class="ui-boxs">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h4 class="fw-bold">{{ trans('front::messages.profile.all-notifications') }}</h4>

                        <div class="d-flex align-items-center">
                            <span class="badge bg-light text-dark rounded-pill me-2">
                                کل اعلان‌ها: {{ $notifications->total() ?? $notifications->count() }}
                            </span>
                            <span class="badge rounded-pill main-color-one-bg">
                                خوانده‌نشده: {{ Auth::user()->unreadNotifications()->count() }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="ui-box-item-desc">
                    @if($notifications->count())
                        @foreach($notifications as $notification)
                            @php
                                $isRead = ! is_null($notification->userRecipients()->first()->read_at);
                            @endphp

                            <div class="alert {{ $isRead ? 'alert-light' : 'alert-warning' }}" role="alert">
                                <div class="notifi-title">
                                    <h6 class="alert-link">
                                        @if(! $isRead)
                                            <span class="badge rounded-pill main-color-one-bg ms-1">جدید</span>
                                        @endif
                                        {{ $notification->title }}
                                    </h6>
                                    <p class="text-muted">
                                        <i class="bi bi-clock"></i>
                                        {{ jdate($notification->created_at)->ago() }}
                                    </p>
                                </div>
                                <div class="notifi-desc">
                                    <p>{{ $notification->message }}</p>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-light text-center" role="alert">
                            <i class="bi bi-bell-slash font-25 d-block mb-2 text-muted"></i>
                            <h6>صندوق اعلان شما خالی است</h6>
                            <p class="mb-0 text-muted">وقتی اعلان جدیدی دریافت کنید، اینجا نمایش داده می‌شود</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="my-paginate mt-4">
        <nav aria-label="Page navigation">
            {{ $notifications->links('front::components.paginate') }}
        </nav>
    </div>

@endsection

@include('back.partials.plugins', ['plugins' => ['persian-datepicker', 'jquery.validate']])
