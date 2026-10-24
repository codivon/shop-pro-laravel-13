{{-- فایل‌های محصول دانلودی سانیار — $product --}}
<div class="multi-seller">
    <div class="container-fluid">
        <div class="content-box">
            <div class="container-fluid">
                <div class="title-panel mb-3">
                    <h6 class="font-16 mb-0">
                        <i class="bi bi-file-earmark-arrow-down main-color-one-color me-1"></i>
                        فایل‌های محصول (دانلودی)
                    </h6>
                </div>

                <input id="cart-quantity" class="quantity" value="1" type="hidden">

                <div class="responsive-table p-0">
                    <table class="table main-table rounded-0 mb-0 saniar-stores-table">
                        <thead class="text-center">
                            <tr>
                                <th class="font-14">فایل</th>
                                <th class="font-14">حجم</th>
                                <th class="font-14">قیمت</th>
                                <th class="font-14"></th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @foreach ($product->prices()->whereHas('file')->orderBy('prices.ordering')->get() as $price)
                                <tr>
                                    <td class="font-14">
                                        <i class="bi bi-file-earmark-zip me-1 text-muted"></i>
                                        {{ $price->file->title }}
                                    </td>
                                    <td class="font-14 ltr" dir="ltr">{{ formatSizeUnits($price->file->size) }}</td>
                                    <td class="font-14">
                                        @if ($price->discount)
                                            <del class="text-danger font-12 d-block">{{ number_format($price->tomanPrice()) }} تومان</del>
                                        @endif
                                        <p class="fw-bold text-muted-2 mb-0 d-inline font-16">{{ number_format($price->discountPrice()) }}</p>
                                        <span class="text-muted font-12">تومان</span>
                                    </td>
                                    <td class="font-14">
                                        @guest
                                            <a href="{{ route('login', ['redirect' => route('front.products.show', ['product' => $product])]) }}"
                                               class="btn btn-sm btn-success rounded-3 px-3">
                                                <i class="bi bi-box-arrow-in-left me-1"></i>ورود و خرید
                                            </a>
                                        @else
                                            <button type="button"
                                                    class="btn btn-sm py-2 px-3 border-0 main-color-one-bg waves-effect waves-light rounded-3 js-btn-add-to-cart add-to-cart"
                                                    data-price_id="{{ $price->id }}"
                                                    data-action="{{ route('front.cart.store', ['product' => $product]) }}"
                                                    data-product="{{ $product->slug }}">
                                                <i class="bi bi-basket text-white font-14 me-1"></i>
                                                <span class="text-white font-14">افزودن به سبد</span>
                                            </button>
                                        @endguest
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
