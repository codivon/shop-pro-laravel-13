<div class="faq-headline">پرسش و پاسخ
    <span>{{$product->title}}</span>
</div>
@auth

<form id="comments-form" action="{{ $route_link }}" method="post" class="form-faq">
    <div class="form-faq-row comment--form">
        <div class="comment-replay-to" style="display: none">
            <div>
                <span></span> <a href="javasript:void(0)"> لغو پاسخ</a>
            </div>

        </div>
        <input type="hidden" name="comment_id">
        {{-- فاز ۴۴ — حفاظت ربات‌گیر (تلهٔ زمان/هانی‌پات) + کپچای اختیاری --}}
        {!! \App\Services\CommentProtection::hiddenFields() !!}
        @if(\App\Services\SecuritySettings::captchaEnabled('comments'))
            <div class="row pr-4">
                <x-captcha />
            </div>
            @endif
        <div class="form-faq-col">
            <div class="ui-textarea">
                                                  <textarea name="body" title="متن سوال"
                                                            class="ui-textarea-field" required></textarea>
            </div>
        </div>
    </div>
    <div class="form-faq-row">
        <div class="form-faq-col form-faq-col-submit">
            <button  type="submit" class="btn-tertiary comment-submit-btn">ثبت پرسش</button>
        </div>
        <div class="account-agree">
            <label class="checkbox-primary">
                <span class="checkbox-check checkbox-custom-pic"></span>
                <input type="checkbox" id="accountAutoLogin" class="remember-checkbox">
            </label>
            <label for="accountAutoLogin" class="remember-me">
            </label>

        </div>

    </div>
</form>
@else
    <div class="alert alert-warning margin-top-1" style="margin-top: 15px" role="alert">
        برای شرکت در پرسش و پاسخ،
        باید وارد حساب کاربری شوید
    </div>

@endauth
@if(count($questions))
<div class="comments-summary">
    <div class="col-lg-12 col-md-12 col-xs-12 pull-left">
        <div class="comments-filter">
            <div class="filter-item-main">
                <ul class="filter-items nav nav-tabs" id="myTab" role="tablist">
                    <li>
                                                        <span class="sort-row-text"><i class="mdi mdi-sort"></i>
                                                            مرتب‌سازی
                                                            پرسش ها بر اساس:</span>
                    </li>
                    <li class="nav-item" data-id="new">
                        <a class="nav-link active " id="Newscomments-tab" data-toggle="tab"
                           role="tab" aria-controls="Newscomments"
                           aria-selected="true">جدیدترین پرسش ها</a>
                    </li>
                    <li class="nav-item" data-id="moreLike">
                        <a class="nav-link" id="Usefulcomments-tab" data-toggle="tab"
                            role="tab"
                           aria-controls="Usefulcomments" aria-selected="true">مفیدترین
                             پرسش ها</a>
                    </li>

                </ul>
            </div>
        </div>
        <div id="product-comment-list">
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="question-answer"
                     role="tabpanel" aria-labelledby="Buyerscomments-tab">
                  @include('front::components.question-answer-data')
                </div>
            </div>
        </div>
    </div>
</div>
@endif






