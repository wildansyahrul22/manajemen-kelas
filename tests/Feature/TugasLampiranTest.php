<?php

namespace Tests\Feature;

use App\Enums\AksiLog;
use App\Enums\ModulLog;
use App\Livewire\Tugas\Index as TugasIndex;
use App\Livewire\Tugas\Show as TugasShow;
use App\Models\ActivityLog;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Tugas;
use App\Models\TugasLampiran;
use App\Support\PesanWhatsApp;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TugasLampiranTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Tugas::LAMPIRAN_DISK);
    }

    public function test_admin_kelas_can_attach_files_and_members_can_open_each(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);

        Livewire::actingAs($this->admin($kelas))
            ->test(TugasIndex::class)
            ->assertSet('canUpload', true)
            ->call('openCreate')
            ->assertSee('Lampiran (soal, template, dll.)')
            ->assertSee('2 file per tugas')
            ->set('form.nama', 'Laporan Praktikum 2')
            ->set('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->set('form.deadline', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('form.lampiran', [
                UploadedFile::fake()->create('soal.pdf', 120, 'application/pdf'),
                UploadedFile::fake()->image('contoh-output.png', 800, 600),
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('form.lampiran', [])
            ->assertSee('2 lampiran');

        $tugas = Tugas::query()->where('nama', 'Laporan Praktikum 2')->firstOrFail();
        $lampiran = $tugas->lampiran()->get();

        $this->assertSame(['soal.pdf', 'contoh-output.png'], $lampiran->pluck('nama')->all());
        $this->assertSame(120 * 1024, $lampiran[0]->ukuran);
        $this->assertTrue($lampiran[1]->isImage());
        $lampiran->each(fn (TugasLampiran $file) => Storage::disk(Tugas::LAMPIRAN_DISK)->assertExists($file->path));
        $this->assertStringStartsWith(Tugas::LAMPIRAN_DIR.'/'.$kelas->id.'/', $lampiran[0]->path);

        $mahasiswa = $this->mahasiswa($kelas);

        // A PDF is streamed inline so it can be previewed in a new tab; ?unduh=1 downloads it instead.
        $this->actingAs($mahasiswa)
            ->get(route('tugas.lampiran', [$tugas, $lampiran[0]]))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=soal.pdf');

        $this->actingAs($mahasiswa)
            ->get(route('tugas.lampiran', [$tugas, $lampiran[0], 'unduh' => 1]))
            ->assertOk()
            ->assertDownload('soal.pdf');

        $this->actingAs($mahasiswa)
            ->get(route('tugas.lampiran', [$tugas, $lampiran[1]]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->actingAs($mahasiswa)
            ->get(route('tugas.show', $tugas))
            ->assertOk()
            ->assertSeeInOrder(['Lampiran', 'soal.pdf', 'contoh-output.png']);

        $this->actingAs($this->mahasiswa($this->kelas()))
            ->get(route('tugas.lampiran', [$tugas, $lampiran[0]]))
            ->assertForbidden();
    }

    public function test_files_that_are_not_images_or_pdf_are_always_downloaded(): void
    {
        $kelas = $this->kelas();
        $tugas = $this->tugas($kelas);
        $lampiran = TugasLampiran::factory()->create(['tugas_id' => $tugas->id, 'nama' => 'template.docx']);
        Storage::disk(Tugas::LAMPIRAN_DISK)->put($lampiran->path, 'isi');

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('tugas.lampiran', [$tugas, $lampiran]))
            ->assertOk()
            ->assertDownload('template.docx');
    }

    public function test_lampiran_route_is_scoped_to_its_tugas(): void
    {
        $kelas = $this->kelas();
        $tugas = $this->tugas($kelas);
        $lain = $this->tugas($kelas);
        $lampiranLain = TugasLampiran::factory()->create(['tugas_id' => $lain->id]);
        Storage::disk(Tugas::LAMPIRAN_DISK)->put($lampiranLain->path, 'isi');

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('tugas.lampiran', [$tugas, $lampiranLain]))
            ->assertNotFound();
    }

    public function test_each_file_is_validated_and_the_per_tugas_limit_is_enforced(): void
    {
        $kelas = $this->kelas();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $admin = $this->admin($kelas);

        Livewire::actingAs($admin)
            ->test(TugasIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'File aneh')
            ->set('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->set('form.deadline', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('form.lampiran', [
                UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream'),
                UploadedFile::fake()->create('besar.pdf', Tugas::LAMPIRAN_MAKS_KB + 1, 'application/pdf'),
            ])
            ->call('save')
            ->assertHasErrors(['form.lampiran.0', 'form.lampiran.1' => 'max'])
            ->assertHasNoErrors(['form.lampiran'])
            ->assertSee('Jenis file lampiran tidak didukung.')
            ->assertSee('Ukuran tiap lampiran maksimal 2 MB.');

        Livewire::actingAs($admin)
            ->test(TugasIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'Terlalu banyak')
            ->set('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->set('form.deadline', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('form.lampiran', collect(range(1, Tugas::LAMPIRAN_MAKS_JUMLAH + 1))
                ->map(fn (int $i) => UploadedFile::fake()->create("file-{$i}.pdf", 10, 'application/pdf'))
                ->all())
            ->call('save')
            ->assertHasErrors(['form.lampiran' => 'max'])
            ->assertSee('Maksimal 2 file per tugas.');

        $this->assertSame(0, Tugas::query()->count());
        $this->assertSame([], Storage::disk(Tugas::LAMPIRAN_DISK)->allFiles());
    }

    public function test_the_limit_counts_files_already_attached_minus_the_ones_being_removed(): void
    {
        $kelas = $this->kelas();
        $tugas = $this->tugas($kelas);
        $tersimpan = TugasLampiran::factory()->create(['tugas_id' => $tugas->id]);
        Storage::disk(Tugas::LAMPIRAN_DISK)->put($tersimpan->path, 'x');
        $duaBaru = fn () => [
            UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
        ];

        // 1 stored + 2 new = 3 > 2.
        Livewire::actingAs($this->admin($kelas))
            ->test(TugasIndex::class)
            ->call('openEdit', $tugas->id)
            ->set('form.lampiran', $duaBaru())
            ->call('save')
            ->assertHasErrors(['form.lampiran'])
            ->assertSee('Maksimal 2 file per tugas (sudah ada 1).');

        $this->assertSame(1, $tugas->lampiran()->count());

        // Removing the stored one first makes room: 1 - 1 + 2 = 2.
        Livewire::actingAs($this->admin($kelas))
            ->test(TugasIndex::class)
            ->call('openEdit', $tugas->id)
            ->set('form.hapus_lampiran', [(string) $tersimpan->id])
            ->set('form.lampiran', $duaBaru())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, $tugas->lampiran()->count());
        Storage::disk(Tugas::LAMPIRAN_DISK)->assertMissing($tersimpan->path);
    }

    public function test_admin_can_remove_some_lampiran_add_new_ones_from_the_detail_page_and_the_change_is_logged(): void
    {
        $kelas = $this->kelas();
        $tugas = $this->tugas($kelas);
        $lama = TugasLampiran::factory()->create(['tugas_id' => $tugas->id, 'nama' => 'lama.pdf']);
        $tetap = TugasLampiran::factory()->create(['tugas_id' => $tugas->id, 'nama' => 'tetap.png']);
        Storage::disk(Tugas::LAMPIRAN_DISK)->put($lama->path, 'lama');
        Storage::disk(Tugas::LAMPIRAN_DISK)->put($tetap->path, 'tetap');

        Livewire::actingAs($this->admin($kelas))
            ->test(TugasShow::class, ['tugas' => $tugas])
            ->call('openEdit', $tugas->id)
            ->assertSee('lama.pdf')
            ->assertSee('tetap.png')
            ->set('form.hapus_lampiran', [(string) $lama->id])
            ->set('form.lampiran', [UploadedFile::fake()->create('baru.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('baru.docx')
            ->assertDontSee('lama.pdf');

        $this->assertSame(['tetap.png', 'baru.docx'], $tugas->lampiran()->pluck('nama')->all());
        Storage::disk(Tugas::LAMPIRAN_DISK)->assertMissing($lama->path);
        Storage::disk(Tugas::LAMPIRAN_DISK)->assertExists($tetap->path);
        $this->assertModelMissing($lama);

        $log = ActivityLog::query()->where('modul', ModulLog::Tugas->value)->where('aksi', AksiLog::Ubah->value)->latest('id')->firstOrFail();
        $this->assertSame(['lama.pdf, tetap.png', 'tetap.png, baru.docx'], $log->perubahan['lampiran']);
    }

    public function test_deleting_the_tugas_removes_every_attached_file(): void
    {
        $kelas = $this->kelas();
        $tugas = $this->tugas($kelas);
        $files = TugasLampiran::factory()->count(2)->create(['tugas_id' => $tugas->id]);
        $files->each(fn (TugasLampiran $file) => Storage::disk(Tugas::LAMPIRAN_DISK)->put($file->path, 'x'));

        Livewire::actingAs($this->admin($kelas))
            ->test(TugasIndex::class)
            ->call('confirmDelete', $tugas->id)
            ->call('delete');

        $this->assertModelMissing($tugas);
        $this->assertSame(0, TugasLampiran::query()->count());
        $this->assertSame([], Storage::disk(Tugas::LAMPIRAN_DISK)->allFiles());
    }

    public function test_admin_kelas_cannot_attach_files_when_the_plan_has_no_upload(): void
    {
        $kelas = $this->kelas(attributes: ['upload' => false]);
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);

        Livewire::actingAs($this->admin($kelas))
            ->test(TugasIndex::class)
            ->assertSet('canUpload', false)
            ->call('openCreate')
            ->assertDontSee('Lampiran (soal, template, dll.)')
            ->assertSee('Paket kelas ini belum termasuk unggah file')
            ->set('form.nama', 'Kuis 1')
            ->set('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->set('form.deadline', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('form.lampiran', [UploadedFile::fake()->create('soal.pdf', 100, 'application/pdf')])
            ->call('save')
            ->assertHasNoErrors();

        $tugas = Tugas::query()->where('nama', 'Kuis 1')->firstOrFail();

        $this->assertSame(0, $tugas->lampiran()->count());
        $this->assertSame([], Storage::disk(Tugas::LAMPIRAN_DISK)->allFiles());
    }

    public function test_super_admin_can_attach_files_even_when_the_plan_has_no_upload(): void
    {
        $kelas = $this->kelas(attributes: ['upload' => false]);
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);

        Livewire::actingAs($this->superAdmin())
            ->test(TugasIndex::class)
            ->assertSet('canUpload', true)
            ->call('openCreate')
            ->assertSee('Lampiran (soal, template, dll.)')
            ->assertDontSee('Paket kelas ini belum termasuk unggah file')
            ->set('form.nama', 'Kuis 1')
            ->set('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->set('form.deadline', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('form.lampiran', [UploadedFile::fake()->create('soal.pdf', 100, 'application/pdf')])
            ->call('save')
            ->assertHasNoErrors();

        $tugas = Tugas::query()->where('nama', 'Kuis 1')->firstOrFail();

        $this->assertSame(['soal.pdf'], $tugas->lampiran()->pluck('nama')->all());
        $this->assertCount(1, Storage::disk(Tugas::LAMPIRAN_DISK)->allFiles());
    }

    public function test_the_list_and_whatsapp_message_mention_the_attachments(): void
    {
        $kelas = $this->kelas();
        $tugas = $this->tugas($kelas, ['nama' => 'Laporan 1']);
        TugasLampiran::factory()->count(2)->create(['tugas_id' => $tugas->id]);

        Livewire::actingAs($this->mahasiswa($kelas))
            ->test(TugasIndex::class)
            ->assertSee('2 lampiran');

        $this->assertStringContainsString(
            '📎 2 lampiran (buka di aplikasi, login dengan NPM)',
            PesanWhatsApp::tugas($tugas->fresh(['mataKuliah', 'lampiran']), $kelas),
        );

        $tanpaLampiran = $this->tugas($kelas);

        $this->assertStringNotContainsString('lampiran', PesanWhatsApp::tugas($tanpaLampiran->fresh(['mataKuliah', 'lampiran']), $kelas));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function tugas(Kelas $kelas, array $attributes = []): Tugas
    {
        return Tugas::factory()->create([
            'mata_kuliah_id' => MataKuliah::factory()->create(['kelas_id' => $kelas->id])->id,
            ...$attributes,
        ]);
    }
}
