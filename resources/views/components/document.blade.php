@php
    $dir = $dir ?? 'auto';
    $lang = $lang ?? 'en';
    $title = $title ?? 'Document';
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-size: 12pt;
            line-height: 1.5;
            word-wrap: break-word;
        }
        img { max-width: 100%; }
        table { border-collapse: collapse; width: 100%; }
    </style>
</head>
<body>
    {{ $slot }}
</body>
</html>
