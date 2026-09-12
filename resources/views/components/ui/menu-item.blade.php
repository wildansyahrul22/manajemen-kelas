{{-- `opens`: name of a boolean Livewire property (modal model) to switch on client-side right on click. --}}
@props(['icon' => null, 'danger' => false, 'href' => null, 'type' => 'button', 'opens' => null])
@php
    $classes = 'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium transition '.($danger
        ? 'text-rose-600 hover:bg-rose-600 hover:text-white'
        : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900');
@endphp
@if ($href)
    <a href="{{ $href }}" wire:navigate role="menuitem" x-on:click="close()" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-dynamic-component :component="$icon" class="size-4 shrink-0" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" role="menuitem" x-on:click="close(){{ $opens ? '; $wire.'.$opens.' = true' : '' }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-dynamic-component :component="$icon" class="size-4 shrink-0" />@endif
        {{ $slot }}
    </button>
@endif
