<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    public function test_the_demo_route_does_not_exist_until_an_account_is_configured(): void
    {
        $this->mahasiswa($this->kelas(), ['npm' => '24010001']);

        config(['demo.npm' => null]);
        $this->get(route('demo'))->assertNotFound();

        config(['demo.npm' => '']);
        $this->get(route('demo'))->assertNotFound();

        $this->assertGuest();
    }

    public function test_only_the_configured_account_can_be_entered_through_the_demo_route(): void
    {
        $kelas = $this->kelas();
        $this->admin($kelas, ['npm' => '24010001']);

        // The account named in the config does not exist in this database.
        config(['demo.npm' => '99999999']);

        $this->get(route('demo'))->assertNotFound();
        $this->assertGuest();
    }

    public function test_a_visitor_is_signed_in_as_the_demo_account_and_lands_on_the_dashboard(): void
    {
        $kelas = $this->kelas();
        $demo = $this->admin($kelas, ['npm' => '24010001', 'name' => 'Admin Demo']);

        config(['demo.npm' => '24010001']);

        $this->get(route('demo'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('notify');

        $this->assertAuthenticatedAs($demo);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mode demo');
    }

    public function test_an_expired_demo_kelas_sends_the_visitor_back_to_the_landing_page(): void
    {
        $kelas = Kelas::factory()->kedaluwarsa()->create();
        $this->admin($kelas, ['npm' => '24010001']);

        config(['demo.npm' => '24010001']);

        $this->get(route('demo'))->assertRedirect(route('landing'));
        $this->assertGuest();
    }

    public function test_someone_already_signed_in_keeps_their_own_account(): void
    {
        $kelas = $this->kelas();
        $this->admin($kelas, ['npm' => '24010001']);
        $user = $this->mahasiswa($kelas);

        config(['demo.npm' => '24010001']);

        $this->actingAs($user)->get(route('demo'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_login_page_ends_a_demo_session_so_a_visitor_can_sign_in_with_their_own_account(): void
    {
        $kelas = $this->kelas();
        $this->admin($kelas, ['npm' => '24010001']);
        $user = $this->mahasiswa($kelas);

        config(['demo.npm' => '24010001']);

        $this->get(route('demo'))->assertRedirect(route('dashboard'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk');

        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        // A real session is untouched: the login page still sends signed-in users to their dashboard.
        $this->actingAs($user)->get(route('login'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_demo_banner_offers_a_way_out_of_the_demo(): void
    {
        $this->admin($this->kelas(), ['npm' => '24010001']);

        config(['demo.npm' => '24010001']);

        $this->get(route('demo'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Keluar dari demo')
            ->assertSee(route('logout'));

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_the_demo_kelas_can_be_seeded_next_to_a_kelas_that_is_really_in_use(): void
    {
        $this->kelas(attributes: ['nama' => 'TI-R8']);

        $this->seed(DemoSeeder::class);

        $demo = User::query()->where('npm', DemoSeeder::NPM_DEMO)->firstOrFail();
        $this->assertSame(DemoSeeder::KELAS_DEMO, $demo->kelas->nama);

        // Seeding again adds nothing, so it is safe to rerun on a live database.
        $jumlahKelas = Kelas::query()->count();
        $jumlahUser = User::query()->count();

        $this->seed(DemoSeeder::class);

        $this->assertSame($jumlahKelas, Kelas::query()->count());
        $this->assertSame($jumlahUser, User::query()->count());

        config(['demo.npm' => DemoSeeder::NPM_DEMO]);

        $this->get(route('demo'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($demo);
    }

    public function test_the_demo_banner_only_shows_for_the_demo_account(): void
    {
        $kelas = $this->kelas();
        $this->admin($kelas, ['npm' => '24010001']);

        config(['demo.npm' => '24010001']);

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Mode demo');
    }
}
