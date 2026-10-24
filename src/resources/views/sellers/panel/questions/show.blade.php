{{-- پارشال AJAX جزئیات پرسش — سانیار فاز J (بدون لایوت — HTML رندرشده در #sfq-question-detail تزریق می‌شود)
    قرارداد QuestionSellerController@show : $question (body, status, comment_id, user/admin/seller, commentable(محصول)) --}}
<div class="saniar-detail">
    <form id="sfq-question-form" action="{{ route('seller.questions.update', ['question' => $question]) }}" method="POST" novalidate>
        @csrf
        @method('put')

        {{-- ردیف محصول + تاریخ --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="min-w-0">
                <span class="font-12 text-muted d-block mb-1">محصول:</span>
                <a href="{{ $question->commentable->link() }}" target="_blank" rel="noopener"
                   class="font-14 fw-bold text-decoration-none text-dark">
                    {{ $question->commentable->title }}
                    <i class="bi bi-box-arrow-up-right font-12 main-color-one-color"></i>
                </a>
            </div>
            <div class="text-md-end">
                <span class="font-12 text-muted d-block mb-1">تاریخ ارسال</span>
                <strong class="font-14">{{ jdate($question->created_at)->format('Y/m/d H:i') }}</strong>
            </div>
        </div>

        {{-- نویسنده + نوع + وضعیت --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="font-13 text-muted">پرسش‌گر:</span>
            <strong class="font-13">
                @if($question->user)
                    کاربر: {{ $question->user->fullname ?? 'کاربر' }}
                @elseif($question->admin)
                    پشتیبان سایت
                @elseif($question->seller)
                    فروشنده: {{ $question->seller->business_name ?? 'فروشنده' }}
                @else
                    کاربر مهمان
                @endif
            </strong>
            @if($question->comment_id)
                <span class="badge rounded-pill success-label font-12">پاسخ</span>
            @else
                <span class="badge rounded-pill warning-label font-12">پرسش</span>
            @endif
            @if($question->status == 'pending')
                <span class="badge rounded-pill warning-label font-12">منتظر تایید</span>
            @elseif($question->status == 'noanswer')
                <span class="badge rounded-pill warning-label font-12">در انتظار پاسخ</span>
            @elseif($question->status == 'accepted')
                <span class="badge rounded-pill success-label font-12">تایید شده</span>
            @elseif($question->status == 'unconfirmed')
                <span class="badge rounded-pill danger-label font-12">رد شده</span>
            @endif
        </div>

        {{-- متن پرسش/پاسخ --}}
        <div id="sfq-body" class="saniar-detail-body bg-light rounded-3 p-3 mb-3">
            <p class="font-14 mb-0" style="white-space: pre-line;">{{ $question->body }}</p>
            @if($question->comment_id && $question->seller_id == sellerID())
                <button type="button" class="sfq-edit-toggle btn btn-sm btn-outline-primary rounded-3 font-12 mt-2">
                    <i class="bi bi-pencil-square ms-1"></i>ویرایش پاسخ
                </button>
            @endif
        </div>

        @if($question->comment_id && $question->seller_id == sellerID())
            {{-- پاسخ خودِ فروشنده: ویرایشگر (پیش از submit نمایان می‌شود) --}}
            <div id="sfq-edit-body" class="mb-3 d-none">
                <label class="form-label font-13 fw-bold" for="sfq-edit-textarea">متن پاسخ <span class="text-danger">*</span></label>
                <textarea id="sfq-edit-textarea" name="body" class="form-control font-13 rounded-3" rows="4">{{ $question->body }}</textarea>
            </div>
        @elseif(!$question->comment_id)
            {{-- پرسش: متن پرسش ثابت می‌ماند (hidden) و پاسخ جدید ثبت می‌شود --}}
            <input type="hidden" name="body" value="{{ $question->body }}">
            <div class="mb-3">
                <label class="form-label font-13 fw-bold" for="sfq-replay-textarea">پاسخ شما</label>
                <textarea id="sfq-replay-textarea" name="replay" class="form-control font-13 rounded-3" rows="4"
                          placeholder="پاسخ خود را بنویسید؛ پس از بررسی مدیریت روی صفحه محصول نمایش داده می‌شود."></textarea>
            </div>
        @else
            {{-- پاسخ کاربرِ دیگر: فقط نمایش --}}
            <input type="hidden" name="body" value="{{ $question->body }}">
        @endif

        <div id="sfq-question-error" class="font-12 text-danger d-none mb-2" role="alert"></div>

        {{-- دکمه‌ها --}}
        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
            <button type="button" class="btn btn-outline-secondary rounded-3 font-13" data-bs-dismiss="modal">بستن</button>
            @if(!$question->comment_id || $question->seller_id == sellerID())
                <button type="button" id="sfq-question-submit" class="btn main-color-one-bg text-white rounded-3 font-13">
                    <i class="bi bi-check-lg ms-1"></i>{{ $question->comment_id ? 'ذخیره تغییرات' : 'ثبت پاسخ' }}
                </button>
            @endif
        </div>
    </form>
</div>
