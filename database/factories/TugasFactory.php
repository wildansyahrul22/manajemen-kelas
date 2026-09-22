<?php

namespace Database\Factories;

use App\Models\MataKuliah;
use App\Models\Tugas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tugas>
 */
class TugasFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mata_kuliah_id' => MataKuliah::factory(),
            'nama' => fake()->randomElement(['Tugas', 'Laporan', 'Kuis', 'Project']).' '.fake()->numberBetween(1, 5),
            'deskripsi' => fake()->paragraph(),
            // Relative to now() (not faker's clock) so tests that travel in time still get the right side of the deadline.
            'deadline' => now()->addDays(fake()->numberBetween(1, 21))->setTime(fake()->numberBetween(8, 23), fake()->randomElement([0, 30])),
        ];
    }

    public function lewat(): static
    {
        return $this->state(fn () => ['deadline' => now()->subDays(fake()->numberBetween(1, 14))->setTime(fake()->numberBetween(8, 23), fake()->randomElement([0, 30]))]);
    }
}
