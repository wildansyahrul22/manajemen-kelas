<x-ui.modal model="showForm" :title="$form->jadwal ? 'Edit Jadwal' : 'Tambah Jadwal'">
    <form id="form-jadwal" wire:submit="save" class="space-y-4">
        <x-ui.combobox label="Mata kuliah" name="form.mata_kuliah_id" wire:model="form.mata_kuliah_id" :options="$this->mataKuliahOptions->pluck('nama', 'id')" placeholder="Pilih mata kuliah" required />

        <x-ui.select label="Hari" name="form.hari" wire:model="form.hari" :options="\App\Enums\Hari::options()" placeholder="Pilih hari" required />

        <div class="grid grid-cols-2 gap-4">
            <x-ui.input label="Jam mulai" name="form.jam_mulai" type="time" wire:model="form.jam_mulai" required />
            <x-ui.input label="Jam selesai" name="form.jam_selesai" type="time" wire:model="form.jam_selesai" required />
        </div>

        <x-ui.input label="Ruangan" name="form.ruangan" wire:model="form.ruangan" placeholder="Contoh: R.301 / Lab 2 (opsional)" />
    </form>

    <x-slot:footer>
        <x-ui.form-actions form="form-jadwal" :label="$form->jadwal ? 'Simpan Perubahan' : 'Tambah Jadwal'" />
    </x-slot:footer>
</x-ui.modal>
