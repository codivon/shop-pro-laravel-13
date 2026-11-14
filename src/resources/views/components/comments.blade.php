{{-- =====================================================================
    فاز ۱۵۸ — بازطراحی پرسش و پاسخ محصول (فرم + فهرست)
    فرم مدرن با سربرگ، جای‌نگهدار و دکمهٔ آیکون‌دار
    کلاس‌های متصل به JS (comments.js) عیناً حفظ شده‌اند:
    #comments-form / .comment-replay-to / input[name=comment_id] /
    textarea[name=body] / .comment-submit-btn
    ===================================================================== --}}
<div class="qa-section-head">
    <div class="qa-section-head-icon"><i class="bi bi-patch-question"></i></div>
    <div>
        <span class="qa-section-head-title">پرسش و پاسخ</span>
        <span class="qa-section-head-sub">سوال خود را دربارهٔ «{{ $product->title }}» بپرسید</span>
    </div>
</div>

@auth

<form id="comments-form" action="{{ $route_link }}" method="post" class="form-faq qa-form">
    <div class="form-faq-row comment--form">
        <div class="comment-replay-to" style="display: none">
            <div>
                <span></span> <a href="javasript:void(0)"><i class="bi bi-x-lg"></i> لغو پاسخ</a>
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
            <div class="qa-form-avatar"><i class="bi bi-chat-square-dots"></i></div>
            <div class="ui-textarea">
                                                  <textarea name="body" title="متن سوال"
                                                            placeholder="پرسش خود را به‌صورت واضح و کامل بنویسید…"
                                                            class="ui-textarea-field" required></textarea>
            </div>
        </div>
    </div>
    <div class="form-faq-row">
        <div class="form-faq-col form-faq-col-submit">
            <button  type="submit" class="btn-tertiary comment-submit-btn"><i class="bi bi-send"></i> ثبت پرسش</button>
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
    <a href="{{ route('login') }}" class="qa-login-hint">
        <i class="bi bi-info-circle"></i>
        برای ثبت پرسش و پاسخ، باید وارد حساب کاربری شوید
        <i class="bi bi-arrow-left"></i>
    </a>

@endauth
@if(count($questions))
<div class="comments-summary">
    <div class="col-lg-12 col-md-12 col-xs-12 pull-left">
        <div class="comments-filter qa-filter">
            <div class="filter-item-main">
                <ul class="filter-items nav nav-tabs" id="myTab" role="tablist">
                    <li>
                                                        <span class="sort-row-text"><i class="bi bi-funnel"></i>
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
@else
    <div class="qa-empty">
        <i class="bi bi-inbox"></i>
        <span>{{ $message ?? 'هنوز پرسشی برای این محصول ثبت نشده است. اولین نفر باشید!' }}</span>
    </div>
@endif
