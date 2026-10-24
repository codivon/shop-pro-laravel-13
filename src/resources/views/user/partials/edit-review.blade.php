{{-- مودال ویرایش دیدگاه (پارشال نظرات من) — پر کردن از data-* دکمه ویرایش؛ ارسال به reviews.store --}}
<div class="modal fade" id="edit-product-review-modal" tabindex="-1" aria-labelledby="edit-product-review-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 overflow-hidden">
            <div class="modal-header">
                <h5 class="modal-title font-16" id="edit-product-review-label">
                    <i class="bi bi-pencil-square main-color-one-color me-1"></i> ویرایش دیدگاه
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <p class="font-14 text-muted" id="edit-review-product-title"></p>
                <hr>
                <form id="edit-product-review-form" action="{{ route('front.reviews.store') }}" method="post" novalidate>
                    @csrf
                    {!! \App\Services\CommentProtection::hiddenFields() !!}
                    <input type="hidden" name="product_id" id="edit-review-product-id" value="">

                    {{-- امتیاز با ستاره‌ها --}}
                    <div class="mb-3">
                        <div class="form-row-title font-14 fw-bold mb-2">امتیاز شما <span class="text-danger">*</span></div>
                        <div class="saniar-star-rating d-flex flex-row-reverse justify-content-end gap-1" role="radiogroup" aria-label="امتیاز">
                            @for ($i = 5; $i >= 1; $i--)
                                <input type="radio" class="btn-check" name="rating" value="{{ $i }}"
                                       id="edit-review-star-{{ $i }}" autocomplete="off">
                                <label class="saniar-star-btn fs-4" for="edit-review-star-{{ $i }}"
                                       title="{{ ['خیلی بد', 'بد', 'معمولی', 'خوب', 'عالی'][$i - 1] }}">
                                    <i class="bi bi-star-fill"></i>
                                </label>
                            @endfor
                        </div>
                    </div>

                    {{-- پیشنهاد خرید --}}
                    <div class="mb-3">
                        <div class="form-row-title font-14 fw-bold mb-2">خرید این محصول را به دیگران ...</div>
                        <div class="d-flex flex-wrap gap-2">
                            <div>
                                <input type="radio" class="btn-check" id="edit-suggest-yes" name="suggest" value="yes" autocomplete="off">
                                <label class="btn btn-light rounded-3" for="edit-suggest-yes">
                                    <i class="bi bi-hand-thumbs-up text-success me-1"></i>پیشنهاد می‌کنم
                                </label>
                            </div>
                            <div>
                                <input type="radio" class="btn-check" id="edit-suggest-not-sure" name="suggest" value="not_sure" autocomplete="off">
                                <label class="btn btn-light rounded-3" for="edit-suggest-not-sure">
                                    <i class="bi bi-question-circle text-warning me-1"></i>مطمئن نیستم
                                </label>
                            </div>
                            <div>
                                <input type="radio" class="btn-check" id="edit-suggest-no" name="suggest" value="no" autocomplete="off">
                                <label class="btn btn-light rounded-3" for="edit-suggest-no">
                                    <i class="bi bi-hand-thumbs-down text-danger me-1"></i>پیشنهاد نمی‌کنم
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- عنوان --}}
                    <div class="mb-3">
                        <div class="form-row-title font-14 fw-bold mb-2">عنوان نظر شما <span class="text-danger">*</span></div>
                        <input class="form-control rounded-3" name="title" id="edit-review-title" type="text"
                               placeholder="عنوان نظر خود را بنویسید" required maxlength="100">
                    </div>

                    {{-- نقاط قوت --}}
                    <div class="mb-3">
                        <div class="form-row-title font-14 fw-bold mb-2">نقاط قوت</div>
                        <div class="input-group">
                            <input class="form-control" type="text" id="edit-advantage-input" autocomplete="off" placeholder="مثلا: کیفیت ساخت بالا">
                            <button class="btn btn-light border js-edit-point-add" type="button" data-list="edit-advantages-list" aria-label="افزودن نقطه قوت">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        <div class="form-comment-dynamic-labels d-flex flex-wrap gap-2 mt-2" id="edit-advantages-list"></div>
                    </div>

                    {{-- نقاط ضعف --}}
                    <div class="mb-3">
                        <div class="form-row-title font-14 fw-bold mb-2">نقاط ضعف</div>
                        <div class="input-group">
                            <input class="form-control" type="text" id="edit-disadvantage-input" autocomplete="off" placeholder="مثلا: قیمت بالا">
                            <button class="btn btn-light border js-edit-point-add" type="button" data-list="edit-disadvantages-list" aria-label="افزودن نقطه ضعف">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        <div class="form-comment-dynamic-labels d-flex flex-wrap gap-2 mt-2" id="edit-disadvantages-list"></div>
                    </div>

                    {{-- متن نظر --}}
                    <div class="mb-3">
                        <div class="form-row-title font-14 fw-bold mb-2">متن نظر شما <span class="text-danger">*</span></div>
                        <textarea name="body" id="edit-review-body" class="form-control rounded-3" rows="5"
                                  placeholder="متن خود را بنویسید" required maxlength="1000"></textarea>
                    </div>

                    <div id="edit-review-error" class="font-12 text-danger d-none mb-2" role="alert"></div>

                    <button type="submit" class="btn main-color-one-bg text-white rounded-3 w-100 py-2">
                        <i class="bi bi-send me-1"></i> ثبت ویرایش نظر
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
