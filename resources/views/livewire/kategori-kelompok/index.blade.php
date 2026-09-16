<div>
    <x-ui.page-header title="Kategori Kelompok" :description="'Pengelompokan kelompok per mata kuliah pada '.$this->kelas->semesterAktif->nama.'. Satu mahasiswa hanya bisa berada di satu kelompok per kategori. Selama kategori masih terbuka, seluruh anggota kelas bisa menyusun kelompoknya; setelah ditandai final oleh admin kelas, kelompoknya terkunci.'">
        <x-slot:actions>
            <x-ui.export-button />
            <x-ui.button wire:click="openCreate" opens="showForm"><x-heroicon-m-plus class="size-4" /> Tambah Kategori</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card :padding="false">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:p-5">
            <div class="flex-1 sm:max-w-xs">
                <x-ui.search wire:model.live.debounce.500ms="search" placeholder="Cari kategori..." />
            </div>
            <x-ui.combobox name="mataKuliahId" wire:model.live="mataKuliahId" :options="$this->mataKuliahOptions->pluck('nama', 'id')" placeholder="Semua mata kuliah" clearable class="sm:w-56" />
            <div class="sm:ml-auto">
                <x-ui.per-page wire:model.live="perPage" />
            </div>
        </div>

        <div wire:loading.class="opacity-50" wire:target="search, mataKuliahId, perPage, gotoPage, nextPage, previousPage" class="transition-opacity">
            @if ($this->daftarKategori->isEmpty())
                @if ($this->mataKuliahOptions->isEmpty())
                    <x-ui.empty-state title="Belum ada mata kuliah" description="Tambahkan mata kuliah pada semester aktif terlebih dahulu sebelum membuat kategori kelompok." icon="heroicon-o-book-open">
                        <x-ui.button :href="route('mata-kuliah.index')" variant="secondary">Kelola mata kuliah</x-ui.button>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state title="Belum ada kategori kelompok" description="Buat kategori seperti Project Akhir, Presentasi, atau Praktikum untuk sebuah mata kuliah." icon="heroicon-o-rectangle-group">
                        <x-ui.button wire:click="openCreate" opens="showForm" variant="secondary"><x-heroicon-m-plus class="size-4" /> Tambah kategori</x-ui.button>
                    </x-ui.empty-state>
                @endif
            @else
                <x-ui.table>
                    <x-slot:head>
                        <x-ui.th>Kategori</x-ui.th>
                        <x-ui.th>Mata Kuliah</x-ui.th>
                        <x-ui.th>Status</x-ui.th>
                        <x-ui.th class="text-center">Jumlah Kelompok</x-ui.th>
                        <x-ui.th class="hidden sm:table-cell">Dibuat</x-ui.th>
                        <x-ui.th class="text-right">Aksi</x-ui.th>
                    </x-slot:head>
                    @foreach ($this->daftarKategori as $kategori)
                        <tr wire:key="kategori-{{ $kategori->id }}" class="transition hover:bg-slate-50/70">
                            <x-ui.td class="font-semibold text-slate-800">{{ $kategori->nama }}</x-ui.td>
                            <x-ui.td class="text-slate-600">{{ $kategori->mataKuliah->nama }}</x-ui.td>
                            <x-ui.td>
                                @if ($kategori->isFinal())
                                    <x-ui.badge color="amber" title="Kelompok pada kategori ini dikunci"><x-heroicon-m-lock-closed class="size-3.5" /> Final</x-ui.badge>
                                @else
                                    <x-ui.badge color="emerald" title="Semua anggota kelas bisa menyusun kelompoknya"><x-heroicon-m-lock-open class="size-3.5" /> Terbuka</x-ui.badge>
                                @endif
                            </x-ui.td>
                            <x-ui.td class="text-center">
                                <a href="{{ route('kelompok.index', ['kategori' => $kategori->id]) }}" wire:navigate class="font-medium text-primary-700 hover:underline">{{ $kategori->kelompok_count }}</a>
                            </x-ui.td>
                            <x-ui.td class="hidden text-slate-500 sm:table-cell">{{ $kategori->created_at->isoFormat('D MMM YYYY') }}</x-ui.td>
                            <x-ui.td class="text-right">
                                <x-ui.action-menu>
                                    <x-ui.menu-item :href="route('kelompok.index', ['kategori' => $kategori->id])" icon="heroicon-o-user-group">Lihat kelompok</x-ui.menu-item>
                                    @can('setFinal', $kategori)
                                        <x-ui.menu-item wire:click="toggleFinal({{ $kategori->id }})" :icon="$kategori->isFinal() ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed'">
                                            {{ $kategori->isFinal() ? 'Lepas status final' : 'Tandai final' }}
                                        </x-ui.menu-item>
                                    @endcan
                                    @can('update', $kategori)
                                        <x-ui.menu-item wire:click="openEdit({{ $kategori->id }})" opens="showForm" icon="heroicon-o-pencil-square">Edit</x-ui.menu-item>
                                        <x-ui.menu-item wire:click="confirmDelete({{ $kategori->id }})" opens="confirmingDelete" icon="heroicon-o-trash" danger>Hapus</x-ui.menu-item>
                                    @endcan
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

    <x-ui.modal model="showForm" :title="$form->kategori ? 'Edit Kategori' : 'Tambah Kategori'" max-width="max-w-md" loading="openCreate, openEdit">
        <form id="form-kategori" wire:submit="save" class="space-y-4">
            <x-ui.combobox label="Mata kuliah" name="form.mata_kuliah_id" wire:model="form.mata_kuliah_id" :options="$this->mataKuliahOptions->pluck('nama', 'id')" placeholder="Pilih mata kuliah" required />
            <x-ui.input label="Nama kategori" name="form.nama" wire:model="form.nama" placeholder="Contoh: Project Akhir" required />
        </form>
        <x-slot:footer>
            <x-ui.form-actions form="form-kategori" :label="$form->kategori ? 'Simpan Perubahan' : 'Tambah'" />
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm model="confirmingDelete" title="Hapus kategori?" description="Kategori hanya bisa dihapus jika tidak dipakai oleh kelompok mana pun." />
</div>
