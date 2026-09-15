<?php

namespace Database\Factories;

use App\Models\Informasi;
use App\Models\InformasiLampiran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InformasiLampiran>
 */
class InformasiLampiranFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->randomElement(['materi.pdf', 'jadwal.png', 'modul-praktikum.docx']);

        return [
            'informasi_id' => Informasi::factory(),
            'path' => Informasi::LAMPIRAN_DIR.'/'.fake()->uuid().'.'.pathinfo($nama, PATHINFO_EXTENSION),
            'nama' => $nama,
            'ukuran' => fake()->numberBetween(20_000, 2_000_000),
        ];
    }
}
