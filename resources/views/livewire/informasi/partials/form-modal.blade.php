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
            @php
                $tersimpan = $form->lampiranTersimpan();
                $adaErrorFile = $errors->has('form.lampiran') || $errors->has('form.lampiran.*');
                $perFile = \App\Support\BatasUnggah::perFileBytes();
                $perPermintaan = \App\Support\BatasUnggah::perPermintaanBytes();
            @endphp
            <div
                x-data="{
                    pesan: null,
                    progres: null,
                    perFile: {{ $perFile }},
                    perPermintaan: {{ $perPermintaan ?? 'null' }},
                    pilih(daftar) {
                        const files = Array.from(daftar);
                        this.pesan = null;

                        if (files.length === 0) return;

                        const besar = files.filter(f => f.size > this.perFile);
                        if (besar.length) {
                            this.pesan = besar.map(f => f.name).join(', ') + ' melebihi batas {{ \App\Support\BatasUnggah::mb($perFile) }} MB per file.';
                            this.$refs.input.value = '';
                            return;
                        }

                        const total = files.reduce((n, f) => n + f.size, 0);
                        if (this.perPermintaan !== null && total > this.perPermintaan) {
                            this.pesan = 'Total ukuran file yang dipilih sekaligus melebihi {{ $perPermintaan ? \App\Support\BatasUnggah::mb($perPermintaan) : '' }} MB. Pilih lebih sedikit file, lalu tambahkan sisanya.';
                            this.$refs.input.value = '';
                            return;
                        }

                        this.progres = 0;
                        $wire.$uploadMultiple('form.lampiran', files,
                            () => { this.progres = null; this.$refs.input.value = ''; },
                            () => { this.progres = null; this.pesan = 'File gagal diunggah. Pastikan ukurannya maksimal {{ \App\Support\BatasUnggah::mb($perFile) }} MB, lalu coba lagi.'; this.$refs.input.value = ''; },
                            (event) => { this.progres = event.detail.progress; },
                        );
                    },
                }"
            >
                <label for="form-lampiran" class="mb-1.5 block text-sm font-medium text-slate-700">Lampiran (gambar/file)</label>

                @if ($tersimpan->isNotEmpty())
                    <ul class="mb-2 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-slate-50 text-sm">
                        @foreach ($tersimpan as $lampiran)
                            <li wire:key="lampiran-lama-{{ $lampiran->id }}" class="flex flex-wrap items-center justify-between gap-3 px-3.5 py-2.5">
                                <span class="inline-flex min-w-0 items-center gap-2 text-slate-700">
                                    @if ($lampiran->isImage())<x-heroicon-o-photo class="size-4 shrink-0 text-slate-400" />@else<x-heroicon-o-paper-clip class="size-4 shrink-0 text-slate-400" />@endif
                                    <span class="truncate">{{ $lampiran->nama }}</span>
                                    @if ($lampiran->ukuranTerbaca())<span class="shrink-0 text-xs text-slate-400">{{ $lampiran->ukuranTerbaca() }}</span>@endif
                                </span>
                                <x-ui.checkbox label="Hapus" name="form.hapus_lampiran.{{ $lampiran->id }}" wire:model="form.hapus_lampiran" value="{{ $lampiran->id }}" />
                            </li>
                        @endforeach
                    </ul>
                @endif

                <input
                    id="form-lampiran"
                    type="file"
                    multiple
                    x-ref="input"
                    x-on:change="pilih($event.target.files)"
                    :disabled="progres !== null"
                    accept="{{ collect(\App\Models\Informasi::LAMPIRAN_EKSTENSI)->map(fn ($ext) => '.'.$ext)->join(',') }}"
                    @class([
                        'block w-full rounded-xl border bg-white text-sm text-slate-600 shadow-sm transition file:mr-3 file:rounded-l-xl file:border-0 file:bg-slate-100 file:px-3.5 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:opacity-60',
                        'border-rose-300' => $adaErrorFile,
                        'border-slate-200' => ! $adaErrorFile,
                    ])
                >

                <div x-show="progres !== null" x-cloak class="mt-1.5 flex items-center gap-2 text-xs text-slate-500">
                    <x-heroicon-m-arrow-path class="size-3.5 animate-spin" />
                    <span>Mengunggah file… <span x-text="progres + '%'"></span></span>
                    <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100"><span class="block h-full rounded-full bg-primary-900 transition-all" :style="'width:' + progres + '%'"></span></span>
                </div>

                <p x-show="pesan" x-cloak x-text="pesan" class="mt-1.5 text-sm text-rose-600"></p>

                @if ($form->lampiran)
                    <ul class="mt-2 divide-y divide-slate-100 rounded-xl border border-primary-100 bg-primary-50/40 text-sm">
                        @foreach ($form->lampiran as $file)
                            <li wire:key="lampiran-baru-{{ $file->getFilename() }}" class="flex items-center justify-between gap-3 px-3.5 py-2">
                                <span class="inline-flex min-w-0 items-center gap-2 text-slate-700">
                                    <x-heroicon-o-arrow-up-tray class="size-4 shrink-0 text-primary-700" />
                                    <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                                    <span class="shrink-0 text-xs text-slate-400">{{ \Illuminate\Support\Number::fileSize($file->getSize(), precision: 1) }}</span>
                                </span>
                                <button type="button" wire:click="$removeUpload('form.lampiran', '{{ $file->getFilename() }}')" class="rounded-lg p-1 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" aria-label="Batalkan {{ $file->getClientOriginalName() }}" title="Batalkan file ini">
                                    <x-heroicon-m-x-mark class="size-4" />
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($errors->has('form.lampiran'))
                    <p class="mt-1.5 text-sm text-rose-600">{{ $errors->first('form.lampiran') }}</p>
                @elseif ($errors->has('form.lampiran.*'))
                    @foreach ($errors->get('form.lampiran.*') as $key => $messages)
                        @php $indeks = (int) \Illuminate\Support\Str::afterLast($key, '.'); @endphp
                        <p class="mt-1.5 text-sm text-rose-600">{{ ($form->lampiran[$indeks] ?? null)?->getClientOriginalName() ?? 'Lampiran ke-'.($indeks + 1) }}: {{ $messages[0] }}</p>
                    @endforeach
                @else
                    <p class="mt-1.5 text-xs text-slate-500">
                        Bisa pilih beberapa file sekaligus. Maks. {{ \App\Support\BatasUnggah::mb($perFile) }} MB per file, {{ \App\Models\Informasi::LAMPIRAN_MAKS_JUMLAH }} file per informasi; format {{ strtoupper(implode(', ', \App\Models\Informasi::LAMPIRAN_EKSTENSI)) }}. File hanya bisa dibuka oleh anggota kelas.
                    </p>
                @endif
            </div>
        @endif

        @if ($this->canManage)
            <x-ui.checkbox label="Sematkan di atas" name="form.is_pinned" wire:model="form.is_pinned" description="Informasi yang disematkan selalu tampil paling atas." />
        @endif
    </form>

    <x-slot:footer>
        <x-ui.form-actions form="form-informasi" :label="$form->informasi ? 'Simpan Perubahan' : 'Bagikan'" />
    </x-slot:footer>
</x-ui.modal>
