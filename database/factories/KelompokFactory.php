<?php

namespace Database\Factories;

use App\Models\Kelompok;
use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelompok>
 */
class KelompokFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mata_kuliah_id' => MataKuliah::factory(),
            'nama' => 'Kelompok '.fake()->numberBetween(1, 8),
            'deskripsi' => fake()->sentence(),
        ];
    }
}
