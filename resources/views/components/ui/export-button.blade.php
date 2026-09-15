{{-- Secondary button that triggers a Livewire action returning a file download (default: export). --}}
@props(['action' => 'export', 'label' => 'Export Excel'])
<x-ui.button variant="secondary" wire:click="{{ $action }}" wire:loading.attr="disabled" wire:target="{{ $action }}" {{ $attributes }}>
    <x-heroicon-m-arrow-down-tray class="size-4" wire:loading.remove wire:target="{{ $action }}" />
    <x-heroicon-m-arrow-path class="size-4 animate-spin" wire:loading wire:target="{{ $action }}" />
    {{ $label }}
</x-ui.button>
