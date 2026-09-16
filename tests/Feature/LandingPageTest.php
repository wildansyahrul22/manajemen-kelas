<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_guests_see_the_subscription_offer_with_both_plans_and_the_contacts(): void
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Kelas KampusKu')
            ->assertSee('Rp200.000')
            ->assertSee('Rp250.000')
            ->assertSee('Promo langganan pertama')
            ->assertSee('62812790106175')
            ->assertSee('admin@kelaskampusku.com')
            ->assertSee(asset('images/dashboard.png'))
            ->assertSee(route('login'));
    }

    public function test_the_landing_page_says_only_admin_kelas_upload_and_never_mentions_a_super_admin(): void
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Mengunggah file dan gambar juga hanya bisa dilakukan admin kelas')
            ->assertDontSee('super admin', escape: false);
    }

    public function test_a_signed_in_user_still_gets_the_landing_page_with_a_link_to_their_dashboard(): void
    {
        config(['demo.npm' => '24010001']);

        $this->actingAs($this->mahasiswa($this->kelas()))
            ->get(route('landing'))
            ->assertOk()
            ->assertSee('Buka dashboard')
            ->assertDontSee('Coba demo sekarang');
    }

    public function test_the_demo_account_is_still_offered_the_login_and_the_demo_not_a_dashboard(): void
    {
        $demo = $this->admin($this->kelas(), ['npm' => '24010001']);

        config(['demo.npm' => '24010001']);

        $this->actingAs($demo)
            ->get(route('landing'))
            ->assertOk()
            ->assertSee('Coba demo sekarang')
            ->assertSee('Masuk')
            ->assertDontSee('Buka dashboard');
    }

    public function test_the_demo_call_to_action_only_shows_when_a_demo_account_is_configured(): void
    {
        config(['demo.npm' => null]);

        $this->get(route('landing'))->assertDontSee('Coba demo sekarang');

        config(['demo.npm' => '24010001']);

        $this->get(route('landing'))
            ->assertSee('Coba demo sekarang')
            ->assertSee(route('demo'));
    }
}
