{{-- اتمام ثبت‌نام — مرحله ۳ (فاز G)
    POST: seller.registration_checkout_store → JSON {status, redirect} → ورود خودکار به داشبورد
    قرارداد SellerController@registration_checkout : $categories (دسته‌های دارای کمیسیون) --}}
@extends('front::sellers.auth.layouts.master', ['title' => 'اتمام ثبت‌نام', 'auth_step' => 3, 'auth_card_class' => 'col-lg-6'])

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-0 bg-white seller-auth-card text-center">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">تبریک!</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <div class="mb-3">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                  style="width:72px;height:72px;background: var(--main-color-three-shadow); color: var(--main-color-three); font-size: 2rem;">
                <i class="bi bi-hand-thumbs-up-fill"></i>
            </span>
        </div>

        <h5 class="fw-bold mb-3">اکنون شما به جمع فروشندگان {{ option('info_site_title', config('front.demo.name')) }} پیوستید.</h5>
        <p class="font-14 text-muted" style="line-height: 2;">
            پس از ورود به داشبورد پنل فروشندگان، با مراجعه به صفحه پروفایل می‌توانید نسخه آنلاین قرارداد و
            اطلاعات تجاری خود را مشاهده کنید. لطفا توجه کنید که نیازی به چاپ و ارسال قرارداد نیست.
        </p>

        <form id="seller-register-checkout" action="{{ route('seller.registration_checkout_store') }}"
              method="post" data-ajax novalidate>
            @csrf
            <input type="hidden" name="register[back_to_training]" value="1">
            <div class="form-group">
                <button type="submit" class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    ورود به پنل فروشندگان
                    <i class="bi bi-box-arrow-in-left ms-1"></i>
                </button>
            </div>
        </form>

        @if(count($categories))
            <button type="button" class="btn btn-outline-secondary rounded-3 mt-2 font-14" data-bs-toggle="modal" data-bs-target="#commission-modal">
                <i class="bi bi-table me-1"></i>مشاهده جدول کمیسیون
            </button>

            {{-- مودال جدول کمیسیون (داینامیک از دسته‌ها) --}}
            <div class="modal fade" id="commission-modal" tabindex="-1" aria-labelledby="commission-modal-label" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="commission-modal-label">
                                <i class="bi bi-percent main-color-one-color ms-1"></i>جدول کمیسیون دسته‌بندی‌ها
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="table-light">
                                    <tr>
                                        <th scope="col">عناوین دسته‌بندی‌ها</th>
                                        <th scope="col" class="text-center">درصد کمیسیون</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($categories as $category)
                                        <tr>
                                            <td>{{ $category->title }}</td>
                                            <td class="text-center fw-bold main-color-one-color">{{ $category->commission }}%</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="font-13 text-muted mb-0" style="line-height: 2;">
                                میزان کمیسیون متناسب با دسته‌بندی کالا تعیین می‌شود؛ با فروش در
                                {{ option('info_site_title', config('front.demo.name')) }} دیگر نیازی به پرداخت اجاره فروشگاه یا طراحی سایت ندارید.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
