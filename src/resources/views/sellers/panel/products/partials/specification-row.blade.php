{{-- یک ردیف مشخصات (رندر سروری) — سانیار فاز H
    ورودی: $gi (اندیس گروه), $si (اندیس مشخصه), $spec_name, $spec_value, $spec_special --}}
<div class="single-specificition saniar-spec-row row g-2 align-items-center">
    <div class="col-auto">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="sfp-spec-special-{{ $gi }}-{{ $si }}"
                   name="specification_group[{{ $gi }}][specifications][{{ $si }}][special]"
                   {{ $spec_special ? 'checked' : '' }} value="1">
            <label class="form-check-label font-12" for="sfp-spec-special-{{ $gi }}-{{ $si }}"
                   title="نمایش برجسته در صفحهٔ محصول">ویژه</label>
        </div>
    </div>
    <div class="col-md-4">
        <input type="text" class="form-control" name="specification_group[{{ $gi }}][specifications][{{ $si }}][name]"
               placeholder="مثال: حافظه داخلی" value="{{ $spec_name }}" required aria-label="عنوان مشخصه">
    </div>
    <div class="col">
        <textarea class="form-control" rows="1" name="specification_group[{{ $gi }}][specifications][{{ $si }}][value]"
                  placeholder="مثال: 32 گیگابایت" required aria-label="مقدار مشخصه">{{ $spec_value }}</textarea>
    </div>
    <div class="col-auto">
        <button type="button" class="btn btn-sm btn-outline-danger rounded-3 remove-specification" title="حذف مشخصه">
            <i class="bi bi-trash"></i>
        </button>
    </div>
</div>
