<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Dashboard;
use App\Livewire\Kelas\Index as KelasIndex;
use App\Livewire\Kelompok\Index as KelompokIndex;
use App\Livewire\Users\Index;
use App\Models\KategoriKelompok;
use App\Models\MataKuliah;
use App\Models\Semester;
use App\Models\User;
use App\Rules\NomorHpIndonesia;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_phone_numbers_are_normalised_to_indonesian_format(): void
    {
        $this->assertSame('6281234567890', NomorHpIndonesia::normalize('081234567890'));
        $this->assertSame('6281234567890', NomorHpIndonesia::normalize('+62 812-3456-7890'));
        $this->assertSame('6281234567890', NomorHpIndonesia::normalize('81234567890'));
        $this->assertSame('6281234567890', NomorHpIndonesia::normalize('6281234567890'));
    }

    public function test_admin_can_create_mahasiswa_in_own_kelas(): void
    {
        $kelas = $this->kelas();
        $admin = $this->admin($kelas);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openCreate')
            ->set('form.npm', '24010077')
            ->set('form.name', 'Mahasiswa Baru')
            ->set('form.no_hp', '0812-3456-7890')
            ->set('form.role', Role::Mahasiswa->value)
            ->set('form.password', 'rahasia123')
            ->set('form.password_confirmation', 'rahasia123')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::query()->where('npm', '24010077')->firstOrFail();

        $this->assertSame('6281234567890', $user->no_hp);
        $this->assertSame($kelas->id, $user->kelas_id);
        $this->assertTrue($user->isMahasiswa());
    }

    public function test_phone_number_is_optional(): void
    {
        $kelas = $this->kelas();

        Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('openCreate')
            ->set('form.npm', '24010088')
            ->set('form.name', 'Tanpa Nomor')
            ->set('form.no_hp', '')
            ->set('form.role', Role::Mahasiswa->value)
            ->set('form.password', 'rahasia123')
            ->set('form.password_confirmation', 'rahasia123')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::query()->where('npm', '24010088')->firstOrFail();

        $this->assertNull($user->no_hp);
        $this->assertNull($user->noHpFormatted());
        $this->assertNull($user->whatsappUrl());

        $this->actingAs($this->admin($kelas))->get(route('users.show', $user))->assertOk()->assertSee('Tanpa Nomor');
    }

    public function test_validation_rules_for_npm_name_and_phone(): void
    {
        $kelas = $this->kelas();

        Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('openCreate')
            ->set('form.npm', '12345')
            ->set('form.name', str_repeat('a', 101))
            ->set('form.no_hp', '0812345')
            ->set('form.password', 'rahasia123')
            ->set('form.password_confirmation', 'rahasia123')
            ->call('save')
            ->assertHasErrors(['form.npm', 'form.name', 'form.no_hp']);

        Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('openCreate')
            ->set('form.npm', str_repeat('1', 21))
            ->set('form.name', 'Nama')
            ->set('form.no_hp', '62812345678901234')
            ->set('form.password', 'rahasia123')
            ->set('form.password_confirmation', 'rahasia123')
            ->call('save')
            ->assertHasErrors(['form.npm', 'form.no_hp']);
    }

    public function test_admin_cannot_assign_super_admin_role_or_touch_other_kelas(): void
    {
        $kelas = $this->kelas();
        $kelasLain = $this->kelas();
        $admin = $this->admin($kelas);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openCreate')
            ->set('form.npm', '24010078')
            ->set('form.name', 'Calon Super')
            ->set('form.no_hp', '081234567890')
            ->set('form.role', Role::SuperAdmin->value)
            ->set('form.kelas_id', (string) $kelasLain->id)
            ->set('form.password', 'rahasia123')
            ->set('form.password_confirmation', 'rahasia123')
            ->call('save')
            ->assertHasErrors(['form.role']);

        $userLain = $this->mahasiswa($kelasLain);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openEdit', $userLain->id)
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmDelete', $admin->id)
            ->assertForbidden();
    }

    public function test_user_list_is_scoped_to_kelas(): void
    {
        $kelas = $this->kelas();
        $this->mahasiswa($kelas, ['name' => 'Anak Kelas Sendiri']);
        $this->mahasiswa($this->kelas(), ['name' => 'Anak Kelas Lain']);

        Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->assertSee('Anak Kelas Sendiri')
            ->assertDontSee('Anak Kelas Lain');
    }

    public function test_admin_can_add_a_kelas_terbang_user_and_a_semester_is_required_for_it(): void
    {
        $kelas = $this->kelas(semester: 3);
        $semester5 = Semester::query()->where('nomor', 5)->firstOrFail();

        $component = Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->call('openCreate')
            ->assertDontSee('Semester kelas terbang')
            ->set('form.npm', '23010050')
            ->set('form.name', 'Dimas Terbang')
            ->set('form.role', Role::Mahasiswa->value)
            ->set('form.password', 'rahasia123')
            ->set('form.password_confirmation', 'rahasia123')
            ->set('form.kelas_terbang', true)
            ->assertSee('Semester kelas terbang')
            ->call('save')
            ->assertHasErrors(['form.kelas_terbang_semester_id' => 'required']);

        $component
            ->set('form.kelas_terbang_semester_id', (string) $semester5->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify', function (string $event, array $params) {
                return str_contains($params['message'], 'baru tampil saat Semester 5 menjadi semester aktif');
            });

        $user = User::query()->where('npm', '23010050')->firstOrFail();
        $this->assertTrue($user->isKelasTerbang());
        $this->assertSame($semester5->id, $user->kelas_terbang_semester_id);

        // Unticking the box clears the semester even if one was picked.
        Livewire::actingAs($this->admin($kelas))
            ->test(Index::class)
            ->set('semuaSemester', true)
            ->call('openEdit', $user->id)
            ->assertSet('form.kelas_terbang', true)
            ->assertSet('form.kelas_terbang_semester_id', (string) $semester5->id)
            ->set('form.kelas_terbang', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($user->fresh()->isKelasTerbang());
    }

    public function test_kelas_terbang_user_is_only_a_member_during_their_semester(): void
    {
        $kelas = $this->kelas(semester: 3);
        $admin = $this->admin($kelas, ['name' => 'Rizky Admin']);
        $reguler = $this->mahasiswa($kelas, ['name' => 'Siti Reguler']);
        $semester3 = Semester::query()->where('nomor', 3)->firstOrFail();
        $semester4 = Semester::query()->where('nomor', 4)->firstOrFail();
        $terbangSekarang = $this->mahasiswa($kelas, ['name' => 'Dimas Terbang', 'kelas_terbang_semester_id' => $semester3->id]);
        $terbangNanti = $this->mahasiswa($kelas, ['name' => 'Nadia Terbang', 'kelas_terbang_semester_id' => $semester4->id]);

        // Users list: active-semester roster by default, everyone with the toggle.
        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee('Siti Reguler')
            ->assertSee('Dimas Terbang')
            ->assertSee('Kelas terbang · Semester 3')
            ->assertDontSee('Nadia Terbang')
            ->set('semuaSemester', true)
            ->assertSee('Nadia Terbang')
            ->assertSee('Kelas terbang · Semester 4');

        // Dashboard counts admin + reguler + kelas terbang of this semester.
        $this->assertSame(3, Livewire::actingAs($admin)->test(Dashboard::class)->get('ringkasan')['mahasiswa']);

        // Kelompok member picker and the WhatsApp "belum masuk kelompok" list follow the same rule.
        $mataKuliah = MataKuliah::factory()->create(['kelas_id' => $kelas->id]);
        $kategori = KategoriKelompok::factory()->create(['mata_kuliah_id' => $mataKuliah->id]);

        $component = Livewire::actingAs($admin)
            ->test(KelompokIndex::class)
            ->call('openCreate', $kategori->id);

        $ids = $component->get('mahasiswaOptions')->pluck('id')->all();
        $this->assertContains($reguler->id, $ids);
        $this->assertContains($terbangSekarang->id, $ids);
        $this->assertNotContains($terbangNanti->id, $ids);

        $component
            ->set('form.nama', 'Kelompok 1')
            ->set('form.anggota', [$terbangNanti->id])
            ->call('save')
            ->assertHasErrors(['form.anggota.0']);

        $teks = Livewire::actingAs($admin)->test(KelompokIndex::class)->set('kategoriId', (string) $kategori->id)->get('teksWhatsApp');
        $this->assertStringContainsString('Dimas Terbang', $teks);
        $this->assertStringNotContainsString('Nadia Terbang', $teks);

        // Kelas list (super admin) counts the active-semester roster too.
        $daftarKelas = Livewire::actingAs($this->superAdmin())->test(KelasIndex::class)->get('daftarKelas');
        $this->assertSame(3, $daftarKelas->firstWhere('id', $kelas->id)->mahasiswa_count);
    }
}
