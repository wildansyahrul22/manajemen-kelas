@props(['label', 'value', 'icon', 'color' => 'primary', 'hint' => null, 'href' => null])
@php
    $colors = [
        'primary' => 'bg-primary-100 text-primary-800',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'sky' => 'bg-sky-50 text-sky-600',
    ];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif {{ $attributes->merge(['class' => 'flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03] transition '.($href ? 'hover:border-slate-300 hover:shadow-md' : '')]) }}>
    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl {{ $colors[$color] }}">
        <x-dynamic-component :component="$icon" class="size-6" />
    </span>
    <div class="min-w-0">
        <p class="text-sm leading-snug text-slate-500">{{ $label }}</p>
        <p class="text-2xl font-bold tracking-tight text-slate-900">{{ $value }}</p>
        @if ($hint)<p class="truncate text-xs text-slate-400">{{ $hint }}</p>@endif
    </div>
</{{ $tag }}>
