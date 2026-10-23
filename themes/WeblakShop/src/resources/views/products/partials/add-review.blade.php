@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/bootstrap-slider.min.css') }}">

@endpush
<!-- Modal -->
<div class="modal fade" data-action="{{ route('front.reviews.show', ['product' => $product]) }}" id="add-product-review-modal" tabindex="-1" aria-labelledby="add-product-review-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header pb-0">
                <h5 class="modal-title" id="price-changes-modal-label">افزودن نظر</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>{{ $product->title }}</p>
                <hr>
                <div class="row comments-add-col--content">
                    <div class="col-md-6 col-sm-12">
                        <div class="form-ui">
                            <form id="add-product-review-form" action="{{ route('front.reviews.store') }}" class="px-2" method="post">
                                {{-- فاز ۴۴ — حفاظت ربات‌گیر (تلهٔ زمان/هانی‌پات) + کپچای اختیاری --}}
                                {!! \App\Services\CommentProtection::hiddenFields() !!}
                                @if(\App\Services\SecuritySettings::captchaEnabled('comments'))
                                <div class="row pr-4 mb-2">
                                    <x-captcha />
                                </div>
                                @endif
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-row-title mb-2"> امتیاز دهید!<span class="text-danger">*</span></div>
                                        <div class="product-review-rate">
                                            <div class="col-sm-12 col-12 mb-3">
                                                <div class="comments-product-attributes-title">
                                                   </div>
                                                <input name="rating" style="width: 100%" id="ex1" type="text" data-provide="slider"
                                                       data-slider-ticks="[ 0, 1, 2, 3, 4, 5]"
                                                       data-slider-ticks-labels='["","خیلی بد", "بد", "معمولی","خوب","عالی"]'
                                                       data-slider-min="1" data-slider-max="6" data-slider-step="1"
                                                       data-slider-value="0" data-slider-tooltip="show" />
                                            </div>
                                        </div>
                                    </div>
                                    @if (auth('web')->user()->hasBoughtProduct($product))
                                        <div class="col-12 mt-3">
                                            <div class="product-offer-question">
                                                <div class="form-row-title mb-3">خرید این محصول را به دیگران ...</div>
                                                <div class="product-review-suggest d-flex">

                                                    <div>
                                                        <input class="hide" type="radio" id="review-suggest-yes" name="suggest" value="yes">
                                                        <div class="review-suggest-item">
                                                            <label for="review-suggest-yes">
                                                                <i class="mdi mdi-thumb-up-outline"></i>
                                                                <p> پیشنهاد می کنم</p>
                                                            </label>
                                                        </div>
                                                    </div>

                                                <div>
                                                    <input class="hide" type="radio" id="review-suggest-not-sure" name="suggest" value="not_sure">
                                                    <div class="review-suggest-item">
                                                        <label for="review-suggest-not-sure">
                                                            <i class="mdi mdi-help"></i>
                                                            <p> مطمئن نیستم</p>
                                                        </label>
                                                    </div>
                                                </div>


                                                    <div>
                                                        <input class="hide" type="radio" id="review-suggest-no" name="suggest" value="no">
                                                        <div class="review-suggest-item">
                                                            <label  for="review-suggest-no">
                                                                <i class="mdi mdi-thumb-down-outline"></i>
                                                                <p> پیشنهاد نمی کنم</p>
                                                            </label>
                                                        </div>
                                                    </div>


                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="col-12">
                                        <div class="form-row-title mb-2">عنوان نظر شما
                                            (اجباری)
                                            <span class="text-danger">*</span></div>
                                        <div class="form-row">
                                            <input class="input-ui pr-2" name="title" type="text" placeholder="عنوان نظر خود را بنویسید" required>
                                        </div>
                                    </div>
                                    <div class="col-12 form-comment-title--positive mt-2">
                                        <div class="form-row-title mb-2 pr-2">
                                            نقاط قوت
                                        </div>
                                        <div id="advantages" class="form-row">
                                            <div class="ui-input--add-point">
                                                <input class="input-ui pr-2 ui-input-field" type="text" id="advantage-input" autocomplete="off" value="">
                                                <button class="ui-input-point js-icon-form-add" type="button"></button>
                                            </div>
                                            <div class="form-comment-dynamic-labels js-advantages-list"></div>
                                        </div>
                                    </div>
                                    <div class="col-12 form-comment-title--negative mt-2">
                                        <div class="form-row-title mb-2 pr-2">
                                            نقاط ضعف
                                        </div>
                                        <div id="disadvantages" class="form-row">
                                            <div class="ui-input--add-point">
                                                <input class="input-ui pr-2 ui-input-field" type="text" id="disadvantage-input" autocomplete="off" value="">
                                                <button class="ui-input-point js-icon-form-add" type="button"></button>
                                            </div>
                                            <div class="form-comment-dynamic-labels js-disadvantages-list"></div>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="form-row-title mb-2">متن نظر شما
                                            (اجباری) <span class="text-danger">*</span></div>
                                        <div class="form-row">
                                            <textarea name="body" class="input-ui pr-2 pt-2" rows="5" style="height: auto" placeholder="متن خود را بنویسید" required></textarea>
                                        </div>
                                    </div>


                                    <div class="col-12 mt-2">
                                        <button class="btn btn-danger product-add-to-cart-btn comment-submit-btn" style="padding: 10px 41%;margin: 15px 5px;">
                                            ثبت نظر
                                        </button>
                                        <p class="d-block">با “ثبت نظر” موافقت خود را با
                                            <a class="border-bottom-dt "
                                               target="_blank">قوانین
                                                انتشار محتوا
                                            </a> در {{ option('info_site_title', 'او پی شاپ') }} اعلام می‌کنم.
                                        </p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        @if (option('dt_product_reviews_description'))
                            {!! option('dt_product_reviews_description') !!}
                        @else
                            <h3>دیگران را با نوشتن نظرات خود، برای انتخاب این محصول راهنمایی کنید.</h3>
                            <div class="desc-comment">
                                <p>لطفا پیش از ارسال نظر، خلاصه قوانین زیر را مطالعه کنید:</p>
                                <p>فارسی بنویسید و از کیبورد فارسی استفاده کنید. بهتر است از فضای خالی (Space)
                                    بیش‌از‌حدِ معمول، شکلک یا ایموجی استفاده نکنید و از کشیدن حروف یا کلمات با
                                    صفحه‌کلید بپرهیزید.</p>
                                <p>نظرات خود را براساس تجربه و استفاده‌ی عملی و با دقت به نکات فنی ارسال کنید؛
                                    بدون
                                    تعصب به محصول خاص، مزایا و معایب را بازگو کنید و بهتر است از ارسال نظرات
                                    چندکلمه‌‌ای خودداری کنید.</p>
                                <p>بهتر است در نظرات خود از تمرکز روی عناصر متغیر مثل قیمت، پرهیز کنید.</p>
                                <p>به کاربران و سایر اشخاص احترام بگذارید. پیام‌هایی که شامل محتوای توهین‌آمیز و
                                    کلمات نامناسب باشند، حذف می‌شوند.</p>
                                <p>از ارسال لینک‌های سایت‌های دیگر و ارایه‌ی اطلاعات شخصی خودتان مثل شماره تماس،
                                    ایمیل و آی‌دی شبکه‌های اجتماعی پرهیز کنید.</p>
                                <p>با توجه به ساختار بخش نظرات، از پرسیدن سوال یا درخواست راهنمایی در این بخش
                                    خودداری کرده و سوالات خود را در بخش «پرسش و پاسخ» مطرح کنید.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script src="{{theme_asset('js/bootstrap-slider.min.js')}}"></script>
    <script>
        $('input[name=rating]').val(3);
    </script>
@endpush
