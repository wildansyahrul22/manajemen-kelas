<?php

namespace Tests\Feature;

use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    public function test_mahasiswa_can_open_read_only_pages(): void
    {
        $user = $this->mahasiswa($this->kelas());

        foreach (['dashboard', 'tugas.index', 'jadwal.index', 'mata-kuliah.index', 'informasi.index', 'kategori-informasi.index', 'kelompok.index', 'kategori-kelompok.index', 'profile.edit'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_mahasiswa_cannot_open_management_pages(): void
    {
        $user = $this->mahasiswa($this->kelas());

        foreach (['users.index', 'semester-aktif.index', 'kelas.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }

    public function test_admin_can_manage_users_and_semester_but_not_kelas(): void
    {
        $admin = $this->admin($this->kelas());

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('semester-aktif.index'))->assertOk();
        $this->actingAs($admin)->get(route('kelas.index'))->assertForbidden();
    }

    public function test_super_admin_has_full_access(): void
    {
        $this->kelas();
        $superAdmin = $this->superAdmin();

        foreach (['dashboard', 'users.index', 'semester-aktif.index', 'kelas.index'] as $route) {
            $this->actingAs($superAdmin)->get(route($route))->assertOk();
        }
    }

    public function test_super_admin_without_any_kelas_is_sent_to_kelas_page(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dashboard'))
            ->assertRedirect(route('kelas.index'));
    }

    public function test_mahasiswa_does_not_see_crud_buttons_on_tugas_page(): void
    {
        $kelas = $this->kelas();

        $this->actingAs($this->mahasiswa($kelas))
            ->get(route('tugas.index'))
            ->assertOk()
            ->assertDontSee('Tambah Tugas');

        $this->actingAs($this->admin($kelas))
            ->get(route('tugas.index'))
            ->assertOk()
            ->assertSee('Tambah Tugas');
    }
}
