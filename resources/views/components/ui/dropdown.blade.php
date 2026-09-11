@props(['align' => 'right', 'width' => 'w-56'])
<div x-data="{ open: false, toggle() { this.open = ! this.open }, close() { this.open = false } }" x-on:keydown.escape.window="close()" class="relative">
    <div x-on:click="toggle()">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-on:click.outside="close()"
        x-transition:enter="transition duration-150 ease-out"
        x-transition:enter-start="scale-95 opacity-0"
        x-transition:enter-end="scale-100 opacity-100"
        x-transition:leave="transition duration-100 ease-in"
        x-transition:leave-end="scale-95 opacity-0"
        x-cloak
        @class(['absolute z-30 mt-2 origin-top rounded-xl border border-slate-200 bg-white shadow-lg shadow-slate-900/10', $width, 'right-0' => $align === 'right', 'left-0' => $align === 'left'])
    >
        {{ $slot }}
    </div>
</div>
