<div>
    <x-ui.page-header :title="$informasi->judul" :back="route('informasi.index')">
        <x-slot:actions>
            <x-ui.whatsapp-button :text="$this->teksWhatsApp" />
            @can('update', $informasi)
                @can('pin', $informasi)
                    <x-ui.button variant="secondary" wire:click="togglePin({{ $informasi->id }})">
                        @if ($informasi->is_pinned)<x-heroicon-s-bookmark class="size-4 text-amber-500" /> Lepas sematan @else <x-heroicon-o-bookmark class="size-4" /> Sematkan @endif
                    </x-ui.button>
                @endcan
                <x-ui.button variant="secondary" wire:click="openEdit({{ $informasi->id }})" opens="showForm"><x-heroicon-m-pencil-square class="size-4" /> Edit</x-ui.button>
                <x-ui.button variant="danger" wire:click="confirmDelete({{ $informasi->id }})" opens="confirmingDelete"><x-heroicon-m-trash class="size-4" /> Hapus</x-ui.button>
            @endcan
        </x-slot:actions>
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
            @php
                $gambar = $informasi->lampiran->filter(fn ($lampiran) => $lampiran->isImage())->values();
                $indeksGambar = $gambar->pluck('id')->flip();
                $galeri = $gambar->map(fn ($lampiran) => [
                    'nama' => $lampiran->nama,
                    'keterangan' => strtoupper($lampiran->ekstensi()).($lampiran->ukuranTerbaca() ? ' · '.$lampiran->ukuranTerbaca() : ''),
                    'src' => route('informasi.lampiran', [$informasi, $lampiran]),
                    'unduh' => route('informasi.lampiran', [$informasi, $lampiran, 'unduh' => 1]),
                ])->all();
            @endphp
            <x-ui.lightbox :gambar="$galeri">
                <div class="mt-5 space-y-3">
                    @if ($gambar->isNotEmpty())
                        <div @class(['grid gap-3', 'grid-cols-1' => $gambar->count() === 1, 'grid-cols-2' => $gambar->count() > 1])>
                            @foreach ($gambar as $indeks => $lampiran)
                                <a href="{{ route('informasi.lampiran', [$informasi, $lampiran]) }}" x-on:click.prevent="buka({{ $indeks }})" wire:key="gambar-{{ $lampiran->id }}" title="Lihat {{ $lampiran->nama }}" class="group relative block overflow-hidden rounded-xl border border-slate-200 bg-slate-50 transition hover:border-primary-300">
                                    <img src="{{ route('informasi.lampiran', [$informasi, $lampiran]) }}" alt="{{ $lampiran->nama }}" @class(['mx-auto w-auto max-w-full object-contain', 'max-h-[28rem]' => $gambar->count() === 1, 'h-48 w-full object-cover' => $gambar->count() > 1]) loading="lazy">
                                    <span class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-0 transition group-hover:bg-slate-900/25 group-hover:opacity-100">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium text-slate-700 shadow"><x-heroicon-m-magnifying-glass-plus class="size-4" /> Lihat</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @foreach ($informasi->lampiran as $lampiran)
                        @php
                            $pratinjauUrl = route('informasi.lampiran', [$informasi, $lampiran]);
                            $unduhUrl = route('informasi.lampiran', [$informasi, $lampiran, 'unduh' => 1]);
                        @endphp
                        <a
                            href="{{ $lampiran->bisaDipratinjau() ? $pratinjauUrl : $unduhUrl }}"
                            @if ($lampiran->isImage())
                                x-on:click.prevent="buka({{ $indeksGambar[$lampiran->id] }})"
                            @elseif ($lampiran->isPdf())
                                target="_blank" rel="noopener"
                            @endif
                            wire:key="lampiran-{{ $lampiran->id }}"
                            class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm transition hover:border-primary-300 hover:bg-slate-50"
                        >
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                @if ($lampiran->isImage())<x-heroicon-o-photo class="size-5" />@elseif ($lampiran->isPdf())<x-heroicon-o-document-text class="size-5" />@else<x-heroicon-o-document-arrow-down class="size-5" />@endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium text-slate-800">{{ $lampiran->nama }}</span>
                                <span class="block text-xs text-slate-500">Lampiran · {{ strtoupper($lampiran->ekstensi()) }}{{ $lampiran->ukuranTerbaca() ? ' · '.$lampiran->ukuranTerbaca() : '' }} · klik untuk {{ $lampiran->isImage() ? 'melihat' : ($lampiran->isPdf() ? 'pratinjau di tab baru' : 'mengunduh') }}</span>
                            </span>
                            @if ($lampiran->isImage())<x-heroicon-m-magnifying-glass-plus class="size-4 shrink-0 text-slate-400" />@elseif ($lampiran->isPdf())<x-heroicon-m-arrow-top-right-on-square class="size-4 shrink-0 text-slate-400" />@else<x-heroicon-m-arrow-down-tray class="size-4 shrink-0 text-slate-400" />@endif
                        </a>
                    @endforeach

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
            </x-ui.lightbox>
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
