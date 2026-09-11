<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, Semester::JUMLAH) as $nomor) {
            Semester::query()->firstOrCreate(['nomor' => $nomor], ['nama' => "Semester {$nomor}"]);
        }
    }
}
