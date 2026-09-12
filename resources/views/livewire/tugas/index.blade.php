@php
    $statusBadge = ['aktif' => ['emerald', 'Aktif'], 'segera' => ['amber', 'Segera'], 'lewat' => ['rose', 'Lewat']];
@endphp
<div>
    <x-ui.page-header title="Daftar Tugas" :description="'Tugas kelas ' . $this->kelas->nama . ' pada ' . $this->kelas->semesterAktif->nama . '.'">
        @if ($this->canManage)
            <x-slot:actions>
                <x-ui.button wire:click="openCreate" opens="showForm">
                    <x-heroicon-m-plus class="size-4" /> Tambah Tugas
                </x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card :padding="false">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center">
            <div class="flex-1 lg:max-w-xs">
                <x-ui.search wire:model.live.debounce.500ms="search" placeholder="Cari nama tugas..." />
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ui.combobox name="mataKuliahId" wire:model.live="mataKuliahId" :options="$this->mataKuliahOptions->pluck('nama', 'id')"
                    placeholder="Semua mata kuliah" clearable class="sm:w-56" />
                <x-ui.select name="status" wire:model.live="status" :options="['aktif' => 'Belum deadline', 'lewat' => 'Lewat deadline']" placeholder="Semua status"
                    clearable class="sm:w-44" />
            </div>
            <div class="lg:ml-auto">
                <x-ui.per-page wire:model.live="perPage" />
            </div>
        </div>

        <div wire:loading.class="opacity-50"
            wire:target="search, mataKuliahId, status, perPage, gotoPage, nextPage, previousPage"
            class="transition-opacity">
            @if ($this->daftarTugas->isEmpty())
                <x-ui.empty-state title="Tugas tidak ditemukan"
                    description="Belum ada tugas yang cocok dengan filter yang dipilih."
                    icon="heroicon-o-clipboard-document-list">
                    @if ($this->canManage)
                        <x-ui.button wire:click="openCreate" opens="showForm" variant="secondary"><x-heroicon-m-plus class="size-4" />
                            Tambah tugas pertama</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @else
                <x-ui.table>
                    <x-slot:head>
                        <x-ui.th>Tugas</x-ui.th>
                        <x-ui.th class="hidden md:table-cell">Mata Kuliah</x-ui.th>
                        <x-ui.th>Deadline</x-ui.th>
                        <x-ui.th class="hidden lg:table-cell">Dibuat</x-ui.th>
                        <x-ui.th class="hidden lg:table-cell">Diperbarui</x-ui.th>
                        @if ($this->canManage)
                            <x-ui.th class="text-right">Aksi</x-ui.th>
                        @endif
                    </x-slot:head>

                    @foreach ($this->daftarTugas as $tugas)
                        @php [$warna, $label] = $statusBadge[$tugas->status()]; @endphp
                        <tr wire:key="tugas-{{ $tugas->id }}" class="transition hover:bg-slate-50/70">
                            <x-ui.td>
                                <a href="{{ route('tugas.show', $tugas) }}" wire:navigate
                                    class="font-semibold text-slate-800 hover:text-primary-900 hover:underline">{{ $tugas->nama }}</a>
                                <p class="mt-0.5 text-xs text-slate-500 md:hidden">{{ $tugas->mataKuliah->nama }}</p>
                            </x-ui.td>
                            <x-ui.td class="hidden md:table-cell">
                                <p class="font-medium text-slate-700">{{ $tugas->mataKuliah->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $tugas->mataKuliah->dosen }}</p>
                            </x-ui.td>
                            <x-ui.td>
                                <div class="flex flex-col gap-1">
                                    <span
                                        class="whitespace-nowrap font-medium text-slate-700">{{ $tugas->deadline->isoFormat('D MMM YYYY, HH:mm') }}</span>
                                    <span class="flex items-center gap-2">
                                        <x-ui.badge :color="$warna">{{ $label }}</x-ui.badge>
                                        <span class="text-xs text-slate-400">{{ $tugas->sisaWaktu() }}</span>
                                    </span>
                                </div>
                            </x-ui.td>
                            <x-ui.td
                                class="hidden whitespace-nowrap text-slate-500 lg:table-cell">{{ $tugas->created_at->isoFormat('D MMM YYYY') }}</x-ui.td>
                            <x-ui.td
                                class="hidden whitespace-nowrap text-slate-500 lg:table-cell">{{ $tugas->updated_at->isoFormat('D MMM YYYY') }}</x-ui.td>
                            @if ($this->canManage)
                                <x-ui.td class="text-right">
                                    <x-ui.action-menu>
                                        <x-ui.menu-item :href="route('tugas.show', $tugas)" icon="heroicon-o-eye">Lihat
                                            detail</x-ui.menu-item>
                                        <x-ui.menu-item wire:click="openEdit({{ $tugas->id }})" opens="showForm"
                                            icon="heroicon-o-pencil-square">Edit</x-ui.menu-item>
                                        <x-ui.menu-item wire:click="confirmDelete({{ $tugas->id }})" opens="confirmingDelete"
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
            {{ $this->daftarTugas->links() }}
        </div>
    </x-ui.card>

    @if ($this->canManage)
        @include('livewire.tugas.partials.form-modal')
        <x-ui.confirm model="confirmingDelete" title="Hapus tugas?"
            description="Tugas akan dihapus dari daftar seluruh anggota kelas." />
    @endif
</div>
