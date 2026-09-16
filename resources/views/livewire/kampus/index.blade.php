<div>
    <x-ui.page-header title="Kampus" description="Daftar universitas tempat kelas bernaung.">
        <x-slot:actions>
            <x-ui.export-button />
            <x-ui.button wire:click="openCreate" opens="showForm"><x-heroicon-m-plus class="size-4" /> Tambah Kampus</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card :padding="false">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:p-5">
            <div class="flex-1 sm:max-w-xs">
                <x-ui.search wire:model.live.debounce.500ms="search" placeholder="Cari kampus..." />
            </div>
            <div class="sm:ml-auto">
                <x-ui.per-page wire:model.live="perPage" />
            </div>
        </div>

        <div wire:loading.class="opacity-50" wire:target="search, perPage, gotoPage, nextPage, previousPage"
            class="transition-opacity">
            @if ($this->daftarKampus->isEmpty())
                <x-ui.empty-state title="Belum ada kampus"
                    description="Tambahkan kampus dulu, karena setiap kelas harus bernaung di satu kampus."
                    icon="heroicon-o-academic-cap">
                    <x-ui.button wire:click="openCreate" opens="showForm" variant="secondary"><x-heroicon-m-plus class="size-4" /> Tambah
                        kampus</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.table>
                    <x-slot:head>
                        <x-ui.th>Kampus</x-ui.th>
                        <x-ui.th class="text-center">Kelas</x-ui.th>
                        <x-ui.th class="hidden md:table-cell">Dibuat</x-ui.th>
                        <x-ui.th class="text-right">Aksi</x-ui.th>
                    </x-slot:head>
                    @foreach ($this->daftarKampus as $kampus)
                        <tr wire:key="kampus-{{ $kampus->id }}" class="transition hover:bg-slate-50/70">
                            <x-ui.td>
                                <p class="font-semibold text-slate-800">{{ $kampus->nama }}</p>
                            </x-ui.td>
                            <x-ui.td class="text-center">{{ $kampus->kelas_count }}</x-ui.td>
                            <x-ui.td class="hidden md:table-cell">{{ $kampus->created_at->isoFormat('D MMM YYYY') }}</x-ui.td>
                            <x-ui.td class="text-right">
                                <x-ui.action-menu>
                                    <x-ui.menu-item wire:click="openEdit({{ $kampus->id }})" opens="showForm"
                                        icon="heroicon-o-pencil-square">Edit</x-ui.menu-item>
                                    <x-ui.menu-item wire:click="confirmDelete({{ $kampus->id }})" opens="confirmingDelete"
                                        icon="heroicon-o-trash" danger>Hapus</x-ui.menu-item>
                                </x-ui.action-menu>
                            </x-ui.td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </div>

        <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
            {{ $this->daftarKampus->links() }}
        </div>
    </x-ui.card>

    <x-ui.modal model="showForm" :title="$form->kampus ? 'Edit Kampus' : 'Tambah Kampus'" loading="openCreate, openEdit">
        <form id="form-kampus" wire:submit="save" class="space-y-4">
            <x-ui.input label="Nama kampus" name="form.nama" wire:model="form.nama"
                placeholder="Contoh: Universitas Indraprasta PGRI" required />
        </form>
        <x-slot:footer>
            <x-ui.form-actions form="form-kampus" :label="$form->kampus ? 'Simpan Perubahan' : 'Tambah Kampus'" />
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm model="confirmingDelete" title="Hapus kampus?"
        description="Kampus hanya bisa dihapus jika tidak memiliki kelas." />
</div>
