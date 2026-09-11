<?php

namespace Tests;

use App\Enums\Role;
use App\Models\Kelas;
use App\Models\User;
use Database\Seeders\SemesterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SemesterSeeder::class);
    }

    protected function kelas(int $semester = 3, array $attributes = []): Kelas
    {
        return Kelas::factory()->semester($semester)->create($attributes);
    }

    protected function mahasiswa(Kelas $kelas, array $attributes = []): User
    {
        return User::factory()->create([...$attributes, 'kelas_id' => $kelas->id, 'role' => Role::Mahasiswa]);
    }

    protected function admin(Kelas $kelas, array $attributes = []): User
    {
        return User::factory()->admin()->create([...$attributes, 'kelas_id' => $kelas->id]);
    }

    protected function superAdmin(array $attributes = []): User
    {
        return User::factory()->superAdmin()->create($attributes);
    }
}
