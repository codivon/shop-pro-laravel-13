{{-- ویرایش تعرفه ارسال — سانیار فاز J
    قرارداد SellerTariffController@edit :
    $tariff (تعرفهٔ حاملِ فروشنده جاری — گارد assertSellerTariff)
    به‌روزرسانی: UpdateTariffRequest → پاسخ متنی «success» + توست سشن
    نکته: در WeblakShop فرم ویرایش به روت‌های ادمین (admin.tariffs.*) اشاره داشت →
    اینجا به روت‌های seller.tariffs.* اصلاح شد. --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'ویرایش تعرفه', 'active' => 'tariffs'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=3">
@endpush

@section('seller-content')
    {{-- سربرگ صفحه --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-pencil-square main-color-one-color ms-1"></i>ویرایش تعرفه</h5>
                    <p class="font-13 text-muted mb-2">بازه وزنی و هزینه ارسال این تعرفه را ویرایش کنید.</p>
                    <span class="badge rounded-pill bg-light text-dark border font-12">
                        <i class="bi bi-truck ms-1"></i>حامل: {{ $tariff->carrier?->title }}
                    </span>
                </div>
                <a href="{{ route('seller.tariffs.index', ['carrier' => $tariff->carrier_id]) }}" class="btn btn-outline-secondary rounded-3 font-14">
                    <i class="bi bi-arrow-right ms-1"></i>بازگشت به تعرفه‌ها
                </a>
            </div>
        </div>
    </div>

    <form class="form" id="sft-tariff-form" action="{{ route('seller.tariffs.update', ['tariff' => $tariff]) }}" method="post"
          data-redirect="{{ route('seller.tariffs.index', ['carrier' => $tariff->carrier_id]) }}">
        @csrf
        @method('put')
        <input type="hidden" name="carrier_id" value="{{ $tariff->carrier_id }}">

        <div class="content-box">
            <div class="container-fluid">
                <div class="title-panel mb-3">
                    <h2 class="font-16 mb-0"><i class="bi bi-cash-coin main-color-one-color me-1"></i>اطلاعات تعرفه</h2>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label font-13 fw-bold" for="sft-type">نوع منطقه ارسالی <span class="text-danger">*</span></label>
                        <select class="form-select font-13" id="sft-type" name="type" required>
                            <option value="within_province" @selected($tariff->type == 'within_province')>درون استانی</option>
                            <option value="extra_province" @selected($tariff->type == 'extra_province')>برون استانی</option>
                        </select>
                        <div class="font-12 text-muted mt-1">درون استانی: شهر مقصد در استان فروشگاه شماست.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-13 fw-bold" for="sft-max-weight">حداکثر وزن (گرم) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control font-13" id="sft-max-weight" name="max_weight"
                               min="1" inputmode="numeric" value="{{ $tariff->max_weight }}" required>
                        <div class="font-12 text-muted mt-1">اگر وزن مرسوله تا این مقدار باشد، این تعرفه اعمال می‌شود.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-13 fw-bold" for="sft-shipping-cost">هزینه ارسال (تومان) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control font-13" id="sft-shipping-cost" name="shipping_cost"
                               min="0" inputmode="numeric" value="{{ $tariff->shipping_cost }}" required>
                        <div class="font-12 text-muted mt-1">مبلغی که از خریدار برای این بازه وزنی دریافت می‌شود.</div>
                    </div>
                </div>
                <div class="alert alert-info font-12 mt-3 mb-0" role="alert">
                    <i class="bi bi-info-circle ms-1"></i>برای هر نوع منطقه و هر بازه وزنی فقط یک تعرفه می‌توانید ثبت کنید؛ تعرفه با کمترین «حداکثر وزن» که از وزن مرسوله بیشتر باشد مبنا قرار می‌گیرد.
                </div>
            </div>
        </div>

        <div class="content-box mt-3">
            <div class="container-fluid">
                <div class="d-flex flex-wrap justify-content-end gap-2 py-3">
                    <a href="{{ route('seller.tariffs.index', ['carrier' => $tariff->carrier_id]) }}" class="btn btn-outline-secondary rounded-3 font-14">
                        <i class="bi bi-arrow-right ms-1"></i>بازگشت به تعرفه‌ها
                    </a>
                    <button type="submit" class="btn main-color-one-bg text-white rounded-3 font-14 px-4" id="sft-submit-btn">
                        <span class="saniar-btn-spinner spinner-border spinner-border-sm d-none ms-1" role="status" aria-hidden="true"></span>
                        <i class="bi bi-check2-circle ms-1"></i>ویرایش تعرفه
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-tariffs.js') }}?v=1"
            data-saniar-tariffs="edit"></script>
@endpush
