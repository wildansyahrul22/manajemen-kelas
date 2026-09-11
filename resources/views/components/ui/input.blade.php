@props(['label' => null, 'name', 'hint' => null, 'type' => 'text', 'required' => false])
@php
    $id = str_replace('.', '-', $name);
    $isPassword = $type === 'password';
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif
    @if ($isPassword)<div x-data="{ show: false }" class="relative">@endif
    <input
        id="{{ $id }}"
        type="{{ $type }}"
        @if ($isPassword) x-bind:type="show ? 'text' : 'password'" @endif
        {{ $attributes->except('class')->class([
            'block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-slate-50 disabled:text-slate-500',
            'pr-11' => $isPassword,
            'border-rose-300 focus:border-rose-400' => $errors->has($name),
            'border-slate-200 focus:border-primary-900' => ! $errors->has($name),
        ]) }}
    >
    @if ($isPassword)
        <button
            type="button"
            x-on:click="show = ! show"
            :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
            :title="show ? 'Sembunyikan password' : 'Tampilkan password'"
            :aria-pressed="show"
            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 transition hover:text-slate-600 focus:outline-none focus-visible:text-primary-900"
        >
            <x-heroicon-o-eye class="size-5" x-show="! show" />
            <x-heroicon-o-eye-slash class="size-5" x-show="show" x-cloak />
        </button>
    </div>
    @endif
    @error($name)
        <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
    @else
        @if ($hint)<p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>@endif
    @enderror
</div>
