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
            'deadline' => fake()->dateTimeBetween('+1 day', '+3 weeks'),
        ];
    }

    public function lewat(): static
    {
        return $this->state(fn () => ['deadline' => fake()->dateTimeBetween('-2 weeks', '-1 day')]);
    }
}
