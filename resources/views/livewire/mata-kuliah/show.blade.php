<div>
    <x-ui.page-header :title="$mataKuliah->nama" :description="($mataKuliah->kode ? $mataKuliah->kode.' · ' : '').$mataKuliah->sks.' SKS · '.$mataKuliah->semester->nama" :back="route('mata-kuliah.index')">
        @can('update', $mataKuliah)
            <x-slot:actions>
                <x-ui.button variant="secondary" wire:click="openEdit({{ $mataKuliah->id }})"><x-heroicon-m-pencil-square class="size-4" /> Edit</x-ui.button>
                <x-ui.button variant="danger" wire:click="confirmDelete({{ $mataKuliah->id }})"><x-heroicon-m-trash class="size-4" /> Hapus</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Jadwal" :padding="false">
                @forelse ($this->jadwal as $jadwal)
                    <div wire:key="jadwal-{{ $jadwal->id }}" class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 last:border-b-0 sm:px-6">
                        <span class="w-16 shrink-0 text-sm font-semibold text-slate-800">{{ $jadwal->hari->label() }}</span>
                        <span class="text-sm text-slate-600">{{ $jadwal->jam() }}</span>
                        @if ($jadwal->ruangan)
                            <span class="ml-auto inline-flex items-center gap-1 text-xs text-slate-500"><x-heroicon-m-map-pin class="size-3.5" /> {{ $jadwal->ruangan }}</span>
                        @endif
                    </div>
                @empty
                    <x-ui.empty-state title="Belum ada jadwal" icon="heroicon-o-calendar" class="py-8" />
                @endforelse
            </x-ui.card>

            <x-ui.card title="Tugas" description="10 tugas terakhir" :padding="false">
                @forelse ($this->tugas as $tugas)
                    <a href="{{ route('tugas.show', $tugas) }}" wire:navigate wire:key="tugas-{{ $tugas->id }}" class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 transition last:border-b-0 hover:bg-slate-50 sm:px-6">
                        <p class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-800">{{ $tugas->nama }}</p>
                        <x-ui.badge :color="$tugas->isLewat() ? 'rose' : 'emerald'">{{ $tugas->isLewat() ? 'Lewat' : 'Aktif' }}</x-ui.badge>
                        <span class="whitespace-nowrap text-sm text-slate-500">{{ $tugas->deadline->isoFormat('D MMM YYYY') }}</span>
                    </a>
                @empty
                    <x-ui.empty-state title="Belum ada tugas" icon="heroicon-o-clipboard-document-list" class="py-8" />
                @endforelse
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Informasi">
                <dl class="space-y-4 text-sm">
                    <div><dt class="text-slate-500">Dosen pengampu</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ $mataKuliah->dosen }}</dd></div>
                    <div><dt class="text-slate-500">Kode</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $mataKuliah->kode ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Semester</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $mataKuliah->semester->nama }}</dd></div>
                    <div><dt class="text-slate-500">SKS</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $mataKuliah->sks }}</dd></div>
                </dl>
            </x-ui.card>

            <x-ui.card title="Kelompok" :padding="false">
                @forelse ($this->kelompok as $kelompok)
                    <a href="{{ route('kelompok.show', $kelompok) }}" wire:navigate wire:key="kelompok-{{ $kelompok->id }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5 text-sm transition last:border-b-0 hover:bg-slate-50 sm:px-6">
                        <span class="font-medium text-slate-800">{{ $kelompok->nama }}</span>
                        <span class="text-xs text-slate-500">{{ $kelompok->anggota_count }} anggota</span>
                    </a>
                @empty
                    <x-ui.empty-state title="Belum ada kelompok" icon="heroicon-o-user-group" class="py-8" />
                @endforelse
            </x-ui.card>
        </div>
    </div>

    @can('update', $mataKuliah)
        @include('livewire.mata-kuliah.partials.form-modal')
        <x-ui.confirm model="confirmingDelete" title="Hapus mata kuliah?" description="Semua jadwal, tugas, dan kelompok pada mata kuliah ini ikut terhapus." />
    @endcan
</div>
