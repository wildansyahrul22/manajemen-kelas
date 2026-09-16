@props(['title' => null])
@inject('kelasContext', 'App\Support\KelasContext')
@php
    $user = auth()->user();
    $kelasAktif = $kelasContext->current();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans">
    <div
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: $persist(false).as('mk-sidebar-collapsed'),
            toggleSidebar() {
                window.matchMedia('(min-width: 64rem)').matches
                    ? this.sidebarCollapsed = ! this.sidebarCollapsed
                    : this.sidebarOpen = ! this.sidebarOpen;
            },
        }"
        x-on:keydown.escape.window="sidebarOpen = false"
        class="min-h-full"
    >
        {{-- Mobile backdrop --}}
        <div
            x-show="sidebarOpen"
            x-transition.opacity.duration.200ms
            x-on:click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-sm lg:hidden"
            x-cloak
        ></div>

        @include('layouts.partials.sidebar')

        <div class="flex min-h-screen flex-col transition-[padding] duration-200 ease-in-out" :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72'">
            @include('layouts.partials.header')

            @if ($user->isDemo())
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900 sm:px-6 lg:px-8">
                    <span class="inline-flex items-center gap-2 font-semibold"><x-heroicon-m-beaker class="size-4" /> Mode demo</span>
                    <span class="text-amber-800">Datanya dipakai bersama pengunjung lain — silakan coba apa saja.</span>
                    <a href="{{ route('landing') }}#harga" class="ml-auto font-semibold underline underline-offset-2 hover:no-underline">Lihat harga langganan</a>
                </div>
            @endif

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="mx-auto w-full max-w-7xl">
                    <div data-page-content>
                        {{ $slot }}
                    </div>
                    @include('layouts.partials.page-skeleton')
                </div>
            </main>

            <footer class="px-4 py-5 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </footer>
        </div>
    </div>

    @include('layouts.partials.toast')
    @livewireScripts
</body>
</html>
