{{-- پرسش و پاسخ محصولات فروشنده — سانیار فاز J
    قرارداد QuestionSellerController@index :
    $questions (paginate 20 روی کامنت‌های محصولاتِ فروشنده — user/admin/seller, body, status,
    comment_id, commentable(محصول)) + فیلترهای GET: status, ordering --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'پرسش و پاسخ', 'active' => 'questions'])

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
                    <h5 class="fw-bold mb-1"><i class="bi bi-patch-question main-color-one-color ms-1"></i>پرسش و پاسخ</h5>
                    <p class="font-13 text-muted mb-0">پرسش‌های خریداران درباره محصولات شما؛ با پاسخ سریع، اعتماد و فروش را افزایش دهید.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- فیلترها --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <form id="sfq-filter-form" class="row g-2 py-3" method="GET" action="{{ route('seller.questions.index') }}">
                <div class="col-md-4 col-6">
                    <label class="form-label font-13 fw-bold" for="sfq-status">وضعیت پرسش</label>
                    <select class="form-select font-13" id="sfq-status" name="status">
                        <option value="all" {{ request('status') == 'all' || !request('status') ? 'selected' : '' }}>همه</option>
                        <option value="noanswer" {{ request('status') == 'noanswer' ? 'selected' : '' }}>پاسخ داده نشده</option>
                        <option value="unconfirmed" {{ request('status') == 'unconfirmed' ? 'selected' : '' }}>رد شده</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>در انتظار بررسی</option>
                        <option value="answer_accepted" {{ request('status') == 'answer_accepted' ? 'selected' : '' }}>پاسخ تایید شده</option>
                    </select>
                </div>
                <div class="col-md-4 col-6">
                    <label class="form-label font-13 fw-bold" for="sfq-ordering">مرتب‌سازی</label>
                    <select class="form-select font-13" id="sfq-ordering" name="ordering">
                        <option value="latest" {{ request('ordering') == 'oldest' ? '' : 'selected' }}>جدیدترین</option>
                        <option value="oldest" {{ request('ordering') == 'oldest' ? 'selected' : '' }}>قدیمی‌ترین</option>
                    </select>
                </div>
                <div class="col-md-4 col-12 d-flex align-items-end gap-2">
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2 font-12 ms-auto" aria-live="polite">
                        <i class="bi bi-list-ol main-color-one-color ms-1"></i>تعداد نتایج: {{ $questions->total() }}
                    </span>
                    <button type="button" class="btn btn-outline-secondary rounded-3 font-13" id="sfq-reset-btn" aria-label="پاک کردن فیلترها">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- جدول پرسش‌ها --}}
    <div class="content-box">
        <div class="container-fluid">
            <div class="py-3 border-bottom">
                <h6 class="fw-bold mb-0 font-14"><i class="bi bi-table main-color-one-color ms-1"></i>لیست پرسش‌ها</h6>
            </div>
            @if($questions->count())
                <div class="table-responsive py-2">
                    <table class="table align-middle site-tabel mb-2">
                        <thead class="text-bg-dark bg-opacity-75 text-center">
                        <tr>
                            <th scope="col" style="width:60px;">ردیف</th>
                            <th scope="col">پرسش‌گر</th>
                            <th scope="col">متن پرسش</th>
                            <th scope="col">محصول</th>
                            <th scope="col">نوع</th>
                            <th scope="col">وضعیت</th>
                            <th scope="col">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="text-center">
                        @foreach($questions as $question)
                            <tr>
                                <td class="font-13">{{ ($questions->currentPage() - 1) * $questions->perPage() + $loop->iteration }}</td>
                                <td class="font-13 text-nowrap">
                                    @if($question->user)
                                        <i class="bi bi-person ms-1 main-color-one-color"></i>کاربر: {{ $question->user->fullname ?? 'کاربر' }}
                                    @elseif($question->admin)
                                        <i class="bi bi-headset ms-1 main-color-one-color"></i>پشتیبان سایت
                                    @elseif($question->seller)
                                        <i class="bi bi-shop ms-1 main-color-one-color"></i>فروشنده: {{ $question->seller->business_name ?? 'فروشنده' }}
                                    @else
                                        <i class="bi bi-person-x ms-1 text-muted"></i>کاربر مهمان
                                    @endif
                                </td>
                                <td class="font-13 text-start">{{ short_content($question->body, 15) }}</td>
                                <td class="font-13 text-start">
                                    <a href="{{ $question->commentable->link() }}" target="_blank" rel="noopener"
                                       class="text-decoration-none text-dark" aria-label="مشاهده محصول {{ $question->commentable->title }}">
                                        {{ \Illuminate\Support\Str::limit($question->commentable->title, 35, ' …') }}
                                        <i class="bi bi-box-arrow-up-right font-12 main-color-one-color"></i>
                                    </a>
                                </td>
                                <td class="font-13">
                                    @if($question->comment_id)
                                        <span class="badge rounded-pill success-label">پاسخ</span>
                                    @else
                                        <span class="badge rounded-pill warning-label">پرسش</span>
                                    @endif
                                </td>
                                <td class="font-13">
                                    @if($question->status == 'pending')
                                        <span class="badge rounded-pill warning-label">منتظر تایید</span>
                                    @elseif($question->status == 'noanswer')
                                        <span class="badge rounded-pill warning-label">در انتظار پاسخ</span>
                                    @elseif($question->status == 'accepted')
                                        <span class="badge rounded-pill success-label">تایید شده</span>
                                    @elseif($question->status == 'unconfirmed')
                                        <span class="badge rounded-pill danger-label">رد شده</span>
                                    @else
                                        <span class="badge rounded-pill bg-secondary">{{ $question->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="sfq-show-btn btn btn-sm btn-outline-primary rounded-3 font-12"
                                            data-action="{{ route('seller.questions.show', ['question' => $question]) }}"
                                            aria-label="مشاهده و پاسخ به پرسش {{ $question->id }}">
                                        <i class="bi bi-eye"></i> مشاهده و پاسخ
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="py-3">
                    {{ $questions->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-patch-question text-muted d-block mb-2" style="font-size:2.6rem;" aria-hidden="true"></i>
                    <h6 class="font-16 mt-3 mb-2">پرسشی یافت نشد</h6>
                    <p class="text-muted font-14 mb-0">هنوز پرسشی درباره محصولات شما ثبت نشده یا نتیجه‌ای با فیلترهای فعلی وجود ندارد.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- مودال جزئیات پرسش (محتوای AJAX از questions/show) --}}
    <div class="modal fade" id="sfq-show-modal" tabindex="-1" aria-labelledby="sfq-show-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title font-15" id="sfq-show-modal-title">
                        <i class="bi bi-patch-question main-color-one-color ms-1"></i>جزئیات پرسش
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div id="sfq-question-detail" class="modal-body"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-questions.js') }}?v=1" data-saniar-questions="index"></script>
@endpush
