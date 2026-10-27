@extends('sellers-old.panel.layouts.master')
@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/vendors/css/file-uploaders/dropzone.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/plugins/file-uploaders/dropzone.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/assets/css/pages/tickets/show.css') }}">
@endpush
@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">مشاهده درخواست ثبت شده</span>
                <span class="c-content-page__header-desc">اینجا می‌توانید درخواستی را که ثبت کردید ببینید.</span>
            </div>
        </div>
    </div>
    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">
        <div class="row dashboard-steps-3">
            <div class="col-12 dashboard-steps-3-item ">
                <div class="c-card min-height-auto">
                    <div class="c-card__header d-flex pt-1 pb-1">
                        <h2 class="c-card__title line-height-40">مشاهده درخواست شماره {{$ticket->id}}</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">

                            <div class="col-12">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <dt class="col-md-3">موضوع تیکت:</dt>
                                            <dd class="col-md-6">
                                                {{$ticket->subject}}
                                            </dd>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <dt class="col-md-3">تاریخ ایجاد :</dt>
                                            <dd class="col-md-6">{{ jdate($ticket->created_at) }}</dd>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <dt class="col-md-3">اولویت :</dt>
                                            <dd class="col-md-6">{{ $ticket->priorityText() }}</dd>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <dt class="col-md-3">وضعیت :</dt>
                                            <dd class="col-md-6">
                                                @if($ticket->status== 'pending')در انتظار پاسخ
                                                @elseif($ticket->status== 'answered')پاسخ داده شده
                                                @elseif($ticket->status== 'open')باز
                                                @elseif($ticket->status== 'close')بسته
                                                @endif


                                            </dd>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                    <div class="content-body mt-2">
                        <div class="row">
                            <div class="col-md-12 chat-application">
                                <div class="content-right">
                                    <div class="content-header row">
                                    </div>
                                    <div class="content-body">
                                        <div class="chat-overlay"></div>
                                        <section class="chat-app-window">

                                            <div class="active-chat ">

                                                <div class="user-chats">
                                                    <div class="chats">

                                                        @foreach ($ticket->messages()->oldest()->get() as $message)

                                                            @if ($loop->first)
                                                                <div class="divider">
                                                                    <div class="divider-text">{{ jdate($message->created_at)->ago() }}</div>
                                                                </div>
                                                            @endif

                                                            @if ($message->admin_id)
                                                                @include('back.tickets.partials.message', ['own' => true])
                                                            @else
                                                                @include('back.tickets.partials.message', ['own' => false])
                                                            @endif

                                                        @endforeach

                                                    </div>
                                                </div>

                                            </div>
                                        </section>

                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 mt-2">
                                <section class="card">

                                    <div id="main-card" class="card-content">
                                        <div class="card-body">
                                            <div class="col-12 col-md-10 offset-md-1">
                                                <form class="form" id="ticket-update-form" data-redirect="{{ route('seller.tickets.show', ['ticket' => $ticket]) }}" action="{{ route('seller.tickets.update', ['ticket' => $ticket]) }}" method="post" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('put')
                                                    <div class="form-body col-md-12">

                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="form-group">
                                                                    <label>پیام</label>
                                                                    <textarea name="message" class="form-control valid" rows="3"></textarea>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <fieldset class="form-group">
                                                                    <label>فایل های پیوست</label>
                                                                    <div class="custom-file">
                                                                        <input type="file" accept="image/*" name="upload_files[]" class="custom-file-input valid" aria-invalid="false" multiple>
                                                                        <label class="custom-file-label" for="image"></label>
                                                                    </div>
                                                                </fieldset>
                                                            </div>

                                                        </div>

                                                        <div class="row justify-content-center mt-1">
                                                            <div class="col-md-3">
                                                                <div class="form-checkout-valid-row">
                                                                    <div class="parent-btn">
                                                                        <button id="submit-btn" class="c-wallet__header-card-btn--deposit js-trigger-wallet-modal w-100 border-0">ثبت پاسخ
                                                                            <i class="fa fa-check sign-in"></i>
                                                                        </button>

                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>

                    </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('back/app-assets/vendors/js/extensions/dropzone.min.js') }}"></script>
    <script src="{{ asset('back/app-assets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('back/app-assets/plugins/jquery-validation/localization/messages_fa.min.js') }}"></script>


    <script src="{{ theme_asset('js/pages/sellers/tickets/show.js') }}"></script>
@endpush
