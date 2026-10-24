{{-- نظرات من — CommentController@index : $comments (status pending/accepted/rejected, rating, title, body, points, likes_count, dislikes_count, product), $active --}}
@extends('front::user.layouts.master', ['title' => 'نظرات من'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    <div class="ui-boxs">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title p-3">
                    <h2 class="fw-bold font-16 mb-0">
                        <i class="bi bi-chat-dots main-color-one-color me-1"></i>
                        دیدگاه های <span class="main-color-one-color">من</span>
                    </h2>
                </div>
                <div class="ui-box-item-desc">
                    @if($comments->count())
                        <div class="orders d-flex flex-column gap-3">
                            @foreach($comments as $comment)
                                <div class="order-item">
                                    <div class="order-item-status">
                                        <div class="order-item-status-item">
                                            <a href="{{ $comment->product->link() }}">
                                                <img src="{{ $comment->product->image ? asset($comment->product->image) : asset('no-image-product.svg') }}"
                                                     alt="{{ $comment->product->title }}" width="80" height="80" class="rounded-3" loading="lazy">
                                            </a>
                                        </div>
                                        <div class="order-item-status-item dropd-status d-flex align-items-center gap-2">
                                            @if($comment->status == 'pending')
                                                <span class="badge bg-warning text-dark rounded-pill">منتظر تایید</span>
                                            @elseif($comment->status == 'accepted')
                                                <span class="badge bg-success rounded-pill">تایید شده</span>
                                            @else
                                                <span class="badge bg-danger rounded-pill">رد شده</span>
                                            @endif
                                            <div class="dropdown">
                                                <a href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                                                   aria-label="عملیات دیدگاه">
                                                    <i class="bi bi-three-dots-vertical text-dark fs-5"></i>
                                                </a>
                                                <ul class="dropdown-menu flex-column">
                                                    <li>
                                                        <button type="button" class="dropdown-item js-edit-review"
                                                                data-url="{{ route('front.reviews.show', ['product' => $comment->product->slug]) }}"
                                                                data-product-id="{{ $comment->product->id }}"
                                                                data-rating="{{ $comment->rating }}"
                                                                data-suggest="{{ $comment->suggest ?? '' }}"
                                                                data-title="{{ $comment->title }}"
                                                                data-body="{{ $comment->body }}"
                                                                data-points='@json($comment->points->map(fn($p) => ['text' => $p->text, 'type' => $p->type]))'>
                                                            <i class="bi bi-pencil"></i> ویرایش
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger js-comment-delete"
                                                                data-action="{{ route('front.comments.destroy', ['comment' => $comment]) }}"
                                                                data-bs-toggle="modal" data-bs-target="#commentDeleteModal">
                                                            <i class="bi bi-trash text-danger"></i> حذف
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-item-detail border-0">
                                        <div class="comment-item">
                                            <div class="comment-item-desc">
                                                <div class="comment-item-title d-flex justify-content-between align-items-center flex-wrap gap-1">
                                                    <div class="comment-date d-flex align-items-center gap-1 font-13 text-muted">
                                                        <span>{{ auth()->user()->username }}</span>
                                                        <span>–</span>
                                                        <span>{{ \Morilog\Jalali\Jalalian::fromDateTime($comment->created_at)->format('j F Y') }}</span>
                                                    </div>
                                                    <div class="comment-item-rating text-warning">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            <i class="bi {{ $i <= $comment->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                                        @endfor
                                                    </div>
                                                </div>
                                                <div class="comment-item-content mt-2">
                                                    <a href="{{ $comment->product->link() }}" class="font-14 fw-bold text-dark">
                                                        {{ $comment->product->title }}
                                                    </a>
                                                    <p class="font-14 mb-1 mt-1"><strong>{{ $comment->title }}</strong></p>
                                                    <p class="font-14 text-muted mb-0">{!! nl2br(e($comment->body)) !!}</p>
                                                    @if ($comment->points->where('type', 'positive')->count() || $comment->points->where('type', 'negative')->count())
                                                        <div class="content-expert-evaluation-positive mt-2">
                                                            <ul class="list-unstyled mb-1">
                                                                @foreach ($comment->points->where('type', 'positive') as $point)
                                                                    <li class="font-13"><b class="badge-positive text-success">+</b> {{ $point->text }}</li>
                                                                @endforeach
                                                            </ul>
                                                            <ul class="list-unstyled mb-0">
                                                                @foreach ($comment->points->where('type', 'negative') as $point)
                                                                    <li class="font-13"><b class="badge-negative text-danger">−</b> {{ $point->text }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 mt-2 font-12 text-muted">
                                        <span><i class="bi bi-hand-thumbs-up me-1"></i>{{ $comment->likes_count ?: 0 }}</span>
                                        <span><i class="bi bi-hand-thumbs-down me-1"></i>{{ $comment->dislikes_count ?: 0 }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="my-paginate mt-4">
                            {{ $comments->links('front::components.paginate') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-chat-square-text text-muted" style="font-size:3rem;"></i>
                            <h3 class="font-16 mt-3">دیدگاهی ثبت نکرده‌اید</h3>
                            <p class="text-muted font-14 mb-0">نظرات خود را درباره محصولات اینجا مدیریت کنید.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('front::user.partials.edit-review')

    {{-- مودال حذف دیدگاه --}}
    <div class="modal fade" id="commentDeleteModal" tabindex="-1" aria-labelledby="commentDeleteLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-16" id="commentDeleteLabel">
                        <i class="bi bi-trash text-danger me-1"></i> حذف دیدگاه
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <p class="font-14 mb-3">آیا تمایل به حذف این نظر دارید؟</p>
                    <form id="comment-remove-form" action="#" method="POST">
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
