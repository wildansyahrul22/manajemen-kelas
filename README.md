# Kelas KampusKu

Aplikasi manajemen kelas perkuliahan: dashboard, daftar tugas, jadwal kelas, jadwal lab (praktikum), mata kuliah, kelompok (per kategori), informasi kelas, manajemen user/kelas, pengaturan semester aktif, dan log aktivitas. Hampir semua daftar bisa diekspor ke Excel.

Halaman depan (`/`) adalah landing page penawaran langganan; aplikasinya sendiri ada di `/login` (pengguna yang sudah masuk langsung diarahkan ke dashboard).

Dibangun dengan **Laravel 13**, **Livewire 4**, **Tailwind CSS 4**, dan **MySQL** (MAMP).

## Menjalankan di lokal (MAMP)

1. Pastikan MAMP (MySQL) sudah berjalan — default port `8889`, user `root`, password `root`.
2. Buat database `manajemen_kelas` (phpMyAdmin atau CLI MAMP):
   ```sh
   /Applications/MAMP/Library/bin/mysql80/bin/mysql -h 127.0.0.1 -P 8889 -u root -proot -e "CREATE DATABASE IF NOT EXISTS manajemen_kelas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```
3. Instal dependensi & siapkan aplikasi:
   ```sh
   composer install
   cp .env.example .env        # lewati jika .env sudah ada
   php artisan key:generate
   php artisan migrate --seed  # membuat tabel + data semester 1-14 dan akun super admin
   npm install
   npm run build               # atau `npm run dev` saat mengembangkan
   composer run serve          # = php artisan serve + batas unggah dari public/.user.ini
   ```
4. Buka <http://localhost:8000>.

> `php artisan serve` biasa memakai php.ini CLI (bawaan Homebrew: `upload_max_filesize = 2M`) dan **mengabaikan** `public/.user.ini`, sehingga lampiran > 2 MB gagal diunggah. `composer run serve` / `composer run dev` menambahkan `PHP_INI_SCAN_DIR=:public` agar batas 3M/8M ikut terbaca. Alternatif: naikkan `upload_max_filesize` dan `post_max_size` di php.ini CLI.

Pengaturan koneksi database ada di `.env` (`DB_HOST=127.0.0.1`, `DB_PORT=8889`, `DB_DATABASE=manajemen_kelas`, `DB_USERNAME=root`, `DB_PASSWORD=root`).

## Akun awal

| Role        | NPM          | Password   |
|-------------|--------------|------------|
| Super Admin | `superadmin` | `password` |

Segera ganti password lewat menu **Profil Saya** setelah login pertama. Sesi login berlaku 2 hari sejak aktivitas terakhir (`SESSION_LIFETIME=2880`); dengan **Ingat saya**, login bertahan hingga 30 hari. Kelas dan akun lain dibuat dari menu **Kelas** dan **Users**.

Data contoh (2 kelas, mahasiswa, mata kuliah, jadwal, jadwal lab, tugas, informasi, kelompok) bersifat opsional:

```sh
php artisan db:seed --class=DemoSeeder   # hanya berjalan jika belum ada kelas
```

Akun demo yang dibuat (password `password`): `24010001` admin TI-3A, `24010002` mahasiswa TI-3A, `25010001` admin TI-1B.

## Hak akses

| Fitur                                   | Mahasiswa | Admin Kelas       | Super Admin |
|-----------------------------------------|-----------|-------------------|-------------|
| Dashboard, Daftar Tugas, Jadwal, Jadwal Lab, Mata Kuliah | Lihat | CRUD (kelasnya) | CRUD (semua) |
| Informasi & Kategori Informasi          | CRUD (edit/hapus hanya data buatan sendiri) | CRUD | CRUD |
| Lampiran file/gambar pada informasi     | —         | Upload (bila paket kelas mengizinkan) | Upload (bila paket kelas mengizinkan) |
| Kategori Kelompok                       | CRUD (edit/hapus hanya data buatan sendiri, selama belum final) | CRUD | CRUD |
| Kelompok                                | CRUD selama kategorinya belum final | CRUD selama kategorinya belum final | CRUD selama kategorinya belum final |
| Tandai kategori kelompok final          | —         | Kelasnya          | Semua kelas |
| Users                                   | —         | Kelasnya          | Semua       |
| Kelas                                   | —         | —                 | CRUD        |
| Semester Aktif                          | —         | Kelasnya          | Semua kelas |
| Log Aktivitas                           | —         | Kelasnya (lihat)  | Semua kelas (filter kelas, hapus) |

Super admin memilih kelas yang sedang dikelola melalui filter kelas di header.

Setiap informasi bisa menyertakan **tautan** (mis. file di Google Drive yang aksesnya dibuka untuk "siapa saja yang memiliki link") — ini cara mahasiswa membagikan gambar/file. Admin kelas dan super admin bisa mengunggah **beberapa lampiran** sekaligus (maks. 2 MB per file, 2 file per informasi — di hosting dijamin oleh `public/.user.ini`: `upload_max_filesize = 3M`, `post_max_size = 8M`; form menampilkan batas efektif server dan menolak file kebesaran sebelum diunggah; disimpan di `storage/app/private/informasi` dan hanya bisa dibuka anggota kelas lewat `/informasi/{id}/lampiran/{lampiran}`).

**Bagikan ke WhatsApp**: tombol/ikon WhatsApp membuka WhatsApp dengan pesan siap kirim (tinggal pilih grup) — per informasi (daftar & detail), per tugas (daftar & detail, termasuk deadline & link pengumpulan), per hari atau seminggu penuh pada Jadwal Kelas, per tanggal atau per mata kuliah pada Jadwal Lab, dan per kategori pada Kelompok (pilih kategori dulu; pesan memuat semua kelompok beserta anggota + NPM dan mahasiswa yang belum masuk kelompok). Teks pesan disusun di `App\Support\PesanWhatsApp`.

**Kelas terbang**: saat menambah/mengedit user, centang *Kelas terbang* lalu pilih semesternya untuk mahasiswa dari kelas lain yang hanya ikut kelas ini pada satu semester. User tersebut hanya dihitung sebagai anggota kelas saat semester itu aktif — di dashboard, pilihan anggota kelompok (dan validasinya), pesan WhatsApp kelompok, jumlah mahasiswa di daftar Kelas/Semester Aktif, serta daftar Users (centang "Tampilkan kelas terbang semester lain" untuk melihat yang lain). Export Users memuat kolom *Keanggotaan* (Reguler / Kelas terbang · Semester N).

**Log Aktivitas** (`activity_log`) mencatat otomatis setiap buat/ubah/hapus pada informasi, kategori, kelompok (termasuk perubahan anggota/ketua), tugas, jadwal, jadwal lab, mata kuliah, user, dan kelas, plus masuk/keluar — lengkap dengan pelaku, kelas, perubahan field (sebelum → sesudah; password disamarkan), dan IP. Admin kelas hanya melihat log kelasnya; super admin melihat semua, bisa memfilter per kelas, dan satu-satunya yang boleh menghapus entri (per entri atau semua yang cocok dengan filter aktif — penghapusan itu sendiri dicatat sebagai entri log baru).

**Export Excel** tersedia di halaman Daftar Tugas, Mata Kuliah, Kelompok, Kategori Kelompok, Jadwal Kelas, Jadwal Lab, Daftar Informasi, Kategori Informasi, Users, dan Kelas. Isinya mengikuti filter yang sedang aktif (pencarian, mata kuliah, status, semester, kategori, role) tanpa dibatasi halaman; file berisi judul, kelas/semester, filter aktif, waktu ekspor, header tebal yang dibekukan, tanggal/jam sebagai nilai tanggal Excel, dan lebar kolom otomatis. Data majemuk tidak pernah digabung dalam satu sel: export Kelompok punya lembar "Anggota" (satu baris per mahasiswa, dengan NPM) dan lembar "Kelompok" (ringkasan per kelompok). Dibangun dengan `phpoffice/phpspreadsheet` lewat helper `App\Support\ExcelExport`.

**Tugas** dapat menyertakan **link pengumpulan** (Google Form, folder Drive, LMS, dll.) yang tampil sebagai tombol *Kumpulkan Tugas* di detail tugas dan penanda *Kumpulkan* di daftar tugas/dashboard, serta bisa ditandai sebagai **tugas kelompok** dengan memilih kategori kelompok dari mata kuliah yang sama — di halaman detail, tiap mahasiswa melihat kelompoknya sendiri (anggota + ketua) atau pemberitahuan bila belum masuk kelompok. Keduanya ikut diekspor ke Excel dan disebut dalam pesan WhatsApp.

**Jadwal Lab** mencatat sesi praktikum per mata kuliah pada tanggal tertentu (tanggal, jam, ruangan, keterangan). Pilih mata kuliah pada form (atau lewat tombol "+" pada kartu mata kuliah yang sudah punya sesi), lalu isi beberapa sesi sekaligus — sesi baru otomatis mengikuti jam & ruangan sesi sebelumnya dengan tanggal seminggu berikutnya. Halaman hanya menampilkan mata kuliah yang sudah memiliki jadwal lab. Sesi hari ini ikut tampil di "Jadwal Hari Ini" pada dashboard.

Kelompok selalu berada di bawah sebuah **kategori kelompok** (misal "Project Akhir" pada mata kuliah Pemrograman Web). Satu mahasiswa hanya bisa tergabung di satu kelompok per kategori, sehingga saat membuat kelompok hanya mahasiswa yang belum punya kelompok pada kategori itu yang ditawarkan.

Selama kategori masih **terbuka**, seluruh anggota kelas boleh menyusun kelompok di dalamnya — membuat, mengubah, maupun menghapus, siapa pun yang membuatnya. Admin kelas (dan super admin) bisa menandainya **final** lewat menu aksi di Kategori Kelompok: setelah itu tidak ada yang bisa mengubah kelompoknya, termasuk admin sendiri, sampai status final dilepas kembali. Kategori yang sudah final juga hanya bisa diedit/dihapus oleh admin kelas.

## Masa aktif & paket kelas

Setiap kelas punya **masa aktif** (rentang tanggal) dan penanda **fitur upload**, keduanya hanya bisa diatur super admin lewat menu Kelas:

- Di luar masa aktif, seluruh akun kelas tersebut tidak bisa masuk — dan sesi yang sedang terbuka ikut dikeluarkan pada permintaan berikutnya. Kosongkan kedua tanggal untuk kelas tanpa batas waktu. Super admin tidak terpengaruh karena tidak terikat kelas.
- Bila fitur upload dimatikan, admin kelas tidak bisa melampirkan file pada informasi; lampiran tetap bisa dibagikan sebagai tautan Google Drive.

## Import mahasiswa dari CSV

Siapkan CSV dengan header `npm,nama` (kolom `no_hp` opsional; format `08xx` otomatis diubah ke `62xx`), lalu:

```sh
php artisan mahasiswa:import path/ke/file.csv --kelas=TI-R8            # password awal = NPM
php artisan mahasiswa:import path/ke/file.csv --kelas=TI-R8 --dry-run  # cek dulu tanpa menyimpan
php artisan mahasiswa:import path/ke/file.csv --kelas=TI-R8 --password=rahasia
```

Kelas harus sudah ada. NPM yang sudah terdaftar atau baris yang tidak valid dilewati dan dilaporkan.

## Deploy

Push ke `main` menjalankan test lalu deploy otomatis lewat `.github/workflows/deploy.yml` (skrip server: `bin/deploy.sh`). Panduan lengkap disimpan terpisah di luar repo.

## Testing

```sh
php artisan test
```
