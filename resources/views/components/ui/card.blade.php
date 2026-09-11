@props(['title' => null, 'description' => null, 'padding' => true])
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]']) }}>
    @if ($title || isset($actions))
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div>
                @if ($title)<h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>@endif
                @if ($description)<p class="mt-0.5 text-sm text-slate-500">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div @class(['p-5 sm:p-6' => $padding])>
        {{ $slot }}
    </div>
</div>
