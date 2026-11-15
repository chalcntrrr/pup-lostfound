<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - PUPSRC Lost and Found</title>
    <link rel="icon" href="{{ asset('images/logo-dashboard.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@yield('content')
@stack('scripts')
</body>
</html>
