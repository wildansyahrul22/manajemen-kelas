<x-ui.modal model="showForm" :title="$form->tugas ? 'Edit Tugas' : 'Tambah Tugas'" description="Tugas akan tampil untuk seluruh anggota kelas." loading="openCreate, openEdit">
    <form id="form-tugas" wire:submit="save" class="space-y-4">
        <x-ui.input label="Nama tugas" name="form.nama" wire:model="form.nama" placeholder="Contoh: Laporan Praktikum 2" required />

        <x-ui.combobox label="Mata kuliah" name="form.mata_kuliah_id" wire:model.live="form.mata_kuliah_id" :options="$this->mataKuliahOptions->pluck('nama', 'id')" placeholder="Pilih mata kuliah" required />

        <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4">
            <x-ui.checkbox label="Tugas kelompok" name="form.tugas_kelompok" wire:model.live="form.tugas_kelompok"
                description="Tugas dikerjakan per kelompok. Pilih kategori kelompoknya; di halaman detail setiap mahasiswa akan melihat kelompoknya sendiri." />
            @if ($form->tugas_kelompok)
                @if ($form->mata_kuliah_id === '')
                    <p class="text-sm text-slate-500">Pilih mata kuliah terlebih dahulu untuk memilih kategori kelompoknya.</p>
                @else
                    <x-ui.combobox label="Kategori kelompok" name="form.kategori_kelompok_id" wire:model="form.kategori_kelompok_id" :options="$this->kategoriKelompokOptions" placeholder="Pilih kategori kelompok" required class="sm:w-72"
                        :hint="$this->kategoriKelompokOptions->isEmpty() ? 'Mata kuliah ini belum punya kategori kelompok. Buat dulu di menu Kategori Kelompok.' : 'Kelompok pada kategori inilah yang mengerjakan tugas.'" />
                @endif
            @endif
        </div>

        <x-ui.input label="Deadline" name="form.deadline" type="datetime-local" wire:model="form.deadline" required />

        <x-ui.input label="Link pengumpulan" name="form.link_pengumpulan" type="url" inputmode="url" wire:model="form.link_pengumpulan" placeholder="https://forms.gle/... atau https://drive.google.com/..."
            hint="Opsional. Tautan tempat mahasiswa mengumpulkan tugas (Google Form, folder Drive, LMS, dll.). Tampil sebagai tombol Kumpulkan Tugas." />

        <x-ui.textarea label="Deskripsi" name="form.deskripsi" wire:model="form.deskripsi" rows="4" placeholder="Detail tugas, format pengumpulan, dll. (opsional)" />
    </form>

    <x-slot:footer>
        <x-ui.form-actions form="form-tugas" :label="$form->tugas ? 'Simpan Perubahan' : 'Tambah Tugas'" />
    </x-slot:footer>
</x-ui.modal>
