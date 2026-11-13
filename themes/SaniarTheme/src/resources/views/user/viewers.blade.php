@extends('front::user.layouts.master')

@section('user-content')

    {{-- بازدیدهای اخیر — ساختار کارکردی قبلی با کارت‌های قالب سانیار (favorite.html) --}}
    <div class="ui-boxs">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold">بازدیدهای اخیر</h4>
                    </div>
                </div>
                <div class="ui-box-item-desc">
                    @if(count($products))
                        <div class="product-list-row">
                            @foreach($products as $product)
                                <div class="product-row">
                                    <div class="product-row-desc">
                                        <a href="{{ route('front.products.show', ['product' => $product]) }}">
                                            <div class="product-row-desc-item">
                                                <div class="product-row-img">
                                                    <img src="{{ $product->image ? asset($product->image) : asset('/no-image-product.svg') }}"
                                                         alt="{{ $product->title }}" width="100">
                                                </div>
                                                <div class="product-row-title">
                                                    <h6>{{ $product->title }}</h6>
                                                    <div class="d-flex align-items-center gap-1">
                                                        @for($i=1;$i<=$product->rating;$i++)
                                                            <i class="bi bi-star-fill text-warning font-12"></i>
                                                        @endfor
                                                        @for($i=$product->rating;$i<5;$i++)
                                                            <i class="bi bi-star text-muted font-12"></i>
                                                        @endfor
                                                    </div>
                                                    <div class="product-price">
                                                        <p>{{ $product->getLowestPrice() }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                        <div class="product-row-icon">
                                            <a href="{{ route('front.products.show', ['product' => $product]) }}"
                                               class="btn main-color-one-bg btn-sm history-same-product-modal">مشاهده کالا</a>
                                            <a class="btn btn-danger btn-sm remove-item-profile-history viewer-remove-btn cursor-pointer"
                                               data-bs-toggle="modal" data-bs-target="#comment-delete-modal"
                                               data-action="{{ route('front.user.user-history.delete', ['slug' => $product->slug]) }}"
                                               title="حذف"><i class="bi bi-trash text-white"></i></a>
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

    @if ($products->links()->paginator->hasPages())
        <div class="my-paginate mt-4">
            <nav aria-label="Page navigation">
                {{$products->links()}}
            </nav>
        </div>
    @endif

@endsection

@push('scripts')
    {{-- Start viewers delete --}}
    <div class="modal fade" id="comment-delete-modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-geo-alt"></i>
                        حذف از بازدیدها
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <p>آیا از حذف این محصول از بازدیدهای اخیر اطمینان دارید؟</p>

                            <form id="viewers-remove-form" action="#" method="POST">
                                <div class="text-center">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-danger btn-md">بله حذف شود</button>
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">لغو</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- End viewers delete --}}

    <script src="{{ theme_asset('js/pages/user/jquery-bs5-modal-compat.js') }}"></script>
    <script src="{{ theme_asset('js/pages/profile/viewers.js') }}"></script>
@endpush
