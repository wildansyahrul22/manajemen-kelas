<div>
    <x-ui.page-header title="Kelas" description="Daftar kelas yang terdaftar pada sistem.">
        <x-slot:actions>
            <x-ui.export-button />
            <x-ui.button wire:click="openCreate" opens="showForm"><x-heroicon-m-plus class="size-4" /> Tambah Kelas</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card :padding="false">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:p-5">
            <div class="flex-1 sm:max-w-xs">
                <x-ui.search wire:model.live.debounce.500ms="search" placeholder="Cari kelas..." />
            </div>
            <div class="sm:ml-auto">
                <x-ui.per-page wire:model.live="perPage" />
            </div>
        </div>

        <div wire:loading.class="opacity-50" wire:target="search, perPage, gotoPage, nextPage, previousPage"
            class="transition-opacity">
            @if ($this->daftarKelas->isEmpty())
                <x-ui.empty-state title="Belum ada kelas"
                    description="Tambahkan kelas pertama, lalu buat akun admin kelasnya di menu Users."
                    icon="heroicon-o-building-library">
                    <x-ui.button wire:click="openCreate" opens="showForm" variant="secondary"><x-heroicon-m-plus class="size-4" /> Tambah
                        kelas</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.table>
                    <x-slot:head>
                        <x-ui.th>Kelas</x-ui.th>
                        <x-ui.th class="hidden md:table-cell">Angkatan</x-ui.th>
                        <x-ui.th>Semester Aktif</x-ui.th>
                        <x-ui.th class="hidden text-center sm:table-cell">Mahasiswa</x-ui.th>
                        <x-ui.th class="hidden text-center lg:table-cell">Mata Kuliah</x-ui.th>
                        <x-ui.th class="text-right">Aksi</x-ui.th>
                    </x-slot:head>
                    @foreach ($this->daftarKelas as $kelas)
                        <tr wire:key="kelas-{{ $kelas->id }}" class="transition hover:bg-slate-50/70">
                            <x-ui.td>
                                <p class="font-semibold text-slate-800">{{ $kelas->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $kelas->prodi ?: '—' }}<span class="md:hidden"> ·
                                        {{ $kelas->angkatan }}</span></p>
                            </x-ui.td>
                            <x-ui.td class="hidden md:table-cell">{{ $kelas->angkatan }}</x-ui.td>
                            <x-ui.td><x-ui.badge
                                    color="primary">{{ $kelas->semesterAktif->nama }}</x-ui.badge></x-ui.td>
                            <x-ui.td class="hidden text-center sm:table-cell">{{ $kelas->mahasiswa_count }}</x-ui.td>
                            <x-ui.td class="hidden text-center lg:table-cell">{{ $kelas->mata_kuliah_count }}</x-ui.td>
                            <x-ui.td class="text-right">
                                <x-ui.action-menu>
                                    <x-ui.menu-item wire:click="openEdit({{ $kelas->id }})" opens="showForm"
                                        icon="heroicon-o-pencil-square">Edit</x-ui.menu-item>
                                    <x-ui.menu-item wire:click="confirmDelete({{ $kelas->id }})" opens="confirmingDelete"
                                        icon="heroicon-o-trash" danger>Hapus</x-ui.menu-item>
                                </x-ui.action-menu>
                            </x-ui.td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </div>

        <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
            {{ $this->daftarKelas->links() }}
        </div>
    </x-ui.card>

    <x-ui.modal model="showForm" :title="$form->kelas ? 'Edit Kelas' : 'Tambah Kelas'" loading="openCreate, openEdit">
        <form id="form-kelas" wire:submit="save" class="space-y-4">
            <x-ui.input label="Nama kelas" name="form.nama" wire:model="form.nama" placeholder="Contoh: TI-3A"
                required />
            <x-ui.input label="Program studi" name="form.prodi" wire:model="form.prodi"
                placeholder="Contoh: Teknik Informatika (opsional)" />
            <div class="grid grid-cols-2 gap-4">
                <x-ui.input label="Angkatan" name="form.angkatan" type="number" wire:model="form.angkatan"
                    placeholder="{{ now()->year }}" required />
                <x-ui.combobox label="Semester aktif" name="form.semester_aktif_id" wire:model="form.semester_aktif_id"
                    :options="$this->semesterOptions->pluck('nama', 'id')" placeholder="Pilih semester" required />
            </div>
        </form>
        <x-slot:footer>
            <x-ui.form-actions form="form-kelas" :label="$form->kelas ? 'Simpan Perubahan' : 'Tambah Kelas'" />
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm model="confirmingDelete" title="Hapus kelas?"
        description="Kelas hanya bisa dihapus jika tidak memiliki user. Mata kuliah, jadwal, tugas, dan informasi kelas akan ikut terhapus." />
</div>
