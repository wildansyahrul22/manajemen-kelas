<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Layout\KelasFilter;
use App\Support\KelasContext;
use Livewire\Livewire;
use Tests\TestCase;

class KelasContextTest extends TestCase
{
    public function test_super_admin_can_switch_kelas_from_the_header(): void
    {
        $kelasA = $this->kelas(1, ['nama' => 'TI-1A']);
        $kelasB = $this->kelas(1, ['nama' => 'TI-1B']);
        $this->mahasiswa($kelasA);
        $this->mahasiswa($kelasB);
        $this->mahasiswa($kelasB);

        $superAdmin = $this->superAdmin();

        Livewire::actingAs($superAdmin)
            ->test(KelasFilter::class)
            ->set('kelasId', (string) $kelasB->id)
            ->assertRedirect();

        $this->assertSame($kelasB->id, session(KelasContext::SESSION_KEY));

        Livewire::actingAs($superAdmin)
            ->test(Dashboard::class)
            ->assertSee('TI-1B')
            ->assertSet('ringkasan.mahasiswa', 2);
    }

    public function test_mahasiswa_cannot_switch_kelas(): void
    {
        $kelasA = $this->kelas();
        $kelasB = $this->kelas();

        Livewire::actingAs($this->mahasiswa($kelasA))
            ->test(KelasFilter::class)
            ->set('kelasId', (string) $kelasB->id)
            ->assertForbidden();
    }

    public function test_context_follows_the_authenticated_user_within_one_process(): void
    {
        $kelasA = $this->kelas(1, ['nama' => 'TI-1A']);
        $kelasB = $this->kelas(1, ['nama' => 'TI-1B']);

        $this->actingAs($this->mahasiswa($kelasA))->get(route('dashboard'))->assertOk()->assertSee('TI-1A')->assertDontSee('TI-1B');
        $this->actingAs($this->mahasiswa($kelasB))->get(route('dashboard'))->assertOk()->assertSee('TI-1B')->assertDontSee('TI-1A');
    }

    public function test_dashboard_counts_students_of_own_kelas_only(): void
    {
        $kelas = $this->kelas();
        $user = $this->mahasiswa($kelas);
        $this->admin($kelas);
        $this->mahasiswa($this->kelas());

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSet('ringkasan.mahasiswa', 2);
    }
}
