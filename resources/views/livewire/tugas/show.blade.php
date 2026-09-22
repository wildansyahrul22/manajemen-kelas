@php
    $statusBadge = ['aktif' => ['emerald', 'Belum deadline'], 'segera' => ['amber', 'Segera deadline'], 'lewat' => ['rose', 'Lewat deadline']];
    [$warna, $label] = $statusBadge[$tugas->status()];
@endphp
<div>
    <x-ui.page-header :title="$tugas->nama" :back="route('tugas.index')">
        <x-slot:actions>
            <x-ui.whatsapp-button :text="$this->teksWhatsApp" />
            @if ($tugas->link_pengumpulan)
                <x-ui.button :href="$tugas->link_pengumpulan" :navigate="false" target="_blank" rel="noopener noreferrer"><x-heroicon-m-arrow-up-tray class="size-4" /> Kumpulkan Tugas</x-ui.button>
            @endif
            @can('update', $tugas)
                <x-ui.button variant="secondary" wire:click="openEdit({{ $tugas->id }})" opens="showForm"><x-heroicon-m-pencil-square class="size-4" /> Edit</x-ui.button>
                <x-ui.button variant="danger" wire:click="confirmDelete({{ $tugas->id }})" opens="confirmingDelete"><x-heroicon-m-trash class="size-4" /> Hapus</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
        <x-ui.card class="lg:col-span-2">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :color="$warna">{{ $label }}</x-ui.badge>
                <span class="text-sm text-slate-500">{{ $tugas->sisaWaktu() }}</span>
                @if ($tugas->isTugasKelompok())
                    <x-ui.badge color="sky"><x-heroicon-m-user-group class="size-3.5" /> Tugas kelompok · {{ $tugas->kategoriKelompok->nama }}</x-ui.badge>
                @endif
            </div>

            <h3 class="mt-4 text-sm font-semibold uppercase tracking-wider text-slate-400">Deskripsi</h3>
            @if ($tugas->deskripsi)
                <div class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $tugas->deskripsi }}</div>
            @else
                <p class="mt-2 text-sm italic text-slate-400">Tidak ada deskripsi.</p>
            @endif

            <h3 class="mt-6 text-sm font-semibold uppercase tracking-wider text-slate-400">Pengumpulan</h3>
            @if ($tugas->link_pengumpulan)
                <a href="{{ $tugas->link_pengumpulan }}" target="_blank" rel="noopener noreferrer" class="mt-2 flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm transition hover:border-primary-300 hover:bg-slate-50">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                        <x-heroicon-o-arrow-up-tray class="size-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-medium text-slate-800">Kumpulkan lewat {{ parse_url($tugas->link_pengumpulan, PHP_URL_HOST) ?: 'tautan' }}</span>
                        <span class="block truncate text-xs text-slate-500">{{ $tugas->link_pengumpulan }}</span>
                    </span>
                    <x-heroicon-m-arrow-top-right-on-square class="size-4 shrink-0 text-slate-400" />
                </a>
                @if ($tugas->isLewat())
                    <p class="mt-2 text-xs text-rose-600">Deadline sudah lewat — pastikan pengumpulan masih diterima.</p>
                @endif
            @else
                <p class="mt-2 text-sm italic text-slate-400">Belum ada link pengumpulan. Ikuti petunjuk dosen atau admin kelas.</p>
            @endif

            @if ($tugas->hasLampiran())
                <h3 class="mt-6 text-sm font-semibold uppercase tracking-wider text-slate-400">Lampiran</h3>
                <x-ui.lampiran-list :daftar="$tugas->lampiran" route="tugas.lampiran" :induk="$tugas" class="mt-2" />
            @endif
        </x-ui.card>

        <div class="space-y-6">
            @if ($tugas->isTugasKelompok())
                <x-ui.card title="Kelompok Saya" :description="'Kategori '.$tugas->kategoriKelompok->nama.' · '.$this->jumlahKelompok.' kelompok'" :padding="false">
                    @if ($this->kelompokSaya)
                        @php $ketua = $this->kelompokSaya->anggota->first(fn ($anggota) => (bool) $anggota->pivot->is_ketua); @endphp
                        <div class="px-5 py-4 sm:px-6">
                            <a href="{{ route('kelompok.show', $this->kelompokSaya) }}" wire:navigate class="text-base font-bold text-slate-900 hover:text-primary-900 hover:underline">{{ $this->kelompokSaya->nama }}</a>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $this->kelompokSaya->anggota->count() }} anggota{{ $ketua ? ' · Ketua: '.$ketua->name : '' }}</p>
                        </div>
                        <ul class="divide-y divide-slate-100 border-t border-slate-100">
                            @foreach ($this->kelompokSaya->anggota as $anggota)
                                <li wire:key="anggota-{{ $anggota->id }}" class="flex items-center gap-3 px-5 py-2.5 sm:px-6">
                                    <x-ui.avatar :name="$anggota->name" size="xs" />
                                    <div class="min-w-0 flex-1">
                                        <p @class(['truncate text-sm text-slate-800', 'font-semibold' => $anggota->id === auth()->id()])>{{ $anggota->name }}{{ $anggota->id === auth()->id() ? ' (saya)' : '' }}</p>
                                        <p class="font-mono text-xs text-slate-400">{{ $anggota->npm }}</p>
                                    </div>
                                    @if ($anggota->pivot->is_ketua)<x-ui.badge color="primary">Ketua</x-ui.badge>@endif
                                </li>
                            @endforeach
                        </ul>
                        <div class="border-t border-slate-100 px-5 py-3 sm:px-6">
                            <a href="{{ route('kelompok.show', $this->kelompokSaya) }}" wire:navigate class="text-sm font-medium text-primary-700 hover:text-primary-900 hover:underline">Lihat detail kelompok</a>
                        </div>
                    @else
                        <x-ui.empty-state title="Kamu belum masuk kelompok" :description="'Belum ada kelompok pada kategori '.$tugas->kategoriKelompok->nama.' yang mencantumkan kamu sebagai anggota.'" icon="heroicon-o-user-group" class="py-8">
                            <x-ui.button :href="route('kelompok.index', ['kategori' => $tugas->kategori_kelompok_id])" variant="secondary" size="sm">Lihat kelompok kategori ini</x-ui.button>
                        </x-ui.empty-state>
                    @endif
                </x-ui.card>
            @endif

            <x-ui.card title="Detail">
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-slate-500">Deadline</dt>
                        <dd class="mt-0.5 font-semibold text-slate-800">{{ $tugas->deadline->isoFormat('dddd, D MMMM YYYY') }}<br><span class="font-medium text-slate-600">Pukul {{ $tugas->deadline->format('H:i') }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Mata kuliah</dt>
                        <dd class="mt-0.5">
                            <a href="{{ route('mata-kuliah.show', $tugas->mataKuliah) }}" wire:navigate class="font-semibold text-slate-800 hover:text-primary-900 hover:underline">{{ $tugas->mataKuliah->nama }}</a>
                            <p class="text-slate-600">{{ $tugas->mataKuliah->dosen }}</p>
                            <p class="text-xs text-slate-400">{{ $tugas->mataKuliah->kode ? $tugas->mataKuliah->kode.' · ' : '' }}{{ $tugas->mataKuliah->sks }} SKS</p>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Dibuat oleh</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $tugas->creator?->name ?? '—' }}</dd>
                    </div>
                    <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4">
                        <div>
                            <dt class="text-slate-500">Dibuat</dt>
                            <dd class="mt-0.5 font-medium text-slate-700">{{ $tugas->created_at->isoFormat('D MMM YYYY, HH:mm') }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Diperbarui</dt>
                            <dd class="mt-0.5 font-medium text-slate-700">{{ $tugas->updated_at->isoFormat('D MMM YYYY, HH:mm') }}</dd>
                        </div>
                    </div>
                </dl>
            </x-ui.card>
        </div>
    </div>

    @can('update', $tugas)
        @include('livewire.tugas.partials.form-modal')
        <x-ui.confirm model="confirmingDelete" title="Hapus tugas?" description="Tugas akan dihapus dari daftar seluruh anggota kelas." />
    @endcan
</div>
