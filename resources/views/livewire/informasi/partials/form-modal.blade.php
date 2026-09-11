<x-ui.modal model="showForm" :title="$form->informasi ? 'Edit Informasi' : 'Bagikan Informasi'" max-width="max-w-2xl">
    <form id="form-informasi" wire:submit="save" class="space-y-4">
        <x-ui.input label="Judul" name="form.judul" wire:model="form.judul" placeholder="Judul informasi" required />

        <x-ui.combobox label="Kategori" name="form.kategori_informasi_id" wire:model="form.kategori_informasi_id" :options="$this->kategoriOptions->pluck('nama', 'id')" placeholder="Pilih kategori" required
            :hint="$this->kategoriOptions->isEmpty() ? 'Belum ada kategori. Buat dulu di menu Kategori Informasi.' : null" />

        <x-ui.textarea label="Isi informasi" name="form.isi" wire:model="form.isi" rows="7" placeholder="Tulis informasi selengkap mungkin..." required />

        @if ($this->canManage)
            <x-ui.checkbox label="Sematkan di atas" name="form.is_pinned" wire:model="form.is_pinned" description="Informasi yang disematkan selalu tampil paling atas." />
        @endif
    </form>

    <x-slot:footer>
        <x-ui.form-actions form="form-informasi" :label="$form->informasi ? 'Simpan Perubahan' : 'Bagikan'" />
    </x-slot:footer>
</x-ui.modal>
