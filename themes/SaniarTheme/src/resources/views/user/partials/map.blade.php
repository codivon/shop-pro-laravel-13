@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/mapir/css/mapp.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/mapir/css/fa/style.css') }}" />
    <link rel="stylesheet" href="{{theme_asset('css/map-selected-styles.css')}}" />
@endpush
<div class="map-container">
    <div class="container search-box">
        <div class="container search-box__item  flex-row">
            <input autocomplete="off" type="text" id="search" placeholder="جستجوی آدرس" /><span class="clear-seach">&#10006;</span>
           {{-- <div class="btn-seach">
                <span>برو</span>
            </div>--}}
        </div>


    </div>
    <div id="map-element"></div>
    <div id="center-marker" style="background-image:url({{theme_asset('img/pin-location.svg')}})"></div>
</div>

@push('scripts-top-js')


@endpush

@push('scripts')
    {{--<script type="text/javascript" src="https://cdn.map.ir/web-sdk/1.4.2/js/jquery-3.2.1.min.js"></script>--}}
    <script type="text/javascript" src="{{ theme_asset('js/plugins/map.ir/mapp.env.js') }}"></script>
    <script type="text/javascript" src="{{ theme_asset('js/plugins/map.ir/mapp.min.js') }}"></script>
    <script src="{{theme_asset('js/pages/addresses/map-select-scripts.js')}}"></script>
@endpush
