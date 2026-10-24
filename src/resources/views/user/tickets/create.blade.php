{{-- ثبت تیکت جدید — TicketController@create : $active (POST به tickets.store با subject/priority/message/upload_files[]) --}}
@extends('front::user.layouts.master', ['title' => 'ثبت تیکت جدید'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    <div class="content-box">
        <div class="container-fluid">
            <div class="title-panel mb-4">
                <h2 class="font-16 mb-0"><i class="bi bi-plus-circle main-color-one-color me-1"></i>ثبت تیکت جدید</h2>
            </div>

            <form id="ticket-create-form" action="{{ route('front.tickets.store') }}" method="POST"
                  enctype="multipart/form-data" data-redirect="{{ route('front.tickets.index') }}" novalidate>
                @csrf
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label font-14 fw-bold" for="subject">موضوع <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="subject" name="subject"
                               placeholder="موضوع تیکت خود را بنویسید" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-14 fw-bold" for="priority">اولویت <span class="text-danger">*</span></label>
                        <select name="priority" id="priority" class="form-select rounded-3" required>
                            <option value="low">کم</option>
                            <option value="medium" selected>متوسط</option>
                            <option value="hight">زیاد</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label font-14 fw-bold" for="message">پیام <span class="text-danger">*</span></label>
                        <textarea id="message" class="form-control rounded-3" rows="6" name="message"
                                  placeholder="شرح کامل مشکل یا سؤال خود را بنویسید..." required></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label font-14 fw-bold" for="ticket-files">فایل های پیوست (اختیاری)</label>
                        <input type="file" class="form-control rounded-3" name="upload_files[]" id="ticket-files" multiple
                               accept=".png,.jpeg,.jpg,.zip">
                        <p class="font-12 text-muted mt-1 mb-0">فرمت‌های مجاز: png، jpg، zip — هر فایل حداکثر ۲۰۰ مگابایت</p>
                    </div>
                    <div class="col-12">
                        <div id="ticket-form-error" class="font-12 text-danger d-none" role="alert"></div>
                        <button type="submit" class="btn border-0 main-color-one-bg text-white rounded-3 px-5 py-2 waves-effect waves-light">
                            <i class="bi bi-send me-1"></i> ایجاد تیکت
                        </button>
                        <a href="{{ route('front.tickets.index') }}" class="btn btn-light border rounded-3 px-4 py-2">انصراف</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush
@push('scripts')
    <script src="{{ theme_asset('js/pages/user/panel.js') }}?v=1"></script>
@endpush
