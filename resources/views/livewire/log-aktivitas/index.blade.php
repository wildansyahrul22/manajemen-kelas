@php $isSuperAdmin = $this->actor->isSuperAdmin(); @endphp
<div>
    <x-ui.page-header title="Log Aktivitas" :description="$isSuperAdmin ? 'Riwayat aktivitas seluruh kelas: siapa membuat, mengubah, atau menghapus data, serta masuk/keluar.' : 'Riwayat aktivitas kelas '.$this->actor->kelas?->nama.': siapa membuat, mengubah, atau menghapus data, serta masuk/keluar.'" />

    <x-ui.card :padding="false">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:flex-wrap lg:items-center">
            <div class="flex-1 lg:max-w-xs">
                <x-ui.search wire:model.live.debounce.500ms="search" placeholder="Cari nama user / data..." />
            </div>
            @if ($isSuperAdmin)
                <x-ui.combobox name="kelasId" wire:model.live="kelasId" :options="$this->kelasOptions" placeholder="Semua kelas" clearable class="sm:w-48" />
            @endif
            <x-ui.select name="aksi" wire:model.live="aksi" :options="$aksiOptions" placeholder="Semua aksi" clearable class="sm:w-40" />
            <x-ui.select name="modul" wire:model.live="modul" :options="$modulOptions" placeholder="Semua modul" clearable class="sm:w-48" />
            <div class="lg:ml-auto">
                <x-ui.per-page wire:model.live="perPage" />
            </div>
        </div>

        <div wire:loading.class="opacity-50" wire:target="search, kelasId, aksi, modul, perPage, gotoPage, nextPage, previousPage" class="transition-opacity">
            @if ($this->daftarLog->isEmpty())
                <x-ui.empty-state title="Belum ada aktivitas" description="Aktivitas akan tercatat otomatis saat data dibuat, diubah, atau dihapus." icon="heroicon-o-clock" />
            @else
                <x-ui.table>
                    <x-slot:head>
                        <x-ui.th>Waktu</x-ui.th>
                        <x-ui.th>User</x-ui.th>
                        <x-ui.th>Aksi</x-ui.th>
                        <x-ui.th>Data</x-ui.th>
                        @if ($isSuperAdmin)<x-ui.th class="hidden md:table-cell">Kelas</x-ui.th>@endif
                        <x-ui.th class="hidden lg:table-cell">IP</x-ui.th>
                    </x-slot:head>
                    @foreach ($this->daftarLog as $log)
                        <tr wire:key="log-{{ $log->id }}" x-data="{ buka: false }" class="align-top transition hover:bg-slate-50/70">
                            <x-ui.td class="whitespace-nowrap text-slate-600">
                                <span class="block">{{ $log->created_at->isoFormat('D MMM YYYY') }}</span>
                                <span class="block text-xs text-slate-400">{{ $log->created_at->format('H:i:s') }}</span>
                            </x-ui.td>
                            <x-ui.td>
                                <div class="flex items-center gap-2.5">
                                    <x-ui.avatar :name="$log->user_name" size="xs" />
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-800">{{ $log->user_name }}</p>
                                        <p class="text-xs text-slate-400">{{ $log->user?->role?->label() ?? ($log->user_id ? 'Pengguna terhapus' : 'Otomatis') }}</p>
                                    </div>
                                </div>
                            </x-ui.td>
                            <x-ui.td><x-ui.badge :class="$log->aksi->badgeClass()">{{ $log->aksi->label() }}</x-ui.badge></x-ui.td>
                            <x-ui.td>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $log->modul->label() }}</p>
                                <p class="font-medium text-slate-800">{{ $log->subjek_label }}</p>
                                @if ($log->perubahan)
                                    <button type="button" x-on:click="buka = ! buka" class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-primary-700 hover:underline">
                                        <x-heroicon-m-chevron-down class="size-3.5 transition" ::class="buka && 'rotate-180'" />
                                        <span x-text="buka ? 'Sembunyikan perubahan' : '{{ count($log->perubahan) }} perubahan'"></span>
                                    </button>
                                    <dl x-show="buka" x-cloak class="mt-2 space-y-1.5 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs">
                                        @foreach ($log->perubahan as $field => [$sebelum, $sesudah])
                                            <div class="grid gap-x-3 sm:grid-cols-[minmax(6rem,auto)_1fr]">
                                                <dt class="font-medium text-slate-600">{{ str_replace('_', ' ', $field) }}</dt>
                                                <dd class="break-words text-slate-700">
                                                    <span class="text-rose-600 line-through decoration-rose-300">{{ is_scalar($sebelum) ? ($sebelum === '' ? '(kosong)' : (is_bool($sebelum) ? ($sebelum ? 'ya' : 'tidak') : $sebelum)) : ($sebelum === null ? '(kosong)' : json_encode($sebelum, JSON_UNESCAPED_UNICODE)) }}</span>
                                                    <span class="mx-1 text-slate-400">→</span>
                                                    <span class="text-emerald-700">{{ is_scalar($sesudah) ? ($sesudah === '' ? '(kosong)' : (is_bool($sesudah) ? ($sesudah ? 'ya' : 'tidak') : $sesudah)) : ($sesudah === null ? '(kosong)' : json_encode($sesudah, JSON_UNESCAPED_UNICODE)) }}</span>
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif
                            </x-ui.td>
                            @if ($isSuperAdmin)
                                <x-ui.td class="hidden text-slate-600 md:table-cell">{{ $log->kelas?->nama ?? '—' }}</x-ui.td>
                            @endif
                            <x-ui.td class="hidden font-mono text-xs text-slate-400 lg:table-cell">{{ $log->ip ?? '—' }}</x-ui.td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </div>

        <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
            {{ $this->daftarLog->links() }}
        </div>
    </x-ui.card>
</div>
