@props(['href', 'active' => false, 'icon'])
@php $label = trim(strip_tags($slot->toHtml())); @endphp
<a
    href="{{ $href }}"
    wire:navigate
    x-on:click="sidebarOpen = false"
    :class="sidebarCollapsed && 'lg:justify-center lg:px-0'"
    :title="sidebarCollapsed ? @js($label) : null"
    @class([
        'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
        'bg-primary-900 text-white shadow-sm shadow-primary-900/20' => $active,
        'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $active,
    ])
    @if ($active) aria-current="page" @endif
>
    <x-dynamic-component :component="$icon" :class="'size-5 shrink-0 '.($active ? 'text-white' : 'text-slate-400 group-hover:text-slate-700')" />
    <span class="truncate" :class="sidebarCollapsed && 'lg:hidden'">{{ $slot }}</span>
</a>
