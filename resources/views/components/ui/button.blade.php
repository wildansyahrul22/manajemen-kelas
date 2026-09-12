{{-- `opens`: name of a boolean Livewire property (modal model) to switch on client-side right on click. --}}
@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button', 'href' => null, 'navigate' => true, 'opens' => null])
@php
    $base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60';
    $sizes = ['sm' => 'px-3 py-1.5 text-xs', 'md' => 'px-4 py-2.5 text-sm', 'icon' => 'p-2', 'icon-sm' => 'p-1.5'];
    $variants = [
        'primary' => 'bg-primary-900 text-white shadow-sm shadow-primary-900/20 hover:bg-primary-700',
        'secondary' => 'border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900',
        'danger' => 'border border-rose-200 bg-white text-rose-600 shadow-sm hover:border-rose-600 hover:bg-rose-600 hover:text-white',
        'danger-solid' => 'bg-rose-600 text-white shadow-sm hover:bg-rose-700',
        'ghost' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
        'ghost-danger' => 'text-rose-600 hover:bg-rose-600 hover:text-white',
    ];
    $classes = "{$base} {$sizes[$size]} {$variants[$variant]}";
@endphp
@if ($href)
    <a href="{{ $href }}" @if ($navigate) wire:navigate @endif {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" @if ($opens) x-on:click="$wire.{{ $opens }} = true" @endif {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
