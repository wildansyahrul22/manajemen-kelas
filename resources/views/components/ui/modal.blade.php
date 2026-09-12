{{--
    Modal bound to a boolean Livewire property. Open it from the client first (`opens="model"` on
    <x-ui.button> / <x-ui.menu-item>, or `x-on:click="$wire.model = true"`) so it appears instantly;
    pass `loading` (a wire:target list) to cover the body with a spinner until the server action
    that fills it has finished.
--}}
@props(['model', 'title' => null, 'description' => null, 'maxWidth' => 'max-w-lg', 'loading' => null])
<div
    x-data="{ show: $wire.entangle('{{ $model }}') }"
    x-show="show"
    x-on:keydown.escape.window="show = false"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
>
    <div x-show="show" x-transition.opacity.duration.200ms class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" x-on:click="show = false"></div>

    <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
        <div
            x-show="show"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-4 opacity-0 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-end="translate-y-4 opacity-0 sm:scale-95"
            x-trap.noscroll="show"
            class="relative w-full {{ $maxWidth }} rounded-2xl bg-white shadow-xl"
        >
            @if ($loading)
                <div wire:loading.flex wire:target="{{ $loading }}" class="absolute inset-0 z-10 flex-col items-center justify-center gap-3 rounded-2xl bg-white/90 text-sm text-slate-500" aria-live="polite">
                    <x-heroicon-m-arrow-path class="size-6 animate-spin text-primary-900" />
                    <span>Memuat...</span>
                </div>
            @endif

            @if ($title)
                <div class="flex items-start justify-between gap-4 px-6 pt-6">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
                        @if ($description)<p class="mt-1 text-sm text-slate-500">{{ $description }}</p>@endif
                    </div>
                    <button type="button" x-on:click="show = false" class="-mr-1 -mt-1 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                        <x-heroicon-o-x-mark class="size-5" />
                    </button>
                </div>
            @endif

            <div class="px-6 py-5">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex flex-col-reverse gap-2 rounded-b-2xl border-t border-slate-100 bg-slate-50/70 px-6 py-4 sm:flex-row sm:justify-end">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
