<?php

namespace Tests\Feature;

use App\Enums\AksiLog;
use App\Enums\ModulLog;
use App\Livewire\Kampus\Index as KampusIndex;
use App\Livewire\Kelas\Index as KelasIndex;
use App\Models\ActivityLog;
use App\Models\Kampus;
use App\Models\Kelas;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class KampusTest extends TestCase
{
    public function test_the_migration_moves_every_existing_kelas_into_the_first_kampus(): void
    {
        Artisan::call('migrate:rollback', ['--step' => 1]);

        $semesterId = DB::table('semesters')->where('nomor', 1)->value('id');
        DB::table('kelas')->insert([
            ['nama' => 'TI-1A', 'angkatan' => 2025, 'semester_aktif_id' => $semesterId, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'TI-1B', 'angkatan' => 2025, 'semester_aktif_id' => $semesterId, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Artisan::call('migrate');

        $kampus = Kampus::query()->sole();
        $this->assertSame(Kampus::AWAL, $kampus->nama);
        $this->assertSame(2, $kampus->kelas()->count());
        $this->assertSame(0, Kelas::query()->whereNull('kampus_id')->count());
    }

    public function test_the_migration_creates_no_kampus_when_there_is_no_kelas_yet(): void
    {
        Artisan::call('migrate:rollback', ['--step' => 1]);
        Artisan::call('migrate');

        $this->assertSame(0, Kampus::query()->count());
    }

    public function test_a_new_kelas_is_stored_in_the_chosen_kampus(): void
    {
        $lain = Kampus::factory()->create(['nama' => 'Universitas Lain']);

        Livewire::actingAs($this->superAdmin())
            ->test(KelasIndex::class)
            ->call('openCreate')
            ->assertSet('form.kampus_id', (string) Kampus::query()->where('nama', Kampus::AWAL)->value('id'))
            ->set('form.kampus_id', (string) $lain->id)
            ->set('form.nama', 'TI-7A')
            ->set('form.angkatan', '2026')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($lain->id, Kelas::query()->where('nama', 'TI-7A')->sole()->kampus_id);
    }

    public function test_a_kelas_requires_an_existing_kampus(): void
    {
        $component = Livewire::actingAs($this->superAdmin())
            ->test(KelasIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'TI-7A')
            ->set('form.angkatan', '2026');

        $component->set('form.kampus_id', '')->call('save')->assertHasErrors(['form.kampus_id' => 'required']);
        $component->set('form.kampus_id', '999')->call('save')->assertHasErrors(['form.kampus_id' => 'exists']);

        $this->assertSame(0, Kelas::query()->count());
    }

    public function test_editing_a_kelas_can_move_it_to_another_kampus(): void
    {
        $kelas = $this->kelas();
        $lain = Kampus::factory()->create();

        Livewire::actingAs($this->superAdmin())
            ->test(KelasIndex::class)
            ->call('openEdit', $kelas->id)
            ->assertSet('form.kampus_id', (string) $kelas->kampus_id)
            ->set('form.kampus_id', (string) $lain->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($lain->id, $kelas->fresh()->kampus_id);
    }

    public function test_a_kampus_that_still_has_kelas_cannot_be_deleted(): void
    {
        $kelas = $this->kelas();

        $this->expectException(QueryException::class);

        $kelas->kampus->delete();
    }

    public function test_the_sidebar_shows_the_kampus_menu_only_to_super_admin(): void
    {
        $kelas = $this->kelas();

        $this->actingAs($this->superAdmin())->get(route('dashboard'))->assertSee(route('kampus.index'));
        $this->actingAs($this->admin($kelas))->get(route('dashboard'))->assertDontSee(route('kampus.index'));
        $this->actingAs($this->mahasiswa($kelas))->get(route('dashboard'))->assertDontSee(route('kampus.index'));
    }

    public function test_super_admin_can_create_a_kampus(): void
    {
        Livewire::actingAs($this->superAdmin())
            ->test(KampusIndex::class)
            ->call('openCreate')
            ->assertSet('showForm', true)
            ->set('form.nama', 'Universitas Lain')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertSee('Universitas Lain');

        $this->assertDatabaseHas('kampus', ['nama' => 'Universitas Lain']);
        $this->assertDatabaseHas('activity_log', ['modul' => ModulLog::Kampus->value, 'aksi' => AksiLog::Buat->value, 'subjek_label' => 'Universitas Lain']);
    }

    public function test_kampus_name_is_required_and_unique(): void
    {
        $component = Livewire::actingAs($this->superAdmin())->test(KampusIndex::class)->call('openCreate');

        $component->set('form.nama', '')->call('save')->assertHasErrors(['form.nama' => 'required']);
        $component->set('form.nama', Kampus::AWAL)->call('save')->assertHasErrors(['form.nama' => 'unique']);
        $component->set('form.nama', str_repeat('a', 151))->call('save')->assertHasErrors(['form.nama' => 'max']);

        $this->assertSame(1, Kampus::query()->count());
    }

    public function test_super_admin_can_rename_a_kampus(): void
    {
        $kampus = Kampus::factory()->create(['nama' => 'Universitas Lama']);

        Livewire::actingAs($this->superAdmin())
            ->test(KampusIndex::class)
            ->call('openEdit', $kampus->id)
            ->assertSet('form.nama', 'Universitas Lama')
            ->set('form.nama', 'Universitas Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Universitas Baru', $kampus->fresh()->nama);
        $this->assertDatabaseHas('activity_log', ['modul' => ModulLog::Kampus->value, 'aksi' => AksiLog::Ubah->value, 'subjek_id' => $kampus->id]);
    }

    public function test_super_admin_can_delete_an_empty_kampus(): void
    {
        $kampus = Kampus::factory()->create();

        Livewire::actingAs($this->superAdmin())
            ->test(KampusIndex::class)
            ->call('confirmDelete', $kampus->id)
            ->assertSet('confirmingDelete', true)
            ->call('delete')
            ->assertSet('confirmingDelete', false);

        $this->assertDatabaseMissing('kampus', ['id' => $kampus->id]);
    }

    public function test_deleting_a_kampus_that_still_has_kelas_is_refused_with_a_message(): void
    {
        $kelas = $this->kelas();

        Livewire::actingAs($this->superAdmin())
            ->test(KampusIndex::class)
            ->call('confirmDelete', $kelas->kampus_id)
            ->call('delete')
            ->assertSet('confirmingDelete', false)
            ->assertDispatched('notify');

        $this->assertDatabaseHas('kampus', ['id' => $kelas->kampus_id]);
        $this->assertSame(0, ActivityLog::query()->where('modul', ModulLog::Kampus->value)->where('aksi', AksiLog::Hapus->value)->count());
    }

    public function test_admin_kelas_cannot_open_the_kampus_page(): void
    {
        Livewire::actingAs($this->admin($this->kelas()))
            ->test(KampusIndex::class)
            ->assertForbidden();
    }

    public function test_the_kampus_list_can_be_searched(): void
    {
        Kampus::factory()->create(['nama' => 'Universitas Terpencil']);
        Kampus::factory()->create(['nama' => 'Universitas Tersembunyi']);

        Livewire::actingAs($this->superAdmin())
            ->test(KampusIndex::class)
            ->set('search', 'Terpencil')
            ->assertSee('Universitas Terpencil')
            ->assertDontSee('Universitas Tersembunyi');
    }

    public function test_the_kelas_list_can_be_filtered_by_kampus(): void
    {
        $lain = Kampus::factory()->create(['nama' => 'Universitas Lain']);
        $this->kelas(attributes: ['nama' => 'TI-AWAL']);
        $this->kelas(attributes: ['nama' => 'TI-LAIN', 'kampus_id' => $lain->id]);

        $component = Livewire::actingAs($this->superAdmin())->test(KelasIndex::class);

        $component->assertSee('TI-AWAL')->assertSee('TI-LAIN');
        $component->set('kampusId', (string) $lain->id)->assertSee('TI-LAIN')->assertDontSee('TI-AWAL');
        $component->set('kampusId', '')->assertSee('TI-AWAL')->assertSee('TI-LAIN');
    }
}
