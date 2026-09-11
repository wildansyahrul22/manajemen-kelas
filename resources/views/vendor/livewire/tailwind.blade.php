@php
    $pageName = $paginator->getPageName();
    $suffix = $pageName === 'page' ? '' : ".{$pageName}";
    $scrollTo ??= 'body';
    $scrollSnippet = $scrollTo !== false
        ? "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView({ block: 'start' })"
        : '';
    $button = 'inline-flex size-9 items-center justify-center rounded-lg border text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-40';
    $idle = 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900';
    $current = 'border-primary-900 bg-primary-900 text-white shadow-sm';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">
                Menampilkan
                <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
                dari <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span> data
            </p>

            <div class="flex items-center gap-1">
                <button
                    type="button"
                    wire:click="previousPage('{{ $pageName }}')"
                    x-on:click="{{ $scrollSnippet }}"
                    wire:loading.attr="disabled"
                    dusk="previousPage{{ $suffix }}"
                    @disabled($paginator->onFirstPage())
                    class="{{ $button }} {{ $idle }}"
                    aria-label="{{ __('pagination.previous') }}"
                >
                    <x-heroicon-m-chevron-left class="size-4" />
                </button>

                @foreach ($paginator->links()->elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex size-9 items-center justify-center text-sm text-slate-400" aria-disabled="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <span wire:key="paginator-{{ $pageName }}-page-{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span class="{{ $button }} {{ $current }}" aria-current="page">{{ $page }}</span>
                                @else
                                    <button
                                        type="button"
                                        wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                                        x-on:click="{{ $scrollSnippet }}"
                                        class="{{ $button }} {{ $idle }} hidden sm:inline-flex"
                                        aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                    >{{ $page }}</button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach

                <span class="px-2 text-sm text-slate-500 sm:hidden">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

                <button
                    type="button"
                    wire:click="nextPage('{{ $pageName }}')"
                    x-on:click="{{ $scrollSnippet }}"
                    wire:loading.attr="disabled"
                    dusk="nextPage{{ $suffix }}"
                    @disabled(! $paginator->hasMorePages())
                    class="{{ $button }} {{ $idle }}"
                    aria-label="{{ __('pagination.next') }}"
                >
                    <x-heroicon-m-chevron-right class="size-4" />
                </button>
            </div>
        </nav>
    @elseif ($paginator->total() > 0)
        <p class="text-sm text-slate-500">
            Menampilkan <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span> data
        </p>
    @endif
</div>
