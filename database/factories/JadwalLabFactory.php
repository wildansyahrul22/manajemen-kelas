<?php

namespace Database\Factories;

use App\Models\JadwalLab;
use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JadwalLab>
 */
class JadwalLabFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mulai = fake()->numberBetween(7, 15);

        return [
            'mata_kuliah_id' => MataKuliah::factory(),
            'tanggal' => fake()->dateTimeBetween('+1 day', '+8 weeks')->format('Y-m-d'),
            'jam_mulai' => sprintf('%02d:00', $mulai),
            'jam_selesai' => sprintf('%02d:40', $mulai + 1),
            'ruangan' => 'Lab '.fake()->numberBetween(1, 4),
            'keterangan' => fake()->optional()->sentence(3),
        ];
    }

    public function lewat(): static
    {
        return $this->state(fn () => [
            'tanggal' => fake()->dateTimeBetween('-8 weeks', '-1 day')->format('Y-m-d'),
        ]);
    }
}
