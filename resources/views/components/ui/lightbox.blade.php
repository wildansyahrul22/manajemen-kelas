{{--
    Image viewer. Wrap the thumbnails/rows in this component and call `buka(i)` from any of them to
    show `$gambar[i]`, with a download link and (when there is more than one) navigation.
    Each item: ['nama' => ..., 'keterangan' => ..., 'src' => ..., 'unduh' => ...].

    The image is capped in `dvh` so a tall portrait still leaves the dialog and its controls fully
    visible; the controls sit on the opaque panel, never on the blurred backdrop. The arrows are
    fixed to the viewport so they stay put when the panel resizes between a portrait and a landscape
    image. The panel stays in the DOM (x-show, not x-if) so a Livewire re-render cannot strip it
    while it is open, and `item` keeps the bindings safe while nothing is selected.
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
        <div x-show="aktif !== null" x-transition.opacity.duration.200ms x-on:click="tutup()" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

        <div class="flex h-full items-center justify-center p-4 sm:p-6" x-on:click.self="tutup()">
            <div
                x-show="aktif !== null"
                x-transition:enter="transition duration-200 ease-out"
                x-transition:enter-start="translate-y-4 opacity-0 sm:scale-95"
                x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                x-transition:leave="transition duration-150 ease-in"
                x-transition:leave-end="translate-y-4 opacity-0 sm:scale-95"
                x-trap.noscroll="aktif !== null"
                class="relative flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
            >
                <div class="flex shrink-0 items-start justify-between gap-3 px-4 py-3 sm:px-5">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900" x-text="item.nama"></p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            <span x-text="item.keterangan"></span>
                            <span x-show="gambar.length > 1"> · <span x-text="(aktif + 1) + ' dari ' + gambar.length"></span></span>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <x-ui.button href="#" :navigate="false" size="sm" x-bind:href="item.unduh">
                            <x-heroicon-m-arrow-down-tray class="size-4" /> <span class="hidden sm:inline">Unduh</span>
                        </x-ui.button>
                        <x-ui.button variant="ghost" size="icon-sm" x-on:click="tutup()" aria-label="Tutup pratinjau">
                            <x-heroicon-o-x-mark class="size-5" />
                        </x-ui.button>
                    </div>
                </div>

                <div class="flex items-center justify-center border-t border-slate-100 bg-slate-50 p-3 sm:p-4">
                    <img :src="item.src" :alt="item.nama" class="max-h-[70dvh] w-auto max-w-full rounded-lg object-contain sm:max-h-[78dvh]">
                </div>
            </div>
        </div>

        {{-- Anchored to the viewport, aligned with the panel's edges, so they never move. --}}
        <div x-show="aktif !== null && gambar.length > 1" class="pointer-events-none fixed inset-x-0 top-1/2 mx-auto flex w-full max-w-5xl -translate-y-1/2 justify-between px-6 sm:px-8">
            <button type="button" x-on:click="geser(-1)" class="pointer-events-auto rounded-full border border-slate-200 bg-white p-2 text-slate-600 shadow-md transition hover:bg-slate-50 hover:text-slate-900" aria-label="Gambar sebelumnya">
                <x-heroicon-m-chevron-left class="size-5" />
            </button>

            <button type="button" x-on:click="geser(1)" class="pointer-events-auto rounded-full border border-slate-200 bg-white p-2 text-slate-600 shadow-md transition hover:bg-slate-50 hover:text-slate-900" aria-label="Gambar berikutnya">
                <x-heroicon-m-chevron-right class="size-5" />
            </button>
        </div>
    </div>
</div>
