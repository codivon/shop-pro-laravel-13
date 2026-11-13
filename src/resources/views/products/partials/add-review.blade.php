@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/theme-product.css') }}?v=1">
@endpush

{{-- ============================================================
     فاز ۱۵۵ — مودال «افزودن نظر» با طراحی سانیار
     (وایت کارت، هدر با دکمهٔ بستن، امتیاز ستاره‌ای، اینپوت با حلقهٔ فوکوس،
      دکمهٔ ثبت آبی main-color-one-bg)
     نکتهٔ مهم: ساختار فیلدها و شناسه‌ها عیناً حفظ شده تا اسکریپت‌های
     show.js (سابمیت ایجکسی فرم + پرکردن ویرایش نظر) بدون تغییر کار کنند:
     #add-product-review-modal (data-action) / #add-product-review-form /
     name=rating|suggest|title|body / #advantages+#advantage-input /
     #disadvantages+#disadvantage-input / js-advantages-list,js-disadvantages-list
     ============================================================ --}}

<div class="modal fade review-modal" data-action="{{ route('front.reviews.show', ['product' => $product]) }}"
     id="add-product-review-modal" tabindex="-1" aria-labelledby="add-product-review-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="add-product-review-label">
                    ثبت نظر جدید
                    <small>{{ $product->title }}</small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <div class="row gy-4">
                    <div class="col-lg-7 col-md-6 col-12">
                        <form id="add-product-review-form" action="{{ route('front.reviews.store') }}" method="post">
                            {{-- فاز ۴۴ — حفاظت ربات‌گیر (تلهٔ زمان/هانی‌پات) + کپچای اختیاری --}}
                            {!! \App\Services\CommentProtection::hiddenFields() !!}
                            @if(\App\Services\SecuritySettings::captchaEnabled('comments'))
                                <div class="row mb-3">
                                    <x-captcha />
                                </div>
                            @endif
                            <input type="hidden" name="product_id" value="{{ $product->id }}">

                            {{-- امتیاز ستاره‌ای — رادیوهای مخفی + ستاره‌های پوستی (فاز ۱۵۵) --}}
                            <div class="mb-4">
                                <div class="form-row-title">امتیاز شما به این کالا<span class="text-danger">*</span></div>
                                <div class="d-flex align-items-center flex-wrap">
                                    {{-- ترتیب نزولی input/label + direction:ltr در CSS —
                                         ستارهٔ ۱ سمت راست (RTL) و سلکتور «~» تا ستارهٔ انتخابی پر می‌کند --}}
                                    <div class="review-star-rating" role="radiogroup" aria-label="امتیاز از ۵">
                                        @for ($i = 5; $i >= 1; $i--)
                                            <input type="radio" name="rating" id="review-star-input-{{ $i }}"
                                                   value="{{ $i }}" required>
                                            <label class="review-star" for="review-star-input-{{ $i }}"
                                                   data-value="{{ $i }}" data-text="{{ [1 => 'خیلی بد', 2 => 'بد', 3 => 'معمولی', 4 => 'خوب', 5 => 'عالی'][$i] }}"
                                                   aria-label="{{ $i }} ستاره از ۵">
                                                <i class="bi bi-star-fill"></i>
                                            </label>
                                        @endfor
                                    </div>
                                    <span class="review-rating-text" id="review-rating-text"></span>
                                </div>
                            </div>

                            @if (auth('web')->user()->hasBoughtProduct($product))
                                {{-- پیشنهاد به دیگران — ساختار و شناسه‌های قدیمی حفظ شده (review-suggest-*) --}}
                                <div class="mb-4">
                                    <div class="form-row-title">خرید این محصول را به دیگران ...</div>
                                    <div class="product-review-suggest d-flex flex-wrap gap-2">
                                        <div class="review-suggest-item">
                                            <input class="d-none" type="radio" id="review-suggest-yes" name="suggest" value="yes">
                                            <label for="review-suggest-yes">
                                                <i class="mdi mdi-thumb-up-outline"></i>
                                                <span>پیشنهاد می‌کنم</span>
                                            </label>
                                        </div>
                                        <div class="review-suggest-item">
                                            <input class="d-none" type="radio" id="review-suggest-not-sure" name="suggest" value="not_sure">
                                            <label for="review-suggest-not-sure">
                                                <i class="mdi mdi-help"></i>
                                                <span>مطمئن نیستم</span>
                                            </label>
                                        </div>
                                        <div class="review-suggest-item">
                                            <input class="d-none" type="radio" id="review-suggest-no" name="suggest" value="no">
                                            <label for="review-suggest-no">
                                                <i class="mdi mdi-thumb-down-outline"></i>
                                                <span>پیشنهاد نمی‌کنم</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="mb-3">
                                <div class="form-row-title">عنوان نظر شما (اجباری)<span class="text-danger">*</span></div>
                                <input class="input-ui" name="title" type="text"
                                       placeholder="عنوان نظر خود را بنویسید" required>
                            </div>

                            {{-- نقاط قوت/ضعف — ساختار و شناسه‌های قدیمی حفظ شده (هوک main.js/show.js) --}}
                            <div class="row gy-3 mb-1">
                                <div class="col-md-6 col-12">
                                    <div class="form-row-title">نقاط قوت</div>
                                    <div id="advantages" class="form-row">
                                        <div class="ui-input--add-point">
                                            <input class="input-ui ui-input-field" type="text" id="advantage-input"
                                                   autocomplete="off" value="" placeholder="یک نقطه قوت بنویسید">
                                            <button class="ui-input-point js-icon-form-add" type="button"
                                                    aria-label="افزودن"></button>
                                        </div>
                                        <div class="form-comment-dynamic-labels js-advantages-list"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-row-title">نقاط ضعف</div>
                                    <div id="disadvantages" class="form-row">
                                        <div class="ui-input--add-point">
                                            <input class="input-ui ui-input-field" type="text" id="disadvantage-input"
                                                   autocomplete="off" value="" placeholder="یک نقطه ضعف بنویسید">
                                            <button class="ui-input-point js-icon-form-add" type="button"
                                                    aria-label="افزودن"></button>
                                        </div>
                                        <div class="form-comment-dynamic-labels js-disadvantages-list"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-row-title">متن نظر شما (اجباری)<span class="text-danger">*</span></div>
                                <textarea name="body" class="input-ui" rows="5"
                                          placeholder="متن خود را بنویسید" required></textarea>
                            </div>

                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <button type="submit" class="comment-submit-btn">
                                    <i class="bi bi-send"></i>
                                    ثبت نظر
                                </button>
                            </div>
                            <p class="review-disclaimer">با «ثبت نظر» موافقت خود را با
                                <a target="_blank">قوانین انتشار محتوا</a>
                                در {{ option('info_site_title', 'او پی شاپ') }} اعلام می‌کنم.
                            </p>
                        </form>
                    </div>

                    <div class="col-lg-5 col-md-6 col-12">
                        <aside class="review-guideline">
                            @if (option('dt_product_reviews_description'))
                                {!! option('dt_product_reviews_description') !!}
                            @else
                                <h3>دیگران را با نوشتن نظرات خود، برای انتخاب این محصول راهنمایی کنید.</h3>
                                <p>لطفا پیش از ارسال نظر، خلاصه قوانین زیر را مطالعه کنید:</p>
                                <p>فارسی بنویسید و از کیبورد فارسی استفاده کنید. بهتر است از فضای خالی (Space)
                                    بیش‌ازحدِ معمول، شکلک یا ایموجی استفاده نکنید و از کشیدن حروف یا کلمات با
                                    صفحه‌کلید بپرهیزید.</p>
                                <p>نظرات خود را براساس تجربه و استفاده‌ی عملی و با دقت به نکات فنی ارسال کنید؛
                                    بدون تعصب به محصول خاص، مزایا و معایب را بازگو کنید و بهتر است از ارسال نظرات
                                    چندکلمه‌ای خودداری کنید.</p>
                                <p>بهتر است در نظرات خود از تمرکز روی عناصر متغیر مثل قیمت، پرهیز کنید.</p>
                                <p>به کاربران و سایر اشخاص احترام بگذارید. پیام‌هایی که شامل محتوای توهین‌آمیز و
                                    کلمات نامناسب باشند، حذف می‌شوند.</p>
                                <p>از ارسال لینک‌های سایت‌های دیگر و ارایه‌ی اطلاعات شخصی خودتان مثل شماره تماس،
                                    ایمیل و آی‌دی شبکه‌های اجتماعی پرهیز کنید.</p>
                                <p>با توجه به ساختار بخش نظرات، از پرسیدن سوال یا درخواست راهنمایی در این بخش
                                    خودداری کرده و سوالات خود را در بخش «پرسش و پاسخ» مطرح کنید.</p>
                            @endif
                        </aside>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
