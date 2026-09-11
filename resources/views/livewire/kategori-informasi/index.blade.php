<div>
    <x-ui.page-header title="Kategori Informasi" description="Kelompokkan informasi kelas agar mudah dicari.">
        <x-slot:actions>
            <x-ui.button wire:click="openCreate"><x-heroicon-m-plus class="size-4" /> Tambah Kategori</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card :padding="false">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:p-5">
            <div class="flex-1 sm:max-w-xs">
                <x-ui.search wire:model.live.debounce.500ms="search" placeholder="Cari kategori..." />
            </div>
            <div class="sm:ml-auto">
                <x-ui.per-page wire:model.live="perPage" />
            </div>
        </div>

        <div wire:loading.class="opacity-50" wire:target="search, perPage, gotoPage, nextPage, previousPage"
            class="transition-opacity">
            @if ($this->daftarKategori->isEmpty())
                <x-ui.empty-state title="Belum ada kategori"
                    description="Buat kategori seperti Pengumuman, Akademik, atau Kegiatan." icon="heroicon-o-tag">
                    <x-ui.button wire:click="openCreate" variant="secondary"><x-heroicon-m-plus class="size-4" /> Tambah
                        kategori</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.table>
                    <x-slot:head>
                        <x-ui.th>Kategori</x-ui.th>
                        <x-ui.th class="text-center">Jumlah Informasi</x-ui.th>
                        <x-ui.th class="hidden sm:table-cell">Dibuat</x-ui.th>
                        <x-ui.th class="text-right">Aksi</x-ui.th>
                    </x-slot:head>
                    @foreach ($this->daftarKategori as $kategori)
                        <tr wire:key="kategori-{{ $kategori->id }}" class="transition hover:bg-slate-50/70">
                            <x-ui.td><x-ui.badge :class="$kategori->badgeClass()">{{ $kategori->nama }}</x-ui.badge></x-ui.td>
                            <x-ui.td class="text-center">{{ $kategori->informasi_count }}</x-ui.td>
                            <x-ui.td
                                class="hidden text-slate-500 sm:table-cell">{{ $kategori->created_at->isoFormat('D MMM YYYY') }}</x-ui.td>
                            <x-ui.td class="text-right">
                                <x-ui.action-menu>
                                    <x-ui.menu-item wire:click="openEdit({{ $kategori->id }})"
                                        icon="heroicon-o-pencil-square">Edit</x-ui.menu-item>
                                    <x-ui.menu-item wire:click="confirmDelete({{ $kategori->id }})"
                                        icon="heroicon-o-trash" danger>Hapus</x-ui.menu-item>
                                </x-ui.action-menu>
                            </x-ui.td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </div>

        <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
            {{ $this->daftarKategori->links() }}
        </div>
    </x-ui.card>

    <x-ui.modal model="showForm" :title="$form->kategori ? 'Edit Kategori' : 'Tambah Kategori'" max-width="max-w-md">
        <form id="form-kategori" wire:submit="save" class="space-y-4">
            <x-ui.input label="Nama kategori" name="form.nama" wire:model="form.nama" placeholder="Contoh: Pengumuman"
                required />

            <div>
                <p class="mb-2 block text-sm font-medium text-slate-700">Warna label</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($warnaOptions as $warna => $kelasWarna)
                        <label class="cursor-pointer">
                            <input type="radio" name="form.warna" value="{{ $warna }}" wire:model="form.warna"
                                class="peer sr-only">
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-2 ring-transparent ring-offset-2 transition peer-checked:ring-primary-900 {{ $kelasWarna }}">{{ ucfirst($warna) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('form.warna')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </form>
        <x-slot:footer>
            <x-ui.form-actions form="form-kategori" :label="$form->kategori ? 'Simpan Perubahan' : 'Tambah'" />
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm model="confirmingDelete" title="Hapus kategori?"
        description="Kategori hanya bisa dihapus jika tidak dipakai oleh informasi mana pun." />
</div>
