@extends('front::user.layouts.master')

@section('user-content')

    {{-- لیست تیکت ها — ریسکین‌شده با جدول قالب سانیار (ticket.html) --}}
    <div class="ui-boxs">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold">لیست تیکت‌ ها</h4>
                        <a href="{{ route('front.tickets.create') }}" class="btn btn-sm main-color-one-bg waves-effect waves-light">ثبت تیکت جدید <i class="bi bi-plus-circle text-white"></i></a>
                    </div>
                </div>
                <div class="ui-box-item-desc p-0">
                    <div class="responsive-table p-0">
                        <table class="table main-table rounded-0">
                            <thead class="text-bg-dark bg-opacity-75 text-center">
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
                            <tbody class="text-center">
                            @if($tickets->count())
                                @foreach ($tickets as $ticket)
                                    <tr>
                                        <td class="font-14">{{ $loop->iteration}}</td>
                                        <td class="font-14 main-color-one-color">{{ $ticket->id }}</td>
                                        <td class="font-14">{{ $ticket->subject }}</td>
                                        <td class="font-14">{{ jdate($ticket->created_at)->format('%d %B %Y') }}</td>
                                        <td class="font-14">{{ $ticket->priorityText() }}</td>
                                        <td class="font-14">
                                            @switch($ticket->status)
                                                @case('pending')
                                                    <span class="badge bg-secondary rounded-0">در انتظار پاسخ</span>
                                                    @break
                                                @case('answered')
                                                    <span class="badge bg-success rounded-0">پاسخ داده شده</span>
                                                    @break
                                                @case('open')
                                                    <span class="badge bg-info rounded-0">باز</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-danger rounded-0">بسته</span>
                                            @endswitch
                                        </td>
                                        <td class="font-14">
                                            <a href="{{ route('front.tickets.show', ['ticket' => $ticket]) }}"
                                               class="btn btn-sm border-0 main-color-three-bg waves-effect waves-light"> <i class="bi bi-eye me-1 text-white"></i> نمایش</a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">چیزی برای نمایش وجود ندارد!</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="my-paginate mt-4 pb-3">
                        <nav aria-label="Page navigation">
                            {{$tickets->links("pagination::bootstrap-4")}}
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
