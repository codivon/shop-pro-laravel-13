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

    // CSS سفارشی فرم (توسط js/pages/blog/form-embed.js تزریق می‌شود — بدون style اینلاین)
    $customCss = $setting->custom_css ?? '';
@endphp

<link rel="stylesheet" href="{{ theme_asset('css/form-builder.css') }}">

{{-- فرم ساندکد — سانیار content-box --}}
<div class="shortcode-form shortcode-form-{{ $form->id }} my-4"
     data-form-id="{{ $form->id }}"
     data-position="{{ $formPosition }}"
     data-custom-css="{{ $customCss }}">
    <div class="content-box">
        <div class="container-fluid">
            <div class="title-panel mb-3">
                <h5 class="mb-0">{{ $form->title }}</h5>
            </div>

            {{-- توضیحات فرم --}}
            @if($form->description)
                <div class="form-description mb-4" id="form-description-{{ $form->id }}">
                    {!! $form->description !!}
                </div>
            @endif

            {{-- فرم (با قابلیت جابجایی به بالا یا پایین توضیحات) --}}
            <div class="form-wrapper {{ $widthClass }} {{ $alignmentClass }} {{ $formCustomClass }}"
                 id="form-wrapper-{{ $form->id }}"
                 data-position="{{ $formPosition }}">

                <form method="POST" action="{{ route('front.form.submit', $form) }}" class="ajax-form" id="ajax-form-{{ $form->id }}">
                    @csrf

                    <div class="row" id="form-fields-container">
                        @foreach($form->fields->sortBy('order') as $field)
                            @php
                                // تعیین کلاس ستون - اولویت با column_class فیلد
                                $columnClass = $field->column_class ?? ($setting->default_column_class ?? 'col-md-6');

                                // برای textarea اگر ستون خاصی تنظیم نشده، col-12 بده
                                if ($field->type === 'textarea' && empty($field->column_class)) {
                                    $columnClass = 'col-12';
                                }

                                // کلاس والد فیلد
                                $wrapperClass = $field->wrapper_class ?? '';
                            @endphp

                            <div class="form-group-custom {{ $columnClass }} {{ $wrapperClass }} mb-3" data-field-id="{{ $field->id }}">
                                <div class="form-group mb-0">
                                    @if($field->show_label)
                                        <label for="field_{{ $field->id }}" class="form-label fw-bold mb-2 {{ $field->label_class }}">
                                            {{ $field->label }}
                                            @if($field->required)
                                                <span class="text-danger">*</span>
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
                                                   id="field_{{ $field->id }}"
                                                   class="form-control {{ $field->class }}"
                                                   placeholder="{{ $field->placeholder }}"
                                                   value="{{ $field->default_value }}"
                                                {{ $field->required ? 'required' : '' }}>
                                    @endswitch

                                    @if($field->help_text)
                                        <small class="form-text text-muted mt-1">{{ $field->help_text }}</small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn border-0 main-color-one-bg rounded-3 px-4 py-2">
                                <i class="bi bi-send text-white"></i> <span class="text-white">{{ $form->button_text }}</span>
                            </button>
                            <div class="form-result mt-3"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="{{ theme_asset('js/pages/blog/form-embed.js') }}"></script>
