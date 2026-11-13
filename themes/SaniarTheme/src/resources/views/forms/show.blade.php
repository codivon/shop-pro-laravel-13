@extends('front::layouts.master', ['title' => $form->title])

@php
    // بارگذاری تنظیمات فرم
    $setting = $form->setting ?? new \App\Models\FormSetting();

    // کلاس‌های عرض فرم
    $widthClass = '';
    switch($setting->form_width ?? 'full') {
        case 'half':
            $widthClass = 'col-md-6 col-12';
            break;
        case 'third':
            $widthClass = 'col-md-4 col-12';
            break;
        default:
            $widthClass = 'col-12';
    }

    // کلاس‌های تراز فرم
    $alignmentClass = '';
    switch($setting->form_alignment ?? 'center') {
        case 'center':
            $alignmentClass = 'mx-auto';
            break;
        case 'right':
            $alignmentClass = 'ms-auto';
            break;
        case 'left':
            $alignmentClass = 'me-auto';
            break;
    }

    // موقعیت فرم (top = بالای توضیحات, bottom = زیر توضیحات)
    $formPosition = $setting->form_position ?? 'top';

    // کلاس سفارشی فرم
    $formCustomClass = $setting->form_class ?? '';

    // CSS سفارشی
    $customCss = $setting->custom_css ?? '';
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/form-builder.css') }}">
    <style>

        /* CSS سفارشی فرم */
        {!! $customCss !!}
    </style>
@endpush

@section('content')
    <div class="form-container">
        <div class="">
            <div class="row justify-content-center">
                <div class="col-lg-12 col-md-12">
                    <div class="form-card">
                        <div class="form-header">
                            <h1 class="text-right">{{ $form->title }}</h1>
                            <div class="form-description mt-4 mb-4 text-right" id="form-description-{{ $form->id }}">
                                @if($form->description)
                                    <p>{!! $form->description !!}</p>
                                @endif
                            </div>

                        </div>

                        <div class="form-body">
                            <!-- فرم (با قابلیت جابجایی) -->
                            <div class="form-wrapper {{ $widthClass }} {{ $alignmentClass }} {{ $formCustomClass }}" id="form-wrapper-{{ $form->id }}" data-position="{{ $formPosition }}">
                                <form id="dynamic-form" method="POST" action="{{ route('front.form.submit', $form) }}" enctype="multipart/form-data">
                                    @csrf

                                    <div class="row">
                                        @foreach($form->fields->sortBy('order') as $field)
                                            @php
                                                // تعیین کلاس ستون - اولویت با column_class فیلد
                                                $columnClass = $field->column_class ?? ($setting->default_column_class ?? 'col-md-6');

                                                // برای textarea اگر ستون خاصی تنظیم نشده و type textarea است، col-12 بده
                                                if ($field->type === 'textarea' && empty($field->column_class)) {
                                                    $columnClass = 'col-12';
                                                }

                                                // کلاس والد فیلد
                                                $wrapperClass = $field->wrapper_class ?? '';
                                            @endphp

                                            <div class="form-group-custom {{ $columnClass }} {{ $wrapperClass }}" data-field-id="{{ $field->id }}">
                                                @if($field->show_label)
                                                    <label for="{{ $field->name }}" class="{{ $field->label_class }}">
                                                        {{ $field->label }}
                                                        @if($field->required)
                                                            <span class="required-star">*</span>
                                                        @endif
                                                    </label>
                                                @endif

                                                @switch($field->type)
                                                    @case('textarea')
                                                        <textarea name="{{ $field->name }}"
                                                                  id="{{ $field->name }}"
                                                                  class="form-control-custom {{ $field->class }}"
                                                                  placeholder="{{ $field->placeholder ?? 'متن خود را وارد کنید...' }}"
                                                                  rows="4">{{ old($field->name) }}</textarea>
                                                        @break

                                                    @case('select')
                                                        <select name="{{ $field->name }}"
                                                                id="{{ $field->name }}"
                                                                class="select-custom {{ $field->class }}">
                                                            <option value="">انتخاب کنید...</option>
                                                            @foreach($field->options_array as $option)
                                                                <option value="{{ $option }}" {{ old($field->name) == $option ? 'selected' : '' }}>
                                                                    {{ $option }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @break

                                                    @case('checkbox')
                                                        <div class="checkbox-group">
                                                            @foreach($field->options_array as $index => $option)
                                                                <div class="form-auth-row">
                                                                    <label class="ui-checkbox has-diviter">
                                                                        <input type="checkbox"
                                                                               id="checkbox-{{ $field->id }}-{{ $index }}"
                                                                               value="{{ $option }}"
                                                                               name="{{ $field->name }}[]"
                                                                               class="{{ $field->class }}"
                                                                            {{ in_array($option, (array)old($field->name, [])) ? 'checked' : '' }}>
                                                                        <span class="ui-checkbox-check"></span>
                                                                    </label>
                                                                    <label for="checkbox-{{ $field->id }}-{{ $index }}" class="remember-me has-diviter-remember-me cursor-pointer">
                                                                        {{ $option }}
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        @break

                                                    @case('radio')
                                                        <div class="radio-group">
                                                            @foreach($field->options_array as $index => $option)
                                                                <label class="radio-item">
                                                                    <input type="radio"
                                                                           name="{{ $field->name }}"
                                                                           value="{{ $option }}"
                                                                           class="{{ $field->class }}"
                                                                           id="radio-{{ $field->id }}-{{ $index }}"
                                                                        {{ old($field->name) == $option ? 'checked' : '' }}>
                                                                    <span>{{ $option }}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                        @break

                                                    @case('file')
                                                        <div class="form-group-custom">
{{--                                                            <label for="file-input-{{ $field->name }}">{{ $field->label }}</label>--}}
                                                            <label class="upload-label" for="file-input-{{ $field->name }}">
                                                                <i class="fa fa-upload"></i>
                                                                <span>برای آپلود کلیک کنید</span>
                                                            </label>
                                                            <input type="file"
                                                                   name="{{ $field->name }}"
                                                                   id="file-input-{{ $field->name }}"
                                                                   class="file-input-hidden {{ $field->class }}"
                                                                   style="display: none;">
                                                            <span id="file-name-{{ $field->name }}" class="file-name-display"></span>
                                                            <small class="d-block mt-2 text-muted">فرمت‌های مجاز: jpg, png, pdf</small>
                                                        </div>
                                                        @break

                                                    @default
                                                        <input type="{{ $field->type }}"
                                                               name="{{ $field->name }}"
                                                               id="{{ $field->name }}"
                                                               class="form-control-custom {{ $field->class }}"
                                                               placeholder="{{ $field->placeholder ?? 'لطفا وارد کنید...' }}"
                                                               value="{{ old($field->name, $field->default_value ?? '') }}">
                                                @endswitch

                                                @if($field->help_text)
                                                    <div class="help-text">
                                                        <i class="fa fa-info-circle"></i> {{ $field->help_text }}
                                                    </div>
                                                @endif

                                                <div class="error-message" id="error-{{ $field->name }}"></div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <button type="submit" class="btn-submit" id="submit-btn">
                                        <i class="fa fa-paper-plane"></i> {{ $form->button_text }}
                                    </button>
                                    <div class="form-result mt-3"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>
    <script type="text/javascript" src="{{ theme_asset('js/pages/form-builder.js') }}"></script>
    <script>
        $(document).ready(function() {
            var formId = {{ $form->id }};
            var formPosition = '{{ $formPosition }}';
            var formWrapper = document.getElementById('form-wrapper-' + formId);
            var formDescription = document.getElementById('form-description-' + formId);
            console.log(formId);
            console.log(formPosition);
            console.log(formWrapper);
            console.log(formDescription);
            // تنظیم موقعیت فرم نسبت به توضیحات
            if (formPosition === 'bottom') {
                // فرم باید زیر توضیحات قرار بگیرد
                if (formDescription && formWrapper) {
                    // اگر توضیحات وجود دارد، فرم را بعد از توضیحات قرار بده
                    formDescription.parentNode.insertBefore(formWrapper, formDescription.nextSibling);
                }
            } else {
                // حالت top (پیش‌فرض) - فرم بالای توضیحات قرار می‌گیرد
                if (formDescription && formWrapper) {
                    // اگر توضیحات وجود دارد، فرم را قبل از توضیحات قرار بده
                    formDescription.parentNode.insertBefore(formWrapper, formDescription);
                }
            }

            // نمایش فرم بعد از جابجایی
            if (formWrapper) {
                formWrapper.style.display = 'block';
            }

            let rules = {};
            let messages = {};
            let fieldsData = [];

            @foreach($form->fields as $field)
            fieldsData.push({
                name: '{{ $field->name }}',
                label: '{{ $field->label }}',
                type: '{{ $field->type }}',
                required: {{ $field->required ? 'true' : 'false' }},
                rules_validation: '{{ addslashes($field->rules_validation ?? '') }}'
            });

            @if($field->required)
                rules['{{ $field->name }}'] = {
                required: true
            };
            messages['{{ $field->name }}'] = {
                required: 'وارد کردن {{ $field->label }} الزامی است'
            };
            @endif

            @if($field->type === 'email')
            if (rules['{{ $field->name }}']) {
                rules['{{ $field->name }}'].email = true;
            } else {
                rules['{{ $field->name }}'] = { email: true };
            }
            messages['{{ $field->name }}'] = {
                ...messages['{{ $field->name }}'],
                email: 'لطفا یک ایمیل معتبر وارد کنید'
            };
            @endif

            @if($field->type === 'tel')
            if (rules['{{ $field->name }}']) {
                rules['{{ $field->name }}'].pattern = /^09[0-9]{9}$/;
            } else {
                rules['{{ $field->name }}'] = { pattern: /^09[0-9]{9}$/ };
            }
            messages['{{ $field->name }}'] = {
                ...messages['{{ $field->name }}'],
                pattern: 'شماره موبایل باید با 09 شروع شود و 11 رقم باشد'
            };
            @endif

            @if($field->rules_validation)
            @php
                $validationRules = explode('|', $field->rules_validation);
            @endphp
            @foreach($validationRules as $rule)
            @if(str_contains($rule, 'min:'))
            let minVal_{{ $field->name }} = '{{ $rule }}'.split(':')[1];
            if (rules['{{ $field->name }}']) {
                rules['{{ $field->name }}'].minlength = parseInt(minVal_{{ $field->name }});
            } else {
                rules['{{ $field->name }}'] = { minlength: parseInt(minVal_{{ $field->name }}) };
            }
            messages['{{ $field->name }}'] = {
                ...messages['{{ $field->name }}'],
                minlength: 'حداقل باید ' + minVal_{{ $field->name }} + ' کاراکتر وارد شود'
            };
            @endif

            @if(str_contains($rule, 'max:'))
            let maxVal_{{ $field->name }} = '{{ $rule }}'.split(':')[1];
            if (rules['{{ $field->name }}']) {
                rules['{{ $field->name }}'].maxlength = parseInt(maxVal_{{ $field->name }});
            } else {
                rules['{{ $field->name }}'] = { maxlength: parseInt(maxVal_{{ $field->name }}) };
            }
            messages['{{ $field->name }}'] = {
                ...messages['{{ $field->name }}'],
                maxlength: 'حداکثر ' + maxVal_{{ $field->name }} + ' کاراکتر مجاز است'
            };
            @endif

                @if(str_contains($rule, 'numeric'))
            if (rules['{{ $field->name }}']) {
                rules['{{ $field->name }}'].number = true;
            } else {
                rules['{{ $field->name }}'] = { number: true };
            }
            messages['{{ $field->name }}'] = {
                ...messages['{{ $field->name }}'],
                number: 'لطفا یک مقدار عددی وارد کنید'
            };
            @endif
            @endforeach
            @endif
            @endforeach


            $('#dynamic-form').validate({
                rules: rules,
                messages: messages,
                errorClass: 'error-message',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('error-message');
                    error.insertAfter(element);
                    element.addClass('error');
                },
                success: function(label, element) {
                    $(element).removeClass('error');
                },
                highlight: function(element) {
                    $(element).addClass('error');
                },
                unhighlight: function(element) {
                    $(element).removeClass('error');
                }
            });


        });
    </script>
@endpush
