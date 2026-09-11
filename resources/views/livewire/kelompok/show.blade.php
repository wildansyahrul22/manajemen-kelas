<div>
    <x-ui.page-header :title="$kelompok->nama" :description="$kelompok->mataKuliah->nama.' · '.$kelompok->mataKuliah->dosen" :back="route('kelompok.index')">
        @can('update', $kelompok)
            <x-slot:actions>
                <x-ui.button variant="secondary" wire:click="openEdit({{ $kelompok->id }})"><x-heroicon-m-pencil-square class="size-4" /> Edit</x-ui.button>
                <x-ui.button variant="danger" wire:click="confirmDelete({{ $kelompok->id }})"><x-heroicon-m-trash class="size-4" /> Hapus</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card title="Anggota" :description="$this->anggota->count().' orang'" :padding="false" class="lg:col-span-2">
            <x-ui.table>
                <x-slot:head>
                    <x-ui.th>Nama</x-ui.th>
                    <x-ui.th>NPM</x-ui.th>
                    <x-ui.th class="hidden sm:table-cell">No. HP</x-ui.th>
                    <x-ui.th>Peran</x-ui.th>
                </x-slot:head>
                @foreach ($this->anggota as $anggota)
                    <tr wire:key="anggota-{{ $anggota->id }}">
                        <x-ui.td>
                            <div class="flex items-center gap-3">
                                <x-ui.avatar :name="$anggota->name" size="sm" />
                                <span class="font-medium text-slate-800">{{ $anggota->name }}</span>
                            </div>
                        </x-ui.td>
                        <x-ui.td class="text-slate-500">{{ $anggota->npm }}</x-ui.td>
                        <x-ui.td class="hidden text-slate-500 sm:table-cell">
                            @if ($anggota->no_hp)
                                <a href="{{ $anggota->whatsappUrl() }}" target="_blank" rel="noopener" class="hover:text-primary-900 hover:underline">{{ $anggota->noHpFormatted() }}</a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </x-ui.td>
                        <x-ui.td>
                            @if ($anggota->pivot->is_ketua)<x-ui.badge color="primary">Ketua</x-ui.badge>@else<span class="text-slate-500">Anggota</span>@endif
                        </x-ui.td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>

        <x-ui.card title="Tentang Kelompok">
            <dl class="space-y-4 text-sm">
                <div><dt class="text-slate-500">Mata kuliah</dt><dd class="mt-0.5"><a href="{{ route('mata-kuliah.show', $kelompok->mata_kuliah_id) }}" wire:navigate class="font-semibold text-slate-800 hover:text-primary-900 hover:underline">{{ $kelompok->mataKuliah->nama }}</a></dd></div>
                <div><dt class="text-slate-500">Deskripsi</dt><dd class="mt-0.5 whitespace-pre-line text-slate-700">{{ $kelompok->deskripsi ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Dibuat oleh</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $kelompok->creator?->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Dibuat</dt><dd class="mt-0.5 text-slate-700">{{ $kelompok->created_at->isoFormat('D MMM YYYY, HH:mm') }}</dd></div>
            </dl>
        </x-ui.card>
    </div>

    @can('update', $kelompok)
        @include('livewire.kelompok.partials.form-modal')
        <x-ui.confirm model="confirmingDelete" title="Hapus kelompok?" description="Kelompok beserta daftar anggotanya akan dihapus." />
    @endcan
</div>
