{{-- تعرفه‌های ارسال — سانیار فاز J
    قرارداد SellerTariffController@index :
    $carrier (حاملِ فروشنده جاری) + $tariffs (paginate 20) --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'تعرفه‌های ارسال', 'active' => 'tariffs'])

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
                    <h5 class="fw-bold mb-1"><i class="bi bi-signpost-split main-color-one-color ms-1"></i>تعرفه‌های ارسال</h5>
                    <p class="font-13 text-muted mb-2">هزینه ارسال مرسوله‌ها را بر اساس نوع منطقه (درون یا برون استانی) و حداکثر وزن تعیین کنید.</p>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge rounded-pill bg-light text-dark border font-12">
                            <i class="bi bi-truck ms-1"></i>حامل: {{ $carrier->title }}
                        </span>
                        @if($carrier->carrige_forward)
                            <span class="badge rounded-pill warning-label font-12">پس‌کرایه — هزینه هنگام تحویل</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('seller.carriers.index') }}" class="btn btn-outline-secondary rounded-3 font-14">
                        <i class="bi bi-arrow-right ms-1"></i>بازگشت به حامل‌ها
                    </a>
                    <a href="{{ route('seller.tariffs.create', ['carrier' => $carrier]) }}" class="btn main-color-one-bg text-white rounded-3 font-14">
                        <i class="bi bi-plus-lg ms-1"></i>ایجاد تعرفه
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول تعرفه‌ها --}}
    <div class="content-box">
        <div class="container-fluid">
            <div class="py-3 border-bottom">
                <h6 class="fw-bold mb-0 font-14"><i class="bi bi-table main-color-one-color ms-1"></i>لیست تعرفه‌ها</h6>
            </div>
            @if($tariffs->count())
                <div class="table-responsive py-2">
                    <table class="table align-middle site-tabel mb-2">
                        <thead class="text-bg-dark bg-opacity-75 text-center">
                        <tr>
                            <th scope="col" style="width:60px;">ردیف</th>
                            <th scope="col">نوع منطقه</th>
                            <th scope="col">حداکثر وزن (گرم)</th>
                            <th scope="col">هزینه ارسال</th>
                            <th scope="col">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="text-center">
                        @foreach($tariffs as $tariff)
                            <tr>
                                <td class="font-13">{{ ($tariffs->currentPage() - 1) * $tariffs->perPage() + $loop->iteration }}</td>
                                <td>
                                    @if($tariff->type == 'within_province')
                                        <span class="badge rounded-pill success-label">درون استانی</span>
                                    @else
                                        <span class="badge rounded-pill warning-label">برون استانی</span>
                                    @endif
                                </td>
                                <td class="font-13" dir="ltr">{{ number_format($tariff->max_weight) }}</td>
                                <td class="font-13 fw-bold">{{ number_format($tariff->shipping_cost) }} <span class="font-11 text-muted">تومان</span></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('seller.tariffs.edit', ['tariff' => $tariff]) }}"
                                       class="btn btn-sm btn-outline-primary rounded-3 font-12"
                                       aria-label="ویرایش تعرفه حداکثر وزن {{ number_format($tariff->max_weight) }} گرم">
                                        <i class="bi bi-pencil"></i> ویرایش
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-3 font-12 sft-btn-delete"
                                            data-action="{{ route('seller.tariffs.destroy', ['tariff' => $tariff]) }}"
                                            data-bs-toggle="modal" data-bs-target="#sft-delete-modal"
                                            aria-label="حذف تعرفه حداکثر وزن {{ number_format($tariff->max_weight) }} گرم">
                                        <i class="bi bi-trash"></i> حذف
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="py-3">
                    {{ $tariffs->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-signpost-split main-color-one-color font-40 d-block mb-2"></i>
                    <p class="font-14 text-muted mb-3">برای این حامل هنوز تعرفه‌ای ثبت نشده است.</p>
                    <a href="{{ route('seller.tariffs.create', ['carrier' => $carrier]) }}" class="btn main-color-one-bg text-white rounded-3 font-14">
                        <i class="bi bi-plus-lg ms-1"></i>ایجاد تعرفه
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- مودال حذف تعرفه --}}
    <div class="modal fade" id="sft-delete-modal" tabindex="-1" aria-hidden="true" aria-labelledby="sft-delete-title">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-15" id="sft-delete-title">
                        <i class="bi bi-exclamation-triangle text-danger ms-1"></i>حذف تعرفه
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body font-14">
                    آیا از حذف این تعرفه مطمئن هستید؟ پس از حذف، برای این بازه وزنی هزینه ارسالی محاسبه نمی‌شود.
                </div>
                <form action="#" id="sft-tariff-delete-form" class="modal-footer">
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
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-tariffs.js') }}?v=1"
            data-saniar-tariffs="index"></script>
@endpush
