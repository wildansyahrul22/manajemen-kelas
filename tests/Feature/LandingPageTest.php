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

    public function test_a_signed_in_user_still_gets_the_landing_page_with_a_link_to_their_dashboard(): void
    {
        $this->actingAs($this->mahasiswa($this->kelas()))
            ->get(route('landing'))
            ->assertOk()
            ->assertSee('Buka dashboard')
            ->assertDontSee('Coba demo sekarang');
    }

    public function test_the_demo_call_to_action_only_shows_when_a_demo_account_is_configured(): void
    {
        $this->get(route('landing'))->assertDontSee('Coba demo sekarang');

        config(['demo.npm' => '24010001']);

        $this->get(route('landing'))
            ->assertSee('Coba demo sekarang')
            ->assertSee(route('demo'));
    }
}
