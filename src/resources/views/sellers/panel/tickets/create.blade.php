{{-- ثبت درخواست پشتیبانی فروشنده — سانیار فاز J
    قرارداد SellerTicketController@create :
    فرم POST به seller.tickets.store با subject/priority/message/upload_files[] --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'ثبت درخواست پشتیبانی', 'active' => 'tickets'])

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
                    <h5 class="fw-bold mb-1"><i class="bi bi-plus-circle main-color-one-color ms-1"></i>ثبت درخواست پشتیبانی</h5>
                    <p class="font-13 text-muted mb-0">موضوع و شرح کامل مشکل را بنویسید تا پشتیبان سریع‌تر راهنمایی شما باشد.</p>
                </div>
                <a href="{{ route('seller.tickets.index') }}" class="btn btn-outline-secondary rounded-3 font-14">
                    <i class="bi bi-arrow-right ms-1"></i>بازگشت به لیست
                </a>
            </div>
        </div>
    </div>

    {{-- فرم ثبت تیکت --}}
    <div class="content-box">
        <div class="container-fluid">
            <form id="sfk-ticket-form" action="{{ route('seller.tickets.store') }}" method="POST"
                  enctype="multipart/form-data" data-redirect="{{ route('seller.tickets.index') }}" novalidate>
                @csrf
                <div class="row g-3 py-3">
                    <div class="col-md-8">
                        <label class="form-label font-14 fw-bold" for="sfk-subject">موضوع <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="sfk-subject" name="subject"
                               placeholder="موضوع درخواست خود را بنویسید" required aria-required="true">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-14 fw-bold" for="sfk-priority">اولویت <span class="text-danger">*</span></label>
                        <select name="priority" id="sfk-priority" class="form-select rounded-3" required aria-required="true">
                            <option value="low">کم</option>
                            <option value="medium" selected>متوسط</option>
                            <option value="hight">زیاد</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label font-14 fw-bold" for="sfk-message">پیام <span class="text-danger">*</span></label>
                        <textarea id="sfk-message" class="form-control rounded-3" rows="5" name="message"
                                  placeholder="شرح کامل مشکل یا سؤال خود را بنویسید..." required aria-required="true"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label font-14 fw-bold" for="sfk-files">فایل‌های پیوست (اختیاری)</label>
                        <input type="file" class="form-control rounded-3" name="upload_files[]" id="sfk-files" multiple
                               accept=".png,.jpeg,.jpg,.zip" aria-label="فایل‌های پیوست اختیاری">
                        <p class="font-12 text-muted mt-1 mb-0">فرمت‌های مجاز: png، jpg، zip — هر فایل حداکثر ۲۰۰ مگابایت</p>
                    </div>
                    <div class="col-12">
                        <div id="sfk-ticket-form-error" class="font-12 text-danger d-none" role="alert"></div>
                        <button type="submit" class="btn main-color-one-bg text-white rounded-3 font-14 px-5 py-2">
                            <i class="bi bi-send ms-1"></i>ثبت تیکت
                        </button>
                        <a href="{{ route('seller.tickets.index') }}" class="btn btn-light border rounded-3 font-14 px-4 py-2">انصراف</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-tickets.js') }}?v=1" data-saniar-tickets="create"></script>
@endpush
