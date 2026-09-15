<div>
    <x-ui.page-header title="Jadwal Lab" :description="'Jadwal praktikum kelas '.$this->kelas->nama.' pada '.$this->kelas->semesterAktif->nama.'.'">
        <x-slot:actions>
            <x-ui.export-button />
            @if ($this->canManage)
                <x-ui.button wire:click="openCreate" opens="showForm"><x-heroicon-m-plus class="size-4" /> Tambah Jadwal Lab</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->mataKuliahOptions->isEmpty())
        <x-ui.card :padding="false">
            <x-ui.empty-state title="Belum ada mata kuliah" description="Tambahkan mata kuliah pada semester aktif terlebih dahulu sebelum menyusun jadwal lab." icon="heroicon-o-book-open">
                @if ($this->canManage)
                    <x-ui.button :href="route('mata-kuliah.index')" variant="secondary">Kelola mata kuliah</x-ui.button>
                @endif
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
            <x-ui.combobox name="mataKuliahId" wire:model.live="mataKuliahId" :options="$this->mataKuliahOptions->pluck('nama', 'id')" placeholder="Semua mata kuliah" clearable class="sm:w-64" />
            <x-ui.select name="status" wire:model.live="status" :options="['mendatang' => 'Mendatang', 'lewat' => 'Sudah lewat']" placeholder="Semua sesi" clearable class="sm:w-44" />
        </div>

        <div wire:loading.class="opacity-50" wire:target="mataKuliahId, status" class="transition-opacity">
            @if ($this->mataKuliahDenganJadwal->isEmpty())
                @php $adaFilter = $mataKuliahId !== '' || $status !== ''; @endphp
                <x-ui.card :padding="false">
                    <x-ui.empty-state :title="$adaFilter ? 'Jadwal lab tidak ditemukan' : 'Belum ada jadwal lab'" :description="$adaFilter ? 'Tidak ada sesi praktikum yang cocok dengan filter yang dipilih.' : 'Pilih mata kuliah lalu masukkan tanggal, jam, dan ruangan sesi praktikumnya.'" icon="heroicon-o-beaker">
                        @if ($this->canManage)
                            <x-ui.button wire:click="openCreate" opens="showForm" variant="secondary"><x-heroicon-m-plus class="size-4" /> Tambah jadwal lab</x-ui.button>
                        @endif
                    </x-ui.empty-state>
                </x-ui.card>
            @else
                <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->mataKuliahDenganJadwal as $mataKuliah)
                        <x-ui.card wire:key="mk-{{ $mataKuliah->id }}" :padding="false">
                            <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                                <div class="min-w-0">
                                    <a href="{{ route('mata-kuliah.show', $mataKuliah) }}" wire:navigate class="block truncate font-semibold text-slate-900 hover:text-primary-900 hover:underline">{{ $mataKuliah->nama }}</a>
                                    <p class="truncate text-xs text-slate-500">{{ $mataKuliah->dosen }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="text-xs text-slate-400">{{ $mataKuliah->jadwalLab->count() }} sesi</span>
                                    @if ($this->canManage)
                                        <button type="button" wire:click="openCreate({{ $mataKuliah->id }})" x-on:click="$wire.showForm = true" class="-mr-1.5 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-primary-900" aria-label="Tambah jadwal lab {{ $mataKuliah->nama }}" title="Tambah jadwal lab {{ $mataKuliah->nama }}">
                                            <x-heroicon-m-plus class="size-4" />
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @foreach ($mataKuliah->jadwalLab as $jadwal)
                                @php $lewat = $jadwal->isLewat(); $hariIni = $jadwal->isHariIni(); @endphp
                                <div wire:key="lab-{{ $jadwal->id }}" @class(['group flex items-start gap-3 border-b border-slate-100 px-5 py-4 last:border-b-0', 'opacity-60' => $lewat])>
                                    <div class="w-12 shrink-0 text-center" title="{{ $jadwal->tanggal->isoFormat('dddd, D MMMM YYYY') }}">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $jadwal->tanggal->isoFormat('ddd') }}</p>
                                        <p class="text-lg font-bold leading-tight text-slate-900">{{ $jadwal->tanggal->format('j') }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $jadwal->tanggal->isoFormat($jadwal->tanggal->isCurrentYear() ? 'MMM' : 'MMM YYYY') }}</p>
                                    </div>
                                    <div @class(['min-w-0 flex-1 border-l-2 pl-3', 'border-primary-900' => $hariIni && ! $lewat, 'border-slate-200' => ! $hariIni || $lewat])>
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <p class="text-sm font-semibold text-slate-800">{{ $jadwal->jam() }}</p>
                                            @if ($lewat)
                                                <x-ui.badge>Lewat</x-ui.badge>
                                            @elseif ($hariIni)
                                                <x-ui.badge color="primary">Hari ini</x-ui.badge>
                                            @endif
                                        </div>
                                        @if ($jadwal->ruangan)
                                            <p class="mt-0.5 inline-flex items-center gap-1 text-xs text-slate-500"><x-heroicon-m-map-pin class="size-3.5" /> {{ $jadwal->ruangan }}</p>
                                        @endif
                                        @if ($jadwal->keterangan)
                                            <p class="mt-1 text-xs text-slate-500">{{ $jadwal->keterangan }}</p>
                                        @endif
                                    </div>
                                    <div class="-mr-1.5 -mt-1 flex shrink-0 items-center">
                                        <x-ui.whatsapp-button icon :text="$this->teksWhatsApp[$jadwal->tanggal->toDateString()] ?? null" label="Bagikan jadwal lab {{ $jadwal->tanggal->isoFormat('D MMM') }} ke WhatsApp" />
                                        @if ($this->canManage)
                                            <x-ui.action-menu>
                                                <x-ui.menu-item wire:click="openEdit({{ $jadwal->id }})" opens="showForm" icon="heroicon-o-pencil-square">Edit</x-ui.menu-item>
                                                <x-ui.menu-item wire:click="confirmDelete({{ $jadwal->id }})" opens="confirmingDelete" icon="heroicon-o-trash" danger>Hapus</x-ui.menu-item>
                                            </x-ui.action-menu>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </x-ui.card>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if ($this->canManage)
        @include('livewire.jadwal-lab.partials.form-modal')
        <x-ui.confirm model="confirmingDelete" title="Hapus jadwal lab?" description="Sesi praktikum ini akan dihapus dari jadwal lab." />
    @endif
</div>
