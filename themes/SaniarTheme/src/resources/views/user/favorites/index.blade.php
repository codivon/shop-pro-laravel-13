@extends('front::user.layouts.master')

@section('user-content')

    {{-- لیست علاقه مندی ها — ریسکین‌شده با کامپوننت محصولات قالب سانیار (favorite.html) --}}
    <div class="ui-boxs">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold">علاقه مندی ها</h4>
                    </div>
                </div>
                <div class="ui-box-item-desc">
                    @if($favorites->count())
                        <div class="product-list-row">
                            @foreach ($favorites as $favorite)
                                <div class="product-row">
                                    <div class="product-row-desc">
                                        <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}">
                                            <div class="product-row-desc-item">
                                                <div class="product-row-img">
                                                    <img src="{{ $favorite->product->image ? asset($favorite->product->image) : asset('/no-image-product.svg') }}"
                                                         alt="{{ $favorite->product->title }}" width="100">
                                                </div>
                                                <div class="product-row-title">
                                                    <h6>{{ $favorite->product->title }}</h6>
                                                    <div class="product-price">
                                                        <p>{{ $favorite->product->getLowestPrice() }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                        <div class="product-row-icon">
                                            <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}"
                                               class="btn btn-light btn-sm" title="مشاهده محصول"><i class="bi bi-eye"></i></a>
                                            <a class="btn btn-danger btn-sm favorite-remove-btn"
                                               data-action="{{ route('front.favorites.destroy', ['favorite' => $favorite]) }}"
                                               data-bs-toggle="modal" data-bs-target="#favorite-delete-modal"
                                               title="حذف از علاقه مندی ها"><i class="bi bi-trash text-white"></i></a>
                                            <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}"
                                               class="btn main-color-one-bg btn-sm" title="افزودن به سبد"><i class="bi bi-cart-plus text-white"></i></a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-muted py-4">چیزی برای نمایش وجود ندارد!</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="my-paginate mt-4">
        <nav aria-label="Page navigation">
            {{$favorites->links("pagination::bootstrap-4")}}
        </nav>
    </div>

@endsection

@push('scripts')
    @include('front::user.partials.favorite-delete-modal')
    <script src="{{ theme_asset('js/pages/user/jquery-bs5-modal-compat.js') }}"></script>
    <script src="{{ theme_asset('js/pages/favorites/index.js') }}"></script>
@endpush
