@php
    $statusBadge = ['aktif' => ['emerald', 'Belum deadline'], 'segera' => ['amber', 'Segera deadline'], 'lewat' => ['rose', 'Lewat deadline']];
    [$warna, $label] = $statusBadge[$tugas->status()];
@endphp
<div>
    <x-ui.page-header :title="$tugas->nama" :back="route('tugas.index')">
        @can('update', $tugas)
            <x-slot:actions>
                <x-ui.button variant="secondary" wire:click="openEdit({{ $tugas->id }})" opens="showForm"><x-heroicon-m-pencil-square class="size-4" /> Edit</x-ui.button>
                <x-ui.button variant="danger" wire:click="confirmDelete({{ $tugas->id }})" opens="confirmingDelete"><x-heroicon-m-trash class="size-4" /> Hapus</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
        <x-ui.card class="lg:col-span-2">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :color="$warna">{{ $label }}</x-ui.badge>
                <span class="text-sm text-slate-500">{{ $tugas->sisaWaktu() }}</span>
            </div>

            <h3 class="mt-4 text-sm font-semibold uppercase tracking-wider text-slate-400">Deskripsi</h3>
            @if ($tugas->deskripsi)
                <div class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $tugas->deskripsi }}</div>
            @else
                <p class="mt-2 text-sm italic text-slate-400">Tidak ada deskripsi.</p>
            @endif
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card title="Detail">
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-slate-500">Deadline</dt>
                        <dd class="mt-0.5 font-semibold text-slate-800">{{ $tugas->deadline->isoFormat('dddd, D MMMM YYYY') }}<br><span class="font-medium text-slate-600">Pukul {{ $tugas->deadline->format('H:i') }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Mata kuliah</dt>
                        <dd class="mt-0.5">
                            <a href="{{ route('mata-kuliah.show', $tugas->mataKuliah) }}" wire:navigate class="font-semibold text-slate-800 hover:text-primary-900 hover:underline">{{ $tugas->mataKuliah->nama }}</a>
                            <p class="text-slate-600">{{ $tugas->mataKuliah->dosen }}</p>
                            <p class="text-xs text-slate-400">{{ $tugas->mataKuliah->kode ? $tugas->mataKuliah->kode.' · ' : '' }}{{ $tugas->mataKuliah->sks }} SKS</p>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Dibuat oleh</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $tugas->creator?->name ?? '—' }}</dd>
                    </div>
                    <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4">
                        <div>
                            <dt class="text-slate-500">Dibuat</dt>
                            <dd class="mt-0.5 font-medium text-slate-700">{{ $tugas->created_at->isoFormat('D MMM YYYY, HH:mm') }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Diperbarui</dt>
                            <dd class="mt-0.5 font-medium text-slate-700">{{ $tugas->updated_at->isoFormat('D MMM YYYY, HH:mm') }}</dd>
                        </div>
                    </div>
                </dl>
            </x-ui.card>
        </div>
    </div>

    @can('update', $tugas)
        @include('livewire.tugas.partials.form-modal')
        <x-ui.confirm model="confirmingDelete" title="Hapus tugas?" description="Tugas akan dihapus dari daftar seluruh anggota kelas." />
    @endcan
</div>
