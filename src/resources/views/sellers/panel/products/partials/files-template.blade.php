{{-- قالب JS ردیف فایل دانلودی — سانیار فاز H (نیم‌اسپیس‌ها توسط seller-products.js بازنویسی می‌شوند) --}}
<script id="files-template" type="text/x-custom-template">
    <div class="single-file saniar-file-row">
        <div class="saniar-price-head d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <span class="badge rounded-pill bg-secondary text-white font-12">فایل</span>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-3 remove-file" title="حذف فایل">
                <i class="bi bi-trash"></i> حذف
            </button>
        </div>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label font-12 mb-1">عنوان فایل <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="title" placeholder="مثال: نسخهٔ ویندوز" required>
            </div>
            <div class="col-md-6">
                <label class="form-label font-12 mb-1">فایل <span class="text-danger">*</span></label>
                <input type="file" class="form-control" name="file" required>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label font-12 mb-1">قیمت (تومان) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="price" min="0" required placeholder="مثال: 500000">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label font-12 mb-1">تخفیف (٪)</label>
                <input type="number" step="any" class="form-control" name="discount" min="0" max="100" placeholder="مثال: 20">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label font-12 mb-1">وضعیت</label>
                <select class="form-select form-select-sm" name="status">
                    <option value="active">فعال</option>
                    <option value="inactive">غیر فعال</option>
                </select>
            </div>
        </div>
    </div>
</script>
