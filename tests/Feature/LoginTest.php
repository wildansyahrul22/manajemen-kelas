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

    public function test_remember_me_issues_a_remember_token(): void
    {
        $user = $this->mahasiswa($this->kelas(), ['npm' => '24010002']);

        Livewire::test(Login::class)
            ->set('npm', '24010002')
            ->set('password', 'password')
            ->set('remember', true)
            ->call('login')
            ->assertHasNoErrors();

        $this->assertNotEmpty($user->fresh()->remember_token);
        $this->assertTrue(auth()->viaRemember() || auth()->check());

        $cookie = collect(app('cookie')->getQueuedCookies())->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));
        $this->assertNotNull($cookie);

        // The cookie lasts 30 days (Laravel's default would be 400).
        $this->assertEqualsWithDelta(now()->addDays(30)->getTimestamp(), $cookie->getExpiresTime(), 60);
    }

    public function test_without_remember_me_no_remember_token_is_issued(): void
    {
        $user = $this->mahasiswa($this->kelas(), ['npm' => '24010002']);
        $tokenSebelum = $user->remember_token;

        Livewire::test(Login::class)
            ->set('npm', '24010002')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertSame($tokenSebelum, $user->fresh()->remember_token);
        $this->assertNull(collect(app('cookie')->getQueuedCookies())->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_')));
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
