@props(['label' => null, 'name', 'hint' => null, 'type' => 'text', 'required' => false])
@php $id = str_replace('.', '-', $name); @endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif
    <input
        id="{{ $id }}"
        type="{{ $type }}"
        {{ $attributes->except('class')->class([
            'block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-slate-50 disabled:text-slate-500',
            'border-rose-300 focus:border-rose-400' => $errors->has($name),
            'border-slate-200 focus:border-primary-900' => ! $errors->has($name),
        ]) }}
    >
    @error($name)
        <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
    @else
        @if ($hint)<p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>@endif
    @enderror
</div>
