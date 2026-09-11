<?php

namespace Database\Factories;

use App\Models\JadwalKelas;
use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JadwalKelas>
 */
class JadwalKelasFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mulai = fake()->numberBetween(7, 15);

        return [
            'mata_kuliah_id' => MataKuliah::factory(),
            'hari' => fake()->numberBetween(1, 5),
            'jam_mulai' => sprintf('%02d:00', $mulai),
            'jam_selesai' => sprintf('%02d:40', $mulai + 1),
            'ruangan' => 'R.'.fake()->numberBetween(101, 405),
        ];
    }
}
