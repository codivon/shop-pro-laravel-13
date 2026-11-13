@extends('front::user.layouts.master')

@section('user-content')

    {{-- نقد و نظرات — ریسکین‌شده با کامپوننت دیدگاه‌های قالب سانیار (comments.html) --}}
    <div class="ui-boxs">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold">دیدگاه های من</h4>
                    </div>
                </div>
                <div class="ui-box-item-desc">
                    @if($comments->count())
                        <div class="orders">
                            @foreach($comments as $comment)
                                <div class="order-item">
                                    <div class="order-item-status">
                                        <div class="order-item-status-item">
                                            <a href="{{ $comment->product->link() }}"><img src="{{ $comment->product->image ? asset($comment->product->image) : asset('/no-image-product.svg') }}" alt="{{ $comment->product->title }}" width="80"></a>
                                        </div>
                                        <div class="order-item-status-item dropd-status">
                                            <div class="dropdown">
                                                @if($comment->status == 'pending')
                                                    <span class="bg-warning badge">منتظر تایید</span>
                                                @elseif($comment->status == 'accepted')
                                                    <span class="bg-success badge">تایید شده</span>
                                                @else
                                                    <span class="bg-danger badge">تایید نشده</span>
                                                @endif
                                                <a class="" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots-vertical text-dark fs-5"></i>
                                                </a>

                                                <ul class="dropdown-menu flex-column">
                                                    <li>
                                                        <button class="dropdown-item add-product-review-modal-btn" data-url="{{route('front.reviews.show',$comment->product->slug)}}" data-productId="{{$comment->product->id}}" data-bs-toggle="modal" data-bs-target="#edit-product-review-modal"><i class="bi bi-pencil"></i> ویرایش</button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger comment-remove-btn" data-bs-toggle="modal" data-bs-target="#comment-delete-modal" data-action="{{ route('front.comments.destroy', ['comment' => $comment]) }}"><i class="bi bi-trash text-danger"></i> حذف</button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-item-detail border-0">
                                        <div class="comment-item">
                                            <div class="comment-item-desc">
                                                <div class="comment-item-title">
                                                    <div class="product-row-title">
                                                        <h6>
                                                            <a href="{{ $comment->product->link() }}"> {{ $comment->product->title }}</a>
                                                        </h6>
                                                        <p class="fw-bold mb-0">{{$comment->title}}</p>
                                                    </div>
                                                    <div class="comment-item-rating">
                                                        <p class="mb-0 text-muted font-12">امتیاز من به محصول</p>
                                                        <div class="comment-item-rating">
                                                            @for($i=1;$i<=$comment->rating;$i++)
                                                                <i class="bi bi-star-fill text-warning"></i>
                                                            @endfor
                                                            @for($i=$comment->rating;$i<5;$i++)
                                                                <i class="bi bi-star text-muted"></i>
                                                            @endfor
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="comment-item-content">
                                                    <p>{!! nl2br(htmlentities($comment->body)) !!}</p>
                                                </div>
                                                @if ($comment->points->where('type', 'positive')->count() or  $comment->points->where('type', 'negative')->count())
                                                    <div class="d-flex flex-wrap gap-4 mt-2">
                                                        <ul class="list-unstyled mb-0">
                                                            @foreach ($comment->points->where('type', 'positive') as $point)
                                                                <li class="text-success"><b>+</b> {{ $point->text }}</li>
                                                            @endforeach
                                                        </ul>

                                                        <ul class="list-unstyled mb-0">
                                                            @foreach ($comment->points->where('type', 'negative') as $point)
                                                                <li class="text-danger"><b>-</b> {{ $point->text }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-muted py-4">چیزی برای نمایش وجود ندارد!</p>
                    @endif

                    <div class="my-paginate mt-4">
                        <nav aria-label="Page navigation">
                            {{$comments->links("pagination::bootstrap-4")}}
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($comments->count())
        @include('front::user.partials.edit-review',['product'=>$comment->product])
    @endif
@endsection

@push('scripts')
    {{-- Start comment delete --}}
    <div class="modal fade" id="comment-delete-modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-geo-alt"></i>
                        حذف نقد و نظرات
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <p>آیا تمایل به حذف این نظر دارید؟</p>

                            <form id="comment-remove-form" action="#" method="POST">
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
    {{-- End comment delete --}}

    <script src="{{ theme_asset('js/pages/user/jquery-bs5-modal-compat.js') }}"></script>
    <script src="{{ theme_asset('js/pages/profile/comment.js') }}"></script>
@endpush
