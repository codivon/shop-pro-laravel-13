{{-- گفتگوی درخواست پشتیبانی فروشنده — سانیار فاز J
    قرارداد SellerTicketController@show :
    $ticket (subject, priority, status, created_at, updated_at, messages(seller_id, admin_id, message, files)) --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'درخواست پشتیبانی #' . $ticket->id, 'active' => 'tickets'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=3">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush

@section('seller-content')
    {{-- سربرگ + اطلاعات تیکت --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="py-3 border-bottom">
                <h5 class="fw-bold mb-1">
                    <i class="bi bi-chat-square-text main-color-one-color ms-1"></i>درخواست پشتیبانی #{{ $ticket->id }}
                </h5>
                <p class="font-13 text-muted mb-0">گفتگو با پشتیبانی فروشگاه دربارهٔ «{{ $ticket->subject }}»</p>
            </div>
            <div class="row g-3 py-3">
                <div class="col-md-3 col-6">
                    <div class="border rounded-3 p-2 h-100">
                        <span class="font-12 text-muted d-block">موضوع</span>
                        <strong class="font-14">{{ $ticket->subject }}</strong>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="border rounded-3 p-2 h-100">
                        <span class="font-12 text-muted d-block">تاریخ ایجاد</span>
                        <strong class="font-14">{{ jdate($ticket->created_at)->format('Y/m/d H:i') }}</strong>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="border rounded-3 p-2 h-100">
                        <span class="font-12 text-muted d-block">اولویت</span>
                        @if($ticket->priority == 'low')
                            <span class="badge rounded-pill success-label">کم</span>
                        @elseif($ticket->priority == 'medium')
                            <span class="badge rounded-pill warning-label">متوسط</span>
                        @else
                            <span class="badge rounded-pill danger-label">زیاد</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="border rounded-3 p-2 h-100">
                        <span class="font-12 text-muted d-block">وضعیت</span>
                        @if($ticket->status == 'pending')
                            <span class="badge rounded-pill warning-label">در انتظار پاسخ</span>
                        @elseif($ticket->status == 'answered')
                            <span class="badge rounded-pill success-label">پاسخ داده شده</span>
                        @elseif($ticket->status == 'open')
                            <span class="badge rounded-pill bg-secondary">باز</span>
                        @else
                            <span class="badge rounded-pill danger-label">بسته</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- گفتگو (حباب‌ها با کلاس‌های سراسری comment — الگوی صفحهٔ تیکت کاربر) --}}
    <div class="content-box">
        <div class="container-fluid">
            <div class="py-3 border-bottom">
                <h6 class="fw-bold mb-0 font-14"><i class="bi bi-chat-dots main-color-one-color ms-1"></i>گفتگو با پشتیبان</h6>
            </div>

            <div id="sfk-chat-scroll" class="border rounded-3 bg-light p-3 my-3" style="max-height:520px;overflow-y:auto;">
                @foreach($ticket->messages()->oldest()->get() as $message)
                    {{-- کلاس active = پیام خودِ فروشنده (سمت کهربایی)؛ بدون کلاس = پشتیبان --}}
                    <div class="comment shadow-none {{ $message->admin_id ? '' : 'active' }}">
                        <div class="comment-author-ava">
                            @if($message->admin_id)
                                <img src="{{ $message->admin ? $message->admin->imageUrl : asset('/back/app-assets/images/portrait/small/default.png') }}" alt="پشتیبان" loading="lazy">
                            @else
                                <img src="{{ $message->seller ? $message->seller->imageUrl : asset('/back/app-assets/images/portrait/small/default.png') }}" alt="{{ seller_info()->business_name ?? 'فروشنده' }}" loading="lazy">
                            @endif
                        </div>
                        <div class="comment-body">
                            <p class="comment-text">{{ $message->message }}</p>
                            @if($message->files->count())
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    @foreach($message->files as $file)
                                        <a target="_blank" rel="noopener" href="{{ asset($file->file) }}"
                                           class="btn btn-sm btn-light border rounded-3 font-12" aria-label="دانلود فایل پیوست {{ $loop->iteration }}">
                                            <i class="bi bi-file-earmark-arrow-down main-color-one-color ms-1"></i> فایل {{ $loop->iteration }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                            <div class="comment-footer">
                                <span class="comment-meta">{{ $message->admin_id ? 'پشتیبان' : (seller_info()->business_name ?? 'فروشنده') }}</span>
                                <div class="comment-date float-end">
                                    <span class="comment-meta">{{ \Morilog\Jalali\Jalalian::fromDateTime($message->created_at)->format('j F Y — H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($ticket->status == 'close')
                <div class="my-3">
                    <div class="alert alert-danger text-center rounded-3 font-14" role="alert">
                        <i class="bi bi-lock ms-1"></i>این درخواست بسته شده است. در صورت نیاز تیکت جدید ثبت کنید.
                    </div>
                </div>
            @else
                {{-- فرم پاسخ --}}
                <h6 class="font-14 fw-bold mb-3"><i class="bi bi-reply main-color-one-color ms-1"></i>ارسال پیام</h6>
                <form id="sfk-reply-form" method="POST"
                      action="{{ route('seller.tickets.update', ['ticket' => $ticket]) }}"
                      enctype="multipart/form-data"
                      data-redirect="{{ route('seller.tickets.show', ['ticket' => $ticket]) }}" novalidate>
                    @csrf
                    @method('put')
                    <div class="mb-3">
                        <label class="form-label font-14 fw-bold" for="sfk-reply-message">پیام <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-3" id="sfk-reply-message" rows="4"
                                  name="message" placeholder="پیام خود را وارد کنید..." required aria-required="true"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-14 fw-bold" for="sfk-reply-files">فایل (اختیاری)</label>
                        <input class="form-control rounded-3" type="file" id="sfk-reply-files" name="upload_files[]" multiple
                               accept=".png,.jpeg,.jpg,.zip" aria-label="فایل‌های پیوست اختیاری">
                        <p class="font-12 text-muted mt-1 mb-0">فرمت‌های مجاز: png، jpg، zip — هر فایل حداکثر ۲۰۰ مگابایت</p>
                    </div>
                    <div id="sfk-ticket-form-error" class="font-12 text-danger d-none mb-2" role="alert"></div>
                    <button type="submit" class="btn main-color-one-bg text-white rounded-3 font-14 px-5">
                        <i class="bi bi-send ms-1"></i>ثبت پاسخ
                    </button>
                </form>
            @endif
            <div class="pb-3"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-tickets.js') }}?v=1" data-saniar-tickets="show"></script>
@endpush
