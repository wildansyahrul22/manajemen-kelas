<div>
    <x-ui.page-header title="Mata Kuliah" :description="'Mata kuliah kelas ' . $this->kelas->nama . ' per semester.'">
        @if ($this->canManage)
            <x-slot:actions>
                <x-ui.button wire:click="openCreate" opens="showForm"><x-heroicon-m-plus class="size-4" /> Tambah Mata Kuliah</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card :padding="false">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center">
            <div class="flex-1 lg:max-w-xs">
                <x-ui.search wire:model.live.debounce.500ms="search" placeholder="Cari mata kuliah / dosen..." />
            </div>
            <x-ui.combobox name="semesterId" wire:model.live="semesterId" :options="$this->semesterOptions->pluck('nama', 'id')" :placeholder="'Semester aktif (' . $this->kelas->semesterAktif->nama . ')'" clearable
                class="sm:w-60" />
            <div class="lg:ml-auto">
                <x-ui.per-page wire:model.live="perPage" />
            </div>
        </div>

        <div wire:loading.class="opacity-50" wire:target="search, semesterId, perPage, gotoPage, nextPage, previousPage"
            class="transition-opacity">
            @if ($this->daftarMataKuliah->isEmpty())
                <x-ui.empty-state title="Belum ada mata kuliah" description="Belum ada mata kuliah pada semester ini."
                    icon="heroicon-o-book-open">
                    @if ($this->canManage)
                        <x-ui.button wire:click="openCreate" opens="showForm" variant="secondary"><x-heroicon-m-plus class="size-4" />
                            Tambah mata kuliah</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @else
                <x-ui.table>
                    <x-slot:head>
                        <x-ui.th>Mata Kuliah</x-ui.th>
                        <x-ui.th class="hidden md:table-cell">Dosen</x-ui.th>
                        <x-ui.th class="text-center">SKS</x-ui.th>
                        <x-ui.th class="hidden text-center lg:table-cell">Jadwal</x-ui.th>
                        <x-ui.th class="hidden text-center lg:table-cell">Lab</x-ui.th>
                        <x-ui.th class="hidden text-center lg:table-cell">Tugas</x-ui.th>
                        @if ($this->canManage)
                            <x-ui.th class="text-right">Aksi</x-ui.th>
                        @endif
                    </x-slot:head>

                    @foreach ($this->daftarMataKuliah as $mataKuliah)
                        <tr wire:key="mk-{{ $mataKuliah->id }}" class="transition hover:bg-slate-50/70">
                            <x-ui.td>
                                <a href="{{ route('mata-kuliah.show', $mataKuliah) }}" wire:navigate
                                    class="font-semibold text-slate-800 hover:text-primary-900 hover:underline">{{ $mataKuliah->nama }}</a>
                                <p class="text-xs text-slate-500">{{ $mataKuliah->kode ?: '—' }}<span class="md:hidden">
                                        · {{ $mataKuliah->dosen }}</span></p>
                            </x-ui.td>
                            <x-ui.td class="hidden md:table-cell">{{ $mataKuliah->dosen }}</x-ui.td>
                            <x-ui.td class="text-center font-medium">{{ $mataKuliah->sks }}</x-ui.td>
                            <x-ui.td
                                class="hidden text-center text-slate-500 lg:table-cell">{{ $mataKuliah->jadwal_count }}</x-ui.td>
                            <x-ui.td
                                class="hidden text-center text-slate-500 lg:table-cell">{{ $mataKuliah->jadwal_lab_count }}</x-ui.td>
                            <x-ui.td
                                class="hidden text-center text-slate-500 lg:table-cell">{{ $mataKuliah->tugas_count }}</x-ui.td>
                            @if ($this->canManage)
                                <x-ui.td class="text-right">
                                    <x-ui.action-menu>
                                        <x-ui.menu-item :href="route('mata-kuliah.show', $mataKuliah)" icon="heroicon-o-eye">Lihat
                                            detail</x-ui.menu-item>
                                        <x-ui.menu-item wire:click="openEdit({{ $mataKuliah->id }})" opens="showForm"
                                            icon="heroicon-o-pencil-square">Edit</x-ui.menu-item>
                                        <x-ui.menu-item wire:click="confirmDelete({{ $mataKuliah->id }})" opens="confirmingDelete"
                                            icon="heroicon-o-trash" danger>Hapus</x-ui.menu-item>
                                    </x-ui.action-menu>
                                </x-ui.td>
                            @endif
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </div>

        <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
            {{ $this->daftarMataKuliah->links() }}
        </div>
    </x-ui.card>

    @if ($this->canManage)
        @include('livewire.mata-kuliah.partials.form-modal')
        <x-ui.confirm model="confirmingDelete" title="Hapus mata kuliah?"
            description="Semua jadwal, tugas, dan kelompok pada mata kuliah ini ikut terhapus." />
    @endif
</div>
