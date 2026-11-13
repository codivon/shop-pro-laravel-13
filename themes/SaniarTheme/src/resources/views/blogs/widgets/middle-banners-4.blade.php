@if($widget)
    @php
        $variables              = get_widget($widget);
        $index_middle_4_banners   = $variables['index_middle_4_banners'];

    @endphp

    @if ($index_middle_4_banners->count())

        <!--   adplacement -------------------->
        <div class="adplacement">
            @php $i=1; @endphp
            @foreach ($index_middle_4_banners as $banner)
                @if(in_array($widget->option('place'), $banner->places ?? []))
                    @if($i<=$widget->option('number'))
                        <div class="col-6 col-lg-3 pull-right" @if($i==1 or $i==3) style="padding-left:0;" @endif>
                            <a href="{{ $banner->link }}" class="item-adplacement image-data-src">
                                <img data-src="{{ asset($banner->image) }}" title="{{ $banner->title }}" alt="{{ $banner->title }}">
                            </a>
                        </div>
                        @php $i++; @endphp
                    @endif
                @endif
            @endforeach
        </div>
        <!--   adplacement -------------------->

    @endif

@endif
