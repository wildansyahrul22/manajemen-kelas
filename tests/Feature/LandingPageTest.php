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
            ->assertSee(route('login'));
    }

    public function test_a_signed_in_user_goes_straight_to_the_dashboard(): void
    {
        $this->actingAs($this->mahasiswa($this->kelas()))
            ->get(route('landing'))
            ->assertRedirect(route('dashboard'));
    }
}
