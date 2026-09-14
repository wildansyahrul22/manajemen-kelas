<x-ui.modal model="showForm" :title="$form->jadwalLab ? 'Edit Jadwal Lab' : 'Tambah Jadwal Lab'" :description="$form->jadwalLab ? null : 'Pilih mata kuliah, lalu isi satu atau beberapa sesi praktikum sekaligus.'" max-width="max-w-2xl" loading="openCreate, openEdit">
    <form id="form-jadwal-lab" wire:submit="save" class="space-y-4">
        <x-ui.combobox label="Mata kuliah" name="form.mata_kuliah_id" wire:model="form.mata_kuliah_id" :options="$this->mataKuliahOptions->pluck('nama', 'id')" placeholder="Pilih mata kuliah" required />

        <div class="space-y-3">
            @foreach ($form->sesi as $key => $sesi)
                <div wire:key="sesi-{{ $key }}" class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                    @unless ($form->jadwalLab)
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sesi {{ $loop->iteration }}</p>
                            @if (count($form->sesi) > 1)
                                <button type="button" wire:click="removeSesi('{{ $key }}')" class="-mr-1.5 -mt-1 rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" aria-label="Hapus sesi {{ $loop->iteration }}">
                                    <x-heroicon-m-x-mark class="size-4" />
                                </button>
                            @endif
                        </div>
                    @endunless

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <x-ui.input label="Tanggal" name="form.sesi.{{ $key }}.tanggal" type="date" wire:model="form.sesi.{{ $key }}.tanggal" class="col-span-2 sm:col-span-1" required />
                            <x-ui.input label="Jam mulai" name="form.sesi.{{ $key }}.jam_mulai" type="time" wire:model="form.sesi.{{ $key }}.jam_mulai" required />
                            <x-ui.input label="Jam selesai" name="form.sesi.{{ $key }}.jam_selesai" type="time" wire:model="form.sesi.{{ $key }}.jam_selesai" required />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <x-ui.input label="Ruangan" name="form.sesi.{{ $key }}.ruangan" wire:model="form.sesi.{{ $key }}.ruangan" placeholder="Lab 2 (opsional)" />
                            <x-ui.input label="Keterangan" name="form.sesi.{{ $key }}.keterangan" wire:model="form.sesi.{{ $key }}.keterangan" placeholder="Materi / asisten / catatan (opsional)" class="sm:col-span-2" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @unless ($form->jadwalLab)
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <x-ui.button variant="secondary" size="sm" wire:click="addSesi" wire:loading.attr="disabled" wire:target="addSesi">
                    <x-heroicon-m-plus class="size-4" /> Tambah sesi lain
                </x-ui.button>
                <p class="text-xs text-slate-500">Sesi baru mengikuti jam &amp; ruangan sesi sebelumnya, tanggal +1 minggu.</p>
            </div>
        @endunless
    </form>

    <x-slot:footer>
        <x-ui.form-actions form="form-jadwal-lab" :label="$form->jadwalLab ? 'Simpan Perubahan' : (count($form->sesi) > 1 ? 'Tambah '.count($form->sesi).' Jadwal Lab' : 'Tambah Jadwal Lab')" />
    </x-slot:footer>
</x-ui.modal>
