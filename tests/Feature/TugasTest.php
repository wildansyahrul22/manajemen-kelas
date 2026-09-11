<?php

namespace Tests\Feature;

use App\Livewire\Tugas\Index;
use App\Models\MataKuliah;
use App\Models\Semester;
use App\Models\Tugas;
use Livewire\Livewire;
use Tests\TestCase;

class TugasTest extends TestCase
{
    public function test_list_only_shows_tugas_of_the_active_semester_of_own_kelas(): void
    {
        $kelas = $this->kelas(3);
        $kelasLain = $this->kelas(3);

        $aktif = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $lama = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'semester_id' => Semester::query()->where('nomor', 2)->value('id')]);
        $lain = MataKuliah::factory()->create(['kelas_id' => $kelasLain->id]);

        Tugas::factory()->create(['mata_kuliah_id' => $aktif->id, 'nama' => 'Tugas Semester Ini']);
        Tugas::factory()->create(['mata_kuliah_id' => $lama->id, 'nama' => 'Tugas Semester Lalu']);
        Tugas::factory()->create(['mata_kuliah_id' => $lain->id, 'nama' => 'Tugas Kelas Lain']);

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(Index::class)
            ->assertSee('Tugas Semester Ini')
            ->assertDontSee('Tugas Semester Lalu')
            ->assertDontSee('Tugas Kelas Lain');
    }

    public function test_status_filter_and_search_work(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);

        Tugas::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'nama' => 'Tugas Aktif']);
        Tugas::factory()->lewat()->create(['mata_kuliah_id' => $mataKuliah->id, 'nama' => 'Tugas Lewat']);

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(Index::class)
            ->set('status', 'aktif')
            ->assertSee('Tugas Aktif')
            ->assertDontSee('Tugas Lewat')
            ->set('status', '')
            ->set('search', 'Lewat')
            ->assertSee('Tugas Lewat')
            ->assertDontSee('Tugas Aktif');
    }

    public function test_admin_can_create_edit_and_delete_tugas(): void
    {
        $kelas = $this->kelas();
        $admin = $this->admin($kelas);
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openCreate')
            ->assertSet('showForm', true)
            ->set('form.nama', 'Laporan Praktikum')
            ->set('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->set('form.deadline', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertDispatched('notify');

        $tugas = Tugas::query()->where('nama', 'Laporan Praktikum')->firstOrFail();
        $this->assertSame($admin->id, $tugas->created_by);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openEdit', $tugas->id)
            ->assertSet('form.nama', 'Laporan Praktikum')
            ->set('form.nama', 'Laporan Praktikum 2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Laporan Praktikum 2', $tugas->fresh()->nama);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmDelete', $tugas->id)
            ->assertSet('confirmingDelete', true)
            ->call('delete');

        $this->assertModelMissing($tugas);
    }

    public function test_tugas_cannot_be_attached_to_mata_kuliah_of_another_kelas(): void
    {
        $kelas = $this->kelas();
        $mataKuliahLain = MataKuliah::factory()->create(['kelas_id' => $this->kelas()->id]);

        Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('openCreate')
            ->set('form.nama', 'Tugas Nyasar')
            ->set('form.mata_kuliah_id', (string) $mataKuliahLain->id)
            ->set('form.deadline', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasErrors(['form.mata_kuliah_id']);
    }

    public function test_mahasiswa_cannot_create_or_delete_tugas(): void
    {
        $kelas = $this->kelas();
        $tugas = Tugas::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $kelas->id])->id]);

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(Index::class)
            ->call('openCreate')
            ->assertForbidden();

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(Index::class)
            ->call('confirmDelete', $tugas->id)
            ->assertForbidden();

        $this->assertModelExists($tugas);
    }

    public function test_detail_page_is_scoped_to_own_kelas(): void
    {
        $kelas = $this->kelas();
        $tugas = Tugas::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $kelas->id])->id]);
        $tugasLain = Tugas::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $this->kelas()->id])->id]);

        $this->actingAs($this->mahasiswa($kelas))->get(route('tugas.show', $tugas))->assertOk()->assertSee($tugas->nama);
        $this->actingAs($this->mahasiswa($kelas))->get(route('tugas.show', $tugasLain))->assertForbidden();
    }
}
