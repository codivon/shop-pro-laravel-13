{{-- قالب JS ردیف تنوع/قیمت — سانیار فاز H (نیم‌اسپیس‌ها توسط seller-products.js بازنویسی می‌شوند) --}}
<script id="prices-template" type="text/x-custom-template">
    <div class="single-price saniar-price-row">
        <div class="saniar-price-head d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <span class="badge rounded-pill main-color-one-bg text-white font-12">تنوع</span>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-3 remove-product-price" title="حذف تنوع">
                <i class="bi bi-trash"></i> حذف
            </button>
        </div>

        <div class="row g-2 mb-2">
            @foreach ($attributeGroups as $attributeGroup)
                <div class="col-md-3 col-6">
                    <label class="form-label font-12 mb-1">{{ $attributeGroup->name }}</label>
                    <select class="form-select form-select-sm price-attribute-select" name="attributes"
                            {{ $attributeGroup->type == 'color' ? 'data-color-group="1"' : '' }}>
                        <option value="">انتخاب کنید</option>
                        @foreach ($attributeGroup->get_attributes as $attribute)
                            <option value="{{ $attribute->id }}" data-color="{{ $attribute->value }}">{{ $attribute->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>

        <div class="row g-2">
            <div class="col-md-3 col-6">
                <label class="form-label font-12 mb-1">قیمت (تومان) <span class="text-danger">*</span></label>
                <input type="number" class="form-control price" name="price" min="0" placeholder="مثال: 25000000">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label font-12 mb-1">تخفیف (٪)</label>
                <input type="number" class="form-control discount" name="discount" min="0" max="100" placeholder="مثال: 10">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label font-12 mb-1">موجودی نزد شما <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="stock" min="0" placeholder="مثال: 5">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label font-12 mb-1">قیمت نهایی</label>
                <input type="text" class="form-control final-price" disabled placeholder="—">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label font-12 mb-1">حداقل در هر سفارش</label>
                <input type="number" class="form-control" name="cart_min" min="1" value="1">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label font-12 mb-1">حداکثر در هر سفارش</label>
                <input type="number" class="form-control" name="cart_max" min="1" value="1">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label font-12 mb-1">وضعیت</label>
                <select class="form-select form-select-sm" name="published">
                    <option value="1">نمایش</option>
                    <option value="0">عدم نمایش</option>
                </select>
            </div>
        </div>

        <select class="d-none" name="attributes_seller">
            <option selected value="{{ seller_info()->seller_id }}"></option>
        </select>
    </div>
</script>
