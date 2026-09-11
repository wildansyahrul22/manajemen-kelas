<?php

namespace Database\Factories;

use App\Models\KategoriInformasi;
use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KategoriInformasi>
 */
class KategoriInformasiFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kelas_id' => Kelas::factory(),
            'nama' => fake()->unique()->randomElement(['Pengumuman', 'Akademik', 'Kegiatan', 'Keuangan', 'Umum', 'Penting']),
            'warna' => fake()->randomElement(array_keys(KategoriInformasi::WARNA)),
        ];
    }
}
