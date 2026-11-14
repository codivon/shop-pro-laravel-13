@extends('front::layouts.master')

@section('content')
    <!-- Start main-content -->
    <main class="main-content">

        <!-- start breadcroumb -->
        <div class="bread-crumb pt-3">
            <div class="container-fluid">
                <div class="content-box">
                    <div class="container-fluid">
                        <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('front.index') }}"
                                        class="font-14 text-muted">خانه</a></li>
                                <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">
                                    سبد خرید</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- end breadcroumb -->

        <!-- start content -->
        <div class="content">
            <div class="container-fluid">

                <div class="line-step-container d-sm-block d-none">
                    <div class="line-step">
                        <div class="line-step-boxs">
                            <div class="line-step-box complete">
                                <a href="{{ route('front.cart') }}">
                                    <p>سبد خرید</p>
                                    <div class="icon">1</div>
                                </a>
                            </div>
                            <div class="line-step-box disabled">
                                <a href="{{ route('front.checkout') }}">
                                    <p>جزییات پرداخت</p>
                                    <div class="icon">2</div>
                                </a>
                            </div>
                            <div class="line-step-box disabled">
                                <a href="javascript:void(0)">
                                    <p>تکمیل سفارش</p>
                                    <div class="icon">3</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- فاز ۱۶۱ — کل ناحیهٔ سبد در یک پارشال است تا پس از تغییرات
                 ای‌جکسی (تعداد/حذف) بدون رفرش صفحه جایگزین شود --}}
            @if($cart && $cart->products()->count())
                @include('front::carts.partials.cart-page-area', ['cart' => $cart])
            @else
                {{-- سبد خرید خالی — مطابق empty-cart.html قالب سانیار --}}
                @include('front::carts.partials.cart-empty-area')
            @endif

        </div>
        <!-- end content -->
    </main>
    <!-- End main-content -->

    <!-- Start delete modal -->
    <div class="modal fade" id="delete-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">
                        <i class="bi bi-trash3 main-color-one-color me-1"></i>
                        حذف محصول
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>آیا تمایل به حذف این محصول از سبدخرید دارید؟</p>
                    <form id="delete-form" action="#" method="POST"
                        class="d-flex align-items-center justify-content-center gap-2 mb-0">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn btn-danger rounded-3">بله حذف شود</button>
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">لغو</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End delete modal -->
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/carts/cart.js') }}?v=161"></script>
    <script src="{{ theme_asset('js/pages/cart.js') }}?v=5"></script>
@endpush
