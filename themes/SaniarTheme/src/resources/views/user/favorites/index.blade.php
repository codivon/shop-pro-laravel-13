@extends('front::user.layouts.master')

@section('user-content')
    <div class="col-lg-12 col-xs-12 pull-right">
        <div class="headline-profile">
            <span>لیست علاقه مندی</span>
        </div>
        <div class="profile-stats">
            @if($favorites->count())
                @foreach ($favorites as $favorite)
            <div class="profile-recent-fav profile-favorites-fav">
                <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}" class="img-profile-favorites"><img
                        src="{{  $favorite->product->image ? asset($favorite->product->image) : asset('/no-image-product.svg') }}" alt="{{ $favorite->product->title }}"></a>
                <div class="profile-recent-fav-col">
                    <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}" style="font-size:14px;">{{ $favorite->product->title }}</a>
                    <div class="profile-recent-fav-price">{{ $favorite->product->getLowestPrice() }}</div>
                    <div class="profile-recent-fav-remove favorite-remove-btn" data-action="{{ route('front.favorites.destroy', ['favorite' => $favorite]) }}"  data-toggle="modal" data-target="#favorite-delete-modal"><a><i class="fa fa-trash"></i></a></div>
                    <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}"  class="profile-wishlist">مشاهده محصول<i class="fa fa-angle-left"></i></a>
                </div>
            </div>
                @endforeach
            @else
                <div class="row">
                    <div class="col-12">
                        <div class="page dt-sl dt-sn pt-3">
                            <p class="text-center">چیزی برای نمایش وجود ندارد!</p>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <div class="pager pager-back-none">
        {{$favorites->links("pagination::bootstrap-4")}}
    </div>
@endsection

@push('scripts')
    <!-- Start favorite delete -->
    <div class="modal fade" id="favorite-delete-modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="now-ui-icons location_pin"></i>
                        حذف از لیست علاقمندی ها
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <p>آیا تمایل به حذف این محصول از لیست علاقمندی ها دارید؟</p>

                            <div class="form-ui dt-sl">
                                <form id="favorite-remove-form" action="#" method="POST">
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

    <script src="{{ theme_asset('js/pages/favorites/index.js') }}"></script>
@endpush
