<?php

namespace Database\Factories;

use App\Models\Tugas;
use App\Models\TugasLampiran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TugasLampiran>
 */
class TugasLampiranFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->randomElement(['soal.pdf', 'template-laporan.docx', 'contoh-output.png']);

        return [
            'tugas_id' => Tugas::factory(),
            'path' => Tugas::LAMPIRAN_DIR.'/'.fake()->uuid().'.'.pathinfo($nama, PATHINFO_EXTENSION),
            'nama' => $nama,
            'ukuran' => fake()->numberBetween(20_000, 2_000_000),
        ];
    }
}
