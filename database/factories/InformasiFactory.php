<?php

namespace Database\Factories;

use App\Models\Informasi;
use App\Models\KategoriInformasi;
use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Informasi>
 */
class InformasiFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kelas_id' => Kelas::factory(),
            'kategori_informasi_id' => fn (array $attributes) => KategoriInformasi::factory()->create(['kelas_id' => $attributes['kelas_id']])->id,
            'judul' => fake()->sentence(5),
            'isi' => fake()->paragraphs(2, true),
            'is_pinned' => false,
        ];
    }
}
