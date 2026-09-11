<?php

namespace Database\Factories;

use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Semester>
 */
class SemesterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nomor = fake()->unique()->numberBetween(1, Semester::JUMLAH);

        return [
            'nomor' => $nomor,
            'nama' => "Semester {$nomor}",
        ];
    }
}
