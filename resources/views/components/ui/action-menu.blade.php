@props(['width' => 'w-44', 'label' => 'Aksi'])
<div x-data="uiMenu()" class="inline-flex">
    <button
        type="button"
        x-ref="trigger"
        x-on:click="toggle()"
        :aria-expanded="open"
        aria-haspopup="menu"
        aria-label="{{ $label }}"
        {{ $attributes->merge(['class' => 'rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/30']) }}
        :class="open && 'bg-slate-100 text-slate-900'"
    >
        <x-heroicon-m-ellipsis-vertical class="size-5" />
    </button>

    <div
        x-show="open"
        x-ref="panel"
        x-cloak
        x-transition:enter="transition duration-100 ease-out"
        x-transition:enter-start="scale-95 opacity-0"
        x-transition:enter-end="scale-100 opacity-100"
        x-transition:leave="transition duration-75 ease-in"
        x-transition:leave-end="scale-95 opacity-0"
        x-on:click.outside="close()"
        x-on:keydown.escape.window="close()"
        :style="style"
        role="menu"
        class="fixed z-[60] {{ $width }} origin-top-right rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg shadow-slate-900/10"
    >
        {{ $slot }}
    </div>
</div>
