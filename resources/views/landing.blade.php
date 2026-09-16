@php
    $wa = 'https://wa.me/62812790106175?text='.rawurlencode('Halo, saya mau tanya soal langganan Kelas KampusKu untuk kelas saya.');
    $email = 'admin@kelaskampusku.com';
    $adaDemo = filled(config('demo.npm'));
    // Someone looking around as the demo account is still a prospect: offer them the login and the demo, not "their" dashboard.
    $tamu = auth()->guest() || auth()->user()->isDemo();

    $menu = [
        'Akademik' => [
            ['heroicon-o-clipboard-document-list', 'Daftar Tugas', 'Deadline, link pengumpulan, dan penanda tugas kelompok.'],
            ['heroicon-o-book-open', 'Mata Kuliah', 'Dosen, SKS, dan seluruh isi satu mata kuliah dalam satu halaman.'],
            ['heroicon-o-user-group', 'Kelompok', 'Anggota dan ketua tiap kelompok, lengkap dengan kontaknya.'],
            ['heroicon-o-rectangle-group', 'Kategori Kelompok', 'Pembagian berbeda untuk project akhir, presentasi, praktikum.'],
        ],
        'Jadwal' => [
            ['heroicon-o-calendar-days', 'Jadwal Kelas', 'Jadwal seminggu penuh, siap dibagikan ke grup WhatsApp.'],
            ['heroicon-o-beaker', 'Jadwal Lab', 'Jadwal praktikum terpisah, per mata kuliah.'],
        ],
        'Informasi' => [
            ['heroicon-o-megaphone', 'Pengumuman', 'Berkategori, bisa disematkan, dengan lampiran atau tautan.'],
            ['heroicon-o-tag', 'Kategori Informasi', 'Warna sendiri untuk tiap jenis pengumuman.'],
        ],
        'Pengelolaan' => [
            ['heroicon-o-users', 'Data Mahasiswa', 'Dua peran akun: mahasiswa dan admin kelas.'],
            ['heroicon-o-adjustments-horizontal', 'Semester Aktif', 'Ganti semester tanpa kehilangan data semester lalu.'],
            ['heroicon-o-arrow-down-tray', 'Export Excel', 'Setiap daftar bisa diunduh rapi untuk laporan.'],
            ['heroicon-o-clock', 'Log Aktivitas', 'Catatan siapa mengubah apa, kapan.'],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelas KampusKu — sistem informasi untuk satu kelas kuliah</title>
    <meta name="description" content="Jadwal, tugas, kelompok, dan pengumuman kelas dalam satu aplikasi. Mulai Rp200.000 per semester untuk satu kelas.">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @fonts
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --tinta: #16243f;
            --kertas: #f6f5f1;
            --kunyit: #e0a22c;
            /* Same accent, dark enough to read as text on the paper surfaces. */
            --kunyit-tua: #96660f;
            --redup: #5b6577;
            --garis: #ddd9cf;
        }

        .angka {
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.03em;
        }

        /* The one control on the page: make it feel like a physical slider. */
        .geser {
            -webkit-appearance: none;
            appearance: none;
            background: transparent;
        }

        .geser::-webkit-slider-runnable-track {
            height: 4px;
            border-radius: 999px;
            background: var(--garis);
        }

        .geser::-moz-range-track {
            height: 4px;
            border-radius: 999px;
            background: var(--garis);
        }

        .geser::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            margin-top: -9px;
            height: 22px;
            width: 22px;
            border-radius: 999px;
            background: var(--tinta);
            border: 4px solid #fff;
            box-shadow: 0 1px 6px rgb(22 36 63 / 35%);
            cursor: grab;
        }

        .geser::-moz-range-thumb {
            height: 22px;
            width: 22px;
            border-radius: 999px;
            background: var(--tinta);
            border: 4px solid #fff;
            box-shadow: 0 1px 6px rgb(22 36 63 / 35%);
            cursor: grab;
        }

        .geser:focus-visible::-webkit-slider-thumb {
            outline: 2px solid var(--kunyit);
            outline-offset: 2px;
        }
    </style>
</head>
<body class="h-full bg-[var(--kertas)] font-sans text-[var(--tinta)] antialiased">

<header class="border-b border-[var(--garis)]">
    <div class="mx-auto flex w-full max-w-5xl items-center gap-4 px-5 py-4 sm:px-8">
        <a href="#atas" class="flex items-center gap-2.5">
            <x-ui.logo size="size-8" />
            <span class="text-[15px] font-bold tracking-tight">Kelas KampusKu</span>
        </a>

        <nav class="ml-auto hidden items-center gap-7 text-sm text-[var(--redup)] sm:flex">
            <a href="#isinya" class="transition hover:text-[var(--tinta)]">Isi aplikasinya</a>
            <a href="#harga" class="transition hover:text-[var(--tinta)]">Harga</a>
            @if ($tamu)
                <a href="{{ route('login') }}" class="transition hover:text-[var(--tinta)]">Masuk</a>
            @else
                <a href="{{ route('dashboard') }}" class="transition hover:text-[var(--tinta)]">Buka dashboard</a>
            @endif
        </nav>

        <a href="{{ $wa }}" target="_blank" rel="noopener"
           class="ml-auto inline-flex items-center gap-2 rounded-full bg-[var(--tinta)] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0f1a2e] sm:ml-0">
            <x-ui.whatsapp-icon class="size-4" />
            Tanya dulu
        </a>
    </div>
</header>

<main id="atas">
    <section class="mx-auto w-full max-w-5xl px-5 pb-16 pt-14 sm:px-8 sm:pb-24 sm:pt-20">
        <div class="max-w-2xl">
            <h1 class="text-[2.5rem] font-bold leading-[1.05] tracking-[-0.035em] sm:text-6xl">
                Satu kelas,<br>satu tempat.
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-[var(--redup)]">
                Jadwal kuliah, deadline tugas, daftar kelompok, dan pengumuman berhenti tenggelam di grup
                WhatsApp. Semuanya rapi di satu aplikasi yang dipakai bareng sekelas.
            </p>

            @if ($tamu && $adaDemo)
                <div class="mt-8 flex flex-wrap items-center gap-x-5 gap-y-3">
                    <a href="{{ route('demo') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-[var(--tinta)] px-6 py-3.5 font-semibold text-white transition hover:bg-[#0f1a2e]">
                        <x-heroicon-m-play class="size-5" />
                        Coba demo sekarang
                    </a>
                    <p class="text-sm text-[var(--redup)]">Tanpa daftar, langsung masuk ke kelas contoh.</p>
                </div>
            @endif
        </div>

        <div class="mt-12 border-t border-[var(--garis)] pt-10">
            <p class="text-sm text-[var(--redup)]">Hitung patungannya dulu</p>

            <div id="kalkulator" class="mt-5 grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)] lg:items-start">
                <div>
                    <div class="flex flex-wrap items-baseline gap-x-4 gap-y-2">
                        <span id="harga-terpilih" class="angka text-3xl font-bold sm:text-4xl">Rp250.000</span>
                        <span class="text-2xl text-[var(--redup)]">dibagi</span>
                        <span class="angka text-3xl font-bold sm:text-4xl"><span id="jumlah-tampil">38</span> mahasiswa</span>
                    </div>

                    <div class="mt-7 flex flex-wrap items-end gap-x-3 gap-y-1">
                        <span class="angka text-6xl font-bold leading-none text-[var(--kunyit-tua)] sm:text-7xl" id="per-orang">Rp6.579</span>
                        <span class="pb-1 text-base text-[var(--redup)]">per orang, untuk 6 bulan</span>
                    </div>

                    <label for="jumlah" class="mt-9 block text-sm font-medium">Jumlah mahasiswa di kelas kamu</label>
                    <input id="jumlah" name="jumlah" type="range" min="10" max="60" step="1" value="38"
                           class="geser mt-4 w-full max-w-md" aria-describedby="per-orang">
                    <div class="mt-1 flex w-full max-w-md justify-between text-xs text-[var(--redup)]">
                        <span>10</span><span>60</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-[var(--garis)] bg-white p-6">
                    <p class="text-sm font-semibold">Pilih paketnya</p>
                    <div class="mt-4 space-y-3">
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-[var(--garis)] p-3.5 transition has-[:checked]:border-[var(--tinta)] has-[:checked]:bg-[var(--kertas)]">
                            <input type="radio" name="paket" value="200000" class="mt-0.5 size-4 accent-[#16243f]" data-paket>
                            <span class="text-sm">
                                <span class="angka block font-semibold">Rp200.000</span>
                                <span class="mt-0.5 block text-[var(--redup)]">Semua fitur, lampiran lewat tautan Drive.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-[var(--garis)] p-3.5 transition has-[:checked]:border-[var(--tinta)] has-[:checked]:bg-[var(--kertas)]">
                            <input type="radio" name="paket" value="250000" class="mt-0.5 size-4 accent-[#16243f]" data-paket checked>
                            <span class="text-sm">
                                <span class="angka block font-semibold">Rp250.000</span>
                                <span class="mt-0.5 block text-[var(--redup)]">Termasuk unggah berkas langsung di aplikasi.</span>
                            </span>
                        </label>
                    </div>
                    <p class="mt-4 border-t border-[var(--garis)] pt-4 text-sm text-[var(--redup)]">
                        Sekali patungan seharga segelas kopi, kelas kamu punya sistemnya sendiri selama satu semester.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="isinya" class="border-y border-[var(--garis)] bg-white">
        <div class="mx-auto w-full max-w-5xl px-5 py-16 sm:px-8 sm:py-20">
            <h2 class="max-w-xl text-3xl font-bold tracking-[-0.03em] sm:text-4xl">Inilah menu yang kelas kamu dapat</h2>
            <p class="mt-4 max-w-xl text-[var(--redup)]">
                Bukan daftar janji — ini menu yang benar-benar ada di dalam aplikasinya begitu kelas kamu aktif.
            </p>

            <figure class="mt-10">
                <div class="overflow-hidden rounded-2xl border border-[var(--garis)] bg-[var(--kertas)] p-1.5 shadow-xl shadow-[#16243f]/10 sm:p-2">
                    <img src="{{ asset('images/dashboard.png') }}" width="3558" height="1892" loading="lazy" decoding="async"
                         alt="Dashboard kelas: jadwal hari ini, tugas mendekati deadline, dan informasi terbaru"
                         class="w-full rounded-xl">
                </div>
                <figcaption class="mt-3 text-sm text-[var(--redup)]">
                    Dashboard yang dilihat setiap anggota kelas begitu masuk: jadwal hari ini, tugas yang deadline-nya
                    paling dekat, dan pengumuman terbaru dalam satu layar.
                </figcaption>
            </figure>

            <div class="mt-14 grid gap-x-12 gap-y-10 sm:grid-cols-2">
                @foreach ($menu as $grup => $item)
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--kunyit-tua)]">{{ $grup }}</h3>
                        <ul class="mt-3 divide-y divide-[var(--garis)] border-t border-[var(--garis)]">
                            @foreach ($item as [$ikon, $judul, $keterangan])
                                <li class="flex gap-3.5 py-3.5">
                                    <x-dynamic-component :component="$ikon" class="mt-0.5 size-5 shrink-0 text-[var(--redup)]" />
                                    <div>
                                        <p class="text-[15px] font-semibold leading-snug">{{ $judul }}</p>
                                        <p class="mt-0.5 text-sm leading-relaxed text-[var(--redup)]">{{ $keterangan }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <p class="mt-12 max-w-2xl border-l-2 border-[var(--kunyit)] pl-5 text-[15px] leading-relaxed text-[var(--redup)]">
                Mahasiswa hanya bisa melihat dan berbagi. Admin kelas yang mengatur jadwal, tugas, dan pengumuman.
                Mengunggah file dan gambar juga hanya bisa dilakukan admin kelas, supaya tidak ada yang iseng
                atau mengirim spam. Kategori kelompok bisa dikunci kalau pembagiannya sudah final, jadi tidak ada
                lagi yang mengubah diam-diam.
            </p>
        </div>
    </section>

    <section id="harga" class="bg-[var(--tinta)] text-white">
        <div class="mx-auto w-full max-w-5xl px-5 py-16 sm:px-8 sm:py-20">
            <h2 class="max-w-xl text-3xl font-bold tracking-[-0.03em] sm:text-4xl">Harga per kelas, bukan per mahasiswa</h2>
            <p class="mt-4 max-w-xl text-white/65">
                Satu kali bayar untuk satu semester penuh — enam bulan. Tidak ada biaya per akun, berapa pun
                jumlah mahasiswanya.@if ($tamu && $adaDemo) Belum yakin? <a href="{{ route('demo') }}" class="font-semibold text-white underline underline-offset-4 hover:no-underline">Coba demonya dulu</a>. @endif
            </p>

            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                <div class="relative flex flex-col overflow-hidden rounded-2xl bg-white p-7 text-[var(--tinta)] sm:p-9">
                    <div class="-mx-7 -mt-7 mb-7 bg-[var(--kunyit)] px-7 py-2.5 text-sm font-semibold sm:-mx-9 sm:-mt-9 sm:px-9">
                        Promo langganan pertama
                    </div>
                    <p class="text-base font-semibold">Paket Kelas</p>
                    <p class="angka mt-3 text-5xl font-bold">Rp200.000</p>
                    <p class="mt-1.5 text-sm text-[var(--redup)]">satu semester, 6 bulan</p>

                    <ul class="mt-7 mb-8 space-y-3 text-[15px]">
                        <li class="flex gap-3"><x-heroicon-m-check class="mt-1 size-4 shrink-0 text-[#1f7a63]" /> Semua menu akademik, jadwal, dan pengumuman</li>
                        <li class="flex gap-3"><x-heroicon-m-check class="mt-1 size-4 shrink-0 text-[#1f7a63]" /> Akun untuk seluruh mahasiswa dan admin kelas</li>
                        <li class="flex gap-3"><x-heroicon-m-check class="mt-1 size-4 shrink-0 text-[#1f7a63]" /> Export Excel dan bagikan ke WhatsApp</li>
                        <li class="flex gap-3"><x-heroicon-m-check class="mt-1 size-4 shrink-0 text-[#1f7a63]" /> <span><strong class="font-semibold">Khusus langganan pertama:</strong> unggah berkas ikut aktif, tanpa tambahan biaya</span></li>
                        <li class="flex gap-3 text-[var(--redup)]"><x-heroicon-m-minus-small class="mt-1 size-4 shrink-0" /> Perpanjangan berikutnya tanpa unggah berkas — lampiran lewat tautan Drive</li>
                    </ul>

                    <a href="{{ $wa }}" target="_blank" rel="noopener"
                       class="mt-auto flex w-full items-center justify-center gap-2 rounded-xl bg-[var(--tinta)] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#0f1a2e]">
                        <x-ui.whatsapp-icon class="size-4" />
                        Ambil promo ini
                    </a>
                </div>

                <div class="flex flex-col rounded-2xl border border-white/20 p-7 sm:p-9">
                    <p class="text-base font-semibold">Paket Kelas + Berkas</p>
                    <p class="angka mt-3 text-5xl font-bold">Rp250.000</p>
                    <p class="mt-1.5 text-sm text-white/55">satu semester, 6 bulan</p>

                    <ul class="mt-7 mb-8 space-y-3 text-[15px] text-white/85">
                        <li class="flex gap-3"><x-heroicon-m-check class="mt-1 size-4 shrink-0 text-[var(--kunyit)]" /> Semua isi Paket Kelas</li>
                        <li class="flex gap-3"><x-heroicon-m-check class="mt-1 size-4 shrink-0 text-[var(--kunyit)]" /> Unggah lampiran langsung di pengumuman</li>
                        <li class="flex gap-3"><x-heroicon-m-check class="mt-1 size-4 shrink-0 text-[var(--kunyit)]" /> Berkas hanya bisa dibuka anggota kelas</li>
                        <li class="flex gap-3"><x-heroicon-m-check class="mt-1 size-4 shrink-0 text-[var(--kunyit)]" /> Gambar tampil langsung, PDF bisa dipratinjau</li>
                    </ul>

                    <a href="{{ $wa }}" target="_blank" rel="noopener"
                       class="mt-auto flex w-full items-center justify-center gap-2 rounded-xl border border-white/30 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        <x-ui.whatsapp-icon class="size-4" />
                        Langganan paket ini
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-5xl px-5 py-16 sm:px-8 sm:py-20">
        <div class="grid gap-10 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
            <div>
                <h2 class="text-3xl font-bold tracking-[-0.03em] sm:text-4xl">Masih ragu? Tanya dulu</h2>
                <p class="mt-4 max-w-lg text-[var(--redup)]">
                    Ceritakan kelas kamu — berapa mahasiswanya, berapa mata kuliah semester ini. Kami bantu
                    siapkan kelasnya, termasuk memasukkan data mahasiswa dari file Excel.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:items-end">
                <a href="{{ $wa }}" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-[#1f7a63] px-6 py-3.5 font-semibold text-white transition hover:bg-[#1a6653]">
                    <x-ui.whatsapp-icon class="size-5" />
                    +62 812790106175
                </a>
                <a href="mailto:{{ $email }}" class="inline-flex items-center justify-center gap-2.5 rounded-xl border border-[var(--garis)] bg-white px-6 py-3.5 font-semibold transition hover:bg-[var(--kertas)]">
                    <x-heroicon-o-envelope class="size-5 text-[var(--redup)]" />
                    {{ $email }}
                </a>
            </div>
        </div>
    </section>
</main>

<footer class="border-t border-[var(--garis)]">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-3 px-5 py-8 text-sm text-[var(--redup)] sm:flex-row sm:items-center sm:px-8">
        <p>&copy; {{ date('Y') }} Kelas KampusKu</p>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 sm:ml-auto">
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="transition hover:text-[var(--tinta)]">WhatsApp 62812790106175</a>
            <a href="mailto:{{ $email }}" class="transition hover:text-[var(--tinta)]">{{ $email }}</a>
            <a href="{{ route('login') }}" class="transition hover:text-[var(--tinta)]">Masuk ke aplikasi</a>
        </div>
    </div>
</footer>

<script>
    (() => {
        const rupiah = new Intl.NumberFormat('id-ID');
        const geser = document.getElementById('jumlah');
        const jumlahTampil = document.getElementById('jumlah-tampil');
        const hargaTampil = document.getElementById('harga-terpilih');
        const perOrang = document.getElementById('per-orang');
        const paket = [...document.querySelectorAll('[data-paket]')];

        const hitung = () => {
            const harga = Number(paket.find((pilihan) => pilihan.checked)?.value ?? 250000);
            const jumlah = Number(geser.value);

            jumlahTampil.textContent = jumlah;
            hargaTampil.textContent = 'Rp' + rupiah.format(harga);
            perOrang.textContent = 'Rp' + rupiah.format(Math.ceil(harga / jumlah));
        };

        geser.addEventListener('input', hitung);
        paket.forEach((pilihan) => pilihan.addEventListener('change', hitung));
        hitung();
    })();
</script>

</body>
</html>
