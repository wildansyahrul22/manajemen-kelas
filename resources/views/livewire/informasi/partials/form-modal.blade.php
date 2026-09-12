<x-ui.modal model="showForm" :title="$form->informasi ? 'Edit Informasi' : 'Bagikan Informasi'" max-width="max-w-2xl" loading="openCreate, openEdit">
    <form id="form-informasi" wire:submit="save" class="space-y-4">
        <x-ui.input label="Judul" name="form.judul" wire:model="form.judul" placeholder="Judul informasi" required />

        <x-ui.combobox label="Kategori" name="form.kategori_informasi_id" wire:model="form.kategori_informasi_id" :options="$this->kategoriOptions->pluck('nama', 'id')" placeholder="Pilih kategori" required
            :hint="$this->kategoriOptions->isEmpty() ? 'Belum ada kategori. Buat dulu di menu Kategori Informasi.' : null" />

        <x-ui.textarea label="Isi informasi" name="form.isi" wire:model="form.isi" rows="7" placeholder="Tulis informasi selengkap mungkin..." required />

        <x-ui.input label="Tautan (link)" name="form.link" type="url" inputmode="url" wire:model="form.link" placeholder="https://drive.google.com/..."
            hint="Opsional. Punya gambar atau file pendukung? Unggah ke Google Drive, OneDrive, atau layanan serupa, atur aksesnya agar bisa dibuka (mis. 'Siapa saja yang memiliki link'), lalu tempel tautannya di sini supaya anggota kelas lain bisa melihat/mengaksesnya." />

        @if ($this->canUpload)
            <div>
                <label for="form-lampiran" class="mb-1.5 block text-sm font-medium text-slate-700">Lampiran (gambar/file)</label>

                @if ($form->informasi?->hasLampiran() && ! $form->lampiran)
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm">
                        <span class="inline-flex min-w-0 items-center gap-2 text-slate-700">
                            <x-heroicon-o-paper-clip class="size-4 shrink-0 text-slate-400" />
                            <span class="truncate">{{ $form->informasi->lampiran_nama }}</span>
                        </span>
                        <x-ui.checkbox label="Hapus lampiran ini" name="form.hapus_lampiran" wire:model="form.hapus_lampiran" />
                    </div>
                @endif

                <input
                    id="form-lampiran"
                    type="file"
                    wire:model="form.lampiran"
                    accept="{{ collect(\App\Models\Informasi::LAMPIRAN_EKSTENSI)->map(fn ($ext) => '.'.$ext)->join(',') }}"
                    @class([
                        'block w-full rounded-xl border bg-white text-sm text-slate-600 shadow-sm transition file:mr-3 file:rounded-l-xl file:border-0 file:bg-slate-100 file:px-3.5 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20',
                        'border-rose-300' => $errors->has('form.lampiran'),
                        'border-slate-200' => ! $errors->has('form.lampiran'),
                    ])
                >

                <div wire:loading wire:target="form.lampiran" class="mt-1.5 inline-flex items-center gap-1.5 text-xs text-slate-500">
                    <x-heroicon-m-arrow-path class="size-3.5 animate-spin" /> Mengunggah file...
                </div>

                @error('form.lampiran')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @else
                    <p class="mt-1.5 text-xs text-slate-500">
                        @if ($form->lampiran)
                            Dipilih: <span class="font-medium text-slate-700">{{ $form->lampiran->getClientOriginalName() }}</span>{{ $form->informasi?->hasLampiran() ? ' (menggantikan lampiran lama).' : '.' }}
                        @else
                            Khusus super admin. Maks. {{ \App\Models\Informasi::LAMPIRAN_MAKS_KB / 1024 }} MB; format {{ strtoupper(implode(', ', \App\Models\Informasi::LAMPIRAN_EKSTENSI)) }}. File hanya bisa dibuka oleh anggota kelas.
                        @endif
                    </p>
                @enderror
            </div>
        @endif

        @if ($this->canManage)
            <x-ui.checkbox label="Sematkan di atas" name="form.is_pinned" wire:model="form.is_pinned" description="Informasi yang disematkan selalu tampil paling atas." />
        @endif
    </form>

    <x-slot:footer>
        <x-ui.form-actions form="form-informasi" :label="$form->informasi ? 'Simpan Perubahan' : 'Bagikan'" target="save, form.lampiran" />
    </x-slot:footer>
</x-ui.modal>
