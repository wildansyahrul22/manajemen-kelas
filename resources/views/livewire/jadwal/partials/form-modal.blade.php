<x-ui.modal model="showForm" :title="$form->jadwal ? 'Edit Jadwal' : 'Tambah Jadwal'" :description="$form->jadwal ? null : 'Satu hari bisa diisi beberapa mata kuliah sekaligus.'" max-width="max-w-2xl" loading="openCreate, openEdit">
    <form id="form-jadwal" wire:submit="save" class="space-y-4">
        <x-ui.select label="Hari" name="form.hari" wire:model="form.hari" :options="\App\Enums\Hari::options()" placeholder="Pilih hari" required />

        <div class="space-y-3">
            @foreach ($form->sesi as $key => $sesi)
                <div wire:key="sesi-{{ $key }}" class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                    @unless ($form->jadwal)
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mata kuliah {{ $loop->iteration }}</p>
                            @if (count($form->sesi) > 1)
                                <button type="button" wire:click="removeSesi('{{ $key }}')" class="-mr-1.5 -mt-1 rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" aria-label="Hapus mata kuliah {{ $loop->iteration }}">
                                    <x-heroicon-m-x-mark class="size-4" />
                                </button>
                            @endif
                        </div>
                    @endunless

                    <div class="space-y-4">
                        <x-ui.combobox label="Mata kuliah" name="form.sesi.{{ $key }}.mata_kuliah_id" wire:model="form.sesi.{{ $key }}.mata_kuliah_id" :options="$this->mataKuliahOptions->pluck('nama', 'id')" placeholder="Pilih mata kuliah" required />

                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <x-ui.input label="Jam mulai" name="form.sesi.{{ $key }}.jam_mulai" type="time" wire:model="form.sesi.{{ $key }}.jam_mulai" required />
                            <x-ui.input label="Jam selesai" name="form.sesi.{{ $key }}.jam_selesai" type="time" wire:model="form.sesi.{{ $key }}.jam_selesai" required />
                            <x-ui.input label="Ruangan" name="form.sesi.{{ $key }}.ruangan" wire:model="form.sesi.{{ $key }}.ruangan" placeholder="R.301 / Lab 2 (opsional)" class="col-span-2 sm:col-span-1" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @unless ($form->jadwal)
            <x-ui.button variant="secondary" size="sm" wire:click="addSesi" wire:loading.attr="disabled" wire:target="addSesi">
                <x-heroicon-m-plus class="size-4" /> Tambah mata kuliah lain
            </x-ui.button>
        @endunless
    </form>

    <x-slot:footer>
        <x-ui.form-actions form="form-jadwal" :label="$form->jadwal ? 'Simpan Perubahan' : (count($form->sesi) > 1 ? 'Tambah '.count($form->sesi).' Jadwal' : 'Tambah Jadwal')" />
    </x-slot:footer>
</x-ui.modal>
