<?php

namespace Database\Factories;

use App\Models\KategoriKelompok;
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
            'kategori_kelompok_id' => fn (array $attributes) => KategoriKelompok::factory()->create(['mata_kuliah_id' => $attributes['mata_kuliah_id']])->id,
            'nama' => 'Kelompok '.fake()->numberBetween(1, 8),
            'deskripsi' => fake()->sentence(),
        ];
    }

    /**
     * Keep mata_kuliah_id in sync with the kategori when a kategori is passed explicitly.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Kelompok $kelompok) {
            $kelompok->mata_kuliah_id = KategoriKelompok::query()->whereKey($kelompok->kategori_kelompok_id)->value('mata_kuliah_id');
        });
    }
}
