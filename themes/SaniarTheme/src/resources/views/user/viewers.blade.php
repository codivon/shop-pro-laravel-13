@extends('front::user.layouts.master')

@push('styles')
@endpush

@section('user-content')
    <div class="col-lg-12 col-xs-12 pull-right">
        <div class="headline-profile">
            <span>بازدیدهای اخیر</span>
        </div>
        @if(count($products))
        <div class="profile-user-history">
            <ul>
                @foreach($products as $product)

                    <li>
                        <div class="user-history-list-item">
                            <a href="{{ route('front.products.show', ['product' => $product]) }}"><img src="{{  $product->image ? asset($product->image) : asset('/no-image-product.svg') }}" alt="{{ $product->title }}"></a>
                        </div>

                        <div class="user-history-list-item-content">
                            <a href="{{ route('front.products.show', ['product' => $product]) }}">{{ $product->title }}</a>

                            <div class="stars rating-div" style="width: 83px;">
                                @for($i=1;$i<=$product->rating;$i++)
                                    <span class="star-item"><i class="fa fa-star active"></i></span>
                                @endfor
                                <span class="star-item"><i class="fa fa-star"></i></span>
                                <span class="star-item"><i class="fa fa-star"></i></span>
                                <span class="star-item"><i class="fa fa-star"></i></span>
                                <span class="star-item"><i class="fa fa-star"></i></span>
                                <span class="star-item"><i class="fa fa-star"></i></span>

                            </div>

                            <div class="user-history-list-item-content-container">
                                <div class="new-price">
                                    <span class="new-price-value">{{ $product->getLowestPrice() }}</span>

                                </div>
                            </div>

                            <div class="user-history-list-item-button-group">
                                <a href="{{ route('front.products.show', ['product' => $product]) }}" class="history-same-product-modal" >مشاهده کالا</a>
                                <a class="remove-item-profile-history viewer-remove-btn cursor-pointer" data-toggle="modal" data-target="#comment-delete-modal" data-action="{{ route('front.user.user-history.delete', ['slug' => $product->slug]) }}"><i class="fa fa-trash"></i></a>
                            </div>
                        </div>
                    </li>

                @endforeach
            </ul>
        </div>
        @else


            <div class="profile-stats">
                <div class="row">
                    <div class="col-12">
                        <div class="page dt-sl dt-sn pt-3">
                            <p class="text-center">چیزی برای نمایش وجود ندارد!</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    @if ($products->links()->paginator->hasPages())
    <div class="pager pager-back-none">
        {{$products->links()}}
    </div>
    @endif

@endsection

@push('scripts')
    <!-- Start favorite delete -->
    <div class="modal fade" id="comment-delete-modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="now-ui-icons location_pin"></i>
                        حذف از بازدیدها
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"
                            aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <p>آیا از حذف این محصول از بازدیدهای اخیر اطمینان دارید؟</p>

                            <div class="form-ui dt-sl">
                                <form id="viewers-remove-form" action="#" method="POST">
                                    <div class="modal-body text-center">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-danger btn-md">بله حذف شود</button>
                                        <button class="btn btn-light" data-dismiss="modal">لغو</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End favorite delete -->

    <script src="{{ theme_asset('js/pages/profile/viewers.js') }}"></script>
@endpush
