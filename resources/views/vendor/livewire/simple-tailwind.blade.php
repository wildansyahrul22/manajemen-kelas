@php
    $pageName = $paginator->getPageName();
    $button = 'inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40';
@endphp
<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between gap-2">
            <button type="button" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled" @disabled($paginator->onFirstPage()) class="{{ $button }}">
                <x-heroicon-m-chevron-left class="size-4" /> {{ __('pagination.previous') }}
            </button>
            <button type="button" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled" @disabled(! $paginator->hasMorePages()) class="{{ $button }}">
                {{ __('pagination.next') }} <x-heroicon-m-chevron-right class="size-4" />
            </button>
        </nav>
    @endif
</div>
