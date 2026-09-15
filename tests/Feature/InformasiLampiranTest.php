<?php

namespace Tests\Feature;

use App\Enums\AksiLog;
use App\Enums\ModulLog;
use App\Livewire\Informasi\Index as InformasiIndex;
use App\Models\ActivityLog;
use App\Models\Informasi;
use App\Models\InformasiLampiran;
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

    public function test_admin_kelas_can_attach_several_files_at_once_and_members_can_open_each(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);
        $admin = $this->admin($kelas);

        Livewire::actingAs($admin)
            ->test(InformasiIndex::class)
            ->assertSet('canUpload', true)
            ->call('openCreate')
            ->set('form.judul', 'Jadwal UAS')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Terlampir jadwal dan tata tertib.')
            ->set('form.lampiran', [
                UploadedFile::fake()->image('jadwal-uas.png', 800, 600),
                UploadedFile::fake()->create('tata-tertib.pdf', 120, 'application/pdf'),
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('form.lampiran', [])
            ->assertSee('2 lampiran');

        $informasi = Informasi::query()->where('judul', 'Jadwal UAS')->firstOrFail();
        $lampiran = $informasi->lampiran()->get();

        $this->assertSame(['jadwal-uas.png', 'tata-tertib.pdf'], $lampiran->pluck('nama')->all());
        $this->assertTrue($lampiran[0]->isImage());
        $this->assertFalse($lampiran[1]->isImage());
        $this->assertSame(120 * 1024, $lampiran[1]->ukuran);
        $lampiran->each(fn (InformasiLampiran $file) => Storage::disk(Informasi::LAMPIRAN_DISK)->assertExists($file->path));

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('informasi.lampiran', [$informasi, $lampiran[0]]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('informasi.lampiran', [$informasi, $lampiran[1]]))
            ->assertOk()
            ->assertDownload('tata-tertib.pdf');

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('informasi.show', $informasi))
            ->assertOk()
            ->assertSeeInOrder(['jadwal-uas.png', 'tata-tertib.pdf']);

        $this->actingAs($this->mahasiswa($this->kelas()))
            ->get(route('informasi.lampiran', [$informasi, $lampiran[0]]))
            ->assertForbidden();
    }

    public function test_lampiran_route_is_scoped_to_its_informasi(): void
    {
        $kelas = $this->kelas();
        $informasi = Informasi::factory()->create(['kelas_id' => $kelas->id]);
        $lain = Informasi::factory()->create(['kelas_id' => $kelas->id]);
        $lampiranLain = InformasiLampiran::factory()->create(['informasi_id' => $lain->id]);
        Storage::disk(Informasi::LAMPIRAN_DISK)->put($lampiranLain->path, 'isi');

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('informasi.lampiran', [$informasi, $lampiranLain]))
            ->assertNotFound();
    }

    public function test_each_file_is_validated_and_the_per_informasi_limit_is_enforced(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);
        $admin = $this->admin($kelas);

        Livewire::actingAs($admin)
            ->test(InformasiIndex::class)
            ->call('openCreate')
            ->set('form.judul', 'File aneh')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Coba.')
            ->set('form.lampiran', [
                UploadedFile::fake()->create('modul.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream'),
                UploadedFile::fake()->create('besar.pdf', Informasi::LAMPIRAN_MAKS_KB + 1, 'application/pdf'),
            ])
            ->call('save')
            ->assertHasErrors(['form.lampiran.1', 'form.lampiran.2' => 'max'])
            ->assertHasNoErrors(['form.lampiran.0'])
            ->assertSee('Ukuran tiap lampiran maksimal 5 MB.');

        Livewire::actingAs($admin)
            ->test(InformasiIndex::class)
            ->call('openCreate')
            ->set('form.judul', 'Terlalu banyak')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Coba.')
            ->set('form.lampiran', collect(range(1, Informasi::LAMPIRAN_MAKS_JUMLAH + 1))
                ->map(fn (int $i) => UploadedFile::fake()->create("file-{$i}.pdf", 10, 'application/pdf'))
                ->all())
            ->call('save')
            ->assertHasErrors(['form.lampiran' => 'max'])
            ->assertSee('Maksimal 5 file per informasi.');

        $this->assertSame(0, Informasi::query()->count());
        $this->assertSame([], Storage::disk(Informasi::LAMPIRAN_DISK)->allFiles());
    }

    public function test_the_limit_counts_files_already_attached_minus_the_ones_being_removed(): void
    {
        $kelas = $this->kelas();
        $informasi = Informasi::factory()->create(['kelas_id' => $kelas->id]);
        $tersimpan = InformasiLampiran::factory()->count(4)->create(['informasi_id' => $informasi->id]);
        $tersimpan->each(fn (InformasiLampiran $file) => Storage::disk(Informasi::LAMPIRAN_DISK)->put($file->path, 'x'));
        $duaBaru = fn () => [
            UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
        ];

        // 4 stored + 2 new = 6 > 5.
        Livewire::actingAs($this->admin($kelas))
            ->test(InformasiIndex::class)
            ->call('openEdit', $informasi->id)
            ->set('form.lampiran', $duaBaru())
            ->call('save')
            ->assertHasErrors(['form.lampiran'])
            ->assertSee('Maksimal 5 file per informasi (sudah ada 4).');

        $this->assertSame(4, $informasi->lampiran()->count());

        // Removing one first makes room: 4 - 1 + 2 = 5.
        Livewire::actingAs($this->admin($kelas))
            ->test(InformasiIndex::class)
            ->call('openEdit', $informasi->id)
            ->set('form.hapus_lampiran', [(string) $tersimpan->first()->id])
            ->set('form.lampiran', $duaBaru())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(5, $informasi->lampiran()->count());
    }

    public function test_admin_can_remove_some_lampiran_add_new_ones_and_the_change_is_logged(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);
        $informasi = Informasi::factory()->create(['kelas_id' => $kelas->id, 'kategori_informasi_id' => $kategori->id]);
        $lama = InformasiLampiran::factory()->create(['informasi_id' => $informasi->id, 'nama' => 'lama.pdf']);
        $tetap = InformasiLampiran::factory()->create(['informasi_id' => $informasi->id, 'nama' => 'tetap.png']);
        Storage::disk(Informasi::LAMPIRAN_DISK)->put($lama->path, 'lama');
        Storage::disk(Informasi::LAMPIRAN_DISK)->put($tetap->path, 'tetap');

        Livewire::actingAs($this->admin($kelas))
            ->test(InformasiIndex::class)
            ->call('openEdit', $informasi->id)
            ->assertSee('lama.pdf')
            ->assertSee('tetap.png')
            ->set('form.hapus_lampiran', [(string) $lama->id])
            ->set('form.lampiran', [UploadedFile::fake()->create('baru.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['tetap.png', 'baru.docx'], $informasi->lampiran()->pluck('nama')->all());
        Storage::disk(Informasi::LAMPIRAN_DISK)->assertMissing($lama->path);
        Storage::disk(Informasi::LAMPIRAN_DISK)->assertExists($tetap->path);
        $this->assertModelMissing($lama);

        $log = ActivityLog::query()->where('modul', ModulLog::Informasi->value)->where('aksi', AksiLog::Ubah->value)->latest('id')->firstOrFail();
        $this->assertSame(['lama.pdf, tetap.png', 'tetap.png, baru.docx'], $log->perubahan['lampiran']);
    }

    public function test_deleting_the_informasi_removes_every_attached_file(): void
    {
        $kelas = $this->kelas();
        $informasi = Informasi::factory()->create(['kelas_id' => $kelas->id]);
        $files = InformasiLampiran::factory()->count(2)->create(['informasi_id' => $informasi->id]);
        $files->each(fn (InformasiLampiran $file) => Storage::disk(Informasi::LAMPIRAN_DISK)->put($file->path, 'x'));

        Livewire::actingAs($this->admin($kelas))
            ->test(InformasiIndex::class)
            ->call('confirmDelete', $informasi->id)
            ->call('delete');

        $this->assertModelMissing($informasi);
        $this->assertSame(0, InformasiLampiran::query()->count());
        $this->assertSame([], Storage::disk(Informasi::LAMPIRAN_DISK)->allFiles());
    }

    public function test_mahasiswa_cannot_upload_or_remove_files(): void
    {
        $kelas = $this->kelas();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);
        $mahasiswa = $this->mahasiswa($kelas);

        Livewire::actingAs($mahasiswa)
            ->test(InformasiIndex::class)
            ->assertSet('canUpload', false)
            ->call('openCreate')
            ->assertDontSee('Lampiran (gambar/file)')
            ->set('form.judul', 'Coba upload')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Coba.')
            ->set('form.lampiran', [UploadedFile::fake()->create('modul.pdf', 100, 'application/pdf')])
            ->call('save')
            ->assertHasNoErrors();

        $informasi = Informasi::query()->where('judul', 'Coba upload')->firstOrFail();
        $this->assertSame(0, $informasi->lampiran()->count());
        $this->assertSame([], Storage::disk(Informasi::LAMPIRAN_DISK)->allFiles());

        // A file attached by admin stays even if the mahasiswa author edits and ticks it for removal.
        $lampiran = InformasiLampiran::factory()->create(['informasi_id' => $informasi->id]);

        Livewire::actingAs($mahasiswa)
            ->test(InformasiIndex::class)
            ->call('openEdit', $informasi->id)
            ->set('form.hapus_lampiran', [(string) $lampiran->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertModelExists($lampiran);
    }
}
