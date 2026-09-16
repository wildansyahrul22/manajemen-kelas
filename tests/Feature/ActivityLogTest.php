<?php

namespace Tests\Feature;

use App\Enums\AksiLog;
use App\Enums\ModulLog;
use App\Livewire\Auth\Login;
use App\Livewire\Informasi\Index as InformasiIndex;
use App\Livewire\Kelompok\Index as KelompokIndex;
use App\Livewire\LogAktivitas\Index as LogIndex;
use App\Models\ActivityLog;
use App\Models\Informasi;
use App\Models\KategoriInformasi;
use App\Models\KategoriKelompok;
use App\Models\Kelompok;
use App\Models\MataKuliah;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    public function test_create_update_and_delete_are_logged_with_actor_kelas_and_changes(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);
        $admin = $this->admin($kelas, ['name' => 'Admin Kelas A']);

        ActivityLog::query()->delete();

        $component = Livewire::actingAs($admin)
            ->test(InformasiIndex::class)
            ->call('openCreate')
            ->set('form.judul', 'Kuis Minggu Depan')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Siapkan materi bab 3.')
            ->call('save')
            ->assertHasNoErrors();

        $informasi = Informasi::query()->where('judul', 'Kuis Minggu Depan')->firstOrFail();

        $buat = ActivityLog::query()->where('modul', ModulLog::Informasi->value)->where('aksi', AksiLog::Buat->value)->firstOrFail();
        $this->assertSame($kelas->id, $buat->kelas_id);
        $this->assertSame($admin->id, $buat->user_id);
        $this->assertSame('Admin Kelas A', $buat->user_name);
        $this->assertSame($informasi->id, $buat->subjek_id);
        $this->assertSame('Kuis Minggu Depan', $buat->subjek_label);
        $this->assertNull($buat->perubahan);

        $component
            ->call('openEdit', $informasi->id)
            ->set('form.judul', 'Kuis Minggu Ini')
            ->call('save')
            ->assertHasNoErrors();

        $ubah = ActivityLog::query()->where('modul', ModulLog::Informasi->value)->where('aksi', AksiLog::Ubah->value)->latest('id')->firstOrFail();
        $this->assertSame(['judul' => ['Kuis Minggu Depan', 'Kuis Minggu Ini']], $ubah->perubahan);

        $component->call('confirmDelete', $informasi->id)->call('delete');

        $hapus = ActivityLog::query()->where('modul', ModulLog::Informasi->value)->where('aksi', AksiLog::Hapus->value)->firstOrFail();
        $this->assertSame('Kuis Minggu Ini', $hapus->subjek_label);
        $this->assertSame($kelas->id, $hapus->kelas_id);
    }

    public function test_password_changes_are_masked_and_pin_toggle_is_logged(): void
    {
        $kelas = $this->kelas();
        $user = $this->mahasiswa($kelas);
        $informasi = Informasi::factory()->create(['kelas_id' => $kelas->id]);

        $user->update(['password' => 'rahasia-baru']);

        $log = ActivityLog::query()->where('modul', ModulLog::User->value)->where('aksi', AksiLog::Ubah->value)->latest('id')->firstOrFail();
        $this->assertSame(['••••••', '(diubah)'], $log->perubahan['password']);
        $this->assertStringNotContainsString('rahasia-baru', json_encode($log->perubahan));

        Livewire::actingAs($this->admin($kelas))->test(InformasiIndex::class)->call('togglePin', $informasi->id);

        $pin = ActivityLog::query()->where('modul', ModulLog::Informasi->value)->where('subjek_id', $informasi->id)->where('aksi', AksiLog::Ubah->value)->latest('id')->firstOrFail();
        $this->assertSame([false, true], $pin->perubahan['is_pinned']);
    }

    public function test_login_and_logout_are_logged(): void
    {
        $kelas = $this->kelas();
        $user = $this->mahasiswa($kelas, ['npm' => '24010002']);

        Livewire::test(Login::class)
            ->set('npm', '24010002')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors();

        $masuk = ActivityLog::query()->where('aksi', AksiLog::Masuk->value)->firstOrFail();
        $this->assertSame($user->id, $masuk->user_id);
        $this->assertSame($kelas->id, $masuk->kelas_id);
        $this->assertSame(ModulLog::Auth, $masuk->modul);

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertSame(1, ActivityLog::query()->where('aksi', AksiLog::Keluar->value)->where('user_id', $user->id)->count());
    }

    public function test_kelompok_membership_changes_are_logged(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriKelompok::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $kelas->id])->id]);
        $lama = $this->mahasiswa($kelas, ['name' => 'Anggota Lama']);
        $baru = $this->mahasiswa($kelas, ['name' => 'Anggota Baru']);
        $kelompok = Kelompok::factory()->create(['kategori_kelompok_id' => $kategori->id]);
        $kelompok->anggota()->attach($lama->id, ['is_ketua' => true]);

        Livewire::actingAs($this->admin($kelas))
            ->test(KelompokIndex::class)
            ->call('openEdit', $kelompok->id)
            ->set('form.anggota', [$lama->id, $baru->id])
            ->set('form.ketua_id', (string) $baru->id)
            ->call('save')
            ->assertHasNoErrors();

        $log = ActivityLog::query()->where('modul', ModulLog::Kelompok->value)->where('aksi', AksiLog::Ubah->value)->latest('id')->firstOrFail();
        $this->assertSame(['Anggota Lama', 'Anggota Baru, Anggota Lama'], $log->perubahan['anggota']);
        $this->assertSame(['Anggota Lama', 'Anggota Baru'], $log->perubahan['ketua']);
    }

    public function test_admin_kelas_only_sees_logs_of_their_own_kelas(): void
    {
        $kelasA = $this->kelas();
        $kelasB = $this->kelas();
        Informasi::factory()->create(['kelas_id' => $kelasA->id, 'judul' => 'Info Kelas A']);
        Informasi::factory()->create(['kelas_id' => $kelasB->id, 'judul' => 'Info Kelas B']);

        Livewire::actingAs($this->admin($kelasA))
            ->test(LogIndex::class)
            ->assertSee('Info Kelas A')
            ->assertDontSee('Info Kelas B')
            ->assertDontSee('Semua kelas');

        // The kelas filter is ignored for admin kelas.
        Livewire::actingAs($this->admin($kelasA))
            ->test(LogIndex::class)
            ->set('kelasId', (string) $kelasB->id)
            ->assertSee('Info Kelas A')
            ->assertDontSee('Info Kelas B');
    }

    public function test_super_admin_sees_every_kelas_and_can_filter_by_kelas_aksi_and_modul(): void
    {
        $kelasA = $this->kelas(3, ['nama' => 'TI-A']);
        $kelasB = $this->kelas(3, ['nama' => 'TI-B']);
        $infoA = Informasi::factory()->create(['kelas_id' => $kelasA->id, 'judul' => 'Info Kelas A']);
        Informasi::factory()->create(['kelas_id' => $kelasB->id, 'judul' => 'Info Kelas B']);
        $infoA->delete();

        $component = Livewire::actingAs($this->superAdmin())
            ->test(LogIndex::class)
            ->assertSee('Info Kelas A')
            ->assertSee('Info Kelas B')
            ->assertSee('Semua kelas');

        $component->set('kelasId', (string) $kelasB->id)->assertSee('Info Kelas B')->assertDontSee('Info Kelas A');
        $component->set('kelasId', '')->set('aksi', AksiLog::Hapus->value)->assertSee('Info Kelas A')->assertDontSee('Info Kelas B');
        $component->set('aksi', '')->set('modul', ModulLog::Kelas->value)->assertSee('TI-A')->assertDontSee('Info Kelas A');
        $component->set('modul', '')->set('search', 'Kelas B')->assertSee('Info Kelas B')->assertDontSee('Info Kelas A');
    }

    public function test_mahasiswa_cannot_open_the_log_page(): void
    {
        $kelas = $this->kelas();

        $this->actingAs($this->mahasiswa($kelas))->get(route('log-aktivitas.index'))->assertForbidden();
        $this->actingAs($this->admin($kelas))->get(route('log-aktivitas.index'))->assertOk()->assertSee('Log Aktivitas');
    }

    public function test_super_admin_can_delete_a_single_log_entry(): void
    {
        $kelas = $this->kelas();
        Informasi::factory()->create(['kelas_id' => $kelas->id, 'judul' => 'Info Hapus']);
        $log = ActivityLog::query()->where('subjek_label', 'Info Hapus')->firstOrFail();

        Livewire::actingAs($this->superAdmin())
            ->test(LogIndex::class)
            ->assertSee('Hapus entri')
            ->call('confirmDelete', $log->id)
            ->assertSet('confirmingDelete', true)
            ->call('delete')
            ->assertDispatched('notify')
            ->assertDontSee('Info Hapus');

        $this->assertModelMissing($log);
    }

    public function test_super_admin_can_delete_every_entry_matching_the_filters_and_the_purge_is_logged(): void
    {
        $kelasA = $this->kelas(3, ['nama' => 'TI-A']);
        $kelasB = $this->kelas(3, ['nama' => 'TI-B']);
        Informasi::factory()->count(2)->create(['kelas_id' => $kelasA->id]);
        Informasi::factory()->create(['kelas_id' => $kelasB->id]);
        $superAdmin = $this->superAdmin(['name' => 'Super']);

        $sebelum = ActivityLog::query()->count();
        $milikA = ActivityLog::query()->where('kelas_id', $kelasA->id)->count();
        $this->assertGreaterThan(0, $milikA);

        Livewire::actingAs($superAdmin)
            ->test(LogIndex::class)
            ->set('kelasId', (string) $kelasA->id)
            ->assertSee("Hapus {$milikA} entri")
            ->call('confirmDeleteFiltered')
            ->assertSet('confirmingDeleteFiltered', true)
            ->assertSee('kelas TI-A')
            ->call('deleteFiltered')
            ->assertSet('confirmingDeleteFiltered', false)
            ->assertDispatched('notify');

        // Entries of TI-A are gone except the new purge record; TI-B is untouched.
        $purge = ActivityLog::query()->where('modul', ModulLog::LogAktivitas->value)->where('aksi', AksiLog::Hapus->value)->firstOrFail();
        $this->assertSame($superAdmin->id, $purge->user_id);
        $this->assertSame($kelasA->id, $purge->kelas_id);
        $this->assertSame("{$milikA} entri log (kelas TI-A)", $purge->subjek_label);
        $this->assertSame(1, ActivityLog::query()->where('kelas_id', $kelasA->id)->count());
        $this->assertSame($sebelum - $milikA + 1, ActivityLog::query()->count());
    }

    public function test_admin_kelas_cannot_delete_log_entries(): void
    {
        $kelas = $this->kelas();
        Informasi::factory()->create(['kelas_id' => $kelas->id, 'judul' => 'Info Tetap']);
        $log = ActivityLog::query()->where('subjek_label', 'Info Tetap')->firstOrFail();
        $admin = $this->admin($kelas);

        Livewire::actingAs($admin)
            ->test(LogIndex::class)
            ->assertSee('Info Tetap')
            ->assertDontSee('Hapus entri')
            ->assertDontSee('Hapus '.ActivityLog::query()->count().' entri')
            ->call('confirmDelete', $log->id)
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test(LogIndex::class)
            ->call('deleteFiltered')
            ->assertForbidden();

        $this->assertModelExists($log);
    }
}
