{{--
    Image viewer. Wrap the thumbnails/rows in this component and call `buka(i)` from any of them to
    show `$gambar[i]` full size, with a download link and (when there is more than one) navigation.
    Each item: ['nama' => ..., 'keterangan' => ..., 'src' => ..., 'unduh' => ...].

    The panel stays in the DOM (x-show, not x-if) so a Livewire re-render cannot strip it while it
    is open; `item` keeps the bindings safe while nothing is selected.
--}}
@props(['gambar' => []])
<div
    x-data="{
        gambar: {{ \Illuminate\Support\Js::from($gambar) }},
        aktif: null,
        get item() { return this.gambar[this.aktif] ?? { nama: '', keterangan: '', src: null, unduh: null }; },
        buka(indeks) { this.aktif = indeks; },
        tutup() { this.aktif = null; },
        geser(langkah) { this.aktif = (this.aktif + langkah + this.gambar.length) % this.gambar.length; },
    }"
    x-on:keydown.escape.window="tutup()"
    x-on:keydown.arrow-left.window="if (aktif !== null && gambar.length > 1) geser(-1)"
    x-on:keydown.arrow-right.window="if (aktif !== null && gambar.length > 1) geser(1)"
>
    {{ $slot }}

    <div x-show="aktif !== null" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Pratinjau gambar">
        <div x-show="aktif !== null" x-transition.opacity.duration.200ms x-on:click="tutup()" class="absolute inset-0 bg-slate-900/85 backdrop-blur-sm"></div>

        <div class="relative flex h-full flex-col" x-trap.noscroll="aktif !== null">
            <div class="flex items-start justify-between gap-3 px-4 pt-4 sm:px-6">
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-white" x-text="item.nama"></p>
                    <p class="mt-0.5 text-xs text-slate-300">
                        <span x-text="item.keterangan"></span>
                        <span x-show="gambar.length > 1"> · <span x-text="(aktif + 1) + ' dari ' + gambar.length"></span></span>
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <a :href="item.unduh" class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-white/20">
                        <x-heroicon-m-arrow-down-tray class="size-4" /> <span class="hidden sm:inline">Unduh</span>
                    </a>
                    <button type="button" x-on:click="tutup()" class="rounded-lg bg-white/10 p-1.5 text-white transition hover:bg-white/20" aria-label="Tutup pratinjau">
                        <x-heroicon-o-x-mark class="size-5" />
                    </button>
                </div>
            </div>

            <div class="flex min-h-0 flex-1 items-center justify-center gap-2 p-4 sm:gap-4 sm:p-6" x-on:click.self="tutup()">
                <button type="button" x-show="gambar.length > 1" x-on:click="geser(-1)" class="shrink-0 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20" aria-label="Gambar sebelumnya">
                    <x-heroicon-m-chevron-left class="size-5" />
                </button>

                <img :src="item.src" :alt="item.nama" class="max-h-full w-auto max-w-full rounded-xl object-contain shadow-2xl">

                <button type="button" x-show="gambar.length > 1" x-on:click="geser(1)" class="shrink-0 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20" aria-label="Gambar berikutnya">
                    <x-heroicon-m-chevron-right class="size-5" />
                </button>
            </div>
        </div>
    </div>
</div>
