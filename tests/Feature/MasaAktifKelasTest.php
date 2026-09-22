<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Informasi\Index as InformasiIndex;
use App\Livewire\Kelas\Index as KelasIndex;
use App\Models\Informasi;
use App\Models\KategoriInformasi;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class MasaAktifKelasTest extends TestCase
{
    public function test_a_kelas_without_dates_never_expires(): void
    {
        $kelas = $this->kelas();

        $this->assertTrue($kelas->isAktif());
        $this->assertFalse($kelas->sudahBerakhir());
        $this->assertNull($kelas->masaAktifTerbaca());

        $this->masuk($this->mahasiswa($kelas, ['npm' => '24010001']))
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));
    }

    public function test_members_of_an_expired_kelas_cannot_sign_in(): void
    {
        $kelas = Kelas::factory()->kedaluwarsa()->create(['nama' => 'TI-9Z']);
        $mahasiswa = $this->mahasiswa($kelas, ['npm' => '24010002']);

        $this->masuk($mahasiswa)->assertHasErrors('npm');

        $this->assertGuest();
    }

    public function test_members_of_a_kelas_that_has_not_started_cannot_sign_in_yet(): void
    {
        $kelas = Kelas::factory()->belumMulai()->create();

        $this->masuk($this->admin($kelas, ['npm' => '24010003']))->assertHasErrors('npm');

        $this->assertGuest();
    }

    public function test_a_super_admin_signs_in_even_when_a_kelas_has_expired(): void
    {
        Kelas::factory()->kedaluwarsa()->create();

        $this->masuk($this->superAdmin(['npm' => 'superadmin']))
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));
    }

    public function test_an_open_session_is_signed_out_once_the_kelas_expires(): void
    {
        $kelas = $this->kelas();
        $mahasiswa = $this->mahasiswa($kelas);

        $this->actingAs($mahasiswa)->get(route('dashboard'))->assertOk();

        $kelas->update([
            'masa_aktif_mulai' => now()->subMonths(6)->toDateString(),
            'masa_aktif_selesai' => now()->subDay()->toDateString(),
        ]);

        // A real request resolves the user (and its kelas) from the session, so start from a fresh
        // instance rather than the one whose relation this test already loaded.
        $this->actingAs($mahasiswa->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_super_admin_sets_the_subscription_window_and_the_upload_plan(): void
    {
        $kelas = $this->kelas(attributes: ['nama' => 'TI-3A']);

        Livewire::actingAs($this->superAdmin())
            ->test(KelasIndex::class)
            ->call('openEdit', $kelas->id)
            ->assertSet('form.upload', true)
            ->set('form.masa_aktif_mulai', now()->startOfMonth()->toDateString())
            ->set('form.masa_aktif_selesai', now()->startOfMonth()->subDay()->toDateString())
            ->call('save')
            ->assertHasErrors(['form.masa_aktif_selesai'])
            ->assertSee('Akhir masa aktif tidak boleh lebih awal dari tanggal mulai.')
            ->set('form.masa_aktif_selesai', now()->addMonths(6)->toDateString())
            ->set('form.upload', false)
            ->call('save')
            ->assertHasNoErrors();

        $kelas->refresh();

        $this->assertSame(now()->startOfMonth()->toDateString(), $kelas->masa_aktif_mulai->toDateString());
        $this->assertSame(now()->addMonths(6)->toDateString(), $kelas->masa_aktif_selesai->toDateString());
        $this->assertFalse($kelas->bolehUpload());
        $this->assertTrue($kelas->isAktif());
    }

    public function test_admin_kelas_cannot_attach_files_when_the_plan_has_no_upload(): void
    {
        Storage::fake(Informasi::LAMPIRAN_DISK);

        $kelas = $this->kelas(attributes: ['upload' => false]);
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);

        Livewire::actingAs($this->admin($kelas))
            ->test(InformasiIndex::class)
            ->assertSet('canUpload', false)
            ->call('openCreate')
            ->assertDontSee('Lampiran (gambar/file)')
            ->set('form.judul', 'Jadwal UAS')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Terlampir.')
            ->set('form.lampiran', [UploadedFile::fake()->create('jadwal.pdf', 100, 'application/pdf')])
            ->call('save')
            ->assertHasNoErrors();

        $informasi = Informasi::query()->where('judul', 'Jadwal UAS')->firstOrFail();

        $this->assertSame(0, $informasi->lampiran()->count());
        $this->assertSame([], Storage::disk(Informasi::LAMPIRAN_DISK)->allFiles());

        // Turning the plan back on restores the feature.
        $kelas->update(['upload' => true]);

        Livewire::actingAs($this->admin($kelas))->test(InformasiIndex::class)->assertSet('canUpload', true);
    }

    public function test_super_admin_can_attach_files_even_when_the_plan_has_no_upload(): void
    {
        Storage::fake(Informasi::LAMPIRAN_DISK);

        $kelas = $this->kelas(attributes: ['upload' => false]);
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $kelas->id]);

        Livewire::actingAs($this->superAdmin())
            ->test(InformasiIndex::class)
            ->assertSet('canUpload', true)
            ->call('openCreate')
            ->assertSee('Lampiran (gambar/file)')
            ->assertDontSee('Paket kelas ini belum termasuk unggah file')
            ->set('form.judul', 'Jadwal UAS')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Terlampir.')
            ->set('form.lampiran', [UploadedFile::fake()->create('jadwal.pdf', 100, 'application/pdf')])
            ->call('save')
            ->assertHasNoErrors();

        $informasi = Informasi::query()->where('judul', 'Jadwal UAS')->firstOrFail();

        $this->assertSame(['jadwal.pdf'], $informasi->lampiran()->pluck('nama')->all());
        $this->assertCount(1, Storage::disk(Informasi::LAMPIRAN_DISK)->allFiles());
    }

    /**
     * Sign in as the given user with the factory password.
     */
    protected function masuk(User $user): Testable
    {
        return Livewire::test(Login::class)
            ->set('npm', $user->npm)
            ->set('password', 'password')
            ->call('login');
    }
}
