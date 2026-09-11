<div>
    <x-ui.page-header title="Semester Aktif" :description="auth()->user()->isSuperAdmin()
        ? 'Atur semester berjalan untuk setiap kelas.'
        : 'Atur semester berjalan kelas Anda. Semester aktif menentukan mata kuliah, jadwal, tugas, dan kelompok yang ditampilkan.'" />

    <x-ui.card :padding="false">
        @if (auth()->user()->isSuperAdmin())
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:p-5">
                <div class="flex-1 sm:max-w-xs">
                    <x-ui.search wire:model.live.debounce.500ms="search" placeholder="Cari kelas..." />
                </div>
                <div class="sm:ml-auto">
                    <x-ui.per-page wire:model.live="perPage" />
                </div>
            </div>
        @endif

        @if ($this->daftarKelas->isEmpty())
            <x-ui.empty-state title="Belum ada kelas" icon="heroicon-o-building-library" />
        @else
            <x-ui.table>
                <x-slot:head>
                    <x-ui.th>Kelas</x-ui.th>
                    <x-ui.th class="hidden text-center sm:table-cell">Mahasiswa</x-ui.th>
                    <x-ui.th>Semester Aktif</x-ui.th>
                    <x-ui.th>Ubah ke</x-ui.th>
                    <x-ui.th class="hidden lg:table-cell">Terakhir diubah</x-ui.th>
                </x-slot:head>
                @foreach ($this->daftarKelas as $kelas)
                    @php
                        $pilihan = $this->pilihan[$kelas->id] ?? '';
                        $berubah = $pilihan !== '' && (int) $pilihan !== $kelas->semester_aktif_id;
                    @endphp
                    <tr wire:key="kelas-{{ $kelas->id }}" class="transition hover:bg-slate-50/70">
                        <x-ui.td>
                            <p class="font-semibold text-slate-800">{{ $kelas->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $kelas->prodi ?: '—' }} · Angkatan
                                {{ $kelas->angkatan }}</p>
                        </x-ui.td>
                        <x-ui.td class="hidden text-center sm:table-cell">{{ $kelas->mahasiswa_count }}</x-ui.td>
                        <x-ui.td><x-ui.badge color="primary">{{ $kelas->semesterAktif->nama }}</x-ui.badge></x-ui.td>
                        <x-ui.td>
                            <div class="flex items-center gap-2">
                                <x-ui.combobox name="pilihan.{{ $kelas->id }}"
                                    wire:model.live="pilihan.{{ $kelas->id }}" :options="$this->semesterOptions->pluck('nama', 'id')"
                                    placeholder="Pilih semester" size="sm" class="w-44" />
                                <x-ui.button size="sm" wire:click="simpan({{ $kelas->id }})" :disabled="!$berubah"
                                    wire:loading.attr="disabled"
                                    wire:target="simpan({{ $kelas->id }})">Simpan</x-ui.button>
                            </div>
                        </x-ui.td>
                        <x-ui.td
                            class="hidden whitespace-nowrap text-slate-500 lg:table-cell">{{ $kelas->updated_at->isoFormat('D MMM YYYY, HH:mm') }}</x-ui.td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif

        @if (auth()->user()->isSuperAdmin())
            <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                {{ $this->daftarKelas->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
