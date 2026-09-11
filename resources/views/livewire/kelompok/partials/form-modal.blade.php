<x-ui.modal model="showForm" :title="$form->kelompok ? 'Edit Kelompok' : 'Buat Kelompok'" max-width="max-w-2xl">
    <form id="form-kelompok" wire:submit="save" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.input label="Nama kelompok" name="form.nama" wire:model="form.nama" placeholder="Contoh: Kelompok 1" required />
            <x-ui.combobox label="Mata kuliah" name="form.mata_kuliah_id" wire:model="form.mata_kuliah_id" :options="$this->mataKuliahOptions->pluck('nama', 'id')" placeholder="Pilih mata kuliah" required />
        </div>

        <x-ui.textarea label="Deskripsi" name="form.deskripsi" wire:model="form.deskripsi" rows="2" placeholder="Topik / project kelompok (opsional)" />

        <div x-data="{ cari: '' }">
            <div class="mb-2 flex items-center justify-between gap-3">
                <p class="text-sm font-medium text-slate-700">Anggota <span class="text-rose-500">*</span></p>
                <span class="text-xs text-slate-500">{{ count($form->anggota) }} dipilih</span>
            </div>
            <input type="search" x-model="cari" placeholder="Cari nama / NPM..." class="mb-2 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
            <div class="scrollbar-thin max-h-56 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200">
                {{-- Only query the student list while the modal is open. --}}
                @foreach ($showForm ? $this->mahasiswaOptions : [] as $mahasiswa)
                    <label
                        wire:key="anggota-{{ $mahasiswa->id }}"
                        x-show="cari === '' || @js(Str::lower($mahasiswa->name.' '.$mahasiswa->npm)).includes(cari.toLowerCase())"
                        class="flex cursor-pointer items-center gap-3 px-3.5 py-2.5 text-sm hover:bg-slate-50"
                    >
                        <input type="checkbox" value="{{ $mahasiswa->id }}" wire:model.live="form.anggota" class="size-4 rounded border-slate-300">
                        <span class="flex-1 font-medium text-slate-700">{{ $mahasiswa->name }}</span>
                        <span class="text-xs text-slate-400">{{ $mahasiswa->npm }}</span>
                    </label>
                @endforeach
            </div>
            @error('form.anggota')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
            @error('form.anggota.*')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <x-ui.select label="Ketua kelompok" name="form.ketua_id" wire:model="form.ketua_id" :options="$showForm ? $this->mahasiswaOptions->whereIn('id', array_map('intval', $form->anggota))->pluck('name', 'id') : []" placeholder="Tanpa ketua" clearable hint="Pilih anggota terlebih dahulu, lalu tentukan ketuanya." />
    </form>

    <x-slot:footer>
        <x-ui.form-actions form="form-kelompok" :label="$form->kelompok ? 'Simpan Perubahan' : 'Buat Kelompok'" />
    </x-slot:footer>
</x-ui.modal>
