<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'TI-'.fake()->unique()->numberBetween(1, 9).fake()->randomElement(['A', 'B', 'C']),
            'prodi' => 'Teknik Informatika',
            'angkatan' => (int) now()->subYears(2)->format('Y'),
            'semester_aktif_id' => fn () => Semester::query()->where('nomor', 1)->value('id')
                ?? Semester::factory()->create(['nomor' => 1, 'nama' => 'Semester 1'])->id,
        ];
    }

    public function semester(int $nomor): static
    {
        return $this->state(fn () => [
            'semester_aktif_id' => Semester::query()->firstOrCreate(['nomor' => $nomor], ['nama' => "Semester {$nomor}"])->id,
        ]);
    }
}
