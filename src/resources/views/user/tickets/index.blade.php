{{-- لیست تیکت‌ها — TicketController@index : $tickets (id, subject, priorityText(), statusText(), created_at, status), $active --}}
@extends('front::user.layouts.master', ['title' => 'تیکت‌های پشتیبانی'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="title-panel">
            <h2 class="font-16 mb-0"><i class="bi bi-life-preserver main-color-one-color me-1"></i>لیست تیکت ها</h2>
        </div>
        <a href="{{ route('front.tickets.create') }}"
           class="btn btn-sm border-0 main-color-one-bg text-white rounded-3 px-4 waves-effect waves-light">
            <i class="bi bi-plus-circle me-1"></i> ثبت تیکت جدید
        </a>
    </div>

    <div class="content-box">
        <div class="container-fluid">
            <div class="table-responsive-lg table-custom">
                <table class="table table-bordered shadow-none mb-0">
                    <thead>
                    <tr>
                        <th class="align-middle text-center"><h3 class="font-14 text-muted mb-0">شناسه</h3></th>
                        <th class="align-middle text-center"><h3 class="font-14 text-muted mb-0">عنوان</h3></th>
                        <th class="align-middle text-center"><h3 class="font-14 text-muted mb-0">اولویت</h3></th>
                        <th class="align-middle text-center"><h3 class="font-14 text-muted mb-0">تاریخ ثبت</h3></th>
                        <th class="align-middle text-center"><h3 class="font-14 text-muted mb-0">نمایش</h3></th>
                    </tr>
                    </thead>
                    <tbody>
                    @if($tickets->count())
                        @foreach ($tickets as $ticket)
                            <tr>
                                <td class="align-middle text-center"><p class="mt-2 font-14 mb-0">{{ $ticket->id }}</p></td>
                                <td class="align-middle text-center">
                                    <p class="mt-2 font-14 mb-0">
                                        {{ $ticket->subject }}
                                        @if($ticket->status == 'pending')
                                            <span class="badge bg-secondary rounded-0 ms-2">در انتظار پاسخ</span>
                                        @elseif($ticket->status == 'answered')
                                            <span class="badge bg-success rounded-0 ms-2">پاسخ داده شده</span>
                                        @else
                                            <span class="badge bg-danger rounded-0 ms-2">بسته شده</span>
                                        @endif
                                    </p>
                                </td>
                                <td class="align-middle text-center"><p class="mt-2 font-14 mb-0">{{ $ticket->priorityText() }}</p></td>
                                <td class="align-middle text-center">
                                    <p class="mt-2 font-14 mb-0">{{ \Morilog\Jalali\Jalalian::fromDateTime($ticket->created_at)->format('j F Y') }}</p>
                                </td>
                                <td class="align-middle text-center">
                                    <a href="{{ route('front.tickets.show', ['ticket' => $ticket]) }}"
                                       class="btn main-color-three-bg text-white shadow-none btn-sm rounded-3">
                                        <i class="bi bi-eye me-1"></i> نمایش
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-life-preserver text-muted" style="font-size:2.6rem;"></i>
                                <h3 class="font-16 mt-3 mb-2">تیکتی ثبت نشده است</h3>
                                <p class="text-muted font-14">سؤال یا مشکلی دارید؟ برای ما تیکت ثبت کنید.</p>
                                <a href="{{ route('front.tickets.create') }}" class="btn border-0 main-color-one-bg text-white rounded-3 px-4">
                                    <i class="bi bi-plus-circle me-1"></i> ثبت اولین تیکت
                                </a>
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($tickets->hasPages())
        <div class="my-paginate mt-4">
            {{ $tickets->links('front::components.paginate') }}
        </div>
    @endif
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush
