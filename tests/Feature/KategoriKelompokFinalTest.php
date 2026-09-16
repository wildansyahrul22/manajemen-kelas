<?php

namespace Tests\Feature;

use App\Livewire\KategoriKelompok\Index as KategoriKelompokIndex;
use App\Livewire\Kelompok\Index as KelompokIndex;
use App\Models\KategoriKelompok;
use App\Models\Kelompok;
use App\Models\MataKuliah;
use Livewire\Livewire;
use Tests\TestCase;

class KategoriKelompokFinalTest extends TestCase
{
    public function test_only_admin_kelas_and_super_admin_can_mark_a_kategori_final(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $mahasiswa = $this->mahasiswa($kelas);
        $kategori = KategoriKelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'created_by' => $mahasiswa->id]);

        // Not even the mahasiswa who created the kategori may lock it.
        Livewire::actingAs($mahasiswa)
            ->test(KategoriKelompokIndex::class)
            ->call('toggleFinal', $kategori->id)
            ->assertForbidden();

        $this->assertFalse($kategori->fresh()->isFinal());

        Livewire::actingAs($this->admin($kelas))
            ->test(KategoriKelompokIndex::class)
            ->call('toggleFinal', $kategori->id)
            ->assertHasNoErrors();

        $this->assertTrue($kategori->fresh()->isFinal());

        // The same action unlocks it again.
        Livewire::actingAs($this->superAdmin())
            ->test(KategoriKelompokIndex::class)
            ->call('toggleFinal', $kategori->id);

        $this->assertFalse($kategori->fresh()->isFinal());
    }

    public function test_a_final_kategori_freezes_its_kelompok_for_everyone(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $kategori = KategoriKelompok::factory()->final()->create(['mata_kuliah_id' => $mataKuliah->id]);
        $mahasiswa = $this->mahasiswa($kelas);
        $kelompok = Kelompok::factory()->create([
            'mata_kuliah_id' => $mataKuliah->id,
            'kategori_kelompok_id' => $kategori->id,
            'created_by' => $mahasiswa->id,
        ]);

        foreach ([$mahasiswa, $this->admin($kelas), $this->superAdmin()] as $user) {
            Livewire::actingAs($user)->test(KelompokIndex::class)->call('openEdit', $kelompok->id)->assertForbidden();
            Livewire::actingAs($user)->test(KelompokIndex::class)->call('confirmDelete', $kelompok->id)->assertForbidden();
            Livewire::actingAs($user)->test(KelompokIndex::class)->call('openCreate', $kategori->id)->assertForbidden();
        }

        $this->assertModelExists($kelompok);
    }

    public function test_a_kelompok_cannot_be_created_in_or_moved_into_a_final_kategori(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $terbuka = KategoriKelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id]);
        $final = KategoriKelompok::factory()->final()->create(['mata_kuliah_id' => $mataKuliah->id, 'nama' => 'Project Akhir']);
        $admin = $this->admin($kelas);
        $anggota = $this->mahasiswa($kelas);

        Livewire::actingAs($admin)
            ->test(KelompokIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'Kelompok Baru')
            ->set('form.kategori_kelompok_id', (string) $final->id)
            ->set('form.anggota', [$anggota->id])
            ->call('save')
            ->assertHasErrors(['form.kategori_kelompok_id'])
            ->assertSee('Kategori Project Akhir sudah final');

        $this->assertSame(0, Kelompok::query()->count());

        // Moving an existing kelompok out of an open kategori into the final one is refused too.
        $kelompok = Kelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'kategori_kelompok_id' => $terbuka->id]);
        $kelompok->anggota()->attach($anggota->id);

        Livewire::actingAs($admin)
            ->test(KelompokIndex::class)
            ->call('openEdit', $kelompok->id)
            ->set('form.kategori_kelompok_id', (string) $final->id)
            ->call('save')
            ->assertHasErrors(['form.kategori_kelompok_id']);

        $this->assertSame($terbuka->id, $kelompok->fresh()->kategori_kelompok_id);
    }

    public function test_unlocking_the_kategori_lets_the_kelas_arrange_its_kelompok_again(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $kategori = KategoriKelompok::factory()->final()->create(['mata_kuliah_id' => $mataKuliah->id]);
        $kelompok = Kelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'kategori_kelompok_id' => $kategori->id]);
        $kelompok->anggota()->attach($this->mahasiswa($kelas)->id);

        Livewire::actingAs($this->admin($kelas))
            ->test(KategoriKelompokIndex::class)
            ->call('toggleFinal', $kategori->id);

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(KelompokIndex::class)
            ->call('openEdit', $kelompok->id)
            ->set('form.nama', 'Kelompok Disusun Ulang')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Kelompok Disusun Ulang', $kelompok->fresh()->nama);
    }

    public function test_a_final_kategori_can_only_be_edited_or_deleted_by_admin_kelas(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $mahasiswa = $this->mahasiswa($kelas);
        $kategori = KategoriKelompok::factory()->final()->create(['mata_kuliah_id' => $mataKuliah->id, 'created_by' => $mahasiswa->id]);

        Livewire::actingAs($mahasiswa)->test(KategoriKelompokIndex::class)->call('openEdit', $kategori->id)->assertForbidden();
        Livewire::actingAs($mahasiswa)->test(KategoriKelompokIndex::class)->call('confirmDelete', $kategori->id)->assertForbidden();

        Livewire::actingAs($this->admin($kelas))
            ->test(KategoriKelompokIndex::class)
            ->call('openEdit', $kategori->id)
            ->set('form.nama', 'Project Akhir (Final)')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Project Akhir (Final)', $kategori->fresh()->nama);
    }
}
