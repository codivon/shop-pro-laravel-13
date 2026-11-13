@extends('front::user.layouts.master')

@section('user-content')
    <div class="headline-profile page-profile-order">
        <span>ثبت تیکت جدید</span>
    </div>
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
        <div class="px-3 px-res-0">

            <div class="dt-sl dt-sn pt-4">
                <div class="col-12 col-md-10 offset-md-1">
                    <form class="form" id="ticket-create-form" data-redirect="{{ route('front.tickets.index') }}" action="{{ route('front.tickets.store') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>موضوع</label>
                                        <input type="text" class="form-control" name="subject">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>اولویت</label>
                                        <select name="priority" class="form-control">
                                            <option value="low">کم</option>
                                            <option value="medium">متوسط</option>
                                            <option value="hight">زیاد</option>
                                        </select>
                                    </div>
                                </div>


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

                                <div class=" form-legal-row-submit">
                                    <div class="parent-btn col-md-4 display-inline-block">
                                        <button id="submit-btn" class="dk-btn dk-btn-info w-100">
                                            ایجاد تیکت
                                            <i class="fa fa-check sign-in"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>

    <script src="{{ theme_asset('js/pages/tickets/create.js') }}"></script>
@endpush
