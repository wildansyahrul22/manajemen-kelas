<?php

namespace Tests\Feature;

use App\Livewire\Tugas\Index;
use App\Models\KategoriKelompok;
use App\Models\Kelompok;
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

    public function test_link_pengumpulan_is_optional_must_be_a_full_url_and_is_shown_to_everyone(): void
    {
        $kelas = $this->kelas();
        $admin = $this->admin($kelas);
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);

        $component = Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openCreate')
            ->set('form.nama', 'Laporan Praktikum')
            ->set('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->set('form.deadline', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('form.link_pengumpulan', 'forms.gle/abc')
            ->call('save')
            ->assertHasErrors(['form.link_pengumpulan' => 'url'])
            ->assertSee('Link pengumpulan harus berupa alamat lengkap yang diawali http:// atau https://.');

        $component
            ->set('form.link_pengumpulan', ' https://forms.gle/abc ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Kumpulkan');

        $tugas = Tugas::query()->where('nama', 'Laporan Praktikum')->firstOrFail();
        $this->assertSame('https://forms.gle/abc', $tugas->link_pengumpulan);

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('tugas.show', $tugas))
            ->assertOk()
            ->assertSee('Kumpulkan Tugas')
            ->assertSee('https://forms.gle/abc')
            ->assertSee('Kumpulkan lewat forms.gle');

        // Clearing the field removes the link again.
        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openEdit', $tugas->id)
            ->assertSet('form.link_pengumpulan', 'https://forms.gle/abc')
            ->set('form.link_pengumpulan', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($tugas->fresh()->link_pengumpulan);

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('tugas.show', $tugas))
            ->assertOk()
            ->assertDontSee('Kumpulkan Tugas')
            ->assertSee('Belum ada link pengumpulan');
    }

    public function test_tugas_kelompok_needs_a_kategori_of_the_same_mata_kuliah(): void
    {
        $kelas = $this->kelas();
        $admin = $this->admin($kelas);
        $web = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Pemrograman Web']);
        $basisData = MataKuliah::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Basis Data']);
        $kategoriWeb = KategoriKelompok::factory()->create(['mata_kuliah_id' => $web->id, 'nama' => 'Project Akhir']);
        $kategoriBasisData = KategoriKelompok::factory()->create(['mata_kuliah_id' => $basisData->id, 'nama' => 'Presentasi']);

        $component = Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openCreate')
            ->set('form.nama', 'Project Akhir Web')
            ->set('form.mata_kuliah_id', (string) $web->id)
            ->set('form.deadline', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('form.tugas_kelompok', true)
            ->assertSee('Kategori kelompok')
            ->call('save')
            ->assertHasErrors(['form.kategori_kelompok_id' => 'required'])
            ->assertSee('Pilih kategori kelompok untuk tugas kelompok ini.');

        // Only kategori of the chosen mata kuliah are offered, and others are rejected.
        $this->assertSame([$kategoriWeb->id => 'Project Akhir'], $component->get('kategoriKelompokOptions')->all());

        $component
            ->set('form.kategori_kelompok_id', (string) $kategoriBasisData->id)
            ->call('save')
            ->assertHasErrors(['form.kategori_kelompok_id' => 'exists'])
            ->assertSee('Kategori kelompok harus berasal dari mata kuliah yang sama dengan tugas.');

        $component
            ->set('form.kategori_kelompok_id', (string) $kategoriWeb->id)
            ->call('save')
            ->assertHasNoErrors();

        $tugas = Tugas::query()->where('nama', 'Project Akhir Web')->firstOrFail();
        $this->assertSame($kategoriWeb->id, $tugas->kategori_kelompok_id);

        // Switching mata kuliah in the form drops a kategori that no longer matches.
        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openEdit', $tugas->id)
            ->assertSet('form.tugas_kelompok', true)
            ->assertSet('form.kategori_kelompok_id', (string) $kategoriWeb->id)
            ->set('form.mata_kuliah_id', (string) $basisData->id)
            ->assertSet('form.kategori_kelompok_id', '');

        // Unticking turns it back into an individual tugas.
        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openEdit', $tugas->id)
            ->set('form.tugas_kelompok', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($tugas->fresh()->kategori_kelompok_id);
    }

    public function test_detail_of_a_tugas_kelompok_shows_the_viewer_own_kelompok(): void
    {
        $kelas = $this->kelas();
        $web = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $kategori = KategoriKelompok::factory()->create(['mata_kuliah_id' => $web->id, 'nama' => 'Project Akhir']);
        $siti = $this->mahasiswa($kelas, ['npm' => '24010001', 'name' => 'Siti']);
        $budi = $this->mahasiswa($kelas, ['npm' => '24010002', 'name' => 'Budi']);
        $cici = $this->mahasiswa($kelas, ['name' => 'Cici']);
        $kelompok = Kelompok::factory()->create(['mata_kuliah_id' => $web->id, 'kategori_kelompok_id' => $kategori->id, 'nama' => 'Kelompok 1']);
        $kelompok->anggota()->attach([$siti->id => ['is_ketua' => true], $budi->id => ['is_ketua' => false]]);
        Kelompok::factory()->create(['mata_kuliah_id' => $web->id, 'kategori_kelompok_id' => $kategori->id, 'nama' => 'Kelompok 2']);
        $tugas = Tugas::factory()->create(['mata_kuliah_id' => $web->id, 'kategori_kelompok_id' => $kategori->id, 'nama' => 'Project Akhir Web']);

        $this->actingAs($budi)
            ->get(route('tugas.show', $tugas))
            ->assertOk()
            ->assertSee('Tugas kelompok · Project Akhir')
            ->assertSee('Kelompok Saya')
            ->assertSee('Kategori Project Akhir · 2 kelompok')
            ->assertSeeInOrder(['Kelompok 1', 'Ketua: Siti', 'Siti', '24010001', 'Ketua', 'Budi (saya)', '24010002'])
            ->assertDontSee('Kelompok 2')
            ->assertDontSee('Kamu belum masuk kelompok');

        $this->actingAs($cici)
            ->get(route('tugas.show', $tugas))
            ->assertOk()
            ->assertSee('Kamu belum masuk kelompok')
            ->assertSee('Lihat kelompok kategori ini')
            ->assertDontSee('Budi (saya)');

        Livewire::actingAs($budi)
            ->test(Index::class)
            ->assertSee('Kelompok')
            ->assertSee('Tugas kelompok · Project Akhir');
    }
}
