<x-ui.modal model="showForm" :title="$form->mataKuliah ? 'Edit Mata Kuliah' : 'Tambah Mata Kuliah'">
    <form id="form-mata-kuliah" wire:submit="save" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.combobox label="Semester" name="form.semester_id" wire:model="form.semester_id" :options="$this->semesterOptions->pluck('nama', 'id')" placeholder="Pilih semester" required />
            <x-ui.input label="Kode" name="form.kode" wire:model="form.kode" placeholder="Contoh: IF301" />
        </div>
        <x-ui.input label="Nama mata kuliah" name="form.nama" wire:model="form.nama" placeholder="Contoh: Pemrograman Web" required />
        <x-ui.input label="Dosen pengampu" name="form.dosen" wire:model="form.dosen" placeholder="Nama dosen" required />
        <x-ui.input label="SKS" name="form.sks" type="number" min="1" max="6" wire:model="form.sks" class="sm:w-32" required />
    </form>

    <x-slot:footer>
        <x-ui.form-actions form="form-mata-kuliah" :label="$form->mataKuliah ? 'Simpan Perubahan' : 'Tambah'" />
    </x-slot:footer>
</x-ui.modal>
