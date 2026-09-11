@props(['title', 'description' => null, 'back' => null])
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" wire:navigate class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-primary-900 hover:underline">
                <x-heroicon-m-arrow-left class="size-4" /> Kembali
            </a>
        @endif
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h2>
        @if ($description)<p class="mt-1 text-sm text-slate-500">{{ $description }}</p>@endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
