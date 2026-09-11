@props(['options' => [10, 25, 50]])
@php $items = collect($options)->mapWithKeys(fn ($n) => [$n => "{$n} / halaman"])->all(); @endphp
<x-ui.select {{ $attributes }} name="perPage" :options="$items" size="sm" class="w-36" />
