<div>
    <x-ui.page-header title="Kelompok" :description="'Kelompok belajar / project kelas ' .
        $this->kelas->nama .
        ' pada ' .
        $this->kelas->semesterAktif->nama .
        '.'">
        <x-slot:actions>
            <x-ui.export-button />
            <x-ui.button wire:click="openCreate" opens="showForm"><x-heroicon-m-plus class="size-4" /> Buat Kelompok</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="flex-1 lg:max-w-xs">
            <x-ui.search wire:model.live.debounce.500ms="search" placeholder="Cari nama kelompok..." />
        </div>
        <x-ui.combobox name="mataKuliahId" wire:model.live="mataKuliahId" :options="$this->mataKuliahOptions->pluck('nama', 'id')" placeholder="Semua mata kuliah"
            clearable class="sm:w-56" />
        <x-ui.combobox name="kategoriId" wire:model.live="kategoriId" :options="$this->kategoriFilterOptions" placeholder="Semua kategori"
            clearable class="sm:w-56" />
        <div class="flex flex-col gap-1">
            <x-ui.whatsapp-button :text="$this->teksWhatsApp" label="Bagikan ke WhatsApp" disabled-title="Pilih kategori terlebih dahulu" class="whitespace-nowrap" />
            @if ($kategoriId === '')
                <p class="text-xs text-slate-500">Pilih kategori dulu untuk membagikan pembagian kelompoknya.</p>
            @endif
        </div>
        <div class="lg:ml-auto">
            <x-ui.per-page wire:model.live="perPage" />
        </div>
    </div>

    <div wire:loading.class="opacity-50" wire:target="search, mataKuliahId, perPage, gotoPage, nextPage, previousPage"
        class="transition-opacity">
        @if ($this->daftarKelompok->isEmpty())
            <x-ui.card :padding="false">
                @if ($this->kategoriOptions->isEmpty())
                    <x-ui.empty-state title="Belum ada kategori kelompok"
                        description="Buat kategori kelompok (misal: Project Akhir) pada mata kuliah semester aktif terlebih dahulu."
                        icon="heroicon-o-rectangle-group">
                        <x-ui.button :href="route('kategori-kelompok.index')" variant="secondary"><x-heroicon-m-plus class="size-4" /> Buat kategori</x-ui.button>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state title="Belum ada kelompok"
                        description="Buat kelompok dan tambahkan anggotanya dari daftar mahasiswa kelas."
                        icon="heroicon-o-user-group">
                        <x-ui.button wire:click="openCreate" opens="showForm" variant="secondary"><x-heroicon-m-plus class="size-4" /> Buat
                            kelompok</x-ui.button>
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($this->daftarKelompok as $kelompok)
                    @php $ketua = $kelompok->anggota->first(fn ($anggota) => (bool) $anggota->pivot->is_ketua); @endphp
                    <x-ui.card wire:key="kelompok-{{ $kelompok->id }}"
                        class="flex flex-col transition hover:border-primary-200 hover:shadow-md" :padding="false">
                        <div class="flex-1 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-medium text-slate-500">
                                        {{ $kelompok->mataKuliah->nama }}</p>
                                    <x-ui.badge class="mt-1 bg-slate-100 text-slate-700">{{ $kelompok->kategori->nama }}</x-ui.badge>
                                    <a href="{{ route('kelompok.show', $kelompok) }}" wire:navigate
                                        class="mt-0.5 block truncate text-lg font-bold text-slate-900 hover:text-primary-900 hover:underline">{{ $kelompok->nama }}</a>
                                </div>
                                @if (auth()->user()->can('update', $kelompok))
                                    <x-ui.action-menu class="-mr-1.5 -mt-1">
                                        <x-ui.menu-item :href="route('kelompok.show', $kelompok)" icon="heroicon-o-eye">Lihat
                                            detail</x-ui.menu-item>
                                        <x-ui.menu-item wire:click="openEdit({{ $kelompok->id }})" opens="showForm"
                                            icon="heroicon-o-pencil-square">Edit</x-ui.menu-item>
                                        <x-ui.menu-item wire:click="confirmDelete({{ $kelompok->id }})" opens="confirmingDelete"
                                            icon="heroicon-o-trash" danger>Hapus</x-ui.menu-item>
                                    </x-ui.action-menu>
                                @endif
                            </div>
                            @if ($kelompok->deskripsi)
                                <p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ $kelompok->deskripsi }}</p>
                            @endif
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-3.5">
                            <div class="flex -space-x-2">
                                @foreach ($kelompok->anggota->take(5) as $anggota)
                                    <x-ui.avatar :name="$anggota->name" size="xs" class="ring-2 ring-white" />
                                @endforeach
                                @if ($kelompok->anggota->count() > 5)
                                    <span
                                        class="inline-flex size-7 items-center justify-center rounded-full bg-slate-100 text-[11px] font-semibold text-slate-600 ring-2 ring-white">+{{ $kelompok->anggota->count() - 5 }}</span>
                                @endif
                            </div>
                            <div class="min-w-0 text-right text-xs text-slate-500">
                                <p>{{ $kelompok->anggota->count() }} anggota</p>
                                @if ($ketua)
                                    <p class="truncate">Ketua: <span
                                            class="font-medium text-slate-700">{{ $ketua->name }}</span></p>
                                @endif
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mt-5">
        {{ $this->daftarKelompok->links() }}
    </div>

    @include('livewire.kelompok.partials.form-modal')
    <x-ui.confirm model="confirmingDelete" title="Hapus kelompok?"
        description="Kelompok beserta daftar anggotanya akan dihapus." />
</div>
