<?php

namespace Database\Factories;

use App\Models\Kampus;
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
            'kampus_id' => fn () => Kampus::query()->value('id') ?? Kampus::factory()->create()->id,
            'nama' => 'TI-'.fake()->unique()->numberBetween(1, 9).fake()->randomElement(['A', 'B', 'C']),
            'prodi' => 'Teknik Informatika',
            'angkatan' => (int) now()->subYears(2)->format('Y'),
            'semester_aktif_id' => fn () => Semester::query()->where('nomor', 1)->value('id')
                ?? Semester::factory()->create(['nomor' => 1, 'nama' => 'Semester 1'])->id,
            'masa_aktif_mulai' => null,
            'masa_aktif_selesai' => null,
            'upload' => true,
        ];
    }

    /** Subscription that ran out yesterday: its members can no longer sign in. */
    public function kedaluwarsa(): static
    {
        return $this->state(fn () => [
            'masa_aktif_mulai' => now()->subMonths(6)->toDateString(),
            'masa_aktif_selesai' => now()->subDay()->toDateString(),
        ]);
    }

    /** Subscription that has not started yet. */
    public function belumMulai(): static
    {
        return $this->state(fn () => [
            'masa_aktif_mulai' => now()->addWeek()->toDateString(),
            'masa_aktif_selesai' => now()->addMonths(6)->toDateString(),
        ]);
    }

    /** Plan without the upload features. */
    public function tanpaUpload(): static
    {
        return $this->state(fn () => ['upload' => false]);
    }

    public function semester(int $nomor): static
    {
        return $this->state(fn () => [
            'semester_aktif_id' => Semester::query()->firstOrCreate(['nomor' => $nomor], ['nama' => "Semester {$nomor}"])->id,
        ]);
    }
}
