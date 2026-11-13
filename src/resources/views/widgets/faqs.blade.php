{{-- ویجت سوالات متداول — آکاردئون سانیار --}}
@php
    $variables = get_widget($widget);
    $faqs = $variables['faqs'];
@endphp

@if(count($faqs))
    <div class="py-20">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <div class="faq-parent">
                        <div class="faq-title">
                            <div class="text-center">
                                <i class="bi bi-patch-question font-40 main-color-one-color"></i>
                            </div>
                            <h4 class="text-center fw-semibold my-3">{{ $widget->title ?: 'سوالات پرتکرار شما' }}</h4>
                        </div>

                        <div class="accordion-style" id="faqAccordion{{ $widget->id }}">
                            @foreach($faqs as $index => $faq)
                                <div class="card mb-3">
                                    <div class="card-header" id="faqHeading{{ $widget->id }}-{{ $index }}">
                                        <h5 class="mb-0">
                                            <button aria-controls="faqCollapse{{ $widget->id }}-{{ $index }}"
                                                    aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                                    class="btn btn-link {{ $index === 0 ? '' : 'collapsed' }}"
                                                    data-bs-target="#faqCollapse{{ $widget->id }}-{{ $index }}"
                                                    data-bs-toggle="collapse">
                                                {{ $faq->question }}
                                            </button>
                                        </h5>
                                    </div>
                                    <div aria-labelledby="faqHeading{{ $widget->id }}-{{ $index }}"
                                         class="collapse {{ $index === 0 ? 'show' : '' }}"
                                         data-bs-parent="#faqAccordion{{ $widget->id }}"
                                         id="faqCollapse{{ $widget->id }}-{{ $index }}">
                                        <div class="card-body">
                                            {{ $faq->answer }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
