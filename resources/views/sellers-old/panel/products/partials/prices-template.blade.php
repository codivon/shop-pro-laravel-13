<script id="prices-template" type="text/x-custom-template">
    <div class="row animated fadeIn single-price">

        <div class="col-12">
            <div class="row">
                @foreach ($attributeGroups as $attributeGroup)
                    <div class="col-md-3 col-12">
                        <div class="form-group">
                            <label>{{ $attributeGroup->name }}</label>
                            <select class="form-control price-attribute-select" name="attribute">
                                <option value="">انتخاب کنید</option>
                                @foreach ($attributeGroup->get_attributes as $attribute)
                                    <option value="{{ $attribute->id }}">{{ $attribute->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-md-3 col-12">
            <div class="form-group">
                <label>قیمت
                    <span class="required-star" style="color:red;">*</span>
                </label>
                <input type="number" data-unit="تومان" class="form-control amount-input price" name="price" required>
            </div>
        </div>

        <div class="col-md-3 col-12">
            <div class="form-group">
                <label>تخفیف</label>
                <input type="number" class="form-control discount" name="discount" min="0" max="100" placeholder="%">
            </div>
        </div>

        <div class="col-md-3 col-12">
            <div class="form-group">
                <label>بیشترین تعداد مجاز در هر سفارش</label>
                <input type="number" class="form-control" name="cart_max" min="1">
            </div>
        </div>
        <div class="col-md-3 col-12">
            <div class="form-group">
                <label>کمترین تعداد مجاز در هر سفارش</label>
                <input type="number" class="form-control" name="cart_min" min="1">
            </div>
        </div>
        <div class="col-md-3 col-12">
            <div class="form-group">
                <label>موجودی محصول نزد شما
                    <span class="required-star" style="color:red;">*</span>
                </label>
                <input type="number" class="form-control" name="stock" min="0" required>
            </div>
        </div>
        <div class="col-md-3 col-12">
            <div class="form-group">
                <label>قیمت نهایی</label>
                <input type="text" class="form-control final-price" disabled>
            </div>
        </div>
        <div class="col-md-3 col-12">
            <div class="form-group">
                <label>وضعیت</label>
                <select class="form-control" name="published">
                    <option value="1">نمایش</option>
                    <option value="0">عدم نمایش</option>

                </select>
            </div>
        </div>
        <div class="col-md-6 d-none">
            <div class="form-group">
                <select class="form-control" name="attribute_seller">
                    <option selected value="{{seller_info()->seller_id}}"></option>
                </select>
            </div>
        </div>
        <div class="col-md-12 text-right">
            <button type="button" class="btn btn-flat-danger waves-effect waves-light remove-product-price custom-padding">حذف<i class="fa fa-trash text-danger"></i></button>
        </div>

        <div class="col-md-12"><hr></div>
    </div>
</script>
