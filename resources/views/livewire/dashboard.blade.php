@php
    $ringkasan = $this->ringkasan;
    $user = auth()->user();
@endphp
<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl bg-slate-900 px-6 py-7 text-white shadow-lg shadow-slate-900/10 sm:px-8">
        <div class="pointer-events-none absolute -right-16 -top-16 size-56 rounded-full bg-white/10 blur-3xl"></div>
        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm text-slate-300">{{ now()->isoFormat('dddd, D MMMM YYYY') }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">Halo, {{ Str::before($user->name, ' ') }} 👋</h2>
                <p class="mt-2 max-w-xl text-sm text-slate-300">
                    Ringkasan kelas <span class="font-semibold text-white">{{ $this->kelas->nama }}</span>
                    pada <span class="font-semibold text-white">{{ $this->kelas->semesterAktif->nama }}</span>.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-ui.button :href="route('tugas.index', ['status' => 'aktif'])" variant="secondary" size="sm">
                    <x-heroicon-m-clipboard-document-list class="size-4" /> Tugas aktif
                </x-ui.button>
                <x-ui.button :href="route('jadwal.index')" variant="secondary" size="sm">
                    <x-heroicon-m-calendar-days class="size-4" /> Jadwal
                </x-ui.button>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Total Mahasiswa" :value="$ringkasan['mahasiswa']" icon="heroicon-o-users" color="primary" :href="$user->isMahasiswa() ? null : route('users.index')" />
        <x-ui.stat-card label="Tugas Belum Deadline" :value="$ringkasan['tugas_aktif']" icon="heroicon-o-clipboard-document-check" color="amber" :href="route('tugas.index', ['status' => 'aktif'])" />
        <x-ui.stat-card label="Jadwal Hari Ini" :value="$ringkasan['jadwal_hari_ini']" icon="heroicon-o-calendar-days" color="emerald" :hint="now()->isoFormat('dddd')" :href="route('jadwal.index')" />
        <x-ui.stat-card label="Mata Kuliah" :value="$ringkasan['mata_kuliah']" icon="heroicon-o-book-open" color="sky" :hint="$this->kelas->semesterAktif->nama" :href="route('mata-kuliah.index')" />
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-ui.card title="Jadwal Hari Ini" :description="now()->isoFormat('dddd, D MMMM')" :padding="false" class="xl:col-span-1">
            <x-slot:actions>
                <a href="{{ route('jadwal.index') }}" wire:navigate class="text-sm font-medium text-primary-700 hover:text-primary-900 hover:underline">Lihat semua</a>
            </x-slot:actions>
            @forelse ($this->jadwalHariIni as $jadwal)
                <div wire:key="jadwal-{{ $jadwal->id }}" class="flex items-start gap-4 border-b border-slate-100 px-5 py-4 last:border-b-0 sm:px-6">
                    <div class="w-14 shrink-0 text-center">
                        <p class="text-sm font-bold text-slate-900">{{ $jadwal->jam_mulai->format('H:i') }}</p>
                        <p class="text-xs text-slate-400">{{ $jadwal->jam_selesai->format('H:i') }}</p>
                    </div>
                    <div class="min-w-0 flex-1 border-l-2 border-primary-900 pl-4">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $jadwal->mataKuliah->nama }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $jadwal->mataKuliah->dosen }}</p>
                        @if ($jadwal->ruangan)
                            <p class="mt-1 inline-flex items-center gap-1 text-xs text-slate-500"><x-heroicon-m-map-pin class="size-3.5" /> {{ $jadwal->ruangan }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <x-ui.empty-state title="Tidak ada jadwal" description="Tidak ada kelas hari ini. Nikmati harimu!" icon="heroicon-o-calendar" class="py-10" />
            @endforelse
        </x-ui.card>

        <x-ui.card title="Tugas Mendekati Deadline" description="5 tugas dengan deadline terdekat" :padding="false" class="xl:col-span-2">
            <x-slot:actions>
                <a href="{{ route('tugas.index') }}" wire:navigate class="text-sm font-medium text-primary-700 hover:text-primary-900 hover:underline">Lihat semua</a>
            </x-slot:actions>
            @forelse ($this->tugasTerdekat as $tugas)
                <a href="{{ route('tugas.show', $tugas) }}" wire:navigate wire:key="tugas-{{ $tugas->id }}" class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 transition last:border-b-0 hover:bg-slate-50 sm:px-6">
                    <span @class(['flex size-10 shrink-0 items-center justify-center rounded-xl', 'bg-rose-50 text-rose-600' => $tugas->status() === 'segera', 'bg-primary-100 text-primary-800' => $tugas->status() !== 'segera'])>
                        <x-heroicon-o-document-text class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $tugas->nama }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $tugas->mataKuliah->nama }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-sm font-medium text-slate-700">{{ $tugas->deadline->isoFormat('D MMM, HH:mm') }}</p>
                        <p @class(['text-xs', 'text-rose-600 font-medium' => $tugas->status() === 'segera', 'text-slate-400' => $tugas->status() !== 'segera'])>{{ $tugas->sisaWaktu() }}</p>
                    </div>
                </a>
            @empty
                <x-ui.empty-state title="Tidak ada tugas aktif" description="Semua tugas sudah melewati deadline atau belum ada tugas." icon="heroicon-o-clipboard-document-check" class="py-10" />
            @endforelse
        </x-ui.card>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-ui.card title="Informasi Terbaru" :padding="false" class="xl:col-span-2">
            <x-slot:actions>
                <a href="{{ route('informasi.index') }}" wire:navigate class="text-sm font-medium text-primary-700 hover:text-primary-900 hover:underline">Lihat semua</a>
            </x-slot:actions>
            @forelse ($this->informasiTerbaru as $informasi)
                <a href="{{ route('informasi.show', $informasi) }}" wire:navigate wire:key="info-{{ $informasi->id }}" class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 transition last:border-b-0 hover:bg-slate-50 sm:px-6">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            @if ($informasi->is_pinned)
                                <x-heroicon-s-bookmark class="size-4 shrink-0 text-amber-500" />
                            @endif
                            <p class="truncate text-sm font-semibold text-slate-800">{{ $informasi->judul }}</p>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-400">{{ $informasi->created_at->diffForHumans() }}</p>
                    </div>
                    <x-ui.badge :class="$informasi->kategori->badgeClass()">{{ $informasi->kategori->nama }}</x-ui.badge>
                </a>
            @empty
                <x-ui.empty-state title="Belum ada informasi" description="Bagikan informasi pertama untuk kelas ini." icon="heroicon-o-megaphone" class="py-10" />
            @endforelse
        </x-ui.card>

        <div class="grid grid-cols-2 gap-4 xl:grid-cols-1">
            <x-ui.stat-card label="Kelompok" :value="$ringkasan['kelompok']" icon="heroicon-o-user-group" color="primary" :href="route('kelompok.index')" />
            <x-ui.stat-card label="Informasi" :value="$ringkasan['informasi']" icon="heroicon-o-megaphone" color="rose" :href="route('informasi.index')" />
        </div>
    </section>
</div>
