@extends('front::user.layouts.master')

@section('user-content')
    <!-- Start Content -->
    <div class="headline-profile">
        <span>مشاهده تیکت شماره {{ $ticket->id }}</span>
    </div>
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 tickets">
        <div class="row">

            <div class="col-12 mb-2">
                <div class="dt-sl dt-sn">
                    <div class="row table-draught px-3 border-0">
                        <div class="col-md-3 col-sm-12">
                            <span class="title">موضوع تیکت:</span>
                            <span class="value">{{ $ticket->subject }}</span>
                        </div>
                        <div class="col-md-3 col-sm-12">
                            <span class="title">تاریخ ایجاد:</span>
                            <span class="value">{{ jdate($ticket->created_at)->format('%d %B %Y') }}</span>
                        </div>
                        <div class="col-md-3 col-sm-12">
                            <span class="title">اولویت:</span>
                            <span class="value">{{ $ticket->priorityText() }}</span>
                        </div>
                        <div class="col-md-3 col-sm-12">
                            <span class="title">وضعیت:</span>
                            <span class="value">{{ $ticket->statusText() }}</span>
                        </div>
                    </div>
                </div>
            </div>


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
                                                @include('front::user.tickets.partials.message', ['own' => true])
                                            @else
                                                @include('front::user.tickets.partials.message', ['own' => false])
                                            @endif

                                        @endforeach

                                    </div>
                                </div>

                            </div>
                        </section>

                    </div>
                </div>
            </div>


            <div class="headline-profile">
                <span>ثبت پیام جدید</span>
            </div>
        </div>
        <div class="dt-sl dt-sn pt-4 mb-5">
            <div class="col-12 col-md-12 offset-md-1">
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
                        </div>

                        <div class=" form-legal-row-submit pb-3">
                            <div class="parent-btn col-md-3 display-inline-block">
                                <button id="submit-btn" class="dk-btn dk-btn-info w-100">
                                    ثبت پیام
                                    <i class="fa fa-check sign-in"></i>
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>


    </div>
    <!-- End Content -->
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>

    <script src="{{ theme_asset('js/pages/tickets/show.js') }}"></script>
@endpush
