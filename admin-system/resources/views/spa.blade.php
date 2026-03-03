<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Admin System') }}</title>

    @php
        $manifest = public_path('.vite/manifest.json');
        $assets = [];
        if (file_exists($manifest)) {
            $manifestContent = json_decode(file_get_contents($manifest), true);
            if (isset($manifestContent['index.html'])) {
                $assets = $manifestContent['index.html'];
            }
        }
    @endphp

    @if(!empty($assets))
        @if(!empty($assets['css']))
            @foreach($assets['css'] as $cssFile)
                <link rel="stylesheet" href="/{{ $cssFile }}">
            @endforeach
        @endif
    @endif
</head>
<body>
    <div id="app"></div>

    @if(!empty($assets) && !empty($assets['file']))
        <script type="module" src="/{{ $assets['file'] }}"></script>
    @endif
</body>
</html>
