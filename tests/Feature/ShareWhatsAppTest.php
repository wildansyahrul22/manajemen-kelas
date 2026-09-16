<?php

namespace Tests\Feature;

use App\Livewire\Informasi\Index as InformasiIndex;
use App\Livewire\Jadwal\Index as JadwalIndex;
use App\Livewire\JadwalLab\Index as JadwalLabIndex;
use App\Livewire\Kelompok\Index as KelompokIndex;
use App\Livewire\Tugas\Index as TugasIndex;
use App\Models\Informasi;
use App\Models\InformasiLampiran;
use App\Models\JadwalKelas;
use App\Models\JadwalLab;
use App\Models\KategoriInformasi;
use App\Models\KategoriKelompok;
use App\Models\Kelompok;
use App\Models\MataKuliah;
use App\Models\Tugas;
use App\Support\PesanWhatsApp;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ShareWhatsAppTest extends TestCase
{
    public function test_url_opens_whatsapp_with_the_message_prefilled(): void
    {
        $this->assertSame('https://wa.me/?text=Halo%20%2A%20dunia%0Abaris%20dua', PesanWhatsApp::url("Halo * dunia\nbaris dua"));
    }

    public function test_informasi_message_and_share_links_on_list_and_detail(): void
    {
        $kelas = $this->kelas(attributes: ['nama' => 'TI-3A']);
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pengumuman']);
        $informasi = Informasi::factory()->create([
            'kelas_id' => $kelas->id,
            'kategori_informasi_id' => $kategori->id,
            'judul' => 'Kuis Bab 3',
            'isi' => "Kuis dilaksanakan Senin.\nBawa kalkulator.",
            'link' => 'https://drive.google.com/x',
            'is_pinned' => true,
        ]);
        InformasiLampiran::factory()->count(2)->create(['informasi_id' => $informasi->id]);

        $teks = PesanWhatsApp::informasi($informasi->fresh(['kategori', 'lampiran']), $kelas);

        $this->assertSame(implode("\n", [
            '📢 *INFORMASI KELAS TI-3A*',
            'Kategori: Pengumuman 📌',
            '',
            '*Kuis Bab 3*',
            "Kuis dilaksanakan Senin.\nBawa kalkulator.",
            '',
            '🔗 Tautan: https://drive.google.com/x',
            '📎 2 lampiran (buka di aplikasi, login dengan NPM)',
            '',
            '👉 Selengkapnya: '.route('informasi.show', $informasi),
            '_Dibagikan dari aplikasi Manajemen Kelas TI-3A_',
        ]), $teks);

        $mahasiswa = $this->mahasiswa($kelas);

        Livewire::actingAs($mahasiswa)
            ->test(InformasiIndex::class)
            ->assertSee(PesanWhatsApp::url($teks), false)
            ->assertSee('Bagikan informasi ini ke WhatsApp');

        $this->actingAs($mahasiswa)
            ->get(route('informasi.show', $informasi))
            ->assertOk()
            ->assertSee(PesanWhatsApp::url($teks), false)
            ->assertSee('Bagikan ke WhatsApp');
    }

    public function test_long_isi_is_shortened_in_the_message(): void
    {
        $kelas = $this->kelas();
        $informasi = Informasi::factory()->create(['kelas_id' => $kelas->id, 'isi' => str_repeat('a', 2000), 'link' => null]);

        $teks = PesanWhatsApp::informasi($informasi->fresh(['kategori', 'lampiran']), $kelas);

        $this->assertStringContainsString(str_repeat('a', 1500).' … (selengkapnya di aplikasi)', $teks);
        $this->assertStringNotContainsString(str_repeat('a', 1501), $teks);
        $this->assertStringNotContainsString('🔗', $teks);
        $this->assertStringNotContainsString('📎', $teks);
    }

    public function test_jadwal_kelas_is_shared_per_hari_only_when_the_day_has_sessions(): void
    {
        $this->travelTo(Carbon::create(2026, 9, 14, 9, 0)); // Senin

        $kelas = $this->kelas(attributes: ['nama' => 'TI-3A']);
        $web = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web', 'dosen' => 'Dr. Andi']);
        $basisData = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Basis Data', 'dosen' => 'Ir. Dewi']);
        JadwalKelas::factory()->create(['mata_kuliah_id' => $basisData->id, 'hari' => 1, 'jam_mulai' => '13:00', 'jam_selesai' => '14:40', 'ruangan' => null]);
        JadwalKelas::factory()->create(['mata_kuliah_id' => $web->id, 'hari' => 1, 'jam_mulai' => '08:00', 'jam_selesai' => '09:40', 'ruangan' => 'Lab 2']);

        $component = Livewire::actingAs($this->mahasiswa($kelas))->test(JadwalIndex::class);
        $teks = $component->get('teksWhatsApp');

        $this->assertSame([1], array_keys($teks));
        $this->assertSame(implode("\n", [
            '📅 *JADWAL KELAS TI-3A — SENIN (HARI INI)*',
            'Semester 3',
            '',
            '1. 08:00–09:40 · *Pemrograman Web*',
            '    Dr. Andi · 📍 Lab 2',
            '2. 13:00–14:40 · *Basis Data*',
            '    Ir. Dewi',
            '',
            'Jangan lupa hadir tepat waktu 🙌',
            '👉 Jadwal lengkap: '.route('jadwal.index'),
        ]), $teks[1]);

        $component
            ->assertSee(PesanWhatsApp::url($teks[1]), false)
            ->assertSee('Bagikan jadwal Senin ke WhatsApp')
            ->assertDontSee('Bagikan jadwal Selasa ke WhatsApp');
    }

    public function test_jadwal_lab_is_shared_per_tanggal_across_mata_kuliah(): void
    {
        $this->travelTo(Carbon::create(2026, 9, 14, 9, 0));

        $kelas = $this->kelas(attributes: ['nama' => 'TI-3A']);
        $web = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web']);
        $jarkom = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Jaringan Komputer']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $web->id, 'tanggal' => '2026-09-15', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'ruangan' => 'Lab 2', 'keterangan' => 'Routing']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $jarkom->id, 'tanggal' => '2026-09-15', 'jam_mulai' => '08:00', 'jam_selesai' => '10:00', 'ruangan' => null, 'keterangan' => null]);
        JadwalLab::factory()->create(['mata_kuliah_id' => $web->id, 'tanggal' => '2026-09-22', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'ruangan' => 'Lab 2', 'keterangan' => null]);

        $component = Livewire::actingAs($this->mahasiswa($kelas))->test(JadwalLabIndex::class);
        $teks = $component->get('teksWhatsApp');

        $this->assertSame(['2026-09-15', '2026-09-22'], array_keys($teks));
        $this->assertSame(implode("\n", [
            '🔬 *JADWAL LAB / PRAKTIKUM TI-3A*',
            '📆 Selasa, 15 September 2026 (besok)',
            '',
            '1. 08:00–10:00 · *Jaringan Komputer*',
            '2. 13:00–15:00 · *Pemrograman Web*',
            '    📍 Lab 2 · 📝 Routing',
            '',
            'Siapkan perlengkapan praktikum dan hadir tepat waktu ya 🙌',
            '👉 Jadwal lab lengkap: '.route('jadwal-lab.index'),
        ]), $teks['2026-09-15']);

        $component
            ->assertSee(PesanWhatsApp::url($teks['2026-09-15']), false)
            ->assertSee('Bagikan jadwal lab 15 Sep ke WhatsApp')
            ->assertSee('Bagikan jadwal lab 22 Sep ke WhatsApp');
    }

    public function test_kelompok_is_shared_per_kategori_and_requires_one_to_be_picked(): void
    {
        $kelas = $this->kelas(attributes: ['nama' => 'TI-3A']);
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web']);
        $kategori = KategoriKelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'nama' => 'Project Akhir']);
        $siti = $this->mahasiswa($kelas, ['npm' => '24010001', 'name' => 'Siti']);
        $budi = $this->mahasiswa($kelas, ['npm' => '24010002', 'name' => 'Budi']);
        $this->mahasiswa($kelas, ['npm' => '24010003', 'name' => 'Cici']);
        $kelompok = Kelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'kategori_kelompok_id' => $kategori->id, 'nama' => 'Kelompok 1']);
        $kelompok->anggota()->attach([$siti->id => ['is_ketua' => true], $budi->id => ['is_ketua' => false]]);
        Kelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'kategori_kelompok_id' => $kategori->id, 'nama' => 'Kelompok 2']);

        $component = Livewire::actingAs($siti)
            ->test(KelompokIndex::class)
            ->assertSet('teksWhatsApp', null)
            ->assertSee('Pilih kategori dulu untuk membagikan pembagian kelompoknya.')
            ->assertDontSee('https://wa.me/', false)
            ->set('kategoriId', (string) $kategori->id);

        $teks = $component->get('teksWhatsApp');

        $this->assertSame(implode("\n", [
            '👥 *PEMBAGIAN KELOMPOK — PROJECT AKHIR*',
            'Pemrograman Web · Kelas TI-3A',
            '',
            '*Kelompok 1* — Ketua: Siti',
            '1. Siti (24010001)',
            '2. Budi (24010002)',
            '',
            '*Kelompok 2*',
            '',
            '⚠️ Belum masuk kelompok: Cici',
            '',
            'Total: 2 kelompok · 2 mahasiswa',
            '👉 Detail kelompok: '.route('kelompok.index', ['kategori' => $kategori->id]),
        ]), $teks);

        $component
            ->assertSee(PesanWhatsApp::url($teks), false)
            ->assertDontSee('Pilih kategori dulu untuk membagikan pembagian kelompoknya.');
    }

    public function test_kelompok_share_ignores_a_kategori_of_another_kelas(): void
    {
        $kelas = $this->kelas();
        $kategoriLain = KategoriKelompok::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $this->kelas()->id])->id]);

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(KelompokIndex::class)
            ->set('kategoriId', (string) $kategoriLain->id)
            ->assertSet('teksWhatsApp', null);
    }

    public function test_tugas_is_shared_per_record_with_deadline_and_submission_link(): void
    {
        $this->travelTo(Carbon::create(2026, 9, 14, 9, 0));

        $kelas = $this->kelas(attributes: ['nama' => 'TI-3A']);
        $web = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web', 'dosen' => 'Dr. Andi']);
        $tugas = Tugas::factory()->create([
            'mata_kuliah_id' => $web->id,
            'nama' => 'Laporan Praktikum 2',
            'deskripsi' => 'Kumpulkan dalam format PDF.',
            'deadline' => '2026-09-21 23:59:00',
            'link_pengumpulan' => 'https://forms.gle/abc',
        ]);

        $teks = PesanWhatsApp::tugas($tugas->fresh(['mataKuliah']), $kelas);

        $this->assertSame(implode("\n", [
            '📝 *TUGAS KELAS TI-3A*',
            'Mata kuliah: Pemrograman Web (Dr. Andi)',
            '',
            '*Laporan Praktikum 2*',
            '⏰ Deadline: Senin, 21 September 2026 pukul 23:59 (1 minggu lagi)',
            '',
            'Kumpulkan dalam format PDF.',
            '',
            '📤 Kumpulkan di: https://forms.gle/abc',
            '👉 Detail tugas: '.route('tugas.show', $tugas),
            'Jangan sampai terlewat ya 💪',
        ]), $teks);

        $mahasiswa = $this->mahasiswa($kelas);

        Livewire::actingAs($mahasiswa)
            ->test(TugasIndex::class)
            ->assertSee(PesanWhatsApp::url($teks), false)
            ->assertSee('Bagikan tugas ini ke WhatsApp');

        $this->actingAs($mahasiswa)
            ->get(route('tugas.show', $tugas))
            ->assertOk()
            ->assertSee(PesanWhatsApp::url($teks), false);

        // Without deskripsi/link those lines are simply absent, and a passed deadline says so.
        $lewat = Tugas::factory()->create(['mata_kuliah_id' => $web->id, 'nama' => 'Kuis', 'deskripsi' => null, 'deadline' => '2026-09-10 10:00:00', 'link_pengumpulan' => null]);
        $teksLewat = PesanWhatsApp::tugas($lewat->fresh(['mataKuliah']), $kelas);
        $this->assertStringContainsString('(sudah lewat)', $teksLewat);
        $this->assertStringNotContainsString('📤', $teksLewat);
        $this->assertStringNotContainsString("*Kuis*\n\n\n", $teksLewat);
    }

    public function test_whole_week_of_jadwal_kelas_can_be_shared_next_to_the_per_day_icons(): void
    {
        $this->travelTo(Carbon::create(2026, 9, 14, 9, 0)); // Senin

        $kelas = $this->kelas(attributes: ['nama' => 'TI-3A']);
        $web = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web']);
        $basisData = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Basis Data']);

        $kosong = Livewire::actingAs($this->mahasiswa($kelas))->test(JadwalIndex::class);
        $this->assertNull($kosong->get('teksWhatsAppMingguan'));
        $kosong->assertDontSee('Bagikan Jadwal Seminggu');

        JadwalKelas::factory()->create(['mata_kuliah_id' => $web->id, 'hari' => 1, 'jam_mulai' => '08:00', 'jam_selesai' => '09:40', 'ruangan' => 'Lab 2']);
        JadwalKelas::factory()->create(['mata_kuliah_id' => $basisData->id, 'hari' => 3, 'jam_mulai' => '13:00', 'jam_selesai' => '14:40', 'ruangan' => null]);

        $component = Livewire::actingAs($this->mahasiswa($kelas))->test(JadwalIndex::class);
        $teks = $component->get('teksWhatsAppMingguan');

        $this->assertSame(implode("\n", [
            '📅 *JADWAL KELAS TI-3A*',
            'Semester 3',
            '',
            '*SENIN* (hari ini)',
            '1. 08:00–09:40 · Pemrograman Web · 📍 Lab 2',
            '',
            '*RABU*',
            '1. 13:00–14:40 · Basis Data',
            '',
            'Simpan jadwalnya, jangan sampai salah ruangan 🙌',
            '👉 Jadwal lengkap: '.route('jadwal.index'),
        ]), $teks);

        $component
            ->assertSee('Bagikan Jadwal Seminggu')
            ->assertSee(PesanWhatsApp::url($teks), false)
            ->assertSee('Bagikan jadwal Senin ke WhatsApp')
            ->assertSee('Bagikan jadwal Rabu ke WhatsApp');
    }

    public function test_all_lab_sessions_of_one_mata_kuliah_can_be_shared_next_to_the_per_date_icons(): void
    {
        $this->travelTo(Carbon::create(2026, 9, 14, 9, 0));

        $kelas = $this->kelas(attributes: ['nama' => 'TI-3A']);
        $web = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web', 'dosen' => 'Dr. Andi']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $web->id, 'tanggal' => '2026-09-07', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'ruangan' => 'Lab 1', 'keterangan' => 'Instalasi']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $web->id, 'tanggal' => '2026-09-14', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'ruangan' => 'Lab 1', 'keterangan' => null]);
        JadwalLab::factory()->create(['mata_kuliah_id' => $web->id, 'tanggal' => '2026-09-21', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'ruangan' => null, 'keterangan' => 'Routing']);

        $component = Livewire::actingAs($this->mahasiswa($kelas))->test(JadwalLabIndex::class);
        $teks = $component->get('teksWhatsAppMataKuliah');

        $this->assertSame([$web->id], array_keys($teks));
        $this->assertSame(implode("\n", [
            '🔬 *JADWAL LAB PEMROGRAMAN WEB*',
            'Kelas TI-3A · Dr. Andi',
            '',
            '1. Sen, 7 Sep 2026 · 13:00–15:00 ✅',
            '    📍 Lab 1 · 📝 Instalasi',
            '2. Sen, 14 Sep 2026 · 13:00–15:00 (hari ini)',
            '    📍 Lab 1',
            '3. Sen, 21 Sep 2026 · 13:00–15:00',
            '    📝 Routing',
            '',
            'Catat tanggalnya dan siapkan perlengkapan praktikum 🙌',
            '👉 Jadwal lab lengkap: '.route('jadwal-lab.index', ['mk' => $web->id]),
        ]), $teks[$web->id]);

        $component
            ->assertSee('Bagikan semua jadwal lab Pemrograman Web ke WhatsApp')
            ->assertSee(PesanWhatsApp::url($teks[$web->id]), false)
            ->assertSee('Bagikan jadwal lab 14 Sep ke WhatsApp');
    }
}
