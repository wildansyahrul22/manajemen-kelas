@props(['title', 'description' => null, 'icon' => 'heroicon-o-inbox'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-14 text-center']) }}>
    <span class="flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
        <x-dynamic-component :component="$icon" class="size-7" />
    </span>
    <h3 class="mt-4 text-base font-semibold text-slate-800">{{ $title }}</h3>
    @if ($description)<p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-5">{{ $slot }}</div>@endif
</div>
