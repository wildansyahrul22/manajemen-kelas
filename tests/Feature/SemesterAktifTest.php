<?php

namespace Tests\Feature;

use App\Livewire\SemesterAktif\Index;
use App\Models\Semester;
use Livewire\Livewire;
use Tests\TestCase;

class SemesterAktifTest extends TestCase
{
    public function test_admin_can_change_active_semester_of_own_kelas(): void
    {
        $kelas = $this->kelas(3);
        $semester4 = Semester::query()->where('nomor', 4)->firstOrFail();

        Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            // The combobox can only bind to a key that already exists, seeded with the current semester.
            ->assertSet('pilihan', [$kelas->id => (string) $kelas->semester_aktif_id])
            ->set("pilihan.{$kelas->id}", (string) $semester4->id)
            ->call('simpan', $kelas->id)
            ->assertRedirect(route('semester-aktif.index'));

        $this->assertSame($semester4->id, $kelas->fresh()->semester_aktif_id);
    }

    public function test_admin_cannot_change_another_kelas(): void
    {
        $kelas = $this->kelas(3);
        $kelasLain = $this->kelas(1);
        $semester2 = Semester::query()->where('nomor', 2)->firstOrFail();

        Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->assertSee($kelas->nama)
            ->assertDontSee($kelasLain->nama)
            ->set("pilihan.{$kelasLain->id}", (string) $semester2->id)
            ->call('simpan', $kelasLain->id)
            ->assertForbidden();

        $this->assertNotSame($semester2->id, $kelasLain->fresh()->semester_aktif_id);
    }

    public function test_super_admin_sees_and_changes_all_kelas(): void
    {
        $kelasA = $this->kelas(1, ['nama' => 'TI-1A']);
        $kelasB = $this->kelas(1, ['nama' => 'TI-1B']);
        $semester5 = Semester::query()->where('nomor', 5)->firstOrFail();

        Livewire::actingAs($this->superAdmin())
            ->test(Index::class)
            ->assertSee('TI-1A')
            ->assertSee('TI-1B')
            ->set("pilihan.{$kelasB->id}", (string) $semester5->id)
            ->call('simpan', $kelasB->id);

        $this->assertSame($semester5->id, $kelasB->fresh()->semester_aktif_id);
        $this->assertNotSame($semester5->id, $kelasA->fresh()->semester_aktif_id);
    }

    public function test_there_are_fourteen_semesters(): void
    {
        $this->assertSame(14, Semester::query()->count());
        $this->assertSame(range(1, 14), Semester::query()->orderBy('nomor')->pluck('nomor')->all());
    }
}
