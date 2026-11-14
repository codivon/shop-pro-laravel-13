{{-- ناحیهٔ اصلی سبد خرید در حالت خالی — برای رندر مجدد AJAX (فاز ۱۶۱) --}}
<div id="cart-main-area">
    <div class="container-fluid">
        <div class="content-box">
            <div class="container-fluid">
                <div class="text-center py-5">
                    <img src="{{ theme_asset('image/empty-cart.svg') }}" style="max-width: 400px;"
                        alt="سبد خرید خالی" class="mx-auto d-block img-fluid">
                    <h6 class="font-18 mt-4">سبد خرید شما خالی است!</h6>
                    <p class="text-muted font-14 mt-2 mb-4">
                        می‌توانید برای مشاهده محصولات بیشتر به صفحه اصلی بروید.
                    </p>
                    <a href="{{ route('front.index') }}"
                        class="btn main-color-one-bg border-0 rounded-3">صفحه اصلی</a>
                </div>
            </div>
        </div>
    </div>
</div>
