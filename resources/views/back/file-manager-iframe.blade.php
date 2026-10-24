<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'File Manager') }}</title>

    <!-- Styles — فاز ۷۰: همهٔ دارایی‌ها لوکال (CDN در ایران فیلتر/کند است) + Bootstrap 4.6.2 (UI فایل‌منیجر با v5 می‌شکست) -->
    <link rel="stylesheet" href="{{ asset('vendor/file-manager/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/file-manager/css/bootstrap-rtl.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/file-manager/css/fontawesome.min.css') }}">
    <link href="{{ asset('vendor/file-manager/css/file-manager.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/file-manager/css/styles.css') }}">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12" id="fm-main-block">
                <div id="fm"></div>
            </div>
        </div>
    </div>

    <!-- File manager -->
    <script src="{{ asset('vendor/file-manager/js/file-manager.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // set fm height
            document.getElementById('fm-main-block').setAttribute('style', 'height:' + window.innerHeight +
                'px');

        });

    </script>
</body>

</html>
