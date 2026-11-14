{{-- مودال افزودن محصول برای مقایسه — بوت‌استرپ ۵ --}}
<div class="modal fade compare-modal" id="compareModal" tabindex="-1" aria-labelledby="compareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="compareModalLabel">افزودن محصول برای مقایسه</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <form id="compare-products-form" action="{{ route('front.products.similar-compare') }}">
                    @csrf
                    @foreach ($products as $product)
                        <input type="hidden" name="products[]" value="{{ $product->id }}">
                    @endforeach
                    <input type="hidden" name="current_url" value="{{ $current_url }}">

                    <div class="row g-2">
                        <div class="col-12 col-md-9">
                            <input type="text" class="form-control" name="search" placeholder="جستجو..." required>
                        </div>
                        <div class="col-12 col-md-3">
                            <button type="submit" class="btn w-100 border-0 main-color-one-bg">جستجو کن</button>
                        </div>
                    </div>
                </form>
                <hr>
                <div class="row g-3 products-row">
                    @if ($similar_products->count())
                        @foreach ($similar_products as $product)
                            <div class="col-md-3 col-6">
                                <div class="compare-product shadow-box rounded-3 p-2 text-center">
                                    <a href="{{ $current_url . '/' . $product->id }}">
                                        {{-- فاز ۱۵۶ — تصویر با src مستقیم؛ data-src توسط lazyloadxt
                                             در مودال‌های مخفی هیچ‌وقت تعویض نمی‌شد و تصویر خالی می‌ماند --}}
                                        <img class="w-100 mb-2" loading="lazy" decoding="async"
                                             src="{{ $product->image ? asset($product->image) : asset('/no-image-product.svg') }}"
                                             alt="{{ $product->title }}">
                                        <p class="product-title font-14 text-overflow-1 mb-0">{{ $product->title }}</p>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="col-12">
                            <p class="text-center text-muted mb-0">چیزی برای مقایسه یافت نشد!</p>
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
</div>
