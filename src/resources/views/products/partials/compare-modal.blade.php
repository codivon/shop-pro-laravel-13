{{-- مودال افزودن محصول برای مقایسه سانیار — $similar_products, $products (اختیاری), $current_url --}}
<div class="modal fade compare-modal" id="compareModal" tabindex="-1" role="dialog" aria-labelledby="compareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 overflow-hidden">
            <div class="modal-header">
                <h5 class="modal-title font-16" id="compareModalLabel">
                    <i class="bi bi-shuffle main-color-one-color me-1"></i>افزودن محصول برای مقایسه
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <form id="compare-products-form" action="{{ route('front.products.similar-compare') }}">
                    @csrf
                    @if (isset($products) && $products->count())
                        @foreach ($products as $product)
                            <input type="hidden" name="products[]" value="{{ $product->id }}">
                        @endforeach
                    @endif
                    <input type="hidden" name="current_url" value="{{ $current_url }}">

                    <div class="row g-2 mb-3">
                        <div class="col-lg-9 col-8">
                            <input type="text" class="form-control" name="search" placeholder="جستجوی محصول مشابه ..." required>
                        </div>
                        <div class="col-lg-3 col-4">
                            <button type="submit" class="btn main-color-one-bg text-white rounded-3 w-100">
                                <i class="bi bi-search me-1"></i>جستجو
                            </button>
                        </div>
                    </div>
                </form>
                <hr>
                <div class="row products-row g-3">
                    @if ($similar_products->count())
                        @foreach ($similar_products as $product)
                            <div class="col-lg-3 col-md-4 col-6">
                                <div class="compare-product content-box h-100">
                                    <a href="{{ $current_url . '/' . $product->id }}" class="d-block text-decoration-none p-2">
                                        <img class="w-100 rounded-3 mb-2" style="max-height:130px;object-fit:contain;"
                                             src="{{ $product->image ? asset($product->image) : asset('no-image-product.svg') }}"
                                             loading="lazy" alt="{{ $product->title }}">
                                        <p class="product-title font-13 text-dark mb-0">{{ \Illuminate\Support\Str::limit($product->title, 45) }}</p>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="col-12">
                            <p class="text-center text-muted py-3 mb-0">چیزی برای مقایسه یافت نشد!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
