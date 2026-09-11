@props(['label', 'name', 'description' => null])
@php $id = str_replace('.', '-', $name); @endphp
<label for="{{ $id }}" class="flex cursor-pointer items-start gap-3">
    <input id="{{ $id }}" type="checkbox" {{ $attributes->merge(['class' => 'mt-0.5 size-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500']) }}>
    <span>
        <span class="block text-sm font-medium text-slate-700">{{ $label }}</span>
        @if ($description)<span class="block text-xs text-slate-500">{{ $description }}</span>@endif
    </span>
</label>
