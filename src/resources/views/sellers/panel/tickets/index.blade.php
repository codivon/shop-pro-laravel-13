{{-- درخواست‌های پشتیبانی فروشنده — سانیار فاز J
    قرارداد SellerTicketController@index :
    $tickets (paginate 20 — id, subject, priority, status, created_at) --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'درخواست‌های پشتیبانی', 'active' => 'tickets'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=3">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush

@section('seller-content')
    {{-- سربرگ صفحه --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-life-preserver main-color-one-color ms-1"></i>درخواست‌های پشتیبانی</h5>
                    <p class="font-13 text-muted mb-0">سؤالات و مشکلات خود را با پشتیبانی فروشگاه در میان بگذارید.</p>
                </div>
                <a href="{{ route('seller.tickets.create') }}" class="btn main-color-one-bg text-white rounded-3 font-14">
                    <i class="bi bi-plus-circle ms-1"></i>ثبت درخواست جدید
                </a>
            </div>
        </div>
    </div>

    {{-- لیست تیکت‌ها --}}
    <div class="content-box">
        <div class="container-fluid">
            <div class="py-3 border-bottom">
                <h6 class="fw-bold mb-0 font-14"><i class="bi bi-list-ul main-color-one-color ms-1"></i>لیست درخواست‌ها</h6>
            </div>
            @if($tickets->count())
                <div class="table-responsive py-2">
                    <table class="table align-middle site-tabel mb-2">
                        <thead class="text-bg-dark bg-opacity-75 text-center">
                        <tr>
                            <th scope="col" style="width:60px;">ردیف</th>
                            <th scope="col">شماره درخواست</th>
                            <th scope="col">موضوع</th>
                            <th scope="col" class="text-nowrap">تاریخ ثبت</th>
                            <th scope="col">اولویت</th>
                            <th scope="col">وضعیت</th>
                            <th scope="col">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="text-center">
                        @foreach($tickets as $ticket)
                            <tr>
                                <td class="font-13">{{ ($tickets->currentPage() - 1) * $tickets->perPage() + $loop->iteration }}</td>
                                <td class="font-13 text-info fw-bold">#{{ $ticket->id }}</td>
                                <td class="font-13 text-start">{{ $ticket->subject }}</td>
                                <td class="font-13 text-nowrap">{{ jdate($ticket->created_at)->format('Y/m/d H:i') }}</td>
                                <td class="font-13">
                                    @if($ticket->priority == 'low')
                                        <span class="badge rounded-pill success-label">کم</span>
                                    @elseif($ticket->priority == 'medium')
                                        <span class="badge rounded-pill warning-label">متوسط</span>
                                    @else
                                        <span class="badge rounded-pill danger-label">زیاد</span>
                                    @endif
                                </td>
                                <td class="font-13">
                                    @if($ticket->status == 'pending')
                                        <span class="badge rounded-pill warning-label">در انتظار پاسخ</span>
                                    @elseif($ticket->status == 'answered')
                                        <span class="badge rounded-pill success-label">پاسخ داده شده</span>
                                    @elseif($ticket->status == 'open')
                                        <span class="badge rounded-pill bg-secondary">باز</span>
                                    @else
                                        <span class="badge rounded-pill danger-label">بسته</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('seller.tickets.show', ['ticket' => $ticket]) }}"
                                       class="btn btn-sm btn-outline-primary rounded-3 font-12" aria-label="نمایش جزئیات درخواست {{ $ticket->id }}">
                                        <i class="bi bi-eye"></i> نمایش جزئیات
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="py-3">
                    {{ $tickets->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-life-preserver text-muted d-block mb-2" style="font-size:2.6rem;" aria-hidden="true"></i>
                    <h6 class="font-16 mt-3 mb-2">تیکتی ثبت نشده است</h6>
                    <p class="text-muted font-14 mb-3">سؤال یا مشکلی دارید؟ برای پشتیبانی فروشگاه درخواست ثبت کنید.</p>
                    <a href="{{ route('seller.tickets.create') }}" class="btn main-color-one-bg text-white rounded-3 font-14 px-4">
                        <i class="bi bi-plus-circle ms-1"></i>ثبت اولین درخواست
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-tickets.js') }}?v=1" data-saniar-tickets="index"></script>
@endpush
