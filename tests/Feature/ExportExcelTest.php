<?php

namespace Tests\Feature;

use App\Livewire\Informasi\Index as InformasiIndex;
use App\Livewire\Jadwal\Index as JadwalIndex;
use App\Livewire\JadwalLab\Index as JadwalLabIndex;
use App\Livewire\KategoriInformasi\Index as KategoriInformasiIndex;
use App\Livewire\KategoriKelompok\Index as KategoriKelompokIndex;
use App\Livewire\Kelas\Index as KelasIndex;
use App\Livewire\Kelompok\Index as KelompokIndex;
use App\Livewire\MataKuliah\Index as MataKuliahIndex;
use App\Livewire\Tugas\Index as TugasIndex;
use App\Livewire\Users\Index as UsersIndex;
use App\Models\Informasi;
use App\Models\InformasiLampiran;
use App\Models\JadwalKelas;
use App\Models\JadwalLab;
use App\Models\KategoriInformasi;
use App\Models\KategoriKelompok;
use App\Models\Kelompok;
use App\Models\MataKuliah;
use App\Models\Tugas;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ExportExcelTest extends TestCase
{
    public function test_tugas_export_follows_the_active_filters_and_is_formatted(): void
    {
        $this->travelTo(Carbon::create(2026, 9, 15, 9, 0));

        $kelas = $this->kelas();
        $admin = $this->admin($kelas, ['name' => 'Rizky']);
        $web = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web', 'dosen' => 'Dr. Andi']);
        $basisData = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Basis Data']);
        $kategori = KategoriKelompok::factory()->create(['mata_kuliah_id' => $web->id, 'nama' => 'Project Akhir']);
        Tugas::factory()->create(['mata_kuliah_id' => $web->id, 'kategori_kelompok_id' => $kategori->id, 'nama' => 'Laporan 1', 'deskripsi' => "Baris satu\nBaris dua", 'deadline' => '2026-09-20 23:59:00', 'link_pengumpulan' => 'https://forms.gle/abc', 'created_by' => $admin->id]);
        Tugas::factory()->create(['mata_kuliah_id' => $web->id, 'nama' => '=SUM(1)', 'deskripsi' => null, 'deadline' => '2026-09-16 12:00:00', 'created_by' => $admin->id]);
        Tugas::factory()->lewat()->create(['mata_kuliah_id' => $web->id, 'nama' => 'Tugas Lewat', 'created_by' => $admin->id]);
        Tugas::factory()->create(['mata_kuliah_id' => $basisData->id, 'nama' => 'Tugas Basis Data', 'created_by' => $admin->id]);

        $component = Livewire::actingAs($admin)
            ->test(TugasIndex::class)
            ->set('mataKuliahId', (string) $web->id)
            ->set('status', 'aktif')
            ->call('export')
            ->assertFileDownloaded('daftar-tugas-'.strtolower($kelas->nama).'_2026-09-15.xlsx', contentType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $sheet = $this->downloadedSpreadsheet($component)->getSheet(0);
        $rows = $sheet->toArray(null, true, true, false);

        $this->assertSame('Daftar Tugas', $rows[0][0]);
        $this->assertSame('Kelas '.$kelas->nama.' · Semester 3', $rows[1][0]);
        $this->assertSame('Filter: Mata kuliah = Pemrograman Web · Status = Belum deadline', $rows[2][0]);
        $this->assertSame('Diekspor: 15 Sep 2026 09:00 oleh Rizky', $rows[3][0]);
        $this->assertSame(['No', 'Nama Tugas', 'Mata Kuliah', 'Dosen', 'Deadline', 'Status', 'Tugas Kelompok (Kategori)', 'Link Pengumpulan', 'Deskripsi', 'Dibuat Oleh', 'Dibuat Pada'], $rows[5]);
        $this->assertSame(['1', '=SUM(1)', 'Pemrograman Web', 'Dr. Andi', '16 Sep 2026 12:00', 'Segera', null, null, null, 'Rizky', '15 Sep 2026 09:00'], $rows[6]);
        $this->assertSame(['2', 'Laporan 1', 'Pemrograman Web', 'Dr. Andi', '20 Sep 2026 23:59', 'Aktif', 'Project Akhir', 'https://forms.gle/abc', "Baris satu\nBaris dua", 'Rizky', '15 Sep 2026 09:00'], $rows[7]);
        $this->assertCount(8, $rows);

        $this->assertSame('n', $sheet->getCell('A7')->getDataType());
        $this->assertSame('s', $sheet->getCell('B7')->getDataType());
        $this->assertSame('n', $sheet->getCell('E7')->getDataType());
        $this->assertSame('A7', $sheet->getFreezePane());
        $this->assertSame('', $sheet->getAutoFilter()->getRange());
        $this->assertTrue($sheet->getStyle('A6')->getFont()->getBold());
        $this->assertSame('FF0F172A', $sheet->getStyle('A6')->getFill()->getStartColor()->getARGB());
        $this->assertSame(50.0, $sheet->getColumnDimension('I')->getWidth());
        $this->assertTrue($sheet->getStyle('I7')->getAlignment()->getWrapText());
    }

    public function test_export_without_data_says_so_instead_of_an_empty_table(): void
    {
        $kelas = $this->kelas();

        $component = Livewire::actingAs($this->mahasiswa($kelas))
            ->test(TugasIndex::class)
            ->set('search', 'tidak ada')
            ->call('export')
            ->assertFileDownloaded();

        $rows = $this->downloadedSpreadsheet($component)->getSheet(0)->toArray(null, true, true, false);

        $this->assertSame('Filter: Pencarian = tidak ada', $rows[2][0]);
        $this->assertSame('Tidak ada data.', $rows[6][0]);
    }

    public function test_mata_kuliah_export_uses_the_viewed_semester(): void
    {
        $kelas = $this->kelas(semester: 3);
        MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Mata Kuliah Aktif']);
        $lama = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'semester_id' => 2, 'kode' => 'IF201', 'nama' => 'Algoritma', 'dosen' => 'Fajar', 'sks' => 3]);
        JadwalKelas::factory()->count(2)->create(['mata_kuliah_id' => $lama->id]);
        JadwalLab::factory()->create(['mata_kuliah_id' => $lama->id]);

        $component = Livewire::actingAs($this->mahasiswa($kelas))
            ->test(MataKuliahIndex::class)
            ->set('semesterId', '2')
            ->call('export')
            ->assertFileDownloaded();

        $rows = $this->downloadedSpreadsheet($component)->getSheet(0)->toArray(null, true, true, false);

        $this->assertSame('Filter: Semester = Semester 2', $rows[2][0]);
        $this->assertSame(['1', 'IF201', 'Algoritma', 'Fajar', '3', 'Semester 2', '2', '1', '0'], $rows[6]);
        $this->assertCount(7, $rows);
    }

    public function test_kelompok_export_lists_one_anggota_per_row_with_npm_and_never_joins_names(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web']);
        $kategori = KategoriKelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'nama' => 'Project Akhir']);
        $ketua = $this->mahasiswa($kelas, ['npm' => '24010001', 'name' => 'Siti']);
        $anggota = $this->mahasiswa($kelas, ['npm' => '24010002', 'name' => 'Budi']);
        $kelompok = Kelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'kategori_kelompok_id' => $kategori->id, 'nama' => 'Kelompok 1', 'deskripsi' => 'Project akhir web.']);
        $kelompok->anggota()->attach([$ketua->id => ['is_ketua' => true], $anggota->id => ['is_ketua' => false]]);
        Kelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'kategori_kelompok_id' => $kategori->id, 'nama' => 'Kelompok Kosong', 'deskripsi' => null]);

        $component = Livewire::actingAs($ketua)
            ->test(KelompokIndex::class)
            ->set('kategoriId', (string) $kategori->id)
            ->call('export')
            ->assertFileDownloaded();

        $spreadsheet = $this->downloadedSpreadsheet($component);
        $this->assertSame(['Anggota', 'Kelompok'], $spreadsheet->getSheetNames());

        $daftarAnggota = $spreadsheet->getSheetByName('Anggota')->toArray(null, true, true, false);
        $this->assertSame('Filter: Kategori = Project Akhir · Pemrograman Web', $daftarAnggota[2][0]);
        $this->assertSame(['No', 'Mata Kuliah', 'Kategori', 'Kelompok', 'NPM', 'Nama', 'Peran'], $daftarAnggota[5]);
        $this->assertSame(['1', 'Pemrograman Web', 'Project Akhir', 'Kelompok 1', '24010001', 'Siti', 'Ketua'], $daftarAnggota[6]);
        $this->assertSame(['2', 'Pemrograman Web', 'Project Akhir', 'Kelompok 1', '24010002', 'Budi', 'Anggota'], $daftarAnggota[7]);
        $this->assertCount(8, $daftarAnggota);

        $ringkasan = $spreadsheet->getSheetByName('Kelompok')->toArray(null, true, true, false);
        $this->assertSame(['No', 'Mata Kuliah', 'Kategori', 'Nama Kelompok', 'NPM Ketua', 'Nama Ketua', 'Jumlah Anggota', 'Deskripsi'], $ringkasan[5]);
        $this->assertSame(['1', 'Pemrograman Web', 'Project Akhir', 'Kelompok 1', '24010001', 'Siti', '2', 'Project akhir web.'], $ringkasan[6]);
        $this->assertSame(['2', 'Pemrograman Web', 'Project Akhir', 'Kelompok Kosong', null, null, '0', null], $ringkasan[7]);
        $this->assertStringNotContainsString('Siti, Budi', json_encode($ringkasan));
    }

    public function test_kategori_kelompok_export_lists_kategori_of_the_filtered_mata_kuliah(): void
    {
        $kelas = $this->kelas();
        $web = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web']);
        $lain = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Basis Data']);
        $kategori = KategoriKelompok::factory()->create(['mata_kuliah_id' => $web->id, 'nama' => 'Project Akhir']);
        KategoriKelompok::factory()->create(['mata_kuliah_id' => $lain->id, 'nama' => 'Presentasi']);
        Kelompok::factory()->count(3)->create(['mata_kuliah_id' => $web->id, 'kategori_kelompok_id' => $kategori->id]);

        $component = Livewire::actingAs($this->mahasiswa($kelas))
            ->test(KategoriKelompokIndex::class)
            ->set('mataKuliahId', (string) $web->id)
            ->call('export')
            ->assertFileDownloaded();

        $rows = $this->downloadedSpreadsheet($component)->getSheet(0)->toArray(null, true, true, false);

        $this->assertSame('Filter: Mata kuliah = Pemrograman Web', $rows[2][0]);
        $this->assertSame(['Pemrograman Web', 'Project Akhir', 'Terbuka', '3'], array_slice($rows[6], 1, 4));
        $this->assertCount(7, $rows);
    }

    public function test_jadwal_kelas_export_lists_the_week_in_order(): void
    {
        $kelas = $this->kelas();
        $web = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web', 'kode' => 'IF301', 'dosen' => 'Dr. Andi']);
        JadwalKelas::factory()->create(['mata_kuliah_id' => $web->id, 'hari' => 3, 'jam_mulai' => '13:00', 'jam_selesai' => '14:40', 'ruangan' => 'R.204']);
        JadwalKelas::factory()->create(['mata_kuliah_id' => $web->id, 'hari' => 1, 'jam_mulai' => '08:00', 'jam_selesai' => '09:40', 'ruangan' => null]);

        $component = Livewire::actingAs($this->mahasiswa($kelas))
            ->test(JadwalIndex::class)
            ->call('export')
            ->assertFileDownloaded();

        $rows = $this->downloadedSpreadsheet($component)->getSheet(0)->toArray(null, true, true, false);

        $this->assertSame('Filter: Semua data (tanpa filter)', $rows[2][0]);
        $this->assertSame(['1', 'Senin', '08:00', '09:40', 'Pemrograman Web', 'IF301', 'Dr. Andi', null], $rows[6]);
        $this->assertSame(['2', 'Rabu', '13:00', '14:40', 'Pemrograman Web', 'IF301', 'Dr. Andi', 'R.204'], $rows[7]);
    }

    public function test_jadwal_lab_export_is_chronological_and_follows_the_status_filter(): void
    {
        $this->travelTo(Carbon::create(2026, 9, 15, 9, 0));

        $kelas = $this->kelas();
        $web = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web', 'dosen' => 'Dr. Andi']);
        $basisData = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Basis Data', 'dosen' => 'Dewi']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $web->id, 'tanggal' => '2026-09-08', 'keterangan' => 'Lewat']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $web->id, 'tanggal' => '2026-09-22', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'ruangan' => 'Lab 2', 'keterangan' => 'Routing']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $basisData->id, 'tanggal' => '2026-09-15', 'jam_mulai' => '10:00', 'jam_selesai' => '12:00', 'ruangan' => 'Lab 1', 'keterangan' => null]);

        $component = Livewire::actingAs($this->mahasiswa($kelas))
            ->test(JadwalLabIndex::class)
            ->set('status', 'mendatang')
            ->call('export')
            ->assertFileDownloaded();

        $rows = $this->downloadedSpreadsheet($component)->getSheet(0)->toArray(null, true, true, false);

        $this->assertSame('Filter: Status = Mendatang', $rows[2][0]);
        $this->assertSame(['1', '15 Sep 2026', 'Selasa', '10:00', '12:00', 'Basis Data', 'Dewi', 'Lab 1', null, 'Hari ini'], $rows[6]);
        $this->assertSame(['2', '22 Sep 2026', 'Selasa', '13:00', '15:00', 'Pemrograman Web', 'Dr. Andi', 'Lab 2', 'Routing', 'Mendatang'], $rows[7]);
        $this->assertCount(8, $rows);
    }

    public function test_informasi_export_follows_kategori_filter_and_includes_link_and_lampiran(): void
    {
        $kelas = $this->kelas();
        $penulis = $this->mahasiswa($kelas, ['name' => 'Siti']);
        $pengumuman = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pengumuman']);
        $lain = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Kegiatan']);
        $kuis = Informasi::factory()->create(['kelas_id' => $kelas->id, 'kategori_informasi_id' => $pengumuman->id, 'judul' => 'Kuis Bab 3', 'isi' => 'Siapkan materi.', 'link' => 'https://drive.google.com/x', 'is_pinned' => true, 'created_by' => $penulis->id]);
        InformasiLampiran::factory()->create(['informasi_id' => $kuis->id, 'nama' => 'materi.pdf', 'ukuran' => 2048]);
        InformasiLampiran::factory()->create(['informasi_id' => $kuis->id, 'nama' => 'contoh-soal.png', 'ukuran' => 1024 * 1024]);
        Informasi::factory()->create(['kelas_id' => $kelas->id, 'kategori_informasi_id' => $lain->id, 'judul' => 'Lomba', 'created_by' => $penulis->id]);

        $component = Livewire::actingAs($penulis)
            ->test(InformasiIndex::class)
            ->set('kategoriId', (string) $pengumuman->id)
            ->call('export')
            ->assertFileDownloaded();

        $spreadsheet = $this->downloadedSpreadsheet($component);
        $this->assertSame(['Informasi', 'Lampiran'], $spreadsheet->getSheetNames());

        $rows = $spreadsheet->getSheetByName('Informasi')->toArray(null, true, true, false);
        $this->assertSame('Filter: Kategori = Pengumuman', $rows[2][0]);
        $this->assertSame(['Kuis Bab 3', 'Pengumuman', 'Siapkan materi.', 'https://drive.google.com/x', '2', 'Ya', 'Siti'], array_slice($rows[6], 1, 7));
        $this->assertCount(7, $rows);

        $lampiran = $spreadsheet->getSheetByName('Lampiran')->toArray(null, true, true, false);
        $this->assertSame(['No', 'Judul Informasi', 'Kategori', 'Nama File', 'Ukuran'], $lampiran[5]);
        $this->assertSame(['1', 'Kuis Bab 3', 'Pengumuman', 'materi.pdf', '2.0 KB'], $lampiran[6]);
        $this->assertSame(['2', 'Kuis Bab 3', 'Pengumuman', 'contoh-soal.png', '1.0 MB'], $lampiran[7]);
    }

    public function test_kategori_informasi_export_counts_informasi_per_kategori(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pengumuman', 'warna' => 'orange']);
        KategoriInformasi::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Kegiatan']);
        Informasi::factory()->count(2)->create(['kelas_id' => $kelas->id, 'kategori_informasi_id' => $kategori->id]);

        $component = Livewire::actingAs($this->mahasiswa($kelas))
            ->test(KategoriInformasiIndex::class)
            ->set('search', 'pengu')
            ->call('export')
            ->assertFileDownloaded();

        $rows = $this->downloadedSpreadsheet($component)->getSheet(0)->toArray(null, true, true, false);

        $this->assertSame('Filter: Pencarian = pengu', $rows[2][0]);
        $this->assertSame(['Pengumuman', 'Orange', '2'], array_slice($rows[6], 1, 3));
        $this->assertCount(7, $rows);
    }

    public function test_users_export_has_npm_and_follows_role_filter_within_own_kelas(): void
    {
        $kelas = $this->kelas();
        $admin = $this->admin($kelas, ['npm' => '24010001', 'name' => 'Rizky', 'no_hp' => '6281234567890']);
        $this->mahasiswa($kelas, ['npm' => '24010002', 'name' => 'Siti', 'no_hp' => null]);
        $this->mahasiswa($this->kelas(), ['npm' => '25010001', 'name' => 'Kelas Lain']);

        $component = Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->set('role', 'mahasiswa')
            ->call('export')
            ->assertFileDownloaded('users-'.strtolower($kelas->nama).'_'.now()->format('Y-m-d').'.xlsx');

        $rows = $this->downloadedSpreadsheet($component)->getSheet(0)->toArray(null, true, true, false);

        $this->assertSame('Kelas '.$kelas->nama, $rows[1][0]);
        $this->assertSame('Filter: Role = Mahasiswa · Keanggotaan = Anggota Semester 3', $rows[2][0]);
        $this->assertSame(['No', 'NPM', 'Nama', 'No. HP', 'Role', 'Kelas', 'Keanggotaan', 'Terdaftar Pada'], $rows[5]);
        $this->assertSame(['1', '24010002', 'Siti', null, 'Mahasiswa', $kelas->nama, 'Reguler'], array_slice($rows[6], 0, 7));
        $this->assertCount(7, $rows);

        $component->set('role', '');
        $rows = $this->downloadedSpreadsheet($component->call('export'))->getSheet(0)->toArray(null, true, true, false);

        $this->assertSame(['24010001', 'Rizky', '+62 812-3456-7890', 'Admin Kelas'], array_slice($rows[6], 1, 4));
        $this->assertSame(['24010002', 'Siti'], array_slice($rows[7], 1, 2));
        $this->assertCount(8, $rows);
    }

    public function test_super_admin_can_export_the_super_admin_list_and_all_kelas(): void
    {
        $kelas = $this->kelas(attributes: ['nama' => 'TI-3A', 'prodi' => 'Teknik Informatika', 'angkatan' => 2024]);
        $this->mahasiswa($kelas);
        $this->admin($kelas);
        MataKuliah::factory()->count(2)->create(['kelas_id' => $kelas->id]);
        $superAdmin = $this->superAdmin(['npm' => 'superadmin', 'name' => 'Super Admin', 'no_hp' => null]);

        $component = Livewire::actingAs($superAdmin)
            ->test(UsersIndex::class)
            ->set('role', 'super_admin')
            ->call('export')
            ->assertFileDownloaded();

        $rows = $this->downloadedSpreadsheet($component)->getSheet(0)->toArray(null, true, true, false);
        $this->assertSame('Semua super admin', $rows[1][0]);
        $this->assertSame(['1', 'superadmin', 'Super Admin', null, 'Super Admin', null], array_slice($rows[6], 0, 6));
        $this->assertCount(7, $rows);

        $component = Livewire::actingAs($superAdmin)
            ->test(KelasIndex::class)
            ->set('search', 'TI-3')
            ->call('export')
            ->assertFileDownloaded('kelas_'.now()->format('Y-m-d').'.xlsx');

        $rows = $this->downloadedSpreadsheet($component)->getSheet(0)->toArray(null, true, true, false);
        $this->assertSame('Filter: Pencarian = TI-3', $rows[2][0]);
        $this->assertSame(['No', 'Nama Kelas', 'Prodi', 'Angkatan', 'Semester Aktif', 'Masa Aktif', 'Status', 'Fitur Upload', 'Jumlah Mahasiswa', 'Jumlah Mata Kuliah', 'Dibuat Pada'], $rows[5]);
        $this->assertSame(['1', 'TI-3A', 'Teknik Informatika', '2024', 'Semester 3', 'Tanpa batas', 'Aktif', 'Ya', '2', '2'], array_slice($rows[6], 0, 10));
        $this->assertCount(7, $rows);
    }

    public function test_admin_kelas_cannot_export_the_kelas_list(): void
    {
        Livewire::actingAs($this->admin($this->kelas()))
            ->test(KelasIndex::class)
            ->assertForbidden();
    }

    private function downloadedSpreadsheet(Testable $component): Spreadsheet
    {
        $path = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
        file_put_contents($path, base64_decode(data_get($component->effects, 'download.content')));

        try {
            return IOFactory::load($path);
        } finally {
            unlink($path);
        }
    }
}
