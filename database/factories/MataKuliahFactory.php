<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MataKuliah>
 */
class MataKuliahFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kelas_id' => Kelas::factory(),
            'semester_id' => fn (array $attributes) => Kelas::query()->whereKey($attributes['kelas_id'])->value('semester_aktif_id'),
            'kode' => strtoupper(fake()->bothify('IF###')),
            'nama' => fake()->randomElement([
                'Pemrograman Web', 'Basis Data', 'Struktur Data', 'Jaringan Komputer',
                'Sistem Operasi', 'Rekayasa Perangkat Lunak', 'Kecerdasan Buatan', 'Matematika Diskrit',
            ]),
            'dosen' => fake()->name(),
            'sks' => fake()->randomElement([2, 3, 4]),
        ];
    }
}
