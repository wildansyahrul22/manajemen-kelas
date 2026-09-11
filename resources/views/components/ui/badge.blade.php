@props(['color' => 'slate', 'class' => null])
@php
    $colors = [
        'slate' => 'bg-slate-100 text-slate-700',
        'primary' => 'bg-primary-100 text-primary-800',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'rose' => 'bg-rose-50 text-rose-700',
        'sky' => 'bg-sky-50 text-sky-700',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium '.($class ?? $colors[$color] ?? $colors['slate'])]) }}>{{ $slot }}</span>
