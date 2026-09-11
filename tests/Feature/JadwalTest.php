<?php

namespace Tests\Feature;

use App\Livewire\Jadwal\Index;
use App\Models\JadwalKelas;
use App\Models\MataKuliah;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalTest extends TestCase
{
    public function test_admin_can_add_several_mata_kuliah_on_one_hari_at_once(): void
    {
        $kelas = $this->kelas();
        $pagi = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web']);
        $siang = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Basis Data']);

        $component = Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('openCreate', 1)
            ->assertSet('showForm', true)
            ->assertSet('form.hari', '1')
            ->call('addSesi');

        [$keyA, $keyB] = array_keys($component->get('form.sesi'));

        $component
            ->set("form.sesi.{$keyB}.mata_kuliah_id", (string) $pagi->id)
            ->set("form.sesi.{$keyB}.jam_mulai", '08:00')
            ->set("form.sesi.{$keyB}.jam_selesai", '09:40')
            ->set("form.sesi.{$keyB}.ruangan", 'Lab 2')
            ->set("form.sesi.{$keyA}.mata_kuliah_id", (string) $siang->id)
            ->set("form.sesi.{$keyA}.jam_mulai", '13:00')
            ->set("form.sesi.{$keyA}.jam_selesai", '14:40')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertDispatched('notify')
            ->assertSeeInOrder(['Pemrograman Web', 'Basis Data']);

        $this->assertSame(2, JadwalKelas::query()->where('hari', 1)->count());
        $this->assertDatabaseHas('jadwal_kelas', ['mata_kuliah_id' => $pagi->id, 'hari' => 1, 'ruangan' => 'Lab 2']);
        $this->assertDatabaseHas('jadwal_kelas', ['mata_kuliah_id' => $siang->id, 'hari' => 1, 'ruangan' => null]);
    }

    public function test_each_sesi_is_validated_on_its_own_row(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $mataKuliahLain = MataKuliah::factory()->create(['kelas_id' => $this->kelas()->id]);

        $component = Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('openCreate')
            ->call('addSesi');

        [$keyA, $keyB] = array_keys($component->get('form.sesi'));

        $component
            ->set('form.hari', '2')
            ->set("form.sesi.{$keyA}.mata_kuliah_id", (string) $mataKuliah->id)
            ->set("form.sesi.{$keyA}.jam_mulai", '10:00')
            ->set("form.sesi.{$keyA}.jam_selesai", '09:00')
            ->set("form.sesi.{$keyB}.mata_kuliah_id", (string) $mataKuliahLain->id)
            ->set("form.sesi.{$keyB}.jam_mulai", '13:00')
            ->set("form.sesi.{$keyB}.jam_selesai", '14:40')
            ->call('save')
            ->assertHasErrors(["form.sesi.{$keyA}.jam_selesai", "form.sesi.{$keyB}.mata_kuliah_id"])
            ->assertHasNoErrors(["form.sesi.{$keyA}.mata_kuliah_id", "form.sesi.{$keyB}.jam_selesai"]);

        $this->assertSame(0, JadwalKelas::query()->count());
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

    public function test_admin_can_edit_and_delete_a_single_jadwal(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $jadwal = JadwalKelas::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'hari' => 3, 'jam_mulai' => '08:00', 'jam_selesai' => '09:40']);

        $component = Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('openEdit', $jadwal->id)
            ->assertSet('form.hari', '3');

        $sesi = $component->get('form.sesi');
        $this->assertCount(1, $sesi);
        $key = array_key_first($sesi);
        $this->assertSame('08:00', $sesi[$key]['jam_mulai']);

        $component
            ->set('form.hari', '4')
            ->set("form.sesi.{$key}.jam_mulai", '10:00')
            ->set("form.sesi.{$key}.jam_selesai", '11:40')
            ->call('save')
            ->assertHasNoErrors();

        $jadwal->refresh();
        $this->assertSame(4, $jadwal->hari->value);
        $this->assertSame('10:00 - 11:40', $jadwal->jam());
        $this->assertSame(1, JadwalKelas::query()->count());

        Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('confirmDelete', $jadwal->id)
            ->assertSet('confirmingDelete', true)
            ->call('delete');

        $this->assertModelMissing($jadwal);
    }

    public function test_mahasiswa_cannot_open_the_form_or_add_rows(): void
    {
        $kelas = $this->kelas();
        MataKuliah::factory()->create(['kelas_id' => $kelas->id]);

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(Index::class)
            ->call('openCreate')
            ->assertForbidden();

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(Index::class)
            ->call('addSesi')
            ->assertForbidden();
    }
}
