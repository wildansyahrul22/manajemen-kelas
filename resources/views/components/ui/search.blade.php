@props(['placeholder' => 'Cari...'])
<div class="relative">
    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-slate-400" />
    <input type="search" placeholder="{{ $placeholder }}" {{ $attributes->merge(['class' => 'block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20']) }}>
</div>
