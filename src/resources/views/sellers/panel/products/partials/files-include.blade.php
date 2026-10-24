{{-- ردیف فایل موجود (edit) — سانیار فاز H
    ورودی: $price (قیمت دانلودی دارای file), $fi (اندیس)
    نکته: اگر قیمت file ندارد (ردیف ناقص دیتابیس)، چیزی رندر نمی‌کنیم --}}
@if ($price->file)
    <div class="single-file saniar-file-row">
        <div class="saniar-price-head d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <span class="badge rounded-pill bg-secondary text-white font-12">فایل {{ $fi }}</span>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-3 remove-file" title="حذف فایل">
                <i class="bi bi-trash"></i> حذف
            </button>
        </div>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label font-12 mb-1">عنوان فایل <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="download_files[{{ $fi }}][title]"
                       value="{{ $price->file->title }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label font-12 mb-1">فایل (برای جایگزینی انتخاب کنید)</label>
                <input type="file" class="form-control" name="download_files[{{ $fi }}][file]">
                <div class="form-text font-12">فایل فعلی: {{ \Illuminate\Support\Str::limit($price->file->file, 40) }}</div>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label font-12 mb-1">قیمت (تومان) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="download_files[{{ $fi }}][price]"
                       min="0" value="{{ $price->price }}" required>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label font-12 mb-1">تخفیف (٪)</label>
                <input type="number" step="any" class="form-control" name="download_files[{{ $fi }}][discount]"
                       min="0" max="100" value="{{ $price->discount }}">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label font-12 mb-1">وضعیت</label>
                <select class="form-select form-select-sm" name="download_files[{{ $fi }}][status]">
                    <option value="active" {{ $price->file->status == 'active' ? 'selected' : '' }}>فعال</option>
                    <option value="inactive" {{ $price->file->status == 'inactive' ? 'selected' : '' }}>غیر فعال</option>
                </select>
            </div>
            <div class="col-md-3 col-6 d-flex align-items-end">
                <a href="{{ $price->downloadLink() }}" class="btn btn-sm btn-outline-success rounded-3 font-13 w-100" target="_blank">
                    <i class="bi bi-download ms-1"></i>دانلود فایل فعلی
                </a>
            </div>
        </div>
        <input type="hidden" name="download_files[{{ $fi }}][price_id]" value="{{ $price->id }}">
    </div>
@endif
