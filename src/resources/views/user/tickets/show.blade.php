{{-- گفتگوی تیکت — TicketController@show : $ticket (subject, priorityText(), statusText(), status, created_at, updated_at, messages(admin_id, message, files)), $active --}}
@extends('front::user.layouts.master', ['title' => 'تیکت #' . $ticket->id])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="title-panel">
                <div class="d-flex align-items-baseline flex-wrap gap-3">
                    <div class="ticket-status d-flex align-items-center gap-2">
                        @if($ticket->status == 'pending')
                            <span class="bg-secondary badge rounded-0 font-14">در انتظار پاسخ</span>
                        @elseif($ticket->status == 'answered')
                            <span class="bg-success badge rounded-0 font-14">پاسخ داده شده</span>
                        @else
                            <span class="bg-danger badge rounded-0 font-14">بسته شده</span>
                        @endif
                        <span class="text-muted font-14 fw-lighter mb-0">#{{ $ticket->id }}</span>
                    </div>
                    <div class="ticket-created-at font-13 text-muted">
                        <i class="bi bi-chat-square-text me-1 main-color-one-color"></i>
                        <span>موضوع: <strong class="text-dark">{{ $ticket->subject }}</strong></span>
                    </div>
                    <div class="ticket-created-at font-13 text-muted">
                        <i class="bi bi-clock me-1 main-color-one-color"></i>
                        <span>ایجاد: {{ \Morilog\Jalali\Jalalian::fromDateTime($ticket->created_at)->format('j F Y — H:i') }}</span>
                    </div>
                    <div class="ticket-created-at font-13 text-muted">
                        <i class="bi bi-arrow-left main-color-one-color me-1"></i>
                        <span>بروزرسانی: {{ \Morilog\Jalali\Jalalian::fromDateTime($ticket->updated_at)->format('j F Y — H:i') }}</span>
                    </div>
                    <div class="ticket-created-at font-13 text-muted">
                        <span>اولویت: <strong class="text-dark">{{ $ticket->priorityText() }}</strong></span>
                    </div>
                </div>
            </div>

            <div class="ticket-message">

                {{-- پیام‌ها (کلاس active = کاربر؛ بدون کلاس = پشتیبان — مطابق ticket-message.html) --}}
                @foreach ($ticket->messages()->oldest()->get() as $message)
                    <div class="comment shadow-none {{ $message->admin_id ? '' : 'active' }}">
                        <div class="comment-author-ava">
                            @if($message->admin_id)
                                <img src="{{ $message->admin->imageUrl }}" alt="پشتیبان" loading="lazy">
                            @else
                                <img src="{{ $message->user->imageUrl }}" alt="{{ $message->user->fullname ?: 'کاربر' }}" loading="lazy">
                            @endif
                        </div>
                        <div class="comment-body">
                            <p class="comment-text">{{ $message->message }}</p>
                            @if($message->files->count())
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    @foreach ($message->files as $file)
                                        <a target="_blank" rel="noopener" href="{{ asset($file->file) }}"
                                           class="btn btn-sm btn-light border rounded-3 font-12">
                                            <i class="bi bi-file-earmark-arrow-down main-color-one-color me-1"></i> فایل {{ $loop->iteration }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                            <div class="comment-footer">
                                <span class="comment-meta">{{ $message->admin_id ? 'پشتیبان' : ($message->user->fullname ?: 'کاربر') }}</span>
                                <div class="comment-date float-end">
                                    <span class="comment-meta">{{ \Morilog\Jalali\Jalalian::fromDateTime($message->created_at)->format('j F Y — H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if($ticket->status == 'close')
                    <div class="my-4">
                        <div class="alert alert-danger text-center rounded-3 font-14">
                            <i class="bi bi-lock me-1"></i> تیکت بسته شده است و امکان ارسال پاسخ جدید وجود ندارد
                        </div>
                    </div>
                @else
                    {{-- فرم پاسخ --}}
                    <h3 class="font-16 mb-3 mt-4"><i class="bi bi-reply main-color-one-color me-1"></i>ارسال پیام</h3>
                    <form id="ticket-update-form" method="post"
                          action="{{ route('front.tickets.update', ['ticket' => $ticket]) }}"
                          enctype="multipart/form-data"
                          data-redirect="{{ route('front.tickets.show', ['ticket' => $ticket]) }}" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-3">
                            <label class="form-label font-14 fw-bold" for="reply-message">پیام <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-rounded rounded-3" id="reply-message" rows="6"
                                      name="message" placeholder="پیام خود را وارد کنید..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-14 fw-bold" for="reply-files">فایل (اختیاری)</label>
                            <input class="form-control rounded-3" type="file" id="reply-files" name="upload_files[]" multiple
                                   accept=".png,.jpeg,.jpg,.zip">
                        </div>
                        <div id="ticket-form-error" class="font-12 text-danger d-none mb-2" role="alert"></div>
                        <button class="btn border-0 main-color-one-bg text-white rounded-3 px-5" type="submit">
                            <i class="bi bi-send me-1"></i> ارسال پیام
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush
@push('scripts')
    <script src="{{ theme_asset('js/pages/user/panel.js') }}?v=1"></script>
@endpush
