@extends('front::user.layouts.master')

@section('user-content')

    {{-- مشاهده تیکت — ریسکین‌شده با کامپوننت گفتگوی قالب سانیار (ticket-message.html) --}}
    <div class="content-box">
        <div class="container-fluid">
            <div class="title-panel">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0">تیکت شماره {{ $ticket->id }}</h5>
                    <div class="ticket-status d-flex align-items-center">
                        <span class="badge rounded-0 font-14 me-3 {{ $ticket->status == 'answered' ? 'bg-success' : ($ticket->status == 'close' ? 'bg-danger' : 'bg-secondary') }}">{{ $ticket->statusText() }}</span>
                        <span class="text-muted font-16 fw-lighter mb-0 me-3">#{{ $ticket->id }}</span>
                        <div class="ticket-created-at me-3">
                            <i class="bi bi-clock me-1"></i>
                            <span>تاریخ ایجاد {{ jdate($ticket->created_at)->format('%d %B %Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row py-3 border-bottom mb-3">
                <div class="col-md-4 col-12 mb-2">
                    <span class="title text-muted">موضوع تیکت:</span>
                    <span class="value fw-bold">{{ $ticket->subject }}</span>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <span class="title text-muted">تاریخ ایجاد:</span>
                    <span class="value fw-bold">{{ jdate($ticket->created_at)->format('%d %B %Y') }}</span>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <span class="title text-muted">اولویت:</span>
                    <span class="value fw-bold">{{ $ticket->priorityText() }}</span>
                </div>
                <div class="col-md-3 col-12 mb-2">
                    <span class="title text-muted">وضعیت:</span>
                    <span class="value fw-bold">{{ $ticket->statusText() }}</span>
                </div>
            </div>

            <div class="ticket-message">

                {{-- Messages--}}
                @foreach ($ticket->messages()->oldest()->get() as $message)

                    @if ($loop->first)
                        <div class="divider">
                            <div class="divider-text">{{ jdate($message->created_at)->ago() }}</div>
                        </div>
                    @endif

                    @if ($message->admin_id)
                        @include('front::user.tickets.partials.message', ['own' => true])
                    @else
                        @include('front::user.tickets.partials.message', ['own' => false])
                    @endif

                @endforeach

            </div>

            {{-- Reply Form--}}
            <h5 class="mt-4 mb-3">ثبت پیام جدید</h5>
            <form class="form" id="ticket-update-form" data-redirect="{{ route('front.tickets.show', ['ticket' => $ticket]) }}" action="{{ route('front.tickets.update', ['ticket' => $ticket]) }}" method="post" enctype="multipart/form-data">
                @csrf
                @method('put')

                <div class="form-body">
                    <div class="row">

                        <div class="col-12">
                            <div class="form-group">
                                <label for="message">پیام</label>
                                <textarea id="message" class="form-control" rows="4" name="message"></textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>فایل های پیوست</label>
                                <input type="file" class="form-control" name="upload_files[]"  multiple>
                            </div>
                        </div>

                        <div class="col-12 pb-3">
                            <div class="parent-btn">
                                <button id="submit-btn" class="btn border-0 main-color-one-bg waves-effect waves-light py-2 px-5">
                                    ثبت پیام
                                    <i class="bi bi-check-lg text-white"></i>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>

    <script src="{{ theme_asset('js/pages/tickets/show.js') }}"></script>
@endpush
