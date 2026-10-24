{{-- قالب‌های JS گروه/ردیف مشخصات + ردیف امتیاز ویژگی — سانیار فاز H
    (نیم‌اسپیس‌ها توسط seller-products.js بازنویسی می‌شوند) --}}
<script id="specification-group" type="text/x-custom-template">
    <div class="saniar-spec-group specification-group" data-group="">
        <div class="row g-2 align-items-center mb-2">
            <div class="col-auto">
                <span class="badge rounded-pill main-color-one-bg text-white font-12">گروه</span>
            </div>
            <div class="col">
                <input type="text" class="form-control group-input" name="specification_group"
                       placeholder="مثال: مشخصات کلی" required aria-label="نام گروه مشخصات">
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-outline-danger rounded-3 remove-group" title="حذف گروه">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <div class="all-specifications d-grid gap-2"></div>
        <button type="button" class="btn btn-sm btn-outline-success rounded-3 font-13 add-specifaction mt-2">
            <i class="bi bi-plus-lg ms-1"></i>افزودن مشخصات
        </button>
    </div>
</script>

<script id="specification-single" type="text/x-custom-template">
    <div class="single-specificition saniar-spec-row row g-2 align-items-center">
        <div class="col-auto">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="special_specification" value="1">
                <label class="form-check-label font-12" title="نمایش برجسته در صفحهٔ محصول">ویژه</label>
            </div>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" name="specification_name"
                   placeholder="مثال: حافظه داخلی" required aria-label="عنوان مشخصه">
        </div>
        <div class="col">
            <textarea class="form-control" rows="1" name="specification_value"
                      placeholder="مثال: 32 گیگابایت" required aria-label="مقدار مشخصه"></textarea>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-sm btn-outline-danger rounded-3 remove-specification" title="حذف مشخصه">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</script>

<script id="AbilityProductScore-group" type="text/x-custom-template">
    <div class="saniar-ability-row row g-2 align-items-center">
        <div class="col-md-6">
            <input type="text" class="form-control" name="AbilityProductScore"
                   placeholder="مثال: کیفیت ساخت" required aria-label="عنوان ویژگی">
        </div>
        <div class="col-md-4">
            <input type="number" step="any" min="0" max="5" class="form-control" name="AbilityProductScore_value"
                   placeholder="مثال: 4.5" required aria-label="امتیاز">
        </div>
        <div class="col-md-2 col-auto">
            <button type="button" class="btn btn-sm btn-outline-danger rounded-3 remove-ability" title="حذف">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</script>
