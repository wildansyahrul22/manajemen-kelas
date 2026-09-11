@props(['name', 'size' => 'md'])
@php
    $initials = collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $sizes = ['xs' => 'size-7 text-[11px]', 'sm' => 'size-8 text-xs', 'md' => 'size-10 text-sm', 'lg' => 'size-14 text-lg'];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700 '.$sizes[$size]]) }} title="{{ $name }}">{{ $initials }}</span>
