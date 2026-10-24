{{-- ویرایش اطلاعات کاربری — قرارداد UserController@editProfile : $user, $provinces, $active --}}
@extends('front::user.layouts.master', ['title' => 'ویرایش پروفایل'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    @php $birth_date = explode('/', (string) $user->birth_date); @endphp

    <div class="content-box">
        <div class="container-fluid">
            <form id="profile-edit-form" action="{{ route('front.user.profile.update') }}" method="POST"
                  data-redirect="{{ route('front.user.profile') }}?profile=updated">
                @csrf
                @method('PUT')

                <div class="title title-panel d-flex align-items-baseline justify-content-between flex-wrap mb-4">
                    <div class="d-flex align-items-baseline">
                        <i class="bi bi-person-gear font-25 main-color-one-color"></i>
                        <h2 class="font-16 ms-2 mb-0">ویرایش <span class="main-color-one-color">اطلاعات کاربری</span></h2>
                    </div>
                    <a href="{{ route('front.user.password') }}" class="btn btn-sm btn-light rounded-3 border px-3">
                        <i class="bi bi-key me-1 main-color-one-color"></i> تغییر رمز ورود
                    </a>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control float-input" id="floatingInputFname" name="first_name"
                                   placeholder="نام خود را وارد کنید" value="{{ $user->first_name }}" required>
                            <label for="floatingInputFname">نام خود را وارد کنید</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control float-input" id="floatingInputLname" name="last_name"
                                   placeholder="نام خانوادگی خود را وارد کنید" value="{{ $user->last_name }}" required>
                            <label for="floatingInputLname">نام خانوادگی خود را وارد کنید</label>
                        </div>
                    </div>

                    {{-- تاریخ تولد (سه انتخاب روز/ماه/سال) --}}
                    <div class="col-md-6">
                        <label class="form-label font-14 fw-bold mb-2 d-block" for="day">تاریخ تولد</label>
                        <div class="d-flex gap-2">
                            <select class="form-select" name="day" id="day" aria-label="روز تولد">
                                <option value="date-desc" selected>روز</option>
                                @for($i = 1; $i <= 31; $i++)
                                    <option value="{{ $i }}" {{ (@$birth_date[2] == $i) ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                            <select class="form-select" name="month" id="month" aria-label="ماه تولد">
                                <option value="date-desc" selected>ماه</option>
                                @foreach(['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'] as $mi => $mn)
                                    <option value="{{ $mi + 1 }}" {{ (@$birth_date[1] == $mi + 1) ? 'selected' : '' }}>{{ $mn }}</option>
                                @endforeach
                            </select>
                            <select class="form-select" name="year" id="year" aria-label="سال تولد">
                                <option value="date-desc" selected>سال</option>
                                @for($i = 1360; $i <= 1404; $i++)
                                    <option value="{{ $i }}" {{ (@$birth_date[0] == $i) ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control float-input" id="code-national" name="national_code"
                                   placeholder="کد ملی" value="{{ $user->national_code }}" inputmode="numeric">
                            <label for="code-national">کد ملی</label>
                        </div>
                    </div>

                    {{-- موبایل (نام کاربری — غیرقابل تغییر از این فرم) --}}
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control float-input bg-light" id="phone"
                                   value="{{ $user->username }}" placeholder="شماره موبایل" disabled>
                            <label for="phone"><i class="bi bi-lock me-1 font-12"></i> شماره موبایل (غیرقابل تغییر)</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control float-input" id="email" name="email"
                                   placeholder="ایمیل" value="{{ $user->email }}">
                            <label for="email">ایمیل</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control float-input" id="number-card" name="card_number"
                                   placeholder="شماره کارت" value="{{ $user->card_number }}" inputmode="numeric">
                            <label for="number-card">شماره کارت</label>
                        </div>
                    </div>

                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="newsletter" name="newsletter"
                                   value="1" {{ $user->newsletter == 1 ? 'checked' : '' }}>
                            <label class="form-check-label font-14" for="newsletter">
                                اشتراک در خبرنامه {{ option('info_site_title', config('front.demo.name')) }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                    <button type="submit" class="btn border-0 main-color-one-bg text-white waves-effect waves-light py-2 px-5 rounded-3">
                        <i class="bi bi-pencil-square text-white me-1"></i> ثبت اطلاعات کاربری
                    </button>
                    <a href="{{ route('front.user.profile') }}" class="btn btn-light border rounded-3 px-4 py-2">انصراف</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush
@push('scripts')
    <script src="{{ theme_asset('js/pages/user/panel.js') }}?v=1"></script>
@endpush
