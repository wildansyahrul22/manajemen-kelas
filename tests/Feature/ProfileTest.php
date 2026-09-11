<?php

namespace Tests\Feature;

use App\Livewire\Profile\Edit;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_user_can_update_name_and_phone(): void
    {
        $user = $this->mahasiswa($this->kelas());

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('name', 'Nama Baru')
            ->set('no_hp', '085712345678')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('6285712345678', $user->no_hp);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = $this->mahasiswa($this->kelas());

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('current_password', 'salah')
            ->set('password', 'password-baru')
            ->set('password_confirmation', 'password-baru')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('current_password', 'password')
            ->set('password', 'password-baru')
            ->set('password_confirmation', 'password-baru')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));
    }
}
