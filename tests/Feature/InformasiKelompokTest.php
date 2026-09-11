<?php

namespace Tests\Feature;

use App\Livewire\Informasi\Index as InformasiIndex;
use App\Livewire\KategoriInformasi\Index as KategoriIndex;
use App\Livewire\Kelompok\Index as KelompokIndex;
use App\Models\Informasi;
use App\Models\KategoriInformasi;
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
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $pembuat = $this->mahasiswa($kelas);
        $teman = $this->mahasiswa($kelas);
        $orangLain = $this->mahasiswa($this->kelas());

        Livewire::actingAs($pembuat)
            ->test(KelompokIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'Kelompok 1')
            ->set('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->set('form.anggota', [$pembuat->id, $orangLain->id])
            ->call('save')
            ->assertHasErrors(['form.anggota.1']);

        Livewire::actingAs($pembuat)
            ->test(KelompokIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'Kelompok 1')
            ->set('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->set('form.anggota', [$pembuat->id, $teman->id])
            ->set('form.ketua_id', (string) $teman->id)
            ->call('save')
            ->assertHasNoErrors();

        $kelompok = Kelompok::query()->where('nama', 'Kelompok 1')->firstOrFail();

        $this->assertCount(2, $kelompok->anggota);
        $this->assertTrue((bool) $kelompok->anggota->firstWhere('id', $teman->id)->pivot->is_ketua);
        $this->assertFalse((bool) $kelompok->anggota->firstWhere('id', $pembuat->id)->pivot->is_ketua);
    }
}
