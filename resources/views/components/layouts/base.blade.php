@props(['title' => null, 'description' => 'Medimatch BD connects people in Bangladesh who need eligible medicines with neighbours and organisations who can help, with privacy and safety checks built in.', 'bodyClass' => 'bg-white'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#0f6b66">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name', 'Medimatch BD') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen {{ $bodyClass }}">
    <a href="#main" class="sr-only z-50 rounded-lg bg-white px-4 py-2 font-semibold text-brand-700 shadow-pop focus:not-sr-only focus:fixed focus:top-2 focus:left-2">Skip to content</a>
    {{ $slot }}
</body>
</html>
