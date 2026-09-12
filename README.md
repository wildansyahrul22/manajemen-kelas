# Manajemen Kelas

Aplikasi manajemen kelas perkuliahan: dashboard, daftar tugas, jadwal kelas, mata kuliah, kelompok (per kategori), informasi kelas, manajemen user/kelas, dan pengaturan semester aktif.

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
   php artisan serve
   ```
4. Buka <http://localhost:8000>.

Pengaturan koneksi database ada di `.env` (`DB_HOST=127.0.0.1`, `DB_PORT=8889`, `DB_DATABASE=manajemen_kelas`, `DB_USERNAME=root`, `DB_PASSWORD=root`).

## Akun awal

| Role        | NPM          | Password   |
|-------------|--------------|------------|
| Super Admin | `superadmin` | `password` |

Segera ganti password lewat menu **Profil Saya** setelah login pertama. Kelas dan akun lain dibuat dari menu **Kelas** dan **Users**.

Data contoh (2 kelas, mahasiswa, mata kuliah, jadwal, tugas, informasi, kelompok) bersifat opsional:

```sh
php artisan db:seed --class=DemoSeeder   # hanya berjalan jika belum ada kelas
```

Akun demo yang dibuat (password `password`): `24010001` admin TI-3A, `24010002` mahasiswa TI-3A, `25010001` admin TI-1B.

## Hak akses

| Fitur                                   | Mahasiswa | Admin Kelas       | Super Admin |
|-----------------------------------------|-----------|-------------------|-------------|
| Dashboard, Daftar Tugas, Jadwal, Mata Kuliah | Lihat | CRUD (kelasnya) | CRUD (semua) |
| Informasi & Kategori Informasi          | CRUD (edit/hapus informasi milik sendiri) | CRUD | CRUD |
| Kelompok & Kategori Kelompok            | CRUD (edit/hapus kelompok buatan sendiri) | CRUD | CRUD |
| Users                                   | —         | Kelasnya          | Semua       |
| Kelas                                   | —         | —                 | CRUD        |
| Semester Aktif                          | —         | Kelasnya          | Semua kelas |

Super admin memilih kelas yang sedang dikelola melalui filter kelas di header.

Kelompok selalu berada di bawah sebuah **kategori kelompok** (misal "Project Akhir" pada mata kuliah Pemrograman Web). Satu mahasiswa hanya bisa tergabung di satu kelompok per kategori, sehingga saat membuat kelompok hanya mahasiswa yang belum punya kelompok pada kategori itu yang ditawarkan.

## Import mahasiswa dari CSV

Siapkan CSV dengan header `npm,nama` (kolom `no_hp` opsional; format `08xx` otomatis diubah ke `62xx`), lalu:

```sh
php artisan mahasiswa:import path/ke/file.csv --kelas=TI-R8            # password awal = NPM
php artisan mahasiswa:import path/ke/file.csv --kelas=TI-R8 --dry-run  # cek dulu tanpa menyimpan
php artisan mahasiswa:import path/ke/file.csv --kelas=TI-R8 --password=rahasia
```

Kelas harus sudah ada. NPM yang sudah terdaftar atau baris yang tidak valid dilewati dan dilaporkan.

## Deploy (CI/CD)

Setiap push ke `main` menjalankan workflow `.github/workflows/deploy.yml`: test dijalankan dulu, lalu jika lulus GitHub Actions masuk ke server lewat SSH dan menjalankan `bin/deploy.sh` (`git reset --hard origin/main` → `composer install --no-dev` → `migrate --force` → `optimize`). Situs production: <https://manajemen-kelas.web.id>.

Secrets yang dibutuhkan di repo: `SSH_HOST`, `SSH_PORT`, `SSH_USER`, `SSH_PRIVATE_KEY`. Asset Vite di-commit (`public/build`) karena server tidak memiliki Node.

Di server, project berada di `~/public_html/manajemen-kelas.web.id` (folder addon domain) dengan document root di subfolder `public/`; root project diblokir dari web oleh `.htaccess`. Deploy manual dari server: `bash ~/public_html/manajemen-kelas.web.id/bin/deploy.sh`.

## Testing

```sh
php artisan test
```
