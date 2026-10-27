@extends('sellers-old.panel.layouts.master')

@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">ثبت درخواست پشتیبانی</span>
                <span class="c-content-page__header-desc">اینجا می‌توانید برای مشکل خود «درخواست پشتیبانی» ثبت کنید.</span>
            </div>
        </div>
    </div>
    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">
        <div class="row dashboard-steps-3">
            <div class="col-12 dashboard-steps-3-item ">
                <div class="c-card">
                    <div class="c-card__header d-flex pt-1 pb-1">
                        <h2 class="c-card__title line-height-40">ثبت درخواست پشتیبانی جدید</h2>
                    </div>
                    <div class="c-card__body uk-height-1-1 uk-flex-middle">
                        <form class="form" id="ticket-create-form" data-redirect="{{ route('seller.tickets.index') }}" action="{{ route('seller.tickets.store') }}" method="post" enctype="multipart/form-data">
                            @csrf
                        <div class="form-body col-md-12">
                            <div class=" w-100 row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>موضوع</label>
                                        <input type="text" class="form-control valid" value="" name="subject" aria-invalid="false">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>اولویت</label>
                                        <select name="priority" class="w-100 valid form-control">
                                            <option value="low">کم</option>
                                            <option value="medium">متوسط</option>
                                            <option value="hight">زیاد</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-10">
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
                                            <button id="submit-btn" class="c-wallet__header-card-btn--deposit js-trigger-wallet-modal w-100 border-0">ثبت تیکت
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
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>

    <script src="{{ theme_asset('js/pages/sellers/tickets/create.js') }}"></script>
@endpush
