<div>
    <x-ui.page-header title="Jadwal Kelas" :description="'Jadwal kelas '.$this->kelas->nama.' pada '.$this->kelas->semesterAktif->nama.'.'">
        @if ($this->canManage)
            <x-slot:actions>
                <x-ui.button wire:click="openCreate" opens="showForm"><x-heroicon-m-plus class="size-4" /> Tambah Jadwal</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    @if ($this->mataKuliahOptions->isEmpty())
        <x-ui.card :padding="false">
            <x-ui.empty-state title="Belum ada mata kuliah" description="Tambahkan mata kuliah pada semester aktif terlebih dahulu sebelum menyusun jadwal." icon="heroicon-o-book-open">
                @if ($this->canManage)
                    <x-ui.button :href="route('mata-kuliah.index')" variant="secondary">Kelola mata kuliah</x-ui.button>
                @endif
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->jadwalPerHari as $hariValue => $daftar)
                @php $hari = \App\Enums\Hari::from($hariValue); $isToday = $hari === $hariIni; @endphp
                <x-ui.card wire:key="hari-{{ $hariValue }}" :padding="false" :class="$isToday ? 'ring-2 ring-primary-900/60' : ''">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-slate-900">{{ $hari->label() }}</h3>
                            @if ($isToday)<x-ui.badge color="primary">Hari ini</x-ui.badge>@endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400">{{ $daftar->count() }} sesi</span>
                            @if ($this->canManage)
                                <button type="button" wire:click="openCreate({{ $hariValue }})" x-on:click="$wire.showForm = true" class="-mr-1.5 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-primary-900" aria-label="Tambah jadwal {{ $hari->label() }}" title="Tambah jadwal {{ $hari->label() }}">
                                    <x-heroicon-m-plus class="size-4" />
                                </button>
                            @endif
                        </div>
                    </div>

                    @forelse ($daftar as $jadwal)
                        <div wire:key="jadwal-{{ $jadwal->id }}" class="group flex items-start gap-3 border-b border-slate-100 px-5 py-4 last:border-b-0">
                            <div class="w-12 shrink-0 text-center">
                                <p class="text-sm font-bold text-slate-900">{{ $jadwal->jam_mulai->format('H:i') }}</p>
                                <p class="text-xs text-slate-400">{{ $jadwal->jam_selesai->format('H:i') }}</p>
                            </div>
                            <div @class(['min-w-0 flex-1 border-l-2 pl-3', 'border-primary-900' => $isToday, 'border-slate-200' => ! $isToday])>
                                <a href="{{ route('mata-kuliah.show', $jadwal->mata_kuliah_id) }}" wire:navigate class="block truncate text-sm font-semibold text-slate-800 hover:text-primary-900 hover:underline">{{ $jadwal->mataKuliah->nama }}</a>
                                <p class="truncate text-xs text-slate-500">{{ $jadwal->mataKuliah->dosen }}</p>
                                @if ($jadwal->ruangan)
                                    <p class="mt-1 inline-flex items-center gap-1 text-xs text-slate-500"><x-heroicon-m-map-pin class="size-3.5" /> {{ $jadwal->ruangan }}</p>
                                @endif
                            </div>
                            @if ($this->canManage)
                                <x-ui.action-menu class="-mr-1.5 -mt-1">
                                    <x-ui.menu-item wire:click="openEdit({{ $jadwal->id }})" opens="showForm" icon="heroicon-o-pencil-square">Edit</x-ui.menu-item>
                                    <x-ui.menu-item wire:click="confirmDelete({{ $jadwal->id }})" opens="confirmingDelete" icon="heroicon-o-trash" danger>Hapus</x-ui.menu-item>
                                </x-ui.action-menu>
                            @endif
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-sm text-slate-400">
                            Tidak ada jadwal.
                            @if ($this->canManage)
                                <button type="button" wire:click="openCreate({{ $hariValue }})" x-on:click="$wire.showForm = true" class="ml-1 font-medium text-primary-600 hover:underline">Tambah</button>
                            @endif
                        </div>
                    @endforelse
                </x-ui.card>
            @endforeach
        </div>
    @endif

    @if ($this->canManage)
        @include('livewire.jadwal.partials.form-modal')
        <x-ui.confirm model="confirmingDelete" title="Hapus jadwal?" description="Jadwal ini akan dihapus dari jadwal kelas." />
    @endif
</div>
