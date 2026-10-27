@extends('sellers-old.panel.layouts.master')

@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">سفارش‌ها</span>
                <span class="c-content-page__header-desc">اینجا می‌توانید تمام سفارش‌های خود را ببینید.</span>
            </div>
        </div>
    </div>
    <div class="content-body">
        <!-- filter start -->
        @include('sellers-old.panel.orders.partials.filters')
        <!-- filter end -->

        <section id="main-card" class="card">
            <div class="card-header">
                <h4 class="card-title">لیست سفارشات</h4>

                <div class="heading-elements">
                    <ul class="list-inline mb-0">
                        <li><button type="button" data-toggle="modal" data-target="#orders-export-modal" class="btn btn-outline-primary waves-effect waves-light"><i class="fa fa-file-excel-o"></i> خروجی گرفتن از لیست</button></li>
                    </ul>
                </div>

            </div>
            <div class="card-content">
                <div class="card-body">
                    <div class="mb-2 collapse datatable-actions">
                        <div class="d-flex align-items-center">
                            <div class="font-weight-bold text-danger mr-3"><span id="datatable-selected-rows">0</span> مورد انتخاب شده: </div>
                            <button class="btn personal-danger-btn mr-2" type="button" data-toggle="modal" data-target="#multiple-delete-modal">حذف همه</button>

                            <button type="button" id="print-all-factor-btn" data-action="{{ route('seller.orders.printAll') }}" class="btn btn-success mr-2">پرینت فاکتور ها</button>
                            <button type="button" id="print-all-shipping" data-action="{{ route('seller.orders.printAllShippingFormsMin') }}" class="btn btn-success mr-2">پرینت لیبل ها</button>
                            <button type="button" id="print-all-btn" data-action="{{ route('seller.orders.printAllShippingForms') }}" class="btn personal-success-btn mr-2">پرینت برچسب ها</button>


                        </div>
                    </div>
                    <div class="datatable datatable-bordered datatable-head-custom" id="orders_datatable" data-action="{{ route('seller.orders.apiIndex') }}"></div>
                </div>
            </div>
        </section>
    </div>
    {{-- multiple delete modal --}}
    <div class="modal fade text-left" id="multiple-delete-modal" tabindex="-1" role="dialog"  aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel19">آیا مطمئن هستید؟</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    با حذف سفارشات دیگر قادر به بازیابی آنها نخواهید بود
                </div>
                <div class="modal-footer">
                    <form action="{{ route('seller.orders.multipleDestroy') }}" id="order-multiple-delete-form">
                        @csrf
                        @method('delete')
                        <button type="button" class="btn personal-success-btn waves-effect waves-light" data-dismiss="modal">خیر</button>
                        <button type="submit" class="btn personal-danger-btn waves-effect waves-light">بله حذف شود</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

     <!-- export modal -->
     <div class="modal fade text-left" id="orders-export-modal" tabindex="-1" role="dialog"  aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel19">فیلدهای مورد نظر را انتخاب کنید</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form id="orders-export-form" action="{{ route('seller.orders.export') }}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-row" type="checkbox" class="custom-control-input" name="filters[row]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-row">ردیف</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-id" type="checkbox" class="custom-control-input" name="filters[id]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-id">آیدی</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-user_id" type="checkbox" class="custom-control-input" name="filters[user_id]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-user_id">آیدی کاربر</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-name" type="checkbox" class="custom-control-input" name="filters[name]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-name">نام و نام خانوادگی</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-mobile" type="checkbox" class="custom-control-input" name="filters[mobile]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-mobile">شماره همراه</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-province" type="checkbox" class="custom-control-input" name="filters[province]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-province">استان</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-city" type="checkbox" class="custom-control-input" name="filters[city]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-city">شهر</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-postal_code" type="checkbox" class="custom-control-input" name="filters[postal_code]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-postal_code">کد پستی</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-address" type="checkbox" class="custom-control-input" name="filters[address]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-address">آدرس کامل</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-carrier" type="checkbox" class="custom-control-input" name="filters[carrier]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-carrier">شیوه تحویل</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-created_at" type="checkbox" class="custom-control-input" name="filters[created_at]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-created_at">تاریخ ثبت</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-shipping_cost" type="checkbox" class="custom-control-input" name="filters[shipping_cost]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-shipping_cost">هزینه ارسال</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-total_discount" type="checkbox" class="custom-control-input" name="filters[total_discount]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-total_discount">تخفیف</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-price" type="checkbox" class="custom-control-input" name="filters[price]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-price">جمع قیمت</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-description" type="checkbox" class="custom-control-input" name="filters[description]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-description">توضیحات سفارش</label>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="custom-control custom-checkbox custom-checkbox-success">
                                    <input id="export-checkbox-products" type="checkbox" class="custom-control-input" name="filters[products]" value="1" checked>
                                    <label class="custom-control-label" for="export-checkbox-products">لیست محصولات</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="">نوع خروجی</label>
                                    <select name="export_type" class="form-control">
                                        <option value="excel">اکسل</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn personal-success-btn waves-effect waves-light">خروجی گرفتن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="shiping-status-change" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shiping-status-changeTitle">تغیر وضعیت ارسال گروهی سفارش ها</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="multiple-shipping-status-change" action="{{ route('seller.orders.shippings-status') }}">
                    <div class="modal-body">

                        <select name="status" class="form-control">
                            <option value="w-pending">در انتظار بررسی</option>
                            <option value="pending">در حال بررسی</option>
                            <option value="waiting">منتظر ارسال</option>
                            <option value="sent">ارسال شد</option>
                        </select>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">ذخیره</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
@include('back.partials.plugins', ['plugins' => ['datatable', 'persian-datepicker']])

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/orders/index.js') }}"></script>
@endpush
