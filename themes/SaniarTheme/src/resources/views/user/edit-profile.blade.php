@extends('front::user.layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/vendor/nice-select.css') }}">
@endpush

@section('user-content')
    <div class="col-lg-12 col-xs-12 pull-right">

        <div class="headline-profile">
            <span>ویرایش اطلاعات شخصی</span>

            <a href="{{route('front.user.password')}}"  class="add-address-link float-left cursor-pointer openMap">تغییر رمز ورود</a>


        </div>
        <div class="profile-stats">
            <form id="profile-form" action="{{ route('front.user.profile.update') }}" class="setting_form" method="POST">
                @method('put')
                <div class="form-legal-row">
                <div class="col-lg-6 col-xs-12 mx-auto">
                    <div class="form-legal-col">
                        <fieldset class="form-legal-fieldset">
                            <div class="form-legal-item">
                                <label for="name-first">نام</label>
                                <input type="text" value="{{ $user->first_name }}" name="first_name" id="name-first" class="input-name-first"
                                       placeholder="نام خود را وارد کنید">
                            </div>

                            <div class="form-legal-item">
                                <label for="name-last">نام خانوادگی</label>
                                <input name="last_name" type="text" id="name-last" class="input-name-last"
                                       placeholder="نام خانوادگی خود را وارد کنید" value="{{ $user->last_name }}">
                            </div>

                            <?php $birth_date=explode('/',$user->birth_date) ?>
                            <div class="form-legal-item">
                                <label>تاریخ تولد</label>
                                <select name="day" id="day">
                                    <option value="date-desc" selected="selected">روز</option>
                                    @for($i=1;$i<=31;$i++)
                                    <option @if(@$birth_date[2]==$i) selected @endif value="{{$i}}">{{$i}}</option>
                                   @endfor
                                </select>
                                <select name="month" id="month">
                                    <option value="date-desc" selected="selected">ماه</option>
                                    <option @if(@$birth_date[1]==1) selected @endif value="1">فروردین</option>
                                    <option @if(@$birth_date[1]==2) selected @endif value="2">اردیبهشت</option>
                                    <option @if(@$birth_date[1]==3) selected @endif value="3">خرداد</option>
                                    <option @if(@$birth_date[1]==4) selected @endif value="4">تیر</option>
                                    <option @if(@$birth_date[1]==5) selected @endif value="5">مرداد</option>
                                    <option @if(@$birth_date[1]==6) selected @endif value="6">شهریور</option>
                                    <option @if(@$birth_date[1]==7) selected @endif value="7">مهر</option>
                                    <option @if(@$birth_date[1]==8) selected @endif value="8">آبان</option>
                                    <option @if(@$birth_date[1]==9) selected @endif value="9">آذر</option>
                                    <option @if(@$birth_date[1]==10) selected @endif value="10">دی</option>
                                    <option @if(@$birth_date[1]==11) selected @endif value="11">بهمن</option>
                                    <option @if(@$birth_date[1]==12) selected @endif value="12">اسفند</option>
                                </select>
                                <select name="year" id="year">
                                    <option value="date-desc" selected="selected">سال</option>
                                    @for($i=1360;$i<=1400;$i++)
                                        <option @if(@$birth_date[0]==$i) selected @endif value="{{$i}}">{{$i}}</option>
                                    @endfor
                                </select>
                            </div>

                            <div class="form-legal-item">
                                <label for="code-national">کد ملی</label>
                                <input type="text" id="code-national" class="input-code-national" name="national_code" value="{{$user->national_code}}"
                                       placeholder="کد ملی خود را وارد کنید">
                            </div>
{{--

                            <div class="form-legal-item has-diviter-item">
                                <div class="form-auth-row">
                                    <label class="ui-checkbox has-diviter">
                                        <input type="checkbox" value="1" name="login" checked="" id="remember1">
                                        <span class="ui-checkbox-check"></span>
                                    </label>
                                    <label for="remember1" class="remember-me has-diviter-remember-me">تبعه خارجی
                                        فاقد کد ملی هستم</label>
                                </div>
                            </div>
--}}

                            <div class="form-legal-item">
                                <label for="phone">شماره موبایل</label>
                                <input type="text" id="phone" class="input-code-national" disabled value="{{ $user->username }}"
                                       placeholder="شماره موبایل خود را وارد کنید">
                            </div>

                            <div class="form-legal-item">
                                <label for="email">آدرس ایمیل</label>
                                <input type="text" id="email" class="input-code-national"  name="email" value="{{ $user->email }}"
                                       placeholder="آدرس ایمیل خود را وارد کنید">
                            </div>

                            <div class="form-legal-item has-diviter-item">
                                <div class="form-auth-row">
                                    <label class="ui-checkbox has-diviter">
                                        <input type="checkbox" value="1" name="newsletter" @if($user->newsletter==1)checked="" @endif id="remember2">
                                        <span class="ui-checkbox-check"></span>
                                    </label>
                                    <label for="remember2" class="remember-me has-diviter-remember-me cursor-pointer">اشتراک در
                                        خبرنامه {{ option('info_site_title', 'او پی شاپ') }}</label>
                                </div>
                                <label for="number-card" class="number-card">شماره کارت</label>
                                <input type="text" id="number-card" name="card_number" class=""
                                       placeholder="شماره کارت خود را وارد کنید" value="{{ $user->card_number }}">
                            </div>

                        </fieldset>
                    </div>
                </div>
            </div>

                <div class="form-legal-row-submit">
                <div class="parent-btn">
                    <button  id="submit-btn" class="dk-btn dk-btn-info">
                        ثبت اطلاعات کاربری
                        <i class="fa fa-check sign-in"></i>
                    </button>
                </div>
            </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ theme_asset('js/vendor/jquery.nice-select.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>

    <script src="{{ theme_asset('js/pages/edit-profile.js?v=2') }}"></script>
@endpush
