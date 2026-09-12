<div>
    <x-ui.page-header title="Daftar Informasi" :description="'Pengumuman dan informasi untuk kelas ' . $this->kelas->nama . '.'">
        <x-slot:actions>
            <x-ui.button wire:click="openCreate" opens="showForm"><x-heroicon-m-plus class="size-4" /> Bagikan Informasi</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card :padding="false">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center">
            <div class="flex-1 lg:max-w-xs">
                <x-ui.search wire:model.live.debounce.500ms="search" placeholder="Cari judul informasi..." />
            </div>
            <x-ui.combobox name="kategoriId" wire:model.live="kategoriId" :options="$this->kategoriOptions->pluck('nama', 'id')" placeholder="Semua kategori"
                clearable class="sm:w-52" />
            <div class="lg:ml-auto">
                <x-ui.per-page wire:model.live="perPage" />
            </div>
        </div>

        <div wire:loading.class="opacity-50" wire:target="search, kategoriId, perPage, gotoPage, nextPage, previousPage"
            class="transition-opacity">
            @if ($this->daftarInformasi->isEmpty())
                <x-ui.empty-state title="Belum ada informasi"
                    description="Jadilah yang pertama membagikan informasi untuk kelas ini."
                    icon="heroicon-o-megaphone">
                    <x-ui.button wire:click="openCreate" opens="showForm" variant="secondary"><x-heroicon-m-plus class="size-4" />
                        Bagikan informasi</x-ui.button>
                </x-ui.empty-state>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($this->daftarInformasi as $informasi)
                        <li wire:key="info-{{ $informasi->id }}"
                            class="flex gap-4 px-5 py-4 transition hover:bg-slate-50/70 sm:px-6">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($informasi->is_pinned)
                                        <span
                                            class="inline-flex items-center gap-1 text-xs font-medium text-amber-600"><x-heroicon-s-bookmark
                                                class="size-3.5" /> Disematkan</span>
                                    @endif
                                    <x-ui.badge :class="$informasi->kategori->badgeClass()">{{ $informasi->kategori->nama }}</x-ui.badge>
                                </div>
                                <a href="{{ route('informasi.show', $informasi) }}" wire:navigate
                                    class="mt-1.5 block text-base font-semibold text-slate-800 hover:text-primary-900 hover:underline">{{ $informasi->judul }}</a>
                                <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $informasi->isi }}</p>
                                <p class="mt-2 text-xs text-slate-400">
                                    {{ $informasi->creator?->name ?? 'Pengguna terhapus' }} ·
                                    {{ $informasi->created_at->isoFormat('D MMM YYYY, HH:mm') }}
                                </p>
                            </div>
                            @if (auth()->user()->can('update', $informasi))
                                <div class="shrink-0">
                                    <x-ui.action-menu>
                                        <x-ui.menu-item :href="route('informasi.show', $informasi)" icon="heroicon-o-eye">Lihat
                                            detail</x-ui.menu-item>
                                        @can('pin', $informasi)
                                            <x-ui.menu-item wire:click="togglePin({{ $informasi->id }})"
                                                :icon="$informasi->is_pinned
                                                    ? 'heroicon-s-bookmark'
                                                    : 'heroicon-o-bookmark'">{{ $informasi->is_pinned ? 'Lepas sematan' : 'Sematkan' }}</x-ui.menu-item>
                                        @endcan
                                        <x-ui.menu-item wire:click="openEdit({{ $informasi->id }})" opens="showForm"
                                            icon="heroicon-o-pencil-square">Edit</x-ui.menu-item>
                                        <x-ui.menu-item wire:click="confirmDelete({{ $informasi->id }})" opens="confirmingDelete"
                                            icon="heroicon-o-trash" danger>Hapus</x-ui.menu-item>
                                    </x-ui.action-menu>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
            {{ $this->daftarInformasi->links() }}
        </div>
    </x-ui.card>

    @include('livewire.informasi.partials.form-modal')
    <x-ui.confirm model="confirmingDelete" title="Hapus informasi?"
        description="Informasi akan dihapus untuk seluruh anggota kelas." />
</div>
