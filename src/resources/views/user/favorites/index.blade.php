@extends('front::user.layouts.master', ['title' => 'علاقه‌مندی‌های من'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    <div class="content-box h-100">
        <div class="container-fluid p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap mb-4">
                <h1 class="font-18 mb-0">
                    <i class="bi bi-heart main-color-one-color me-1"></i>
                    لیست علاقه‌مندی‌ها
                </h1>
                <span class="badge bg-light text-dark font-12">({{ $favorites->total() }} محصول)</span>
            </div>

            @if($favorites->count())
                <div class="row g-3">
                    @foreach ($favorites as $favorite)
                        <div class="col-lg-4 col-md-6 col-6">
                            <div class="content-box h-100 position-relative">
                                <div class="container-fluid p-3">
                                    <button type="button"
                                            class="btn btn-sm position-absolute top-0 start-0 m-1 text-danger rounded-circle bg-white shadow-sm favorite-remove-btn"
                                            data-action="{{ route('front.favorites.destroy', ['favorite' => $favorite]) }}"
                                            data-bs-toggle="modal" data-bs-target="#favoriteDeleteModal"
                                            aria-label="حذف از علاقه‌مندی‌ها" style="z-index:2;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}" class="d-block text-decoration-none">
                                        <div class="saniar-result-card border-0 px-0">
                                            <div class="src-thumb">
                                                <img src="{{ $favorite->product->image ? asset($favorite->product->image) : asset('no-image-product.svg') }}"
                                                     loading="lazy" alt="{{ $favorite->product->title }}">
                                            </div>
                                            <div class="src-title" title="{{ $favorite->product->title }}">{{ \Illuminate\Support\Str::limit($favorite->product->title, 40) }}</div>
                                            <div class="src-sub main-color-one-color fw-bold">{{ $favorite->product->getLowestPrice() }}</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $favorites->links('front::components.paginate') }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-heart text-muted" style="font-size:3rem;"></i>
                    <h5 class="font-16 mt-3">لیست علاقه‌مندی‌های شما خالی است</h5>
                    <p class="text-muted font-14">محصولات مورد علاقه خود را با کلیک روی آیکون قلب اضافه کنید</p>
                    <a href="{{ route('front.products.index') }}" class="btn border-0 main-color-one-bg text-white rounded-3 px-4">
                        <i class="bi bi-grid me-1"></i> مشاهده محصولات
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- مودال تأیید حذف --}}
    <div class="modal fade" id="favoriteDeleteModal" tabindex="-1" aria-labelledby="favoriteDeleteLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-16" id="favoriteDeleteLabel">
                        <i class="bi bi-trash text-danger me-1"></i> حذف از علاقه‌مندی‌ها
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <p class="font-14 mb-3">آیا از حذف این محصول از لیست علاقه‌مندی‌ها مطمئن هستید؟</p>
                    <form id="favorite-remove-form" action="#" method="POST">
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

@push('scripts')
    <script>
        // انتخاب محصول برای حذف و تنظیم اکشن فرم
        $(document).on('click', '.favorite-remove-btn', function () {
            $('#favorite-remove-form').attr('action', $(this).data('action'));
        });

        // ارسال فرم حذف به‌صورت AJAX
        $(document).on('submit', '#favorite-remove-form', function (e) {
            e.preventDefault();
            var form = $(this);
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function () {
                    location.reload();
                },
                error: function () {
                    alert('خطا در حذف؛ دوباره تلاش کنید.');
                }
            });
        });
    </script>
@endpush
