<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'npm' => fake()->unique()->numerify('23########'),
            'name' => fake()->name(),
            'no_hp' => '628'.fake()->numerify('##########'),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Mahasiswa,
            'kelas_id' => Kelas::factory(),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => Role::Admin]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => ['role' => Role::SuperAdmin, 'kelas_id' => null]);
    }
}
