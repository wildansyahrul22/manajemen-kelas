<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_can_login_with_npm_and_password(): void
    {
        $user = $this->mahasiswa($this->kelas(), ['npm' => '24010002']);

        Livewire::test(Login::class)
            ->set('npm', '24010002')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->mahasiswa($this->kelas(), ['npm' => '24010002']);

        Livewire::test(Login::class)
            ->set('npm', '24010002')
            ->set('password', 'salah')
            ->call('login')
            ->assertHasErrors(['npm']);

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = $this->mahasiswa($this->kelas());

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
