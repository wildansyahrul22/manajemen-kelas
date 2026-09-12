<?php

namespace Tests\Feature;

use App\Livewire\Informasi\Index as InformasiIndex;
use App\Models\Informasi;
use App\Models\KategoriInformasi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class InformasiLampiranTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Informasi::LAMPIRAN_DISK);
    }

    public function test_anyone_can_share_a_link_and_it_must_be_a_full_url(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);
        $mahasiswa = $this->mahasiswa($kelas);

        Livewire::actingAs($mahasiswa)
            ->test(InformasiIndex::class)
            ->call('openCreate')
            ->set('form.judul', 'Materi minggu 3')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Slide ada di Google Drive.')
            ->set('form.link', 'drive.google.com/abc')
            ->call('save')
            ->assertHasErrors(['form.link' => 'url']);

        Livewire::actingAs($mahasiswa)
            ->test(InformasiIndex::class)
            ->call('openCreate')
            ->set('form.judul', 'Materi minggu 3')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Slide ada di Google Drive.')
            ->set('form.link', 'https://drive.google.com/file/d/abc/view')
            ->call('save')
            ->assertHasNoErrors();

        $informasi = Informasi::query()->where('judul', 'Materi minggu 3')->firstOrFail();
        $this->assertSame('https://drive.google.com/file/d/abc/view', $informasi->link);

        $this->actingAs($mahasiswa)
            ->get(route('informasi.show', $informasi))
            ->assertOk()
            ->assertSee('https://drive.google.com/file/d/abc/view');
    }

    public function test_super_admin_can_attach_a_file_and_members_can_open_it(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);
        $superAdmin = $this->superAdmin();

        Livewire::actingAs($superAdmin)
            ->test(InformasiIndex::class)
            ->assertSet('canUpload', true)
            ->call('openCreate')
            ->set('form.judul', 'Jadwal UAS')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Terlampir.')
            ->set('form.lampiran', UploadedFile::fake()->image('jadwal-uas.png', 800, 600))
            ->call('save')
            ->assertHasNoErrors();

        $informasi = Informasi::query()->where('judul', 'Jadwal UAS')->firstOrFail();

        $this->assertSame('jadwal-uas.png', $informasi->lampiran_nama);
        $this->assertTrue($informasi->lampiranIsImage());
        Storage::disk(Informasi::LAMPIRAN_DISK)->assertExists($informasi->lampiran_path);

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('informasi.lampiran', $informasi))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->actingAs($this->mahasiswa($this->kelas()))
            ->get(route('informasi.lampiran', $informasi))
            ->assertForbidden();
    }

    public function test_lampiran_is_validated_and_removed_together_with_the_informasi(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);
        $superAdmin = $this->superAdmin();

        Livewire::actingAs($superAdmin)
            ->test(InformasiIndex::class)
            ->call('openCreate')
            ->set('form.judul', 'File aneh')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Coba.')
            ->set('form.lampiran', UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream'))
            ->call('save')
            ->assertHasErrors(['form.lampiran']);

        Livewire::actingAs($superAdmin)
            ->test(InformasiIndex::class)
            ->call('openCreate')
            ->set('form.judul', 'File besar')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Coba.')
            ->set('form.lampiran', UploadedFile::fake()->create('besar.pdf', Informasi::LAMPIRAN_MAKS_KB + 1, 'application/pdf'))
            ->call('save')
            ->assertHasErrors(['form.lampiran' => 'max']);

        $component = Livewire::actingAs($superAdmin)
            ->test(InformasiIndex::class)
            ->call('openCreate')
            ->set('form.judul', 'Modul')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Terlampir.')
            ->set('form.lampiran', UploadedFile::fake()->create('modul.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $informasi = Informasi::query()->where('judul', 'Modul')->firstOrFail();
        $path = $informasi->lampiran_path;
        Storage::disk(Informasi::LAMPIRAN_DISK)->assertExists($path);

        // Replacing the file deletes the old one.
        $component
            ->call('openEdit', $informasi->id)
            ->set('form.lampiran', UploadedFile::fake()->create('modul-v2.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $informasi->refresh();
        Storage::disk(Informasi::LAMPIRAN_DISK)->assertMissing($path);
        Storage::disk(Informasi::LAMPIRAN_DISK)->assertExists($informasi->lampiran_path);
        $this->assertSame('modul-v2.pdf', $informasi->lampiran_nama);

        $component->call('confirmDelete', $informasi->id)->call('delete');

        $this->assertModelMissing($informasi);
        Storage::disk(Informasi::LAMPIRAN_DISK)->assertMissing($informasi->lampiran_path);
    }

    public function test_super_admin_can_drop_an_existing_lampiran(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);
        $path = Storage::disk(Informasi::LAMPIRAN_DISK)->putFile(Informasi::LAMPIRAN_DIR, UploadedFile::fake()->create('lama.pdf', 10, 'application/pdf'));
        $informasi = Informasi::factory()->create([
            'kelas_id' => $kelas->id,
            'kategori_informasi_id' => $kategori->id,
            'lampiran_path' => $path,
            'lampiran_nama' => 'lama.pdf',
        ]);

        Livewire::actingAs($this->superAdmin())
            ->test(InformasiIndex::class)
            ->call('openEdit', $informasi->id)
            ->set('form.hapus_lampiran', true)
            ->call('save')
            ->assertHasNoErrors();

        $informasi->refresh();
        $this->assertFalse($informasi->hasLampiran());
        Storage::disk(Informasi::LAMPIRAN_DISK)->assertMissing($path);
    }

    public function test_admin_and_mahasiswa_cannot_upload_files(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);

        foreach ([$this->admin($kelas), $this->mahasiswa($kelas)] as $user) {
            Livewire::actingAs($user)
                ->test(InformasiIndex::class)
                ->assertSet('canUpload', false)
                ->call('openCreate')
                ->assertDontSee('Lampiran (gambar/file)')
                ->set('form.judul', 'Coba upload '.$user->id)
                ->set('form.kategori_informasi_id', (string) $kategori->id)
                ->set('form.isi', 'Coba.')
                ->set('form.lampiran', UploadedFile::fake()->create('modul.pdf', 100, 'application/pdf'))
                ->call('save')
                ->assertHasNoErrors();

            $informasi = Informasi::query()->where('judul', 'Coba upload '.$user->id)->firstOrFail();
            $this->assertFalse($informasi->hasLampiran());
        }

        $this->assertSame([], Storage::disk(Informasi::LAMPIRAN_DISK)->allFiles());
    }
}
