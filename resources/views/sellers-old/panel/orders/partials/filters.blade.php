<div class="card">
    <div class="card-header filter-card">
        <h4 class="card-title">فیلتر کردن</h4>
        <a class="heading-elements-toggle"><i class="fa fa-ellipsis-v font-medium-3"></i></a>
        <div class="heading-elements">
            <ul class="list-inline mb-0">
                <li><a data-action="collapse"><i class="feather icon-chevron-down"></i></a></li>
            </ul>
        </div>
    </div>
    <div class="card-content collapse {{ request()->except('page') ? 'show' : '' }}">
        <div class="card-body">
            <div class="users-list-filter">
                <form id="filter-orders-form" method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <label>نام و نام خانوادگی</label>
                            <fieldset class="form-group">
                                <input type="text" class="form-control datatable-filter" name="fullname" value="{{ request('fullname') }}">
                            </fieldset>
                        </div>

                        <div class="col-md-3">
                            <label>نام کاربری</label>
                            <fieldset class="form-group">
                                <input class="form-control datatable-filter" name="username" value="{{ request('username') }}">
                            </fieldset>
                        </div>

                        <div class="col-md-2">
                            <label>شماره سفارش</label>
                            <fieldset class="form-group">
                                <input type="text" class="form-control datatable-filter" name="id" value="{{ request('id') }}">
                            </fieldset>
                        </div>
                        <div class="col-md-2">
                            <label>وضعیت</label>
                            <fieldset class="form-group">
                                <select name="status" class="form-control datatable-filter">
                                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>همه</option>
                                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>پرداخت شده</option>
                                    <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>پرداخت نشده</option>
                                    <option value="canceled" {{ request('status') == 'canceled' ? 'selected' : '' }}>لغو شده</option>
                                </select>
                            </fieldset>
                        </div>
                        <div class="col-md-2">
                            <label>وضعیت ارسال</label>
                            <fieldset class="form-group">
                                <select name="shipping_status" class="form-control datatable-filter">
                                    <option value="all" {{ request('shipping_status') == 'all' ? 'selected' : '' }}>همه</option>
                                    <option value="w-pending" {{ request('shipping_status') == 'w-pending' ? 'selected' : '' }}>در انتظار بررسی</option>
                                    <option value="pending" {{ request('shipping_status') == 'pending' ? 'selected' : '' }}>در حال بررسی</option>
                                    <option value="waiting" {{ request('shipping_status') == 'waiting' ? 'selected' : '' }}>منتظر ارسال</option>
                                    <option value="sent" {{ request('shipping_status') == 'sent' ? 'selected' : '' }}>ارسال شد</option>
                                    <option value="canceled" {{ request('shipping_status') == 'canceled' ? 'selected' : '' }}>ارسال لغو شد</option>
                                </select>
                            </fieldset>
                        </div>

                        <div class="col-md-3">
                            <label>نام محصول</label>
                            <fieldset class="form-group">
                                <input class="form-control datatable-filter" name="product_name" value="{{ request('product_name') }}">
                            </fieldset>
                        </div>
                        <div class="col-md-3">
                            <label>آیدی محصول</label>
                            <fieldset class="form-group">
                                <input class="form-control datatable-filter" name="product_id" value="{{ request('product_id') }}">
                            </fieldset>
                        </div>

                        <div class="col-md-2">
                            <label class="pre-space" for="from">از تاریخ : </label>
                            <div class="form-group">
                                <input class="form-control persian-date-picker" name="from_date" type="text">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="pre-space" for="from">تا تاریخ : </label>
                            <div class="form-group">
                                <input class="form-control persian-date-picker" name="to_date" type="text">
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>
