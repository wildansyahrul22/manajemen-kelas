{{--
    Attachments of one record on its detail page: image thumbnails (opened in the lightbox), then
    one row per file — images open the lightbox, PDFs preview in a new tab, the rest download.
    `route` is the streaming route that takes [$induk, $lampiran] (informasi.lampiran,
    tugas.lampiran). Extra rows (a link, for instance) go in the slot, after the files.
--}}
@props(['daftar', 'route', 'induk'])
@php
    $gambar = $daftar->filter(fn ($lampiran) => $lampiran->isImage())->values();
    $indeksGambar = $gambar->pluck('id')->flip();
    $galeri = $gambar->map(fn ($lampiran) => [
        'nama' => $lampiran->nama,
        'keterangan' => strtoupper($lampiran->ekstensi()).($lampiran->ukuranTerbaca() ? ' · '.$lampiran->ukuranTerbaca() : ''),
        'src' => route($route, [$induk, $lampiran]),
        'unduh' => route($route, [$induk, $lampiran, 'unduh' => 1]),
    ])->all();
@endphp
<x-ui.lightbox :gambar="$galeri">
    <div {{ $attributes->merge(['class' => 'space-y-3']) }}>
        @if ($gambar->isNotEmpty())
            <div @class(['grid gap-3', 'grid-cols-1' => $gambar->count() === 1, 'grid-cols-2' => $gambar->count() > 1])>
                @foreach ($gambar as $indeks => $lampiran)
                    <a href="{{ route($route, [$induk, $lampiran]) }}" x-on:click.prevent="buka({{ $indeks }})" wire:key="gambar-{{ $lampiran->id }}" title="Lihat {{ $lampiran->nama }}" class="group relative block overflow-hidden rounded-xl border border-slate-200 bg-slate-50 transition hover:border-primary-300">
                        <img src="{{ route($route, [$induk, $lampiran]) }}" alt="{{ $lampiran->nama }}" @class(['mx-auto w-auto max-w-full object-contain', 'max-h-[28rem]' => $gambar->count() === 1, 'h-48 w-full object-cover' => $gambar->count() > 1]) loading="lazy">
                        <span class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-0 transition group-hover:bg-slate-900/25 group-hover:opacity-100">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium text-slate-700 shadow"><x-heroicon-m-magnifying-glass-plus class="size-4" /> Lihat</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        @foreach ($daftar as $lampiran)
            @php
                $pratinjauUrl = route($route, [$induk, $lampiran]);
                $unduhUrl = route($route, [$induk, $lampiran, 'unduh' => 1]);
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

        {{ $slot }}
    </div>
</x-ui.lightbox>
