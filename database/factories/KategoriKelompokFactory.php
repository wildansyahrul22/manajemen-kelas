<?php

namespace Database\Factories;

use App\Models\KategoriKelompok;
use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KategoriKelompok>
 */
class KategoriKelompokFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mata_kuliah_id' => MataKuliah::factory(),
            'nama' => fake()->randomElement(['Project Akhir', 'Presentasi', 'Praktikum', 'Tugas Besar', 'Diskusi']).' '.fake()->numberBetween(1, 99),
        ];
    }
}
