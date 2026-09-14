<?php

namespace Tests\Feature;

use App\Enums\AksiLog;
use App\Enums\ModulLog;
use App\Livewire\JadwalLab\Index;
use App\Models\ActivityLog;
use App\Models\JadwalLab;
use App\Models\MataKuliah;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalLabTest extends TestCase
{
    public function test_admin_can_add_several_sesi_to_one_mata_kuliah_at_once(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web']);
        $admin = $this->admin($kelas);

        $component = Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openCreate', $mataKuliah->id)
            ->assertSet('showForm', true)
            ->assertSet('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->call('addSesi');

        [$keyA, $keyB] = array_keys($component->get('form.sesi'));

        $component
            ->set("form.sesi.{$keyB}.tanggal", '2026-09-18')
            ->set("form.sesi.{$keyB}.jam_mulai", '13:00')
            ->set("form.sesi.{$keyB}.jam_selesai", '15:00')
            ->set("form.sesi.{$keyB}.ruangan", 'Lab 2')
            ->set("form.sesi.{$keyB}.keterangan", 'Instalasi Laravel')
            ->set("form.sesi.{$keyA}.tanggal", '2026-09-25')
            ->set("form.sesi.{$keyA}.jam_mulai", '13:00')
            ->set("form.sesi.{$keyA}.jam_selesai", '15:00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertDispatched('notify')
            ->assertSeeInOrder(['Instalasi Laravel', '25']);

        $this->assertSame(2, JadwalLab::query()->where('mata_kuliah_id', $mataKuliah->id)->count());
        $this->assertDatabaseHas('jadwal_lab', ['mata_kuliah_id' => $mataKuliah->id, 'tanggal' => '2026-09-18', 'ruangan' => 'Lab 2', 'keterangan' => 'Instalasi Laravel']);
        $this->assertDatabaseHas('jadwal_lab', ['mata_kuliah_id' => $mataKuliah->id, 'tanggal' => '2026-09-25', 'ruangan' => null, 'keterangan' => null]);

        $log = ActivityLog::query()->where('modul', ModulLog::JadwalLab->value)->where('aksi', AksiLog::Buat->value)->orderBy('id')->first();
        $this->assertSame($kelas->id, $log->kelas_id);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('Pemrograman Web · 18 Sep 2026 13:00 - 15:00', $log->subjek_label);
    }

    public function test_new_sesi_copies_jam_and_ruangan_and_moves_tanggal_one_week_ahead(): void
    {
        $kelas = $this->kelas();

        $component = Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('openCreate');

        $keyA = array_key_first($component->get('form.sesi'));

        $component
            ->set("form.sesi.{$keyA}.tanggal", '2026-09-18')
            ->set("form.sesi.{$keyA}.jam_mulai", '13:00')
            ->set("form.sesi.{$keyA}.jam_selesai", '15:00')
            ->set("form.sesi.{$keyA}.ruangan", 'Lab 2')
            ->set("form.sesi.{$keyA}.keterangan", 'Instalasi Laravel')
            ->call('addSesi');

        $sesi = $component->get('form.sesi');
        $this->assertCount(2, $sesi);
        $this->assertSame([
            'tanggal' => '2026-09-25',
            'jam_mulai' => '13:00',
            'jam_selesai' => '15:00',
            'ruangan' => 'Lab 2',
            'keterangan' => '',
        ], end($sesi));
    }

    public function test_new_sesi_starts_empty_when_the_previous_row_has_no_tanggal_yet(): void
    {
        $kelas = $this->kelas();

        $component = Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('openCreate')
            ->call('addSesi');

        $sesi = $component->get('form.sesi');
        $this->assertSame(['tanggal' => '', 'jam_mulai' => '', 'jam_selesai' => '', 'ruangan' => '', 'keterangan' => ''], end($sesi));
    }

    public function test_each_sesi_is_validated_on_its_own_row(): void
    {
        $kelas = $this->kelas();
        MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $mataKuliahLain = MataKuliah::factory()->create(['kelas_id' => $this->kelas()->id]);

        $component = Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('openCreate')
            ->call('addSesi');

        [$keyA, $keyB] = array_keys($component->get('form.sesi'));

        $component
            ->set('form.mata_kuliah_id', (string) $mataKuliahLain->id)
            ->set("form.sesi.{$keyA}.tanggal", '2026-09-18')
            ->set("form.sesi.{$keyA}.jam_mulai", '10:00')
            ->set("form.sesi.{$keyA}.jam_selesai", '09:00')
            ->set("form.sesi.{$keyB}.tanggal", 'bukan-tanggal')
            ->set("form.sesi.{$keyB}.jam_mulai", '13:00')
            ->set("form.sesi.{$keyB}.jam_selesai", '15:00')
            ->call('save')
            ->assertHasErrors(['form.mata_kuliah_id', "form.sesi.{$keyA}.jam_selesai", "form.sesi.{$keyB}.tanggal"])
            ->assertHasNoErrors(["form.sesi.{$keyA}.tanggal", "form.sesi.{$keyB}.jam_selesai"])
            ->assertSee('Jam selesai harus berisi tanggal setelah jam mulai.');

        $this->assertSame(0, JadwalLab::query()->count());
    }

    public function test_rows_can_be_removed_but_never_the_last_one(): void
    {
        $kelas = $this->kelas();

        $component = Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('openCreate')
            ->call('addSesi')
            ->call('addSesi');

        [$keyA, $keyB, $keyC] = array_keys($component->get('form.sesi'));

        $component
            ->call('removeSesi', $keyB)
            ->call('removeSesi', $keyA)
            ->call('removeSesi', $keyC);

        $this->assertSame([$keyC], array_keys($component->get('form.sesi')));
    }

    public function test_admin_can_edit_and_delete_a_single_jadwal_lab(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $jadwalLab = JadwalLab::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'tanggal' => '2026-09-18', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'ruangan' => 'Lab 2']);

        $component = Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('openEdit', $jadwalLab->id)
            ->assertSet('form.mata_kuliah_id', (string) $mataKuliah->id);

        $sesi = $component->get('form.sesi');
        $this->assertCount(1, $sesi);
        $key = array_key_first($sesi);
        $this->assertSame('2026-09-18', $sesi[$key]['tanggal']);
        $this->assertSame('13:00', $sesi[$key]['jam_mulai']);

        $component
            ->set("form.sesi.{$key}.tanggal", '2026-09-19')
            ->set("form.sesi.{$key}.jam_mulai", '08:00')
            ->set("form.sesi.{$key}.jam_selesai", '10:00')
            ->set("form.sesi.{$key}.ruangan", '')
            ->call('save')
            ->assertHasNoErrors();

        $jadwalLab->refresh();
        $this->assertSame('2026-09-19', $jadwalLab->tanggal->toDateString());
        $this->assertSame('08:00 - 10:00', $jadwalLab->jam());
        $this->assertNull($jadwalLab->ruangan);
        $this->assertSame(1, JadwalLab::query()->count());

        Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('confirmDelete', $jadwalLab->id)
            ->assertSet('confirmingDelete', true)
            ->call('delete');

        $this->assertModelMissing($jadwalLab);
    }

    public function test_admin_of_another_kelas_cannot_edit_or_delete_the_jadwal_lab(): void
    {
        $jadwalLab = JadwalLab::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $this->kelas()->id])->id]);
        $adminLain = $this->admin($this->kelas());

        Livewire::actingAs($adminLain)->test(Index::class)->call('openEdit', $jadwalLab->id)->assertForbidden();
        Livewire::actingAs($adminLain)->test(Index::class)->call('confirmDelete', $jadwalLab->id)->assertForbidden();

        $this->assertModelExists($jadwalLab);
    }

    public function test_mahasiswa_can_see_the_schedule_but_cannot_open_the_form_or_add_rows(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Basis Data']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'ruangan' => 'Lab 4', 'keterangan' => 'Normalisasi tabel']);

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('jadwal-lab.index'))
            ->assertOk()
            ->assertSeeInOrder(['Basis Data', 'Lab 4', 'Normalisasi tabel'])
            ->assertDontSee('Tambah Jadwal Lab');

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(Index::class)
            ->call('openCreate')
            ->assertForbidden();

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(Index::class)
            ->call('addSesi')
            ->assertForbidden();
    }

    public function test_status_and_mata_kuliah_filters_narrow_the_sesi_shown(): void
    {
        $this->travelTo(Carbon::create(2026, 9, 14, 10, 0));

        $kelas = $this->kelas();
        $web = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web']);
        $basisData = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Basis Data']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $web->id, 'tanggal' => '2026-09-07', 'keterangan' => 'Sesi lewat']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $web->id, 'tanggal' => '2026-09-14', 'jam_mulai' => '08:00', 'jam_selesai' => '09:40', 'keterangan' => 'Sesi tadi pagi']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $web->id, 'tanggal' => '2026-09-14', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'keterangan' => 'Sesi nanti siang']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $basisData->id, 'tanggal' => '2026-09-21', 'keterangan' => 'Sesi minggu depan']);

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(Index::class)
            ->assertSeeInOrder(['Basis Data', 'Sesi minggu depan', 'Pemrograman Web', 'Sesi lewat', 'Sesi tadi pagi', 'Sesi nanti siang'])
            ->set('status', 'mendatang')
            ->assertSee('Sesi nanti siang')
            ->assertSee('Sesi minggu depan')
            ->assertDontSee('Sesi lewat')
            ->assertDontSee('Sesi tadi pagi')
            ->set('status', 'lewat')
            ->assertSee('Sesi lewat')
            ->assertSee('Sesi tadi pagi')
            ->assertDontSee('Sesi nanti siang')
            ->set('status', '')
            ->set('mataKuliahId', (string) $basisData->id)
            ->assertSee('Sesi minggu depan')
            ->assertDontSee('Sesi lewat')
            ->assertDontSee('Sesi nanti siang');
    }

    public function test_only_mata_kuliah_with_sesi_in_the_active_semester_of_own_kelas_are_listed(): void
    {
        $kelas = $this->kelas(semester: 3);
        $semesterLalu = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'semester_id' => 2, 'nama' => 'Algoritma Lama']);
        $kelasLain = MataKuliah::factory()->create(['kelas_id' => $this->kelas()->id, 'nama' => 'Milik Kelas Lain']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $semesterLalu->id, 'keterangan' => 'Lab semester lalu']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $kelasLain->id, 'keterangan' => 'Lab kelas lain']);
        $tanpaSesi = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Jaringan Komputer']);

        $component = Livewire::actingAs($this->mahasiswa($kelas))
            ->test(Index::class)
            ->assertSee('Belum ada jadwal lab')
            ->assertDontSee('0 sesi')
            ->assertDontSee('Algoritma Lama')
            ->assertDontSee('Lab semester lalu')
            ->assertDontSee('Milik Kelas Lain')
            ->assertDontSee('Lab kelas lain');

        JadwalLab::factory()->create(['mata_kuliah_id' => $tanpaSesi->id, 'keterangan' => 'Konfigurasi router']);

        $component
            ->call('$refresh')
            ->assertSeeInOrder(['Jaringan Komputer', '1 sesi', 'Konfigurasi router'])
            ->assertDontSee('Belum ada jadwal lab');
    }

    public function test_filters_with_no_matching_sesi_show_a_not_found_state_instead_of_empty_cards(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Basis Data']);
        JadwalLab::factory()->lewat()->create(['mata_kuliah_id' => $mataKuliah->id, 'keterangan' => 'Sesi lewat']);

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(Index::class)
            ->assertSee('Sesi lewat')
            ->set('status', 'mendatang')
            ->assertSee('Jadwal lab tidak ditemukan')
            ->assertDontSee('0 sesi')
            ->assertDontSee('Sesi lewat');
    }

    public function test_today_lab_session_appears_on_the_dashboard_and_mata_kuliah_detail(): void
    {
        $this->travelTo(Carbon::create(2026, 9, 14, 7, 0));

        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Jaringan Komputer']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'tanggal' => '2026-09-14', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'keterangan' => 'Konfigurasi router']);
        JadwalLab::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'tanggal' => '2026-09-21', 'keterangan' => 'Subnetting']);

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Jadwal Hari Ini', '13:00', 'Jaringan Komputer', 'Lab', 'Konfigurasi router'])
            ->assertDontSee('Subnetting');

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('mata-kuliah.show', $mataKuliah))
            ->assertOk()
            ->assertSeeInOrder(['Jadwal Lab', 'Sen, 14 Sep 2026', 'Konfigurasi router', 'Sen, 21 Sep 2026', 'Subnetting']);
    }
}
