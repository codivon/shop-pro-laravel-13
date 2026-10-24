{{-- بازدیدهای اخیر — UserHistoryController@index : $products, $active --}}
@extends('front::user.layouts.master', ['title' => 'بازدیدهای اخیر'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    <div class="content-box">
        <div class="container-fluid">
            <div class="title-panel mb-3 d-flex align-items-baseline justify-content-between flex-wrap gap-2">
                <h2 class="font-16 mb-0">
                    <i class="bi bi-clock-history main-color-one-color me-1"></i>
                    بازدیدهای <span class="main-color-one-color">اخیر</span>
                </h2>
                <span class="font-12 text-muted">محصولاتی که اخیرا دیده‌اید</span>
            </div>

            @if(count($products))
                <div class="d-flex flex-column gap-3">
                    @foreach($products as $product)
                        <div class="d-flex align-items-center gap-3 saniar-mini-product flex-wrap">
                            <a href="{{ route('front.products.show', ['product' => $product]) }}" class="flex-shrink-0">
                                <img src="{{ $product->image ? asset($product->image) : asset('no-image-product.svg') }}"
                                     alt="{{ $product->title }}" loading="lazy" width="88" height="88" class="rounded-3">
                            </a>
                            <div class="flex-grow-1 min-w-0">
                                <a href="{{ route('front.products.show', ['product' => $product]) }}"
                                   class="d-block font-14 text-dark text-decoration-none fw-bold text-overflow-1">
                                    {{ $product->title }}
                                </a>
                                <div class="text-warning font-12 mt-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="bi {{ $i <= round($product->rating ?: 0) ? 'bi-star-fill' : 'bi-star' }}"></i>
                                    @endfor
                                </div>
                                <div class="font-14 main-color-one-color fw-bold mt-1">{{ $product->getLowestPrice() }}</div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('front.products.show', ['product' => $product]) }}"
                                   class="btn btn-sm border-0 main-color-one-bg text-white rounded-3 px-3">مشاهده کالا</a>
                                <button type="button" class="btn btn-sm btn-light border text-danger rounded-3 px-3 js-viewer-remove"
                                        data-action="{{ route('front.user.user-history.delete', ['slug' => $product->slug]) }}"
                                        data-bs-toggle="modal" data-bs-target="#viewerDeleteModal"
                                        aria-label="حذف از بازدیدها">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($products->hasPages())
                    <div class="my-paginate mt-4">
                        {{ $products->links('front::components.paginate') }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="bi bi-eye text-muted" style="font-size:3rem;"></i>
                    <h3 class="font-16 mt-3">چیزی برای نمایش وجود ندارد!</h3>
                    <p class="text-muted font-14 mb-3">با مرور محصولات، تاریخچه بازدید شما اینجا ثبت می‌شود.</p>
                    <a href="{{ route('front.products.index') }}" class="btn border-0 main-color-one-bg text-white rounded-3 px-4">
                        <i class="bi bi-grid me-1"></i> مشاهده محصولات
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- مودال حذف از بازدیدها --}}
    <div class="modal fade" id="viewerDeleteModal" tabindex="-1" aria-labelledby="viewerDeleteLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-16" id="viewerDeleteLabel">
                        <i class="bi bi-trash text-danger me-1"></i> حذف از بازدیدها
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <p class="font-14 mb-3">آیا از حذف این محصول از بازدیدهای اخیر اطمینان دارید؟</p>
                    <form id="viewer-remove-form" action="#" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="submit" class="btn btn-danger rounded-3 px-4">بله، حذف شود</button>
                            <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">لغو</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush
@push('scripts')
    <script src="{{ theme_asset('js/pages/user/panel.js') }}?v=1"></script>
@endpush
