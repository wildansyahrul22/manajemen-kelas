@props([])
<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-slate-200 text-sm']) }}>
        <thead class="bg-slate-50/80">
            <tr>{{ $head }}</tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            {{ $slot }}
        </tbody>
    </table>
</div>
