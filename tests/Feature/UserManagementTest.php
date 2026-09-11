<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Users\Index;
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
}
