{{-- پیام‌ها (اعلان‌های) فروشنده — سانیار فاز J
    قرارداد NotificationSellerController@index :
    $notifications (paginate 20 از رابطهٔ notifications فروشنده — title, message, priority, created_at,
    pivot->read) + فیلترهای GET: priority, sort, unread --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'پیام‌ها', 'active' => 'notifications'])

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
                    <h5 class="fw-bold mb-1"><i class="bi bi-bell main-color-one-color ms-1"></i>پیام‌ها</h5>
                    <p class="font-13 text-muted mb-0">اطلاعیه‌ها و پیام‌های پشتیبانی برای شما.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- فیلترها --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <form id="sfn-filter-form" class="row g-2 align-items-end py-3" method="GET" action="{{ route('seller.notifications.index') }}">
                <div class="col-md-3 col-6">
                    <label class="form-label font-13 fw-bold" for="sfn-priority">اولویت</label>
                    <select class="form-select font-13" id="sfn-priority" name="priority">
                        <option value="all" {{ request('priority') == 'all' || !request('priority') ? 'selected' : '' }}>همه</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>خیلی مهم</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>مهم</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>عادی</option>
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label font-13 fw-bold" for="sfn-sort">ترتیب نمایش</label>
                    <select class="form-select font-13" id="sfn-sort" name="sort">
                        <option value="latest" {{ request('sort') == 'priority' ? '' : 'selected' }}>جدیدترین</option>
                        <option value="priority" {{ request('sort') == 'priority' ? 'selected' : '' }}>مهم‌ترین</option>
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="sfn-unread" name="unread"
                               {{ request('unread') == 'on' ? 'checked' : '' }}>
                        <label class="form-check-label font-13" for="sfn-unread">فقط خوانده‌نشده</label>
                    </div>
                </div>
                <div class="col-md-3 col-6 d-flex align-items-end justify-content-md-end gap-2">
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2 font-12" aria-live="polite">
                        <i class="bi bi-list-ol main-color-one-color ms-1"></i>تعداد نتایج: {{ $notifications->total() }}
                    </span>
                    <button type="button" class="btn btn-outline-secondary rounded-3 font-13" id="sfn-reset-btn" aria-label="پاک کردن فیلترها">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- آکاردئون اعلان‌ها --}}
    <div class="content-box">
        <div class="container-fluid">
            <div class="py-3 border-bottom">
                <h6 class="fw-bold mb-0 font-14"><i class="bi bi-inbox main-color-one-color ms-1"></i>صندوق پیام‌ها</h6>
            </div>
            @if($notifications->count())
                <div class="py-3">
                    @foreach($notifications as $notification)
                        @php $is_read = (bool) $notification->pivot->read; @endphp
                        <div class="sfn-item border rounded-3 mb-2">
                            <button type="button"
                                    class="sfn-toggle btn w-100 text-decoration-none text-dark d-flex flex-wrap align-items-center gap-2 p-3 collapsed"
                                    data-bs-toggle="collapse" data-bs-target="#sfn-item-{{ $notification->id }}"
                                    aria-expanded="false" aria-controls="sfn-item-{{ $notification->id }}"
                                    aria-label="باز کردن پیام {{ $notification->title }}"
                                    data-action="{{ route('seller.notifications.read', ['notification' => $notification]) }}"
                                    data-read="{{ $is_read ? 'yes' : 'no' }}">
                                <i class="bi bi-bell main-color-one-color" aria-hidden="true"></i>
                                @if($notification->priority == 'high')
                                    <span class="badge rounded-pill danger-label font-12">خیلی مهم</span>
                                @elseif($notification->priority == 'medium')
                                    <span class="badge rounded-pill warning-label font-12">مهم</span>
                                @else
                                    <span class="badge rounded-pill success-label font-12">عادی</span>
                                @endif
                                <span class="font-14 fw-bold">{{ $notification->title }}</span>
                                @unless($is_read)
                                    <i class="bi bi-circle-fill text-danger font-10 sfn-unread-dot" role="img" aria-label="خوانده‌نشده"></i>
                                @endunless
                                <span class="font-12 text-muted me-auto text-nowrap">{{ jdate($notification->created_at)->format('Y/m/d') }}</span>
                                <i class="bi bi-chevron-down font-12 text-muted" aria-hidden="true"></i>
                            </button>
                            <div class="collapse" id="sfn-item-{{ $notification->id }}">
                                <div class="px-3 pb-3 pt-3 border-top">
                                    {{-- HTML خام به‌صورت عمدی: پیام اعلان توسط مدیریت (ادمین) نوشته می‌شود --}}
                                    <div class="font-14 sfn-message-body">{!! $notification->message !!}</div>
                                    <div class="font-12 mt-2 sfn-read-state {{ $is_read ? 'text-success' : 'text-danger' }}">
                                        {{ $is_read ? 'خوانده شده' : 'ناخوانده' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="pb-3">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-bell-slash text-muted d-block mb-2" style="font-size:2.6rem;" aria-hidden="true"></i>
                    <h6 class="font-16 mt-3 mb-2">پیامی برای نمایش وجود ندارد</h6>
                    <p class="text-muted font-14 mb-0">وقتی اطلاعیه جدیدی برای شما ارسال شود، اینجا نمایش داده می‌شود.</p>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-notifications.js') }}?v=1" data-saniar-notifications="index"></script>
@endpush
