@props(['model', 'title' => 'Hapus data?', 'description' => 'Data yang dihapus tidak dapat dikembalikan.', 'action' => 'delete', 'label' => 'Hapus'])
<x-ui.modal :model="$model" max-width="max-w-md">
    <div class="flex items-start gap-4">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
            <x-heroicon-o-exclamation-triangle class="size-6" />
        </span>
        <div>
            <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
            {{ $slot }}
        </div>
    </div>
    <x-slot:footer>
        <x-ui.button variant="secondary" x-on:click="show = false">Batal</x-ui.button>
        <x-ui.button variant="danger-solid" wire:click="{{ $action }}" wire:loading.attr="disabled">{{ $label }}</x-ui.button>
    </x-slot:footer>
</x-ui.modal>
