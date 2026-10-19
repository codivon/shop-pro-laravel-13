<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'File Manager') }}</title>

    <!-- Styles — فاز ۱۶: همه دارایی‌ها لوکال (CDN جی‌اس‌دلیور در ایران فیلتر است و فایل‌منیجر را به‌هم می‌ریزد) -->
    {{-- نکته: آیکون‌های خود فایل‌منیجر FontAwesome (fas fa-*) هستند نه bootstrap-icons --}}
    <link rel="stylesheet" href="{{ asset('vendor/file-manager/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/file-manager/css/fontawesome.min.css') }}">
    <link href="{{ asset('vendor/file-manager/css/file-manager.css') }}" rel="stylesheet">
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
    document.getElementById('fm-main-block').setAttribute('style', 'height:' + window.innerHeight + 'px');

    // Add callback to file manager
    fm.$store.commit('fm/setFileCallBack', function(fileUrl) {
      // ★ دو حالت انتگرال:
      //   1) iframe (پنل جدید — مودال داخل صفحه) → window.parent
      //   2) پنجره popup (پنل قدیمی — window.open) → window.opener
      var inIframe = window.self !== window.top;
      var target = inIframe ? window.parent : window.opener;

      if (target && typeof target.fmSetLink === 'function') {
        target.fmSetLink(fileUrl);
      }

      if (!inIframe) {
        window.close();
      }
    });
  });
</script>
</body>
</html>
