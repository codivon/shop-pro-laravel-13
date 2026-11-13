@extends('front::user.layouts.master')

@section('user-content')
    <!-- Start Content -->
    <div class="col-lg-12 col-xs-12 pull-right">
        <div class="headline-profile">
            <span>نقد و نظرات</span>
        </div>
        <div class="">
        @if($comments->count())
            @foreach($comments as $comment)
        <div class="col-lg-12 col-md-12 col-xs-12 pull-right mb-2">
            <div class="profile-stats">
                <div class="profile-recent-fav profile-comments-fav">
                    <div class="profile-comment">
                        @if($comment->status == 'pending')
                            <span class="profile-comment-status-review text-warning-comment">منتظر تایید</span>
                        @elseif($comment->status == 'accepted')
                            <span class="profile-comment-status-review ">تایید شده</span>
                        @else
                            <span class="profile-comment-status-review text-danger-comment">تایید نشده</span>
                        @endif
                        <div class="profile-comment-thumb">
                            <div class="profile-comment-img">
                                <a href="{{ $comment->product->link() }}"><img src="{{  $comment->product->image ? asset($comment->product->image) : asset('/no-image-product.svg') }}" alt="{{ $comment->product->title }}"></a>
                            </div>
                            <div class="profile-comment-rating">
                                <p>امتیاز من به محصول</p>
                                <div class="rating-div">
                                    @for($i=1;$i<=$comment->rating;$i++)
                                        <span class="star-item"><i class="fa fa-star active"></i></span>
                                    @endfor
                                    <span class="star-item"><i class="fa fa-star"></i></span>
                                    <span class="star-item"><i class="fa fa-star"></i></span>
                                    <span class="star-item"><i class="fa fa-star"></i></span>
                                    <span class="star-item"><i class="fa fa-star"></i></span>
                                    <span class="star-item"><i class="fa fa-star"></i></span>

                                </div>
                            </div>
                        </div>
                        <div class="profile-comment-content">
                            <h4>
                                <a href="{{ $comment->product->link() }}"> {{ $comment->product->title }}</a>

                                <p><b>{{$comment->title}}</b><br>{!! nl2br(htmlentities($comment->body)) !!}</p>
                            </h4>
                            @if ($comment->points->where('type', 'positive')->count() or  $comment->points->where('type', 'negative')->count())
                                    <div class="content-expert-evaluation-positive">
                                        <ul>
                                            @foreach ($comment->points->where('type', 'positive') as $point)

                                                <li><b class="badge-positive">+</b>{{ $point->text }}</li>
                                            @endforeach
                                        </ul>

                                        <ul>
                                            @foreach ($comment->points->where('type', 'negative') as $point)

                                                <li>   <b class="badge-negative">-</b>{{ $point->text }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                            @endif
                        </div>
                                <ul class="profile-comment-actions">
                                    <li>
                                        <button class="btn-helpful">{{ $comment->likes_count  ? : 0}}
                                            <i class="fa fa-thumbs-o-up" aria-hidden="true"></i>
                                        </button>
                                        <button class="btn-helpful">{{ $comment->dislikes_count ? : 0  }}
                                            <i class="fa fa-thumbs-o-down" aria-hidden="true"></i>
                                        </button>
                                    </li>
                                    <li>
                                        <button class="remove-address-btn add-product-review-modal-btn" data-url="{{route('front.reviews.show',$comment->product->slug)}}" data-productId="{{$comment->product->id}}" data-toggle="modal" data-target="#edit-product-review-modal">ویرایش</button>
                                        <button class="remove-address-btn text-danger-comment comment-remove-btn" data-toggle="modal" data-target="#comment-delete-modal" data-action="{{ route('front.comments.destroy', ['comment' => $comment]) }}">حذف</button>
                                    </li>
                                </ul>

                    </div>
                </div>
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
        {{$comments->links("pagination::bootstrap-4")}}
    </div>
    @if($comments->count())
    @include('front::user.partials.edit-review',['product'=>$comment->product])
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
                        حذف نقد و نظرات
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"
                            aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <p>آیا تمایل به حذف این نظر دارید؟</p>

                            <div class="form-ui dt-sl">
                                <form id="comment-remove-form" action="#" method="POST">
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

    <script src="{{ theme_asset('js/pages/profile/comment.js') }}"></script>
@endpush
