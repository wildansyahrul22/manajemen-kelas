<x-ui.modal model="showForm" :title="$form->tugas ? 'Edit Tugas' : 'Tambah Tugas'" description="Tugas akan tampil untuk seluruh anggota kelas." loading="openCreate, openEdit">
    <form id="form-tugas" wire:submit="save" class="space-y-4">
        <x-ui.input label="Nama tugas" name="form.nama" wire:model="form.nama" placeholder="Contoh: Laporan Praktikum 2" required />

        <x-ui.combobox label="Mata kuliah" name="form.mata_kuliah_id" wire:model="form.mata_kuliah_id" :options="$this->mataKuliahOptions->pluck('nama', 'id')" placeholder="Pilih mata kuliah" required />

        <x-ui.input label="Deadline" name="form.deadline" type="datetime-local" wire:model="form.deadline" required />

        <x-ui.textarea label="Deskripsi" name="form.deskripsi" wire:model="form.deskripsi" rows="4" placeholder="Detail tugas, format pengumpulan, dll. (opsional)" />
    </form>

    <x-slot:footer>
        <x-ui.form-actions form="form-tugas" :label="$form->tugas ? 'Simpan Perubahan' : 'Tambah Tugas'" />
    </x-slot:footer>
</x-ui.modal>
