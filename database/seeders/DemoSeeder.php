<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Informasi;
use App\Models\JadwalKelas;
use App\Models\KategoriInformasi;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\MataKuliah;
use App\Models\Semester;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sample data so the app is usable straight after `migrate --seed`.
 * Default password for every account: "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Kelas::query()->exists()) {
            return;
        }

        $semester = fn (int $nomor) => Semester::query()->where('nomor', $nomor)->value('id');

        $kelasA = Kelas::query()->create([
            'nama' => 'TI-3A',
            'prodi' => 'Teknik Informatika',
            'angkatan' => 2024,
            'semester_aktif_id' => $semester(3),
        ]);

        $kelasB = Kelas::query()->create([
            'nama' => 'TI-1B',
            'prodi' => 'Teknik Informatika',
            'angkatan' => 2025,
            'semester_aktif_id' => $semester(1),
        ]);

        $admin = User::query()->create([
            'npm' => '24010001',
            'name' => 'Rizky Pratama',
            'no_hp' => '6281234567890',
            'password' => 'password',
            'role' => Role::Admin,
            'kelas_id' => $kelasA->id,
        ]);

        $mahasiswa = User::query()->create([
            'npm' => '24010002',
            'name' => 'Siti Rahmawati',
            'no_hp' => '6285712345678',
            'password' => 'password',
            'role' => Role::Mahasiswa,
            'kelas_id' => $kelasA->id,
        ]);

        $mahasiswaLain = User::factory()->count(18)->create(['kelas_id' => $kelasA->id]);

        User::query()->create([
            'npm' => '25010001',
            'name' => 'Budi Santoso',
            'no_hp' => '6281398765432',
            'password' => 'password',
            'role' => Role::Admin,
            'kelas_id' => $kelasB->id,
        ]);
        User::factory()->count(10)->create(['kelas_id' => $kelasB->id]);

        $mataKuliahA = collect([
            ['kode' => 'IF301', 'nama' => 'Pemrograman Web', 'dosen' => 'Dr. Andi Wijaya, M.Kom.', 'sks' => 3, 'hari' => 1, 'jam' => '08:00', 'ruangan' => 'Lab 2'],
            ['kode' => 'IF302', 'nama' => 'Basis Data', 'dosen' => 'Ir. Dewi Lestari, M.T.', 'sks' => 3, 'hari' => 2, 'jam' => '10:00', 'ruangan' => 'R.301'],
            ['kode' => 'IF303', 'nama' => 'Struktur Data', 'dosen' => 'Fajar Nugroho, M.Kom.', 'sks' => 3, 'hari' => 3, 'jam' => '13:00', 'ruangan' => 'R.204'],
            ['kode' => 'IF304', 'nama' => 'Jaringan Komputer', 'dosen' => 'Dr. Hendra Kusuma', 'sks' => 2, 'hari' => 4, 'jam' => '08:00', 'ruangan' => 'Lab 1'],
            ['kode' => 'IF305', 'nama' => 'Matematika Diskrit', 'dosen' => 'Ratna Sari, M.Si.', 'sks' => 2, 'hari' => 5, 'jam' => '09:40', 'ruangan' => 'R.105'],
        ])->map(function (array $data) use ($kelasA, $semester) {
            $mataKuliah = MataKuliah::query()->create([
                'kelas_id' => $kelasA->id,
                'semester_id' => $semester(3),
                'kode' => $data['kode'],
                'nama' => $data['nama'],
                'dosen' => $data['dosen'],
                'sks' => $data['sks'],
            ]);

            [$jam, $menit] = explode(':', $data['jam']);
            $mulai = now()->setTime((int) $jam, (int) $menit);

            JadwalKelas::query()->create([
                'mata_kuliah_id' => $mataKuliah->id,
                'hari' => $data['hari'],
                'jam_mulai' => $mulai->format('H:i'),
                'jam_selesai' => $mulai->copy()->addMinutes(50 * $data['sks'])->format('H:i'),
                'ruangan' => $data['ruangan'],
            ]);

            return $mataKuliah;
        });

        // Make sure today always has something on the schedule for the demo.
        JadwalKelas::query()->create([
            'mata_kuliah_id' => $mataKuliahA->first()->id,
            'hari' => now()->dayOfWeekIso,
            'jam_mulai' => '15:30',
            'jam_selesai' => '17:10',
            'ruangan' => 'Lab 3',
        ]);

        // Mata kuliah from a previous semester (should not appear in the active semester views).
        MataKuliah::query()->create([
            'kelas_id' => $kelasA->id,
            'semester_id' => $semester(2),
            'kode' => 'IF201',
            'nama' => 'Algoritma dan Pemrograman',
            'dosen' => 'Fajar Nugroho, M.Kom.',
            'sks' => 3,
        ]);

        MataKuliah::query()->create([
            'kelas_id' => $kelasB->id,
            'semester_id' => $semester(1),
            'kode' => 'IF101',
            'nama' => 'Pengantar Teknologi Informasi',
            'dosen' => 'Dr. Andi Wijaya, M.Kom.',
            'sks' => 2,
        ]);

        $mataKuliahA->each(function (MataKuliah $mataKuliah, int $index) use ($admin) {
            Tugas::factory()->count(2)->create([
                'mata_kuliah_id' => $mataKuliah->id,
                'created_by' => $admin->id,
            ]);

            if ($index % 2 === 0) {
                Tugas::factory()->lewat()->create([
                    'mata_kuliah_id' => $mataKuliah->id,
                    'created_by' => $admin->id,
                ]);
            }
        });

        $kategori = collect([
            ['nama' => 'Pengumuman', 'warna' => 'orange'],
            ['nama' => 'Akademik', 'warna' => 'emerald'],
            ['nama' => 'Kegiatan', 'warna' => 'amber'],
            ['nama' => 'Keuangan', 'warna' => 'rose'],
        ])->map(fn (array $data) => KategoriInformasi::query()->create([...$data, 'kelas_id' => $kelasA->id]));

        Informasi::query()->create([
            'kelas_id' => $kelasA->id,
            'kategori_informasi_id' => $kategori[0]->id,
            'judul' => 'Selamat datang di Manajemen Kelas TI-3A',
            'isi' => "Gunakan aplikasi ini untuk memantau tugas, jadwal, dan informasi kelas.\n\nJika ada pertanyaan, hubungi admin kelas.",
            'is_pinned' => true,
            'created_by' => $admin->id,
        ]);

        Informasi::factory()->count(7)->create([
            'kelas_id' => $kelasA->id,
            'kategori_informasi_id' => fn () => $kategori->random()->id,
            'created_by' => fn () => fake()->randomElement([$admin->id, $mahasiswa->id]),
        ]);

        KategoriInformasi::query()->create(['kelas_id' => $kelasB->id, 'nama' => 'Pengumuman', 'warna' => 'orange']);

        $anggota = $mahasiswaLain->push($mahasiswa)->shuffle();

        $anggota->chunk(5)->values()->each(function ($chunk, int $index) use ($mataKuliahA, $mahasiswa) {
            $kelompok = Kelompok::query()->create([
                'mata_kuliah_id' => $mataKuliahA[0]->id,
                'nama' => 'Kelompok '.($index + 1),
                'deskripsi' => 'Kelompok project akhir Pemrograman Web.',
                'created_by' => $mahasiswa->id,
            ]);

            $kelompok->anggota()->attach(
                $chunk->values()->mapWithKeys(fn (User $user, int $i) => [$user->id => ['is_ketua' => $i === 0]])->all()
            );
        });
    }
}
