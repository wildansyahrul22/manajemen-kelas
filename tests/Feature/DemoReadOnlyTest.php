<?php

namespace Tests\Feature;

use App\Enums\AksiLog;
use App\Exceptions\ModeDemoException;
use App\Livewire\Auth\Login;
use App\Livewire\Informasi\Index as InformasiIndex;
use App\Livewire\Profile\Edit as ProfileEdit;
use App\Livewire\Tugas\Index as TugasIndex;
use App\Livewire\Users\Index as UsersIndex;
use App\Models\ActivityLog;
use App\Models\Informasi;
use App\Models\KategoriInformasi;
use App\Models\MataKuliah;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The shared demo account may open every form and dialog, but nothing it submits is written.
 */
class DemoReadOnlyTest extends TestCase
{
    protected function demo(): User
    {
        $demo = $this->admin($this->kelas(), ['npm' => '24010001']);

        config(['demo.npm' => '24010001']);

        return $demo;
    }

    public function test_demo_can_open_the_create_form_but_saving_writes_nothing_and_closes_it_with_a_warning(): void
    {
        $demo = $this->demo();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $demo->kelas_id]);

        Livewire::actingAs($demo)
            ->test(TugasIndex::class)
            ->call('openCreate')
            ->assertSet('showForm', true)
            ->set('form.nama', 'Laporan Praktikum')
            ->set('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->set('form.deadline', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertDispatched('notify', type: 'warning', message: ModeDemoException::PESAN);

        $this->assertSame(0, Tugas::query()->count());
        $this->assertSame(0, ActivityLog::query()->where('user_id', $demo->id)->count());
    }

    public function test_demo_still_gets_validation_errors_before_the_write_is_refused(): void
    {
        $demo = $this->demo();

        Livewire::actingAs($demo)
            ->test(TugasIndex::class)
            ->call('openCreate')
            ->call('save')
            ->assertHasErrors(['form.nama', 'form.mata_kuliah_id', 'form.deadline'])
            ->assertSet('showForm', true)
            ->assertNotDispatched('notify');
    }

    public function test_demo_can_open_edit_and_delete_dialogs_but_neither_changes_anything(): void
    {
        $demo = $this->demo();
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $demo->kelas_id]);
        $tugas = Tugas::factory()->create(['mata_kuliah_id' => $mataKuliah->id, 'nama' => 'Tugas Asli']);

        Livewire::actingAs($demo)
            ->test(TugasIndex::class)
            ->call('openEdit', $tugas->id)
            ->assertSet('showForm', true)
            ->assertSet('form.nama', 'Tugas Asli')
            ->set('form.nama', 'Tugas Diubah')
            ->call('save')
            ->assertSet('showForm', false)
            ->assertDispatched('notify', type: 'warning')
            ->call('confirmDelete', $tugas->id)
            ->assertSet('confirmingDelete', true)
            ->call('delete')
            ->assertSet('confirmingDelete', false)
            ->assertSet('deletingId', null)
            ->assertDispatched('notify', type: 'warning');

        $this->assertSame('Tugas Asli', $tugas->fresh()->nama);
    }

    public function test_demo_cannot_store_attachments_or_change_the_shared_password(): void
    {
        Storage::fake(Informasi::LAMPIRAN_DISK);

        $demo = $this->demo();
        $kategori = KategoriInformasi::factory()->create(['kelas_id' => $demo->kelas_id]);

        Livewire::actingAs($demo)
            ->test(InformasiIndex::class)
            ->assertSet('canUpload', true)
            ->call('openCreate')
            ->set('form.judul', 'Coba upload')
            ->set('form.kategori_informasi_id', (string) $kategori->id)
            ->set('form.isi', 'Coba.')
            ->set('form.lampiran', [UploadedFile::fake()->create('modul.pdf', 100, 'application/pdf')])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify', type: 'warning');

        $this->assertSame(0, Informasi::query()->count());
        $this->assertSame([], Storage::disk(Informasi::LAMPIRAN_DISK)->allFiles());

        Livewire::actingAs($demo)
            ->test(ProfileEdit::class)
            ->set('current_password', 'password')
            ->set('password', 'password-baru')
            ->set('password_confirmation', 'password-baru')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertDispatched('notify', type: 'warning');

        $this->assertTrue(Hash::check('password', $demo->fresh()->password));
    }

    public function test_demo_cannot_add_or_remove_users_of_the_demo_kelas(): void
    {
        $demo = $this->demo();
        $anggota = $this->mahasiswa($demo->kelas);

        Livewire::actingAs($demo)
            ->test(UsersIndex::class)
            ->call('confirmDelete', $anggota->id)
            ->assertSet('confirmingDelete', true)
            ->call('delete')
            ->assertSet('confirmingDelete', false)
            ->assertDispatched('notify', type: 'warning');

        $this->assertNotNull($anggota->fresh());
    }

    public function test_demo_can_still_sign_in_with_remember_me_and_sign_out_and_both_are_logged(): void
    {
        $demo = $this->demo();

        Livewire::test(Login::class)
            ->set('npm', '24010001')
            ->set('password', 'password')
            ->set('remember', true)
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($demo);
        $this->assertNotEmpty($demo->fresh()->remember_token);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->assertSame(1, ActivityLog::query()->where('user_id', $demo->id)->where('aksi', AksiLog::Masuk->value)->count());
        $this->assertSame(1, ActivityLog::query()->where('user_id', $demo->id)->where('aksi', AksiLog::Keluar->value)->count());
    }

    public function test_other_accounts_of_the_demo_kelas_can_still_write(): void
    {
        $demo = $this->demo();
        $adminLain = $this->admin($demo->kelas);
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $demo->kelas_id]);

        Livewire::actingAs($adminLain)
            ->test(TugasIndex::class)
            ->call('openCreate')
            ->set('form.nama', 'Laporan Praktikum')
            ->set('form.mata_kuliah_id', (string) $mataKuliah->id)
            ->set('form.deadline', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify', type: 'success');

        $this->assertSame(1, Tugas::query()->count());
    }
}
