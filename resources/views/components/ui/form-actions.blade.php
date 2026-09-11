@props(['label' => 'Simpan', 'target' => 'save'])
<x-ui.button variant="secondary" x-on:click="show = false">Batal</x-ui.button>
<x-ui.button type="submit" form="{{ $attributes->get('form') }}" wire:loading.attr="disabled" wire:target="{{ $target }}">
    <x-heroicon-m-arrow-path class="size-4 animate-spin" wire:loading wire:target="{{ $target }}" />
    {{ $label }}
</x-ui.button>
