@extends('front::user.layouts.master')

@section('user-content')

    <div class="headline-profile page-profile-order">
        <span>لیست تیکت‌ ها</span>
        <a href="{{ route('front.tickets.create') }}" class="add-address-link float-left cursor-pointer " >ثبت تیکت جدید</a>

    </div>
    <div class="profile-stats page-profile-order">
        <div class="table-orders">
            <table class="table">
                <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>شماره تیکت</th>
                    <th>موضوع</th>
                    <th>تاریخ ثبت تیکت</th>
                    <th>اولویت</th>
                    <th>وضعیت</th>
                    <th>جزییات</th>
                </tr>
                </thead>
                <tbody>
                @if($tickets->count())
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td>{{ $loop->iteration}}</td>
                            <td class="text-info">{{ $ticket->id }}</td>
                            <td>{{ $ticket->subject }}</td>
                            <td>{{ jdate($ticket->created_at)->format('%d %B %Y') }}</td>
                            <td>{{ $ticket->priorityText() }}</td>
                            <td>
                                {{ $ticket->statusText() }}
                            </td>
                            <td class="details-link">
                                <a href="{{ route('front.tickets.show', ['ticket' => $ticket]) }}">
                                    <i class="mdi mdi-chevron-left"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7">چیزی برای نمایش وجود ندارد!</td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
    <div class="page-profile headline-profile-favorites">
        <div class="page-navigation">
            <div class="page-navigation-title">لیست تیکت‌ ها</div>
            <a href="{{ route('front.tickets.create') }}" class="add-address-link float-left cursor-pointer " >ثبت تیکت جدید</a>

        </div>
        <div class="profile-orders">
            @if(count($tickets))
                @foreach ($tickets as $ticket)
                    <div class="collapse">
                        <div class="profile-orders-item">
                            <div class="profile-orders-header">
                                <a href="{{ route('front.tickets.show', ['ticket' => $ticket]) }}" class="profile-orders-header-details">
                                    <div class="profile-orders-header-summary">
                                        <div class="profile-orders-header-row">
                                            <span class="profile-orders-header-id">{{ $loop->iteration}}</span>
                                            <span class="profile-orders-header-state">
{{ $ticket->statusText() }}
                                    </span>
                                        </div>
                                    </div>
                                </a>
                                <hr class="ui-separator">
                                <div class="profile-orders-header-data">
                                    <div class="profile-info-row">
                                        <div class="profile-info-label">شماره تیکت</div>
                                        <div class="profile-info-value">{{ $ticket->id }}</div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-label">موضوع</div>
                                        <div class="profile-info-value">{{ $ticket->subject }}</div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-label">اولویت</div>
                                        <div class="profile-info-value">{{ $ticket->priorityText() }}</div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-label">تاریخ ثبت تیکت</div>
                                        <div class="profile-info-value">{{ jdate($ticket->created_at)->format('%d %B %Y') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="collapse">
                    <div class="profile-orders-item text-center">
                        چیزی برای نمایش وجود ندارد!
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="pager pager-back-none">
        {{$tickets->links("pagination::bootstrap-4")}}
    </div>


@endsection
