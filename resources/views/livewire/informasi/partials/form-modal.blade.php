<x-ui.modal model="showForm" :title="$form->informasi ? 'Edit Informasi' : 'Bagikan Informasi'" max-width="max-w-2xl" loading="openCreate, openEdit">
    <form id="form-informasi" wire:submit="save" class="space-y-4">
        <x-ui.input label="Judul" name="form.judul" wire:model="form.judul" placeholder="Judul informasi" required />

        <x-ui.combobox label="Kategori" name="form.kategori_informasi_id" wire:model="form.kategori_informasi_id" :options="$this->kategoriOptions->pluck('nama', 'id')" placeholder="Pilih kategori" required
            :hint="$this->kategoriOptions->isEmpty() ? 'Belum ada kategori. Buat dulu di menu Kategori Informasi.' : null" />

        <x-ui.textarea label="Isi informasi" name="form.isi" wire:model="form.isi" rows="7" placeholder="Tulis informasi selengkap mungkin..." required />

        <x-ui.input label="Tautan (link)" name="form.link" type="url" inputmode="url" wire:model="form.link" placeholder="https://drive.google.com/..."
            hint="Opsional. Tempel tautan Google Drive, OneDrive, atau layanan serupa; atur aksesnya agar bisa dibuka (mis. 'Siapa saja yang memiliki link') supaya anggota kelas bisa membukanya." />

        @if (! $this->canUpload)
            <p class="flex gap-2.5 rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-3 text-xs leading-relaxed text-slate-600">
                <x-heroicon-m-lock-closed class="mt-0.5 size-4 shrink-0 text-slate-400" />
                <span>
                    @if ($this->canManage)
                        Paket kelas ini belum termasuk unggah file, jadi lampiran dibagikan lewat tautan di kolom <strong class="font-semibold">Tautan</strong> di atas.
                    @else
                        <strong class="font-semibold text-slate-700">Hanya admin kelas yang bisa mengunggah file/gambar</strong> langsung ke aplikasi, supaya tidak ada yang iseng atau mengirim spam.
                        Kalau kamu perlu membagikan gambar atau file, unggah dulu ke Google Drive/OneDrive lalu tempel tautannya di kolom <strong class="font-semibold">Tautan</strong> di atas.
                    @endif
                </span>
            </p>
        @else
            <x-ui.lampiran-field :form="$form" :model="\App\Models\Informasi::class" sebutan="informasi" />
        @endif

        @if ($this->canManage)
            <x-ui.checkbox label="Sematkan di atas" name="form.is_pinned" wire:model="form.is_pinned" description="Informasi yang disematkan selalu tampil paling atas." />
        @endif
    </form>

    <x-slot:footer>
        <x-ui.form-actions form="form-informasi" :label="$form->informasi ? 'Simpan Perubahan' : 'Bagikan'" />
    </x-slot:footer>
</x-ui.modal>
