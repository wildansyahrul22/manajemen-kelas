<div>
    <x-ui.page-header :title="$informasi->judul" :back="route('informasi.index')">
        @can('update', $informasi)
            <x-slot:actions>
                @can('pin', $informasi)
                    <x-ui.button variant="secondary" wire:click="togglePin({{ $informasi->id }})">
                        @if ($informasi->is_pinned)<x-heroicon-s-bookmark class="size-4 text-amber-500" /> Lepas sematan @else <x-heroicon-o-bookmark class="size-4" /> Sematkan @endif
                    </x-ui.button>
                @endcan
                <x-ui.button variant="secondary" wire:click="openEdit({{ $informasi->id }})" opens="showForm"><x-heroicon-m-pencil-square class="size-4" /> Edit</x-ui.button>
                <x-ui.button variant="danger" wire:click="confirmDelete({{ $informasi->id }})" opens="confirmingDelete"><x-heroicon-m-trash class="size-4" /> Hapus</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.card class="max-w-3xl">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge :class="$informasi->kategori->badgeClass()">{{ $informasi->kategori->nama }}</x-ui.badge>
            @if ($informasi->is_pinned)
                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-600"><x-heroicon-s-bookmark class="size-3.5" /> Disematkan</span>
            @endif
        </div>

        <div class="mt-5 whitespace-pre-line text-[15px] leading-relaxed text-slate-700">{{ $informasi->isi }}</div>

        @if ($informasi->link || $informasi->hasLampiran())
            <div class="mt-5 space-y-3">
                @if ($informasi->hasLampiran())
                    @if ($informasi->lampiranIsImage())
                        <a href="{{ route('informasi.lampiran', $informasi) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                            <img src="{{ route('informasi.lampiran', $informasi) }}" alt="{{ $informasi->lampiran_nama }}" class="mx-auto max-h-[28rem] w-auto max-w-full object-contain" loading="lazy">
                        </a>
                    @endif
                    <a href="{{ route('informasi.lampiran', $informasi) }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm transition hover:border-primary-300 hover:bg-slate-50">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                            @if ($informasi->lampiranIsImage())<x-heroicon-o-photo class="size-5" />@else<x-heroicon-o-document-arrow-down class="size-5" />@endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium text-slate-800">{{ $informasi->lampiran_nama }}</span>
                            <span class="block text-xs text-slate-500">Lampiran · {{ strtoupper($informasi->lampiranEkstensi()) }} · klik untuk {{ $informasi->lampiranIsImage() ? 'membuka' : 'mengunduh' }}</span>
                        </span>
                        <x-heroicon-m-arrow-top-right-on-square class="size-4 shrink-0 text-slate-400" />
                    </a>
                @endif

                @if ($informasi->link)
                    <a href="{{ $informasi->link }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm transition hover:border-primary-300 hover:bg-slate-50">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                            <x-heroicon-o-link class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium text-slate-800">{{ parse_url($informasi->link, PHP_URL_HOST) ?: $informasi->link }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $informasi->link }}</span>
                        </span>
                        <x-heroicon-m-arrow-top-right-on-square class="size-4 shrink-0 text-slate-400" />
                    </a>
                @endif
            </div>
        @endif

        <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
            <x-ui.avatar :name="$informasi->creator?->name ?? '?'" size="sm" />
            <div class="text-sm">
                <p class="font-semibold text-slate-800">{{ $informasi->creator?->name ?? 'Pengguna terhapus' }}</p>
                <p class="text-xs text-slate-500">
                    {{ $informasi->creator?->role?->label() }} · {{ $informasi->created_at->isoFormat('D MMMM YYYY, HH:mm') }}
                    @if ($informasi->updated_at->gt($informasi->created_at->addMinute()))
                        · diperbarui {{ $informasi->updated_at->diffForHumans() }}
                    @endif
                </p>
            </div>
        </div>
    </x-ui.card>

    @can('update', $informasi)
        @include('livewire.informasi.partials.form-modal')
        <x-ui.confirm model="confirmingDelete" title="Hapus informasi?" description="Informasi akan dihapus untuk seluruh anggota kelas." />
    @endcan
</div>
