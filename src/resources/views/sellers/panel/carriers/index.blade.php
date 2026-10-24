{{-- حامل‌های من — سانیار فاز J
    قرارداد SellerCarrierController@index :
    $carriers (paginate 20 + eager: province/city) --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'حامل‌های من', 'active' => 'carriers'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=3">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller-carriers.css') }}?v=1">
@endpush

@section('seller-content')
    {{-- سربرگ صفحه --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-truck main-color-one-color ms-1"></i>حامل‌های من</h5>
                    <p class="font-13 text-muted mb-0">روش‌های ارسال اختصاصی فروشگاه شما؛ تعرفه‌ها و شهرهای تحت پوشش را از این بخش مدیریت کنید.</p>
                </div>
                <a href="{{ route('seller.carriers.create') }}" class="btn main-color-one-bg text-white rounded-3 font-14">
                    <i class="bi bi-plus-lg ms-1"></i>ایجاد روش ارسال
                </a>
            </div>
        </div>
    </div>

    {{-- جدول حامل‌ها --}}
    <div class="content-box">
        <div class="container-fluid">
            <div class="py-3 border-bottom">
                <h6 class="fw-bold mb-0 font-14"><i class="bi bi-table main-color-one-color ms-1"></i>لیست روش‌های ارسال</h6>
            </div>
            @if($carriers->count())
                <div class="table-responsive py-2 sfa-table-wrap">
                    <table class="table align-middle site-tabel mb-2">
                        <thead class="text-bg-dark bg-opacity-75 text-center">
                        <tr>
                            <th scope="col" style="width:60px;">ردیف</th>
                            <th scope="col">عنوان حامل</th>
                            <th scope="col">مرکز فروشگاه</th>
                            <th scope="col">شهرهای تحت پوشش</th>
                            <th scope="col">پس‌کرایه</th>
                            <th scope="col">وضعیت</th>
                            <th scope="col">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="text-center">
                        @foreach($carriers as $carrier)
                            <tr>
                                <td class="font-13">{{ ($carriers->currentPage() - 1) * $carriers->perPage() + $loop->iteration }}</td>
                                <td class="text-start text-nowrap">
                                    @if($carrier->image)
                                        <img src="{{ asset($carrier->image) }}" class="sfa-img-thumb" alt="تصویر روش ارسال {{ $carrier->title }}">
                                    @else
                                        <span class="sfa-img-thumb sfa-img-thumb--empty" aria-hidden="true"><i class="bi bi-truck font-16 text-muted"></i></span>
                                    @endif
                                    <span class="font-13 fw-bold">{{ $carrier->title }}</span>
                                </td>
                                <td class="font-13">{{ $carrier->province?->name }} — {{ $carrier->city?->name }}</td>
                                <td>
                                    @if($carrier->covered_cities == 'all')
                                        <span class="badge rounded-pill bg-secondary font-12">همه شهرها</span>
                                    @else
                                        <a href="{{ route('seller.carriers.cities', ['carrier' => $carrier]) }}"
                                           class="btn btn-sm btn-outline-secondary rounded-3 font-12 sfa-cities-show"
                                           aria-label="مشاهده شهرهای تحت پوشش {{ $carrier->title }}">
                                            <i class="bi bi-geo ms-1"></i>مشاهده شهرها
                                        </a>
                                    @endif
                                </td>
                                <td class="font-13">{{ $carrier->carrige_forward ? 'بله' : 'خیر' }}</td>
                                <td>
                                    @if($carrier->is_active)
                                        <span class="badge rounded-pill success-label">فعال</span>
                                    @else
                                        <span class="badge rounded-pill danger-label">غیرفعال</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary rounded-3 font-12 dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                aria-label="عملیات روش ارسال {{ $carrier->title }}">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu font-13">
                                            @if(!$carrier->carrige_forward)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('seller.tariffs.index', ['carrier' => $carrier]) }}">
                                                        <i class="bi bi-signpost-split ms-1"></i>تعرفه‌های ارسال
                                                    </a>
                                                </li>
                                            @else
                                                <li>
                                                    <span class="dropdown-item-text">
                                                        <span class="badge rounded-pill warning-label font-12">پس‌کرایه — بدون تعرفه</span>
                                                    </span>
                                                </li>
                                            @endif
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('seller.carriers.edit', ['carrier' => $carrier]) }}">
                                                    <i class="bi bi-pencil ms-1"></i>ویرایش
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger sfa-btn-delete"
                                                        data-action="{{ route('seller.carriers.destroy', ['carrier' => $carrier]) }}"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal">
                                                    <i class="bi bi-trash ms-1"></i>حذف
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="py-3">
                    {{ $carriers->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-truck main-color-one-color font-40 d-block mb-2"></i>
                    <p class="font-14 text-muted mb-3">هنوز روش ارسالی برای فروشگاه شما ثبت نشده است.</p>
                    <a href="{{ route('seller.carriers.create') }}" class="btn main-color-one-bg text-white rounded-3 font-14">
                        <i class="bi bi-plus-lg ms-1"></i>ایجاد روش ارسال
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- مودال حذف روش ارسال --}}
    <div class="modal fade" id="delete-modal" tabindex="-1" aria-hidden="true" aria-labelledby="sfa-carrier-delete-title">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-15" id="sfa-carrier-delete-title">
                        <i class="bi bi-exclamation-triangle text-danger ms-1"></i>حذف روش ارسال
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body font-14">
                    با حذف روش ارسال، تعرفه‌های آن نیز غیرفعال می‌شود و امکان بازیابی وجود ندارد.
                </div>
                <form action="#" id="sfa-carrier-delete-form" class="modal-footer">
                    @csrf
                    @method('delete')
                    <button type="button" class="btn btn-outline-secondary rounded-3 font-13" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-danger rounded-3 font-13">
                        <i class="bi bi-trash ms-1"></i>بله، حذف شود
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- مودال شهرهای تحت پوشش (محتوا با AJAX بارگذاری می‌شود) --}}
    <div class="modal fade" id="sfa-cities-modal" tabindex="-1" aria-hidden="true" aria-labelledby="sfa-cities-title">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-15" id="sfa-cities-title">
                        <i class="bi bi-geo main-color-one-color ms-1"></i>شهرهای تحت پوشش
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body" id="sfa-cities-list"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-carriers.js') }}?v=1"
            data-saniar-carriers="index"></script>
@endpush
