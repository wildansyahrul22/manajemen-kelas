{{--
    Shared markup for <x-ui.select> and <x-ui.combobox>.
    Expects: $label, $name, $options, $placeholder, $hint, $required, $clearable, $size, $searchable, $attributes, $errors.
    Binding: pass wire:model="prop" (or wire:model.live="prop"); the value is entangled with Alpine.
--}}
@php
    $wire = $attributes->wire('model');
    $model = $wire->value();
    $live = $wire->hasModifier('live');
    $items = collect($options)
        ->map(fn ($optionLabel, $optionValue) => ['value' => (string) $optionValue, 'label' => (string) $optionLabel])
        ->values();
    $id = str_replace('.', '-', $name);
    $hasError = $errors->has($name);
    $sizes = ['sm' => 'py-2 pl-3 pr-9 text-sm', 'md' => 'py-2.5 pl-3.5 pr-10 text-sm'];
@endphp
<div {{ $attributes->whereDoesntStartWith('wire:model')->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif

    <div
        x-data="uiSelect({
            value: $wire.entangle(@js($model)){!! $live ? '.live' : '' !!},
            placeholder: @js($placeholder),
            searchable: @js($searchable),
            clearable: @js($clearable),
        })"
        data-options="{{ json_encode($items) }}"
        x-on:keydown="onKeydown($event)"
        class="relative"
    >
        <button
            type="button"
            id="{{ $id }}"
            x-ref="trigger"
            x-on:click="toggle()"
            :aria-expanded="open"
            aria-haspopup="listbox"
            @class([
                'relative flex w-full items-center rounded-xl border bg-white text-left shadow-sm transition focus:outline-none focus:ring-2 focus:ring-primary-500/20',
                $sizes[$size] ?? $sizes['md'],
                'border-rose-300 focus:border-rose-400' => $hasError,
                'border-slate-200 hover:border-slate-300 focus:border-primary-500' => ! $hasError,
            ])
            :class="open && '{{ $hasError ? '' : 'border-primary-500 ring-2 ring-primary-500/20' }}'"
        >
            <span class="block truncate" :class="selected ? 'text-slate-800' : 'text-slate-400'" x-text="selected ? label : placeholder"></span>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                <x-heroicon-m-chevron-up-down class="size-4.5" />
            </span>
        </button>

        <div
            x-show="open"
            x-ref="panel"
            x-cloak
            x-transition:enter="transition duration-100 ease-out"
            x-transition:enter-start="-translate-y-1 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition duration-75 ease-in"
            x-transition:leave-end="opacity-0"
            x-on:click.outside="close()"
            :style="style"
            tabindex="-1"
            role="listbox"
            class="fixed z-[60] min-w-40 rounded-xl border border-slate-200 bg-white shadow-lg shadow-slate-900/10 focus:outline-none"
        >
            @if ($searchable)
                <div class="border-b border-slate-100 p-2">
                    <div class="relative">
                        <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <input
                            type="text"
                            x-ref="search"
                            x-model="search"
                            x-on:input="highlighted = 0"
                            placeholder="Cari..."
                            autocomplete="off"
                            class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                        >
                    </div>
                </div>
            @endif

            <ul x-ref="list" class="scrollbar-thin max-h-60 overflow-y-auto p-1.5">
                @if ($clearable)
                    <li>
                        <button type="button" x-on:click="clear()" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-slate-500 hover:bg-slate-100">
                            <x-heroicon-m-x-mark class="size-4" /> {{ $placeholder }}
                        </button>
                    </li>
                @endif

                <template x-for="(option, index) in filtered" :key="option.value">
                    <li
                        :data-index="index"
                        role="option"
                        :aria-selected="isSelected(option)"
                        x-on:click="select(option)"
                        x-on:mousemove="highlight(index)"
                        class="flex cursor-pointer items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm transition"
                        :class="{
                            'bg-slate-100 text-slate-900': highlighted === index && ! isSelected(option),
                            'bg-primary-900 text-white': isSelected(option),
                            'text-slate-700': highlighted !== index && ! isSelected(option),
                        }"
                    >
                        <span class="truncate" x-text="option.label"></span>
                        <x-heroicon-m-check class="size-4 shrink-0" x-show="isSelected(option)" x-cloak />
                    </li>
                </template>

                <li x-show="filtered.length === 0" x-cloak class="px-3 py-6 text-center text-sm text-slate-400">
                    Tidak ada pilihan yang cocok.
                </li>
            </ul>
        </div>
    </div>

    @if ($hasError)
        <p class="mt-1.5 text-sm text-rose-600">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
