{{-- مودال ثبت دیدگاه سانیار — $product (فقط کاربران لاگین) --}}
<div class="modal fade" data-action="{{ route('front.reviews.show', ['product' => $product]) }}"
     id="add-product-review-modal" tabindex="-1" aria-labelledby="add-product-review-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 overflow-hidden">
            <div class="modal-header">
                <h5 class="modal-title font-16" id="add-product-review-label">
                    <i class="bi bi-chat-square-text main-color-one-color me-1"></i>ثبت دیدگاه
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <p class="font-14 text-muted">{{ $product->title }}</p>
                <hr>
                <div class="row g-4">
                    <div class="col-lg-7">
                        <form id="add-product-review-form" action="{{ route('front.reviews.store') }}" method="post">
                            {{-- فاز ۴۴ میزبان — حفاظت ربات‌گیر (تلهٔ زمان/هانی‌پات) + کپچای اختیاری --}}
                            {!! \App\Services\CommentProtection::hiddenFields() !!}
                            @if(\App\Services\SecuritySettings::captchaEnabled('comments'))
                                <div class="mb-3">
                                    <x-captcha />
                                </div>
                            @endif
                            <input type="hidden" name="product_id" value="{{ $product->id }}">

                            {{-- امتیاز با ستاره‌ها --}}
                            <div class="mb-3">
                                <div class="form-row-title font-14 fw-bold mb-2">امتیاز دهید! <span class="text-danger">*</span></div>
                                <div class="saniar-star-rating d-flex flex-row-reverse justify-content-end gap-1" role="radiogroup" aria-label="امتیاز">
                                    @for ($i = 5; $i >= 1; $i--)
                                        <input type="radio" class="btn-check" name="rating" value="{{ $i }}"
                                               id="review-star-{{ $i }}" autocomplete="off" {{ $i == 3 ? 'checked' : '' }}>
                                        <label class="saniar-star-btn fs-4" for="review-star-{{ $i }}" data-title="{{ ['خیلی بد', 'بد', 'معمولی', 'خوب', 'عالی'][$i - 1] }}" title="{{ ['خیلی بد', 'بد', 'معمولی', 'خوب', 'عالی'][$i - 1] }}">
                                            <i class="bi bi-star-fill"></i>
                                        </label>
                                    @endfor
                                </div>
                                <small class="text-muted font-12" id="selected-rating-text">معمولی</small>
                            </div>

                            {{-- پیشنهاد خرید (فقط خریداران) --}}
                            @if (auth('web')->user()->hasBoughtProduct($product))
                                <div class="mb-3">
                                    <div class="form-row-title font-14 fw-bold mb-2">خرید این محصول را به دیگران ...</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <div>
                                            <input type="radio" class="btn-check" id="review-suggest-yes" name="suggest" value="yes" autocomplete="off">
                                            <label class="btn btn-light rounded-3" for="review-suggest-yes">
                                                <i class="bi bi-hand-thumbs-up text-success me-1"></i>پیشنهاد می‌کنم
                                            </label>
                                        </div>
                                        <div>
                                            <input type="radio" class="btn-check" id="review-suggest-not-sure" name="suggest" value="not_sure" autocomplete="off">
                                            <label class="btn btn-light rounded-3" for="review-suggest-not-sure">
                                                <i class="bi bi-question-circle text-warning me-1"></i>مطمئن نیستم
                                            </label>
                                        </div>
                                        <div>
                                            <input type="radio" class="btn-check" id="review-suggest-no" name="suggest" value="no" autocomplete="off">
                                            <label class="btn btn-light rounded-3" for="review-suggest-no">
                                                <i class="bi bi-hand-thumbs-down text-danger me-1"></i>پیشنهاد نمی‌کنم
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- عنوان --}}
                            <div class="mb-3">
                                <div class="form-row-title font-14 fw-bold mb-2">عنوان نظر شما (اجباری) <span class="text-danger">*</span></div>
                                <input class="form-control" name="title" type="text" placeholder="عنوان نظر خود را بنویسید" required maxlength="100">
                            </div>

                            {{-- نقاط قوت --}}
                            <div class="mb-3">
                                <div class="form-row-title font-14 fw-bold mb-2">نقاط قوت</div>
                                <div id="advantages" class="input-group">
                                    <input class="form-control ui-input-field" type="text" id="advantage-input" autocomplete="off" placeholder="مثلا: کیفیت ساخت بالا" value="">
                                    <button class="btn btn-light border js-icon-form-add" type="button" aria-label="افزودن نقطه قوت">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <div class="form-comment-dynamic-labels js-advantages-list d-flex flex-wrap gap-2 mt-2"></div>
                            </div>

                            {{-- نقاط ضعف --}}
                            <div class="mb-3">
                                <div class="form-row-title font-14 fw-bold mb-2">نقاط ضعف</div>
                                <div id="disadvantages" class="input-group">
                                    <input class="form-control ui-input-field" type="text" id="disadvantage-input" autocomplete="off" placeholder="مثلا: قیمت بالا" value="">
                                    <button class="btn btn-light border js-icon-form-add" type="button" aria-label="افزودن نقطه ضعف">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <div class="form-comment-dynamic-labels js-disadvantages-list d-flex flex-wrap gap-2 mt-2"></div>
                            </div>

                            {{-- متن نظر --}}
                            <div class="mb-3">
                                <div class="form-row-title font-14 fw-bold mb-2">متن نظر شما (اجباری) <span class="text-danger">*</span></div>
                                <textarea name="body" class="form-control" rows="5" placeholder="متن خود را بنویسید" required maxlength="1000"></textarea>
                            </div>

                            <button type="submit" class="btn main-color-one-bg text-white rounded-3 w-100 py-2 comment-submit-btn">
                                <i class="bi bi-send me-1"></i>ثبت نظر
                            </button>
                            <p class="font-12 text-muted mt-2 mb-0">
                                با «ثبت نظر» موافقت خود را با قوانین انتشار محتوا در {{ option('info_site_title', config('front.demo.name')) }} اعلام می‌کنید.
                            </p>
                        </form>
                    </div>
                    <div class="col-lg-5">
                        @if (option('dt_product_reviews_description'))
                            {!! option('dt_product_reviews_description') !!}
                        @else
                            <div class="bg-light rounded-3 p-3 font-13 text-muted line-height-2">
                                <h6 class="font-14 text-dark mb-2">راهنمای نوشتن دیدگاه</h6>
                                <p class="mb-2">لطفا پیش از ارسال نظر، خلاصه قوانین زیر را مطالعه کنید:</p>
                                <p class="mb-2">فارسی بنویسید و از کیبورد فارسی استفاده کنید؛ از شکلک و ایموجی بیش‌ازحد و کشیدن حروف پرهیز کنید.</p>
                                <p class="mb-2">نظرات خود را براساس تجربه‌ی عملی و با دقت به نکات فنی ارسال کنید؛ بدون تعصب، مزایا و معایب را بازگو کنید.</p>
                                <p class="mb-2">از تمرکز روی عناصر متغیر مثل قیمت پرهیز کنید.</p>
                                <p class="mb-2">به کاربران احترام بگذارید؛ پیام‌های توهین‌آمیز حذف می‌شوند.</p>
                                <p class="mb-2">از ارسال لینک سایت‌های دیگر و اطلاعات شخصی (شماره تماس، ایمیل، شبکه‌های اجتماعی) پرهیز کنید.</p>
                                <p class="mb-0">سوالات خود را در بخش «پرسش و پاسخ» مطرح کنید.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
