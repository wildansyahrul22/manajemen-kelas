{{--
    Sidebar link. The active look hangs off aria-current="page" so the client can move it to the
    clicked link the moment a wire:navigate visit starts (see resources/js/app.js).
--}}
@props(['href', 'active' => false, 'icon'])
@php $label = trim(strip_tags($slot->toHtml())); @endphp
<a
    href="{{ $href }}"
    wire:navigate.hover
    data-nav-link
    x-on:click="sidebarOpen = false"
    :class="sidebarCollapsed && 'lg:justify-center lg:px-0'"
    :title="sidebarCollapsed ? @js($label) : null"
    class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 aria-[current=page]:bg-primary-900 aria-[current=page]:text-white aria-[current=page]:shadow-sm aria-[current=page]:shadow-primary-900/20 aria-[current=page]:hover:bg-primary-900 aria-[current=page]:hover:text-white"
    @if ($active) aria-current="page" @endif
>
    <x-dynamic-component :component="$icon" class="size-5 shrink-0 text-slate-400 group-hover:text-slate-700 group-aria-[current=page]:text-white group-aria-[current=page]:group-hover:text-white" />
    <span class="truncate" :class="sidebarCollapsed && 'lg:hidden'">{{ $slot }}</span>
</a>
