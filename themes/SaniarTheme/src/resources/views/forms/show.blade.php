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

    // CSS سفارشی فرم (توسط js/pages/blog/form-show.js تزریق می‌شود — بدون style اینلاین)
    $customCss = $setting->custom_css ?? '';

    // پیکربندی فیلدها برای اعتبارسنجی سمت کلاینت (خوانده شده توسط form-show.js)
    $fieldsConfig = [];
    foreach ($form->fields as $field) {
        $fieldsConfig[] = [
            'name'             => $field->name,
            'label'            => $field->label,
            'type'             => $field->type,
            'required'         => (bool) $field->required,
            'rules_validation' => $field->rules_validation ?? '',
        ];
    }
@endphp

@section('content')
    {{-- فرم ساز — سانیار content-box --}}
    <div class="content">
        <div class="container-fluid py-3">
            <div class="content-box">
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="col-lg-12 col-md-12">

                            <div class="title-panel mb-3">
                                <h1 class="font-18">{{ $form->title }}</h1>
                            </div>

                            <div class="form-description mb-4" id="form-description-{{ $form->id }}">
                                @if($form->description)
                                    {!! $form->description !!}
                                @endif
                            </div>

                            {{-- فرم (با قابلیت جابجایی) --}}
                            <div class="form-wrapper {{ $widthClass }} {{ $alignmentClass }} {{ $formCustomClass }}"
                                 id="form-wrapper-{{ $form->id }}"
                                 data-position="{{ $formPosition }}"
                                 data-form-id="{{ $form->id }}"
                                 data-custom-css="{{ $customCss }}"
                                 data-fields="{{ json_encode($fieldsConfig, JSON_UNESCAPED_UNICODE) }}">
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

                                            <div class="form-group-custom {{ $columnClass }} {{ $wrapperClass }} mb-3" data-field-id="{{ $field->id }}">
                                                @if($field->show_label)
                                                    <label for="{{ $field->name }}" class="form-label {{ $field->label_class }}">
                                                        {{ $field->label }}
                                                        @if($field->required)
                                                            <span class="text-danger ms-1">*</span>
                                                        @endif
                                                    </label>
                                                @endif

                                                @switch($field->type)
                                                    @case('textarea')
                                                        <textarea name="{{ $field->name }}"
                                                                  id="{{ $field->name }}"
                                                                  class="form-control {{ $field->class }}"
                                                                  placeholder="{{ $field->placeholder ?? 'متن خود را وارد کنید...' }}"
                                                                  rows="4">{{ old($field->name) }}</textarea>
                                                        @break

                                                    @case('select')
                                                        <select name="{{ $field->name }}"
                                                                id="{{ $field->name }}"
                                                                class="form-select {{ $field->class }}">
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
                                                                <div class="form-check">
                                                                    <input type="checkbox"
                                                                           id="checkbox-{{ $field->id }}-{{ $index }}"
                                                                           value="{{ $option }}"
                                                                           name="{{ $field->name }}[]"
                                                                           class="form-check-input {{ $field->class }}"
                                                                        {{ in_array($option, (array) old($field->name, [])) ? 'checked' : '' }}>
                                                                    <label for="checkbox-{{ $field->id }}-{{ $index }}" class="form-check-label text-muted">
                                                                        {{ $option }}
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        @break

                                                    @case('radio')
                                                        <div class="radio-group">
                                                            @foreach($field->options_array as $index => $option)
                                                                <div class="form-check">
                                                                    <input type="radio"
                                                                           name="{{ $field->name }}"
                                                                           value="{{ $option }}"
                                                                           class="form-check-input {{ $field->class }}"
                                                                           id="radio-{{ $field->id }}-{{ $index }}"
                                                                        {{ old($field->name) == $option ? 'checked' : '' }}>
                                                                    <label for="radio-{{ $field->id }}-{{ $index }}" class="form-check-label text-muted">{{ $option }}</label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        @break

                                                    @case('file')
                                                        <div class="form-group-custom">
                                                            <label class="upload-label form-control rounded-3 d-flex align-items-center justify-content-center text-muted" for="file-input-{{ $field->name }}">
                                                                <i class="bi bi-cloud-arrow-up ms-2"></i>
                                                                <span>برای آپلود کلیک کنید</span>
                                                            </label>
                                                            <input type="file"
                                                                   name="{{ $field->name }}"
                                                                   id="file-input-{{ $field->name }}"
                                                                   class="file-input-hidden d-none {{ $field->class }}"
                                                            <span id="file-name-{{ $field->name }}" class="file-name-display"></span>
                                                            <small class="d-block mt-2 text-muted">فرمت‌های مجاز: jpg, png, pdf</small>
                                                        </div>
                                                        @break

                                                    @default
                                                        <input type="{{ $field->type }}"
                                                               name="{{ $field->name }}"
                                                               id="{{ $field->name }}"
                                                               class="form-control {{ $field->class }}"
                                                               placeholder="{{ $field->placeholder ?? 'لطفا وارد کنید...' }}"
                                                               value="{{ old($field->name, $field->default_value ?? '') }}">
                                                @endswitch

                                                @if($field->help_text)
                                                    <div class="help-text form-text text-muted mt-1">
                                                        <i class="bi bi-info-circle"></i> {{ $field->help_text }}
                                                    </div>
                                                @endif

                                                <div class="error-message" id="error-{{ $field->name }}"></div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <button type="submit" class="btn border-0 main-color-one-bg rounded-3 px-5 py-2" id="submit-btn">
                                        <i class="bi bi-send text-white"></i> <span class="text-white">{{ $form->button_text }}</span>
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

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/form-builder.css') }}">
@endpush

@push('scripts')
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>
    <script src="{{ theme_asset('js/pages/form-builder.js') }}"></script>
    <script src="{{ theme_asset('js/pages/blog/form-show.js') }}"></script>
@endpush
