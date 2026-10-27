
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
<meta name="description" content="laravel shop description">
<meta name="keywords" content="keywords">
<meta name="author" content="laravel shop">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" data-refresh-url="{{ route('csrf') }}" content="{{ csrf_token() }}">
<link rel="apple-touch-icon" href="{{ option('info_icon', theme_asset('images/favicon-32x32.png')) }}">
<link rel="shortcut icon" type="image/x-icon" href="{{ option('info_icon', theme_asset('images/favicon-32x32.png')) }}">

<!-- GCM Manifest (optional if VAPID is used) -->
@if (config('webpush.gcm.sender_id'))
    <link rel="manifest" href="{{ asset('manifest.json') }}">
@endif
