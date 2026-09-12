<?php

namespace Tests\Feature;

use App\Livewire\Informasi\Index as InformasiIndex;
use App\Livewire\KategoriInformasi\Index as KategoriIndex;
use App\Livewire\KategoriKelompok\Index as KategoriKelompokIndex;
use App\Livewire\Kelompok\Index as KelompokIndex;
use App\Models\Informasi;
use App\Models\KategoriInformasi;
use App\Models\KategoriKelompok;
use App\Models\Kelompok;
use App\Models\MataKuliah;
use Livewire\Livewire;
use Tests\TestCase;

class InformasiKelompokTest extends TestCase
{
    public function test_mahasiswa_can_create_informasi_and_only_edit_their_own(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);
        $penulis = $this->mahasiswa($kelas);
        $lain = $this->mahasiswa($kelas);

        Livewire::actingAs($penulis)
            ->test(InformasiIndex::class)
            ->call('openCreate')
            ->set('form.judul', 'Kuis dadakan')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Besok ada kuis, siapkan diri.')
            ->call('save')
            ->assertHasNoErrors();

        $informasi = Informasi::query()->where('judul', 'Kuis dadakan')->firstOrFail();
        $this->assertSame($penulis->id, $informasi->created_by);

        Livewire::actingAs($lain)->test(InformasiIndex::class)->call('openEdit', $informasi->id)->assertForbidden();
        Livewire::actingAs($lain)->test(InformasiIndex::class)->call('togglePin', $informasi->id)->assertForbidden();

        Livewire::actingAs($this->admin($kelas))
            ->test(InformasiIndex::class)
            ->call('togglePin', $informasi->id);

        $this->assertTrue($informasi->fresh()->is_pinned);
    }

    public function test_kategori_in_use_cannot_be_deleted(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);
        Informasi::factory()->create(['kelas_id' => $kelas->id, 'kategori_informasi_id' => $kategori->id]);

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(KategoriIndex::class)
            ->call('confirmDelete', $kategori->id)
            ->call('delete')
            ->assertDispatched('notify', type: 'error');

        $this->assertModelExists($kategori);
    }

    public function test_kelompok_members_must_be_students_of_the_kelas(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriKelompok::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $kelas->id])->id]);
        $pembuat = $this->mahasiswa($kelas);
        $teman = $this->mahasiswa($kelas);
        $orangLain = $this->mahasiswa($this->kelas());

        Livewire::actingAs($pembuat)
            ->test(KelompokIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'Kelompok 1')
            ->set('form.kategori_kelompok_id', (string) $kategori->id)
            ->set('form.anggota', [$pembuat->id, $orangLain->id])
            ->call('save')
            ->assertHasErrors(['form.anggota.1']);

        Livewire::actingAs($pembuat)
            ->test(KelompokIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'Kelompok 1')
            ->set('form.kategori_kelompok_id', (string) $kategori->id)
            ->set('form.anggota', [$pembuat->id, $teman->id])
            ->set('form.ketua_id', (string) $teman->id)
            ->call('save')
            ->assertHasNoErrors();

        $kelompok = Kelompok::query()->where('nama', 'Kelompok 1')->firstOrFail();

        $this->assertSame($kategori->mata_kuliah_id, $kelompok->mata_kuliah_id);
        $this->assertCount(2, $kelompok->anggota);
        $this->assertTrue((bool) $kelompok->anggota->firstWhere('id', $teman->id)->pivot->is_ketua);
        $this->assertFalse((bool) $kelompok->anggota->firstWhere('id', $pembuat->id)->pivot->is_ketua);
    }

    public function test_kelompok_requires_a_kategori_of_the_own_kelas(): void
    {
        $kelas = $this->kelas();
        $pembuat = $this->mahasiswa($kelas);
        $kategoriKelasLain = KategoriKelompok::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $this->kelas()->id])->id]);

        Livewire::actingAs($pembuat)
            ->test(KelompokIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'Kelompok Tanpa Kategori')
            ->set('form.anggota', [$pembuat->id])
            ->call('save')
            ->assertHasErrors(['form.kategori_kelompok_id' => 'required']);

        Livewire::actingAs($pembuat)
            ->test(KelompokIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'Kelompok Nyasar')
            ->set('form.kategori_kelompok_id', (string) $kategoriKelasLain->id)
            ->set('form.anggota', [$pembuat->id])
            ->call('save')
            ->assertHasErrors(['form.kategori_kelompok_id']);

        $this->assertSame(0, Kelompok::query()->count());
    }

    public function test_students_already_in_a_kelompok_of_the_kategori_are_hidden_and_rejected(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $projek = KategoriKelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id]);
        $presentasi = KategoriKelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id]);

        $admin = $this->admin($kelas);
        $sudahPunya = $this->mahasiswa($kelas, ['name' => 'Sudah Punya Kelompok']);
        $bebas = $this->mahasiswa($kelas, ['name' => 'Masih Bebas']);

        $kelompokLama = Kelompok::factory()->create(['kategori_kelompok_id' => $projek->id]);
        $kelompokLama->anggota()->attach($sudahPunya->id);

        $component = Livewire::actingAs($admin)
            ->test(KelompokIndex::class)
            ->call('openCreate')
            ->set('form.kategori_kelompok_id', (string) $projek->id);

        $this->assertEqualsCanonicalizing(
            [$admin->id, $bebas->id],
            $component->instance()->mahasiswaOptions->pluck('id')->all(),
        );

        // Same student is still free for another kategori.
        $component->set('form.kategori_kelompok_id', (string) $presentasi->id);
        $this->assertContains($sudahPunya->id, $component->instance()->mahasiswaOptions->pluck('id')->all());

        // Bypassing the list is caught by validation.
        $component
            ->set('form.kategori_kelompok_id', (string) $projek->id)
            ->set('form.nama', 'Kelompok 2')
            ->set('form.anggota', [$bebas->id, $sudahPunya->id])
            ->call('save')
            ->assertHasErrors(['form.anggota.1'])
            ->assertSee('Sudah Punya Kelompok sudah tergabung di kelompok lain');

        $this->assertSame(1, Kelompok::query()->count());
    }

    public function test_switching_kategori_drops_members_taken_in_the_new_kategori(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $projek = KategoriKelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id]);
        $presentasi = KategoriKelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id]);

        $terpakai = $this->mahasiswa($kelas);
        $bebas = $this->mahasiswa($kelas);
        Kelompok::factory()->create(['kategori_kelompok_id' => $projek->id])->anggota()->attach($terpakai->id);

        Livewire::actingAs($this->admin($kelas))
            ->test(KelompokIndex::class)
            ->call('openCreate')
            ->set('form.kategori_kelompok_id', (string) $presentasi->id)
            ->set('form.anggota', [$terpakai->id, $bebas->id])
            ->set('form.ketua_id', (string) $terpakai->id)
            ->set('form.kategori_kelompok_id', (string) $projek->id)
            ->assertSet('form.anggota', [$bebas->id])
            ->assertSet('form.ketua_id', '');
    }

    public function test_editing_keeps_own_members_selectable(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriKelompok::factory()->create(['mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $kelas->id])->id]);
        $anggota = $this->mahasiswa($kelas);
        $kelompok = Kelompok::factory()->create(['kategori_kelompok_id' => $kategori->id]);
        $kelompok->anggota()->attach($anggota->id, ['is_ketua' => true]);

        $component = Livewire::actingAs($this->admin($kelas))
            ->test(KelompokIndex::class)
            ->call('openEdit', $kelompok->id)
            ->assertSet('form.kategori_kelompok_id', (string) $kategori->id)
            ->assertSet('form.anggota', [$anggota->id])
            ->assertSet('form.ketua_id', (string) $anggota->id);

        $this->assertContains($anggota->id, $component->instance()->mahasiswaOptions->pluck('id')->all());

        $component->set('form.nama', 'Kelompok Baru')->call('save')->assertHasNoErrors();

        $this->assertSame('Kelompok Baru', $kelompok->fresh()->nama);
    }

    public function test_kategori_kelompok_crud_and_delete_guard(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $mataKuliahLain = MataKuliah::factory()->create(['kelas_id' => $this->kelas()->id]);
        $user = $this->mahasiswa($kelas);

        Livewire::actingAs($user)
            ->test(KategoriKelompokIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'Project Akhir')
            ->set('form.mata_kuliah_id', (string) $mataKuliahLain->id)
            ->call('save')
            ->assertHasErrors(['form.mata_kuliah_id']);

        Livewire::actingAs($user)
            ->test(KategoriKelompokIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'Project Akhir')
            ->set('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Project Akhir');

        $kategori = KategoriKelompok::query()->where('nama', 'Project Akhir')->firstOrFail();

        Livewire::actingAs($user)
            ->test(KategoriKelompokIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'Project Akhir')
            ->set('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->call('save')
            ->assertHasErrors(['form.nama' => 'unique']);

        Kelompok::factory()->create(['kategori_kelompok_id' => $kategori->id]);

        Livewire::actingAs($user)
            ->test(KategoriKelompokIndex::class)
            ->call('confirmDelete', $kategori->id)
            ->call('delete')
            ->assertDispatched('notify', type: 'error');

        $this->assertModelExists($kategori);
    }
}
