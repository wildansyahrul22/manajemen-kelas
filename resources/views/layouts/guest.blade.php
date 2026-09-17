@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @include('layouts.partials.ikon')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans">
    <div class="flex min-h-full items-center justify-center px-4 py-12 sm:px-6">
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </div>

    @include('layouts.partials.toast')
    @livewireScripts
</body>
</html>
